function calculateLeaveDays() {
  const startDate = document.getElementById('leaveStartDate').value;
  const endDate = document.getElementById('leaveEndDate').value;
  const validationMessage = document.getElementById('validationMessage');
  const submitBtn = document.getElementById('submitBtn');
  
  // Clear previous validation
  validationMessage.style.display = 'none';
  validationMessage.textContent = '';
  
  if (!startDate || !endDate) {
    document.getElementById('numberOfDays').value = '';
    updateRemainingBalance(0);
    submitBtn.disabled = true;
    return;
  }
  
  const start = new Date(startDate);
  const end = new Date(endDate);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  // Validate: End date must be after start date
  if (end < start) {
    validationMessage.textContent = 'Error: End date must be after start date.';
    validationMessage.style.display = 'block';
    validationMessage.style.backgroundColor = '#f8d7da';
    validationMessage.style.borderColor = '#f5c6cb';
    validationMessage.style.color = '#721c24';
    document.getElementById('numberOfDays').value = '';
    updateRemainingBalance(0);
    submitBtn.disabled = true;
    return;
  }
  
  // Calculate working days (excluding weekends)
  let workingDays = 0;
  let currentDate = new Date(start);
  
  while (currentDate <= end) {
    const dayOfWeek = currentDate.getDay();
    // Skip Saturday (6) and Sunday (0)
    if (dayOfWeek !== 0 && dayOfWeek !== 6) {
      workingDays++;
    }
    currentDate.setDate(currentDate.getDate() + 1);
  }
  
  // Display number of days
  document.getElementById('numberOfDays').value = workingDays + ' day(s)';
  
  // Update remaining balance
  const remaining = updateRemainingBalance(workingDays);
  
  // Validate: Check if leave exceeds balance
  if (workingDays > currentUser.leaveBalance) {
    validationMessage.textContent = `Warning: You are requesting ${workingDays} day(s) but only have ${currentUser.leaveBalance} day(s) remaining.`;
    validationMessage.style.display = 'block';
    validationMessage.style.backgroundColor = '#fff3cd';
    validationMessage.style.borderColor = '#ffeaa7';
    validationMessage.style.color = '#856404';
    submitBtn.disabled = true;
  } else if (workingDays === 0) {
    validationMessage.textContent = 'Warning: Selected dates contain no working days (weekends only).';
    validationMessage.style.display = 'block';
    validationMessage.style.backgroundColor = '#fff3cd';
    validationMessage.style.borderColor = '#ffeaa7';
    validationMessage.style.color = '#856404';
    submitBtn.disabled = true;
  } else if (workingDays > 0) {
    // Show success message
    validationMessage.textContent = `✓ ${workingDays} working day(s) selected. Remaining balance: ${remaining} day(s)`;
    validationMessage.style.display = 'block';
    validationMessage.style.backgroundColor = '#d4edda';
    validationMessage.style.borderColor = '#c3e6cb';
    validationMessage.style.color = '#155724';
    submitBtn.disabled = false;
  } else {
    submitBtn.disabled = true;
  }
}

function updateRemainingBalance(requestedDays) {
  const remaining = currentUser.leaveBalance - requestedDays;
  document.getElementById('remainingBalance').textContent = remaining >= 0 ? remaining : 0;
  
  // Update visual indicator for remaining balance
  const remainingElement = document.getElementById('remainingBalance');
  if (remaining < 0) {
    remainingElement.style.color = '#dc3545';
    remainingElement.style.fontWeight = 'bold';
  } else if (remaining <= 5) {
    remainingElement.style.color = '#ffc107';
    remainingElement.style.fontWeight = 'bold';
  } else {
    remainingElement.style.color = '#28a745';
    remainingElement.style.fontWeight = 'normal';
  }
  
  return remaining;
}

function submitLeaveRequest() {
  // Validate required fields
  const leaveType = document.querySelector('input[name="leaveType"]:checked');
  const startDate = document.getElementById('leaveStartDate').value;
  const endDate = document.getElementById('leaveEndDate').value;
  const leaveReason = document.getElementById('leaveReason').value;
  const numberOfDays = parseInt(document.getElementById('numberOfDays').value) || 0;
  const emergencyContact = document.getElementById('emergencyContact').value;
  
  if (!leaveType) {
    alert('Please select a Type of Leave.');
    return;
  }
  
  if (!startDate || !endDate) {
    alert('Please select Leave Start and End Dates.');
    return;
  }
  
  if (!leaveReason.trim()) {
    alert('Please provide a Reason for Leave.');
    return;
  }
  
  if (numberOfDays <= 0) {
    alert('Please select valid leave dates.');
    return;
  }
  
  // Check leave balance
  if (numberOfDays > currentUser.leaveBalance) {
    alert(`Cannot submit: You are requesting ${numberOfDays} days but only have ${currentUser.leaveBalance} days remaining.`);
    return;
  }
  
  // Disable submit button to prevent double submission
  const submitBtn = document.getElementById('submitBtn');
  submitBtn.disabled = true;
  submitBtn.textContent = 'Submitting...';
  
  // Prepare form data
  const formData = {
    employeeId: currentUser.id,
    employeeName: currentUser.name,
    employeeEmail: currentUser.email,
    employeePosition: currentUser.position,
    employeeDepartment: document.getElementById('employeeDepartment').value,
    leaveType: leaveType.value,
    leaveStartDate: startDate,
    leaveEndDate: endDate,
    numberOfDays: numberOfDays,
    leaveReason: leaveReason,
    emergencyContact: emergencyContact,
    contactNumber: document.getElementById('contactNumber').value,
    dateSubmitted: new Date().toISOString(),
    remainingBalance: currentUser.leaveBalance - numberOfDays,
    status: 'Pending Approval',
    formType: 'leave'
  };

  // Send AJAX request
  fetch('../../backendPHP/insert_leave_request.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(formData)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Show success message
      alert('Leave request submitted successfully!\n\n' +
            'Request ID: #' + data.requestId + '\n' +
            'Duration: ' + numberOfDays + ' day(s)\n' +
            'Your request has been sent for approval.\n' +
            'You will receive email notifications about your request status.');
      
      // Update local leave balance
      currentUser.leaveBalance = data.remainingBalance;
      
      // Redirect to dashboard after 2 seconds
      setTimeout(() => {
        window.location.href = 'activity.php';
      }, 2000);
    } else {
      alert('Error: ' + data.message);
      submitBtn.disabled = false;
      submitBtn.textContent = 'Submit Leave Request';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('An error occurred while submitting your request. Please try again.');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Submit Leave Request';
  });
} 