// DOM Elements
const forgotPasswordLink = document.getElementById('forgotPasswordLink');
const forgotPasswordModal = document.getElementById('forgotPasswordModal');
const closeModal = document.getElementById('closeModal');
const loginBtn = document.getElementById('loginBtn');
const backBtn = document.getElementById('backBtn');
const nextBtn = document.getElementById('nextBtn');
const submitBtn = document.getElementById('submitBtn');

// Step elements
const steps = document.querySelectorAll('.step');
const stepContainers = document.querySelectorAll('.step-container');

// Input elements
const recoveryEmail = document.getElementById('recoveryEmail');
const verifyEmployeeNumber = document.getElementById('verifyEmployeeNumber');
const newPassword = document.getElementById('newPassword');
const confirmPassword = document.getElementById('confirmPassword');
const otpInputs = document.querySelectorAll('.otp-input');
const resendOTPLink = document.getElementById('resendOTP');

// Error elements
const empError = document.getElementById('empError');
const emailError = document.getElementById('emailError');
const otpError = document.getElementById('otpError');
const passwordError = document.getElementById('passwordError');
const confirmError = document.getElementById('confirmError');

// State variables
let currentStep = 1;
let timerInterval;
let timerSeconds = 300; // 5 minutes
let generatedOTP = '';
let canResendOTP = false;

// Toast helper: creates a container and shows a toast message
function showToast(message, type = 'info', duration = 3000) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
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
  toast.className = 'toast-message';
  toast.textContent = message;
  const bg = type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : '#34495e';
  Object.assign(toast.style, {
    background: bg,
    color: '#fff',
    padding: '10px 14px',
    borderRadius: '6px',
    boxShadow: '0 6px 18px rgba(0,0,0,0.12)',
    opacity: '0',
    transform: 'translateY(-8px)',
    transition: 'opacity 220ms ease, transform 220ms ease',
    maxWidth: '320px',
    fontSize: '14px'
  });

  container.appendChild(toast);

  // animate in
  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
  });

  // remove after duration
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-8px)';
    setTimeout(() => container.removeChild(toast), 300);
  }, duration);
}
// Initialize
function initForgotPassword() {
  // Open modal when Forgot Password is clicked
  // forgotPasswordLink.addEventListener('click', () => {
  //   resetForgotPassword();
  //   forgotPasswordModal.style.display = 'flex';
  // });

  // Close modal
  closeModal.addEventListener('click', () => {
    forgotPasswordModal.style.display = 'none';
  });

  // Close modal when clicking outside
  forgotPasswordModal.addEventListener('click', (e) => {
    if (e.target === forgotPasswordModal) {
      forgotPasswordModal.style.display = 'none';
    }
  });

  // OTP input navigation
  otpInputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
      // Move to next input if current is filled
      if (e.target.value.length === 1 && index < otpInputs.length - 1) {
        otpInputs[index + 1].focus();
      }

      // Auto-focus first empty input
      if (e.target.value === '' && index > 0) {
        otpInputs[index - 1].focus();
      }
    });

    // Handle paste event
    input.addEventListener('paste', (e) => {
      e.preventDefault();
      const pasteData = e.clipboardData.getData('text').slice(0, 6);
      pasteData.split('').forEach((char, i) => {
        if (otpInputs[i]) {
          otpInputs[i].value = char;
        }
      });
    });
  });

  // Password strength checker
  newPassword.addEventListener('input', checkPasswordStrength);

  // Back button
  backBtn.addEventListener('click', goBack);

  // Next button
  nextBtn.addEventListener('click', goNext);

  // Submit button
  submitBtn.addEventListener('click', submitNewPassword);

  // Resend OTP
  resendOTPLink.addEventListener('click', resendOTP);

  // Enter key on login inputs should submit login
  const loginInputs = [document.getElementById('employeeNumber'), document.getElementById('password')];
  loginInputs.forEach(input => {
    if (input) {
      input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          loginBtn.click();
        }
      });
    }
  });

  // Login button
  loginBtn.addEventListener("click", function () {
    const employeeNumber = document.getElementById("employeeNumber").value;
    const password = document.getElementById("password").value;

    if (!employeeNumber || !password) {
      showToast("Please enter employee number and password", 'error', 3000);
      return;
    }

    const formData = new FormData();
    formData.append("employeeNumber", employeeNumber);
    formData.append("password", password);

    const loginUrl = new URL("../../backendPHP/login.php", window.location.href);
    loginUrl.searchParams.set('_t', Date.now());
    console.log('Login submit:', loginUrl.href, 'employeeNumber=', employeeNumber);

    fetch(loginUrl.href, {
      method: "POST",
      body: formData,
      cache: 'no-store'
    })
      .then(res => res.json())
      .then(data => {
        console.log('Login response:', data);
        if (data.status === "success") {
          showToast(data.message, 'success', 2500);

          let redirect = '';
          if (data.redirect && typeof data.redirect === 'string' && data.redirect.trim() !== '') {
            redirect = data.redirect.trim();
          } else {
            const role = (data.role || '').toString().trim().toLowerCase();
            redirect = (role === 'admin' || role === 'super_admin') ? '../admin/dashboard.php' : 'dashboard.php';
          }

          if (!redirect.startsWith('/')) {
            redirect = new URL(redirect, window.location.href).href;
          }

          console.log('Login redirect:', redirect, 'role:', data.role);
          // give the toast a moment to appear before redirecting
          setTimeout(() => { window.location.href = redirect; }, 900);
        } else {
          showToast(data.message || 'Login failed', 'error', 3000);
        }
      })
      .catch(err => {
        console.error(err);
        showToast("Server error. Try again.", 'error', 3000);
      });
  });
}

// Reset forgot password flow
function resetForgotPassword() {
  currentStep = 1;
  updateSteps();

  // Reset all inputs
  verifyEmployeeNumber.value = '';
  recoveryEmail.value = '';
  newPassword.value = '';
  confirmPassword.value = '';
  otpInputs.forEach(input => input.value = '');

  // Reset errors
  hideAllErrors();

  // Reset OTP timer
  clearInterval(timerInterval);
  timerSeconds = 300;
  updateTimer();
  resendOTPLink.classList.add('disabled');
  canResendOTP = false;

  // Reset buttons
  nextBtn.textContent = 'Next';
  nextBtn.style.display = 'block';
  submitBtn.style.display = 'none';
  backBtn.style.display = 'block';
}

// Update step indicators
function updateSteps() {
  // Update step circles
  steps.forEach(step => {
    const stepNum = parseInt(step.getAttribute('data-step'));
    step.classList.toggle('active', stepNum === currentStep);
  });

  // Show current step content
  stepContainers.forEach(container => {
    container.classList.remove('active');
  });
  document.getElementById(`step${currentStep}`).classList.add('active');

  // Update buttons
  if (currentStep === 1) {
    backBtn.style.display = 'none';
    nextBtn.textContent = 'Next';
  } else if (currentStep === 2) {
    backBtn.style.display = 'block';
    nextBtn.textContent = 'Send Code';
  } else if (currentStep === 3) {
    backBtn.style.display = 'block';
    nextBtn.textContent = 'Verify Code';
    startOTPTimer();
  } else if (currentStep === 4) {
    backBtn.style.display = 'block';
    nextBtn.style.display = 'none';
    submitBtn.style.display = 'block';
  } else if (currentStep === 5) {
    backBtn.style.display = 'none';
    nextBtn.style.display = 'none';
    submitBtn.style.display = 'none';
  }
}

// Go to next step
function goNext() {
  // Validate current step before proceeding
  if (!validateCurrentStep()) {
    return;
  }

  // Special actions for certain steps
  if (currentStep === 2) {
    // Generate and "send" OTP to email
    generatedOTP = generateOTP();
    const email = recoveryEmail.value;

    // In a real app, you would send the OTP to the user's email
    console.log(`OTP ${generatedOTP} sent to ${email}`);
    alert(`OTP sent to your email! (Check console for OTP: ${generatedOTP})`);
  }

  if (currentStep < 5) {
    currentStep++;
    updateSteps();
  }
}

// Go back to previous step
function goBack() {
  if (currentStep > 1) {
    currentStep--;
    updateSteps();
  }
}

// Validate current step
function validateCurrentStep() {
  hideAllErrors();

  if (currentStep === 1) {
    if (!verifyEmployeeNumber.value.trim()) {
      showError(empError);
      return false;
    }
    return true;
  }

  if (currentStep === 2) {
    if (!recoveryEmail.value.trim() || !isValidEmail(recoveryEmail.value)) {
      showError(emailError);
      return false;
    }
    return true;
  }

  if (currentStep === 3) {
    const enteredOTP = Array.from(otpInputs).map(input => input.value).join('');

    if (enteredOTP.length !== 6) {
      showError(otpError);
      return false;
    }

    // In a real app, you would verify against the server
    if (enteredOTP !== generatedOTP) {
      showError(otpError);
      return false;
    }

    return true;
  }

  if (currentStep === 4) {
    const password = newPassword.value;
    const confirm = confirmPassword.value;

    if (!isValidPassword(password)) {
      showError(passwordError);
      return false;
    }

    if (password !== confirm) {
      showError(confirmError);
      return false;
    }

    return true;
  }

  return true;
}

// Submit new password
function submitNewPassword() {
  if (validateCurrentStep()) {
    // In a real app, you would send the new password to the server
    console.log('Password reset successful for employee:', verifyEmployeeNumber.value);

    currentStep = 5;
    updateSteps();

    // Auto-close modal after 3 seconds
    setTimeout(() => {
      forgotPasswordModal.style.display = 'none';
      alert('Password reset successful! You can now log in with your new password.');
    }, 3000);
  }
}

// Generate random OTP
function generateOTP() {
  return Math.floor(100000 + Math.random() * 900000).toString();
}

// Start OTP timer
function startOTPTimer() {
  clearInterval(timerInterval);
  timerSeconds = 300; // 5 minutes
  updateTimer();
  resendOTPLink.classList.add('disabled');
  canResendOTP = false;

  timerInterval = setInterval(() => {
    timerSeconds--;
    updateTimer();

    if (timerSeconds <= 0) {
      clearInterval(timerInterval);
      resendOTPLink.classList.remove('disabled');
      canResendOTP = true;
    }
  }, 1000);
}

// Update timer display
function updateTimer() {
  const minutes = Math.floor(timerSeconds / 60);
  const seconds = timerSeconds % 60;
  document.getElementById('timer').textContent =
    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

// Resend OTP
function resendOTP() {
  if (!canResendOTP) return;

  generatedOTP = generateOTP();
  const email = recoveryEmail.value;

  console.log(`New OTP ${generatedOTP} sent to ${email}`);
  alert(`New OTP sent to your email! (Check console for OTP: ${generatedOTP})`);

  startOTPTimer();
}

// Check password strength
function checkPasswordStrength() {
  const password = newPassword.value;
  const strengthFill = document.getElementById('strengthFill');
  const strengthText = document.getElementById('strengthText');

  if (password.length === 0) {
    strengthFill.className = 'strength-fill';
    strengthFill.style.width = '0%';
    strengthText.textContent = 'None';
    return;
  }

  let strength = 0;

  // Length check
  if (password.length >= 8) strength++;
  if (password.length >= 12) strength++;

  // Complexity checks
  if (/[A-Z]/.test(password)) strength++;
  if (/[a-z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[^A-Za-z0-9]/.test(password)) strength++;

  // Update display
  if (strength <= 2) {
    strengthFill.className = 'strength-fill weak';
    strengthText.textContent = 'Weak';
  } else if (strength <= 4) {
    strengthFill.className = 'strength-fill medium';
    strengthText.textContent = 'Medium';
  } else {
    strengthFill.className = 'strength-fill strong';
    strengthText.textContent = 'Strong';
  }
}

// Validation functions
function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPassword(password) {
  return password.length >= 8 &&
    /[A-Z]/.test(password) &&
    /[a-z]/.test(password) &&
    /[0-9]/.test(password);
}

// Error handling
function showError(errorElement) {
  errorElement.style.display = 'block';
  errorElement.parentElement.classList.add('error');
}

function hideAllErrors() {
  document.querySelectorAll('.error-message').forEach(el => {
    el.style.display = 'none';
    el.parentElement.classList.remove('error');
  });
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', initForgotPassword);