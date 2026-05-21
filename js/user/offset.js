// Auto-fill form on load
document.addEventListener('DOMContentLoaded', function () {
  checkUserAccess();
  initializeForm();
});

function checkUserAccess() {
  const facultyCheck = document.getElementById('facultyCheck');
  const facultyFields = document.getElementById('facultyFields');
  const submitBtn = document.getElementById('submitBtn');
  const accessMessage = document.getElementById('accessMessage');

  if (currentUser.type !== "TP") {
    // Non-teaching staff - show access restricted
    facultyCheck.style.display = 'block';
    facultyCheck.style.backgroundColor = '#ffebee';
    facultyCheck.style.borderColor = '#ffcdd2';
    facultyCheck.style.color = '#c62828';
    accessMessage.textContent = 'Offset forms are available for Teaching Staff only. Your account type: ' + currentUser.type;

    // Hide faculty fields and disable form
    facultyFields.style.display = 'none';
    submitBtn.disabled = true;
    submitBtn.textContent = 'ACCESS RESTRICTED';

    // Update header to show non-teaching
    document.getElementById('userType').textContent = currentUser.type;
  } else {
    // Faculty - show faculty fields
    facultyCheck.style.display = 'block';
    facultyCheck.style.backgroundColor = '#e8f5e9';
    facultyCheck.style.borderColor = '#c8e6c9';
    facultyCheck.style.color = '#2e7d32';
    accessMessage.innerHTML = '<strong>✓ FACULTY ACCESS GRANTED</strong><br>You may fill out the offset form below.';

    // Show faculty fields
    facultyFields.style.display = 'block';

    // Update header to show faculty
    document.getElementById('userType').textContent = currentUser.type;
  }
}

function initializeForm() {
  // Auto-fill user information
  document.getElementById('employeeName').value = currentUser.name;
  document.getElementById('employeeId').value = currentUser.id;
  document.getElementById('employeePosition').value = currentUser.position;
  document.getElementById('preparedBy').value = currentUser.name;

  // Auto-fill dates
  const now = new Date();
  document.getElementById('dateFiled').value = formatDate(now);
  document.getElementById('submitDate').value = formatDate(now);

  // Set minimum date for offset (tomorrow)
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  document.getElementById('offsetDate').min = formatDateForInput(tomorrow);

  // Update header info
  document.getElementById('userName').textContent = currentUser.name;
  const nameParts = currentUser.name.split(' ');
  if (nameParts.length >= 2) {
    document.getElementById('userAvatar').textContent =
      nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0);
  } else {
    document.getElementById('userAvatar').textContent = currentUser.name.substring(0, 2).toUpperCase();
  }
}

function formatDate(date) {
  return date.toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

function formatTime(date) {
  return date.toLocaleTimeString('en-PH', {
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatDateForInput(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function validateOffsetDates() {
  const originalDate = document.getElementById('originalDate').value;
  const originalTime = document.getElementById('originalTime').value;
  const offsetDate = document.getElementById('offsetDate').value;
  const offsetTime = document.getElementById('offsetTime').value;
  const validationMessage = document.getElementById('validationMessage');
  const submitBtn = document.getElementById('submitBtn');

  // Clear previous validation
  validationMessage.style.display = 'none';
  validationMessage.className = 'validation-message';
  validationMessage.textContent = '';

  // Check if all schedule fields are filled
  if (!originalDate || !originalTime || !offsetDate || !offsetTime) {
    submitBtn.disabled = true;
    return;
  }

  const original = new Date(originalDate + 'T' + originalTime);
  const offset = new Date(offsetDate + 'T' + offsetTime);
  const today = new Date();

  // Validation rules
  let isValid = true;
  let message = '';

  if (offset <= original) {
    message += '❌ Offset schedule must be after original schedule.\n';
    isValid = false;
  }

  if (offset <= today) {
    message += '❌ Offset date must be in the future.\n';
    isValid = false;
  }

  // Check if offset is within 30 days of original
  const diffTime = Math.abs(offset - original);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  if (diffDays > 30) {
    message += '❌ Offset must be within 30 days of original schedule.\n';
    isValid = false;
  }

  if (isValid) {
    validationMessage.textContent = '✓ Schedule is valid. Offset is within acceptable range.';
    validationMessage.className = 'validation-message valid';
    validationMessage.style.display = 'block';
    validateForm(); // Check other fields too
  } else {
    validationMessage.textContent = message;
    validationMessage.style.display = 'block';
    submitBtn.disabled = true;
  }
}

function validateForm() {
  // Only validate if user is teaching staff
  if (currentUser.type !== "TP") {
    return false;
  }

  const subjectCode = document.getElementById('subjectCode').value;
  const subjectDescription = document.getElementById('subjectDescription').value;
  const academicTerm = document.getElementById('academicTerm').value;
  const scheduleSection = document.getElementById('scheduleSection').value;
  const originalDate = document.getElementById('originalDate').value;
  const originalTime = document.getElementById('originalTime').value;
  const offsetDate = document.getElementById('offsetDate').value;
  const offsetTime = document.getElementById('offsetTime').value;
  const offsetReason = document.getElementById('offsetReason').value;
  const submitBtn = document.getElementById('submitBtn');

  const isValid = subjectCode.trim() &&
    subjectDescription.trim() &&
    academicTerm &&
    scheduleSection.trim() &&
    originalDate &&
    originalTime &&
    offsetDate &&
    offsetTime &&
    offsetReason.trim();

  submitBtn.disabled = !isValid;
  return isValid;
}

function submitOffsetForm() {
  // Check access first
  if (currentUser.type !== "TP") {
    alert('Access Denied: Offset forms are only available for Teaching Staff members.');
    return;
  }
  
  // Validate form
  if (!validateForm()) {
    alert('Please complete all required fields.');
    return;
  }
  
  // Validate dates
  const originalDate = document.getElementById('originalDate').value;
  const originalTime = document.getElementById('originalTime').value;
  const offsetDate = document.getElementById('offsetDate').value;
  const offsetTime = document.getElementById('offsetTime').value;
  
  const original = new Date(originalDate + 'T' + originalTime);
  const offset = new Date(offsetDate + 'T' + offsetTime);
  const today = new Date();
  
  if (offset <= original) {
    alert('Offset schedule must be after original schedule.');
    return;
  }
  
  if (offset <= today) {
    alert('Offset date must be in the future.');
    return;
  }
  
  // Disable submit button to prevent double submission
  const submitBtn = document.getElementById('submitBtn');
  submitBtn.disabled = true;
  submitBtn.textContent = 'SUBMITTING...';
  
  // Prepare form data
  const formData = new FormData();
  formData.append('action', 'submit_offset');
  formData.append('employee_number', currentUser.id);
  formData.append('subject_code', document.getElementById('subjectCode').value);
  formData.append('subject_description', document.getElementById('subjectDescription').value);
  formData.append('academic_term', document.getElementById('academicTerm').value);
  formData.append('schedule_section', document.getElementById('scheduleSection').value);
  formData.append('original_sched_date', originalDate);
  formData.append('original_sched_time', originalTime);
  formData.append('offset_sched_date', offsetDate);
  formData.append('offset_sched_time', offsetTime);
  formData.append('reason', document.getElementById('offsetReason').value);
  formData.append('prepaired_by', currentUser.name);
  formData.append('submit_date', new Date().toISOString().split('T')[0]);

  // Send AJAX request
  fetch('../../backendPHP/insert_offset.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    // First check if response is OK
    if (!response.ok) {
      throw new Error('Network response was not ok: ' + response.statusText);
    }
    return response.text();
  })
  .then(text => {
    console.log('Raw response:', text);
    
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      console.error('JSON parse error:', e);
      throw new Error('Server returned invalid JSON');
    }
    
    // Handle response
    if (data.success) {
      // SUCCESS: Show success message
      alert('✅ Offset form submitted successfully!\n\n' +
            'Form ID: ' + data.offset_id + '\n' +
            'Subject: ' + data.subject_code + '\n' +
            'Message: ' + data.message);
      
      // Optional: Reset form
      resetForm();
      
      // Optional: Redirect or reload
      // window.location.reload();
      
    } else {
      // ERROR: Show error message
      throw new Error(data.message || 'Failed to submit offset form');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    
    // Re-enable submit button
    submitBtn.disabled = false;
    submitBtn.textContent = 'SUBMIT OFFSET FORM';
    
    // Show error alert
    alert('❌ Submission Failed\n\n' + error.message);
  });
}

// Optional: Reset form function
function resetForm() {
  document.getElementById('subjectCode').value = '';
  document.getElementById('subjectDescription').value = '';
  document.getElementById('academicTerm').value = '';
  document.getElementById('scheduleSection').value = '';
  document.getElementById('originalDate').value = '';
  document.getElementById('originalTime').value = '';
  document.getElementById('offsetDate').value = '';
  document.getElementById('offsetTime').value = '';
  document.getElementById('offsetReason').value = '';
  
  // Reset submit button
  const submitBtn = document.getElementById('submitBtn');
  submitBtn.disabled = true;
  submitBtn.textContent = 'SUBMIT OFFSET FORM';
  
  // Clear validation message
  const validationMessage = document.getElementById('validationMessage');
  validationMessage.style.display = 'none';
  validationMessage.textContent = '';
}

// Real-time update of date/time
setInterval(() => {
  const now = new Date();
  document.getElementById('dateFiled').value = formatDate(now);
}, 60000); // Update every minute

// Auto-validate form on input
document.querySelectorAll('input, select, textarea').forEach(element => {
  element.addEventListener('input', function () {
    if (this.id === 'originalDate' || this.id === 'originalTime' ||
      this.id === 'offsetDate' || this.id === 'offsetTime') {
      validateOffsetDates();
    } else {
      validateForm();
    }
  });
  element.addEventListener('change', function () {
    if (this.id === 'originalDate' || this.id === 'originalTime' ||
      this.id === 'offsetDate' || this.id === 'offsetTime') {
      validateOffsetDates();
    } else {
      validateForm();
    }
  });
});