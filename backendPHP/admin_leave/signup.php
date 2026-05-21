<?php
include "../database/connection.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $firstName = $_POST['firstName'];
    $lastName  = $_POST['lastName'];
    $employeeNumber = $_POST['employeeNumber'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $gender = $_POST['gender'];
    $birthday = $_POST['birthday'];

    if($password != $confirmPassword){
        $message = "Passwords do not match.";
    }else{

        $password = md5($password);

        $sql = "INSERT INTO users 
        (first_name,last_name,employee_number,email,password,gender,birthday,role)
        VALUES (?,?,?,?,?,?,?,'employee')";

        $stmt = mysqli_prepare($connection,$sql);

        mysqli_stmt_bind_param($stmt,"sssssss",
        $firstName,$lastName,$employeeNumber,$email,$password,$gender,$birthday);

        if(mysqli_stmt_execute($stmt)){
            $message = "Account created successfully!";
        }else{
            $message = "Error creating account.";
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
<div class="message"><?php echo $message; ?></div>
<?php } ?>

<form method="POST">

<div class="input-box">
<label>First Name</label>
<input type="text" name="firstName" required>
</div>

<div class="input-box">
<label>Last Name</label>
<input type="text" name="lastName" required>
</div>

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

<div class="input-box">
<label>Gender</label>
<select name="gender">
<option value="">Select</option>
<option>Male</option>
<option>Female</option>
</select>
</div>

<div class="input-box">
<label>Birthday</label>
<input type="date" name="birthday">
</div>

<button type="submit">Sign Up</button>

</form>

</div>

</body>
</html>