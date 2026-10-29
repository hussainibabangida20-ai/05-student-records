<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$pageTitle = 'Students';
$students = [];
$databaseUnavailable = false;
$csrfToken = csrfToken();

try {
    $statement = db()->query(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students
         ORDER BY last_name, first_name'
    );
    $students = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log('Student Records database error: ' . $exception->getMessage());
    http_response_code(503);
    $databaseUnavailable = true;
}

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
    <?php if ($databaseUnavailable): ?>
        <div class="error-state">
            <h2>Student records are temporarily unavailable</h2>
            <p>
                We couldn't connect to the student database. Check that MySQL is running
                and that the <strong>swd_students</strong> database is set up, then try again.
            </p>
        </div>
    <?php elseif ($students === []): ?>
        <div class="empty-state">
            <h2>No students yet</h2>
            <p>Add a student to get started with your records.</p>
            <a class="button" href="create.php">Add student</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="student-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Course</th>
                        <th scope="col">Enrolled</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td class="student-name">
                                <a class="student-detail-link" href="show.php?id=<?= e($student['id']) ?>">
                                <?= e($student['first_name'] . ' ' . $student['last_name']) ?>
                                </a>
                            </td>
                            <td><?= e($student['email']) ?></td>
                            <td><?= e($student['course']) ?></td>
                            <td>
                                <?php
                                $enrolledOn = DateTimeImmutable::createFromFormat(
                                    '!Y-m-d',
                                    (string) $student['enrolled_on']
                                );
                                echo $enrolledOn !== false
                                    ? e($enrolledOn->format('d M Y'))
                                    : e($student['enrolled_on']);
                                ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="action-link" href="show.php?id=<?= e($student['id']) ?>">View</a>
                                    <a class="action-link" href="edit.php?id=<?= e($student['id']) ?>">Edit</a>
                                    <form class="delete-form" method="post" action="delete.php"
                                        onsubmit="return confirm(<?= e(json_encode(
                                            'Delete ' . $student['first_name'] . ' ' . $student['last_name'] . '?',
                                            JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP
                                        )) ?>)">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= e($student['id']) ?>">
                                        <button class="action-link danger" type="submit">Delete</button>
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

<?php require __DIR__ . '/partials/footer.php'; ?>
