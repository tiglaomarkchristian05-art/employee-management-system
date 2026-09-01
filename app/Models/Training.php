<?php

require_once ROOT_PATH . 'core/Model.php';

class Training extends Model {
    protected $table = 'training_courses';

    public const COURSE_STATUSES = ['Draft','Scheduled','Ongoing','Completed','Cancelled'];
    public const PARTICIPATION_STATUSES = ['Assigned','Applied','Confirmed','Attended','Absent','Completed','Failed','Cancelled'];

    public function getCoursesWithDetails($includeInactive = true) {
        $sql = "SELECT c.*,cat.name category_name,t.name trainer_name,
                       (SELECT COUNT(*) FROM training_registrations r WHERE r.course_id=c.id AND r.status<>'Cancelled') enrolled_count
                FROM training_courses c
                LEFT JOIN training_categories cat ON cat.id=c.category_id
                LEFT JOIN trainers t ON t.id=c.trainer_id";
        if (!$includeInactive) $sql .= " WHERE c.is_active=1 AND c.status IN ('Scheduled','Ongoing')";
        return $this->db->fetchAll($sql . " ORDER BY c.start_date DESC,c.id DESC");
    }

    public function getCourseWithDetails($id) {
        return $this->db->fetchOne(
            "SELECT c.*,cat.name category_name,t.name trainer_name,t.email trainer_email,
                    (SELECT COUNT(*) FROM training_registrations r WHERE r.course_id=c.id AND r.status<>'Cancelled') enrolled_count
             FROM training_courses c LEFT JOIN training_categories cat ON cat.id=c.category_id LEFT JOIN trainers t ON t.id=c.trainer_id WHERE c.id=?",
            [(int)$id]
        );
    }

    public function getAvailableCourses($employeeId) {
        return $this->db->fetchAll(
            "SELECT c.*,cat.name category_name,t.name trainer_name,
                    (SELECT COUNT(*) FROM training_registrations x WHERE x.course_id=c.id AND x.status<>'Cancelled') enrolled_count,
                    r.id registration_id,r.status participation_status
             FROM training_courses c
             LEFT JOIN training_categories cat ON cat.id=c.category_id
             LEFT JOIN trainers t ON t.id=c.trainer_id
             LEFT JOIN training_registrations r ON r.course_id=c.id AND r.employee_id=?
             WHERE c.is_active=1 AND c.status IN ('Scheduled','Ongoing') AND c.end_date>=CURRENT_DATE
             ORDER BY c.start_date ASC",
            [(int)$employeeId]
        );
    }

    public function getRegistrationsWithDetails($employeeId = null, $courseId = null) {
        $sql = "SELECT r.*,c.title course_title,c.course_type,c.start_date,c.end_date,c.venue,c.status course_status,c.requirements,c.material_file,
                       e.first_name,e.last_name,e.employee_code,d.name department_name
                FROM training_registrations r JOIN training_courses c ON c.id=r.course_id JOIN employees e ON e.id=r.employee_id LEFT JOIN departments d ON d.id=e.department_id";
        $where=[];$params=[];
        if ($employeeId) {$where[]='r.employee_id=?';$params[]=(int)$employeeId;}
        if ($courseId) {$where[]='r.course_id=?';$params[]=(int)$courseId;}
        if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
        return $this->db->fetchAll($sql.' ORDER BY r.id DESC',$params);
    }

    public function getRegistration($id) {
        return $this->db->fetchOne(
            "SELECT r.*,c.title course_title,c.status course_status,c.start_date,c.end_date,c.material_file,c.requirements,
                    e.first_name,e.last_name,e.employee_code
             FROM training_registrations r JOIN training_courses c ON c.id=r.course_id JOIN employees e ON e.id=r.employee_id WHERE r.id=?",
            [(int)$id]
        );
    }

    public function getRegistrationForEmployee($courseId, $employeeId) {
        return $this->db->fetchOne("SELECT * FROM training_registrations WHERE course_id=? AND employee_id=?",[(int)$courseId,(int)$employeeId]);
    }

    public function assignEmployees($courseId, array $employeeIds, $assignedBy) {
        $course=$this->getCourseWithDetails($courseId);
        if (!$course || in_array($course['status'],['Completed','Cancelled'],true)) throw new RuntimeException('This training cannot accept assignments.');
        $employeeIds=array_values(array_unique(array_filter(array_map('intval',$employeeIds))));
        if (!$employeeIds) throw new RuntimeException('Select at least one employee.');
        $active=$this->db->fetchAll("SELECT id FROM employees WHERE id IN (".implode(',',array_fill(0,count($employeeIds),'?')).") AND status IN ('Active','Probationary')",$employeeIds);
        $validIds=array_map('intval',array_column($active,'id'));
        $pdo=$this->db->getConnection();$pdo->beginTransaction();$assigned=[];$duplicates=[];
        try {
            $locked=$this->db->fetchOne("SELECT capacity,status FROM training_courses WHERE id=? FOR UPDATE",[(int)$courseId]);
            if(!$locked||in_array($locked['status'],['Completed','Cancelled'],true))throw new RuntimeException('This training cannot accept assignments.');
            $current=(int)($this->db->fetchOne("SELECT COUNT(*) total FROM training_registrations WHERE course_id=? AND status<>'Cancelled'",[(int)$courseId])['total']??0);
            $capacity=max(1,(int)$locked['capacity']);
            foreach($validIds as $employeeId) {
                $existing=$this->getRegistrationForEmployee($courseId,$employeeId);
                if ($existing && $existing['status']!=='Cancelled') {$duplicates[]=$employeeId;continue;}
                if ($current>= $capacity) throw new RuntimeException('Training capacity has been reached.');
                if ($existing) {
                    $this->db->update('training_registrations',['status'=>'Assigned','assigned_by'=>(int)$assignedBy,'attendance_percentage'=>0,'assessment_result'=>null,'result_notes'=>null],"id=?",[$existing['id']]);
                } else {
                    $this->db->insert('training_registrations',['course_id'=>(int)$courseId,'employee_id'=>$employeeId,'status'=>'Assigned','assigned_by'=>(int)$assignedBy]);
                }
                $assigned[]=$employeeId;$current++;
            }
            $pdo->commit();
        } catch(Throwable $e) {if($pdo->inTransaction())$pdo->rollBack();throw $e;}
        return ['assigned'=>$assigned,'duplicates'=>$duplicates];
    }

    public function applyForCourse($courseId, $employeeId) {
        $course=$this->getCourseWithDetails($courseId);
        if (!$course || !$course['is_active'] || !in_array($course['status'],['Scheduled','Ongoing'],true) || strtotime($course['end_date'])<strtotime(date('Y-m-d'))) throw new RuntimeException('This training is not available for enrollment.');
        $pdo=$this->db->getConnection();$pdo->beginTransaction();
        try {
            $locked=$this->db->fetchOne("SELECT capacity,status,is_active,end_date FROM training_courses WHERE id=? FOR UPDATE",[(int)$courseId]);
            if(!$locked||!$locked['is_active']||!in_array($locked['status'],['Scheduled','Ongoing'],true)||strtotime($locked['end_date'])<strtotime(date('Y-m-d')))throw new RuntimeException('This training is not available for enrollment.');
            $existing=$this->getRegistrationForEmployee($courseId,$employeeId);
            if($existing&&$existing['status']!=='Cancelled')throw new RuntimeException('You already have an active participation record for this training.');
            $current=(int)($this->db->fetchOne("SELECT COUNT(*) total FROM training_registrations WHERE course_id=? AND status<>'Cancelled'",[(int)$courseId])['total']??0);
            if($current>=max(1,(int)$locked['capacity']))throw new RuntimeException('This training has reached its capacity.');
            if($existing){$this->db->update('training_registrations',['status'=>'Applied','attendance_percentage'=>0,'assessment_result'=>null,'result_notes'=>null],"id=?",[$existing['id']]);$id=(int)$existing['id'];}
            else $id=(int)$this->db->insert('training_registrations',['course_id'=>(int)$courseId,'employee_id'=>(int)$employeeId,'status'=>'Applied']);
            $pdo->commit();return $id;
        } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }

    public function getDepartmentEmployeeIds($departmentId) {
        return array_column($this->db->fetchAll("SELECT id FROM employees WHERE department_id=? AND status IN ('Active','Probationary')",[(int)$departmentId]),'id');
    }

    public function getSkillsMatrix($employeeId = null) {
        $sql="SELECT s.*,e.first_name,e.last_name,e.employee_code,d.name department_name FROM skills_matrix s JOIN employees e ON e.id=s.employee_id LEFT JOIN departments d ON d.id=e.department_id";
        $params=[];if($employeeId){$sql.=" WHERE s.employee_id=?";$params[]=(int)$employeeId;}
        return $this->db->fetchAll($sql." ORDER BY s.id DESC",$params);
    }

    public function getQuizQuestions($courseId) {return $this->db->fetchAll("SELECT * FROM quiz_questions WHERE course_id=?",[(int)$courseId]);}

    public function getDashboardStats($employeeId = null) {
        $where=$employeeId?' WHERE employee_id=?':'';$params=$employeeId?[(int)$employeeId]:[];
        $row=$this->db->fetchOne("SELECT SUM(status='Completed') completed,SUM(status IN ('Assigned','Applied','Confirmed')) pending,COALESCE(AVG(COALESCE(assessment_result,quiz_score)),0) avg_score FROM training_registrations".$where,$params)?:[];
        $budget=$employeeId?0:($this->db->fetchOne("SELECT COALESCE(SUM(budget),0) total FROM training_courses WHERE is_active=1 AND status<>'Cancelled'")['total']??0);
        return ['completed'=>(int)($row['completed']??0),'pending'=>(int)($row['pending']??0),'total_budget'=>(float)$budget,'avg_score'=>round((float)($row['avg_score']??0),1)];
    }
}
