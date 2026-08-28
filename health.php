<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['status' => 'ok', 'service' => 'core-3']);
