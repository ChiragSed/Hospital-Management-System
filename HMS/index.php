<?php
/**
 * Public Landing Page
 * Hospital Management System
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$db_setup_required = false;
$departments = [];
$doctors = [];
$articles = [];

// Try to connect and load dynamic content, fallback to mock if DB not set up
try {
    if (file_exists(__DIR__ . '/config/database.php')) {
        require_once __DIR__ . '/config/database.php';
        
        // Check if departments table exists and has data
        $check = $pdo->query("SHOW TABLES LIKE 'departments'")->rowCount();
        if ($check > 0) {
            $departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
            
            $doctors = $pdo->query("
                SELECT d.*, dept.name AS dept_name 
                FROM doctors d 
                LEFT JOIN departments dept ON d.department_id = dept.id 
                WHERE d.status = 'approved' 
                LIMIT 4
            ")->fetchAll();
            
            $articles = $pdo->query("
                SELECT a.*, adm.name AS author_name 
                FROM articles a 
                JOIN admins adm ON a.admin_id = adm.id 
                ORDER BY a.created_at DESC 
                LIMIT 3
            ")->fetchAll();
        } else {
            $db_setup_required = true;
        }
    } else {
        $db_setup_required = true;
    }
} catch (Exception $e) {
    $db_setup_required = true;
}

// Seeding Fallback/Mock Data for visual demonstration if DB connection is not ready
if (empty($departments)) {
    $departments = [
        ['name' => 'Cardiology', 'description' => 'Heart health and cardiovascular diagnoses.', 'icon' => 'fa-heart-pulse'],
        ['name' => 'Neurology', 'description' => 'Brain and nervous system disorders.', 'icon' => 'fa-brain'],
        ['name' => 'Orthopedics', 'description' => 'Musculoskeletal system, bones, and joints.', 'icon' => 'fa-bone'],
        ['name' => 'Pediatrics', 'description' => 'Infant, child, and adolescent healthcare.', 'icon' => 'fa-baby'],
        ['name' => 'Dermatology', 'description' => 'Skin, hair, and nail treatments.', 'icon' => 'fa-hand-dots'],
        ['name' => 'General Medicine', 'description' => 'Primary healthcare, diagnoses, and treatments.', 'icon' => 'fa-stethoscope']
    ];
}

if (empty($doctors)) {
    $doctors = [
        ['name' => 'Dr. Suman Shrestha', 'specialization' => 'Interventional Cardiology', 'dept_name' => 'Cardiology', 'qualification' => 'MD, FACC', 'experience' => 14, 'profile_pic' => NULL],
        ['name' => 'Dr. Priya Sharma', 'specialization' => 'Neonatal Care', 'dept_name' => 'Pediatrics', 'qualification' => 'MD - Pediatrics, DCH', 'experience' => 9, 'profile_pic' => NULL],
        ['name' => 'Dr. Ramesh Koirala', 'specialization' => 'Chronic Disease Management', 'dept_name' => 'General Medicine', 'qualification' => 'MBBS, MD - Internal Medicine', 'experience' => 11, 'profile_pic' => NULL]
    ];
}

if (empty($articles)) {
    $articles = [
        [
            'title' => '10 Essential Tips for a Healthier Heart',
            'content' => 'Cardiovascular health is key to longevity. Keep your heart strong by following these foundational tips: exercise 30 minutes daily, limit saturated fats, eat omega-3 rich foods, and manage stress.',
            'category' => 'Health Tips',
            'created_at' => date('Y-m-d H:i:s'),
            'author_name' => 'Chief Medical Officer'
        ],
        [
            'title' => 'Understanding the BMI Metric',
            'content' => 'Body Mass Index (BMI) is a starting point for assessing metabolic health. Standard categories are underweight, normal weight, overweight, and obesity. Learn how to calculate and interpret your score.',
            'category' => 'Disease Awareness',
            'created_at' => date('Y-m-d H:i:s'),
            'author_name' => 'Admin'
        ]
    ];
}

// Handle simulated contact form submission
$contact_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $contact_success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeLine Hospital - Care, Compassion, and Clinical Excellence</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #F8FAF9;
            color: #5F6F65;
        }
        .hero-section {
            background: linear-gradient(135deg, #2A9D8F 0%, #23867A 50%, #A8D5BA 100%);
            padding: 120px 0 160px 0;
            color: white;
            position: relative;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 80px;
            background: #F8FAF9;
            clip-path: polygon(0 100%, 100% 100%, 100% 0);
        }
        .section-title {
            position: relative;
            margin-bottom: 50px;
            font-weight: 700;
            color: #264653;
        }
        .section-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 60px;
            height: 4px;
            background-color: #2A9D8F;
            border-radius: 2px;
        }
        .section-title.center::after {
            left: 50%;
            transform: translateX(-50%);
        }
        .dept-card {
            border: none;
            border-radius: 16px;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
            height: 100%;
        }
        .dept-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(42, 157, 143, 0.06);
        }
        .dept-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background-color: #e6f6f4;
            color: #2A9D8F;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }
        .doc-card {
            border: none;
            border-radius: 16px;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .doc-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .doc-img {
            height: 250px;
            object-fit: cover;
            background-color: #e8ecea;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .emergency-card {
            background: linear-gradient(135deg, #E76F51 0%, #c4563a 100%);
            color: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(231, 111, 81, 0.2);
        }
    </style>
</head>
<body>

<!-- Setup Alert Banner -->
<?php if ($db_setup_required): ?>
<div class="alert alert-warning border-0 rounded-0 mb-0 py-3 text-center no-print">
    <div class="container d-flex justify-content-center align-items-center">
        <i class="fa-solid fa-triangle-exclamation me-2 fa-lg"></i>
        <span><strong>Database setup required:</strong> The database tables have not been initialized. Please run the setup utility to generate the database and seed records.</span>
        <a href="database/setup.php" class="btn btn-outline-dark btn-sm ms-3 fw-bold"><i class="fa-solid fa-gears me-1"></i> Run DB Setup</a>
    </div>
</div>
<?php endif; ?>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="fa-solid fa-hospital-user text-primary fa-xl me-2"></i>
            <span class="fw-bold fs-4 text-dark text-uppercase">LifeLine</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link fw-semibold px-3" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold px-3" href="#about">About</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold px-3" href="#departments">Departments</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold px-3" href="#doctors">Doctors</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold px-3" href="#services">Services</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold px-3" href="#articles">Health Articles</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold px-3" href="#contact">Contact</a></li>
            </ul>
            <div class="d-flex gap-2">
                <?php if (is_logged_in()): ?>
                    <a href="<?php echo $_SESSION['user_type']; ?>/dashboard.php" class="btn btn-primary px-4 fw-semibold"><i class="fa-solid fa-circle-user me-2"></i>Dashboard</a>
                <?php else: ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary px-4 dropdown-toggle fw-semibold" type="button" id="portalDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>Portal Login
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2" aria-labelledby="portalDropdown" style="border-radius: 12px;">
                            <li><a class="dropdown-item py-2 fw-semibold" href="login.php?role=patient"><i class="fa-solid fa-user-injured me-2 text-muted"></i>Patient Login</a></li>
                            <li><a class="dropdown-item py-2 fw-semibold" href="login.php?role=doctor"><i class="fa-solid fa-user-doctor me-2 text-muted"></i>Doctor Login</a></li>
                            <li><a class="dropdown-item py-2 fw-semibold" href="login.php?role=admin"><i class="fa-solid fa-user-shield me-2 text-muted"></i>Admin Login</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 fw-semibold text-primary" href="register.php"><i class="fa-solid fa-user-plus me-2"></i>Patient Registration</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section id="home" class="hero-section">
    <div class="container">
        <div class="row align-items-center py-5">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <span class="badge bg-light text-primary mb-3 px-3 py-2 fw-semibold text-uppercase">Compassionate & Modern Care</span>
                <h1 class="display-4 fw-bold lh-sm mb-4">Your Health is Our Lifeline, Always.</h1>
                <p class="lead mb-4 opacity-90">Experience premium healthcare services with state-of-the-art clinical technologies, world-renowned doctors, and a patients-first management system.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="register.php" class="btn btn-light btn-lg text-primary px-4 fw-bold shadow"><i class="fa-solid fa-calendar-plus me-2"></i>Book Appointment</a>
                    <a href="login.php?role=patient" class="btn btn-outline-light btn-lg px-4 fw-bold">Access Patient Portal</a>
                </div>
            </div>
            <div class="col-lg-6 text-center text-lg-end">
                <!-- SVG Vector Representation for clean professional rendering without heavy external files -->
                <svg width="480" height="340" viewBox="0 0 480 340" fill="none" class="img-fluid hero-img bg-white p-3 border border-5 border-white shadow-lg" style="border-radius: 24px;">
                    <rect width="450" height="310" rx="16" fill="#F8FAF9"/>
                    <circle cx="225" cy="155" r="100" fill="#e6f6f4"/>
                    <path d="M225 95V215M165 155H285" stroke="#2A9D8F" stroke-width="20" stroke-linecap="round"/>
                    <rect x="30" y="30" width="390" height="250" rx="12" stroke="#A8D5BA" stroke-width="4" stroke-dasharray="10 10"/>
                    <circle cx="90" cy="80" r="15" fill="#A8D5BA"/>
                    <circle cx="360" cy="230" r="25" fill="#2A9D8F" opacity="0.3"/>
                </svg>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-5">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-5 mb-lg-0">
                <svg width="350" height="350" viewBox="0 0 350 350" fill="none" class="img-fluid rounded-4 shadow bg-primary p-4">
                    <path d="M175 40 L300 110 L300 230 L175 310 L50 230 L50 110 Z" fill="#ffffff" opacity="0.1"/>
                    <circle cx="175" cy="175" r="70" fill="#ffffff"/>
                    <path d="M175 135V215M135 175H215" stroke="#2A9D8F" stroke-width="12" stroke-linecap="round"/>
                    <circle cx="80" cy="90" r="10" fill="#A8D5BA"/>
                    <circle cx="280" cy="260" r="15" fill="#A8D5BA"/>
                </svg>
            </div>
            <div class="col-lg-7">
                <span class="text-primary fw-bold text-uppercase">Who We Are</span>
                <h2 class="section-title">A Legacy of Clinical Excellence and Integrity</h2>
                <p class="mb-4">LifeLine Hospital is a leading multispecialty healthcare institution, committed to serving patients with cutting-edge medical services. Established with a vision to make premium diagnostic and therapeutic care accessible to everyone, our clinic combines digital innovation with expert medical professionals.</p>
                <div class="row g-4">
                    <div class="col-md-6 d-flex align-items-start">
                        <div class="text-primary me-3 mt-1"><i class="fa-solid fa-circle-check fa-lg"></i></div>
                        <div>
                            <h5 class="fw-bold text-dark">Qualified Medical Experts</h5>
                            <p class="small text-muted">All active physicians are certified specialists in their respective departments.</p>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-start">
                        <div class="text-primary me-3 mt-1"><i class="fa-solid fa-circle-check fa-lg"></i></div>
                        <div>
                            <h5 class="fw-bold text-dark">Modern Diagnostics</h5>
                            <p class="small text-muted">Our laboratories utilize state of the art equipment for exact, speedy lab findings.</p>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-start">
                        <div class="text-primary me-3 mt-1"><i class="fa-solid fa-circle-check fa-lg"></i></div>
                        <div>
                            <h5 class="fw-bold text-dark">Emergency Care 24/7</h5>
                            <p class="small text-muted">Always ready to dispatch ambulances and support trauma or critical care immediately.</p>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-start">
                        <div class="text-primary me-3 mt-1"><i class="fa-solid fa-circle-check fa-lg"></i></div>
                        <div>
                            <h5 class="fw-bold text-dark">Safe Digital Portals</h5>
                            <p class="small text-muted">A dedicated patient system for secure files, invoices, and scheduling.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Departments Section -->
<section id="departments" class="bg-light py-5">
    <div class="container py-5">
        <div class="text-center">
            <span class="text-primary fw-bold text-uppercase">Specialties</span>
            <h2 class="section-title center">Our Clinical Departments</h2>
            <p class="text-muted col-md-6 mx-auto mb-5">Providing extensive medical consults across multiple healthcare fields using specialized treatment protocols.</p>
        </div>
        <div class="row g-4">
            <?php foreach (array_slice($departments, 0, 6) as $dept): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="dept-card p-4">
                        <div class="dept-icon">
                            <i class="fa-solid <?php echo isset($dept['icon']) ? sanitize($dept['icon']) : 'fa-briefcase-medical'; ?>"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-3"><?php echo sanitize($dept['name']); ?></h4>
                        <p class="small text-muted mb-0"><?php echo sanitize($dept['description']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Doctors Section -->
<section id="doctors" class="py-5">
    <div class="container py-5">
        <div class="text-center">
            <span class="text-primary fw-bold text-uppercase">Our Team</span>
            <h2 class="section-title center">Meet Our Expert Specialists</h2>
            <p class="text-muted col-md-6 mx-auto mb-5">Consult with our certified clinicians having extensive international experience and surgical achievements.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($doctors as $doc): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="doc-card card h-100">
                        <div class="doc-img">
                            <?php if (!empty($doc['profile_pic'])): ?>
                                <img src="uploads/profile_pics/<?php echo $doc['profile_pic']; ?>" class="img-fluid w-100 h-100" style="object-fit: cover;" alt="Doctor">
                            <?php else: ?>
                                <i class="fa-solid fa-user-doctor fa-5x text-secondary opacity-25"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body text-center p-4">
                            <h5 class="fw-bold mb-1"><?php echo sanitize($doc['name']); ?></h5>
                            <span class="badge bg-primary-subtle text-primary mb-3 px-3 py-1"><?php echo sanitize($doc['dept_name'] ?? 'General Practitioner'); ?></span>
                            <div class="small text-muted mb-3">
                                <div><i class="fa-solid fa-graduation-cap me-1"></i> <?php echo sanitize($doc['qualification']); ?></div>
                                <div><i class="fa-solid fa-award me-1"></i> <?php echo intval($doc['experience']); ?> Years Experience</div>
                            </div>
                            <a href="login.php?role=patient" class="btn btn-outline-primary btn-sm w-100 fw-semibold">Book Appointment</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Emergency Services Banner -->
<section class="container my-5">
    <div class="emergency-card p-5">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <h3 class="fw-bold mb-2"><i class="fa-solid fa-truck-medical me-2 animate-pulse"></i> Emergency Critical Services & Ambulance</h3>
                <p class="mb-0 lead opacity-90">Are you experiencing a medical crisis or trauma? Get in touch with our trauma response team instantly. We operate 24 hours a day, 7 days a week.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <h2 class="fw-bold mb-3"><i class="fa-solid fa-phone me-2"></i> 102 (Nepal Ambulance)</h2>
                <a href="login.php?role=patient" class="btn btn-light btn-lg text-danger fw-bold px-4 shadow">Request Ambulance</a>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section id="services" class="bg-light py-5">
    <div class="container py-5">
        <div class="text-center">
            <span class="text-primary fw-bold text-uppercase">Features</span>
            <h2 class="section-title center">Modern Healthcare Services</h2>
            <p class="text-muted col-md-6 mx-auto mb-5">Providing integrated clinical infrastructure for modern primary and specialty treatment.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 p-4 shadow-sm h-100" style="border-radius: 12px;">
                    <div class="text-primary mb-3"><i class="fa-solid fa-microscope fa-3x"></i></div>
                    <h5 class="fw-bold text-dark">Automated Diagnostics</h5>
                    <p class="small text-muted mb-0">Book blood panels, scan screenings, and receive medical reports online via our Lab Portal.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 p-4 shadow-sm h-100" style="border-radius: 12px;">
                    <div class="text-primary mb-3"><i class="fa-solid fa-prescription-bottle-medical fa-3x"></i></div>
                    <h5 class="fw-bold text-dark">Digital Prescription (Rx)</h5>
                    <p class="small text-muted mb-0">Access doctor prescriptions, treatment notes, and drug frequencies directly in PDF format.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 p-4 shadow-sm h-100" style="border-radius: 12px;">
                    <div class="text-primary mb-3"><i class="fa-solid fa-headset fa-3x"></i></div>
                    <h5 class="fw-bold text-dark">Doctor Telehealth</h5>
                    <p class="small text-muted mb-0">Schedule digital checks and seek advice regarding chronic conditions from your living room.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Health Articles Module -->
<section id="articles" class="py-5">
    <div class="container py-5">
        <div class="text-center">
            <span class="text-primary fw-bold text-uppercase">Wellness Center</span>
            <h2 class="section-title center">Latest Health & Nutrition Articles</h2>
            <p class="text-muted col-md-6 mx-auto mb-5">Read health guides, food charts, and tips published by our clinical advisory board.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($articles as $art): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-radius: 16px;">
                        <div class="bg-primary text-white p-3 text-center" style="height: 120px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <span class="badge bg-white text-primary mb-2"><?php echo sanitize($art['category']); ?></span>
                            <small class="opacity-75"><?php echo format_date($art['created_at']); ?></small>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="fw-bold text-dark mb-3"><?php echo sanitize($art['title']); ?></h5>
                            <p class="small text-muted mb-4"><?php echo substr(sanitize($art['content']), 0, 140); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                <span class="small text-muted"><i class="fa-solid fa-pen-nib me-1"></i> By <?php echo sanitize($art['author_name']); ?></span>
                                <a href="login.php?role=patient" class="btn btn-link btn-sm text-primary fw-semibold p-0">Read More <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Contact Form Section -->
<section id="contact" class="py-5 bg-white">
    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-5">
                <span class="text-primary fw-bold text-uppercase">Get in Touch</span>
                <h2 class="section-title">We Are Here to Listen & Help</h2>
                <p class="mb-4">Do you have questions about clinic bookings, lab services, or invoices? Drop us a line. Our client support desk will respond shortly.</p>
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle me-3"><i class="fa-solid fa-map-location-dot fa-lg"></i></div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Hospital Location</h6>
                        <span class="small text-muted">Baneshwor, Kathmandu, Nepal</span>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle me-3"><i class="fa-solid fa-envelope-open-text fa-lg"></i></div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Email Support</h6>
                        <span class="small text-muted">support@lifelinehospital.com</span>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle me-3"><i class="fa-solid fa-phone-volume fa-lg"></i></div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Call Support Desk</h6>
                        <span class="small text-muted">+977-1-4433221 / +977-1-5544332</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card border-0 bg-light p-4 p-md-5" style="border-radius: 16px;">
                    <h4 class="fw-bold text-dark mb-4">Send a Message</h4>
                    <?php if ($contact_success): ?>
                        <div class="alert alert-success border-0 mb-4" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i> Thank you! Your message has been sent successfully. We will contact you soon.
                        </div>
                    <?php endif; ?>
                    <form action="#contact" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Name</label>
                                <input type="text" class="form-control" name="contact_name" required placeholder="John Doe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Email Address</label>
                                <input type="email" class="form-control" name="contact_email" required placeholder="john@example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Subject</label>
                                <input type="text" class="form-control" name="contact_subject" required placeholder="Booking Query">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Message</label>
                                <textarea class="form-control" name="contact_message" rows="4" required placeholder="Write your comments here..."></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" name="submit_contact" class="btn btn-primary w-100 py-3 fw-bold"><i class="fa-regular fa-paper-plane me-2"></i> Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white pt-5 pb-3">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-lg-4 col-md-6">
                <h5 class="fw-bold mb-4 text-white d-flex align-items-center">
                    <i class="fa-solid fa-hospital-user text-secondary me-2"></i> LifeLine Hospital
                </h5>
                <p class="small text-muted">A modern multispecialty Hospital and Research Clinic dedicated to diagnostic excellence and personalized therapeutics.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" class="btn btn-outline-light btn-sm rounded-circle"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold text-white mb-4">Quick Links</h6>
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2"><a href="#about" class="text-reset text-decoration-none">About Us</a></li>
                    <li class="mb-2"><a href="#departments" class="text-reset text-decoration-none">Departments</a></li>
                    <li class="mb-2"><a href="#doctors" class="text-reset text-decoration-none">Doctors Team</a></li>
                    <li class="mb-2"><a href="#services" class="text-reset text-decoration-none">Clinical Services</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold text-white mb-4">Patient Portal</h6>
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2"><a href="login.php?role=patient" class="text-reset text-decoration-none">Schedule Appointment</a></li>
                    <li class="mb-2"><a href="register.php" class="text-reset text-decoration-none">Create Patient Account</a></li>
                    <li class="mb-2"><a href="login.php?role=patient" class="text-reset text-decoration-none">View Medical Records</a></li>
                    <li class="mb-2"><a href="login.php?role=patient" class="text-reset text-decoration-none">Download Lab Findings</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold text-white mb-4">Emergency Contact</h6>
                <p class="small text-muted mb-2"><i class="fa-solid fa-phone me-1 text-secondary"></i> Ambulance Dispatch: 102 (Nepal)</p>
                <p class="small text-muted mb-2"><i class="fa-solid fa-envelope me-1 text-secondary"></i> emergency@lifelinehospital.com</p>
                <p class="small text-muted"><i class="fa-solid fa-clock me-1 text-secondary"></i> Critical Care Open: 24 / 7 / 365</p>
            </div>
        </div>
        <hr class="border-secondary opacity-25">
        <div class="text-center pt-3 text-muted small">
            <p>&copy; <?php echo date('Y'); ?> LifeLine Hospital Management System. Built for Bachelor of Information Technology (BIT) Project.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
