
<?php
session_start();

require_once '../includes/db.php';

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $plainPassword = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($email) || empty($plainPassword)) {

        $message = "All fields are required!";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif (strlen($plainPassword) < 8) {

        $message = "Password must contain at least 8 characters.";
        $messageType = "error";

    } else {

        try {

            // Check whether email already exists
            $checkSql = "SELECT id FROM users WHERE email = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$email]);

            if ($checkStmt->fetch()) {

                $message = "Email already registered. Please login.";
                $messageType = "error";

            } else {

                // Hash password securely
                $password = password_hash(
                    $plainPassword,
                    PASSWORD_DEFAULT
                );

                // Insert user into database
                $sql = "INSERT INTO users
                        (username, email, password)
                        VALUES (?, ?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    $username,
                    $email,
                    $password
                ]);

                $message = "Registration successful! You can now login.";
                $messageType = "success";
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());
            $message = "Something went wrong. Please try again.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Create Account | ShopSphere</title>

    <link rel="stylesheet" href="../css/registration.css">
</head>

<body>

    <div class="register-container">

        <h2>Create Account</h2>

        <p class="subtitle">
            Join us and start shopping!
        </p>

        <?php if (!empty($message)): ?>

            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">

                <label for="username">Username</label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    value="<?php echo htmlspecialchars($username ?? ''); ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($email ?? ''); ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a password"
                    minlength="8"
                    required
                >

            </div>

            <button type="submit" class="register-btn">
                Register Now
            </button>

        </form>

        <p class="login-link">
            Already have an account?

            <a href="login.php">Login</a>
        </p>

    </div>

</body>
</html>
