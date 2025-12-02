<?php
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Questionnaire.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/I18n.php';
require_once __DIR__ . '/../python/DecisionTree.php';

/**
 * Questionnaire Controller
 */
class QuestionnaireController {
    private $questionModel;
    private $questionnaireModel;
    
    public function __construct() {
        $this->questionModel = new Question();
        $this->questionnaireModel = new Questionnaire();
    }
    
    /**
     * Get all active questions
     * GET /questions
     */
    public function getQuestions() {
        $lang = $_GET['lang'] ?? I18n::getLanguage();
        $questions = $this->questionModel->getActive($lang);
        
        Response::success([
            'count' => count($questions),
            'data' => $questions
        ]);
    }
    
    /**
     * Submit questionnaire
     * POST /questionnaire/submit
     */
    public function submit() {
        require_once __DIR__ . '/../middleware/Auth.php';
        $user = AuthMiddleware::authenticate();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $lang = $data['lang'] ?? I18n::getLanguage();
        
        // Validation
        $validator = new Validator($data);
        $validator->required('answers')->isArray('answers');
        
        if ($validator->fails()) {
            Response::validationError($validator->getFormattedErrors());
        }
        
        try {
            // Start transaction
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();
            
            // Create submission
            $submissionId = $this->questionnaireModel->createSubmission($user['id']);
            
            // Save answers
            $this->questionnaireModel->saveAnswers($submissionId, $data['answers']);
            
            // Run decision tree
            $decisionTree = new DecisionTree();
            $diagnosis = $decisionTree->classify($data['answers'], $lang);
            
            // Save diagnosis
            $diagnosisId = $this->questionnaireModel->saveDiagnosis($submissionId, $diagnosis);
            
            // Mark as completed
            $this->questionnaireModel->completeSubmission($submissionId);
            
            $db->commit();
            
            Response::success([
                'submission_id' => $submissionId,
                'diagnosis_id' => $diagnosisId,
                'diagnosis' => $diagnosis
            ], I18n::t('questionnaire.submit_success'), 201);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Questionnaire submission error: ' . $e->getMessage());
            Response::serverError('Failed to submit questionnaire');
        }
    }
    
    /**
     * Get result by submission ID
     * GET /questionnaire/result/:id
     */
    public function getResult($id) {
        require_once __DIR__ . '/../middleware/Auth.php';
        $user = AuthMiddleware::authenticate();
        
        $lang = $_GET['lang'] ?? I18n::getLanguage();
        
        $result = $this->questionnaireModel->getResult($id, $user['id'], $lang);
        
        if (!$result) {
            Response::notFound(I18n::t('questionnaire.not_found'));
        }
        
        // Get answers
        $answers = $this->questionnaireModel->getAnswers($id, $lang);
        $result['answers'] = $answers;
        
        Response::success($result);
    }
    
    /**
     * Get user history
     * GET /questionnaire/history
     */
    public function getHistory() {
        require_once __DIR__ . '/../middleware/Auth.php';
        $user = AuthMiddleware::authenticate();
        
        $limit = (int) ($_GET['limit'] ?? 10);
        $offset = (int) ($_GET['offset'] ?? 0);
        $lang = $_GET['lang'] ?? I18n::getLanguage();
        
        $history = $this->questionnaireModel->getUserHistory($user['id'], $limit, $offset, $lang);
        $total = $this->questionnaireModel->getUserSubmissionsCount($user['id']);
        
        Response::success([
            'count' => count($history),
            'total' => $total,
            'data' => $history
        ]);
    }
    
    /**
     * Export result to PDF
     * GET /questionnaire/export/pdf/:id
     */
    public function exportPDF($id) {
        require_once __DIR__ . '/../middleware/Auth.php';
        require_once __DIR__ . '/../utils/PDFExport.php';
        
        $user = AuthMiddleware::authenticate();
        $lang = $_GET['lang'] ?? I18n::getLanguage();
        
        $result = $this->questionnaireModel->getResult($id, $user['id'], $lang);
        
        if (!$result) {
            Response::notFound(I18n::t('questionnaire.not_found'));
        }
        
        $answers = $this->questionnaireModel->getAnswers($id, $lang);
        $result['answers'] = $answers;
        
        $html = PDFExport::generateQuestionnaireResult($result);
        
        // Return HTML for frontend to convert
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
    
    /**
     * Export result to Excel
     * GET /questionnaire/export/excel/:id
     */
    public function exportExcel($id) {
        require_once __DIR__ . '/../middleware/Auth.php';
        require_once __DIR__ . '/../utils/ExcelExport.php';
        
        $user = AuthMiddleware::authenticate();
        $lang = $_GET['lang'] ?? I18n::getLanguage();
        
        $result = $this->questionnaireModel->getResult($id, $user['id'], $lang);
        
        if (!$result) {
            Response::notFound(I18n::t('questionnaire.not_found'));
        }
        
        $answers = $this->questionnaireModel->getAnswers($id, $lang);
        $result['answers'] = $answers;
        
        $csv = ExcelExport::generateQuestionnaireResult($result);
        ExcelExport::download($csv, "questionnaire-result-{$id}.csv");
    }
    
    /**
     * Export history to Excel
     * GET /questionnaire/export/history
     */
    public function exportHistory() {
        require_once __DIR__ . '/../middleware/Auth.php';
        require_once __DIR__ . '/../utils/ExcelExport.php';
        
        $user = AuthMiddleware::authenticate();
        $lang = $_GET['lang'] ?? I18n::getLanguage();
        
        $history = $this->questionnaireModel->getUserHistory($user['id'], 1000, 0, $lang);
        
        $csv = ExcelExport::generateHistory($history);
        ExcelExport::download($csv, "questionnaire-history.csv");
    }
}
?>