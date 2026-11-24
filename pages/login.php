
<?php
session_start();

require_once '../includes/db.php';

// Redirect already logged-in users
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {

        $message = "Please enter your email and password.";

    } else {

        try {

            // Find user by email
            $sql = "SELECT id, username, email, password
                    FROM users
                    WHERE email = ?";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password
            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];

                header("Location: ../index.php");
                exit;

            } else {

                $message = "Invalid email or password.";

            }

        } catch (PDOException $e) {

            error_log($e->getMessage());
            $message = "Something went wrong. Please try again.";

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

    <title>Login | ShopSphere</title>

    <link rel="stylesheet" href="../css/login.css">
</head>

<body>

    <div class="login-container">

        <h2>Welcome Back!</h2>

        <p class="subtitle">
            Login to continue shopping
        </p>

        <?php if (!empty($message)): ?>

            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

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
                    placeholder="Enter your password"
                    required
                >

            </div>

            <button type="submit" class="login-btn">
                Login Now
            </button>

        </form>

        <p class="register-link">
            Don't have an account?

            <a href="registration.php">Register</a>
        </p>

    </div>

</body>
</html>
