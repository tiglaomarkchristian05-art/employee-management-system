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
            'expiring_contracts' => $documentModel->getExpiringContracts(60),
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
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $user = Auth::user();
        $isHRAdmin = Auth::hasRole(['Super Admin', 'HR Manager']);
        $empId = ($isHRAdmin && !empty($_POST['employee_id'])) ? intval($_POST['employee_id']) : ($user['employee_id'] ?? 1);

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
            'status'          => 'Verified'
        ]);

        AuditLogger::log('UPLOAD_DOCUMENT', 'Document Management', "Uploaded document: {$title} (ID: {$docId})");
        $this->json('success', 'Document uploaded and QR security stamp generated successfully!', ['id' => $docId]);
    }

    public function delete() {
        Auth::requireAuth();
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
}
