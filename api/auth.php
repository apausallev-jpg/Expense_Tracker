<?php
// ============================================
// Auth API  (api/auth.php)
// Actions: login, register, logout, me, update_profile
// ============================================

require_once __DIR__ . '/../config.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = $_GET['action'] ?? '';
$db     = getDB();

switch ($action) {

    // ── Register ─────────────────────────────────────────────────────────────
    case 'register':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['full_name']))              jsonResponse(['error' => 'Full name is required'], 422);
        if (empty($data['email']))                  jsonResponse(['error' => 'Email is required'], 422);
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Invalid email'], 422);
        if (empty($data['password']))               jsonResponse(['error' => 'Password is required'], 422);
        if (strlen($data['password']) < 6)          jsonResponse(['error' => 'Password must be at least 6 characters'], 422);

        // Check if email exists
        $check = $db->prepare('SELECT id FROM users WHERE email = :email');
        $check->execute([':email' => strtolower(trim($data['email']))]);
        if ($check->fetch()) jsonResponse(['error' => 'Email already registered'], 409);

        $stmt = $db->prepare(
            'INSERT INTO users (full_name, email, password)
             VALUES (:full_name, :email, :password) RETURNING id, full_name, email, avatar, created_at'
        );
        $stmt->execute([
            ':full_name' => trim($data['full_name']),
            ':email'     => strtolower(trim($data['email'])),
            ':password'  => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
        $user = $stmt->fetch();

        $_SESSION['user_id'] = $user['id'];
        jsonResponse(['message' => 'Registered successfully', 'user' => $user]);

    // ── Login ─────────────────────────────────────────────────────────────────
    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['email']) || empty($data['password']))
            jsonResponse(['error' => 'Email and password are required'], 422);

        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => strtolower(trim($data['email']))]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($data['password'], $user['password']))
            jsonResponse(['error' => 'Invalid email or password'], 401);

        $_SESSION['user_id'] = $user['id'];
        unset($user['password']);
        jsonResponse(['message' => 'Login successful', 'user' => $user]);

    // ── Logout ────────────────────────────────────────────────────────────────
    case 'logout':
        session_destroy();
        jsonResponse(['message' => 'Logged out']);

    // ── Get current user ──────────────────────────────────────────────────────
    case 'me':
        if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Not authenticated'], 401);

        $stmt = $db->prepare('SELECT id, full_name, email, avatar, created_at FROM users WHERE id = :id');
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        $user ? jsonResponse($user) : jsonResponse(['error' => 'User not found'], 404);

    // ── Update profile (name + avatar) ────────────────────────────────────────
    case 'update_profile':
        if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Not authenticated'], 401);

        $userId   = $_SESSION['user_id'];
        $fullName = trim($_POST['full_name'] ?? '');
        if (!$fullName) jsonResponse(['error' => 'Full name is required'], 422);

        $avatarPath = null;

        // Handle avatar upload
        if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mime    = mime_content_type($_FILES['avatar']['tmp_name']);

            if (!in_array($mime, $allowed))
                jsonResponse(['error' => 'Only JPG, PNG, GIF, WEBP allowed'], 422);

            if ($_FILES['avatar']['size'] > 2 * 1024 * 1024)
                jsonResponse(['error' => 'Image must be under 2MB'], 422);

            $ext        = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename   = 'avatar_' . $userId . '_' . time() . '.' . strtolower($ext);
            $uploadDir  = __DIR__ . '/../uploads/avatars/';

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // Delete old avatar
            $old = $db->prepare('SELECT avatar FROM users WHERE id = :id');
            $old->execute([':id' => $userId]);
            $oldAvatar = $old->fetchColumn();
            if ($oldAvatar && file_exists(__DIR__ . '/../' . $oldAvatar)) {
                unlink(__DIR__ . '/../' . $oldAvatar);
            }

            move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename);
            $avatarPath = 'uploads/avatars/' . $filename;
        }

     if ($avatarPath) {
    $stmt = $db->prepare('UPDATE users SET full_name=:name, avatar=:avatar WHERE id=:id RETURNING id, full_name, email, avatar');
    $stmt->execute([':name' => $fullName, ':avatar' => $avatarPath, ':id' => $userId]);
} else {
    $stmt = $db->prepare('UPDATE users SET full_name=:name WHERE id=:id RETURNING id, full_name, email, avatar');
    $stmt->execute([':name' => $fullName, ':id' => $userId]);
}

$row = $stmt->fetch();
if ($row) {
    jsonResponse($row);
} else {
    jsonResponse(['error' => 'Update failed'], 500);
}
     
     
     
     
     
     

     

    default:
        jsonResponse(['error' => 'Unknown action'], 400);
}
