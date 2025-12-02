<?php
/**
 * PDF Export Class
 * Uses TCPDF library (you need to install it via composer or download manually)
 * 
 * Installation: composer require tecnickcom/tcpdf
 * 
 * Or use simple HTML to PDF conversion without external libraries
 */
class PDFExport {
    
    /**
     * Generate PDF for questionnaire result
     * Simple version using HTML and browser print
     */
    public static function generateQuestionnaireResult($data) {
        $html = self::getQuestionnaireHTML($data);
        
        // Return HTML that can be converted to PDF by browser
        // Or use TCPDF/FPDF library for server-side generation
        return $html;
    }
    
    /**
     * Get HTML template for questionnaire result
     */
    private static function getQuestionnaireHTML($data) {
        $userName = htmlspecialchars($data['user_name']);
        $submissionDate = date('d F Y, H:i', strtotime($data['submission_date']));
        $diseaseName = htmlspecialchars($data['disease_name']);
        $confidence = number_format($data['confidence_score'] * 100, 0);
        $diagnosis = htmlspecialchars($data['diagnosis_text']);
        $recommendations = htmlspecialchars($data['recommendations']);
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Hasil Kuisioner - SheCare</title>
            <style>
                @page { margin: 20mm; }
                body { 
                    font-family: 'Arial', sans-serif; 
                    line-height: 1.6; 
                    color: #333;
                    margin: 0;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    border-bottom: 3px solid #667eea;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .header h1 {
                    color: #667eea;
                    margin: 0;
                    font-size: 28px;
                }
                .header p {
                    color: #666;
                    margin: 5px 0;
                }
                .info-box {
                    background: #f8f9ff;
                    border-left: 4px solid #667eea;
                    padding: 15px;
                    margin: 20px 0;
                }
                .info-box h3 {
                    margin-top: 0;
                    color: #667eea;
                }
                .result-section {
                    margin: 30px 0;
                }
                .result-section h2 {
                    color: #667eea;
                    border-bottom: 2px solid #667eea;
                    padding-bottom: 10px;
                }
                .confidence-bar {
                    background: #e0e0e0;
                    height: 30px;
                    border-radius: 15px;
                    overflow: hidden;
                    margin: 10px 0;
                }
                .confidence-fill {
                    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: bold;
                    width: {$confidence}%;
                }
                .answers-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                .answers-table th,
                .answers-table td {
                    border: 1px solid #ddd;
                    padding: 12px;
                    text-align: left;
                }
                .answers-table th {
                    background: #667eea;
                    color: white;
                }
                .answers-table tr:nth-child(even) {
                    background: #f9f9f9;
                }
                .footer {
                    margin-top: 50px;
                    text-align: center;
                    color: #666;
                    font-size: 12px;
                    border-top: 1px solid #ddd;
                    padding-top: 20px;
                }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🏥 SheCare</h1>
                <p>Hasil Diagnosis Kesehatan Kewanitaan</p>
            </div>
            
            <div class='info-box'>
                <h3>Informasi Umum</h3>
                <p><strong>Nama:</strong> {$userName}</p>
                <p><strong>Tanggal:</strong> {$submissionDate}</p>
            </div>
            
            <div class='result-section'>
                <h2>Hasil Diagnosis</h2>
                <p><strong>Diagnosis:</strong> {$diseaseName}</p>
                <p><strong>Tingkat Kepercayaan:</strong></p>
                <div class='confidence-bar'>
                    <div class='confidence-fill'>{$confidence}%</div>
                </div>
            </div>
            
            <div class='result-section'>
                <h2>Penjelasan</h2>
                <p>{$diagnosis}</p>
            </div>
            
            <div class='result-section'>
                <h2>Rekomendasi</h2>
                <div class='info-box'>
                    <p>{$recommendations}</p>
                </div>
            </div>
            
            <div class='result-section'>
                <h2>Jawaban Kuisioner</h2>
                <table class='answers-table'>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Pertanyaan</th>
                            <th>Jawaban</th>
                        </tr>
                    </thead>
                    <tbody>";
        
        $no = 1;
        foreach ($data['answers'] as $answer) {
            $question = htmlspecialchars($answer['question_text']);
            $answerValue = htmlspecialchars($answer['answer_value']);
            $html .= "
                        <tr>
                            <td>{$no}</td>
                            <td>{$question}</td>
                            <td>{$answerValue} / 5</td>
                        </tr>";
            $no++;
        }
        
        $html .= "
                    </tbody>
                </table>
            </div>
            
            <div class='footer'>
                <p><strong>Catatan Penting:</strong> Hasil ini merupakan diagnosis awal berdasarkan gejala yang dilaporkan.</p>
                <p>Untuk diagnosis dan penanganan yang tepat, silakan konsultasikan dengan dokter spesialis.</p>
                <p>&copy; 2024 SheCare - Women's Health Care Platform</p>
            </div>
        </body>
        </html>
        ";
        
        return $html;
    }
    
    /**
     * Download PDF
     */
    public static function download($html, $filename = 'questionnaire-result.pdf') {
        // For simple implementation, we return HTML
        // Frontend can use window.print() or libraries like jsPDF
        
        // For server-side PDF generation, use:
        // require_once('tcpdf/tcpdf.php');
        // $pdf = new TCPDF();
        // $pdf->AddPage();
        // $pdf->writeHTML($html, true, false, true, false, '');
        // $pdf->Output($filename, 'D');
        
        return $html;
    }
}
?>