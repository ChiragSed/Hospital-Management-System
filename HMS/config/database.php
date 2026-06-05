<?php
/**
 * Database Connection configuration
 * Hospital Management System
 */

$host = 'localhost';
$db   = 'hospital';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // For safety, do not print full credentials or stack trace in production
     die("Database connection failed. Please ensure MySQL is running and the database setup has been executed.");
}
