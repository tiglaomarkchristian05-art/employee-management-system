<?php
class TrainingCompletionRules {
    public static function failure(array $record): ?string {
        $attendance=(float)($record['attendance_percentage']??0);
        $minimum=$record['minimum_attendance_percentage']??null;
        if($minimum===null ? $attendance<=0 : $attendance<(float)$minimum)
            return $minimum===null?'Attendance must be recorded before certification.':'Attendance does not meet the configured minimum of '.$minimum.'%.';
        if((int)($record['assessment_required']??1)===1){
            if(($record['assessment_result']??null)===null)return 'The official assessment result must be recorded before certification.';
            if((float)$record['assessment_result']<(float)($record['minimum_passing_score']??75))return 'The assessment result does not meet the configured passing score.';
        }
        return null;
    }
}
