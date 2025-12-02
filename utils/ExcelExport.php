<?php
/**
 * Excel Export Class
 * Uses simple CSV format (compatible with Excel)
 * 
 * For advanced Excel features, use PhpSpreadsheet:
 * composer require phpoffice/phpspreadsheet
 */
class ExcelExport {
    
    /**
     * Generate Excel (CSV) for questionnaire result
     */
    public static function generateQuestionnaireResult($data) {
        $csv = [];
        
        // Header
        $csv[] = ['SheCare - Hasil Kuisioner Kesehatan Kewanitaan'];
        $csv[] = [];
        
        // User Info
        $csv[] = ['Nama', $data['user_name']];
        $csv[] = ['Tanggal', date('d F Y, H:i', strtotime($data['submission_date']))];
        $csv[] = [];
        
        // Diagnosis
        $csv[] = ['HASIL DIAGNOSIS'];
        $csv[] = ['Diagnosis', $data['disease_name']];
        $csv[] = ['Tingkat Kepercayaan', number_format($data['confidence_score'] * 100, 0) . '%'];
        $csv[] = [];
        
        // Explanation
        $csv[] = ['PENJELASAN'];
        $csv[] = [$data['diagnosis_text']];
        $csv[] = [];
        
        // Recommendations
        $csv[] = ['REKOMENDASI'];
        $csv[] = [$data['recommendations']];
        $csv[] = [];
        
        // Answers
        $csv[] = ['JAWABAN KUISIONER'];
        $csv[] = ['No', 'Pertanyaan', 'Jawaban'];
        
        $no = 1;
        foreach ($data['answers'] as $answer) {
            $csv[] = [
                $no,
                $answer['question_text'],
                $answer['answer_value'] . ' / 5'
            ];
            $no++;
        }
        
        $csv[] = [];
        $csv[] = ['Catatan: Hasil ini merupakan diagnosis awal. Konsultasikan dengan dokter untuk penanganan yang tepat.'];
        
        return self::arrayToCSV($csv);
    }
    
    /**
     * Generate Excel (CSV) for questionnaire history
     */
    public static function generateHistory($historyData) {
        $csv = [];
        
        // Header
        $csv[] = ['SheCare - Riwayat Kuisioner'];
        $csv[] = [];
        $csv[] = ['Tanggal', 'Diagnosis', 'Tingkat Kepercayaan', 'Severity'];
        
        foreach ($historyData as $item) {
            $csv[] = [
                date('d F Y, H:i', strtotime($item['submission_date'])),
                $item['disease_name'] ?? 'N/A',
                isset($item['confidence_score']) ? number_format($item['confidence_score'] * 100, 0) . '%' : 'N/A',
                $item['severity'] ?? 'N/A'
            ];
        }
        
        return self::arrayToCSV($csv);
    }
    
    /**
     * Convert array to CSV string
     */
    private static function arrayToCSV($data) {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Download CSV as Excel
     */
    public static function download($csv, $filename = 'questionnaire-result.csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for UTF-8 Excel compatibility
        echo "\xEF\xBB\xBF";
        echo $csv;
        exit;
    }
}
?>