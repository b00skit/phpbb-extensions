<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\service;

/**
 * Backup codes manager (generation, hashing, verification, and consumption)
 */
class backup_code_manager
{
    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var string */
    protected $table_prefix;

    /** @var string */
    protected $backup_codes_table;

    public function __construct(\phpbb\db\driver\driver_interface $db, $table_prefix)
    {
        $this->db = $db;
        $this->table_prefix = $table_prefix;
        $this->backup_codes_table = $table_prefix . 'booskit_2fa_backup_codes';
    }

    /**
     * Generate new backup codes for a user, replacing any existing ones
     *
     * @param int $user_id
     * @param int $count Number of backup keys to generate (default 10)
     * @return array List of plain-text codes to be shown once to the user
     */
    public function generate_codes($user_id, $count = 10)
    {
        $user_id = (int)$user_id;

        // Delete old backup codes
        $this->delete_user_codes($user_id);

        $plain_codes = [];
        $time = time();

        for ($i = 0; $i < $count; $i++) {
            $code = $this->generate_single_code();
            $plain_codes[] = $code;

            $hash = password_hash($code, PASSWORD_DEFAULT);

            $sql_ary = [
                'user_id'    => $user_id,
                'code_hash'  => $hash,
                'is_used'    => 0,
                'used_at'    => 0,
                'created_at' => $time,
            ];

            $sql = 'INSERT INTO ' . $this->backup_codes_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);
        }

        return $plain_codes;
    }

    /**
     * Generate a single random backup code formatted as XXXX-XXXX-XXXX
     *
     * @return string
     */
    protected function generate_single_code()
    {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Exclude ambiguous 0, 1, I, O
        $code = '';
        $max = strlen($chars) - 1;

        for ($i = 0; $i < 12; $i++) {
            if ($i > 0 && $i % 4 === 0) {
                $code .= '-';
            }
            $code .= $chars[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * Normalize backup code string for comparison (trim, uppercase, remove extra spaces/hyphens)
     *
     * @param string $code
     * @return string
     */
    public function normalize_code($code)
    {
        $code = strtoupper(trim($code));
        $clean = preg_replace('/[^2-9A-HJ-NP-Z]/', '', $code);
        if (strlen($clean) === 12) {
            return substr($clean, 0, 4) . '-' . substr($clean, 4, 4) . '-' . substr($clean, 8, 4);
        }
        return $code;
    }

    /**
     * Verify and consume a backup key
     *
     * @param int $user_id
     * @param string $input_code
     * @return bool True if valid and consumed, false otherwise
     */
    public function verify_and_consume_code($user_id, $input_code)
    {
        $user_id = (int)$user_id;
        $normalized = $this->normalize_code($input_code);

        $sql = 'SELECT code_id, code_hash FROM ' . $this->backup_codes_table . '
                WHERE user_id = ' . $user_id . ' AND is_used = 0';
        $result = $this->db->sql_query($sql);

        $matched_code_id = null;
        while ($row = $this->db->sql_fetchrow($result)) {
            if (password_verify($normalized, $row['code_hash']) || password_verify($input_code, $row['code_hash'])) {
                $matched_code_id = (int)$row['code_id'];
                break;
            }
        }
        $this->db->sql_freeresult($result);

        if ($matched_code_id !== null) {
            // Mark as used
            $sql = 'UPDATE ' . $this->backup_codes_table . '
                    SET is_used = 1, used_at = ' . time() . '
                    WHERE code_id = ' . $matched_code_id;
            $this->db->sql_query($sql);
            return true;
        }

        return false;
    }

    /**
     * Get remaining unused backup codes count
     *
     * @param int $user_id
     * @return int
     */
    public function get_remaining_count($user_id)
    {
        $user_id = (int)$user_id;
        $sql = 'SELECT COUNT(code_id) AS total FROM ' . $this->backup_codes_table . '
                WHERE user_id = ' . $user_id . ' AND is_used = 0';
        $result = $this->db->sql_query($sql);
        $count = (int)$this->db->sql_fetchfield('total');
        $this->db->sql_freeresult($result);

        return $count;
    }

    /**
     * Delete all backup codes for a user
     *
     * @param int $user_id
     */
    public function delete_user_codes($user_id)
    {
        $user_id = (int)$user_id;
        $sql = 'DELETE FROM ' . $this->backup_codes_table . ' WHERE user_id = ' . $user_id;
        $this->db->sql_query($sql);
    }
}
