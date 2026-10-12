<?php

namespace App\Services\WebPush;

use RuntimeException;

/**
 * VAPID (RFC 8292) with plain PHP OpenSSL — no Composer package needed.
 * Keys use the standard web-push format: base64url public key (65-byte uncompressed P-256
 * point) and base64url private key (32-byte scalar), so they work with other tools too.
 */
class Vapid
{
    /** @return array{publicKey: string, privateKey: string} */
    public static function createKeys(?string $opensslConfig = null): array
    {
        $options = ['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'];
        if ($opensslConfig) {
            $options['config'] = $opensslConfig;
        }

        $key = openssl_pkey_new($options);
        if ($key === false) {
            throw new RuntimeException('OpenSSL could not create a key: ' . (openssl_error_string() ?: 'unknown error'));
        }

        $ec = openssl_pkey_get_details($key)['ec'];
        $d = str_pad($ec['d'], 32, "\0", STR_PAD_LEFT);
        $x = str_pad($ec['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);

        return [
            'publicKey' => self::b64url("\x04" . $x . $y),
            'privateKey' => self::b64url($d),
        ];
    }

    /** Value for the Authorization header of one push request. */
    public static function authorizationHeader(string $endpoint, string $publicKey, string $privateKey, string $subject, int $ttlSeconds = 43200): string
    {
        $parts = parse_url($endpoint);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            throw new RuntimeException('Invalid push endpoint.');
        }
        $audience = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

        $header = self::b64url(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::b64url(json_encode([
            'aud' => $audience,
            'exp' => time() + min($ttlSeconds, 86400),
            'sub' => $subject,
        ], JSON_UNESCAPED_SLASHES));
        $signingInput = $header . '.' . $claims;

        $pem = self::privateKeyPem(self::b64urlDecode($privateKey), self::b64urlDecode($publicKey));
        $derSignature = '';
        if (!openssl_sign($signingInput, $derSignature, $pem, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign the VAPID token: ' . (openssl_error_string() ?: 'check VAPID_PRIVATE_KEY'));
        }

        $jwt = $signingInput . '.' . self::b64url(self::derToRaw($derSignature));

        return 'vapid t=' . $jwt . ', k=' . $publicKey;
    }

    /** SEC1 "EC PRIVATE KEY" PEM for prime256v1 built from the raw keys. */
    public static function privateKeyPem(string $d, string $publicPoint): string
    {
        if (strlen($d) !== 32 || strlen($publicPoint) !== 65) {
            throw new RuntimeException('VAPID keys have the wrong length — regenerate them with php artisan webpush:vapid.');
        }

        $der = "\x30\x77"                                  // SEQUENCE
            . "\x02\x01\x01"                               // version 1
            . "\x04\x20" . $d                              // privateKey OCTET STRING
            . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07" // [0] OID prime256v1
            . "\xa1\x44\x03\x42\x00" . $publicPoint;       // [1] BIT STRING public key

        return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
    }

    /** ECDSA signature: DER SEQUENCE{INTEGER r, INTEGER s} → 64-byte r||s (JOSE format). */
    public static function derToRaw(string $der): string
    {
        $offset = 2;
        if (ord($der[1]) & 0x80) {
            $offset += ord($der[1]) & 0x7f;
        }

        $out = '';
        for ($i = 0; $i < 2; $i++) {
            if (ord($der[$offset]) !== 0x02) {
                throw new RuntimeException('Unexpected signature format.');
            }
            $len = ord($der[$offset + 1]);
            $int = substr($der, $offset + 2, $len);
            $offset += 2 + $len;
            $int = ltrim($int, "\0");
            $out .= str_pad($int, 32, "\0", STR_PAD_LEFT);
        }

        return $out;
    }

    public static function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function b64urlDecode(string $text): string
    {
        $text = strtr(trim($text), '-_', '+/');

        return (string) base64_decode($text . str_repeat('=', (4 - strlen($text) % 4) % 4), true);
    }
}
