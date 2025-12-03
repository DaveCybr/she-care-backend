<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/I18n.php';
require_once __DIR__ . '/../utils/Mailer.php';

/**
 * Authentication Controller
 */
class AuthController
{
    private $userModel;
    private $config;

    public function __construct()
    {
        $this->userModel = new User();
        $this->config = require __DIR__ . '/../config/config.php';
    }

    /**
     * Register new user
     * POST /auth/register
     */
    public function register()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validation
        $validator = new Validator($data);
        $validator->required('name')
            ->required('email')
            ->email('email')
            ->required('password')
            ->minLength('password', 6);

        if ($validator->fails()) {
            Response::validationError($validator->getFormattedErrors());
        }

        // Check if email already exists
        $existingUser = $this->userModel->findByEmail($data['email']);
        if ($existingUser) {
            Response::error(I18n::t('auth.email_already_exists'), 400);
        }

        // Create user
        $userId = $this->userModel->create($data);
        $user = $this->userModel->findById($userId);

        // Generate token
        $token = JWT::encode(
            ['id' => $user['id']],
            $this->config['jwt']['secret'],
            $this->config['jwt']['expire']
        );

        Response::success([
            'user' => $user,
            'token' => $token
        ], I18n::t('auth.register_success'), 201);
    }

    /**
     * Login user
     * POST /auth/login
     */
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validation
        $validator = new Validator($data);
        $validator->required('email')
            ->email('email')
            ->required('password');

        if ($validator->fails()) {
            Response::validationError($validator->getFormattedErrors());
        }

        // Find user
        $user = $this->userModel->findByEmail($data['email']);

        if (!$user || !$this->userModel->verifyPassword($data['password'], $user['password'])) {
            Response::error(I18n::t('auth.invalid_credentials'), 401);
        }

        // Remove password from response
        unset($user['password']);
        unset($user['reset_token']);
        unset($user['reset_token_expire']);

        // Generate token
        $token = JWT::encode(
            ['id' => $user['id']],
            $this->config['jwt']['secret'],
            $this->config['jwt']['expire']
        );

        Response::success([
            'user' => $user,
            'token' => $token
        ], I18n::t('auth.login_success'));
    }

    /**
     * Get current user
     * GET /auth/me
     */
    public function getMe()
    {
        require_once __DIR__ . '/../middleware/Auth.php';
        $user = AuthMiddleware::authenticate();

        Response::success($user);
    }

    /**
     * Logout (client-side token removal)
     * POST /auth/logout
     */
    public function logout()
    {
        Response::success(null, I18n::t('auth.logout_success'));
    }

    /**
     * Forgot password - Send reset link
     * POST /auth/forgot-password
     */
    public function forgotPassword()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validation
        $validator = new Validator($data);
        $validator->required('email')->email('email');

        if ($validator->fails()) {
            Response::validationError($validator->getFormattedErrors());
        }

        // Find user
        $user = $this->userModel->findByEmail($data['email']);

        if (!$user) {
            // Don't reveal if email exists or not
            Response::success(null, I18n::t('password.reset_link_sent'));
        }

        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));

        // Save token to database
        $this->userModel->setResetToken($data['email'], $resetToken);

        // Send email
        $mailer = new Mailer();
        $mailer->sendPasswordResetEmail($data['email'], $resetToken);

        Response::success(null, I18n::t('password.reset_link_sent'));
    }

    /**
     * Reset password
     * POST /auth/reset-password
     */
    public function resetPassword()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validation
        $validator = new Validator($data);
        $validator->required('token')
            ->required('password')
            ->minLength('password', 6);

        if ($validator->fails()) {
            Response::validationError($validator->getFormattedErrors());
        }

        // Verify token
        $user = $this->userModel->verifyResetToken($data['token']);

        if (!$user) {
            Response::error(I18n::t('password.token_invalid'), 400);
        }

        // Reset password
        $this->userModel->resetPassword($data['token'], $data['password']);

        Response::success(null, I18n::t('password.reset_success'));
    }
}
