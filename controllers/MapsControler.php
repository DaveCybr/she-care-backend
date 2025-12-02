<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

/**
 * Maps Controller (Google Maps API)
 */
class MapsController {
    private $config;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/config.php';
    }
    
    /**
     * Get nearby clinics
     * GET /maps/clinics
     */
    public function getClinics() {
        // Validation
        $validator = new Validator($_GET);
        $validator->required('lat')->required('lng');
        
        if ($validator->fails()) {
            Response::validationError($validator->getFormattedErrors());
        }
        
        $lat = $_GET['lat'];
        $lng = $_GET['lng'];
        $radius = $_GET['radius'] ?? 5000;
        
        $apiKey = $this->config['api']['google_maps_api_key'];
        
        // If no API key, return dummy data
        if (empty($apiKey)) {
            Response::success([
                'count' => 3,
                'data' => $this->getDummyClinics($lat, $lng)
            ]);
        }
        
        try {
            $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?" . http_build_query([
                'location' => "{$lat},{$lng}",
                'radius' => $radius,
                'type' => 'hospital',
                'keyword' => 'women clinic gynecology',
                'key' => $apiKey
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception('API request failed');
            }
            
            $data = json_decode($response, true);
            
            if ($data['status'] !== 'OK') {
                throw new Exception('Google Maps API error: ' . $data['status']);
            }
            
            $clinics = array_map(function($place) {
                return [
                    'name' => $place['name'],
                    'address' => $place['vicinity'],
                    'rating' => $place['rating'] ?? 0,
                    'location' => [
                        'lat' => $place['geometry']['location']['lat'],
                        'lng' => $place['geometry']['location']['lng']
                    ],
                    'open_now' => $place['opening_hours']['open_now'] ?? null,
                    'place_id' => $place['place_id']
                ];
            }, $data['results']);
            
            Response::success([
                'count' => count($clinics),
                'data' => $clinics
            ]);
            
        } catch (Exception $e) {
            error_log('Maps API error: ' . $e->getMessage());
            
            // Return dummy data on error
            Response::success([
                'count' => 3,
                'data' => $this->getDummyClinics($lat, $lng)
            ]);
        }
    }
    
    /**
     * Get dummy clinics (fallback)
     */
    private function getDummyClinics($lat, $lng) {
        return [
            [
                'name' => 'Klinik Kesehatan Wanita Pratama',
                'address' => 'Jl. Sudirman No. 123, Jakarta',
                'phone' => '+62-21-12345678',
                'rating' => 4.5,
                'distance' => '1.2 km',
                'location' => [
                    'lat' => (float)$lat + 0.01,
                    'lng' => (float)$lng + 0.01
                ],
                'open_now' => true
            ],
            [
                'name' => 'RS Ibu dan Anak Harapan',
                'address' => 'Jl. Gatot Subroto No. 456, Jakarta',
                'phone' => '+62-21-87654321',
                'rating' => 4.8,
                'distance' => '2.5 km',
                'location' => [
                    'lat' => (float)$lat + 0.02,
                    'lng' => (float)$lng - 0.01
                ],
                'open_now' => true
            ],
            [
                'name' => 'Klinik Bersalin Sejahtera',
                'address' => 'Jl. Thamrin No. 789, Jakarta',
                'phone' => '+62-21-11223344',
                'rating' => 4.3,
                'distance' => '3.8 km',
                'location' => [
                    'lat' => (float)$lat - 0.01,
                    'lng' => (float)$lng + 0.02
                ],
                'open_now' => false
            ]
        ];
    }
}
?>