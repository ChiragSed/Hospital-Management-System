<?php
/**
 * Database Setup and Seeding Script
 * Hospital Management System - Nepal Localized
 */

header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hospital';

// IMPORTANT: Define flush_log() BEFORE it is called below
function flush_log($msg, $type = "info") {
    $class = "log-info";
    $prefix = "[INFO]";
    if ($type == "success") {
        $class = "log-success";
        $prefix = "[SUCCESS]";
    } elseif ($type == "error") {
        $class = "log-error";
        $prefix = "[ERROR]";
    }
    echo "<div class='{$class}'>{$prefix} " . htmlspecialchars($msg) . "</div>";
    if (ob_get_level() > 0) ob_flush();
    flush();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HMS Database Setup &amp; Seeding</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .setup-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            background: #ffffff;
        }
        .log-box {
            background-color: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Courier New', Courier, monospace;
            padding: 15px;
            border-radius: 8px;
            max-height: 350px;
            overflow-y: auto;
            font-size: 0.9rem;
        }
        .log-success { color: #4caf50; }
        .log-error { color: #f44336; }
        .log-info { color: #00bcd4; }
        .btn-primary {
            background-color: #0D6EFD;
            border-color: #0D6EFD;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="setup-card card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="text-primary mb-2"><i class="fa-solid fa-hospital-user fa-3x"></i></div>
                    <h2 class="fw-bold">HMS Setup Wizard</h2>
                    <p class="text-muted">Database configuration and Nepal-localized mock data seeding utility</p>
                </div>

                <div class="log-box mb-4" id="log-container">
                    <?php
                    flush_log("Connecting to MySQL server at localhost...", "info");
                    
                    try {
                        // Connect to MySQL server first (without DB name)
                        $pdo = new PDO("mysql:host=$host", $username, $password, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        ]);
                        flush_log("MySQL connection successful!", "success");

                        // Create database if not exists
                        flush_log("Creating database 'hospital' (if it does not exist)...", "info");
                        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        flush_log("Database 'hospital' initialized.", "success");

                        // Re-connect to database
                        $pdo->exec("USE `$database`");
                        flush_log("Switched context to database '$database'.", "info");

                        // Read and execute schema
                        $schemaFile = __DIR__ . '/schema.sql';
                        if (!file_exists($schemaFile)) {
                            throw new Exception("Schema file schema.sql not found at $schemaFile");
                        }
                        
                        flush_log("Reading schema.sql file...", "info");
                        $sql = file_get_contents($schemaFile);
                        
                        flush_log("Executing schema SQL queries...", "info");
                        // Split by semicolons, but respect MySQL formatting
                        $queries = array_filter(array_map('trim', explode(';', $sql)));
                        foreach ($queries as $index => $query) {
                            if (!empty($query)) {
                                $pdo->exec($query);
                            }
                        }
                        flush_log("Tables generated successfully (" . count($queries) . " statements executed).", "success");

                        // Seeding data
                        flush_log("Starting Nepal-localized data seeding...", "info");

                        // 1. Departments Seeding
                        $depts = [
                            ['General Medicine', 'Primary healthcare, diagnoses, and non-surgical treatment.', 'fa-stethoscope'],
                            ['Cardiology', 'Heart health and cardiovascular diagnoses.', 'fa-heart-pulse'],
                            ['Neurology', 'Brain and nervous system disorders.', 'fa-brain'],
                            ['Orthopedics', 'Musculoskeletal system, bones, and joints.', 'fa-bone'],
                            ['Pediatrics', 'Infant, child, and adolescent healthcare.', 'fa-baby'],
                            ['Dermatology', 'Skin, hair, and nail treatments.', 'fa-hand-dots'],
                            ['ENT', 'Ear, Nose, and Throat specializations.', 'fa-ear-listen'],
                            ['Gynecology', 'Women reproductive health services.', 'fa-person-dress'],
                            ['Psychiatry', 'Mental wellness, therapy, and cognitive disorders.', 'fa-head-side-virus'],
                            ['Dental', 'Oral care, dentistry, and smile corrections.', 'fa-tooth']
                        ];
                        
                        $stmt = $pdo->prepare("INSERT INTO `departments` (name, description, icon) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE description=VALUES(description), icon=VALUES(icon)");
                        foreach ($depts as $dept) {
                            $stmt->execute($dept);
                        }
                        flush_log("Departments seeded successfully.", "success");

                        // Get department IDs for references
                        $dept_ids = $pdo->query("SELECT name, id FROM departments")->fetchAll(PDO::FETCH_KEY_PAIR);

                        // 2. Admins Seeding
                        $adminEmail = 'admin@hospital.com';
                        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                        $adminName = 'Dr. Admin Director';
                        
                        $stmt = $pdo->prepare("INSERT INTO `admins` (name, email, password, phone, status) VALUES (?, ?, ?, ?, 'active') ON DUPLICATE KEY UPDATE password=VALUES(password)");
                        $stmt->execute([$adminName, $adminEmail, $adminPassword, '01-4256789']);
                        flush_log("Default Admin seeded (Email: admin@hospital.com, Password: admin123).", "success");
                        $adminId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM admins WHERE email = 'admin@hospital.com'")->fetchColumn();

                        // 3. Doctors Seeding (Nepal-localized)
                        $doctors = [
                            [
                                'Dr. Suman Shrestha',
                                'suman.shrestha@lifeline.com.np',
                                password_hash('doctor123', PASSWORD_DEFAULT),
                                '9841234567',
                                $dept_ids['Cardiology'],
                                'MD - Cardiology, FACC',
                                'Interventional Cardiology & Echocardiography',
                                14,
                                1200.00,
                                'approved',
                                'Monday,Wednesday,Friday',
                                '09:00:00',
                                '15:00:00'
                            ],
                            [
                                'Dr. Priya Sharma',
                                'priya.sharma@lifeline.com.np',
                                password_hash('doctor123', PASSWORD_DEFAULT),
                                '9851122334',
                                $dept_ids['Pediatrics'],
                                'MD - Pediatrics, DCH',
                                'Neonatal Care & Child Health',
                                9,
                                700.00,
                                'approved',
                                'Tuesday,Thursday,Saturday',
                                '10:00:00',
                                '17:00:00'
                            ],
                            [
                                'Dr. Anish Acharya',
                                'anish.acharya@lifeline.com.np',
                                password_hash('doctor123', PASSWORD_DEFAULT),
                                '9861234560',
                                $dept_ids['Neurology'],
                                'MD, PhD - Neurology',
                                'Neurodegenerative Disorders & Epilepsy',
                                16,
                                1500.00,
                                'pending',
                                'Monday,Tuesday,Wednesday',
                                '09:00:00',
                                '16:00:00'
                            ],
                            [
                                'Dr. Ramesh Koirala',
                                'ramesh.koirala@lifeline.com.np',
                                password_hash('doctor123', PASSWORD_DEFAULT),
                                '9841112233',
                                $dept_ids['General Medicine'],
                                'MBBS, MD - Internal Medicine',
                                'Chronic Disease Management & Primary Care',
                                11,
                                500.00,
                                'approved',
                                'Monday,Tuesday,Wednesday,Thursday,Friday',
                                '09:00:00',
                                '17:00:00'
                            ],
                            [
                                'Dr. Nabin Gautam',
                                'nabin.gautam@lifeline.com.np',
                                password_hash('doctor123', PASSWORD_DEFAULT),
                                '9865432100',
                                $dept_ids['Orthopedics'],
                                'MS - Orthopedics',
                                'Joint Replacement & Sports Injuries',
                                12,
                                1000.00,
                                'approved',
                                'Monday,Wednesday,Friday',
                                '10:00:00',
                                '16:00:00'
                            ],
                            [
                                'Dr. Ritu Poudel',
                                'ritu.poudel@lifeline.com.np',
                                password_hash('doctor123', PASSWORD_DEFAULT),
                                '9819988776',
                                $dept_ids['Dermatology'],
                                'MD - Dermatology',
                                'Skin Disorders, Cosmetic Dermatology',
                                7,
                                800.00,
                                'approved',
                                'Tuesday,Thursday',
                                '11:00:00',
                                '16:00:00'
                            ]
                        ];

                        $stmt = $pdo->prepare("INSERT INTO `doctors` (name, email, password, phone, department_id, qualification, specialization, experience, consultation_fee, status, available_days, available_time_start, available_time_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status)");
                        foreach ($doctors as $doc) {
                            $stmt->execute($doc);
                        }
                        flush_log("Nepal-localized Doctors seeded (approved and pending).", "success");

                        // Get doctor IDs for references
                        $doc_ids = $pdo->query("SELECT name, id FROM doctors")->fetchAll(PDO::FETCH_KEY_PAIR);

                        // 4. Patients Seeding (Nepal-localized)
                        $patients = [
                            [
                                'Aarav Shrestha',
                                'chirag@gmail.com',
                                password_hash('patient123', PASSWORD_DEFAULT),
                                25,
                                'Male',
                                'O+',
                                '9841234567',
                                'New Baneshwor, Kathmandu',
                                'Sujata Shrestha',
                                '9851234567',
                                175.00,
                                70.00,
                                22.86
                            ],
                            [
                                'Sita Poudel',
                                'sita@gmail.com',
                                password_hash('patient123', PASSWORD_DEFAULT),
                                38,
                                'Female',
                                'AB-',
                                '9865100200',
                                'Jawalakhel, Lalitpur',
                                'Ram Poudel',
                                '9801234000',
                                160.00,
                                55.00,
                                21.48
                            ]
                        ];
                        
                        $stmt = $pdo->prepare("INSERT INTO `patients` (name, email, password, age, gender, blood_group, phone, address, emergency_contact_name, emergency_contact_phone, height, weight, bmi, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active') ON DUPLICATE KEY UPDATE email=email");
                        foreach ($patients as $pat) {
                            $stmt->execute($pat);
                        }
                        flush_log("Nepal-localized Patients seeded (chirag@gmail.com &amp; sita@gmail.com).", "success");

                        // Get patient IDs by email for reliable lookup (handles existing records)
                        $pat_aarav = $pdo->query("SELECT id FROM patients WHERE email = 'chirag@gmail.com' LIMIT 1")->fetchColumn();
                        $pat_sita  = $pdo->query("SELECT id FROM patients WHERE email = 'sita@gmail.com' LIMIT 1")->fetchColumn();
                        $pat_ids = [
                            'Aarav Shrestha' => $pat_aarav,
                            'Sita Poudel'    => $pat_sita,
                        ];

                        // 5. Laboratories Seeding (Nepal)
                        $labs = [
                            ['National Reference Laboratory', 'Kathmandu', 'Teku, Kathmandu', '01-4255629'],
                            ['Star Hospital Lab', 'Kathmandu', 'Maharajgunj, Kathmandu', '01-4478899'],
                            ['Nepal Mediciti Lab', 'Lalitpur', 'Bhaisepati, Lalitpur', '01-5970032'],
                            ['Grande Lab Services', 'Kathmandu', 'Dhapasi, Kathmandu', '01-5159266'],
                            ['Lal PathLabs Nepal', 'Pokhara', 'Lakeside, Pokhara', '061-522001']
                        ];
                        $stmt = $pdo->prepare("INSERT INTO `laboratories` (name, city, address, phone) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE phone=VALUES(phone)");
                        foreach ($labs as $lab) {
                            $stmt->execute($lab);
                        }
                        flush_log("Nepal laboratories seeded.", "success");

                        // Get lab IDs
                        $lab_ids = $pdo->query("SELECT name, id FROM laboratories")->fetchAll(PDO::FETCH_KEY_PAIR);

                        // 6. Lab Tests Seeding (NPR pricing)
                        $tests = [
                            [$lab_ids['National Reference Laboratory'], 'Complete Blood Count (CBC)', 600.00, 'Measures red & white cells, hemoglobin, and platelets.'],
                            [$lab_ids['National Reference Laboratory'], 'Lipid Panel', 1200.00, 'Evaluates cholesterol, HDL, LDL, and triglycerides.'],
                            [$lab_ids['Star Hospital Lab'], 'Thyroid Function Test (T3, T4, TSH)', 1500.00, 'Assesses overall metabolic thyroid hormone regulation.'],
                            [$lab_ids['Star Hospital Lab'], 'HbA1c Diabetes Test', 900.00, 'Measures average blood sugar levels over the past 3 months.'],
                            [$lab_ids['Nepal Mediciti Lab'], 'Cardiography ECG', 800.00, 'Electrocardiogram to monitor electrical signals in heart.'],
                            [$lab_ids['Nepal Mediciti Lab'], 'Liver Function Test (LFT)', 1100.00, 'Evaluates liver enzymes, bilirubin, and protein levels.'],
                            [$lab_ids['Grande Lab Services'], 'Urine Routine Examination', 350.00, 'Basic urinalysis covering color, pH, glucose, proteins.'],
                            [$lab_ids['Grande Lab Services'], 'X-Ray Chest PA View', 700.00, 'Posteroanterior chest radiograph for lungs and heart.'],
                            [$lab_ids['Lal PathLabs Nepal'], 'Dengue NS1 Antigen Test', 1800.00, 'Early detection of dengue virus NS1 antigen.'],
                            [$lab_ids['Lal PathLabs Nepal'], 'Vitamin D Total (25-OH)', 2500.00, 'Measures overall Vitamin D levels in blood serum.']
                        ];
                        $stmt = $pdo->prepare("INSERT INTO `lab_tests` (lab_id, test_name, price, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE price=VALUES(price)");
                        foreach ($tests as $test) {
                            $stmt->execute($test);
                        }
                        flush_log("Nepal lab tests catalog seeded (NPR pricing).", "success");

                        $test_ids = $pdo->query("SELECT test_name, id FROM lab_tests")->fetchAll(PDO::FETCH_KEY_PAIR);

                        // 7. Appointments Seeding
                        $today = date('Y-m-d');
                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                        $tomorrow = date('Y-m-d', strtotime('+1 day'));

                        $appointments = [
                            [$pat_ids['Aarav Shrestha'], $doc_ids['Dr. Suman Shrestha'], $dept_ids['Cardiology'], $yesterday, '10:30:00', 'Completed', 'Routine cardiac checkup and ECG follow-up.'],
                            [$pat_ids['Aarav Shrestha'], $doc_ids['Dr. Suman Shrestha'], $dept_ids['Cardiology'], $tomorrow, '11:00:00', 'Approved', 'Blood pressure monitoring and medication review.'],
                            [$pat_ids['Sita Poudel'], $doc_ids['Dr. Priya Sharma'], $dept_ids['Pediatrics'], $today, '14:30:00', 'Pending', 'Child vaccination schedule consult.'],
                            [$pat_ids['Aarav Shrestha'], $doc_ids['Dr. Ramesh Koirala'], $dept_ids['General Medicine'], $yesterday, '09:00:00', 'Completed', 'Mild fever, cough, and fatigue consult.']
                        ];

                        $stmt = $pdo->prepare("INSERT INTO `appointments` (patient_id, doctor_id, department_id, appointment_date, time_slot, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        foreach ($appointments as $appt) {
                            $stmt->execute($appt);
                        }
                        flush_log("Sample Appointments seeded.", "success");

                        // Get appointment IDs for diagnoses/prescriptions
                        $appt_ids = $pdo->query("SELECT id FROM appointments")->fetchAll(PDO::FETCH_COLUMN);

                        // 8. Diagnoses & Prescriptions Seeding
                        if (!empty($appt_ids)) {
                            // Diagnosis for Aarav (yesterday's General Medicine)
                            $stmtDiag = $pdo->prepare("INSERT INTO `diagnoses` (appointment_id, patient_id, doctor_id, diagnosis, treatment_plan, notes) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmtDiag->execute([
                                $appt_ids[3],
                                $pat_ids['Aarav Shrestha'],
                                $doc_ids['Dr. Ramesh Koirala'],
                                'Acute Viral Pharyngitis',
                                'Symptomatic therapy, warm fluid hydration, rest for 5 days.',
                                'Patient reported mild scratchy throat and low-grade fever of 99.8°F.'
                            ]);
                            $diagId = $pdo->lastInsertId();

                            // Prescription for Aarav (NPR-context medicines)
                            $medicines = "[\n  {\"name\": \"Paracetamol 500mg\", \"dosage\": \"1 tablet\", \"frequency\": \"Three times daily as needed\", \"duration\": \"5 Days\"},\n  {\"name\": \"Amoxicillin 500mg\", \"dosage\": \"1 capsule\", \"frequency\": \"Twice daily after meals\", \"duration\": \"7 Days\"},\n  {\"name\": \"Cetirizine 10mg\", \"dosage\": \"1 tablet\", \"frequency\": \"Once at bedtime\", \"duration\": \"5 Days\"}\n]";
                            $stmtPres = $pdo->prepare("INSERT INTO `prescriptions` (appointment_id, patient_id, doctor_id, prescription_date, symptoms, medicines, instructions) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $stmtPres->execute([
                                $appt_ids[3],
                                $pat_ids['Aarav Shrestha'],
                                $doc_ids['Dr. Ramesh Koirala'],
                                $yesterday,
                                'Sore throat, mild fever (37.8°C), body aches, fatigue.',
                                $medicines,
                                'Take antibiotics strictly after meals. Complete full antibiotic course. Drink warm fluids. Avoid cold beverages.'
                            ]);
                            $presId = $pdo->lastInsertId();

                            // Populate Medical Records
                            $stmtRec = $pdo->prepare("INSERT INTO `medical_records` (patient_id, record_type, reference_id, description, date_recorded) VALUES (?, ?, ?, ?, ?)");
                            $stmtRec->execute([$pat_ids['Aarav Shrestha'], 'Diagnosis', $diagId, 'Diagnosed with Acute Viral Pharyngitis by Dr. Ramesh Koirala', $yesterday]);
                            $stmtRec->execute([$pat_ids['Aarav Shrestha'], 'Prescription', $presId, 'Prescribed Paracetamol, Amoxicillin & Cetirizine by Dr. Ramesh Koirala', $yesterday]);
                        }
                        flush_log("Sample Diagnoses, Prescriptions, and Medical Records linked.", "success");

                        // 9. Lab Bookings Seeding
                        $stmt = $pdo->prepare("INSERT INTO `lab_bookings` (patient_id, lab_test_id, booking_date, status) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$pat_ids['Aarav Shrestha'], $test_ids['Complete Blood Count (CBC)'], $yesterday, 'Completed']);
                        $stmt->execute([$pat_ids['Sita Poudel'], $test_ids['Thyroid Function Test (T3, T4, TSH)'], $today, 'Pending']);
                        flush_log("Lab Bookings seeded.", "success");

                        // 10. Feedback Seeding
                        $stmt = $pdo->prepare("INSERT INTO `feedback` (patient_id, doctor_id, rating, comments) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$pat_ids['Aarav Shrestha'], $doc_ids['Dr. Ramesh Koirala'], 5, 'Excellent consultation. Dr. Koirala listens patiently and explains the treatment clearly. Highly recommended.']);
                        $stmt->execute([$pat_ids['Aarav Shrestha'], $doc_ids['Dr. Suman Shrestha'], 4, 'Very professional cardiologist. ECG results were explained well. Great experience at LifeLine.']);
                        flush_log("Doctor Feedback records seeded.", "success");

                        // 11. Articles Seeding (Nepal health context)
                        $articles = [
                            [
                                $adminId,
                                'Monsoon Health Alert: Preventing Waterborne Diseases in Nepal',
                                "Nepal's monsoon season brings increased risks of cholera, typhoid, and hepatitis A due to contaminated water sources. Protect yourself:\n1. Always drink boiled or purified water.\n2. Wash vegetables and fruits thoroughly.\n3. Avoid street food during heavy rains.\n4. Get vaccinated for typhoid and hepatitis A before monsoon.\n5. Use ORS (Oral Rehydration Solution) immediately if diarrhea starts.\nConsult LifeLine's General Medicine team if symptoms appear.",
                                'Disease Awareness',
                                NULL
                            ],
                            [
                                $adminId,
                                'Understanding High Altitude Health Risks in Nepal',
                                "Nepal's unique geography puts residents and trekkers at risk for altitude-related conditions. Key facts:\n- Acute Mountain Sickness (AMS) starts above 2,500m.\n- Symptoms: headache, nausea, fatigue, and shortness of breath.\n- Descend immediately if symptoms worsen.\n- Acclimatize by spending extra days at intermediate altitudes.\n- Diamox (Acetazolamide) can help prevent AMS under medical supervision.\nConsult a doctor before trekking above 3,500m.",
                                'Health Tips',
                                NULL
                            ],
                            [
                                $adminId,
                                'Nutrition Guide: Dal-Bhat for Balanced Health',
                                "Nepal's staple diet of Dal-Bhat (lentil soup and rice) is nutritionally rich when balanced correctly:\n- Dal: High in protein, iron, and folate - essential for blood health.\n- Bhat: Complex carbohydrates for sustained energy.\n- Tarkari (vegetables): Vitamins A, C and dietary fiber.\n- Achar (pickles): Probiotic-rich fermented foods support gut health.\nAdd leafy greens, eggs, and seasonal fruits to maximize nutritional balance.",
                                'Nutrition Advice',
                                NULL
                            ]
                        ];
                        $stmt = $pdo->prepare("INSERT INTO `articles` (admin_id, title, content, category, image_path) VALUES (?, ?, ?, ?, ?)");
                        foreach ($articles as $art) {
                            $stmt->execute($art);
                        }
                        flush_log("Nepal health articles published.", "success");

                        // 12. Notifications Seeding
                        $stmtNotif = $pdo->prepare("INSERT INTO `notifications` (user_type, user_id, title, message, is_read) VALUES (?, ?, ?, ?, 0)");
                        $stmtNotif->execute(['patient', $pat_ids['Aarav Shrestha'], 'LifeLine HMS मा स्वागत छ!', 'Your patient account has been initialized. You can now book appointments and view health records.']);
                        $stmtNotif->execute(['patient', $pat_ids['Aarav Shrestha'], 'Appointment Confirmed', 'Your appointment with Dr. Suman Shrestha on ' . $tomorrow . ' at 11:00 AM has been approved.']);
                        $stmtNotif->execute(['doctor', $doc_ids['Dr. Suman Shrestha'], 'New Appointment Scheduled', 'Patient Aarav Shrestha has booked a slot for ' . $tomorrow . ' at 11:00 AM.']);
                        $stmtNotif->execute(['admin', $adminId, 'Pending Doctor Account', 'Doctor Dr. Anish Acharya has registered and requires account verification.']);
                        flush_log("System Notification feeds initialized.", "success");

                        // 13. Create Directories
                        flush_log("Verifying upload folders in the application...", "info");
                        $baseDir = dirname(__DIR__);
                        $dirs = [
                            $baseDir . '/uploads',
                            $baseDir . '/uploads/profile_pics',
                            $baseDir . '/uploads/lab_reports'
                        ];
                        
                        foreach ($dirs as $dir) {
                            if (!file_exists($dir)) {
                                if (mkdir($dir, 0777, true)) {
                                    flush_log("Created folder: " . basename($dir), "success");
                                } else {
                                    flush_log("Failed to create folder: " . $dir, "error");
                                }
                            } else {
                                flush_log("Folder already exists: " . basename($dir), "info");
                            }
                        }

                        flush_log("-----------------------------------------", "info");
                        flush_log("HMS SETUP COMPLETED SUCCESSFULLY! Nepal localization active.", "success");
                        flush_log("Login: patient=chirag@gmail.com/patient123 | doctor=suman.shrestha@lifeline.com.np/doctor123 | admin=admin@hospital.com/admin123", "success");

                    } catch (PDOException $e) {
                        flush_log("Database Error: " . $e->getMessage(), "error");
                    } catch (Exception $e) {
                        flush_log("General Error: " . $e->getMessage(), "error");
                    }


                    ?>

                </div>

                <div class="text-center">
                    <a href="../index.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-house me-2"></i> Go to Landing Page</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto scroll setup log to bottom
    const logBox = document.getElementById('log-container');
    logBox.scrollTop = logBox.scrollHeight;
</script>
</body>
</html>
