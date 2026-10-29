<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$pageTitle = 'Student details';
$student = null;
$databaseUnavailable = false;
$studentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$notFound = $studentId === false;

if ($notFound) {
    http_response_code(404);
} else {
    try {
        $statement = db()->prepare(
            'SELECT id, first_name, last_name, email, phone, course, enrolled_on, created_at, updated_at
             FROM students
             WHERE id = :id'
        );
        $statement->execute(['id' => $studentId]);
        $record = $statement->fetch();

        if ($record === false) {
            http_response_code(404);
            $notFound = true;
        } else {
            $student = $record;
        }
    } catch (PDOException $exception) {
        error_log('Student Records database error: ' . $exception->getMessage());
        http_response_code(503);
        $databaseUnavailable = true;
    }
}

require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Student details</h1>
        <p class="subtitle">View student contact and enrollment information.</p>
    </div>
    <?php if (is_array($student)): ?>
        <a class="button" href="edit.php?id=<?= e($student['id']) ?>">Edit student</a>
    <?php endif; ?>
</section>

<?php if ($databaseUnavailable): ?>
    <section class="card error-state">
        <h2>Student records are temporarily unavailable</h2>
        <p>We couldn't load the student database. Check that MySQL is running, then try again.</p>
    </section>
<?php elseif ($notFound): ?>
    <section class="card error-state">
        <h2>Student not found</h2>
        <p>The requested student record does not exist.</p>
        <a class="button" href="index.php">Back to students</a>
    </section>
<?php else: ?>
    <?php
    $enrolledOn = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $student['enrolled_on']);
    ?>
    <section class="card details-card" aria-label="Student details">
        <dl class="student-details">
            <div class="detail-item">
                <dt>Name</dt>
                <dd><?= e($student['first_name'] . ' ' . $student['last_name']) ?></dd>
            </div>
            <div class="detail-item">
                <dt>Email</dt>
                <dd><a href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a></dd>
            </div>
            <div class="detail-item">
                <dt>Phone</dt>
                <dd><?= $student['phone'] !== null && $student['phone'] !== '' ? e($student['phone']) : 'Not provided' ?></dd>
            </div>
            <div class="detail-item">
                <dt>Course</dt>
                <dd><?= e($student['course']) ?></dd>
            </div>
            <div class="detail-item">
                <dt>Enrolled on</dt>
                <dd><?= $enrolledOn !== false ? e($enrolledOn->format('d M Y')) : e($student['enrolled_on']) ?></dd>
            </div>
            <div class="detail-item">
                <dt>Record created</dt>
                <dd><?= e($student['created_at']) ?></dd>
            </div>
            <div class="detail-item">
                <dt>Last updated</dt>
                <dd><?= e($student['updated_at']) ?></dd>
            </div>
        </dl>
        <a class="cancel-link" href="index.php">Back to students</a>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
