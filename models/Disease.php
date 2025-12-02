<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Disease Model
 */
class Disease {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all diseases with language support
     */
    public function getAll($lang = 'id') {
        $nameField = $lang === 'en' ? 'name_en' : 'name_id';
        $descField = $lang === 'en' ? 'description_en' : 'description_id';
        $recoField = $lang === 'en' ? 'recommendations_en' : 'recommendations_id';
        
        $sql = "SELECT id, {$nameField} as name, {$descField} as description, 
                severity, {$recoField} as recommendations, created_at, updated_at
                FROM diseases 
                ORDER BY name ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all diseases (both languages) for admin
     */
    public function getAllAdmin() {
        $sql = "SELECT * FROM diseases ORDER BY name_id ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Find disease by ID
     */
    public function findById($id, $lang = 'id') {
        $nameField = $lang === 'en' ? 'name_en' : 'name_id';
        $descField = $lang === 'en' ? 'description_en' : 'description_id';
        $recoField = $lang === 'en' ? 'recommendations_en' : 'recommendations_id';
        
        $sql = "SELECT id, {$nameField} as name, {$descField} as description, 
                severity, {$recoField} as recommendations
                FROM diseases 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Create new disease
     */
    public function create($data) {
        $sql = "INSERT INTO diseases 
                (name_id, name_en, description_id, description_en, severity, recommendations_id, recommendations_en) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name_id'] ?? $data['name'],
            $data['name_en'] ?? $data['name'],
            $data['description_id'] ?? $data['description'],
            $data['description_en'] ?? $data['description'],
            $data['severity'] ?? 'moderate',
            $data['recommendations_id'] ?? $data['recommendations'],
            $data['recommendations_en'] ?? $data['recommendations']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update disease
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        if (isset($data['name_id'])) {
            $fields[] = "name_id = ?";
            $values[] = $data['name_id'];
        }
        if (isset($data['name_en'])) {
            $fields[] = "name_en = ?";
            $values[] = $data['name_en'];
        }
        if (isset($data['description_id'])) {
            $fields[] = "description_id = ?";
            $values[] = $data['description_id'];
        }
        if (isset($data['description_en'])) {
            $fields[] = "description_en = ?";
            $values[] = $data['description_en'];
        }
        if (isset($data['severity'])) {
            $fields[] = "severity = ?";
            $values[] = $data['severity'];
        }
        if (isset($data['recommendations_id'])) {
            $fields[] = "recommendations_id = ?";
            $values[] = $data['recommendations_id'];
        }
        if (isset($data['recommendations_en'])) {
            $fields[] = "recommendations_en = ?";
            $values[] = $data['recommendations_en'];
        }
        
        $values[] = $id;
        
        $sql = "UPDATE diseases SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete disease
     */
    public function delete($id) {
        $sql = "DELETE FROM diseases WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
}
?>