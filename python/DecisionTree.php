<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Decision Tree Classifier (PHP Implementation)
 * Simple rule-based decision tree based on ID3 algorithm
 */
class DecisionTree {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Classify based on answers
     * 
     * Decision Tree Structure:
     * Root: Nyeri panggul kronis (Q1)?
     * ├─ Tidak (<=2)
     * │  └─ Keputihan abnormal (Q2)?
     * │     ├─ Ya (>=3)
     * │     │  └─ Gatal/Iritasi (Q3)?
     * │     │     ├─ Ya (>=3) → Infeksi Jamur
     * │     │     └─ Tidak (<=2) → Infeksi Bakteri
     * │     └─ Tidak (<=2) → Normal
     * └─ Ya (>=3)
     *    └─ Keputihan abnormal (Q2)?
     *       ├─ Tidak (<=2) → Endometriosis
     *       └─ Ya (>=3) → PID
     */
    public function classify($answers, $lang = 'id') {
        // Convert answers array to map
        $answerMap = [];
        foreach ($answers as $answer) {
            $answerMap[$answer['question_id']] = (int)$answer['answer_value'];
        }
        
        // Assuming question IDs:
        // 1: Nyeri panggul kronis (1-5)
        // 2: Keputihan abnormal (1-5)
        // 3: Gatal/Iritasi (1-5)
        
        $nyeriPanggul = $answerMap[1] ?? 0;
        $keputihanAbnormal = $answerMap[2] ?? 0;
        $gatalIritasi = $answerMap[3] ?? 0;
        
        $diseaseId = null;
        $confidence = 0;
        
        // Apply decision tree logic
        if ($nyeriPanggul <= 2) {
            // No chronic pelvic pain
            if ($keputihanAbnormal >= 3) {
                // Abnormal discharge
                if ($gatalIritasi >= 3) {
                    // With itching -> Fungal Infection
                    $diseaseId = 2;
                    $confidence = 0.85;
                } else {
                    // Without significant itching -> Bacterial Infection
                    $diseaseId = 3;
                    $confidence = 0.75;
                }
            } else {
                // No abnormal discharge -> Normal
                $diseaseId = 5;
                $confidence = 0.90;
            }
        } else {
            // Chronic pelvic pain present
            if ($keputihanAbnormal <= 2) {
                // No significant discharge -> Endometriosis
                $diseaseId = 1;
                $confidence = 0.80;
            } else {
                // With abnormal discharge -> PID
                $diseaseId = 4;
                $confidence = 0.70;
            }
        }
        
        // Get disease details from database
        $disease = $this->getDiseaseById($diseaseId, $lang);
        
        return [
            'disease_id' => $diseaseId,
            'disease_name' => $disease['name'] ?? 'Unknown',
            'confidence' => $confidence,
            'diagnosis_text' => $this->generateDiagnosisText($diseaseId, $confidence, $lang),
            'recommendations' => $disease['recommendations'] ?? ''
        ];
    }
    
    /**
     * Get disease by ID
     */
    private function getDiseaseById($id, $lang = 'id') {
        $nameField = $lang === 'en' ? 'name_en' : 'name_id';
        $descField = $lang === 'en' ? 'description_en' : 'description_id';
        $recoField = $lang === 'en' ? 'recommendations_en' : 'recommendations_id';
        
        $sql = "SELECT 
                    {$nameField} as name,
                    {$descField} as description,
                    {$recoField} as recommendations,
                    severity
                FROM diseases 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Generate diagnosis text based on disease and confidence
     */
    private function generateDiagnosisText($diseaseId, $confidence, $lang = 'id') {
        $texts = [
            'id' => [
                1 => 'Berdasarkan gejala nyeri panggul kronis yang Anda alami, kemungkinan besar Anda mengalami endometriosis. Kondisi ini terjadi ketika jaringan mirip lapisan rahim tumbuh di luar rahim.',
                2 => 'Berdasarkan gejala keputihan abnormal dengan gatal dan iritasi, kemungkinan besar Anda mengalami infeksi jamur vagina (kandidiasis).',
                3 => 'Gejala keputihan abnormal tanpa gatal yang signifikan mengindikasikan kemungkinan infeksi bakteri pada vagina (bacterial vaginosis).',
                4 => 'Kombinasi nyeri panggul kronis dan keputihan abnormal mengindikasikan kemungkinan penyakit radang panggul (PID). Kondisi ini memerlukan penanganan medis segera.',
                5 => 'Berdasarkan gejala yang dilaporkan, tidak ditemukan indikasi penyakit yang signifikan. Kondisi kesehatan reproduksi Anda tampak normal.'
            ],
            'en' => [
                1 => 'Based on the chronic pelvic pain symptoms you are experiencing, you likely have endometriosis. This condition occurs when tissue similar to the uterine lining grows outside the uterus.',
                2 => 'Based on abnormal discharge symptoms with itching and irritation, you likely have a vaginal yeast infection (candidiasis).',
                3 => 'Abnormal discharge symptoms without significant itching indicate a possible bacterial infection in the vagina (bacterial vaginosis).',
                4 => 'The combination of chronic pelvic pain and abnormal discharge indicates possible pelvic inflammatory disease (PID). This condition requires immediate medical attention.',
                5 => 'Based on the reported symptoms, no significant disease indication was found. Your reproductive health appears to be normal.'
            ]
        ];
        
        return $texts[$lang][$diseaseId] ?? '';
    }
}
?>