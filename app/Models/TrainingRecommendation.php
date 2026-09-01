<?php

require_once ROOT_PATH . 'core/Model.php';
require_once APP_PATH . 'Models/Training.php';

class TrainingRecommendation extends Model {
    protected $table = 'ai_training_recommendations';
    public const ALGORITHM_VERSION = 'content_similarity_v1';
    public const MINIMUM_SCORE = 25.0;

    private const PROFICIENCY = ['Beginner'=>1,'Intermediate'=>2,'Advanced'=>3,'Expert'=>4];
    private const STOP_WORDS = ['and','the','for','with','from','into','this','that','training','course','program','skills','skill','employee','employees','of','to','in','a','an','or','on'];

    public function analyze(?int $employeeId = null): array {
        $employees = $this->employeeProfiles($employeeId);
        if ($employeeId && !$employees) throw new RuntimeException('Eligible employee not found.');
        $courses = $this->candidateCourses();
        if (!$courses) throw new RuntimeException('No active scheduled or ongoing training is available for analysis.');

        $generated = 0; $high = 0; $notifiedEmployees = []; $analyzed = 0;
        foreach ($employees as $employee) {
            $analyzed++;
            $this->db->query("UPDATE ai_training_recommendations SET status='Expired' WHERE employee_id=? AND status='Pending Review'", [(int)$employee['id']]);
            $history = $this->history((int)$employee['id']);
            $skills = $this->skillGaps((int)$employee['id']);
            foreach ($courses as $course) {
                $result = $this->score($employee, $course, $history, $skills);
                if (!$result || $result['score'] < self::MINIMUM_SCORE) continue;
                $existing = $this->db->fetchOne('SELECT * FROM ai_training_recommendations WHERE employee_id=? AND training_id=?', [(int)$employee['id'],(int)$course['id']]);
                if ($existing && in_array($existing['status'], ['Accepted','Dismissed','Assigned'], true)) continue;
                $data = [
                    'recommendation_score'=>$result['score'], 'priority'=>$result['priority'],
                    'reason'=>$result['reason'], 'detected_gap'=>$result['gap'],
                    'score_breakdown'=>json_encode($result['breakdown'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                    'algorithm_version'=>self::ALGORITHM_VERSION, 'status'=>'Pending Review',
                    'generated_at'=>date('Y-m-d H:i:s'), 'reviewed_by'=>null, 'reviewed_at'=>null,
                    'dismissed_reason'=>null, 'assigned_registration_id'=>null
                ];
                if ($existing) $this->db->update($this->table, $data, 'id=?', [(int)$existing['id']]);
                else $this->db->insert($this->table, ['employee_id'=>(int)$employee['id'],'training_id'=>(int)$course['id']] + $data);
                $generated++;
                if ($result['priority']==='High') $high++;
                if (!$existing || $existing['status']==='Expired') $notifiedEmployees[(int)$employee['id']] = true;
            }
        }
        return ['employees_analyzed'=>$analyzed,'recommendations_generated'=>$generated,'high_priority'=>$high,'notify_employee_ids'=>array_keys($notifiedEmployees)];
    }

    private function employeeProfiles(?int $employeeId): array {
        $sql="SELECT e.id,e.employee_code,e.hire_date,e.status,e.department_id,e.position_id,d.name department_name,p.title position_title
              FROM employees e JOIN departments d ON d.id=e.department_id JOIN positions p ON p.id=e.position_id
              WHERE e.status IN ('Active','Probationary')";
        $params=[]; if($employeeId){$sql.=' AND e.id=?';$params[]=$employeeId;}
        return $this->db->fetchAll($sql.' ORDER BY e.id',$params);
    }

    private function candidateCourses(): array {
        return $this->db->fetchAll("SELECT c.*,cat.name category_name,d.name target_department_name,p.title target_position_name,
                    (SELECT COUNT(*) FROM training_registrations r WHERE r.course_id=c.id AND r.status<>'Cancelled') enrolled_count
             FROM training_courses c
             LEFT JOIN training_categories cat ON cat.id=c.category_id
             LEFT JOIN departments d ON d.id=c.target_department_id
             LEFT JOIN positions p ON p.id=c.target_position_id
             WHERE c.is_active=1 AND c.status IN ('Scheduled','Ongoing') AND c.end_date>=CURRENT_DATE
             ORDER BY c.id");
    }

    private function history(int $employeeId): array {
        $rows=$this->db->fetchAll("SELECT r.*,c.category_id,c.title,c.end_date FROM training_registrations r JOIN training_courses c ON c.id=r.course_id WHERE r.employee_id=?",[$employeeId]);
        $byCourse=[];$completed=[];
        foreach($rows as $row){$byCourse[(int)$row['course_id']]=$row;if($row['status']==='Completed')$completed[(int)$row['course_id']]=$row;}
        return ['rows'=>$rows,'by_course'=>$byCourse,'completed'=>$completed];
    }

    private function skillGaps(int $employeeId): array {
        $rows=$this->db->fetchAll('SELECT skill_name,proficiency_level,target_level FROM skills_matrix WHERE employee_id=?',[$employeeId]);
        $gaps=[];
        foreach($rows as $row){$current=self::PROFICIENCY[$row['proficiency_level']]??2;$target=self::PROFICIENCY[$row['target_level']]??3;if($target>$current)$gaps[]=['name'=>$row['skill_name'],'gap'=>$target-$current,'current'=>$row['proficiency_level'],'target'=>$row['target_level']];}
        return $gaps;
    }

    private function score(array $employee,array $course,array $history,array $skills): ?array {
        $courseId=(int)$course['id'];
        $active=$history['by_course'][$courseId]??null;
        if($active && !in_array($active['status'],['Cancelled','Completed','Failed'],true))return null;
        if((int)$course['enrolled_count']>=max(1,(int)$course['capacity']))return null;

        $completed=$history['completed'][$courseId]??null;
        if($completed){
            $months=max(0,(int)$course['retraining_months']);
            if($months===0)return null;
            $completedDate=$completed['completed_at']?:$completed['end_date'];
            if($completedDate && strtotime($completedDate.' +'.$months.' months')>time())return null;
        }
        $prerequisite=(int)($course['prerequisite_course_id']??0);
        if($prerequisite && !isset($history['completed'][$prerequisite]))return null;

        $courseText=implode(' ',array_filter([$course['title'],$course['description'],$course['category_name'],$course['required_skills'],$course['target_position_name'],$course['target_department_name']]));
        $gapText=implode(' ',array_map(fn($x)=>str_repeat($x['name'].' ',max(1,(int)$x['gap'])),$skills));
        $skillSimilarity=$gapText!==''?$this->cosine($gapText,$courseText):0.0;
        $positionMatch=$course['target_position_id']!==null
            ? ((int)$course['target_position_id']===(int)$employee['position_id']?1.0:0.0)
            : $this->cosine($employee['position_title'],$courseText);
        $departmentMatch=$course['target_department_id']!==null
            ? ((int)$course['target_department_id']===(int)$employee['department_id']?1.0:0.0)
            : $this->cosine($employee['department_name'],$courseText);
        $historyGap=1.0;
        $assessmentNeed=0.0;$weakTitles=[];
        foreach($history['rows'] as $row){
            if((int)$row['category_id']!==(int)$course['category_id'])continue;
            $score=$row['assessment_result']!==null?(float)$row['assessment_result']:(float)$row['quiz_score'];
            if($row['status']==='Failed'||($score>0&&$score<75)){$assessmentNeed=max($assessmentNeed,min(1.0,(75-$score)/35));$weakTitles[]=$row['title'];}
        }
        $breakdown=[
            'skill_gap_match'=>round($skillSimilarity*35,2),
            'position_match'=>round($positionMatch*25,2),
            'department_match'=>round($departmentMatch*15,2),
            'training_history_gap'=>round($historyGap*15,2),
            'assessment_need'=>round($assessmentNeed*10,2)
        ];
        $score=round(array_sum($breakdown),2);
        $priority=$score>=70?'High':($score>=45?'Medium':'Low');
        $reasons=[];$gaps=[];
        if($skillSimilarity>=0.15&&$skills){$matched=array_values(array_filter($skills,fn($s)=>$this->cosine($s['name'],$courseText)>0));$names=array_slice(array_column($matched,'name'),0,3);if($names){$gaps=array_merge($gaps,$names);$reasons[]='Matches proficiency gaps in '.implode(', ',$names).'.';}}
        if($positionMatch>=0.5)$reasons[]='Relevant to the employee position: '.$employee['position_title'].'.';
        if($departmentMatch>=0.5)$reasons[]='Relevant to the '.$employee['department_name'].' department.';
        if($completed)$reasons[]='Retraining is due under the configured '.$course['retraining_months'].'-month recurrence.';
        else $reasons[]='No completed record for this training was found.';
        if($assessmentNeed>0)$reasons[]='A weak or failed result in a related category indicates improvement is needed.';
        if($prerequisite)$reasons[]='The configured prerequisite has been completed.';
        if(!$gaps)$gaps[]=$assessmentNeed>0?'Assessment improvement: '.$course['category_name']:($course['target_position_name']?:($course['target_department_name']?:'Uncompleted relevant training'));
        return ['score'=>$score,'priority'=>$priority,'reason'=>implode(' ',$reasons),'gap'=>implode(', ',array_unique($gaps)),'breakdown'=>$breakdown];
    }

    private function cosine(string $left,string $right): float {
        $a=$this->vector($left);$b=$this->vector($right);if(!$a||!$b)return 0.0;
        $dot=0.0;foreach($a as $token=>$count)$dot+=$count*($b[$token]??0);
        $na=sqrt(array_sum(array_map(fn($v)=>$v*$v,$a)));$nb=sqrt(array_sum(array_map(fn($v)=>$v*$v,$b)));
        return $na>0&&$nb>0?$dot/($na*$nb):0.0;
    }

    private function vector(string $text): array {
        $text=strtolower(trim(preg_replace('/[^a-z0-9]+/i',' ',$text)));$out=[];
        foreach(preg_split('/\s+/',$text,-1,PREG_SPLIT_NO_EMPTY) as $token){if(strlen($token)<2||in_array($token,self::STOP_WORDS,true))continue;$out[$token]=($out[$token]??0)+1;}
        return $out;
    }

    public function recommendations(?int $employeeId=null): array {
        $sql="SELECT a.*,e.employee_code,CONCAT(e.first_name,' ',e.last_name) employee_name,d.name department_name,p.title position_title,c.title training_title,c.start_date,c.status training_status,r.id registration_id,r.status registration_status
              FROM ai_training_recommendations a JOIN employees e ON e.id=a.employee_id JOIN departments d ON d.id=e.department_id JOIN positions p ON p.id=e.position_id JOIN training_courses c ON c.id=a.training_id LEFT JOIN training_registrations r ON r.course_id=a.training_id AND r.employee_id=a.employee_id";
        $params=[];if($employeeId){$sql.=' WHERE a.employee_id=?';$params[]=$employeeId;}
        return $this->db->fetchAll($sql.' ORDER BY FIELD(a.priority,\'High\',\'Medium\',\'Low\'),a.recommendation_score DESC,a.id DESC',$params);
    }

    public function summary(): array {
        $row=$this->db->fetchOne("SELECT COUNT(DISTINCT employee_id) employees_analyzed,COUNT(*) recommendations_generated,SUM(priority='High' AND status<>'Expired') high_count,SUM(priority='Medium' AND status<>'Expired') medium_count,SUM(status='Accepted') accepted_count FROM ai_training_recommendations WHERE status<>'Expired'")?:[];
        $top=$this->db->fetchOne("SELECT c.title,COUNT(*) total FROM ai_training_recommendations a JOIN training_courses c ON c.id=a.training_id WHERE a.status<>'Expired' GROUP BY a.training_id,c.title ORDER BY total DESC,c.title LIMIT 1");
        $dept=$this->db->fetchOne("SELECT d.name,COUNT(*) total FROM ai_training_recommendations a JOIN employees e ON e.id=a.employee_id JOIN departments d ON d.id=e.department_id WHERE a.status<>'Expired' GROUP BY d.id,d.name ORDER BY total DESC,d.name LIMIT 1");
        return ['employees_analyzed'=>(int)($row['employees_analyzed']??0),'recommendations_generated'=>(int)($row['recommendations_generated']??0),'high_priority'=>(int)($row['high_count']??0),'medium_priority'=>(int)($row['medium_count']??0),'accepted'=>(int)($row['accepted_count']??0),'most_recommended'=>$top['title']??'No data','highest_need_department'=>$dept['name']??'No data'];
    }
}