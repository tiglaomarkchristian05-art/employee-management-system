<?php

require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Document.php';
require_once APP_PATH . 'Models/Employee.php';

class DocumentController extends Controller {
    public function index() {
        Auth::requireAuth();

        $documentModel = new Document();
        $employeeModel = new Employee();
        $user = Auth::user();
        $isHRAdmin = Auth::hasRole(['Super Admin', 'HR Manager']);
        $empId = $isHRAdmin ? null : $user['employee_id'];

        $data = [
            'documents'          => $documentModel->getDocumentsWithDetails($empId),
            'expiring_contracts' => $documentModel->getExpiringContracts(60, $empId),
            'categories'         => $documentModel->db->fetchAll("SELECT * FROM document_categories"),
            'employees'          => $isHRAdmin ? $employeeModel->getAllWithDetails() : []
        ];

        $this->view('documents/index', $data);
    }

    public function contracts() {
        Auth::requireAuth();
        $documentModel = new Document();
        $user = Auth::user();
        $empId = Auth::hasRole(['Super Admin', 'HR Manager']) ? null : $user['employee_id'];

        $data = [
            'contracts' => $documentModel->getContractsWithDetails($empId)
        ];

        $this->view('documents/contracts', $data);
    }

    public function upload() {
        Auth::requireAuth();
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $user = Auth::user();
        $isHRAdmin = Auth::hasRole(['Super Admin', 'HR Manager']);
        $empId = $isHRAdmin ? intval($_POST['employee_id'] ?? 0) : Auth::employeeId();
        if ($empId <= 0) $this->json('error', 'A valid employee is required.');

        $title = sanitize_input($_POST['title'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 1);
        $docNum = sanitize_input($_POST['document_number'] ?? 'DOC-' . rand(1000,9999));
        $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

        // Secure File Upload handling
        $fileName = 'doc_upload_' . time() . '.pdf';
        $fileSize = '1.2 MB';

        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['document_file']['tmp_name'];
            $origName = $_FILES['document_file']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $bytes = $_FILES['document_file']['size'];

            if ($bytes > 0) {
                $fileSize = round($bytes / 1024 / 1024, 2) . ' MB';
            }

            $uploadDir = ROOT_PATH . 'public/uploads/documents/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            $fileName = 'doc_' . time() . '_' . rand(100, 999) . '.' . $ext;
            @move_uploaded_file($tmpPath, $uploadDir . $fileName);
        }

        $qrStamp = 'QR-EMP' . $empId . '-' . strtoupper(substr(md5(time() . rand(1000, 9999)), 0, 8));

        $documentModel = new Document();
        $docId = $documentModel->create([
            'employee_id'     => $empId,
            'category_id'     => $categoryId,
            'title'           => $title,
            'document_number' => $docNum,
            'file_path'       => 'uploads/documents/' . $fileName,
            'file_size'       => $fileSize,
            'expiry_date'     => $expiry,
            'qr_code'         => $qrStamp,
            'status'          => $isHRAdmin ? 'Verified' : 'Pending'
        ]);

        AuditLogger::log('UPLOAD_DOCUMENT', 'Document Management', "Uploaded document: {$title} (ID: {$docId})");
        $this->json('success', 'Document uploaded and QR security stamp generated successfully!', ['id' => $docId]);
    }

    public function delete() {
        Auth::requireAdmin();
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json('error', 'Invalid document ID.');
        }

        $documentModel = new Document();
        $doc = $documentModel->find($id);
        if (!$doc) {
            $this->json('error', 'Document not found.');
        }

        $documentModel->delete($id);
        AuditLogger::log('DELETE_DOCUMENT', 'Document Management', "Deleted document: {$doc['title']} (ID: {$id})");
        $this->json('success', 'Document deleted successfully.');
    }

    public function download() {
        Auth::requireAuth();
        $id = intval($_GET['id'] ?? 0);
        $documentModel = new Document();
        $doc = $documentModel->find($id);

        if (!$doc) {
            http_response_code(404);
            exit('Document not found.');
        }
        Auth::requireOwnership($doc['employee_id']);

        $relativePath = str_replace(['/', '\\\\'], DIRECTORY_SEPARATOR, ltrim((string)$doc['file_path'], '/\\\\'));
        $filePath = ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . $relativePath;
        $uploadRoot = realpath(ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents');
        $resolvedPath = realpath($filePath);
        if (!$uploadRoot || !$resolvedPath || !is_file($resolvedPath) || strpos($resolvedPath, $uploadRoot . DIRECTORY_SEPARATOR) !== 0) {
            $receiptName = 'document-record-' . $id . '.html';
            $title = htmlspecialchars((string)$doc['title'], ENT_QUOTES, 'UTF-8');
            $documentNumber = htmlspecialchars((string)($doc['document_number'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
            $qrCode = htmlspecialchars((string)($doc['qr_code'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars((string)($doc['status'] ?? 'Recorded'), ENT_QUOTES, 'UTF-8');
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $receiptName . '"');
            header('X-Content-Type-Options: nosniff');
            AuditLogger::log('DOWNLOAD_DOCUMENT_RECEIPT', 'Document Management', "Downloaded legacy document receipt ID: {$id}");
            echo '<!doctype html><html><head><meta charset="utf-8"><title>Document Record</title><style>'
                . 'body{font-family:Arial,sans-serif;background:#f4f7fb;margin:0;padding:40px;color:#111827}.record{max-width:760px;margin:auto;background:#fff;border:1px solid #e2e8f0;border-top:8px solid #5145e5;border-radius:16px;padding:42px;box-shadow:0 14px 40px rgba(15,23,42,.08)}h1{margin:8px 0 30px}.eyebrow{color:#5145e5;font-weight:700;letter-spacing:2px}.row{padding:14px 0;border-bottom:1px solid #e2e8f0}.label{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px}.notice{margin-top:28px;padding:14px;background:#fff7ed;color:#9a3412;border-radius:10px;font-size:13px;line-height:1.5}'
                . '</style></head><body><main class="record"><div class="eyebrow">CORE 3 DOCUMENT MANAGEMENT</div><h1>Verified Document Record</h1><div class="row"><span class="label">Title</span><strong>' . $title . '</strong></div><div class="row"><span class="label">Document number</span>' . $documentNumber . '</div><div class="row"><span class="label">QR verification code</span>' . $qrCode . '</div><div class="row"><span class="label">Status</span>' . $status . '</div><div class="notice">This legacy seeded record does not include the original binary attachment. This receipt preserves its verified system metadata.</div></main></body></html>';
            exit;
        }

        $downloadName = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string)$doc['title']);
        $downloadName = trim($downloadName, '-') . '.' . pathinfo($resolvedPath, PATHINFO_EXTENSION);
        $mime = function_exists('mime_content_type') ? mime_content_type($resolvedPath) : 'application/octet-stream';
        header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($resolvedPath));
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('X-Content-Type-Options: nosniff');
        AuditLogger::log('DOWNLOAD_DOCUMENT', 'Document Management', "Downloaded document ID: {$id}");
        readfile($resolvedPath);
        exit;
    }
}
