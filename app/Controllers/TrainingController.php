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
            'stats'         => $trainingModel->getDashboardStats(),
            'courses'       => $trainingModel->getCoursesWithDetails(),
            'my_registrations' => $trainingModel->getRegistrationsWithDetails($empId),
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
        $data = [
            'skills' => $trainingModel->getSkillsMatrix()
        ];
        $this->view('training/matrix', $data);
    }

    public function register() {
        Auth::requireAuth();
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
        Auth::requireAuth();
        $courseId = intval($_GET['course_id'] ?? 1);
        $trainingModel = new Training();

        $data = [
            'course'    => $trainingModel->find($courseId),
            'questions' => $trainingModel->getQuizQuestions($courseId)
        ];

        $this->view('training/quiz', $data);
    }

    public function submitQuiz() {
        Auth::requireAuth();
        $courseId = intval($_POST['course_id'] ?? 0);
        $answers = $_POST['answers'] ?? [];
        $user = Auth::user();
        $empId = $user['employee_id'];

        $trainingModel = new Training();
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
}
