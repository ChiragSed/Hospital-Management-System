<?php
/**
 * AJAX Handler: Get Doctors by Department
 * Hospital Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

if ($department_id <= 0) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name, specialization, consultation_fee 
        FROM doctors 
        WHERE department_id = ? AND status = 'approved'
        ORDER BY name ASC
    ");
    $stmt->execute([$department_id]);
    $doctors = $stmt->fetchAll();
    
    // Sanitize data before sending
    $sanitized_doctors = [];
    foreach ($doctors as $doctor) {
        $sanitized_doctors[] = [
            'id' => $doctor['id'],
            'name' => sanitize($doctor['name']),
            'specialization' => sanitize($doctor['specialization']),
            'consultation_fee' => floatval($doctor['consultation_fee'])
        ];
    }
    
    echo json_encode($sanitized_doctors);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}
