<?php

require_once ROOT_PATH . 'core/Model.php';

class Notification extends Model {
    protected $table = 'notifications';

    public function createForUser($userId, $title, $message, $type = 'info', $link = null, $module = null, $relatedId = null) {
        if ((int)$userId <= 0) return null;
        return $this->create([
            'user_id' => (int)$userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
            'module' => $module ?: $this->inferModule($link),
            'related_id' => $relatedId === null ? $this->inferRelatedId($link) : (int)$relatedId,
            'is_read' => 0,
        ]);
    }

    public function createForEmployee($employeeId, $title, $message, $type = 'info', $link = null, $module = null, $relatedId = null) {
        $users = $this->db->fetchAll("SELECT id FROM users WHERE employee_id=? AND is_active=1", [(int)$employeeId]);
        foreach ($users as $user) $this->createForUser($user['id'], $title, $message, $type, $link, $module, $relatedId);
        return count($users);
    }

    public function createForAdmins($title, $message, $type = 'info', $link = null, $module = null, $relatedId = null) {
        $users = $this->db->fetchAll("SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE u.is_active=1 AND r.name IN ('Super Admin','HR Manager')");
        foreach ($users as $user) $this->createForUser($user['id'], $title, $message, $type, $link, $module, $relatedId);
        return count($users);
    }

    public function getForUser($userId, $limit = 50) {
        $limit = max(1, min(100, (int)$limit));
        return $this->db->fetchAll("SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT {$limit}", [(int)$userId]);
    }

    public function paginateForUser($userId, $page = 1, $perPage = 15) {
        $page=max(1,(int)$page);$perPage=max(5,min(50,(int)$perPage));$offset=($page-1)*$perPage;
        $total=(int)($this->db->fetchOne("SELECT COUNT(*) total FROM notifications WHERE user_id=?",[(int)$userId])['total']??0);
        $items=$this->db->fetchAll("SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",[(int)$userId]);
        return ['items'=>$items,'page'=>$page,'per_page'=>$perPage,'total'=>$total,'pages'=>max(1,(int)ceil($total/$perPage))];
    }

    public function unreadCount($userId) {
        $row = $this->db->fetchOne("SELECT COUNT(*) total FROM notifications WHERE user_id=? AND is_read=0", [(int)$userId]);
        return (int)($row['total'] ?? 0);
    }

    public function markAllRead($userId) {
        return $this->db->query("UPDATE notifications SET is_read=1 WHERE user_id=?", [(int)$userId])->rowCount();
    }

    public function markOneRead($notificationId, $userId) {
        return $this->db->query("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?",[(int)$notificationId,(int)$userId])->rowCount();
    }

    public function getOwned($notificationId, $userId) {
        return $this->db->fetchOne("SELECT * FROM notifications WHERE id=? AND user_id=?",[(int)$notificationId,(int)$userId]);
    }

    private function inferModule($link) {
        if(!$link)return 'system';
        if(stripos($link,'page=benefits_loans')!==false)return 'loans';
        foreach(['training','documents','compliance','benefits','separation'] as $module)if(stripos($link,'page='.$module)!==false)return $module;
        return 'system';
    }

    private function inferRelatedId($link) {
        if($link&&preg_match('/[?&](?:id|loan_id)=([0-9]+)/',$link,$match))return (int)$match[1];
        return null;
    }
}
