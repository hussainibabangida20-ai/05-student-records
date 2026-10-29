<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/student-validation.php';

$pageTitle = 'Add student';
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

try {
    $connection = db();
    $courseOptions = studentCourseOptions($connection);
} catch (PDOException | RuntimeException $exception) {
    error_log('Student Records database error: ' . $exception->getMessage());
    http_response_code(503);
    $databaseUnavailable = true;
}

if (!$databaseUnavailable && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        foreach ($student as $field => $value) {
            $postedValue = $_POST[$field] ?? '';
            $student[$field] = is_string($postedValue) ? trim($postedValue) : '';
        }

        if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
            $errors['form'] = 'Your form session expired. Please review the form and submit it again.';
        }

        if ($errors === []) {
            $errors = validateStudentData($student, $connection, $courseOptions);
        }

        if ($errors === []) {
            $insert = $connection->prepare(
                'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
                 VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
            );
            $insert->execute([
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'email' => $student['email'],
                'phone' => $student['phone'] === '' ? null : $student['phone'],
                'course' => $student['course'],
                'enrolled_on' => $student['enrolled_on'],
            ]);

            flash('success', 'Student added');
            redirect('index.php');
        }
    } catch (PDOException $exception) {
        error_log('Student Records save error: ' . $exception->getMessage());
        $errors['form'] = 'The student could not be saved. Please check the details and try again.';
    }
}

$csrfToken = csrfToken();
require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <h1>Add student</h1>
        <p class="subtitle">Enter the student’s details and enrollment information.</p>
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
<?php else: ?>
    <section class="card form-card">
        <?php require __DIR__ . '/partials/student-form.php'; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
