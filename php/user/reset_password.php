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
                  $del = mysqli_prepare($connection, "DELETE FROM password_resets WHERE employee_number = ?");
                  mysqli_stmt_bind_param($del, 's', $emp);
                  mysqli_stmt_execute($del);
                  mysqli_stmt_close($del);
                  mysqli_close($connection);
                  // Show success message on the same page instead of redirecting to avoid
                  // issues with server paths and subdirectory deployments.
                  $messageType = 'success';
                  $message = 'Password changed successfully.';
                  // Clear token so form won't resubmit sensitive data
                  $token = '';
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
    :root{--bg:#f4f6f8;--card:#ffffff;--accent:#218838;--muted:#6c757d}
    html,body{}
    body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #0f5132, #0d47a1); background-repeat: no-repeat; background-size: cover; background-attachment: fixed; background-position: center center; display:flex; align-items:center; justify-content:center; padding:24px; }
    .card { background:var(--card); width:100%; max-width:460px; padding:28px; border-radius:10px; box-shadow:0 10px 30px rgba(22,28,37,0.08); }
    .input { display:block; width:100%; padding:12px; margin:10px auto; border:1px solid #e0e6eb; border-radius:8px; font-size:15px; box-sizing:border-box }
    label{display:block; font-size:13px; color:var(--muted); margin-top:8px}
    .btn { padding:10px 18px; background:var(--accent); color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; display:block; margin:12px auto 0; min-width:140px }
    .message { padding:12px; border-radius:8px; margin-bottom:14px; font-size:14px }
    .message.error { background:#fff0f0; color:#8a1f1f; }
    .message.success { background:#ecf9f0; color:#1a6b2b; }
    .note{font-size:13px;color:#8a8f96;margin-top:8px}
    /* simple toast/modal for success */
    .overlay{position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.35);}
    .toast{background:#fff;padding:18px 20px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);max-width:380px;text-align:center}
    .toast h3{margin:0 0 8px;font-size:16px}
    .toast p{margin:0 0 12px;color:var(--muted)}
    .toast .ok{padding:8px 12px;border-radius:8px;background:var(--accent);color:#fff;border:none;cursor:pointer;font-weight:600}
  </style>
</head>
<body>
  <div class="card">
    <h2>Reset Password</h2>
    <?php if ($message !== ''): ?>
      <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <div id="clientMessage" class="message error" style="display:none"></div>
    <form method="POST">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
      <label>New Password</label>
      <input class="input" type="password" name="password" required>
      <label>Confirm Password</label>
      <input class="input" type="password" name="confirm" required>
      <div style="margin-top:12px;"><button class="btn" type="submit">Reset Password</button></div>
    </form>
  </div>

  <div class="overlay" id="successOverlay">
    <div class="toast">
      <h3>Password Changed</h3>
      <p>Your password has been updated successfully.</p>
      <button class="ok" id="okBtn">Go to Login</button>
    </div>
  </div>
  <script>
    (function(){
      const overlay = document.getElementById('successOverlay');
      const ok = document.getElementById('okBtn');
      const clientMsg = document.getElementById('clientMessage');
      // client-side validation
      const form = document.querySelector('form');
      if (form) {
        form.addEventListener('submit', function(e){
          // clear client message
          clientMsg.style.display = 'none'; clientMsg.textContent = '';
          const pw = form.querySelector('input[name="password"]').value || '';
          const conf = form.querySelector('input[name="confirm"]').value || '';
          if (pw.length < 8) {
            e.preventDefault(); clientMsg.style.display = ''; clientMsg.textContent = 'Password must be at least 8 characters.'; return false;
          }
          const special = /[!@#$%^&*(),.?":{}|<>\[\]\\/\\\\;:'"`~\-_+=]/;
          if (!special.test(pw)) { e.preventDefault(); clientMsg.style.display = ''; clientMsg.textContent = 'Password must include at least one special character (e.g. !@#).'; return false; }
          if (pw !== conf) { e.preventDefault(); clientMsg.style.display = ''; clientMsg.textContent = 'Passwords do not match.'; return false; }
          return true;
        });
      }

      // If page rendered with a success message, show overlay and hide form
      const msg = <?php echo json_encode($messageType === 'success'); ?>;
      if (msg) {
        overlay.style.display = 'flex';
        const f = document.querySelector('form'); if (f) f.style.display = 'none';
      }
      ok.addEventListener('click', ()=>{
        const host = window.location.protocol + '//' + window.location.host;
        window.location.href = host + '/public_html/php/user/index.php';
      });
    })();
  </script>
</body>
</html>
