<?php

require_once ROOT_PATH . 'core/Model.php';

class Training extends Model {
    protected $table = 'training_courses';

    public function getCoursesWithDetails() {
        $sql = "SELECT c.*, cat.name as category_name, t.name as trainer_name,
                       (SELECT COUNT(*) FROM training_registrations r WHERE r.course_id = c.id) as enrolled_count
                FROM training_courses c
                LEFT JOIN training_categories cat ON c.category_id = cat.id
                LEFT JOIN trainers t ON c.trainer_id = t.id
                ORDER BY c.start_date ASC";
        return $this->db->fetchAll($sql);
    }

    public function getRegistrationsWithDetails($employeeId = null) {
        $sql = "SELECT r.*, c.title as course_title, c.course_type, c.start_date, c.end_date, c.venue,
                       e.first_name, e.last_name, e.employee_code, d.name as department_name
                FROM training_registrations r
                JOIN training_courses c ON r.course_id = c.id
                JOIN employees e ON r.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id";
        
        if ($employeeId) {
            $sql .= " WHERE r.employee_id = ? ORDER BY r.id DESC";
            return $this->db->fetchAll($sql, [$employeeId]);
        }
        
        $sql .= " ORDER BY r.id DESC";
        return $this->db->fetchAll($sql);
    }

    public function getSkillsMatrix($employeeId = null) {
        $sql = "SELECT s.*, e.first_name, e.last_name, e.employee_code, d.name as department_name
                FROM skills_matrix s
                JOIN employees e ON s.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id";
        if ($employeeId) {
            $sql .= " WHERE s.employee_id = ? ORDER BY s.id DESC";
            return $this->db->fetchAll($sql, [$employeeId]);
        }
        $sql .= " ORDER BY s.id DESC";
        return $this->db->fetchAll($sql);
    }

    public function getQuizQuestions($courseId) {
        return $this->db->fetchAll("SELECT * FROM quiz_questions WHERE course_id = ?", [$courseId]);
    }

    public function getDashboardStats($employeeId = null) {
        $scope = $employeeId ? " AND employee_id = ?" : "";
        $params = $employeeId ? [$employeeId] : [];
        $completed = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM training_registrations WHERE status = 'Completed'" . $scope, $params)['cnt'] ?? 0;
        $pending = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM training_registrations WHERE status = 'Pending'" . $scope, $params)['cnt'] ?? 0;
        $totalBudget = $this->db->fetchOne("SELECT SUM(budget) as total FROM training_courses WHERE is_active = 1")['total'] ?? 0;
        $avgScore = $this->db->fetchOne("SELECT AVG(quiz_score) as avg_score FROM training_registrations WHERE quiz_score > 0" . $scope, $params)['avg_score'] ?? 0;

        return [
            'completed' => $completed,
            'pending' => $pending,
            'total_budget' => $totalBudget,
            'avg_score' => round($avgScore, 1)
        ];
    }
}
