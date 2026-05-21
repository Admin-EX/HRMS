
// Auto-fill form on load
document.addEventListener('DOMContentLoaded', function() {
  // Auto-fill user information
  document.getElementById('employeeName').value = currentUser.name;
  document.getElementById('employeeId').value = currentUser.id;
  document.getElementById('employeePosition').value = currentUser.position;
  document.getElementById('employeeDepartment').value = currentUser.department;
  document.getElementById('dateHired').value = formatDate(currentUser.dateHired);
  document.getElementById('preparedBy').value = currentUser.name;
  document.getElementById('contactNumber').value = currentUser.contact;
  
  // Auto-fill dates
  const now = new Date();
  document.getElementById('dateFiled').value = formatDate(now) + ' ' + formatTime(now);
  document.getElementById('submitDate').value = formatDate(now);
  
  // Update header info
  document.getElementById('userName').textContent = currentUser.name;
  document.getElementById('userType').textContent = currentUser.type + ' Staff';
  document.getElementById('userAvatar').textContent = 
    currentUser.firstName.charAt(0) + currentUser.lastName.charAt(0);
});

function formatDate(date) {
  const d = typeof date === 'string' ? new Date(date) : date;
  return d.toLocaleDateString('en-PH', {
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

function submitRequestForm() {
  // Validate required fields
  const requestType = document.getElementById('requestType').value;
  const reasons = document.querySelectorAll('input[name="reason"]:checked');
  
  if (!requestType) {
    alert('Please select a Request Type.');
    return;
  }
  
  if (reasons.length === 0) {
    alert('Please select at least one Reason for Request.');
    return;
  }

  // Calculate expected completion (3 working days)
  const now = new Date();
  const expectedDate = calculateWorkingDays(now, 3);
  const expectedCompletion = formatDate(expectedDate);

  // Prepare form data
  const formData = {
    employee_number: currentUser.id,
    type: requestType,
    reason: Array.from(reasons).map(cb => cb.value).join(', '),
    prepared_by: document.getElementById('preparedBy').value,
    submit_date: now.toISOString().slice(0, 19).replace('T', ' ') // MySQL datetime format
  };

  // Show loading state
  const submitButton = event.target;
  const originalText = submitButton.textContent;
  submitButton.disabled = true;
  submitButton.textContent = 'Submitting...';

  // AJAX request
  fetch('../../backendPHP/request_form_submit.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(formData)
  })
  .then(response => response.json())
  .then(data => {
    submitButton.disabled = false;
    submitButton.textContent = originalText;

    if (data.success) {
      alert('Request submitted successfully!\n\n' +
            'Form Type: ' + formData.type + '\n' +
            'Request ID: ' + data.formid + '\n' +
            'Expected Completion: ' + expectedCompletion + '\n\n' +
            'Your request has been sent to HR for processing.\n' +
            'You will receive email notifications about your request status.');
      
      // Redirect to dashboard after 2 seconds
      setTimeout(() => {
        window.location.href = 'activity.php';
      }, 2000);
    } else {
      alert('Error: ' + (data.message || 'Failed to submit request. Please try again.'));
    }
  })
  .catch(error => {
    submitButton.disabled = false;
    submitButton.textContent = originalText;
    console.error('Error:', error);
    alert('An error occurred while submitting the request. Please try again.');
  });
}

function calculateWorkingDays(startDate, workingDays) {
  let currentDate = new Date(startDate);
  let daysAdded = 0;
  
  while (daysAdded < workingDays) {
    currentDate.setDate(currentDate.getDate() + 1);
    // Skip weekends (0 = Sunday, 6 = Saturday)
    if (currentDate.getDay() !== 0 && currentDate.getDay() !== 6) {
      daysAdded++;
    }
  }
  
  return currentDate;
}

// Real-time update of date/time
setInterval(() => {
  const now = new Date();
  document.getElementById('dateFiled').value = formatDate(now) + ' ' + formatTime(now);
}, 60000); // Update every minute