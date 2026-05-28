<?php
session_start();

$signupMessage = "";
$signupMessageType = "success";
$registrationCompleted = (isset($_GET['registered']) && $_GET['registered'] === '1');
$registrationPending = (isset($_GET['pending']) && $_GET['pending'] === '1');
$resetCompleted = (isset($_GET['reset']) && $_GET['reset'] === '1');
$signupMessage = $signupMessage; // keep existing
if ($registrationPending) {
  $signupMessageType = 'success';
  $signupMessage = 'Thank you for registering. Please check your email or contact HR to confirm your registration.';
  $activeTab = 'login';
}
$signupValues = [
    'employeeNumber' => '',
    'email' => ''
];
$activeTab = 'login';
$signupStage = 'form';

function sendVerificationEmail($recipient, $otp)
{
    $subject = 'BTech HRMS Verification Code';
    $body = "Your verification code is: $otp\n\nThis code expires in 5 minutes.";

    $autoload = __DIR__ . '/../../vendor/autoload.php';
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
            $mail->addAddress($recipient);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['signup_submit'])) {
        /** @var mysqli $connection */
        $connection = null;
        include '../../database/connection.php';

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        $signupValues['email'] = htmlspecialchars($email);
        $activeTab = 'signup';

        if ($email === '' || $password === '' || $confirmPassword === '') {
            $signupMessageType = 'error';
            $signupMessage = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $signupMessageType = 'error';
            $signupMessage = 'Please enter a valid email address.';
        } elseif ($password !== $confirmPassword) {
            $signupMessageType = 'error';
            $signupMessage = 'Passwords do not match.';
        } else {
          $checkSql = 'SELECT email FROM users WHERE email = ? LIMIT 1';
          $checkStmt = mysqli_prepare($connection, $checkSql);

            if (!$checkStmt) {
                $signupMessageType = 'error';
                $signupMessage = 'Database error. Please try again later.';
            } else {
                mysqli_stmt_bind_param($checkStmt, 's', $email);
                mysqli_stmt_execute($checkStmt);
                $result = mysqli_stmt_get_result($checkStmt);

                if ($existing = mysqli_fetch_assoc($result)) {
                  $signupMessageType = 'error';
                  $signupMessage = 'Email address already exists.';
                } else {
                  // ensure pending_registrations table exists and check for duplicate pending
                  $pendingCreate = "CREATE TABLE IF NOT EXISTS pending_registrations (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                  mysqli_query($connection, $pendingCreate);

                  $pcheck = mysqli_prepare($connection, 'SELECT id FROM pending_registrations WHERE email = ? LIMIT 1');
                  if ($pcheck) {
                    mysqli_stmt_bind_param($pcheck, 's', $email);
                    mysqli_stmt_execute($pcheck);
                    $pres = mysqli_stmt_get_result($pcheck);
                    if ($prow = mysqli_fetch_assoc($pres)) {
                      $signupMessageType = 'error';
                      $signupMessage = 'A registration for this email is already pending.';
                      mysqli_stmt_close($pcheck);
                    } else {
                      mysqli_stmt_close($pcheck);
                      $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                      $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                      $_SESSION['pending_signup'] = [
                        'email' => $email,
                        'passwordHash' => $passwordHash,
                        'otp' => $otp,
                        'otp_expires' => time() + 300,
                      ];

                      if (sendVerificationEmail($email, $otp)) {
                        $signupStage = 'otp';
                        $activeTab = 'signup';
                        $signupMessageType = 'success';
                        $signupMessage = 'A 6-digit verification code has been sent to your email.';
                      } else {
                        unset($_SESSION['pending_signup']);
                        $signupMessageType = 'error';
                        $signupMessage = 'Unable to send verification email. Please try again later.';
                      }
                    }
                  } else {
                    $signupMessageType = 'error';
                    $signupMessage = 'Database error. Please try again later.';
                  }
                }
                mysqli_stmt_close($checkStmt);
            }
            mysqli_close($connection);
        }
    } elseif (isset($_POST['verify_otp_submit'])) {
        $activeTab = 'signup';
        $signupStage = 'otp';

        if (empty($_SESSION['pending_signup'])) {
            $signupMessageType = 'error';
            $signupMessage = 'No signup session found. Please start again.';
            $signupStage = 'form';
        } else {
            $pending = $_SESSION['pending_signup'];
            $signupValues['email'] = htmlspecialchars($pending['email']);

            $otpCode = trim($_POST['otp_code'] ?? '');
            if ($otpCode === '') {
                $signupMessageType = 'error';
                $signupMessage = 'Please enter the verification code.';
            } elseif (time() > $pending['otp_expires']) {
                $signupMessageType = 'error';
                $signupMessage = 'The verification code has expired. Please resend the code.';
            } elseif ($otpCode !== $pending['otp']) {
                $signupMessageType = 'error';
                $signupMessage = 'The verification code is incorrect.';
            } else {
                /** @var mysqli $connection */
                $connection = null;
                include '../../database/connection.php';

                // ensure pending_registrations table exists
                $pendingSql = "CREATE TABLE IF NOT EXISTS pending_registrations (
                  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                  email VARCHAR(100) NOT NULL UNIQUE,
                  password VARCHAR(255) NOT NULL,
                  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                mysqli_query($connection, $pendingSql);

                $sql = "INSERT INTO pending_registrations (email, password) VALUES (?, ?)";
                $stmt = mysqli_prepare($connection, $sql);
                if ($stmt) {
                  mysqli_stmt_bind_param($stmt, 'ss', $pending['email'], $pending['passwordHash']);
                  if (mysqli_stmt_execute($stmt)) {
                    unset($_SESSION['pending_signup']);
                    mysqli_stmt_close($stmt);
                    mysqli_close($connection);
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?pending=1');
                    exit;
                  } else {
                    $signupMessageType = 'error';
                    $signupMessage = 'Unable to submit registration. Please try again.';
                  }
                  mysqli_stmt_close($stmt);
                } else {
                  $signupMessageType = 'error';
                  $signupMessage = 'Database error. Please try again later.';
                }
                mysqli_close($connection);
            }
        }
    } elseif (isset($_POST['resend_otp_submit'])) {
        $activeTab = 'signup';
        $signupStage = 'otp';

        if (empty($_SESSION['pending_signup'])) {
            $signupMessageType = 'error';
            $signupMessage = 'No signup session found. Please start again.';
            $signupStage = 'form';
        } else {
            $pending = &$_SESSION['pending_signup'];
            $pending['otp'] = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $pending['otp_expires'] = time() + 300;
            $signupValues['email'] = htmlspecialchars($pending['email']);

            if (sendVerificationEmail($pending['email'], $pending['otp'])) {
                $signupMessageType = 'success';
                $signupMessage = 'A new verification code has been sent to your email.';
            } else {
                $signupMessageType = 'error';
                $signupMessage = 'Unable to send verification email. Please try again later.';
            }
        }
    }
}

if (isset($_SESSION['pending_signup']) && $signupStage === 'form') {
    $signupStage = 'otp';
    $activeTab = 'signup';
            $pending = &$_SESSION['pending_signup'];
            $pending['otp'] = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $pending['otp_expires'] = time() + 300;
            $signupValues['email'] = htmlspecialchars($pending['email']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HRMS Login / Signup</title>
<link rel="stylesheet" href="../../css/user/login.css">  <style>
    html, body { margin: 0; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
    body { background: linear-gradient(135deg, #0f5132, #0d47a1); }
    .auth-wrapper { display:flex; justify-content:center; align-items:center; min-height:100vh; padding:20px; }
    .auth-card { width:420px; background:#fff; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.18); overflow:hidden; }
    .auth-tabs { display:flex; }
    .tab { flex:1; padding:16px 0; border:none; cursor:pointer; background:#f3f4f6; color:#333; font-weight:700; font-size:14px; transition:background .2s; }
    .tab.active { background:#fff; color:#111; border-bottom:4px solid #218838; }
    .form-panel { padding:30px; display:none; }
    .form-panel.active { display:block; }
    .form-panel h2 { text-align:center; margin-bottom:10px; }
    .form-panel p { text-align:center; margin-bottom:24px; color:#555; }
    .input-box { margin-bottom:16px; }
    .input-box label { display:block; font-weight:600; margin-bottom:6px; color:#222; }
    .input-box input { width:100%; padding:12px 14px; border:1px solid #ccd0d5; border-radius:8px; outline:none; transition:border .2s; }
    .input-box input:focus { border-color:#218838; }
    .btn { width:100%; padding:14px; background:#218838; border:none; color:#fff; font-size:16px; border-radius:8px; cursor:pointer; transition:background .2s; }
    .btn:hover { background:#1b6f2b; }
    .message { text-align:center; margin-bottom:16px; padding:12px 14px; border-radius:8px; }
    .message.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
    .message.error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
    .toggle-link { text-align:center; margin-top:14px; font-size:14px; color:#555; }
    .toggle-link a { color:#218838; text-decoration:none; font-weight:700; }
    .remember { margin-bottom:20px; display:flex; align-items:center; gap:8px; }
    .popup-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.65);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }
    .popup-overlay.active { display: flex; }
    .popup-box {
      width: 360px;
      background: #fff;
      border-radius: 16px;
      padding: 28px;
      box-shadow: 0 18px 40px rgba(0,0,0,0.25);
      text-align: center;
    }
    .popup-box h3 { margin: 0 0 12px; font-size: 22px; color: #111; }
    .popup-box p { margin: 0 16px 24px; color: #444; }
    .popup-box button { padding: 12px 18px; background: #218838; color: #fff; border: none; border-radius: 10px; cursor: pointer; font-size: 15px; }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-tabs">
        <button type="button" id="tabLogin" class="tab<?php echo $activeTab === 'login' ? ' active' : ''; ?>">Sign In</button>
        <button type="button" id="tabSignup" class="tab<?php echo $activeTab === 'signup' ? ' active' : ''; ?>">Sign Up</button>
      </div>

      <div id="loginContainer" class="form-panel<?php echo $activeTab === 'login' ? ' active' : ''; ?>">
        <img src="logo 2.1.png" alt="BTech HRMS Logo" style="display:block; margin:0 auto 16px; max-width:160px;">
        <h2>Welcome Back</h2>
        <p>Sign in to BTech HRMS</p>

        <div class="input-box">
          <label>Employee Number</label>
          <input type="text" placeholder="Enter your employee number" id="employeeNumber">
        </div>

        <div class="input-box">
          <label>Password</label>
          <input type="password" placeholder="Enter your password" id="password">
        </div>

        <div class="remember">
          <input type="checkbox" id="remember">
          <label for="remember">Remember me for 30 days</label>
        </div>

        <button type="button" class="btn" id="loginBtn">Sign In</button>

        <div style="margin-top:10px; text-align:right;"><a href="#" id="forgotPasswordLink" style="color:#0d6efd; text-decoration:none;">Forgot password?</a></div>

        <div class="toggle-link">
          Don't have an account? <a href="#" id="openSignup">Create account</a>
        </div>
      </div>

      <div id="signupContainer" class="form-panel<?php echo $activeTab === 'signup' ? ' active' : ''; ?>">
        <h2>Create Account</h2>
        <p>Register with your employee number and email</p>

        <?php if ($signupMessage !== ''): ?>
          <div class="message <?php echo $signupMessageType === 'success' ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($signupMessage); ?>
          </div>
        <?php endif; ?>

        <?php if ($signupStage === 'form'): ?>
          <form method="POST" id="signupForm">

            <div class="input-box">
              <label>Email</label>
              <input type="email" name="email" value="<?php echo $signupValues['email']; ?>" required>
            </div>

            <div class="input-box">
              <label>Password</label>
              <input type="password" name="password" required>
            </div>

            <div class="input-box">
              <label>Confirm Password</label>
              <input type="password" name="confirmPassword" required>
            </div>

            <button type="submit" name="signup_submit" class="btn">Create Account</button>
          </form>

          <div class="toggle-link">
            Already have an account? <a href="#" id="openLogin">Sign in</a>
          </div>
        <?php else: ?>
          <form method="POST" id="otpForm">
              <div class="input-box">
                <label>Email</label>
                <input type="email" value="<?php echo $signupValues['email']; ?>" readonly>
              </div>

            <div class="input-box">
              <label>Verification Code</label>
              <input type="text" name="otp_code" maxlength="6" pattern="\d{6}" placeholder="Enter 6-digit code" required>
            </div>

            <button type="submit" name="verify_otp_submit" class="btn">Verify Code</button>
            <button type="submit" name="resend_otp_submit" class="btn" style="margin-top:12px; background:#6c757d;">Resend Code</button>
          </form>

          <div class="toggle-link">
            Already have an account? <a href="#" id="openLogin">Sign in</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Forgot password modal -->
  <div id="forgotPasswordModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:18px; width:360px; border-radius:10px; box-shadow:0 12px 36px rgba(0,0,0,0.28); margin:auto;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <strong>Reset Password</strong>
        <button id="closeModal" style="background:none;border:none;font-size:18px;cursor:pointer;">&times;</button>
      </div>
      <div style="margin-bottom:8px; color:#333;">Enter your employee number and registered email address. We'll send a reset link.</div>
      <input id="resetEmployeeNumber" placeholder="Employee Number" style="width:100%; padding:10px; margin-bottom:8px; border:1px solid #ccd0d5; border-radius:6px;">
      <input id="recoveryEmail" placeholder="Email" style="width:100%; padding:10px; margin-bottom:12px; border:1px solid #ccd0d5; border-radius:6px;">
      <div style="text-align:right;"><button id="sendResetBtn" class="btn">Send Reset Link</button></div>
      <div id="resetStatus" style="margin-top:8px; display:none; font-size:14px;"></div>
    </div>
  </div>

  <script>
    // Minimal forgot-password modal behavior
    (function(){
      const forgotLink = document.getElementById('forgotPasswordLink');
      const modal = document.getElementById('forgotPasswordModal');
      const closeModal = document.getElementById('closeModal');
      const sendBtn = document.getElementById('sendResetBtn');
      const status = document.getElementById('resetStatus');
      const empInput = document.getElementById('resetEmployeeNumber');
      const emailInput = document.getElementById('recoveryEmail');

      if (forgotLink) forgotLink.addEventListener('click', (e)=>{ e.preventDefault(); modal.style.display='flex'; });
      if (closeModal) closeModal.addEventListener('click', ()=>{ modal.style.display='none'; status.style.display='none'; });
      if (modal) modal.addEventListener('click', (e)=>{ if (e.target===modal) { modal.style.display='none'; status.style.display='none'; } });

      if (sendBtn) sendBtn.addEventListener('click', ()=>{
        const emp = empInput.value.trim();
        const email = emailInput.value.trim();
        status.style.display=''; status.style.color='#155724'; status.textContent = 'Sending...';
        const fd = new FormData(); fd.append('employeeNumber', emp); fd.append('email', email);
        fetch('../../backendPHP/request_password_reset.php', { method:'POST', body: fd })
          .then(r=>r.json())
          .then(j=>{
            if (j.status === 'success') { status.style.color='#155724'; status.textContent = j.message; setTimeout(()=>{ modal.style.display='none'; status.style.display='none'; }, 2200); }
            else { status.style.color='#721c24'; status.textContent = j.message || 'Unable to send reset link'; }
          }).catch(()=>{ status.style.color='#721c24'; status.textContent = 'Server error'; });
      });
    })();
  </script>

  

  <script>
    const tabLogin = document.getElementById('tabLogin');
    const tabSignup = document.getElementById('tabSignup');
    const loginContainer = document.getElementById('loginContainer');
    const signupContainer = document.getElementById('signupContainer');
    const openSignup = document.getElementById('openSignup');
    const openLogin = document.getElementById('openLogin');

    const showLogin = () => {
      tabLogin.classList.add('active');
      tabSignup.classList.remove('active');
      loginContainer.classList.add('active');
      signupContainer.classList.remove('active');
    };

    const showSignup = () => {
      tabSignup.classList.add('active');
      tabLogin.classList.remove('active');
      signupContainer.classList.add('active');
      loginContainer.classList.remove('active');
    };

    tabLogin.addEventListener('click', showLogin);
    tabSignup.addEventListener('click', showSignup);
    openSignup.addEventListener('click', (e) => { e.preventDefault(); showSignup(); });
    openLogin.addEventListener('click', (e) => { e.preventDefault(); showLogin(); });

    const registrationCompleted = <?php echo json_encode($registrationCompleted); ?>;
    const resetCompleted = <?php echo json_encode($resetCompleted); ?>;
    const registrationPending = <?php echo json_encode($registrationPending); ?>;
    function showTopLeftToast(message, duration = 3000) {
      let container = document.getElementById('topRightToastContainer');
      if (!container) {
        container = document.createElement('div');
        container.id = 'topRightToastContainer';
        Object.assign(container.style, {
          position: 'fixed',
          right: '20px',
          top: '20px',
          zIndex: 99999,
          display: 'flex',
          flexDirection: 'column',
          gap: '10px',
          alignItems: 'flex-end'
        });
        document.body.appendChild(container);
      }

      const toast = document.createElement('div');
      toast.textContent = message;
      Object.assign(toast.style, {
        background: '#218838',
        color: '#fff',
        padding: '10px 14px',
        borderRadius: '8px',
        boxShadow: '0 8px 24px rgba(0,0,0,0.15)',
        opacity: '0',
        transform: 'translateY(-6px)',
        transition: 'opacity 220ms ease, transform 220ms ease',
        maxWidth: '360px',
        fontSize: '14px'
      });

      container.appendChild(toast);
      requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; });

      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-6px)';
        setTimeout(() => { if (toast.parentElement) toast.parentElement.removeChild(toast); }, 300);
      }, duration);
    }

    if (registrationCompleted) {
      showLogin();
      showTopLeftToast('Registration complete. Redirecting to login...', 3000);
      setTimeout(() => { window.location.href = window.location.pathname; }, 3000);
    }

    if (registrationPending) {
      showLogin();
      showTopLeftToast('Thank you for registering. Please check your email or contact HR to confirm your registration.', 4500);
    }

    if (resetCompleted) {
      showLogin();
      showTopLeftToast('Password reset successful. You may now log in.', 3000);
      setTimeout(() => { window.location.href = window.location.pathname; }, 3000);
    }
  </script>
  <script src="../../js/user/login.js?v=2"></script>
</body>
</html>