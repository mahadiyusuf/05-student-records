<?php

function validateStudent(PDO $connection, array $student, array $courses, ?int $ignoreId = null): array
{
    $errors = [];

    foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label) {
        if (!preg_match('/\A(?=[\p{L} -]{2,60}\z)[\p{L} -]*\p{L}[\p{L} -]*\z/u', $student[$field])) {
            $errors[$field] = $label . ' must be 2–60 characters and contain only letters, spaces, or hyphens.';
        }
    }

    if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        $sql = 'SELECT id FROM students WHERE email = :email';
        $parameters = ['email' => $student['email']];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :ignore_id';
            $parameters['ignore_id'] = $ignoreId;
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

    if (!in_array($student['course'], $courses, true)) {
        $errors['course'] = 'Select a valid course.';
    }

    $enrolledDate = DateTimeImmutable::createFromFormat('!Y-m-d', $student['enrolled_on']);
    $dateErrors = DateTimeImmutable::getLastErrors();
    $validDate = $enrolledDate !== false
        && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        && $enrolledDate->format('Y-m-d') === $student['enrolled_on'];

    if (!$validDate) {
        $errors['enrolled_on'] = 'Enter a valid enrollment date.';
    } elseif ($enrolledDate > new DateTimeImmutable('today')) {
        $errors['enrolled_on'] = 'The enrollment date cannot be in the future.';
    }

    return $errors;
}
