<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="d-flex align-items-center justify-content-between my-3">
        <div>
            <a href="index.php?page=training" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to LMS Dashboard</a>
            <h4 class="fw-bold text-light mb-1"><i class="fa-solid fa-book-open text-info me-2"></i> Course Catalog & Library</h4>
            <p class="text-secondary mb-0">Browse mandatory compliance, technical engineering, and executive leadership trainings</p>
        </div>
    </div>

    <div class="glass-card p-4 mb-4">
        <div class="table-responsive">
            <table class="table align-middle datatable-init">
                <thead>
                    <tr>
                        <th>Course Title</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Duration</th>
                        <th>Venue / Platform</th>
                        <th>Trainer</th>
                        <th>Budget</th>
                        <th>Schedule</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $c): ?>
                    <tr>
                        <td class="fw-bold text-light"><?= htmlspecialchars($c['title']); ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($c['category_name'] ?? 'General'); ?></span></td>
                        <td><span class="badge badge-soft-info"><?= $c['course_type']; ?></span></td>
                        <td><?= $c['duration_hours']; ?> hrs</td>
                        <td><?= htmlspecialchars($c['venue']); ?></td>
                        <td><?= htmlspecialchars($c['trainer_name'] ?? 'Internal HR'); ?></td>
                        <td class="fw-bold text-success">₱<?= number_format($c['budget'], 2); ?></td>
                        <td><?= $c['start_date']; ?> to <?= $c['end_date']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-info fw-bold btn-register-course" data-id="<?= $c['id']; ?>">
                                Register
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$('.btn-register-course').on('click', function() {
    const courseId = $(this).data('id');
    confirmAction('Register for Course', 'Do you want to enroll in this course?', 'Enroll', function() {
        $.post('index.php?page=training_register', {
            course_id: courseId,
            csrf_token: '<?= generate_csrf_token(); ?>'
        }, function(res) {
            if (res.status === 'success') {
                showToast('success', res.message);
            } else {
                Swal.fire('Notice', res.message, res.status);
            }
        }, 'json');
    });
});
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
