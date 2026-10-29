<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Method Not Allowed';
    exit;
}

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    $pageTitle = 'Request rejected';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-state">
        <h2>Request could not be verified</h2>
        <p>Refresh the student list and try again.</p>
        <a class="button" href="index.php">Back to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$studentId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($studentId === false) {
    http_response_code(400);
    $pageTitle = 'Invalid request';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-state">
        <h2>Invalid student record</h2>
        <p>The student ID in this request is not valid.</p>
        <a class="button" href="index.php">Back to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

try {
    $delete = db()->prepare('DELETE FROM students WHERE id = :id');
    $delete->execute(['id' => $studentId]);

    if ($delete->rowCount() === 0) {
        flash('error', 'Student not found');
    } else {
        flash('success', 'Student deleted');
    }

    redirect('index.php');
} catch (PDOException $exception) {
    error_log('Student Records delete error: ' . $exception->getMessage());
    http_response_code(503);
    $pageTitle = 'Student records unavailable';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-state">
        <h2>Student could not be deleted</h2>
        <p>Student records are temporarily unavailable. Check that MySQL is running and try again.</p>
        <a class="button" href="index.php">Back to students</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
}
