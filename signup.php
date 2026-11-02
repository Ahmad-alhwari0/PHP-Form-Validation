<?php
require_once __DIR__ . '/includes/init.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [];

    $fields = [
        'first_name',
        'middle_name',
        'last_name',
        'family_name',
        'email',
        'mobile',
        'password',
        'confirm_password',
        'day',
        'month',
        'year'
    ];

    foreach ($fields as $field) {
        $value = $_POST[$field] ?? '';
        $input[$field] = is_string($value) ? $value : '';
    }

    $data = [
        'first_name' => trim($input['first_name']),
        'middle_name' => trim($input['middle_name']),
        'last_name' => trim($input['last_name']),
        'family_name' => trim($input['family_name']),
        'email' => trim($input['email']),
        'mobile' => $input['mobile'],
        'password' => $input['password']
    ];

    $nameFields = [
        'first_name',
        'middle_name',
        'last_name',
        'family_name'
    ];

    foreach ($nameFields as $field) {
        if (!Validator::name($data[$field])) {
            $errors[] = "$field must contain letters and be 1–50 characters.";
        }
    }

    if (
        !Validator::email($data['email'])
        || strlen($data['email']) > 255
    ) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!Validator::mobile($data['mobile'])) {
        $errors[] = 'Mobile must contain exactly 14 digits.';
    }

    if (!Validator::password($data['password'])) {
        $errors[] = 'Password must have at least 8 characters, uppercase and lowercase letters, a number and a symbol, with no spaces.';
    }

    if ($data['password'] !== $input['confirm_password']) {
        $errors[] = 'Passwords do not match.';
    }

    $day = filter_var($input['day'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 31]
    ]);

    $month = filter_var($input['month'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 12]
    ]);

    $year = filter_var($input['year'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => (int) date('Y')]
    ]);

    if (
        $day === false
        || $month === false
        || $year === false
        || !Validator::dateOfBirth($day, $month, $year)
    ) {
        $errors[] = 'Enter a valid birth date. You must be older than 16.';
    } else {
        $data['date_of_birth'] = sprintf(
            '%04d-%02d-%02d',
            $year,
            $month,
            $day
        );
    }

    if (empty($errors)) {
        try {
            if ($userModel->findByEmail($data['email'])) {
                $errors[] = 'This email is already registered.';
            } else {
                $userModel->register($data);

                header('Location: login.php?registered=1');
                exit;
            }
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                $errors[] = 'This email is already registered.';
            } else {
                error_log($e->getMessage());
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up</title>
</head>

<body>
    <h1>Sign Up</h1>

    <?php if (!empty($errors)): ?>
        <ul role="alert">
            <?php foreach ($errors as $error): ?>
                <li>
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="signup.php" method="POST" id="signup-form">
        <div>
            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name"
                maxlength="50" required>
        </div>

        <div>
            <label for="middle_name">Middle name</label>
            <input type="text" id="middle_name" name="middle_name"
                maxlength="50" required>
        </div>

        <div>
            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name"
                maxlength="50" required>
        </div>

        <div>
            <label for="family_name">Family name</label>
            <input type="text" id="family_name" name="family_name"
                maxlength="50" required>
        </div>

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                maxlength="255" required>
        </div>

        <div>
            <label for="mobile">Mobile — 14 digits</label>
            <input type="tel" id="mobile" name="mobile"
                inputmode="numeric" pattern="[0-9]{14}"
                minlength="14" maxlength="14" required>
        </div>

        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                minlength="8" autocomplete="new-password" required>
        </div>

        <div>
            <label for="confirm_password">Confirm password</label>
            <input type="password" id="confirm_password"
                name="confirm_password"
                minlength="8" autocomplete="new-password" required>
        </div>

        <fieldset>
            <legend>Date of birth</legend>

            <label for="day">Day</label>
            <input type="number" id="day" name="day"
                min="1" max="31" required>

            <label for="month">Month</label>
            <input type="number" id="month" name="month"
                min="1" max="12" required>

            <label for="year">Year</label>
            <input type="number" id="year" name="year"
                min="1" max="<?= date('Y') ?>" required>
        </fieldset>

        <button type="submit">Sign Up</button>
    </form>

    <p>Already have an account? <a href="login.php">Login</a></p>
    <script>
        const form = document.getElementById('signup-form');

        form.addEventListener('submit', function(event) {
            const errors = [];
            const value = name => form.elements[name].value;

            const namePattern = /^[\p{L}\p{M} ]{1,50}$/u;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const mobilePattern = /^[0-9]{14}$/;
            const passwordPattern =
                /^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\p{P}\p{S}])\S{8,}$/u;

            const names = {
                first_name: 'First name',
                middle_name: 'Middle name',
                last_name: 'Last name',
                family_name: 'Family name'
            };

            for (const [field, label] of Object.entries(names)) {
                const name = value(field).trim();

                if (!name || !namePattern.test(name)) {
                    errors.push(`${label} must contain letters and be 1–50 characters.`);
                }
            }

            const email = value('email').trim();

            if (!emailPattern.test(email) || email.length > 255) {
                errors.push('Enter a valid email address.');
            }

            if (!mobilePattern.test(value('mobile'))) {
                errors.push('Mobile must contain exactly 14 digits.');
            }

            const password = value('password');

            if (!passwordPattern.test(password) || /\s/u.test(password)) {
                errors.push(
                    'Password must have at least 8 characters, uppercase and lowercase letters, a number and a symbol, with no spaces.'
                );
            }

            if (password !== value('confirm_password')) {
                errors.push('Passwords do not match.');
            }

            const day = Number(value('day'));
            const month = Number(value('month'));
            const year = Number(value('year'));

            // setFullYear avoids JavaScript treating years 1–99 as 1901–1999.
            const birthDate = new Date(0);
            birthDate.setHours(0, 0, 0, 0);
            birthDate.setFullYear(year, month - 1, day);

            const validDate =
                Number.isInteger(day) &&
                Number.isInteger(month) &&
                Number.isInteger(year) &&
                year >= 1 &&
                month >= 1 && month <= 12 &&
                day >= 1 && day <= 31 &&
                birthDate.getFullYear() === year &&
                birthDate.getMonth() === month - 1 &&
                birthDate.getDate() === day;

            // تاريخ اليوم حسب توقيت PHP المحدد في init.php
            const limit = new Date(
                <?= (int) date('Y') ?>,
                <?= (int) date('n') - 1 ?>,
                <?= (int) date('j') ?>
            );

            limit.setFullYear(limit.getFullYear() - 16);

            if (!validDate || birthDate >= limit) {
                errors.push('Enter a valid birth date. You must be older than 16.');
            }

            if (errors.length > 0) {
                event.preventDefault();
                alert(errors.join('\n'));
            }
        });
    </script>
</body>

</html>