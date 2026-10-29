<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$requestedCourse = is_string($_GET['course'] ?? null) ? $_GET['course'] : '';
$requestedPage = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$page = $requestedPage === false ? 1 : $requestedPage;
$perPage = 10;

try {
    $pdo = db();
    $courses = studentCourseOptions($pdo);
    $course = in_array($requestedCourse, $courses, true) ? $requestedCourse : '';

    $conditions = [];
    $parameters = [];

    if ($q !== '') {
        $conditions[] = '(first_name LIKE :first_name OR last_name LIKE :last_name OR email LIKE :email)';
        $searchTerm = '%' . $q . '%';
        $parameters['first_name'] = $searchTerm;
        $parameters['last_name'] = $searchTerm;
        $parameters['email'] = $searchTerm;
    }

    if ($course !== '') {
        $conditions[] = 'course = :course';
        $parameters['course'] = $course;
    }

    $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
    $count = $pdo->prepare('SELECT COUNT(*) FROM students' . $where);
    $count->execute($parameters);
    $totalStudents = (int) $count->fetchColumn();
    $totalPages = max(1, (int) ceil($totalStudents / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $list = $pdo->prepare(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students' . $where . '
         ORDER BY last_name, first_name, id
         LIMIT :limit OFFSET :offset'
    );
    foreach ($parameters as $name => $value) {
        $list->bindValue(':' . $name, $value, PDO::PARAM_STR);
    }
    $list->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $list->bindValue(':offset', $offset, PDO::PARAM_INT);
    $list->execute();
    $students = $list->fetchAll();
} catch (PDOException | RuntimeException) {
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

$firstStudent = $totalStudents === 0 ? 0 : $offset + 1;
$lastStudent = $totalStudents === 0 ? 0 : $offset + count($students);
$queryParameters = [];
if ($q !== '') {
    $queryParameters['q'] = $q;
}
if ($course !== '') {
    $queryParameters['course'] = $course;
}

$pageUrl = static function (int $pageNumber) use ($queryParameters): string {
    return 'index.php?' . http_build_query($queryParameters + ['page' => $pageNumber]);
};

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

<form class="student-filters" action="index.php" method="get">
    <div class="filter-field">
        <label for="q">Search students</label>
        <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Name or email">
    </div>
    <div class="filter-field">
        <label for="course">Course</label>
        <select id="course" name="course">
            <option value="">All courses</option>
            <?php foreach ($courses as $courseOption): ?>
                <option value="<?= e($courseOption) ?>" <?= $course === $courseOption ? 'selected' : '' ?>>
                    <?= e($courseOption) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="button" type="submit">Search</button>
    <?php if ($q !== '' || $course !== ''): ?>
        <a class="button button-secondary" href="index.php">Clear filters</a>
    <?php endif; ?>
</form>

<p class="result-count">Showing <?= e((string) $firstStudent) ?>–<?= e((string) $lastStudent) ?> of <?= e((string) $totalStudents) ?> students</p>

<section class="card" aria-label="Student records">
    <?php if ($students === []): ?>
        <div class="empty-state">
            <strong><?= $totalStudents === 0 ? 'No students found' : 'No students on this page' ?></strong>
            <?= $q !== '' || $course !== '' ? 'Try changing your search or course filter.' : 'Add a student to get started.' ?>
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

<?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Student pages">
        <?php if ($page > 1): ?>
            <a class="page-link" href="<?= e($pageUrl($page - 1)) ?>" rel="prev">Previous</a>
        <?php endif; ?>
        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
            <?php if ($pageNumber === $page): ?>
                <span class="page-link current-page" aria-current="page"><?= e((string) $pageNumber) ?></span>
            <?php else: ?>
                <a class="page-link" href="<?= e($pageUrl($pageNumber)) ?>"><?= e((string) $pageNumber) ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a class="page-link" href="<?= e($pageUrl($page + 1)) ?>" rel="next">Next</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
<?php

require __DIR__ . '/partials/footer.php';
