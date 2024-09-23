<?php
require_once('config.php'); // Include your database configuration
require_once('header.php');

// Set timezone
date_default_timezone_set('Asia/Kathmandu');

$error_message = '';
$success_message = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $email = $_POST['email'];

    // Check if the email is already registered
    $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_email = ?");
    $statement->execute(array($email));
    $existing_user = $statement->fetch(PDO::FETCH_ASSOC);

    if ($existing_user) {
        $error_message = '<p style="color:red;">This email is already registered.</p>';
    } else {
        // Generate a random one-time code
        $code = rand(100000, 999999); // Generate a 6-digit code

        // Insert user into the database with cust_status = 0 (inactive) and cust_code = $code
        $statement = $pdo->prepare("INSERT INTO tbl_customer (cust_email, cust_code, cust_status) VALUES (?, ?, 0)");
        $statement->execute(array($email, $code));

        // Send verification email with the code
        sendVerificationEmail($email, $code);

        $success_message = '<p style="color:green;">Registration successful! Please check your email for the verification code.</p>';
    }
}

// Handle code verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $email = $_POST['email'];
    $code = $_POST['code'];

    // Fetch user details based on the email
    $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_email = ?");
    $statement->execute(array($email));
    $result = $statement->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Check if the code matches
        if ($code == $result['cust_code']) {
            // Activate the user
            $statement = $pdo->prepare("UPDATE tbl_customer SET cust_code = NULL, cust_status = 1 WHERE cust_email = ?");
            $statement->execute(array($email));

            $success_message = '<p style="color:green;">Your email has been verified successfully. You can now log in to our website.</p><p><a href="' . BASE_URL . 'login.php" style="color:#167ac6;font-weight:bold;">Click here to login</a></p>';
        } else {
            $error_message = '<p style="color:red;">Invalid verification code.</p>';
        }
    } else {
        $error_message = '<p style="color:red;">No user found with that email address.</p>';
    }
}

// Function to send verification email
function sendVerificationEmail($email, $code) {
    $subject = "Email Verification Code";
    $message = "Your verification code is: " . $code;

    // Use mail() function or any email sending library (like PHPMailer)
    mail($email, $subject, $message);
}
?>

<div class="page-banner" style="background-color:#444;">
    <div class="inner">
        <h1>User Registration & Verification</h1>
    </div>
</div>

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="user-content">
                    <?php 
                        if ($error_message) {
                            echo $error_message;
                        }
                        if ($success_message) {
                            echo $success_message;
                        }
                    ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">Email address:</label>
                            <input type="email" name="email" id="email" required class="form-control">
                        </div>
                        <button type="submit" name="register" class="btn btn-primary">Register</button>
                    </form>

                    <hr>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">Email address:</label>
                            <input type="email" name="email" id="email" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="code">Verification Code:</label>
                            <input type="text" name="code" id="code" required class="form-control">
                        </div>
                        <button type="submit" name="verify" class="btn btn-primary">Verify</button>
                    </form>
                </div>                
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>
