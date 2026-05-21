<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
<link rel="stylesheet" href="../../css/user/login.css">

</head>
<body>
    <div class="login-container">
    <img src="logo 2.1.png" alt="BTech HRMS Logo">
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

    <!-- <div class="forgot">
      <a id="forgotPasswordLink">Forgot Password?</a>
    </div> -->

    <div class="remember">
      <input type="checkbox" id="remember">
      <label for="remember">Remember me for 30 days</label>
    </div>

    <button type="button" class="btn" id="loginBtn">Sign In</button>

    <!-- <div class="divider">
      <span>Or continue with</span>
    </div>

    <div class="help">
      Need help? <a href="#">Contact HR Support</a>
    </div> -->
  </div>

  <!-- Forgot Password Modal -->
  <div id="forgotPasswordModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Reset Your Password</h2>
        <button class="close-modal" id="closeModal">&times;</button>
      </div>
      
      <div class="modal-body">
        <!-- Step Indicator -->
        <div class="step-indicator">
          <div class="step active" data-step="1">1</div>
          <div class="step-line"></div>
          <div class="step" data-step="2">2</div>
          <div class="step-line"></div>
          <div class="step" data-step="3">3</div>
          <div class="step-line"></div>
          <div class="step" data-step="4">4</div>
        </div>
        
        <div class="step-labels">
          <div style="display: flex; justify-content: space-between; padding: 0 10px;">
            <span class="step-label">Employee ID</span>
            <span class="step-label">Email</span>
            <span class="step-label">OTP</span>
            <span class="step-label">New Password</span>
          </div>
        </div>

        <!-- Step 1: Employee Verification -->
        <div id="step1" class="step-container active">
          <p class="modal-description">
            Enter your employee number to verify your identity
          </p>
          
          <div class="input-group">
            <label>Employee Number</label>
            <div class="input-with-icon">
              <input type="text" id="verifyEmployeeNumber" placeholder="Enter your employee number">
              <div class="input-icon">👤</div>
            </div>
            <div class="error-message" id="empError">Please enter a valid employee number</div>
          </div>
        </div>

        <!-- Step 2: Email Verification -->
        <div id="step2" class="step-container">
          <p class="modal-description">
            Enter your registered email address to receive the verification code
          </p>
          
          <div class="email-note">
            Verification code will be sent to your registered email address
          </div>
          
          <div class="input-group">
            <label>Email Address</label>
            <div class="input-with-icon">
              <input type="email" id="recoveryEmail" placeholder="Enter your registered email">
              <div class="input-icon">✉️</div>
            </div>
            <div class="error-message" id="emailError">Please enter a valid email address</div>
          </div>
        </div>

        <!-- Step 3: OTP Verification -->
        <div id="step3" class="step-container">
          <p class="modal-description">
            Enter the 6-digit verification code sent to your email
          </p>
          
          <div class="otp-container">
            <input type="text" class="otp-input" maxlength="1" data-index="0">
            <input type="text" class="otp-input" maxlength="1" data-index="1">
            <input type="text" class="otp-input" maxlength="1" data-index="2">
            <input type="text" class="otp-input" maxlength="1" data-index="3">
            <input type="text" class="otp-input" maxlength="1" data-index="4">
            <input type="text" class="otp-input" maxlength="1" data-index="5">
          </div>
          
          <div class="timer">
            Code expires in: <span class="time" id="timer">05:00</span>
          </div>
          
          <div class="resend-otp">
            <a id="resendOTP" class="disabled">Resend code</a>
          </div>
          
          <div class="error-message" id="otpError" style="text-align: center;">Invalid verification code</div>
        </div>

        <!-- Step 4: New Password -->
        <div id="step4" class="step-container">
          <p class="modal-description">
            Create a new password for your account
          </p>
          
          <div class="input-group new-password">
            <label>New Password</label>
            <div class="input-with-icon">
              <input type="password" id="newPassword" placeholder="Enter new password">
              <div class="input-icon">🔒</div>
            </div>
            <div class="password-strength">
              Password strength: <span id="strengthText">None</span>
              <div class="strength-bar">
                <div class="strength-fill" id="strengthFill"></div>
              </div>
            </div>
            <div class="error-message" id="passwordError">Password must be at least 8 characters with uppercase, lowercase, and numbers</div>
          </div>
          
          <div class="input-group">
            <label>Confirm Password</label>
            <div class="input-with-icon">
              <input type="password" id="confirmPassword" placeholder="Confirm new password">
              <div class="input-icon">🔒</div>
            </div>
            <div class="error-message" id="confirmError">Passwords do not match</div>
          </div>
        </div>

        <!-- Step 5: Success -->
        <div id="step5" class="step-container">
          <div class="success-message">
            <div class="success-icon">✓</div>
            <h3>Password Reset Successful!</h3>
            <p>Your password has been successfully reset. You can now log in with your new password.</p>
          </div>
        </div>
      </div>
      
      <div class="modal-actions">
        <button class="btn back-btn" id="backBtn">Back</button>
        <button class="btn next-btn" id="nextBtn">Next</button>
        <button class="btn submit-btn" id="submitBtn" style="display: none;">Reset Password</button>
      </div>
    </div>
  </div>
</body>
<script src="../../js/user/login.js?v=2"></script>
</html>