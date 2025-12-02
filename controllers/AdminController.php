<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Disease.php';
require_once __DIR__ . '/../models/Questionnaire.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/I18n.php';

/**
 * Admin Controller
 */
class AdminController {
    private $userModel;
    private $questionModel;
    private $diseaseModel;
    private $questionnaireModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->questionModel = new Question();
        $this->diseaseModel = new Disease();
        $this->questionnaireModel = new Questionnaire();
    }
    
    // ==================== USER MANAGEMENT ====================
    
    /**
     * Get all users
     * GET /admin/users
     */
    public function getAllUsers() {
        require_once __DIR__ . '/../middleware/Auth.php';
        AuthMiddleware::requireAdmin();
        
        $limit = (int) ($_GET['limit'] ?? 10);
        $offset = (int) ($_GET['offset'] ?? 0);
        
        $users = $this->userModel->getAll($limit, $offset);
        $total = $this->userModel->count();
        
        Response::success([
            'count' => count($users),
            'total' => $total,
            'data' => $users
        ]);
    }
    
    /**
     * Get user detail
     * GET /admin/users/:id
     */
    public function getUserDetail($id) {
        require_once __DIR__ . '/../middleware/Auth.php';
        AuthMiddleware::requireAdmin();
        
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            Response::notFound(I18n::t('auth.user_not_found'));
        }
        
        // Get submission count
        $submissionCount = $this->questionnaireModel->getUserSubmissionsCount($id);
        $user['total_submissions'] = $submissionCount;
        
        Response::success($user);
    }
    
    /**
     * Delete user
     * DELETE /admin/users/:id
     */
    public function deleteUser($id) {
        require_once __DIR__ . '/../middleware/Auth.php';
        AuthMiddleware::requireAdmin();
        
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            Response::notFound(I18n::t('auth.user_not_found'));
        }
        
        // Prevent deleting admin users
        if ($user['role'] === 'admin') {
            Response::forbidden(I18n::t('admin.cannot_delete_admin'));
        }
        
        $this->userModel->delete($id);
        
        Response::success(null, I18n::t('admin.user_deleted'));
    }
    
    /**
     * Get all history
     * GET /admin/history
     */
    public function getAllHistory() {
        require_once __DIR__ . '/../middleware/Auth.php';
        AuthMiddleware::requireAdmin();
        
        $limit = (int) ($_GET['limit'] ?? 20);
        $offset = (int) ($_GET['offset'] ?? 0);
        $lang = $_GET['lang'] ?? I18n::getLanguage();
        
        $history = $this->questionnaireModel->getAllHistory($limit, $offset, $lang);
        $total = $this->questionnaireModel->getTotalCount();
        
        Response::success([
            'count' => count($history),
            'total' => $total,
            'data' => $history
        ]);
    }
    
    // ==================== QUESTIONS CRUD ====================
    
    /**
     * Get all questions
     * GET /admin/questions
     */
    public function getAllQuestions() {
        require_once __DIR__ . '/../middleware/Auth.php';
        AuthMiddleware::requireAdmin();
        
        $lang = $_GET['lang'] ?? I18n::getLanguage();
        $questions = $this->questionModel->getAll($lang);
        
        Response::success([
            'count' => count($questions),
            'data' => $questions
        ]);
    }
    
    /**
     * Create question
     * POST /admin/questions
     */
    public function createQuestion() {
        require_once __DIR__ . '/../middleware/Auth.php';
        AuthMiddleware::requireAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validation
        $validator = new Validator($data);
        $validator->required('question_text_id')
                  ->required('question_text_en')
                  ->required('order_number')
                  ->integer('order_number');
        
        if ($validator->fails()) {
            Response::validationError($validator->getFormattedErrors());
        }
        
        $questionId = $this->questionModel->create($data);
        $question = $this->questionModel->findById($questionId);
        
        Response::success($question, I18n::t('admin.question_created'), 201);
    }
    
    /**
     * Update question
     * PUT /admin/questions/:id
     */
    public function updateQuestion($id) {
        require_once __DIR__ . '/../middleware/Auth.php';
        AuthMiddleware::requireAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $question = $this->questionModel->findById($id);
        if (!$question) {
            Response::notFound(I18n::t('general.not_found'));
        }
        
        $this->questionModel->update($id, $data);
        $updatedQuestion = $this->questionModel->findById($id);
        
        Response::success($updatedQuestion, I18n::t('admin.question_updated'));
    }
    
    /**
     * Delete question
     * DELETE /admin/questions/:id
     */
    public function deleteQuestion($id) {
        require_once __DIR__ . '/../middleware/Auth.php';
        AuthMiddleware::requireAdmin();
        
        $question = $this->questionModel->findById($id);
        if (!$question) {
            Response::notFound(I18n::t('general.not_found'));
        }
        
        $this->questionModel->delete($id);
        
        Response::success(null, I18n::t('admin.question_deleted'));
    }
    
    // ==================== DISEASES CRUD ====================
    
    /**
     * Get all diseases
     * GET /admin/diseases
     */
    public function getAllDiseases() {
        require_once __DIR__ . '/../middleware/Auth.php';
        AuthMiddleware::requireAdmin();
        
        $diseases = $this->diseaseModel->getAllAdmin();
        
        Response::success([
            'count' => count($diseases),
            'data' => $diseases
        ]);
    }
    
    /**
     * Create disease
     * POST /admin/diseases
     */
    public function createDisease() {
        require_once __DIR__ . '/../middleware/Auth.php';
        AuthMiddleware::requireAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validation
        $validator = new Validator($data);
        $validator->required('name_id')
                  ->required('name_en');
        
        if ($validator->fails()) {
            Response::validationError($validator->getFormattedErrors());
        }
        
        $diseaseId = $this->diseaseModel->create($data);
        $disease = $this->diseaseModel->findById($diseaseId);
        
        Response::success($disease, I18n::t('admin.disease_created'), 201);
    }
    
    /**
     * Update disease
     * PUT /admin/diseases/:id
     */
    public function updateDisease($id) {
        require_once __DIR__ . '/../middleware/Auth.php';
        AuthMiddleware::requireAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $disease = $this->diseaseModel->findById($id);
        if (!$disease) {
            Response::notFound(I18n::t('general.not_found'));
        }
        
        $this->diseaseModel->update($id, $data);
        $updatedDisease = $this->diseaseModel->findById($id);
        
        Response::success($updatedDisease, I18n::t('admin.disease_updated'));
    }
    
    /**
     * Delete disease
     * DELETE /admin/diseases/:id
     */
    public function deleteDisease($id) {
        require_once __DIR__ . '/../middleware/Auth.php';
        AuthMiddleware::requireAdmin();
        
        $disease = $this->diseaseModel->findById($id);
        if (!$disease) {
            Response::notFound(I18n::t('general.not_found'));
        }
        
        $this->diseaseModel->delete($id);
        
        Response::success(null, I18n::t('admin.disease_deleted'));
    }
}
?>