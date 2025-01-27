<?php
session_start();

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['token']) || !isset($data['email'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

// Here you should implement your own logic to verify if the email is allowed to access the admin panel
// For example, you might have a database table with allowed admin emails
$allowed_emails = [
    // Add your admin emails here
    'your-admin-email@example.com'
];

if (in_array($data['email'], $allowed_emails)) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_email'] = $data['email'];
    $_SESSION['admin_name'] = $data['name'];
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Unauthorized email']);
} 