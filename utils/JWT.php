<?php
class JWT
{
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function encode($payload, $secret, $expire = 604800)
    {
        error_log("[JWT::encode] Starting token generation");
        error_log("[JWT::encode] Payload: " . json_encode($payload));
        error_log("[JWT::encode] Secret length: " . strlen($secret));
        error_log("[JWT::encode] Expire seconds: " . $expire);

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $payload['iat'] = time();
        $payload['exp'] = time() + $expire;

        error_log("[JWT::encode] Current time: " . time());
        error_log("[JWT::encode] Token will expire at: " . $payload['exp']);
        error_log("[JWT::encode] Token expires on: " . date('Y-m-d H:i:s', $payload['exp']));

        $payload = json_encode($payload);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        $token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        error_log("[JWT::encode] Token generated successfully");
        error_log("[JWT::encode] Token: " . substr($token, 0, 50) . "...");

        return $token;
    }

    public static function decode($token, $secret)
    {
        error_log("[JWT::decode] Starting token verification");
        error_log("[JWT::decode] Token: " . substr($token, 0, 50) . "...");
        error_log("[JWT::decode] Secret length: " . strlen($secret));

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            error_log("[JWT::decode] ERROR: Invalid token format - parts count: " . count($parts));
            throw new Exception('Invalid token format');
        }

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

        // Verify signature
        $signature = self::base64UrlDecode($base64UrlSignature);
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

        error_log("[JWT::decode] Signature received: " . bin2hex(substr($signature, 0, 10)) . "...");
        error_log("[JWT::decode] Signature expected: " . bin2hex(substr($expectedSignature, 0, 10)) . "...");

        if (!hash_equals($signature, $expectedSignature)) {
            error_log("[JWT::decode] ERROR: Signature mismatch!");
            throw new Exception('Invalid token signature');
        }

        error_log("[JWT::decode] Signature verified successfully");

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);

        error_log("[JWT::decode] Payload decoded: " . json_encode($payload));
        error_log("[JWT::decode] Current time: " . time());
        error_log("[JWT::decode] Token expires at: " . $payload['exp']);
        error_log("[JWT::decode] Is expired? " . ($payload['exp'] < time() ? 'YES' : 'NO'));

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            error_log("[JWT::decode] ERROR: Token has expired");
            throw new Exception('Token has expired');
        }

        error_log("[JWT::decode] Token verification successful");

        return $payload;
    }
}
