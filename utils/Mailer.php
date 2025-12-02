<?php
/**
 * Email Mailer Class
 */
class Mailer {
    private $config;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/config.php';
    }
    
    /**
     * Send email using PHP mail() or SMTP
     */
    public function send($to, $subject, $body, $isHtml = true) {
        $from = $this->config['mail']['from_address'];
        $fromName = $this->config['mail']['from_name'];
        
        $headers = [];
        $headers[] = "From: {$fromName} <{$from}>";
        $headers[] = "Reply-To: {$from}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        if ($isHtml) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        }
        
        $headerString = implode("\r\n", $headers);
        
        try {
            // Use PHP's mail() function
            // For production, consider using PHPMailer or SwiftMailer
            $result = mail($to, $subject, $body, $headerString);
            
            if (!$result) {
                error_log("Failed to send email to: {$to}");
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email, $token) {
        $appUrl = $this->config['app']['url'];
        $resetUrl = $appUrl . "/reset-password?token=" . $token;
        
        $subject = "Reset Password - SheCare";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                          color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; }
                .button { display: inline-block; padding: 12px 30px; background: #667eea; 
                          color: white; text-decoration: none; border-radius: 5px; 
                          margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏥 SheCare</h1>
                </div>
                <div class='content'>
                    <h2>Reset Password</h2>
                    <p>Anda menerima email ini karena kami menerima permintaan reset password untuk akun Anda.</p>
                    <p>Klik tombol di bawah untuk reset password Anda:</p>
                    <a href='{$resetUrl}' class='button'>Reset Password</a>
                    <p>Atau copy link berikut ke browser Anda:</p>
                    <p style='word-break: break-all;'>{$resetUrl}</p>
                    <p>Link ini akan kadaluarsa dalam 1 jam.</p>
                    <p>Jika Anda tidak melakukan permintaan reset password, abaikan email ini.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2024 SheCare. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->send($email, $subject, $body, true);
    }
}
?>