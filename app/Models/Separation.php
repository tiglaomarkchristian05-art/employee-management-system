<?php

require_once ROOT_PATH . 'core/Model.php';

class Separation extends Model {
    protected $table = 'separations';

    public function getSeparationsWithDetails() {
        $sql = "SELECT s.*, e.first_name, e.last_name, e.employee_code, e.hire_date, d.name as department_name, p.title as position_title
                FROM separations s
                JOIN employees e ON s.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                ORDER BY s.id DESC";
        return $this->db->fetchAll($sql);
    }

    public function getClearanceStatus($separationId) {
        return $this->db->fetchAll("SELECT * FROM clearances WHERE separation_id = ?", [$separationId]);
    }

    public function getAssetReturns($separationId) {
        return $this->db->fetchAll("SELECT * FROM asset_returns WHERE separation_id = ?", [$separationId]);
    }

    public function getFinalPay($separationId) {
        return $this->db->fetchOne("SELECT * FROM final_pays WHERE separation_id = ?", [$separationId]);
    }
}
