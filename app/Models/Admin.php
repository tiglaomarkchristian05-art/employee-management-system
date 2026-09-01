<?php

require_once ROOT_PATH . 'core/Model.php';

class Admin extends Model {
    public function getAuditLogs(array $filters = [], $page = 1, $perPage = 20) {
        $where=[];$params=[];
        if(!empty($filters['q'])){$term='%'.$filters['q'].'%';$where[]='(u.username LIKE ? OR a.description LIKE ? OR a.action LIKE ? OR a.module LIKE ? OR a.record_type LIKE ? OR CAST(a.record_id AS CHAR) LIKE ?)';array_push($params,$term,$term,$term,$term,$term,$term);}
        if(!empty($filters['date_from'])){$where[]='a.created_at >= ?';$params[]=$filters['date_from'].' 00:00:00';}
        if(!empty($filters['date_to'])){$where[]='a.created_at <= ?';$params[]=$filters['date_to'].' 23:59:59';}
        if(!empty($filters['user_id'])){$where[]='a.user_id = ?';$params[]=(int)$filters['user_id'];}
        if(!empty($filters['role'])){$where[]='COALESCE(a.role_name,r.name) = ?';$params[]=$filters['role'];}
        if(!empty($filters['module'])){$where[]='a.module = ?';$params[]=$filters['module'];}
        if(!empty($filters['action'])){$where[]='a.action = ?';$params[]=$filters['action'];}
        $clause=$where?' WHERE '.implode(' AND ',$where):'';
        $from=' FROM audit_logs a LEFT JOIN users u ON a.user_id=u.id LEFT JOIN roles r ON u.role_id=r.id';
        $total=(int)($this->db->fetchOne('SELECT COUNT(*) total'.$from.$clause,$params)['total']??0);
        $page=max(1,(int)$page);$perPage=max(10,min(100,(int)$perPage));$pages=max(1,(int)ceil($total/$perPage));$page=min($page,$pages);$offset=($page-1)*$perPage;
        $items=$this->db->fetchAll('SELECT a.*,u.username,COALESCE(a.role_name,r.name) resolved_role'.$from.$clause." ORDER BY a.id DESC LIMIT {$perPage} OFFSET {$offset}",$params);
        return ['items'=>$items,'total'=>$total,'page'=>$page,'pages'=>$pages,'per_page'=>$perPage];
    }

    public function getAuditFilterOptions() {
        return [
            'users'=>$this->db->fetchAll("SELECT DISTINCT u.id,u.username FROM audit_logs a JOIN users u ON u.id=a.user_id ORDER BY u.username"),
            'roles'=>$this->db->fetchAll("SELECT DISTINCT COALESCE(a.role_name,r.name) value FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id LEFT JOIN roles r ON r.id=u.role_id WHERE COALESCE(a.role_name,r.name) IS NOT NULL ORDER BY value"),
            'modules'=>$this->db->fetchAll("SELECT DISTINCT module value FROM audit_logs ORDER BY module"),
            'actions'=>$this->db->fetchAll("SELECT DISTINCT action value FROM audit_logs ORDER BY action"),
        ];
    }

    public function getDepartments() {
        $sql = "SELECT d.*, e.first_name, e.last_name, (SELECT COUNT(*) FROM employees emp WHERE emp.department_id = d.id) as total_employees
                FROM departments d
                LEFT JOIN employees e ON d.manager_id = e.id";
        return $this->db->fetchAll($sql);
    }

    public function getPositions() {
        $sql = "SELECT p.*, d.name as department_name FROM positions p JOIN departments d ON p.department_id = d.id";
        return $this->db->fetchAll($sql);
    }

    public function getRoles() {
        return $this->db->fetchAll("SELECT * FROM roles");
    }

    public function getSystemSettings() {
        return $this->db->fetchAll("SELECT * FROM system_settings");
    }
}
