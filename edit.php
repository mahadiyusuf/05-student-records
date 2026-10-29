<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/student-validation.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false || $id === null) {
    http_response_code(404);
    exit('Student not found.');
}

$errors = [];
$courses = [];

try {
    $pdo = db();
    $courses = studentCourseOptions($pdo);
    $find = $pdo->prepare(
        'SELECT id, first_name, last_name, email, phone, course, enrolled_on
         FROM students WHERE id = :id LIMIT 1'
    );
    $find->execute(['id' => $id]);
    $student = $find->fetch();

    if ($student === false) {
        http_response_code(404);
        exit('Student not found.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach (['first_name', 'last_name', 'email', 'phone', 'course', 'enrolled_on'] as $field) {
            $value = $_POST[$field] ?? '';
            $student[$field] = is_scalar($value) ? trim((string) $value) : '';
        }

        $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
        if (!hash_equals(csrfToken(), $submittedToken)) {
            http_response_code(403);
            $errors['form'] = 'Your form session expired. Please review the form and submit it again.';
        }

        $errors = array_merge($errors, validateStudent($pdo, $student, $courses, (int) $id));

        if ($errors === []) {
            $update = $pdo->prepare(
                'UPDATE students
                 SET first_name = :first_name,
                     last_name = :last_name,
                     email = :email,
                     phone = :phone,
                     course = :course,
                     enrolled_on = :enrolled_on
                 WHERE id = :id'
            );

            try {
                $update->execute([
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'email' => $student['email'],
                    'phone' => $student['phone'] === '' ? null : $student['phone'],
                    'course' => $student['course'],
                    'enrolled_on' => $student['enrolled_on'],
                    'id' => $id,
                ]);
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) === 1062) {
                    $errors['email'] = 'That email address is already in use.';
                } else {
                    throw $exception;
                }
            }

            if ($errors === []) {
                flash('success', 'Student updated');
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
        <a class="button" href="index.php">Return to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = 'Edit student';
$formAction = 'edit.php?id=' . (int) $id;
$submitLabel = 'Save changes';
require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Edit student</h1>
        <p class="subtitle">Update this student’s details.</p>
    </div>
</section>

<section class="card form-card">
    <?php require __DIR__ . '/partials/student-form.php'; ?>
</section>
<?php

require __DIR__ . '/partials/footer.php';
