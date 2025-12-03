<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

/**
 * Article Controller (External API)
 */
class ArticleController
{
    private $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/config.php';
    }

    /**
     * Get health articles from News API
     * GET /articles
     */

    // Backend: ArticlesController.php - FIXED RSS FEEDS

    public function getArticles()
    {
        $limit = min((int) ($_GET['limit'] ?? 10), 50);
        $language = $_GET['lang'] ?? 'en';

        try {
            // Use WORKING RSS feeds
            $feeds = [];

            if ($language === 'id') {
                $feeds = [
                    'https://www.halodoc.com/rss/artikel',
                    'https://health.kompas.com/rss',
                    'https://www.liputan6.com/health/feed'
                ];
            } else {
                // English - use medical/health RSS feeds that work
                $feeds = [
                    'https://rss.nytimes.com/services/xml/rss/nyt/Health.xml',
                    'https://feeds.bbci.co.uk/news/health/rss.xml',
                    'https://www.sciencedaily.com/rss/health_medicine/women_s_health.xml'
                ];
            }

            $allArticles = [];
            $errors = [];

            foreach ($feeds as $feedUrl) {
                try {
                    $articles = $this->fetchRSSFeed($feedUrl, ceil($limit / count($feeds)) + 5);

                    if (!empty($articles)) {
                        $allArticles = array_merge($allArticles, $articles);
                    }
                } catch (Exception $e) {
                    $errors[] = "Feed {$feedUrl}: " . $e->getMessage();
                    error_log("RSS Feed Error ({$feedUrl}): " . $e->getMessage());
                    continue;
                }
            }

            // If no articles fetched, log errors and return error
            if (empty($allArticles)) {
                error_log('All RSS feeds failed: ' . implode('; ', $errors));
                throw new Exception('No articles available. Please try again later.');
            }

            // Remove duplicates by URL
            $seen = [];
            $allArticles = array_filter($allArticles, function ($article) use (&$seen) {
                if (isset($seen[$article['url']])) {
                    return false;
                }
                $seen[$article['url']] = true;
                return true;
            });

            // Sort by date (newest first)
            usort($allArticles, function ($a, $b) {
                return strtotime($b['published_at']) - strtotime($a['published_at']);
            });

            // Limit results
            $allArticles = array_slice($allArticles, 0, $limit);

            Response::success([
                'count' => count($allArticles),
                'data' => [
                    'articles' => $allArticles,
                    'message' => 'Health articles from trusted sources'
                ]
            ]);
        } catch (Exception $e) {
            error_log('Article fetch error: ' . $e->getMessage());

            Response::error(
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Fetch and parse RSS feed with better error handling
     */
    private function fetchRSSFeed($url, $limit = 10)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: application/rss+xml, application/xml, text/xml, */*'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("CURL error: {$error}");
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP {$httpCode}");
        }

        if (empty($response)) {
            throw new Exception("Empty response");
        }

        // Clean response (remove BOM and invalid characters)
        $response = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $response);
        $response = trim($response);

        // Disable XML errors and warnings
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = @simplexml_load_string($response);

        if (!$xml) {
            $errors = libxml_get_errors();
            $errorMsg = !empty($errors) ? $errors[0]->message : 'Invalid XML';
            libxml_clear_errors();
            throw new Exception("XML parse error: {$errorMsg}");
        }

        $articles = [];
        $count = 0;

        // Try RSS 2.0 format
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                if ($count >= $limit) break;

                $article = $this->parseRSSItem($item);
                if ($article) {
                    $articles[] = $article;
                    $count++;
                }
            }
        }
        // Try Atom format
        elseif (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                if ($count >= $limit) break;

                $article = $this->parseAtomEntry($entry);
                if ($article) {
                    $articles[] = $article;
                    $count++;
                }
            }
        }
        // Try RDF format
        elseif (isset($xml->item)) {
            foreach ($xml->item as $item) {
                if ($count >= $limit) break;

                $article = $this->parseRSSItem($item);
                if ($article) {
                    $articles[] = $article;
                    $count++;
                }
            }
        }

        if (empty($articles)) {
            throw new Exception("No valid articles found in feed");
        }

        return $articles;
    }

    /**
     * Parse RSS item with better error handling
     */
    private function parseRSSItem($item)
    {
        try {
            $title = trim((string) $item->title);
            $link = trim((string) $item->link);

            if (empty($title) || empty($link)) {
                return null;
            }

            // Get description from various possible fields
            $description = '';
            if (isset($item->description)) {
                $description = (string) $item->description;
            } elseif (isset($item->children('content', true)->encoded)) {
                $description = (string) $item->children('content', true)->encoded;
            } elseif (isset($item->summary)) {
                $description = (string) $item->summary;
            }

            // Extract image
            $image = null;

            // Try media:thumbnail
            $media = $item->children('media', true);
            if (isset($media->thumbnail)) {
                $image = (string) $media->thumbnail->attributes()->url;
            } elseif (isset($media->content)) {
                $image = (string) $media->content->attributes()->url;
            }

            // Try enclosure
            if (!$image && isset($item->enclosure)) {
                $type = (string) $item->enclosure->attributes()->type;
                if (strpos($type, 'image') !== false) {
                    $image = (string) $item->enclosure->attributes()->url;
                }
            }

            // Try to extract from description
            if (!$image && !empty($description)) {
                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $description, $matches)) {
                    $image = $matches[1];
                }
            }

            // Clean description
            $description = strip_tags($description);
            $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $description = preg_replace('/\s+/', ' ', $description);
            $description = trim($description);

            // Truncate
            if (strlen($description) > 250) {
                $description = substr($description, 0, 250);
                $lastSpace = strrpos($description, ' ');
                if ($lastSpace !== false) {
                    $description = substr($description, 0, $lastSpace);
                }
                $description .= '...';
            }

            // Parse date
            $pubDate = '';
            if (isset($item->pubDate)) {
                $pubDate = (string) $item->pubDate;
            } elseif (isset($item->children('dc', true)->date)) {
                $pubDate = (string) $item->children('dc', true)->date;
            }

            return [
                'title' => html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'description' => $description,
                'url' => $link,
                'image' => $image,
                'published_at' => $this->parseDate($pubDate),
                'source' => [
                    'name' => $this->extractDomain($link)
                ]
            ];
        } catch (Exception $e) {
            error_log("Parse RSS item error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse Atom entry
     */
    private function parseAtomEntry($entry)
    {
        try {
            $title = trim((string) $entry->title);

            $link = '';
            if (isset($entry->link)) {
                if (is_array($entry->link)) {
                    $link = (string) $entry->link[0]->attributes()->href;
                } else {
                    $link = (string) $entry->link->attributes()->href;
                }
            }

            if (empty($title) || empty($link)) {
                return null;
            }

            $description = '';
            if (isset($entry->summary)) {
                $description = (string) $entry->summary;
            } elseif (isset($entry->content)) {
                $description = (string) $entry->content;
            }

            $description = strip_tags($description);
            $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $description = preg_replace('/\s+/', ' ', $description);
            $description = trim($description);

            if (strlen($description) > 250) {
                $description = substr($description, 0, 250);
                $lastSpace = strrpos($description, ' ');
                if ($lastSpace !== false) {
                    $description = substr($description, 0, $lastSpace);
                }
                $description .= '...';
            }

            $pubDate = '';
            if (isset($entry->published)) {
                $pubDate = (string) $entry->published;
            } elseif (isset($entry->updated)) {
                $pubDate = (string) $entry->updated;
            }

            return [
                'title' => html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'description' => $description,
                'url' => $link,
                'image' => null,
                'published_at' => $this->parseDate($pubDate),
                'source' => [
                    'name' => $this->extractDomain($link)
                ]
            ];
        } catch (Exception $e) {
            error_log("Parse Atom entry error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse date with fallback
     */
    private function parseDate($dateStr)
    {
        if (empty($dateStr)) {
            return date('c');
        }

        $timestamp = @strtotime($dateStr);
        if ($timestamp === false || $timestamp < 0) {
            return date('c');
        }

        return date('c', $timestamp);
    }

    /**
     * Extract domain from URL
     */
    private function extractDomain($url)
    {
        $parsed = @parse_url($url);

        if (!$parsed || !isset($parsed['host'])) {
            return 'Unknown Source';
        }

        $host = $parsed['host'];

        // Remove www. and get main domain
        $host = preg_replace('/^www\./', '', $host);

        // Capitalize first letter
        return ucfirst($host);
    }



    // MapsController.php

    public function getClinics()
    {
        // Validation
        $validator = new Validator($_GET);
        $validator->required('lat')->required('lng');

        if ($validator->fails()) {
            Response::validationError($validator->getFormattedErrors());
        }

        $lat = floatval($_GET['lat']);
        $lng = floatval($_GET['lng']);
        $radius = intval($_GET['radius'] ?? 5000); // meters

        try {
            // Use Overpass API to search for healthcare facilities
            $overpassUrl = 'https://overpass-api.de/api/interpreter';

            // Convert radius from meters to degrees (approximate)
            $radiusInDegrees = $radius / 111320; // 1 degree ≈ 111.32 km

            // Overpass QL query for hospitals, clinics, doctors
            $query = "[out:json][timeout:25];
(
  node[\"amenity\"=\"hospital\"](around:{$radius},{$lat},{$lng});
  node[\"amenity\"=\"clinic\"](around:{$radius},{$lat},{$lng});
  node[\"amenity\"=\"doctors\"](around:{$radius},{$lat},{$lng});
  node[\"healthcare\"=\"clinic\"](around:{$radius},{$lat},{$lng});
  node[\"healthcare\"=\"hospital\"](around:{$radius},{$lat},{$lng});
  way[\"amenity\"=\"hospital\"](around:{$radius},{$lat},{$lng});
  way[\"amenity\"=\"clinic\"](around:{$radius},{$lat},{$lng});
);
out body;
>;
out skel qt;";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $overpassUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'data=' . urlencode($query));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'SheCareApp/1.0');

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new Exception('CURL Error: ' . $curlError);
            }

            if ($httpCode !== 200) {
                throw new Exception('API request failed with HTTP code: ' . $httpCode);
            }

            $data = json_decode($response, true);

            if (!$data || !isset($data['elements'])) {
                throw new Exception('Invalid API response');
            }

            $elements = $data['elements'];

            // Filter only nodes with coordinates
            $clinics = [];
            foreach ($elements as $element) {
                if ($element['type'] !== 'node' || !isset($element['lat']) || !isset($element['lon'])) {
                    continue;
                }

                $tags = $element['tags'] ?? [];

                // Skip if no name
                if (empty($tags['name'])) {
                    continue;
                }

                $placeLat = $element['lat'];
                $placeLng = $element['lon'];

                // Calculate distance
                $distance = $this->calculateDistance($lat, $lng, $placeLat, $placeLng);

                $clinics[] = [
                    'name' => $tags['name'],
                    'address' => $this->formatAddress($tags),
                    'phone' => $tags['phone'] ?? $tags['contact:phone'] ?? null,
                    'lat' => $placeLat,
                    'lng' => $placeLng,
                    'rating' => null, // OSM doesn't have ratings
                    'distance' => round($distance),
                    'type' => $tags['amenity'] ?? $tags['healthcare'] ?? 'clinic',
                    'open_now' => null,
                    'website' => $tags['website'] ?? null,
                    'opening_hours' => $tags['opening_hours'] ?? null,
                ];
            }

            // Sort by distance
            usort($clinics, function ($a, $b) {
                return $a['distance'] - $b['distance'];
            });

            // Limit to top 20 results
            $clinics = array_slice($clinics, 0, 20);

            Response::success([
                'count' => count($clinics),
                'data' => [
                    'clinics' => $clinics,
                    'message' => 'Data from OpenStreetMap',
                    'source' => 'OpenStreetMap contributors'
                ]
            ]);
        } catch (Exception $e) {
            error_log('Maps API error: ' . $e->getMessage());

            Response::error(
                'Failed to fetch clinic data: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Format address from OSM tags
     */
    private function formatAddress($tags)
    {
        $parts = [];

        if (!empty($tags['addr:street'])) {
            $parts[] = $tags['addr:street'];
        }
        if (!empty($tags['addr:housenumber'])) {
            $parts[] = $tags['addr:housenumber'];
        }
        if (!empty($tags['addr:city'])) {
            $parts[] = $tags['addr:city'];
        }
        if (!empty($tags['addr:postcode'])) {
            $parts[] = $tags['addr:postcode'];
        }

        return !empty($parts) ? implode(', ', $parts) : 'Alamat tidak tersedia';
    }

    /**
     * Calculate distance using Haversine formula
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // meters

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
