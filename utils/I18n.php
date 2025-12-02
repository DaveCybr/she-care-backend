<?php
/**
 * Internationalization (i18n) Helper Class
 */
class I18n {
    private static $currentLang = 'id';
    private static $translations = [];
    
    /**
     * Initialize with default language
     */
    public static function init($lang = 'id') {
        self::$currentLang = $lang;
        self::loadTranslations();
    }
    
    /**
     * Set current language
     */
    public static function setLanguage($lang) {
        if (in_array($lang, ['id', 'en'])) {
            self::$currentLang = $lang;
            self::loadTranslations();
        }
    }
    
    /**
     * Get current language
     */
    public static function getLanguage() {
        return self::$currentLang;
    }
    
    /**
     * Load translations
     */
    private static function loadTranslations() {
        self::$translations = [
            'id' => [
                // Auth messages
                'auth.register_success' => 'Pendaftaran berhasil',
                'auth.login_success' => 'Login berhasil',
                'auth.logout_success' => 'Logout berhasil',
                'auth.invalid_credentials' => 'Email atau password salah',
                'auth.email_already_exists' => 'Email sudah terdaftar',
                'auth.user_not_found' => 'User tidak ditemukan',
                'auth.unauthorized' => 'Tidak memiliki akses. Silakan login.',
                'auth.token_expired' => 'Token telah kadaluarsa. Silakan login kembali.',
                'auth.token_invalid' => 'Token tidak valid',
                'auth.forbidden' => 'Anda tidak memiliki izin untuk mengakses resource ini',
                
                // Password reset
                'password.reset_link_sent' => 'Link reset password telah dikirim ke email Anda',
                'password.reset_success' => 'Password berhasil direset',
                'password.token_invalid' => 'Token reset password tidak valid atau sudah kadaluarsa',
                
                // Questionnaire
                'questionnaire.submit_success' => 'Kuisioner berhasil dikirim',
                'questionnaire.not_found' => 'Hasil kuisioner tidak ditemukan',
                'questionnaire.invalid_answers' => 'Format jawaban tidak valid',
                
                // Admin
                'admin.question_created' => 'Pertanyaan berhasil ditambahkan',
                'admin.question_updated' => 'Pertanyaan berhasil diperbarui',
                'admin.question_deleted' => 'Pertanyaan berhasil dihapus',
                'admin.disease_created' => 'Penyakit berhasil ditambahkan',
                'admin.disease_updated' => 'Penyakit berhasil diperbarui',
                'admin.disease_deleted' => 'Penyakit berhasil dihapus',
                'admin.user_deleted' => 'User berhasil dihapus',
                'admin.cannot_delete_admin' => 'Tidak dapat menghapus user admin',
                
                // Export
                'export.pdf_generated' => 'File PDF berhasil dibuat',
                'export.excel_generated' => 'File Excel berhasil dibuat',
                
                // Validation
                'validation.required' => 'Field ini wajib diisi',
                'validation.email' => 'Email tidak valid',
                'validation.min_length' => 'Minimal :length karakter',
                'validation.max_length' => 'Maksimal :length karakter',
                
                // General
                'general.success' => 'Berhasil',
                'general.error' => 'Terjadi kesalahan',
                'general.not_found' => 'Data tidak ditemukan',
            ],
            'en' => [
                // Auth messages
                'auth.register_success' => 'Registration successful',
                'auth.login_success' => 'Login successful',
                'auth.logout_success' => 'Logout successful',
                'auth.invalid_credentials' => 'Invalid email or password',
                'auth.email_already_exists' => 'Email already registered',
                'auth.user_not_found' => 'User not found',
                'auth.unauthorized' => 'Not authorized. Please login.',
                'auth.token_expired' => 'Token has expired. Please login again.',
                'auth.token_invalid' => 'Token is invalid',
                'auth.forbidden' => 'You do not have permission to access this resource',
                
                // Password reset
                'password.reset_link_sent' => 'Password reset link has been sent to your email',
                'password.reset_success' => 'Password successfully reset',
                'password.token_invalid' => 'Password reset token is invalid or expired',
                
                // Questionnaire
                'questionnaire.submit_success' => 'Questionnaire submitted successfully',
                'questionnaire.not_found' => 'Questionnaire result not found',
                'questionnaire.invalid_answers' => 'Invalid answer format',
                
                // Admin
                'admin.question_created' => 'Question created successfully',
                'admin.question_updated' => 'Question updated successfully',
                'admin.question_deleted' => 'Question deleted successfully',
                'admin.disease_created' => 'Disease created successfully',
                'admin.disease_updated' => 'Disease updated successfully',
                'admin.disease_deleted' => 'Disease deleted successfully',
                'admin.user_deleted' => 'User deleted successfully',
                'admin.cannot_delete_admin' => 'Cannot delete admin user',
                
                // Export
                'export.pdf_generated' => 'PDF file generated successfully',
                'export.excel_generated' => 'Excel file generated successfully',
                
                // Validation
                'validation.required' => 'This field is required',
                'validation.email' => 'Invalid email',
                'validation.min_length' => 'Minimum :length characters',
                'validation.max_length' => 'Maximum :length characters',
                
                // General
                'general.success' => 'Success',
                'general.error' => 'An error occurred',
                'general.not_found' => 'Data not found',
            ]
        ];
    }
    
    /**
     * Translate a key
     */
    public static function translate($key, $replacements = []) {
        $lang = self::$currentLang;
        
        if (!isset(self::$translations[$lang])) {
            self::loadTranslations();
        }
        
        $translation = self::$translations[$lang][$key] ?? $key;
        
        // Replace placeholders
        foreach ($replacements as $placeholder => $value) {
            $translation = str_replace(":$placeholder", $value, $translation);
        }
        
        return $translation;
    }
    
    /**
     * Short alias for translate
     */
    public static function t($key, $replacements = []) {
        return self::translate($key, $replacements);
    }
}
?>