<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/student-validation.php';

$pageTitle = 'Edit student';
$student = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'course' => '',
    'enrolled_on' => '',
];
$errors = [];
$courseOptions = [];
$databaseUnavailable = false;
$notFound = false;
$studentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($studentId === false) {
    http_response_code(404);
    $notFound = true;
} else {
    try {
        $connection = db();
        $courseOptions = studentCourseOptions($connection);
        $select = $connection->prepare(
            'SELECT first_name, last_name, email, phone, course, enrolled_on
             FROM students WHERE id = :id'
        );
        $select->execute(['id' => $studentId]);
        $record = $select->fetch();

        if ($record === false) {
            http_response_code(404);
            $notFound = true;
        } else {
            $student = $record;
            $student['phone'] = $student['phone'] ?? '';
            $student['enrolled_on'] = (string) $student['enrolled_on'];
        }
    } catch (PDOException | RuntimeException $exception) {
        error_log('Student Records database error: ' . $exception->getMessage());
        http_response_code(503);
        $databaseUnavailable = true;
    }
}

if (!$databaseUnavailable && !$notFound && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($student as $field => $value) {
        $postedValue = $_POST[$field] ?? '';
        $student[$field] = is_string($postedValue) ? trim($postedValue) : '';
    }

    try {
        if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
            $errors['form'] = 'Your form session expired. Please review the form and submit it again.';
        } else {
            $errors = validateStudentData($student, $connection, $courseOptions, $studentId);
        }

        if ($errors === []) {
            $update = $connection->prepare(
                'UPDATE students
                 SET first_name = :first_name,
                     last_name = :last_name,
                     email = :email,
                     phone = :phone,
                     course = :course,
                     enrolled_on = :enrolled_on
                 WHERE id = :id'
            );
            $update->execute([
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'email' => $student['email'],
                'phone' => $student['phone'] === '' ? null : $student['phone'],
                'course' => $student['course'],
                'enrolled_on' => $student['enrolled_on'],
                'id' => $studentId,
            ]);

            flash('success', 'Student updated');
            redirect('index.php');
        }
    } catch (PDOException $exception) {
        error_log('Student Records update error: ' . $exception->getMessage());
        $errors['form'] = 'The student could not be saved. Please check the details and try again.';
    }
}

$csrfToken = csrfToken();
require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Edit student</h1>
        <p class="subtitle">Update the student’s details and enrollment information.</p>
    </div>
</section>

<?php if ($databaseUnavailable): ?>
    <section class="card error-state">
        <h2>Student records are temporarily unavailable</h2>
        <p>
            We couldn't load the student database. Check that MySQL is running and that
            the <strong>swd_students</strong> database is set up, then try again.
        </p>
    </section>
<?php elseif ($notFound): ?>
    <section class="card error-state">
        <h2>Student not found</h2>
        <p>The requested student record does not exist.</p>
        <a class="button" href="index.php">Back to students</a>
    </section>
<?php else: ?>
    <section class="card form-card">
        <?php
        $submitLabel = 'Save changes';
        require __DIR__ . '/partials/student-form.php';
        ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
