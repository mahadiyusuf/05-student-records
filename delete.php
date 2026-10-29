<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false || $id === null) {
    http_response_code(404);
    exit('Student not found.');
}

$submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
if (!hash_equals(csrfToken(), $submittedToken)) {
    http_response_code(403);
    exit('Your form session expired. Please return to the student list and try again.');
}

try {
    $delete = db()->prepare('DELETE FROM students WHERE id = :id');
    $delete->execute(['id' => $id]);

    if ($delete->rowCount() === 0) {
        http_response_code(404);
        exit('Student not found.');
    }

    flash('success', 'Student deleted');
    redirect('index.php');
} catch (PDOException) {
    http_response_code(503);
    $pageTitle = 'Database unavailable';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-card">
        <h1>Student records are temporarily unavailable</h1>
        <p>We couldn’t update the student database. Please make sure MySQL is running in XAMPP, then try again.</p>
        <a class="button" href="index.php">Return to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
}
