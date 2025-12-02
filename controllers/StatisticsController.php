<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/I18n.php';

/**
 * Statistics Controller
 */
class StatisticsController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get disease statistics for homepage
     * GET /statistics/diseases
     */
    public function getDiseaseStatistics() {
        $lang = $_GET['lang'] ?? I18n::getLanguage();
        $nameField = $lang === 'en' ? 'name_en' : 'name_id';
        
        $sql = "SELECT 
                    ds.id,
                    d.{$nameField} as disease_name,
                    ds.region,
                    ds.percentage,
                    ds.total_cases,
                    ds.year,
                    ds.source,
                    d.severity
                FROM disease_statistics ds
                INNER JOIN diseases d ON ds.disease_id = d.id
                WHERE ds.year = (SELECT MAX(year) FROM disease_statistics)
                ORDER BY ds.percentage DESC";
        
        $stmt = $this->db->query($sql);
        $statistics = $stmt->fetchAll();
        
        Response::success([
            'count' => count($statistics),
            'data' => $statistics
        ]);
    }
    
    /**
     * Get summary statistics
     * GET /statistics/summary
     */
    public function getSummary() {
        // Total users
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
        $userCount = $stmt->fetch()['total'];
        
        // Total submissions
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM questionnaire_submissions WHERE completed = TRUE");
        $submissionCount = $stmt->fetch()['total'];
        
        // Total diseases
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM diseases");
        $diseaseCount = $stmt->fetch()['total'];
        
        Response::success([
            'total_users' => $userCount,
            'total_submissions' => $submissionCount,
            'total_diseases' => $diseaseCount
        ]);
    }
}
?>