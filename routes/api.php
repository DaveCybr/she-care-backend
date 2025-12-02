<?php
/**
 * API Router
 * Simple router for handling API requests
 */

class Router {
    private $routes = [];
    
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }
    
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }
    
    public function put($path, $handler) {
        $this->addRoute('PUT', $path, $handler);
    }
    
    public function delete($path, $handler) {
        $this->addRoute('DELETE', $path, $handler);
    }
    
    private function addRoute($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function dispatch($requestMethod, $requestUri) {
        // Remove query string
        $uri = parse_url($requestUri, PHP_URL_PATH);
        
        // Remove /api prefix if exists
        $uri = preg_replace('#^/api#', '', $uri);
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }
            
            // Convert route path to regex pattern
            $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match
                
                // Call handler
                $handler = $route['handler'];
                list($controller, $method) = explode('@', $handler);
                
                require_once __DIR__ . '/../controllers/' . $controller . '.php';
                
                $controllerInstance = new $controller();
                call_user_func_array([$controllerInstance, $method], $matches);
                return;
            }
        }
        
        // 404 Not Found
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found',
            'path' => $uri
        ]);
    }
}

// Initialize router
$router = new Router();

// ==================== AUTH ROUTES ====================
$router->post('/auth/register', 'AuthController@register');
$router->post('/auth/login', 'AuthController@login');
$router->get('/auth/me', 'AuthController@getMe');
$router->post('/auth/logout', 'AuthController@logout');
$router->post('/auth/forgot-password', 'AuthController@forgotPassword');
$router->post('/auth/reset-password', 'AuthController@resetPassword');

// ==================== QUESTIONNAIRE ROUTES ====================
$router->get('/questions', 'QuestionnaireController@getQuestions');
$router->post('/questionnaire/submit', 'QuestionnaireController@submit');
$router->get('/questionnaire/result/{id}', 'QuestionnaireController@getResult');
$router->get('/questionnaire/history', 'QuestionnaireController@getHistory');
$router->get('/questionnaire/export/pdf/{id}', 'QuestionnaireController@exportPDF');
$router->get('/questionnaire/export/excel/{id}', 'QuestionnaireController@exportExcel');
$router->get('/questionnaire/export/history', 'QuestionnaireController@exportHistory');

// ==================== ADMIN - USER MANAGEMENT ====================
$router->get('/admin/users', 'AdminController@getAllUsers');
$router->get('/admin/users/{id}', 'AdminController@getUserDetail');
$router->delete('/admin/users/{id}', 'AdminController@deleteUser');
$router->get('/admin/history', 'AdminController@getAllHistory');

// ==================== ADMIN - QUESTIONS CRUD ====================
$router->get('/admin/questions', 'AdminController@getAllQuestions');
$router->post('/admin/questions', 'AdminController@createQuestion');
$router->put('/admin/questions/{id}', 'AdminController@updateQuestion');
$router->delete('/admin/questions/{id}', 'AdminController@deleteQuestion');

// ==================== ADMIN - DISEASES CRUD ====================
$router->get('/admin/diseases', 'AdminController@getAllDiseases');
$router->post('/admin/diseases', 'AdminController@createDisease');
$router->put('/admin/diseases/{id}', 'AdminController@updateDisease');
$router->delete('/admin/diseases/{id}', 'AdminController@deleteDisease');

// ==================== ARTICLES & MAPS ====================
$router->get('/articles', 'ArticleController@getArticles');
$router->get('/maps/clinics', 'MapsController@getClinics');

// ==================== STATISTICS ====================
$router->get('/statistics/diseases', 'StatisticsController@getDiseaseStatistics');
$router->get('/statistics/summary', 'StatisticsController@getSummary');

// Health check
$router->get('/health', function() {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'SheCare API is running',
        'timestamp' => date('c'),
        'version' => '1.0.0'
    ]);
});

return $router;
?>