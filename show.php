<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false || $id === null) {
    http_response_code(404);
    exit('Student not found.');
}

try {
    $find = db()->prepare(
        'SELECT id, first_name, last_name, email, phone, course, enrolled_on
         FROM students WHERE id = :id LIMIT 1'
    );
    $find->execute(['id' => $id]);
    $student = $find->fetch();
} catch (PDOException) {
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

if ($student === false) {
    http_response_code(404);
    exit('Student not found.');
}

$enrolledOn = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $student['enrolled_on']);
$enrolledDate = $enrolledOn ? $enrolledOn->format('d M Y') : '—';
$pageTitle = 'Student details';
require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <h1><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>
        <p class="subtitle">Student details and enrollment information.</p>
    </div>
    <div class="detail-actions">
        <a class="button" href="edit.php?id=<?= e((string) $student['id']) ?>">Edit student</a>
        <a class="button button-secondary" href="index.php">Back to students</a>
    </div>
</section>

<section class="card detail-card" aria-label="Student details">
    <dl class="student-details">
        <div>
            <dt>Email address</dt>
            <dd><a href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a></dd>
        </div>
        <div>
            <dt>Phone</dt>
            <dd>
                <?php if ($student['phone'] !== null && $student['phone'] !== ''): ?>
                    <a href="tel:<?= e($student['phone']) ?>"><?= e($student['phone']) ?></a>
                <?php else: ?>
                    —
                <?php endif; ?>
            </dd>
        </div>
        <div>
            <dt>Course</dt>
            <dd><?= e($student['course']) ?></dd>
        </div>
        <div>
            <dt>Enrollment date</dt>
            <dd><?= e($enrolledDate) ?></dd>
        </div>
    </dl>
</section>
<?php

require __DIR__ . '/partials/footer.php';
