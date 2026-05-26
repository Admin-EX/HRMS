<?php
include "../../database/connection.php";

$message = "";
$messageType = "success";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employeeNumber = trim($_POST['employeeNumber'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($employeeNumber === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $messageType = 'error';
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messageType = 'error';
        $message = "Please enter a valid email address.";
    } elseif ($password !== $confirmPassword) {
        $messageType = 'error';
        $message = "Passwords do not match.";
    } else {
        $checkSql = "SELECT employee_number, email FROM users WHERE employee_number = ? OR email = ? LIMIT 1";
        $checkStmt = mysqli_prepare($connection, $checkSql);

        if (!$checkStmt) {
            $messageType = 'error';
            $message = "Database error. Please try again later.";
        } else {
            mysqli_stmt_bind_param($checkStmt, "ss", $employeeNumber, $email);
            mysqli_stmt_execute($checkStmt);
            $result = mysqli_stmt_get_result($checkStmt);

            if ($existing = mysqli_fetch_assoc($result)) {
                $messageType = 'error';
                if ($existing['employee_number'] === $employeeNumber) {
                    $message = "Employee number already exists.";
                } else {
                    $message = "Email address already exists.";
                }
            } else {
                $passwordHash = md5($password);
                $sql = "INSERT INTO users (employee_number, email, password, role) VALUES (?, ?, ?, 'employee')";
                $stmt = mysqli_prepare($connection, $sql);

                if (!$stmt) {
                    $messageType = 'error';
                    $message = "Database error. Please try again later.";
                } else {
                    mysqli_stmt_bind_param($stmt, "sss", $employeeNumber, $email, $passwordHash);
                    if (mysqli_stmt_execute($stmt)) {
                        $messageType = 'success';
                        $message = "Account created successfully! You can now log in.";
                    } else {
                        $messageType = 'error';
                        $message = "Error creating account. Please try again.";
                    }
                    mysqli_stmt_close($stmt);
                }
            }

            mysqli_stmt_close($checkStmt);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Sign Up</title>

<style>

body{
font-family: Segoe UI;
background: linear-gradient(135deg,#0f5132,#0d47a1);
display:flex;
justify-content:center;
align-items:center;
height:100vh;
}

.signup-container{
background:#fff;
width:420px;
padding:30px;
border-radius:10px;
box-shadow:0 5px 15px rgba(0,0,0,0.3);
}

.signup-container h2{
text-align:center;
margin-bottom:20px;
}

.input-box{
margin-bottom:15px;
}

.input-box label{
display:block;
font-weight:600;
margin-bottom:5px;
}

.input-box input, select{
width:100%;
padding:10px;
border:1px solid #ccc;
border-radius:5px;
}

button{
width:100%;
padding:12px;
background:#28a745;
border:none;
color:white;
font-size:16px;
border-radius:5px;
cursor:pointer;
}

button:hover{
background:#218838;
}

.message{
text-align:center;
margin-bottom:15px;
color:green;
}

</style>

</head>
<body>

<div class="signup-container">

<h2>Create Account</h2>

<?php if($message!=""){ ?>
<div class="message" style="color: <?php echo $messageType === 'success' ? '#155724' : '#721c24'; ?>; background: <?php echo $messageType === 'success' ? '#d4edda' : '#f8d7da'; ?>; border: 1px solid <?php echo $messageType === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php } ?>

<form method="POST">

<div class="input-box">
<label>Employee Number</label>
<input type="text" name="employeeNumber" required>
</div>

<div class="input-box">
<label>Email</label>
<input type="email" name="email" required>
</div>

<div class="input-box">
<label>Password</label>
<input type="password" name="password" required>
</div>

<div class="input-box">
<label>Confirm Password</label>
<input type="password" name="confirmPassword" required>
</div>

<button type="submit">Sign Up</button>

</form>

</div>

</body>
</html>