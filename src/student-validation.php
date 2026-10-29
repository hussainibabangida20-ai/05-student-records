<?php

function studentCourseOptions(PDO $connection): array
{
    $column = $connection->query("SHOW COLUMNS FROM students LIKE 'course'")->fetch();

    if (!is_array($column) || !preg_match('/^enum\((.*)\)$/i', $column['Type'] ?? '', $matches)) {
        throw new RuntimeException('The students.course column is not an ENUM.');
    }

    return str_getcsv($matches[1], ',', "'", '\\');
}

function validateStudentData(
    array $student,
    PDO $connection,
    array $courseOptions,
    ?int $ignoreStudentId = null
): array {
    $errors = [];

    foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label) {
        if ($student[$field] === '') {
            $errors[$field] = $label . ' is required.';
        } elseif (!preg_match('/\A[\p{L} -]{2,60}\z/u', $student[$field])) {
            $errors[$field] = $label . ' must be 2–60 characters and contain only letters, spaces, or hyphens.';
        }
    }

    if ($student['email'] === '') {
        $errors['email'] = 'Email is required.';
    } elseif (filter_var($student['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif (strlen($student['email']) > 120) {
        $errors['email'] = 'Email must be 120 characters or fewer.';
    } else {
        $sql = 'SELECT id FROM students WHERE email = :email';
        $parameters = ['email' => $student['email']];

        if ($ignoreStudentId !== null) {
            $sql .= ' AND id <> :id';
            $parameters['id'] = $ignoreStudentId;
        }

        $emailCheck = $connection->prepare($sql . ' LIMIT 1');
        $emailCheck->execute($parameters);

        if ($emailCheck->fetch() !== false) {
            $errors['email'] = 'That email address is already in use.';
        }
    }

    if ($student['phone'] !== '' && !preg_match('/\A0[789][01]\d{8}\z/', $student['phone'])) {
        $errors['phone'] = 'Enter a valid phone number or leave this field blank.';
    }

    if (!in_array($student['course'], $courseOptions, true)) {
        $errors['course'] = 'Select a valid course.';
    }

    $enrolledOn = DateTimeImmutable::createFromFormat('!Y-m-d', $student['enrolled_on']);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (
        $enrolledOn === false
        || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        || $enrolledOn->format('Y-m-d') !== $student['enrolled_on']
    ) {
        $errors['enrolled_on'] = 'Enter a valid enrollment date.';
    } elseif ($enrolledOn > new DateTimeImmutable('today')) {
        $errors['enrolled_on'] = 'Enrollment date cannot be in the future.';
    }

    return $errors;
}
