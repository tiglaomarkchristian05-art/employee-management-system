<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="my-3">
        <a href="index.php?page=training" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to LMS</a>
        <h4 class="fw-bold mb-1" style="color: var(--text);"><i class="fa-solid fa-pen-to-square me-2" style="color: var(--warning);"></i> Quiz Assessment: <?= htmlspecialchars($course['title']); ?></h4>
        <p class="text-secondary">Please complete all assessment questions below to submit your evaluation and claim your certificate of completion.</p>
    </div>

    <div class="glass-card p-4 max-w-700 mx-auto mb-4">
        <form id="quizForm">
            <?= csrf_input(); ?>
            <input type="hidden" name="course_id" value="<?= $course['id']; ?>">

            <?php foreach ($questions as $index => $q): ?>
            <div class="p-3 mb-4 rounded bg-light border">
                <h6 class="fw-bold mb-3" style="color: var(--text);">Question <?= ($index + 1); ?>: <?= htmlspecialchars($q['question']); ?></h6>
                
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="answers[<?= $q['id']; ?>]" id="q<?= $q['id']; ?>_a" value="A" required>
                    <label class="form-check-label" for="q<?= $q['id']; ?>_a" style="color: var(--text);">A) <?= htmlspecialchars($q['option_a']); ?></label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="answers[<?= $q['id']; ?>]" id="q<?= $q['id']; ?>_b" value="B">
                    <label class="form-check-label" for="q<?= $q['id']; ?>_b" style="color: var(--text);">B) <?= htmlspecialchars($q['option_b']); ?></label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="answers[<?= $q['id']; ?>]" id="q<?= $q['id']; ?>_c" value="C">
                    <label class="form-check-label" for="q<?= $q['id']; ?>_c" style="color: var(--text);">C) <?= htmlspecialchars($q['option_c']); ?></label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="answers[<?= $q['id']; ?>]" id="q<?= $q['id']; ?>_d" value="D">
                    <label class="form-check-label" for="q<?= $q['id']; ?>_d" style="color: var(--text);">D) <?= htmlspecialchars($q['option_d']); ?></label>
                </div>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-warning text-white btn-lg w-100 fw-bold shadow-sm"><i class="fa-solid fa-paper-plane me-2"></i> Submit Assessment & Issue Certificate</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$('#quizForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: 'index.php?page=training_submit_quiz',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Assessment Passed!',
                    text: res.message,
                    confirmButtonText: 'View Certificate'
                }).then(() => {
                    window.location.href = 'index.php?page=training';
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
});
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
