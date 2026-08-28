<?php

require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Training.php';

class TrainingController extends Controller {
    public function dashboard() {
        Auth::requireAuth();

        $trainingModel = new Training();
        $user = Auth::user();
        $empId = $user['employee_id'];

        $data = [
            'stats'         => $trainingModel->getDashboardStats(Auth::isSelfService() ? Auth::employeeId() : null),
            'courses'       => $trainingModel->getCoursesWithDetails(),
            'my_registrations' => Auth::isSelfService() ? $trainingModel->getRegistrationsWithDetails($empId) : [],
            'all_registrations' => Auth::hasRole(['Super Admin', 'HR Manager']) ? $trainingModel->getRegistrationsWithDetails() : [],
            'skills_matrix' => $trainingModel->getSkillsMatrix($empId)
        ];

        $this->view('training/dashboard', $data);
    }

    public function courses() {
        Auth::requireAuth();
        $trainingModel = new Training();
        $data = [
            'courses' => $trainingModel->getCoursesWithDetails()
        ];
        $this->view('training/courses', $data);
    }

    public function matrix() {
        Auth::requireAuth();
        $trainingModel = new Training();
        $employeeId = Auth::isAdmin() ? null : Auth::employeeId();
        $data = [
            'skills' => $trainingModel->getSkillsMatrix($employeeId)
        ];
        $this->view('training/matrix', $data);
    }

    public function register() {
        Auth::requireSelfService();
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $courseId = intval($_POST['course_id'] ?? 0);
        $user = Auth::user();
        $empId = $user['employee_id'];

        if (!$courseId || !$empId) {
            $this->json('error', 'Unable to complete registration. Invalid course or employee session.');
        }

        $trainingModel = new Training();
        // Check if already registered
        $existing = $trainingModel->db->fetchOne("SELECT id FROM training_registrations WHERE course_id = ? AND employee_id = ?", [$courseId, $empId]);
        if ($existing) {
            $this->json('warning', 'You are already registered for this course.');
        }

        $trainingModel->db->insert('training_registrations', [
            'course_id'   => $courseId,
            'employee_id' => $empId,
            'status'      => 'Approved', // Auto approve for demo
            'attendance_percentage' => 0
        ]);

        AuditLogger::log('TRAINING_REGISTER', 'LMS', "Registered for training course ID: {$courseId}");
        $this->json('success', 'Successfully registered for training course!');
    }

    public function quiz() {
        Auth::requireSelfService();
        $courseId = intval($_GET['course_id'] ?? 1);
        $trainingModel = new Training();
        $registration = $trainingModel->db->fetchOne("SELECT id FROM training_registrations WHERE course_id = ? AND employee_id = ?", [$courseId, Auth::employeeId()]);
        if (!$registration) Auth::deny();

        $data = [
            'course'    => $trainingModel->find($courseId),
            'questions' => $trainingModel->getQuizQuestions($courseId)
        ];

        $this->view('training/quiz', $data);
    }

    public function submitQuiz() {
        Auth::requireSelfService();
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.', [], 403);
        }
        $courseId = intval($_POST['course_id'] ?? 0);
        $answers = $_POST['answers'] ?? [];
        $user = Auth::user();
        $empId = $user['employee_id'];

        $trainingModel = new Training();
        if (!$empId || !$trainingModel->db->fetchOne("SELECT id FROM training_registrations WHERE course_id = ? AND employee_id = ?", [$courseId, $empId])) Auth::deny();
        $questions = $trainingModel->getQuizQuestions($courseId);
        
        $total = count($questions);
        $correct = 0;

        foreach ($questions as $q) {
            if (isset($answers[$q['id']]) && $answers[$q['id']] === $q['correct_option']) {
                $correct++;
            }
        }

        $score = $total > 0 ? round(($correct / $total) * 100) : 100;

        // Update registration score and issue certificate
        $trainingModel->db->query("UPDATE training_registrations SET quiz_score = ?, status = 'Completed', attendance_percentage = 100, certificate_file = 'cert_generated.pdf' WHERE course_id = ? AND employee_id = ?", [$score, $courseId, $empId]);

        AuditLogger::log('QUIZ_SUBMIT', 'LMS', "Completed quiz for course ID {$courseId} with score: {$score}%");
        $this->json('success', "Quiz completed! You scored {$score}%. Certificate generated.", ['score' => $score]);
    }

    public function certificate() {
        Auth::requireAuth();
        $registrationId = intval($_GET['id'] ?? 0);
        $trainingModel = new Training();
        $registration = $trainingModel->db->fetchOne(
            "SELECT r.*, c.title AS course_title, c.start_date, c.end_date,
                    e.first_name, e.last_name, e.employee_code
             FROM training_registrations r
             JOIN training_courses c ON c.id = r.course_id
             JOIN employees e ON e.id = r.employee_id
             WHERE r.id = ? AND r.status = 'Completed'",
            [$registrationId]
        );

        if (!$registration) {
            http_response_code(404);
            exit('Completed training certificate not found.');
        }
        Auth::requireOwnership($registration['employee_id']);

        $certificateNo = 'CERT-' . str_pad((string)$registration['id'], 6, '0', STR_PAD_LEFT);
        $employeeName = htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name'], ENT_QUOTES, 'UTF-8');
        $courseTitle = htmlspecialchars($registration['course_title'], ENT_QUOTES, 'UTF-8');
        $completionDate = htmlspecialchars($registration['end_date'] ?: $registration['start_date'], ENT_QUOTES, 'UTF-8');
        $score = number_format((float)$registration['quiz_score'], 1);
        $fileName = strtolower($certificateNo) . '.html';

        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('X-Content-Type-Options: nosniff');
        AuditLogger::log('DOWNLOAD_CERTIFICATE', 'LMS', "Downloaded certificate {$certificateNo}");
        echo '<!doctype html><html><head><meta charset="utf-8"><title>' . $certificateNo . '</title><style>'
            . 'body{font-family:Arial,sans-serif;background:#f4f7fb;margin:0;padding:40px;color:#111827}.certificate{max-width:900px;margin:auto;background:#fff;border:12px double #5145e5;padding:64px;text-align:center}.eyebrow{color:#5145e5;font-weight:700;letter-spacing:3px}.name{font-size:42px;margin:25px 0 10px}.course{font-size:25px;color:#4338ca}.meta{margin-top:35px;color:#64748b;line-height:1.8}@media print{body{background:#fff;padding:0}.certificate{box-sizing:border-box;min-height:95vh}}'
            . '</style></head><body><main class="certificate"><div class="eyebrow">CORE 3 HRMS</div><h1>Certificate of Completion</h1><p>This certifies that</p><div class="name">' . $employeeName . '</div><p>has successfully completed</p><div class="course">' . $courseTitle . '</div><div class="meta">Completion date: ' . $completionDate . '<br>Assessment score: ' . $score . '%<br>Certificate number: ' . $certificateNo . '</div></main></body></html>';
        exit;
    }
}
