// Account Settings JavaScript

document.addEventListener('DOMContentLoaded', function() {
  // Tab Switching
  const tabButtons = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');

  tabButtons.forEach(button => {
    button.addEventListener('click', () => {
      const tabName = button.getAttribute('data-tab');
      
      // Remove active class from all tabs and contents
      tabButtons.forEach(btn => btn.classList.remove('active'));
      tabContents.forEach(content => content.classList.remove('active'));
      
      // Add active class to clicked tab and corresponding content
      button.classList.add('active');
      document.getElementById(tabName).classList.add('active');
    });
  });

  // Notification Modal
  const notifModal = document.getElementById('notifModal');
  const closeNotifModal = document.getElementById('closeNotifModal');

  if (closeNotifModal) {
    closeNotifModal.addEventListener('click', () => {
      notifModal.classList.remove('active');
    });
  }

  // Profile Picture Modal
  const profilePicModal = document.getElementById('profilePicModal');
  const changeProfilePicBtn = document.getElementById('changeProfilePic');
  const closeProfilePicModal = document.getElementById('closeProfilePicModal');
  const cancelProfilePic = document.getElementById('cancelProfilePic');
  const saveProfilePic = document.getElementById('saveProfilePic');
  const profilePicUpload = document.getElementById('profilePicUpload');
  const previewImage = document.getElementById('previewImage');
  const previewCircle = document.getElementById('previewCircle');

  let selectedProfilePic = null;

  if (changeProfilePicBtn) {
    changeProfilePicBtn.addEventListener('click', () => {
      profilePicModal.classList.add('active');
    });
  }

  if (closeProfilePicModal) {
    closeProfilePicModal.addEventListener('click', () => {
      profilePicModal.classList.remove('active');
      resetProfilePicModal();
    });
  }

  if (cancelProfilePic) {
    cancelProfilePic.addEventListener('click', () => {
      profilePicModal.classList.remove('active');
      resetProfilePicModal();
    });
  }

  if (profilePicUpload) {
    profilePicUpload.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        selectedProfilePic = file;
        const reader = new FileReader();
        reader.onload = (event) => {
          previewImage.src = event.target.result;
          previewImage.style.display = 'block';
          previewCircle.style.display = 'none';
        };
        reader.readAsDataURL(file);
      }
    });
  }

  if (saveProfilePic) {
    saveProfilePic.addEventListener('click', () => {
      if (selectedProfilePic) {
        // Update main profile picture
        const currentProfilePic = document.getElementById('currentProfilePic');
        const mainCircles = document.querySelectorAll('.profile-circle, .profile-circle-large');
        
        const reader = new FileReader();
        reader.onload = (event) => {
          currentProfilePic.src = event.target.result;
          currentProfilePic.style.display = 'block';
          
          // Hide initials circles
          mainCircles.forEach(circle => {
            if (!circle.classList.contains('profile-circle-large') || circle.parentElement.id !== 'previewCircle') {
              circle.style.display = 'none';
            }
          });
          
          showToast('Profile picture updated successfully!');
          profilePicModal.classList.remove('active');
          resetProfilePicModal();
        };
        reader.readAsDataURL(selectedProfilePic);
      }
    });
  }

  function resetProfilePicModal() {
    selectedProfilePic = null;
    if (previewImage) {
      previewImage.style.display = 'none';
      previewImage.src = '';
    }
    if (previewCircle) {
      previewCircle.style.display = 'flex';
    }
    if (profilePicUpload) {
      profilePicUpload.value = '';
    }
  }

  // Remove Profile Picture
  const removeProfilePicBtn = document.getElementById('removeProfilePic');
  if (removeProfilePicBtn) {
    removeProfilePicBtn.addEventListener('click', () => {
      const currentProfilePic = document.getElementById('currentProfilePic');
      const mainCircles = document.querySelectorAll('.profile-circle, .profile-circle-large');
      
      currentProfilePic.style.display = 'none';
      currentProfilePic.src = '';
      
      mainCircles.forEach(circle => {
        circle.style.display = 'flex';
      });
      
      showToast('Profile picture removed');
    });
  }

  // Profile Form Submission
  const profileForm = document.getElementById('profileForm');
  if (profileForm) {
    profileForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const firstName = document.getElementById('firstName').value;
      const lastName = document.getElementById('lastName').value;
      
      // Update profile name in sidebar
      const profileNameElement = document.querySelector('.profile-card h3');
      if (profileNameElement) {
        profileNameElement.textContent = `${firstName} ${lastName}`;
      }
      
      showToast('Profile updated successfully!');
    });
  }

  // Cancel Profile Changes
  const cancelProfileBtn = document.getElementById('cancelProfile');
  if (cancelProfileBtn) {
    cancelProfileBtn.addEventListener('click', () => {
      profileForm.reset();
      // Reset to original values
      document.getElementById('firstName').value = 'Juan';
      document.getElementById('lastName').value = 'Dela Cruz';
    });
  }

  // Password Form
  const passwordForm = document.getElementById('passwordForm');
  const newPasswordInput = document.getElementById('newPassword');
  const confirmPasswordInput = document.getElementById('confirmPassword');
  const strengthFill = document.getElementById('strengthFill');
  const strengthText = document.getElementById('strengthText');

  // Password Strength Checker
  if (newPasswordInput) {
    newPasswordInput.addEventListener('input', (e) => {
      const password = e.target.value;
      const strength = calculatePasswordStrength(password);
      
      strengthFill.className = 'strength-fill';
      
      if (password.length === 0) {
        strengthFill.style.width = '0%';
        strengthText.textContent = 'Password strength';
      } else if (strength < 40) {
        strengthFill.classList.add('weak');
        strengthText.textContent = 'Weak password';
        strengthText.style.color = '#f44336';
      } else if (strength < 70) {
        strengthFill.classList.add('medium');
        strengthText.textContent = 'Medium password';
        strengthText.style.color = '#ff9800';
      } else {
        strengthFill.classList.add('strong');
        strengthText.textContent = 'Strong password';
        strengthText.style.color = '#4caf50';
      }
    });
  }

  function calculatePasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength += 25;
    if (password.length >= 12) strength += 25;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 20;
    if (/[0-9]/.test(password)) strength += 15;
    if (/[^a-zA-Z0-9]/.test(password)) strength += 15;
    
    return strength;
  }

  // Password Form Submission
  if (passwordForm) {
    passwordForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const currentPassword = document.getElementById('currentPassword').value;
      const newPassword = newPasswordInput.value;
      const confirmPassword = confirmPasswordInput.value;
      
      // Validation
      if (newPassword !== confirmPassword) {
        alert('New passwords do not match!');
        return;
      }
      
      if (newPassword.length < 8) {
        alert('Password must be at least 8 characters long!');
        return;
      }
      
      const strength = calculatePasswordStrength(newPassword);
      if (strength < 40) {
        alert('Please choose a stronger password!');
        return;
      }
      
      // Simulate password change
      showToast('Password updated successfully!');
      passwordForm.reset();
      strengthFill.style.width = '0%';
      strengthText.textContent = 'Password strength';
      strengthText.style.color = '#666';
    });
  }

  // Toggle Password Visibility
  const togglePasswordButtons = document.querySelectorAll('.toggle-password');
  togglePasswordButtons.forEach(button => {
    button.addEventListener('click', () => {
      const targetId = button.getAttribute('data-target');
      const targetInput = document.getElementById(targetId);
      const icon = button.querySelector('i');
      
      if (targetInput.type === 'password') {
        targetInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        targetInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    });
  });

  // Two-Factor Authentication Toggle
  const toggle2FA = document.getElementById('toggle2FA');
  if (toggle2FA) {
    toggle2FA.addEventListener('change', (e) => {
      if (e.target.checked) {
        // Show confirmation dialog
        const confirm = window.confirm('Enable Two-Factor Authentication? You will need to set up an authenticator app.');
        if (confirm) {
          showToast('Two-Factor Authentication enabled');
        } else {
          e.target.checked = false;
        }
      } else {
        const confirm = window.confirm('Disable Two-Factor Authentication? This will make your account less secure.');
        if (confirm) {
          showToast('Two-Factor Authentication disabled');
        } else {
          e.target.checked = true;
        }
      }
    });
  }

  // Logout Other Sessions
  const logoutButtons = document.querySelectorAll('.session-item .btn-text-danger');
  logoutButtons.forEach(button => {
    button.addEventListener('click', () => {
      const sessionItem = button.closest('.session-item');
      sessionItem.style.opacity = '0.5';
      setTimeout(() => {
        sessionItem.remove();
        showToast('Session logged out');
      }, 300);
    });
  });

  // Notification Settings
  const notificationToggles = document.querySelectorAll('.notification-setting input[type="checkbox"]');
  notificationToggles.forEach(toggle => {
    toggle.addEventListener('change', () => {
      // Auto-save notification preferences
      console.log('Notification preference updated');
    });
  });

  // Save Notification Settings
  const saveNotifSettings = document.querySelector('#notifications .btn-primary');
  if (saveNotifSettings) {
    saveNotifSettings.addEventListener('click', () => {
      showToast('Notification settings saved!');
    });
  }

  // Save Preferences
  const savePreferences = document.querySelector('#preferences .btn-primary');
  if (savePreferences) {
    savePreferences.addEventListener('click', () => {
      showToast('Preferences saved!');
    });
  }

  // Theme Change
  const themeSelect = document.getElementById('theme');
  if (themeSelect) {
    themeSelect.addEventListener('change', (e) => {
      const theme = e.target.value;
      console.log('Theme changed to:', theme);
      // In a real app, you would apply the theme here
    });
  }

  // Danger Zone Actions
  const deactivateBtn = document.querySelector('.danger-card .btn-secondary');
  if (deactivateBtn) {
    deactivateBtn.addEventListener('click', () => {
      const confirm = window.confirm('Are you sure you want to deactivate your account? You can reactivate it anytime by logging in again.');
      if (confirm) {
        alert('Account deactivation process would start here. (Demo mode)');
      }
    });
  }

  const deleteAccountBtn = document.querySelector('.danger-card .btn-danger');
  if (deleteAccountBtn) {
    deleteAccountBtn.addEventListener('click', () => {
      const confirm = window.confirm('⚠️ WARNING: This will permanently delete your account and ALL data. This action CANNOT be undone!\n\nAre you absolutely sure?');
      if (confirm) {
        const doubleConfirm = prompt('Type "DELETE" to confirm account deletion:');
        if (doubleConfirm === 'DELETE') {
          alert('Account deletion process would start here. (Demo mode)');
        }
      }
    });
  }

  // Toast Notification Function
  function showToast(message) {
    const toast = document.getElementById('successToast');
    const toastMessage = document.getElementById('toastMessage');
    
    toastMessage.textContent = message;
    toast.classList.add('show');
    
    setTimeout(() => {
      toast.classList.remove('show');
    }, 3000);
  }

  // Close modals when clicking outside
  window.addEventListener('click', (e) => {
    if (e.target === notifModal) {
      notifModal.classList.remove('active');
    }
    if (e.target === profilePicModal) {
      profilePicModal.classList.remove('active');
      resetProfilePicModal();
    }
  });

  // Form auto-save indicator (optional feature)
  const formInputs = document.querySelectorAll('.settings-form input, .settings-form textarea, .settings-form select');
  formInputs.forEach(input => {
    input.addEventListener('change', () => {
      // Add visual indicator that form has unsaved changes
      const saveButton = input.closest('.tab-content').querySelector('.btn-primary');
      if (saveButton && !saveButton.classList.contains('unsaved')) {
        saveButton.classList.add('unsaved');
        saveButton.innerHTML = '<i class="fas fa-exclamation-circle"></i> Unsaved Changes';
      }
    });
  });

  // Reset unsaved indicator on save
  const allSaveButtons = document.querySelectorAll('.settings-form .btn-primary');
  allSaveButtons.forEach(button => {
    button.addEventListener('click', () => {
      button.classList.remove('unsaved');
      const originalText = button.getAttribute('data-original-text');
      if (!originalText) {
        button.setAttribute('data-original-text', button.innerHTML);
      }
    });
  });

  console.log('Account Settings page loaded successfully');
});