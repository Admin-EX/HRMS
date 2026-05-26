<?php
session_start();
header('Content-Type: application/json');
include "../database/connection.php";

$employeeNumber = trim($_POST['employeeNumber'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($employeeNumber === '' || $email === '') {
    echo json_encode(["status"=>"error","message"=>"All fields are required"]);
    exit;
}

$stmt = mysqli_prepare($connection, "SELECT email FROM users WHERE employee_number = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $employeeNumber);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$user = mysqli_fetch_assoc($res)) {
    echo json_encode(["status"=>"error","message"=>"No account found for that employee number"]);
    exit;
}

if (strtolower(trim($user['email'])) !== strtolower($email)) {
    echo json_encode(["status"=>"error","message"=>"Email does not match our records"]);
    exit;
}

// ensure table exists
$createSql = "CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_number VARCHAR(100) NOT NULL UNIQUE,
  token VARCHAR(128) NOT NULL,
  expires INT NOT NULL
)";
mysqli_query($connection, $createSql);

$token = bin2hex(random_bytes(32));
$expires = time() + 3600; // 1 hour

$upsert = "INSERT INTO password_resets (employee_number, token, expires) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = VALUES(token), expires = VALUES(expires)";
$uStmt = mysqli_prepare($connection, $upsert);
mysqli_stmt_bind_param($uStmt, 'ssi', $employeeNumber, $token, $expires);
mysqli_stmt_execute($uStmt);

// send email with reset link (build robust path relative to this script)
function url_path_join(...$parts) {
    $segments = [];
    foreach ($parts as $p) {
        $p = str_replace('\\', '/', $p);
        foreach (explode('/', $p) as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') { array_pop($segments); continue; }
            $segments[] = $seg;
        }
    }
    return '/' . implode('/', $segments);
}
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);

// Build a reset path robustly. Prefer Referer when available (preserves any subdirectory
// like /public_html/ used in local dev). Fall back to path built from this script's
// SCRIPT_NAME when Referer is not present.
$resetPath = url_path_join($scriptDir, '..', 'php', 'user', 'reset_password.php');
if (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = $_SERVER['HTTP_REFERER'];
    $u = parse_url($ref);
    $refPath = $u['path'] ?? '';
    if ($refPath !== '') {
        $resetPath = url_path_join(dirname($refPath), 'reset_password.php');
    }
}
$resetLink = $scheme . '://' . $_SERVER['HTTP_HOST'] . $resetPath . '?token=' . $token;

$autoload = __DIR__ . '/../vendor/autoload.php';
$sent = false;
if (file_exists($autoload)) {
    require_once $autoload;
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'mostdevil24@gmail.com';
        $mail->Password = 'bkvx rpin tlfi svpl';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('mostdevil24@gmail.com', 'BTech HRMS');
        $mail->addAddress($email);
        $mail->Subject = 'Password reset request';
        $mail->Body = "A password reset was requested for your account. Click the link below to reset your password (valid 1 hour):\n\n" . $resetLink;
        $sent = $mail->send();
    } catch (Exception $e) {
        $sent = false;
    }
}

if ($sent) {
    echo json_encode(["status"=>"success","message"=>"Password reset link sent to your email."]);
} else {
    echo json_encode(["status"=>"error","message"=>"Unable to send reset email. Try again later."]);
}

mysqli_close($connection);

?>
