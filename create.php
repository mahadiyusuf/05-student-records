<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/student-validation.php';

$student = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'course' => '',
    'enrolled_on' => '',
];
$errors = [];
$courses = [];

try {
    $pdo = db();
    $courses = studentCourseOptions($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach (array_keys($student) as $field) {
            $value = $_POST[$field] ?? '';
            $student[$field] = is_scalar($value) ? trim((string) $value) : '';
        }

        $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals(csrfToken(), $submittedToken)) {
            http_response_code(403);
            $errors['form'] = 'Your form session expired. Please review the form and submit it again.';
        }

        $errors = array_merge($errors, validateStudent($pdo, $student, $courses));

        if ($errors === []) {
            $insert = $pdo->prepare(
                'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
                 VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
            );

            try {
                $insert->execute([
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'email' => $student['email'],
                    'phone' => $student['phone'] === '' ? null : $student['phone'],
                    'course' => $student['course'],
                    'enrolled_on' => $student['enrolled_on'],
                ]);
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) === 1062) {
                    $errors['email'] = 'That email address is already in use.';
                } else {
                    throw $exception;
                }
            }

            if ($errors === []) {
                flash('success', 'Student added');
                redirect('index.php');
            }
        }
    }
} catch (PDOException | RuntimeException) {
    http_response_code(503);
    $pageTitle = 'Database unavailable';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-card">
        <h1>Student records are temporarily unavailable</h1>
        <p>We couldn’t load the student database. Please make sure MySQL is running in XAMPP, then try again.</p>
        <a class="button" href="create.php">Try again</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = 'Add student';
require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Add student</h1>
        <p class="subtitle">Enter the student’s details below.</p>
    </div>
</section>

<section class="card form-card">
    <?php require __DIR__ . '/partials/student-form.php'; ?>
</section>
<?php

require __DIR__ . '/partials/footer.php';
