<?php

require_once __DIR__ . '/Database.php';

class Model {
    public $db;
    protected $table;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function all($orderBy = 'id DESC') {
        return $this->db->fetchAll("SELECT * FROM {$this->table} ORDER BY {$orderBy}");
    }

    public function find($id) {
        return $this->db->fetchOne("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function create($data) {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data) {
        return $this->db->update($this->table, $data, "id = ?", [$id]);
    }

    public function delete($id) {
        return $this->db->delete($this->table, "id = ?", [$id]);
    }
}
