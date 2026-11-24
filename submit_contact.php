<?php
// submit_contact.php
// Saves contact form data to MySQL database
// No message length restriction (1 character allowed)

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Include database connection
require_once 'config.php';

// Get and sanitize input
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
$phone   = trim($_POST['phone'] ?? '');

// === VALIDATION ===
// Name: required, non-empty after trim
if ($name === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Name is required.'
    ]);
    exit;
}

// Email: required and valid format
// Email: optional but if provided must be valid
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address or leave it empty.'
    ]);
    exit;
}

// Phone: required (basic validation)
if ($phone === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Phone number is required.'
    ]);
    exit;
}

// Basic phone format check (allows +, digits, spaces, dashes, parentheses)
if (!preg_match('/^[0-9+()\-\s]{6,30}$/', $phone)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid phone number.'
    ]);
    exit;
}


// Message: required, but NO minimum length
if ($message === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Message is required.'
    ]);
    exit;
}

// Optional: Limit max length to prevent abuse (e.g., 5000 chars)
// Remove or increase if needed
if (strlen($message) > 5000) {
    echo json_encode([
        'success' => false,
        'message' => 'Message is too long. Max 5000 characters.'
    ]);
    exit;
}

// === INSERT INTO DATABASE ===
try {
        // If the contact_messages table has a `phone` column, insert into it; otherwise fall back.
        $hasPhone = false;
        try {
            $col = $pdo->query("SHOW COLUMNS FROM contact_messages LIKE 'phone'")->fetch();
            if ($col) $hasPhone = true;
        } catch (Exception $e) {
            // ignore - if the query fails, we'll attempt fallback insert
        }

        if ($hasPhone) {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, message) VALUES (:name, :email, :phone, :message)");
            $stmt->execute([
                ':name'    => $name,
                ':email'   => ($email === '' ? null : $email),
                ':phone'   => $phone,
                ':message' => $message
            ]);
        } else {
            // Fallback: older schema without phone column
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (:name, :email, :message)");
            $stmt->execute([
                ':name'    => $name,
                ':email'   => ($email === '' ? null : $email),
                ':message' => $message
            ]);
        }

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been sent successfully.'
    ]);

} catch (Exception $e) {
    // Log error internally (optional: write to error.log)
    error_log("Contact form error: " . $e->getMessage());

    // User-friendly error
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, something went wrong. Please try again later.'
    ]);
}
?>