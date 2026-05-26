<?php
session_start();
include '../../database/connection.php';

$token = $_GET['token'] ?? '';
$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($token === '' || $password === '' || $confirm === '') {
        $message = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
    } else {
        $stmt = mysqli_prepare($connection, "SELECT employee_number, expires FROM password_resets WHERE token = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $token);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            if (time() > $row['expires']) {
                $message = 'Reset link expired.';
            } else {
                $emp = $row['employee_number'];
                $pwHash = md5($password);
                $u = mysqli_prepare($connection, "UPDATE users SET password = ? WHERE employee_number = ?");
                mysqli_stmt_bind_param($u, 'ss', $pwHash, $emp);
                if (mysqli_stmt_execute($u)) {
                    mysqli_stmt_close($u);
                    mysqli_prepare($connection, "DELETE FROM password_resets WHERE employee_number = ?");
                    $del = mysqli_prepare($connection, "DELETE FROM password_resets WHERE employee_number = ?");
                    mysqli_stmt_bind_param($del, 's', $emp);
                    mysqli_stmt_execute($del);
                    mysqli_stmt_close($del);
                    mysqli_close($connection);
                    header('Location: /php/user/index.php?reset=1');
                    exit;
                } else {
                    $message = 'Unable to update password. Try again later.';
                }
            }
        } else {
            $message = 'Invalid reset token.';
        }
    }
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Reset Password</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f6f8; padding:40px; }
    .card { background:#fff; max-width:420px; margin:40px auto; padding:24px; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.08); }
    .input { width:100%; padding:10px; margin:8px 0; border:1px solid #ccd0d5; border-radius:6px; }
    .btn { padding:10px 14px; background:#218838; color:#fff; border:none; border-radius:6px; cursor:pointer; }
    .message { padding:10px; border-radius:6px; margin-bottom:12px; }
    .message.error { background:#f8d7da; color:#721c24; }
    .message.success { background:#d4edda; color:#155724; }
  </style>
</head>
<body>
  <div class="card">
    <h2>Reset Password</h2>
    <?php if ($message !== ''): ?>
      <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
      <label>New Password</label>
      <input class="input" type="password" name="password" required>
      <label>Confirm Password</label>
      <input class="input" type="password" name="confirm" required>
      <div style="margin-top:12px;"><button class="btn" type="submit">Reset Password</button></div>
    </form>
  </div>
</body>
</html>
