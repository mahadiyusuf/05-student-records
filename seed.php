<?php

require_once __DIR__ . '/src/helpers.php';

if (($_GET['confirm'] ?? null) !== 'yes') {
    $pageTitle = 'Seed sample students';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-card">
        <h1>Seed sample students?</h1>
        <p>This will add up to 50 realistic sample student records to your database. Existing records are left unchanged.</p>
        <a class="button" href="seed.php?confirm=yes">Insert sample students</a>
        <a class="button button-secondary" href="index.php">Cancel</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

require_once __DIR__ . '/src/Database.php';

$firstNames = [
    'Amina', 'Chinedu', 'Ngozi', 'Tunde', 'Chioma',
    'Ibrahim', 'Adaeze', 'Emeka', 'Fatima', 'Oluwaseun',
];
$lastNames = ['Adebayo', 'Okafor', 'Bello', 'Eze', 'Yusuf'];
$inserted = 0;
$alreadyPresent = 0;

try {
    $pdo = db();
    $courses = studentCourseOptions($pdo);
    if ($courses === []) {
        throw new RuntimeException('No student courses are configured.');
    }

    $find = $pdo->prepare('SELECT id FROM students WHERE email = :email LIMIT 1');
    $insert = $pdo->prepare(
        'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
         VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
    );

    for ($index = 0; $index < 50; $index++) {
        $firstName = $firstNames[$index % count($firstNames)];
        $lastName = $lastNames[intdiv($index, count($firstNames))];
        $email = sprintf(
            'sample.%02d.%s.%s@example.com',
            $index + 1,
            strtolower($firstName),
            strtolower($lastName)
        );

        $find->execute(['email' => $email]);
        if ($find->fetch() !== false) {
            $alreadyPresent++;
            continue;
        }

        $enrolledOn = (new DateTimeImmutable('today'))
            ->modify('-' . ($index * 3) . ' days')
            ->format('Y-m-d');
        $insert->execute([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => sprintf('080%08d', $index + 1),
            'course' => $courses[$index % count($courses)],
            'enrolled_on' => $enrolledOn,
        ]);
        $inserted++;
    }
} catch (PDOException | RuntimeException) {
    http_response_code(503);
    $pageTitle = 'Database unavailable';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-card">
        <h1>Unable to seed sample students</h1>
        <p>We couldn’t update the student database. Please make sure MySQL is running in XAMPP, then try again.</p>
        <a class="button" href="index.php">Return to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = 'Sample students added';
require __DIR__ . '/partials/header.php';
?>
<section class="card error-card">
    <h1>Sample student data is ready</h1>
    <p><?= e((string) $inserted) ?> students inserted; <?= e((string) $alreadyPresent) ?> already existed.</p>
    <a class="button" href="index.php">View students</a>
</section>
<?php

require __DIR__ . '/partials/footer.php';
