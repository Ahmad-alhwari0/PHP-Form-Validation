<?php
require_once __DIR__ . '/includes/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUser = $userModel->findById((int) $_SESSION['user_id']);

if (!$currentUser || $currentUser['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

$errors = [];
$editUser = null;

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function postText(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], postText('token'))) {
        http_response_code(403);
        exit('Invalid request.');
    }

    $id = filter_var(postText('id'), FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);

    $action = postText('action');
    $target = $id !== false ? $userModel->findById($id) : null;

    if (!$target) {
        $errors[] = 'User not found.';
    } elseif ($action === 'delete') {
        if ($id === (int) $currentUser['id']) {
            $errors[] = 'You cannot delete your own account.';
        } else {
            $userModel->delete($id);
            header('Location: admin.php');
            exit;
        }
    } elseif ($action === 'update') {
        $data = [];

        foreach (
            [
                'first_name',
                'middle_name',
                'last_name',
                'family_name'
            ] as $field
        ) {
            $data[$field] = trim(postText($field));

            if (!Validator::name($data[$field])) {
                $errors[] = "Invalid $field.";
            }
        }

        $data['email'] = trim(postText('email'));
        $data['mobile'] = postText('mobile');

        if (
            !Validator::email($data['email'])
            || strlen($data['email']) > 255
        ) {
            $errors[] = 'Invalid email.';
        }

        if (!Validator::mobile($data['mobile'])) {
            $errors[] = 'Mobile must contain exactly 14 digits.';
        }

        $day = filter_var(postText('day'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 31]
        ]);

        $month = filter_var(postText('month'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 12]
        ]);

        $year = filter_var(postText('year'), FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
                'max_range' => (int) date('Y')
            ]
        ]);

        if (
            $day === false || $month === false || $year === false
            || !Validator::dateOfBirth($day, $month, $year)
        ) {
            $errors[] = 'Invalid birth date. Age must be over 16.';
        } else {
            $data['date_of_birth'] = sprintf(
                '%04d-%02d-%02d',
                $year,
                $month,
                $day
            );
        }

        if (empty($errors)) {
            $existing = $userModel->findByEmail($data['email']);

            if ($existing && (int) $existing['id'] !== $id) {
                $errors[] = 'Email belongs to another user.';
            }
        }

        if (empty($errors)) {
            try {
                $userModel->update($id, $data);

                if ($id === (int) $currentUser['id']) {
                    $_SESSION['email'] = $data['email'];
                }

                header('Location: admin.php');
                exit;
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) === 1062) {
                    $errors[] = 'Email belongs to another user.';
                } else {
                    error_log($exception->getMessage());
                    $errors[] = 'Update failed. Please try again.';
                }
            }
        }

        $editUser = array_merge($target, $data);
        $editUser['day'] = postText('day');
        $editUser['month'] = postText('month');
        $editUser['year'] = postText('year');
    } else {
        $errors[] = 'Invalid action.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editId = is_string($_GET['edit'])
        ? filter_var($_GET['edit'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ])
        : false;

    $editUser = $editId !== false
        ? $userModel->findById($editId)
        : null;

    if (!$editUser) {
        $errors[] = 'User not found.';
    } else {
        [$year, $month, $day] = explode('-', $editUser['date_of_birth']);
        $editUser['year'] = (int) $year;
        $editUser['month'] = (int) $month;
        $editUser['day'] = (int) $day;
    }
}

$users = $userModel->getAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin</title>
</head>

<body>
    <h1>Manage Users</h1>
    <a href="welcome.php">Welcome page / Logout</a>

    <?php foreach ($errors as $error): ?>
        <p role="alert"><?= e($error) ?></p>
    <?php endforeach; ?>

    <?php if ($editUser): ?>
        <h2>Edit User #<?= e($editUser['id']) ?></h2>

        <form method="POST" action="admin.php">
            <input type="hidden" name="token"
                value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id"
                value="<?= e($editUser['id']) ?>">

            <?php
            $labels = [
                'first_name' => 'First name',
                'middle_name' => 'Middle name',
                'last_name' => 'Last name',
                'family_name' => 'Family name'
            ];
            ?>

            <?php foreach ($labels as $field => $label): ?>
                <p>
                    <label>
                        <?= e($label) ?>
                        <input type="text" name="<?= e($field) ?>"
                            value="<?= e($editUser[$field]) ?>"
                            maxlength="50" required>
                    </label>
                </p>
            <?php endforeach; ?>

            <p>
                <label>
                    Email
                    <input type="email" name="email"
                        value="<?= e($editUser['email']) ?>"
                        maxlength="255" required>
                </label>
            </p>

            <p>
                <label>
                    Mobile
                    <input type="tel" name="mobile"
                        value="<?= e($editUser['mobile']) ?>"
                        pattern="[0-9]{14}" maxlength="14" required>
                </label>
            </p>

            <fieldset>
                <legend>Date of birth</legend>

                <label>
                    Day
                    <input type="number" name="day" min="1" max="31"
                        value="<?= e($editUser['day']) ?>" required>
                </label>

                <label>
                    Month
                    <input type="number" name="month" min="1" max="12"
                        value="<?= e($editUser['month']) ?>" required>
                </label>

                <label>
                    Year
                    <input type="number" name="year"
                        min="1" max="<?= date('Y') ?>"
                        value="<?= e($editUser['year']) ?>" required>
                </label>
            </fieldset>

            <button type="submit">Save changes</button>
            <a href="admin.php">Cancel</a>
        </form>
    <?php endif; ?>

    <h2>Registered Users</h2>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Password</th>
                <th>Date created</th>
                <th>Last login</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['id']) ?></td>
                    <td>
                        <?= e(
                            $user['first_name'] . ' ' .
                                $user['middle_name'] . ' ' .
                                $user['last_name'] . ' ' .
                                $user['family_name']
                        ) ?>
                    </td>
                    <td><?= e($user['email']) ?></td>
                    <td>Hidden</td>
                    <td><?= e($user['created_at']) ?></td>
                    <td><?= e($user['last_login_at'] ?? 'Never') ?></td>
                    <td>
                        <a href="admin.php?edit=<?= e($user['id']) ?>">
                            Edit
                        </a>
                    </td>
                    <td>
                        <?php if ((int) $user['id'] !== (int) $currentUser['id']): ?>
                            <form method="POST" action="admin.php"
                                onsubmit="return confirm('Delete this user?');">
                                <input type="hidden" name="token"
                                    value="<?= e($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id"
                                    value="<?= e($user['id']) ?>">
                                <button type="submit">Delete</button>
                            </form>
                        <?php else: ?>
                            Your account
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>