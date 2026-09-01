<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\service;

/**
 * RFC 6238 Time-based One-Time Password (TOTP) implementation
 */
class totp
{
    /** @var string Base32 alphabet */
    protected static $base32_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a cryptographically secure random base32 secret
     *
     * @param int $byte_length Secret length in raw bytes (default: 20 bytes = 160 bits = 32 base32 chars)
     * @return string Base32 encoded secret
     */
    public function generate_secret($byte_length = 20)
    {
        $bytes = random_bytes($byte_length);
        return $this->base32_encode($bytes);
    }

    /**
     * Get the TOTP code for a given secret and timestamp
     *
     * @param string $secret Base32 encoded secret
     * @param int|null $time_slice Optional time slice index
     * @return string 6-digit code
     */
    public function get_code($secret, $time_slice = null)
    {
        if ($time_slice === null) {
            $time_slice = (int) floor(time() / 30);
        }

        $secret_key = $this->base32_decode($secret);

        // Pack time slice into 8-byte big-endian binary string
        $time_bin = pack('N*', 0) . pack('N*', $time_slice);

        // Hash using HMAC-SHA1
        $hash = hash_hmac('sha1', $time_bin, $secret_key, true);

        // Dynamic truncation (RFC 4226)
        $offset = ord(substr($hash, -1)) & 0x0F;
        $hash_part = substr($hash, $offset, 4);

        $value = unpack('N', $hash_part);
        $value = $value[1] & 0x7FFFFFFF;

        $modulo = pow(10, 6);
        $code = str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);

        return $code;
    }

    /**
     * Verify a submitted TOTP code against a secret
     *
     * @param string $secret Base32 encoded secret
     * @param string $code 6-digit code from user
     * @param int $disparity Number of 30-second windows before/after to check (default: 2 = +/- 60s)
     * @return bool True if valid, false otherwise
     */
    public function verify_code($secret, $code, $disparity = 2)
    {
        $code = preg_replace('/[^0-9]/', '', (string)$code);

        if (strlen($code) !== 6) {
            return false;
        }

        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', (string)$secret));
        if (empty($secret)) {
            return false;
        }

        $current_slice = (int) floor(time() / 30);

        for ($i = -$disparity; $i <= $disparity; $i++) {
            $calculated_code = $this->get_code($secret, $current_slice + $i);
            if (hash_equals($calculated_code, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format a secret string with readable spaces for display (e.g. "ABCD EFGH IJKL MNOP")
     *
     * @param string $secret
     * @return string
     */
    public function format_secret_for_display($secret)
    {
        $clean = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));
        return chunk_split($clean, 4, ' ');
    }

    /**
     * Build standard otpauth:// URI for authenticator applications
     *
     * @param string $secret
     * @param string $account_name User's username or email
     * @param string $issuer Forum/Board name
     * @return string
     */
    public function get_provisioning_uri($secret, $account_name, $issuer = 'phpBB')
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($account_name);
        $clean_secret = preg_replace('/[^A-Z2-7]/i', '', strtoupper($secret));

        $params = [
            'secret' => $clean_secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ];

        return 'otpauth://totp/' . $label . '?' . http_build_query($params);
    }

    /**
     * Base32 encoding (RFC 4648)
     *
     * @param string $data Raw binary data
     * @return string Base32 string
     */
    public function base32_encode($data)
    {
        if (empty($data)) {
            return '';
        }

        $binary = '';
        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $binary .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }

        $chars = self::$base32_chars;
        $encoded = '';
        $chunks = str_split($binary, 5);

        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $index = bindec($chunk);
            $encoded .= $chars[$index];
        }

        return $encoded;
    }

    /**
     * Base32 decoding (RFC 4648)
     *
     * @param string $secret Base32 string
     * @return string Raw binary data
     */
    public function base32_decode($secret)
    {
        $clean = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));
        if (empty($clean)) {
            return '';
        }

        $chars = self::$base32_chars;
        $binary = '';
        $length = strlen($clean);

        for ($i = 0; $i < $length; $i++) {
            $pos = strpos($chars, $clean[$i]);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $bytes = str_split($binary, 8);
        $decoded = '';
        foreach ($bytes as $byte) {
            if (strlen($byte) === 8) {
                $decoded .= chr(bindec($byte));
            }
        }

        return $decoded;
    }
}
