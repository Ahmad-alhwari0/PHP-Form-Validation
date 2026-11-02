<?php
require_once __DIR__ . '/includes/init.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $email = is_string($email) ? trim($email) : '';
    $password = is_string($password) ? $password : '';

    if (!Validator::email($email) || $password === '') {
        $error = 'Enter a valid email and password.';
    } else {
        $user = $userModel->login($email, $password);

        if ($user === null) {
            $error = 'Invalid email or password.';
        } else {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: welcome.php');
            }

            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
</head>

<body>
    <h1>Login</h1>

    <?php if (($_GET['registered'] ?? '') === '1'): ?>
        <p>Registered successfully. You can now log in.</p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                autocomplete="username" required>
        </div>

        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                autocomplete="current-password" required>
        </div>

        <button type="submit">Login</button>
    </form>

    <p><a href="signup.php">Create an account</a></p>
    <script>
        document.querySelector('form').addEventListener('submit', function(event) {
            const email = this.elements.email.value.trim();
            const password = this.elements.password.value;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(email) || password === '') {
                event.preventDefault();
                alert('Enter a valid email and password.');
            }
        });
    </script>
</body>

</html>