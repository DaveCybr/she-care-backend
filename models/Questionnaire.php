<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Questionnaire Model
 */
class Questionnaire {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create submission
     */
    public function createSubmission($userId) {
        $sql = "INSERT INTO questionnaire_submissions (user_id, completed) VALUES (?, FALSE)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Save answers
     */
    public function saveAnswers($submissionId, $answers) {
        $sql = "INSERT INTO questionnaire_answers (submission_id, question_id, answer_value) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($answers as $answer) {
            $stmt->execute([
                $submissionId,
                $answer['question_id'],
                $answer['answer_value']
            ]);
        }
        
        return true;
    }
    
    /**
     * Save diagnosis result
     */
    public function saveDiagnosis($submissionId, $diagnosis) {
        $sql = "INSERT INTO diagnosis_results 
                (submission_id, disease_id, confidence_score, diagnosis_text, recommendations) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $submissionId,
            $diagnosis['disease_id'] ?? null,
            $diagnosis['confidence'] ?? 0,
            $diagnosis['diagnosis_text'] ?? '',
            $diagnosis['recommendations'] ?? ''
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Mark submission as completed
     */
    public function completeSubmission($submissionId) {
        $sql = "UPDATE questionnaire_submissions SET completed = TRUE WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$submissionId]);
    }
    
    /**
     * Get result by submission ID
     */
    public function getResult($submissionId, $userId, $lang = 'id') {
        $nameField = $lang === 'en' ? 'name_en' : 'name_id';
        $descField = $lang === 'en' ? 'description_en' : 'description_id';
        
        $sql = "SELECT 
                    dr.id,
                    dr.submission_id,
                    dr.confidence_score,
                    dr.diagnosis_text,
                    dr.recommendations,
                    dr.created_at,
                    d.{$nameField} as disease_name,
                    d.{$descField} as disease_description,
                    d.severity,
                    qs.submission_date,
                    u.name as user_name
                FROM diagnosis_results dr
                LEFT JOIN diseases d ON dr.disease_id = d.id
                INNER JOIN questionnaire_submissions qs ON dr.submission_id = qs.id
                INNER JOIN users u ON qs.user_id = u.id
                WHERE dr.submission_id = ? AND qs.user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$submissionId, $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Get answers by submission ID
     */
    public function getAnswers($submissionId, $lang = 'id') {
        $textField = $lang === 'en' ? 'question_text_en' : 'question_text_id';
        
        $sql = "SELECT 
                    qa.question_id,
                    qa.answer_value,
                    q.{$textField} as question_text
                FROM questionnaire_answers qa
                INNER JOIN questions q ON qa.question_id = q.id
                WHERE qa.submission_id = ?
                ORDER BY q.order_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$submissionId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user history
     */
    public function getUserHistory($userId, $limit = 10, $offset = 0, $lang = 'id') {
        $nameField = $lang === 'en' ? 'name_en' : 'name_id';
        
        $sql = "SELECT 
                    qs.id as submission_id,
                    qs.submission_date,
                    qs.completed,
                    dr.id as diagnosis_id,
                    dr.confidence_score,
                    d.{$nameField} as disease_name,
                    d.severity
                FROM questionnaire_submissions qs
                LEFT JOIN diagnosis_results dr ON qs.id = dr.submission_id
                LEFT JOIN diseases d ON dr.disease_id = d.id
                WHERE qs.user_id = ?
                ORDER BY qs.submission_date DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get total user submissions count
     */
    public function getUserSubmissionsCount($userId) {
        $sql = "SELECT COUNT(*) as total FROM questionnaire_submissions WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Get all history (admin)
     */
    public function getAllHistory($limit = 20, $offset = 0, $lang = 'id') {
        $nameField = $lang === 'en' ? 'name_en' : 'name_id';
        
        $sql = "SELECT 
                    qs.id as submission_id,
                    qs.submission_date,
                    qs.completed,
                    u.id as user_id,
                    u.name as user_name,
                    u.email as user_email,
                    dr.confidence_score,
                    d.{$nameField} as disease_name,
                    d.severity
                FROM questionnaire_submissions qs
                INNER JOIN users u ON qs.user_id = u.id
                LEFT JOIN diagnosis_results dr ON qs.id = dr.submission_id
                LEFT JOIN diseases d ON dr.disease_id = d.id
                ORDER BY qs.submission_date DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get total submissions count
     */
    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as total FROM questionnaire_submissions";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result['total'];
    }
}
?>