<?php

$formAction = $formAction ?? 'create.php';
$submitLabel = $submitLabel ?? 'Save student';
$csrfToken = csrfToken();
?>
<form class="student-form" action="<?= e($formAction) ?>" method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-field">
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" type="text" minlength="2" maxlength="60" required
                value="<?= e($student['first_name'] ?? '') ?>"
                <?= isset($errors['first_name']) ? 'aria-invalid="true" aria-describedby="first_name-error"' : '' ?>>
            <?php if (isset($errors['first_name'])): ?>
                <span class="field-error" id="first_name-error"><?= e($errors['first_name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="last_name">Last name</label>
            <input id="last_name" name="last_name" type="text" minlength="2" maxlength="60" required
                value="<?= e($student['last_name'] ?? '') ?>"
                <?= isset($errors['last_name']) ? 'aria-invalid="true" aria-describedby="last_name-error"' : '' ?>>
            <?php if (isset($errors['last_name'])): ?>
                <span class="field-error" id="last_name-error"><?= e($errors['last_name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" maxlength="254" required
                value="<?= e($student['email'] ?? '') ?>"
                <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
            <?php if (isset($errors['email'])): ?>
                <span class="field-error" id="email-error"><?= e($errors['email']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="phone">Phone <span class="optional">(optional)</span></label>
            <input id="phone" name="phone" type="tel" inputmode="numeric" maxlength="11"
                pattern="0[789][01][0-9]{8}" value="<?= e($student['phone'] ?? '') ?>"
                <?= isset($errors['phone']) ? 'aria-invalid="true" aria-describedby="phone-error"' : '' ?>>
            <?php if (isset($errors['phone'])): ?>
                <span class="field-error" id="phone-error"><?= e($errors['phone']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="course">Course</label>
            <select id="course" name="course" required
                <?= isset($errors['course']) ? 'aria-invalid="true" aria-describedby="course-error"' : '' ?>>
                <option value="">Choose a course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course) ?>" <?= ($student['course'] ?? '') === $course ? 'selected' : '' ?>>
                        <?= e($course) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['course'])): ?>
                <span class="field-error" id="course-error"><?= e($errors['course']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="enrolled_on">Enrollment date</label>
            <input id="enrolled_on" name="enrolled_on" type="date" max="<?= e(date('Y-m-d')) ?>" required
                value="<?= e($student['enrolled_on'] ?? '') ?>"
                <?= isset($errors['enrolled_on']) ? 'aria-invalid="true" aria-describedby="enrolled_on-error"' : '' ?>>
            <?php if (isset($errors['enrolled_on'])): ?>
                <span class="field-error" id="enrolled_on-error"><?= e($errors['enrolled_on']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-actions">
        <button class="button" type="submit"><?= e($submitLabel) ?></button>
        <a class="button button-secondary" href="index.php">Cancel</a>
    </div>
</form>
