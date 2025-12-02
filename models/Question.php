<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Question Model
 */
class Question {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all active questions with language support
     */
    public function getActive($lang = 'id') {
        $textField = $lang === 'en' ? 'question_text_en' : 'question_text_id';
        
        $sql = "SELECT id, {$textField} as question_text, question_type, min_value, max_value, order_number 
                FROM questions 
                WHERE is_active = TRUE 
                ORDER BY order_number ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all questions (including inactive) for admin
     */
    public function getAll($lang = 'id') {
        $textIdField = 'question_text_id';
        $textEnField = 'question_text_en';
        
        $sql = "SELECT id, {$textIdField} as question_text_id, {$textEnField} as question_text_en, 
                question_type, min_value, max_value, order_number, is_active, created_at, updated_at
                FROM questions 
                ORDER BY order_number ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Find question by ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM questions WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Create new question
     */
    public function create($data) {
        $sql = "INSERT INTO questions 
                (question_text_id, question_text_en, question_type, min_value, max_value, order_number, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['question_text_id'] ?? $data['question_text'],
            $data['question_text_en'] ?? $data['question_text'],
            $data['question_type'] ?? 'scale',
            $data['min_value'] ?? 1,
            $data['max_value'] ?? 5,
            $data['order_number'],
            $data['is_active'] ?? true
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update question
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        if (isset($data['question_text_id'])) {
            $fields[] = "question_text_id = ?";
            $values[] = $data['question_text_id'];
        }
        if (isset($data['question_text_en'])) {
            $fields[] = "question_text_en = ?";
            $values[] = $data['question_text_en'];
        }
        if (isset($data['question_type'])) {
            $fields[] = "question_type = ?";
            $values[] = $data['question_type'];
        }
        if (isset($data['min_value'])) {
            $fields[] = "min_value = ?";
            $values[] = $data['min_value'];
        }
        if (isset($data['max_value'])) {
            $fields[] = "max_value = ?";
            $values[] = $data['max_value'];
        }
        if (isset($data['order_number'])) {
            $fields[] = "order_number = ?";
            $values[] = $data['order_number'];
        }
        if (isset($data['is_active'])) {
            $fields[] = "is_active = ?";
            $values[] = $data['is_active'];
        }
        
        $values[] = $id;
        
        $sql = "UPDATE questions SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete question
     */
    public function delete($id) {
        $sql = "DELETE FROM questions WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
}
?>