<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="d-flex align-items-center justify-content-between my-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--text);"><i class="fa-solid fa-graduation-cap me-2" style="color: var(--primary);"></i> Learning & Development Management</h4>
            <p class="text-secondary mb-0">Course library, training registrations, online quizzes, and skills competency radar</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?page=training_courses" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-book-open me-1"></i> Course Library</a>
            <a href="index.php?page=training_matrix" class="btn btn-primary btn-sm"><i class="fa-solid fa-chart-pie me-1"></i> Skills Matrix</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="glass-card p-3">
                <small class="text-secondary fw-bold">Completed Trainings</small>
                <h3 class="fw-bold text-success mb-0 mt-1"><?= $stats['completed']; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-3">
                <small class="text-secondary fw-bold">Pending Registrations</small>
                <h3 class="fw-bold text-warning mb-0 mt-1"><?= $stats['pending']; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-3">
                <small class="text-secondary fw-bold">Average Assessment Score</small>
                <h3 class="fw-bold text-info mb-0 mt-1"><?= $stats['avg_score']; ?>%</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-3">
                <small class="text-secondary fw-bold">Total LMS Budget</small>
                <h3 class="fw-bold mb-0 mt-1" style="color: var(--primary);">₱<?= number_format($stats['total_budget'], 2); ?></h3>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3" style="color: var(--text);"><i class="fa-solid fa-book me-2" style="color: var(--primary);"></i> Featured Training Courses</h5>
                <div class="row g-3">
                    <?php foreach ($courses as $course): ?>
                    <div class="col-md-6">
                        <div class="p-3 rounded bg-light border h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge badge-soft-info"><?= $course['course_type']; ?></span>
                                    <small class="text-secondary"><i class="fa-solid fa-clock me-1"></i> <?= $course['duration_hours']; ?> hrs</small>
                                </div>
                                <h6 class="fw-bold mb-1" style="color: var(--text);"><?= htmlspecialchars($course['title']); ?></h6>
                                <p class="text-secondary small mb-3"><?= htmlspecialchars($course['description']); ?></p>
                                <div class="small text-secondary mb-2">
                                    <div><i class="fa-solid fa-chalkboard-user me-1 text-primary"></i> Trainer: <?= htmlspecialchars($course['trainer_name'] ?? 'Internal HR'); ?></div>
                                    <div><i class="fa-solid fa-calendar me-1 text-warning"></i> Date: <?= $course['start_date']; ?></div>
                                </div>
                            </div>
                            <div class="pt-2 border-top d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-success small">Budget: ₱<?= number_format($course['budget'], 2); ?></span>
                                <button class="btn btn-sm btn-primary fw-bold btn-register-course" data-id="<?= $course['id']; ?>">Register Now</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3" style="color: var(--text);"><i class="fa-solid fa-chart-radar me-2" style="color: var(--primary);"></i> Employee Skills Matrix</h5>
                <canvas id="skillsRadarChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <div class="glass-card p-4 mb-4">
        <h5 class="fw-bold mb-3" style="color: var(--text);"><i class="fa-solid fa-list-check me-2" style="color: var(--success);"></i> My Registered Training Sessions & Quiz Status</h5>
        <div class="table-responsive">
            <table class="table align-middle datatable-init">
                <thead>
                    <tr>
                        <th>Course Title</th>
                        <th>Venue / Mode</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th>Attendance %</th>
                        <th>Quiz Score</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_registrations as $reg): ?>
                    <tr>
                        <td class="fw-bold" style="color: var(--text);"><?= htmlspecialchars($reg['course_title']); ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($reg['venue']); ?></span></td>
                        <td><?= $reg['start_date']; ?></td>
                        <td>
                            <?php if ($reg['status'] === 'Completed'): ?>
                                <span class="badge badge-soft-success">Completed</span>
                            <?php else: ?>
                                <span class="badge badge-soft-warning">In Progress</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="progress" style="height: 6px; width: 100px;">
                                <div class="progress-bar bg-info" style="width: <?= $reg['attendance_percentage']; ?>%;"></div>
                            </div>
                            <small class="text-secondary"><?= $reg['attendance_percentage']; ?>%</small>
                        </td>
                        <td><span class="fw-bold text-info"><?= $reg['quiz_score']; ?>%</span></td>
                        <td>
                            <?php if ($reg['status'] === 'Completed'): ?>
                                <button class="btn btn-sm btn-outline-success" onclick="Swal.fire('PDF Certificate Generated', 'Certificate #CERT-<?= $reg['id']; ?> verified and downloaded.', 'success')"><i class="fa-solid fa-certificate me-1"></i> Download Cert</button>
                            <?php else: ?>
                                <a href="index.php?page=training_quiz&course_id=<?= $reg['course_id']; ?>" class="btn btn-sm btn-warning fw-bold text-white"><i class="fa-solid fa-pen-to-square me-1"></i> Take Quiz</a>
                            <?php endif; ?>
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
$(document).ready(function() {
    $('.btn-register-course').on('click', function() {
        const courseId = $(this).data('id');
        confirmAction('Register for Training', 'Do you wish to submit a registration request for this training session?', 'Register', function() {
            $.post('index.php?page=training_register', {
                course_id: courseId,
                csrf_token: '<?= generate_csrf_token(); ?>'
            }, function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Notice', res.message, res.status);
                }
            }, 'json');
        });
    });
});
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>
