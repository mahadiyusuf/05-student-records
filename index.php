<?php

require_once __DIR__ . '/src/helpers.php';

try {
    require_once __DIR__ . '/src/Database.php';
    $students = db()
        ->query('SELECT id, first_name, last_name, email, course, enrolled_on FROM students ORDER BY last_name, first_name')
        ->fetchAll();
} catch (PDOException) {
    http_response_code(503);
    $pageTitle = 'Database unavailable';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-card">
        <h1>Student records are temporarily unavailable</h1>
        <p>We couldn’t connect to the student database. Please make sure MySQL is running in XAMPP, then try again.</p>
        <a class="button" href="index.php">Try again</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = 'Students';
require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Students</h1>
        <p class="subtitle">Manage student records and enrollment details.</p>
    </div>
    <a class="button" href="create.php">Add student</a>
</section>

<section class="card" aria-label="Student records">
    <?php if ($students === []): ?>
        <div class="empty-state">
            <strong>No students yet</strong>
            Add a student to get started.
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Course</th>
                        <th scope="col">Enrolled</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <?php
                        $enrolledOn = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $student['enrolled_on']);
                        $enrolledDate = $enrolledOn ? $enrolledOn->format('d M Y') : '—';
                        ?>
                        <tr>
                            <td class="student-name"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></td>
                            <td><a href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a></td>
                            <td><?= e($student['course']) ?></td>
                            <td><?= e($enrolledDate) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="action-link" href="show.php?id=<?= e((string) $student['id']) ?>">View</a>
                                    <a class="action-link" href="edit.php?id=<?= e((string) $student['id']) ?>">Edit</a>
                                    <form class="action-form" action="delete.php" method="post"
                                        data-student-name="<?= e($student['first_name'] . ' ' . $student['last_name']) ?>"
                                        onsubmit="return confirm('Delete ' + this.dataset.studentName + '?');">
                                        <input type="hidden" name="id" value="<?= e((string) $student['id']) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <button class="action-button" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php

require __DIR__ . '/partials/footer.php';
