<?php
require_once __DIR__ . '/includes/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['logout_token'])) {
    $_SESSION['logout_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';

    if (
        is_string($token)
        && hash_equals($_SESSION['logout_token'], $token)
    ) {
        $_SESSION = [];
        session_destroy();

        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome</title>
</head>
<body>
    <h1>Welcome!</h1>
    <p>You have logged in successfully.</p>

    <p>
        Email:
        <?= htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8') ?>
    </p>

    <?php if ($_SESSION['role'] === 'admin'): ?>
        <p><a href="admin.php">Manage users</a></p>
    <?php endif; ?>

    <form method="POST" action="welcome.php">
        <input type="hidden" name="token"
               value="<?= htmlspecialchars(
                   $_SESSION['logout_token'],
                   ENT_QUOTES,
                   'UTF-8'
               ) ?>">

        <button type="submit">Logout</button>
    </form>
</body>
</html>