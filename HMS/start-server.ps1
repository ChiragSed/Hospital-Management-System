# LifeLine HMS - Server Launcher PowerShell Orchestrator
# Checks environment, starts MySQL, cleans up old servers, and starts PHP server.

$phpPort = 8000
$mysqlPort = 3306

Clear-Host
Write-Host "=====================================================" -ForegroundColor Green
Write-Host "   LifeLine Hospital Management System Server" -ForegroundColor Green
Write-Host "=====================================================" -ForegroundColor Green
Write-Host ""

# 1. Detect PHP Executable
$phpPath = "php"
if (Test-Path "C:\xampp\php\php.exe") {
    $phpPath = "C:\xampp\php\php.exe"
    Write-Host "[*] Found XAMPP PHP: $phpPath" -ForegroundColor Gray
} else {
    $commandCheck = Get-Command php -ErrorAction SilentlyContinue
    if ($commandCheck) {
        $phpPath = $commandCheck.Source
        Write-Host "[*] Found system PHP: $phpPath" -ForegroundColor Gray
    } else {
        Write-Host "[ERROR] PHP not found in C:\xampp\php\php.exe or system PATH!" -ForegroundColor Red
        Write-Host "Please ensure XAMPP or PHP is installed." -ForegroundColor Red
        Read-Host "Press Enter to exit..."
        Exit
    }
}

# 2. Check and start MySQL
Write-Host "[*] Checking MySQL Database status..." -ForegroundColor Gray
$mysqlRunning = $false
$connection = New-Object System.Net.Sockets.TcpClient
try {
    $connection.Connect("127.0.0.1", $mysqlPort)
    $mysqlRunning = $true
    $connection.Close()
    Write-Host "[+] MySQL is already running on port $mysqlPort." -ForegroundColor Green
} catch {
    $mysqlRunning = $false
}

if (-not $mysqlRunning) {
    Write-Host "[-] MySQL is not running. Attempting to start XAMPP MySQL..." -ForegroundColor Yellow
    if (Test-Path "C:\xampp\mysql\bin\mysqld.exe") {
        try {
            # Start MySQL in background
            Start-Process -FilePath "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini", "--standalone" -WindowStyle Hidden
            
            # Wait for MySQL to start up (up to 6 seconds)
            for ($i = 1; $i -le 6; $i++) {
                Start-Sleep -Seconds 1
                $testConn = New-Object System.Net.Sockets.TcpClient
                try {
                    $testConn.Connect("127.0.0.1", $mysqlPort)
                    $testConn.Close()
                    $mysqlRunning = $true
                    break
                } catch {
                    # Continue waiting
                }
            }
            if ($mysqlRunning) {
                Write-Host "[+] XAMPP MySQL started successfully on port $mysqlPort." -ForegroundColor Green
            } else {
                Write-Host "[WARNING] MySQL started but is taking too long to respond. It may still be loading." -ForegroundColor Yellow
            }
        } catch {
            Write-Host "[ERROR] Failed to launch XAMPP MySQL: $_" -ForegroundColor Red
        }
    } else {
        # Check if MySQL service MySQL80 exists and start it if possible
        $mysqlService = Get-Service -Name MySQL80 -ErrorAction SilentlyContinue
        if ($mysqlService) {
            if ($mysqlService.Status -eq "Stopped") {
                Write-Host "[*] Attempting to start MySQL80 Windows Service..." -ForegroundColor Yellow
                try {
                    Start-Service -Name MySQL80 -ErrorAction Stop
                    Write-Host "[+] MySQL80 service started successfully." -ForegroundColor Green
                    $mysqlRunning = $true
                } catch {
                    Write-Host "[WARNING] Failed to start MySQL80 service. You might need administrator privileges: $_" -ForegroundColor Yellow
                }
            } else {
                Write-Host "[+] MySQL80 service is already running." -ForegroundColor Green
                $mysqlRunning = $true
            }
        } else {
            Write-Host "[WARNING] XAMPP MySQL not found and no MySQL80 service detected. Please start your database manually." -ForegroundColor Yellow
        }
    }
}

# 3. Clean up existing PHP server instances on target port
Write-Host "[*] Checking port $phpPort for existing PHP servers..." -ForegroundColor Gray
$connections = Get-NetTCPConnection -LocalPort $phpPort -ErrorAction SilentlyContinue
if ($connections) {
    foreach ($conn in $connections) {
        if ($conn.OwningProcess) {
            $proc = Get-Process -Id $conn.OwningProcess -ErrorAction SilentlyContinue
            if ($proc -and ($proc.Name -eq "php" -or $proc.Name -eq "php-cgi")) {
                Write-Host "[*] Found existing PHP process ($($proc.Id)) on port $phpPort. Terminating it to start fresh..." -ForegroundColor Yellow
                try {
                    Stop-Process -Id $proc.Id -Force -ErrorAction Stop
                    Start-Sleep -Milliseconds 500
                    Write-Host "[+] Terminated process $($proc.Id)." -ForegroundColor Green
                } catch {
                    Write-Host "[WARNING] Could not terminate process $($proc.Id): $_" -ForegroundColor Yellow
                }
            }
        }
    }
}

# Double check if port is still in use (by some other application)
$portInUse = $false
$connection = New-Object System.Net.Sockets.TcpClient
try {
    $connection.Connect("127.0.0.1", $phpPort)
    $portInUse = $true
    $connection.Close()
} catch {
    $portInUse = $false
}

if ($portInUse) {
    Write-Host "[WARNING] Port $phpPort is in use by another application. Finding another port..." -ForegroundColor Yellow
    for ($altPort = 8001; $altPort -le 8010; $altPort++) {
        $testConn = New-Object System.Net.Sockets.TcpClient
        try {
            $testConn.Connect("127.0.0.1", $altPort)
            $testConn.Close()
        } catch {
            $phpPort = $altPort
            break
        }
    }
    Write-Host "[+] Selected alternative port $phpPort." -ForegroundColor Green
}

# 4. Open default browser and check for Browser-Sync live reload
$appUrl = "http://localhost:$phpPort"
$browserSyncPath = "$PSScriptRoot\node_modules\browser-sync"
if (Test-Path $browserSyncPath) {
    Write-Host "[*] browser-sync detected. Starting live-reload proxy in background..." -ForegroundColor Cyan
    # Terminate any existing browser-sync processes to prevent conflicts
    Get-Process node -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like "*browser-sync*" } | Stop-Process -Force -ErrorAction SilentlyContinue
    
    # Start browser-sync proxying the PHP port
    Start-Process npx -ArgumentList "browser-sync", "start", "--proxy", "localhost:$phpPort", "--files", "**/*.php, **/*.css, **/*.js, **/*.html, config/**/*.php, includes/**/*.php", "--no-open" -WindowStyle Hidden
    
    # We open http://localhost:3000 instead which is the browser-sync proxy URL
    $appUrl = "http://localhost:3000"
}

Write-Host "[*] Launching web browser at $appUrl..." -ForegroundColor Gray
try {
    Start-Process $appUrl
} catch {
    Write-Host "[WARNING] Could not open browser automatically: $_" -ForegroundColor Yellow
}

# 5. Display Dashboard and start server
Clear-Host
Write-Host "=====================================================" -ForegroundColor Green
Write-Host "   LifeLine Hospital Management System Server" -ForegroundColor Green
Write-Host "   Nepal Localized Edition" -ForegroundColor Green
Write-Host "=====================================================" -ForegroundColor Green
Write-Host ""
Write-Host "   [*] Status:" -ForegroundColor Green
if ($mysqlRunning) {
    Write-Host "       - Database : MySQL is RUNNING (Port 3306)" -ForegroundColor Green
} else {
    Write-Host "       - Database : MySQL (Port 3306) status check bypass / offline" -ForegroundColor Yellow
}
Write-Host "       - Server   : PHP Web Server is RUNNING at $appUrl" -ForegroundColor Green
Write-Host ""
Write-Host "   [*] Credentials:" -ForegroundColor Cyan
Write-Host "       -----------------------------------------------------------" -ForegroundColor Gray
Write-Host "       Role     : Login Email                      | Password" -ForegroundColor Cyan
Write-Host "       -----------------------------------------------------------" -ForegroundColor Gray
Write-Host "       Patient  : chirag@gmail.com                 | patient123" -ForegroundColor Gray
Write-Host "       Doctor   : suman.shrestha@lifeline.com.np   | doctor123" -ForegroundColor Gray
Write-Host "       Admin    : admin@hospital.com               | admin123" -ForegroundColor Gray
Write-Host "       -----------------------------------------------------------" -ForegroundColor Gray
Write-Host ""
Write-Host "   Server logs will appear below. Press CTRL+C to stop the server." -ForegroundColor Yellow
Write-Host ""

# Start the PHP server in the folder containing this script
& $phpPath -S "localhost:$phpPort" -t "$PSScriptRoot"
