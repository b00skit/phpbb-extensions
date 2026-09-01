<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\service;

class twofactor_manager
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\user */
    protected $user;

    /** @var \phpbb\log\log */
    protected $log;

    /** @var \phpbb\request\request */
    protected $request;

    /** @var \booskit\twofactor\service\totp */
    protected $totp;

    /** @var \booskit\twofactor\service\backup_code_manager */
    protected $backup_codes;

    /** @var string */
    protected $table_prefix;

    /** @var string */
    protected $users_table;

    /** @var string */
    protected $sessions_table;

    /** @var string */
    protected $trusted_devices_table;

    /** @var string */
    protected $pending_logins_table;

    /** @var array Cache for user groups */
    protected $user_groups_cache = [];

    /** @var array Cache for user 2fa record */
    protected $user_record_cache = [];

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\db\driver\driver_interface $db,
        \phpbb\user $user,
        \phpbb\request\request $request,
        \phpbb\log\log $log,
        \booskit\twofactor\service\totp $totp,
        \booskit\twofactor\service\backup_code_manager $backup_codes,
        $table_prefix
    ) {
        $this->config = $config;
        $this->db = $db;
        $this->user = $user;
        $this->request = $request;
        $this->log = $log;
        $this->totp = $totp;
        $this->backup_codes = $backup_codes;
        $this->table_prefix = $table_prefix;

        $this->users_table = $table_prefix . 'booskit_2fa_users';
        $this->sessions_table = $table_prefix . 'booskit_2fa_sessions';
        $this->trusted_devices_table = $table_prefix . 'booskit_2fa_trusted_devices';
        $this->pending_logins_table = $table_prefix . 'booskit_2fa_pending_logins';
    }

    /**
     * Check if 2FA extension is globally enabled
     *
     * @return bool
     */
    public function is_globally_enabled()
    {
        return !empty($this->config['booskit_2fa_enabled']);
    }

    /**
     * Get issuer name for Authenticator app
     *
     * @return string
     */
    public function get_issuer_name()
    {
        if (!empty($this->config['booskit_2fa_issuer'])) {
            return $this->config['booskit_2fa_issuer'];
        }
        return !empty($this->config['sitename']) ? $this->config['sitename'] : 'phpBB';
    }

    /**
     * Get primary theme/authenticator color (hex code)
     *
     * @return string
     */
    public function get_theme_color()
    {
        return !empty($this->config['booskit_2fa_color']) ? $this->config['booskit_2fa_color'] : '#2563eb';
    }

    /**
     * Get custom logo URL
     *
     * @return string
     */
    public function get_logo_url()
    {
        return !empty($this->config['booskit_2fa_logo_url']) ? $this->config['booskit_2fa_logo_url'] : '';
    }

    /**
     * Check if booskit/gtawoauth extension is installed and enabled
     *
     * @return bool
     */
    public function is_gtawoauth_enabled()
    {
        $sql = 'SELECT ext_active FROM ' . $this->table_prefix . "ext WHERE ext_name = 'booskit/gtawoauth'";
        $result = $this->db->sql_query($sql);
        $active = (bool)$this->db->sql_fetchfield('ext_active');
        $this->db->sql_freeresult($result);

        return $active;
    }

    /**
     * Get 2FA record for a user
     *
     * @param int $user_id
     * @return array|null
     */
    public function get_user_record($user_id)
    {
        $user_id = (int)$user_id;
        if (isset($this->user_record_cache[$user_id])) {
            return $this->user_record_cache[$user_id];
        }

        $sql = 'SELECT * FROM ' . $this->users_table . ' WHERE user_id = ' . $user_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        $this->user_record_cache[$user_id] = $row ?: null;
        return $this->user_record_cache[$user_id];
    }

    /**
     * Check if user has completed 2FA setup and enabled it
     *
     * @param int $user_id
     * @return bool
     */
    public function is_user_2fa_enabled($user_id)
    {
        $record = $this->get_user_record($user_id);
        return !empty($record) && !empty($record['is_enabled']);
    }

    /**
     * Get all group IDs for a user (including default group and user_group memberships)
     *
     * @param int $user_id
     * @return array
     */
    public function get_user_group_ids($user_id)
    {
        $user_id = (int)$user_id;
        if (isset($this->user_groups_cache[$user_id])) {
            return $this->user_groups_cache[$user_id];
        }

        $groups = [];

        // Check user_group table
        $sql = 'SELECT group_id FROM ' . $this->table_prefix . 'user_group WHERE user_id = ' . $user_id . ' AND user_pending = 0';
        $result = $this->db->sql_query($sql);
        while ($row = $this->db->sql_fetchrow($result)) {
            $groups[] = (int)$row['group_id'];
        }
        $this->db->sql_freeresult($result);

        // Also check primary group in users table if available
        if ($this->user->data['user_id'] == $user_id && !empty($this->user->data['group_id'])) {
            $groups[] = (int)$this->user->data['group_id'];
        } else {
            $sql = 'SELECT group_id FROM ' . $this->table_prefix . 'users WHERE user_id = ' . $user_id;
            $result = $this->db->sql_query($sql);
            $primary_group = (int)$this->db->sql_fetchfield('group_id');
            $this->db->sql_freeresult($result);
            if ($primary_group) {
                $groups[] = $primary_group;
            }
        }

        $this->user_groups_cache[$user_id] = array_values(array_unique(array_filter($groups)));
        return $this->user_groups_cache[$user_id];
    }

    /**
     * Check if a user matches a comma-separated list of group IDs
     *
     * @param int $user_id
     * @param string|array $group_list
     * @return bool
     */
    public function matches_groups($user_id, $group_list)
    {
        if (is_string($group_list)) {
            if (trim($group_list) === '') {
                return false;
            }
            $target_groups = array_map('intval', explode(',', $group_list));
        } elseif (is_array($group_list)) {
            $target_groups = array_map('intval', $group_list);
        } else {
            return false;
        }

        $target_groups = array_filter($target_groups);
        if (empty($target_groups)) {
            return false;
        }

        $user_groups = $this->get_user_group_ids($user_id);
        $intersection = array_intersect($user_groups, $target_groups);

        return !empty($intersection);
    }

    /**
     * Check if user is enforced to set up 2FA
     *
     * @param int $user_id
     * @return bool
     */
    public function is_user_enforced($user_id)
    {
        if (!$this->is_globally_enabled()) {
            return false;
        }
        $enforce_groups = isset($this->config['booskit_2fa_groups_enforce']) ? $this->config['booskit_2fa_groups_enforce'] : '';
        return $this->matches_groups($user_id, $enforce_groups);
    }

    /**
     * Check if user should receive a 2FA suggestion prompt
     *
     * @param int $user_id
     * @return bool
     */
    public function is_user_suggested($user_id)
    {
        if (!$this->is_globally_enabled()) {
            return false;
        }

        // If user already has 2FA enabled, no suggestion needed
        if ($this->is_user_2fa_enabled($user_id)) {
            return false;
        }

        // If user is enforced, they get blocked, not suggested
        if ($this->is_user_enforced($user_id)) {
            return false;
        }

        $suggest_groups = isset($this->config['booskit_2fa_groups_suggest']) ? $this->config['booskit_2fa_groups_suggest'] : '';
        if (!$this->matches_groups($user_id, $suggest_groups)) {
            return false;
        }

        // Check if suggestion was dismissed in last 30 days
        $record = $this->get_user_record($user_id);
        if ($record && !empty($record['suggestion_dismissed_until'])) {
            if (time() < (int)$record['suggestion_dismissed_until']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user is permitted to remember their device (IP) for 30 days
     * Note: "booskit_2fa_groups_remember" acts as an EXCLUSION list.
     * Select the groups that are NOT ALLOWED to have this feature.
     * If no groups are checked (empty), ALL users with 2FA are permitted.
     * If groups are checked, any user belonging to one of those checked groups is NOT permitted.
     *
     * @param int $user_id
     * @return bool
     */
    public function is_remember_device_permitted($user_id)
    {
        if (!$this->is_globally_enabled()) {
            return false;
        }

        $excluded_groups = isset($this->config['booskit_2fa_groups_remember']) ? $this->config['booskit_2fa_groups_remember'] : '';
        if (empty($excluded_groups)) {
            return true;
        }

        return !$this->matches_groups($user_id, $excluded_groups);
    }

    /**
     * Get the remember device cookie token from request
     *
     * @return string
     */
    public function get_remember_device_cookie()
    {
        $cookie_prefix = !empty($this->config['cookie_name']) ? $this->config['cookie_name'] : 'phpbb3';
        $candidates = [
            $cookie_prefix . '_2fa_remember',
            '2fa_remember',
            'phpbb3_2fa_remember',
        ];

        foreach ($candidates as $name) {
            $token = $this->request->variable($name, '', false, \phpbb\request\request_interface::COOKIE);
            $token = trim($token);
            if (strlen($token) === 64 && ctype_xdigit($token)) {
                return $token;
            }
        }

        return '';
    }

    /**
     * Check if the user's current device is trusted/remembered for 30 days via secure cookie token
     *
     * @param int $user_id
     * @return bool
     */
    public function is_device_remembered($user_id)
    {
        $user_id = (int)$user_id;
        if (!$this->is_remember_device_permitted($user_id)) {
            return false;
        }

        $token = $this->get_remember_device_cookie();
        if (empty($token)) {
            return false;
        }

        $token_hash = hash('sha256', $token);
        $time = time();

        $sql = 'SELECT device_id FROM ' . $this->trusted_devices_table . '
                WHERE user_id = ' . $user_id . '
                AND device_token_hash = \'' . $this->db->sql_escape($token_hash) . '\'
                AND expires_at > ' . $time;
        $result = $this->db->sql_query_limit($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return !empty($row);
    }

    /**
     * Trust/Remember user's current device for 30 days by issuing a persistent secure token cookie
     *
     * @param int $user_id
     * @return bool
     */
    public function remember_device($user_id)
    {
        $user_id = (int)$user_id;
        if (!$this->is_remember_device_permitted($user_id)) {
            return false;
        }

        $ip = $this->user->ip;
        $ip_hash = md5($ip);
        $time = time();
        $expires_at = $time + (30 * 86400);

        try {
            $raw_token = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            $raw_token = md5(uniqid(mt_rand(), true) . microtime() . $user_id) . md5($ip . $time);
        }

        $token_hash = hash('sha256', $raw_token);

        // 1. Set via phpBB user set_cookie if available
        if (method_exists($this->user, 'set_cookie')) {
            $this->user->set_cookie('2fa_remember', $raw_token, $expires_at);
        }

        // 2. Set via PHP native setcookie() with broad path and proper flags
        $cookie_name = (!empty($this->config['cookie_name']) ? $this->config['cookie_name'] : 'phpbb3') . '_2fa_remember';
        $cookie_path = !empty($this->config['cookie_path']) ? $this->config['cookie_path'] : '/';
        $cookie_domain = (!empty($this->config['cookie_domain']) && $this->config['cookie_domain'] !== 'localhost') ? $this->config['cookie_domain'] : '';

        // Determine if connection is actually HTTPS via phpbb request class
        $https = $this->request->server('HTTPS', '');
        $server_port = (int)$this->request->server('SERVER_PORT', 0);
        $forwarded_proto = strtolower($this->request->server('HTTP_X_FORWARDED_PROTO', ''));

        $is_https = (!empty($https) && $https !== 'off')
            || ($server_port === 443)
            || ($forwarded_proto === 'https');

        if (PHP_VERSION_ID >= 70300) {
            @setcookie($cookie_name, $raw_token, [
                'expires'  => $expires_at,
                'path'     => $cookie_path,
                'domain'   => $cookie_domain,
                'secure'   => $is_https,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            @setcookie('2fa_remember', $raw_token, [
                'expires'  => $expires_at,
                'path'     => $cookie_path,
                'domain'   => $cookie_domain,
                'secure'   => $is_https,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            @setcookie($cookie_name, $raw_token, $expires_at, $cookie_path, $cookie_domain, $is_https, true);
            @setcookie('2fa_remember', $raw_token, $expires_at, $cookie_path, $cookie_domain, $is_https, true);
        }

        $sql_ary = [
            'user_id'           => $user_id,
            'ip_address'        => $ip,
            'ip_hash'           => $ip_hash,
            'created_at'        => $time,
            'expires_at'        => $expires_at,
            'device_token_hash' => $token_hash,
        ];
        $sql = 'INSERT INTO ' . $this->trusted_devices_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
        $this->db->sql_query($sql);

        return true;
    }

    /**
     * Revoke all trusted devices for a user and clear client cookie
     *
     * @param int $user_id
     */
    public function revoke_trusted_devices($user_id)
    {
        $user_id = (int)$user_id;
        $sql = 'DELETE FROM ' . $this->trusted_devices_table . ' WHERE user_id = ' . $user_id;
        $this->db->sql_query($sql);

        if (method_exists($this->user, 'set_cookie')) {
            $this->user->set_cookie('2fa_remember', '', time() - 3600);
        }

        $cookie_name = (!empty($this->config['cookie_name']) ? $this->config['cookie_name'] : 'phpbb3') . '_2fa_remember';
        $cookie_path = !empty($this->config['cookie_path']) ? $this->config['cookie_path'] : '/';
        $cookie_domain = (!empty($this->config['cookie_domain']) && $this->config['cookie_domain'] !== 'localhost') ? $this->config['cookie_domain'] : '';

        if (PHP_VERSION_ID >= 70300) {
            @setcookie($cookie_name, '', [
                'expires'  => time() - 3600,
                'path'     => $cookie_path,
                'domain'   => $cookie_domain,
                'secure'   => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            @setcookie('2fa_remember', '', [
                'expires'  => time() - 3600,
                'path'     => $cookie_path,
                'domain'   => $cookie_domain,
                'secure'   => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            @setcookie($cookie_name, '', time() - 3600, $cookie_path, $cookie_domain, false, true);
            @setcookie('2fa_remember', '', time() - 3600, $cookie_path, $cookie_domain, false, true);
        }
    }

    /**
     * Get count of active remembered devices for user
     *
     * @param int $user_id
     * @return int
     */
    public function get_trusted_devices_count($user_id)
    {
        $user_id = (int)$user_id;
        $time = time();
        $sql = 'SELECT COUNT(device_id) AS total FROM ' . $this->trusted_devices_table . '
                WHERE user_id = ' . $user_id . ' AND expires_at > ' . $time;
        $result = $this->db->sql_query($sql);
        $total = (int)$this->db->sql_fetchfield('total');
        $this->db->sql_freeresult($result);
        return $total;
    }

    /**
     * Check if 2FA verification is required on login for this user
     *
     * @param int $user_id
     * @param bool $is_oauth
     * @return bool
     */
    public function is_2fa_required_for_login($user_id, $is_oauth = false)
    {
        if (!$this->is_globally_enabled()) {
            return false;
        }

        // If 2FA is not enabled on user account, cannot verify
        if (!$this->is_user_2fa_enabled($user_id)) {
            return false;
        }

        // If device is remembered for 30 days, skip 2FA prompt
        if ($this->is_device_remembered($user_id)) {
            return false;
        }

        if ($is_oauth) {
            $oauth_groups = isset($this->config['booskit_2fa_groups_oauth']) ? $this->config['booskit_2fa_groups_oauth'] : '';
            if (empty($oauth_groups)) {
                return false;
            }

            return $this->matches_groups($user_id, $oauth_groups);
        }

        // For standard login: all users with 2FA enabled are prompted unless remembered
        return true;
    }

    /**
     * Check if a module is configured to ignore remembered trusted devices
     *
     * @param string $module 'ucp', 'mcp', 'acp', 'login'
     * @return bool
     */
    public function is_ignore_remember_for_module($module)
    {
        switch ($module) {
            case 'ucp':
                return !empty($this->config['booskit_2fa_ucp_ignore_remember']);
            case 'mcp':
                return !empty($this->config['booskit_2fa_mcp_ignore_remember']);
            case 'acp':
                return !empty($this->config['booskit_2fa_acp_ignore_remember']);
            default:
                return false;
        }
    }

    /**
     * Check if 2FA prompt is required for accessing UCP
     *
     * @param int $user_id
     * @return bool
     */
    public function is_2fa_required_for_ucp($user_id)
    {
        if (!$this->is_globally_enabled()) {
            return false;
        }

        if (!$this->is_user_2fa_enabled($user_id)) {
            return false;
        }

        if (!$this->is_ignore_remember_for_module('ucp') && $this->is_device_remembered($user_id)) {
            return false;
        }

        $ucp_groups = isset($this->config['booskit_2fa_groups_ucp']) ? $this->config['booskit_2fa_groups_ucp'] : '';
        if (empty($ucp_groups)) {
            return false;
        }

        return $this->matches_groups($user_id, $ucp_groups);
    }

    /**
     * Check if 2FA prompt is required for accessing MCP
     *
     * @param int $user_id
     * @return bool
     */
    public function is_2fa_required_for_mcp($user_id)
    {
        if (!$this->is_globally_enabled()) {
            return false;
        }

        if (!$this->is_user_2fa_enabled($user_id)) {
            return false;
        }

        if (!$this->is_ignore_remember_for_module('mcp') && $this->is_device_remembered($user_id)) {
            return false;
        }

        $mcp_groups = isset($this->config['booskit_2fa_groups_mcp']) ? $this->config['booskit_2fa_groups_mcp'] : '';
        if (empty($mcp_groups)) {
            return false;
        }

        return $this->matches_groups($user_id, $mcp_groups);
    }

    /**
     * Check if 2FA prompt is required for accessing ACP
     *
     * @param int $user_id
     * @return bool
     */
    public function is_2fa_required_for_acp($user_id)
    {
        if (!$this->is_globally_enabled()) {
            return false;
        }

        if (!$this->is_user_2fa_enabled($user_id)) {
            return false;
        }

        if (!$this->is_ignore_remember_for_module('acp') && $this->is_device_remembered($user_id)) {
            return false;
        }

        $acp_groups = isset($this->config['booskit_2fa_groups_acp']) ? $this->config['booskit_2fa_groups_acp'] : '';
        if (empty($acp_groups)) {
            return false;
        }

        return $this->matches_groups($user_id, $acp_groups);
    }

    /**
     * Check if Shared Session Authentication is enabled
     *
     * @return bool
     */
    public function is_shared_session_enabled()
    {
        return !empty($this->config['booskit_2fa_shared_session']);
    }

    /**
     * Check if current session is verified for 2FA for a given module
     *
     * @param string $session_id
     * @param int $user_id
     * @param string $module 'login', 'ucp', 'mcp', 'acp'
     * @return bool
     */
    public function is_session_verified($session_id, $user_id, $module = 'login')
    {
        if (empty($session_id) || empty($user_id)) {
            return false;
        }

        $sql = "SELECT is_verified, verified_ucp, verified_mcp, verified_acp FROM " . $this->sessions_table . "
                WHERE session_id = '" . $this->db->sql_escape($session_id) . "'
                AND user_id = " . (int)$user_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$row) {
            return false;
        }

        if ($module === 'login') {
            return !empty($row['is_verified']);
        }

        $column = 'verified_' . $module;
        if (!empty($row[$column])) {
            return true;
        }

        // If Shared Session Authentication is enabled, ACP verification or any panel verification shares access across all panels (UCP, MCP, ACP)
        if ($this->is_shared_session_enabled() && in_array($module, ['ucp', 'mcp', 'acp'])) {
            if (!empty($row['verified_acp']) || !empty($row['verified_ucp']) || !empty($row['verified_mcp'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the latest verified 2FA session record for a user
     *
     * @param int $user_id
     * @return array|null
     */
    public function get_latest_verified_session($user_id)
    {
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            return null;
        }

        $sql = 'SELECT * FROM ' . $this->sessions_table . '
                WHERE user_id = ' . $user_id . ' AND is_verified = 1
                ORDER BY verified_at DESC';
        $result = $this->db->sql_query_limit($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return $row ?: null;
    }

    /**
     * Copy / inherit 2FA verification from an existing session to a newly created session (e.g. ACP session)
     *
     * @param string $new_session_id
     * @param int $user_id
     * @param bool $is_admin
     * @return bool
     */
    public function sync_admin_session_verification($new_session_id, $user_id, $is_admin = true)
    {
        $user_id = (int)$user_id;
        $new_session_id = (string)$new_session_id;

        if (empty($new_session_id) || $user_id <= 0) {
            return false;
        }

        $is_acp_required = $this->is_2fa_required_for_acp($user_id);
        $prev = $this->get_latest_verified_session($user_id);
        $is_shared = $this->is_shared_session_enabled();

        $time = time();
        $ip = $this->user->ip;

        // If user already had a verified session on the board, login verification carries over
        $is_login_verified = $prev ? !empty($prev['is_verified']) : true;
        $verified_ucp = $prev ? (int)!empty($prev['verified_ucp']) : 0;
        $verified_mcp = $prev ? (int)!empty($prev['verified_mcp']) : 0;
        $verified_acp = $prev ? (int)!empty($prev['verified_acp']) : 0;

        if ($is_shared && ($verified_ucp || $verified_mcp || $verified_acp)) {
            $verified_ucp = 1;
            $verified_mcp = 1;
            $verified_acp = 1;
        }

        // Always ensure login 2FA is verified for the admin session so no re-prompt on board index
        $is_login_verified = 1;

        // Check if new session already exists in 2fa_sessions table
        $sql = 'SELECT session_id, is_verified, verified_acp FROM ' . $this->sessions_table . "
                WHERE session_id = '" . $this->db->sql_escape($new_session_id) . "'";
        $result = $this->db->sql_query($sql);
        $exists = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($exists) {
            $update_ary = [];
            if ($is_login_verified && empty($exists['is_verified'])) {
                $update_ary['is_verified'] = 1;
                $update_ary['verified_at'] = $time;
            }
            if ($verified_acp && empty($exists['verified_acp'])) {
                $update_ary['verified_acp'] = 1;
            }
            if ($verified_ucp) {
                $update_ary['verified_ucp'] = 1;
            }
            if ($verified_mcp) {
                $update_ary['verified_mcp'] = 1;
            }

            if (!empty($update_ary)) {
                $sql = 'UPDATE ' . $this->sessions_table . '
                        SET ' . $this->db->sql_build_array('UPDATE', $update_ary) . "
                        WHERE session_id = '" . $this->db->sql_escape($new_session_id) . "'";
                $this->db->sql_query($sql);
            }
        } else {
            $sql_ary = [
                'session_id'     => $new_session_id,
                'user_id'        => $user_id,
                'is_verified'    => $is_login_verified ? 1 : 0,
                'verified_ucp'   => $verified_ucp,
                'verified_mcp'   => $verified_mcp,
                'verified_acp'   => $verified_acp ? 1 : 0,
                'verified_at'    => $is_login_verified ? $time : 0,
                'ip_hash'        => md5($ip),
                'pending_secret' => '',
                'auth_via_oauth' => 0,
            ];
            $sql = 'INSERT INTO ' . $this->sessions_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);
        }

        return true;
    }

    /**
     * Clear 2FA session records for a user upon logout
     *
     * @param int $user_id
     */
    public function clear_user_sessions($user_id)
    {
        $user_id = (int)$user_id;
        if ($user_id > 0) {
            $sql = 'DELETE FROM ' . $this->sessions_table . ' WHERE user_id = ' . $user_id;
            $this->db->sql_query($sql);
        }
    }

    /**
     * Clear 2FA session record for a specific session ID
     *
     * @param string $session_id
     */
    public function clear_session($session_id)
    {
        $session_id = (string)$session_id;
        if (!empty($session_id)) {
            $sql = 'DELETE FROM ' . $this->sessions_table . " WHERE session_id = '" . $this->db->sql_escape($session_id) . "'";
            $this->db->sql_query($sql);
        }
    }

    /**
     * Check if current session was created via OAuth login
     *
     * @param string $session_id
     * @return bool
     */
    public function is_session_oauth($session_id)
    {
        if (empty($session_id)) {
            return false;
        }

        $sql = 'SELECT auth_via_oauth FROM ' . $this->sessions_table . "
                WHERE session_id = '" . $this->db->sql_escape($session_id) . "'";
        $result = $this->db->sql_query($sql);
        $is_oauth = (bool)$this->db->sql_fetchfield('auth_via_oauth');
        $this->db->sql_freeresult($result);

        return $is_oauth;
    }

    /**
     * Mark session as created via OAuth
     *
     * @param string $session_id
     * @param int $user_id
     */
    public function mark_session_oauth($session_id, $user_id)
    {
        $session_id = (string)$session_id;
        $user_id = (int)$user_id;
        $ip = $this->user->ip;

        $sql = 'SELECT session_id FROM ' . $this->sessions_table . " WHERE session_id = '" . $this->db->sql_escape($session_id) . "'";
        $result = $this->db->sql_query($sql);
        $exists = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($exists) {
            $sql = 'UPDATE ' . $this->sessions_table . "
                    SET auth_via_oauth = 1, user_id = {$user_id}
                    WHERE session_id = '" . $this->db->sql_escape($session_id) . "'";
            $this->db->sql_query($sql);
        } else {
            $sql_ary = [
                'session_id'     => $session_id,
                'user_id'        => $user_id,
                'is_verified'    => 0,
                'verified_ucp'   => 0,
                'verified_mcp'   => 0,
                'verified_acp'   => 0,
                'verified_at'    => 0,
                'ip_hash'        => md5($ip),
                'pending_secret' => '',
                'auth_via_oauth' => 1,
            ];
            $sql = 'INSERT INTO ' . $this->sessions_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);
        }
    }

    /**
     * Mark session as 2FA verified for a specific module
     *
     * @param string $session_id
     * @param int $user_id
     * @param string $method 'totp', 'backup_code', or 'trusted_device'
     * @param string $module 'login', 'ucp', 'mcp', 'acp', 'all'
     */
    public function mark_session_verified($session_id, $user_id, $method = 'totp', $module = 'login')
    {
        $user_id = (int)$user_id;
        $time = time();
        $ip = $this->user->ip;

        // Upsert session
        $sql = 'SELECT session_id FROM ' . $this->sessions_table . " WHERE session_id = '" . $this->db->sql_escape($session_id) . "'";
        $result = $this->db->sql_query($sql);
        $exists = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        $is_shared = $this->is_shared_session_enabled();
        $is_shared_panel = in_array($module, ['ucp', 'mcp', 'acp']) && $is_shared;

        $set_clause = "is_verified = 1, verified_at = {$time}, user_id = {$user_id}, ip_hash = '" . $this->db->sql_escape(md5($ip)) . "'";
        if ($module === 'all' || $is_shared_panel) {
            $set_clause .= ", verified_ucp = 1, verified_mcp = 1, verified_acp = 1";
        } elseif ($module === 'ucp') {
            $set_clause .= ", verified_ucp = 1";
        } elseif ($module === 'mcp') {
            $set_clause .= ", verified_mcp = 1";
        } elseif ($module === 'acp') {
            $set_clause .= ", verified_acp = 1";
        }

        if ($exists) {
            $sql = 'UPDATE ' . $this->sessions_table . "
                    SET {$set_clause}
                    WHERE session_id = '" . $this->db->sql_escape($session_id) . "'";
            $this->db->sql_query($sql);
        } else {
            $sql_ary = [
                'session_id'   => $session_id,
                'user_id'      => $user_id,
                'is_verified'  => 1,
                'verified_ucp' => ($module === 'all' || $is_shared_panel || $module === 'ucp') ? 1 : 0,
                'verified_mcp' => ($module === 'all' || $is_shared_panel || $module === 'mcp') ? 1 : 0,
                'verified_acp' => ($module === 'all' || $is_shared_panel || $module === 'acp') ? 1 : 0,
                'verified_at'  => $time,
                'ip_hash'      => md5($ip),
            ];
            $sql = 'INSERT INTO ' . $this->sessions_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);
        }

        // If shared session is enabled and this was a panel verification (or 'all'), propagate panel verification across all active verified sessions for this user
        if ($is_shared && ($is_shared_panel || $module === 'all')) {
            $sql = 'UPDATE ' . $this->sessions_table . "
                    SET verified_ucp = 1, verified_mcp = 1, verified_acp = 1
                    WHERE user_id = {$user_id} AND is_verified = 1";
            $this->db->sql_query($sql);
        }

        // Update user's last login information
        $record = $this->get_user_record($user_id);
        if ($record) {
            $sql = 'UPDATE ' . $this->users_table . "
                    SET last_login_at = {$time},
                        last_login_ip = '" . $this->db->sql_escape($ip) . "',
                        last_login_method = '" . $this->db->sql_escape($method) . "'
                    WHERE user_id = {$user_id}";
            $this->db->sql_query($sql);
        } else {
            $sql_ary = [
                'user_id'              => $user_id,
                'secret'               => '',
                'is_enabled'           => 1,
                'enabled_at'           => $time,
                'last_login_at'        => $time,
                'last_login_ip'        => $ip,
                'last_login_method'    => $method,
                'reset_backup_pending' => 0,
            ];
            $sql = 'INSERT INTO ' . $this->users_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);
        }

        unset($this->user_record_cache[$user_id]);
    }

    /**
     * Enable 2FA for a user with verified secret
     *
     * @param int $user_id
     * @param string $secret
     */
    public function enable_user_2fa($user_id, $secret)
    {
        $user_id = (int)$user_id;
        $time = time();
        $record = $this->get_user_record($user_id);

        if ($record) {
            $sql = 'UPDATE ' . $this->users_table . "
                    SET secret = '" . $this->db->sql_escape($secret) . "',
                        is_enabled = 1,
                        enabled_at = {$time},
                        reset_backup_pending = 0
                    WHERE user_id = {$user_id}";
            $this->db->sql_query($sql);
        } else {
            $sql_ary = [
                'user_id'              => $user_id,
                'secret'               => $secret,
                'is_enabled'           => 1,
                'enabled_at'           => $time,
                'last_login_at'        => 0,
                'last_login_ip'        => '',
                'last_login_method'    => '',
                'reset_backup_pending' => 0,
            ];
            $sql = 'INSERT INTO ' . $this->users_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);
        }

        unset($this->user_record_cache[$user_id]);
    }

    /**
     * Disable/Remove 2FA for a user
     *
     * @param int $user_id
     * @param int|null $admin_user_id
     */
    public function disable_user_2fa($user_id, $admin_user_id = null)
    {
        $user_id = (int)$user_id;

        // Clear user 2FA record
        $sql = 'DELETE FROM ' . $this->users_table . ' WHERE user_id = ' . $user_id;
        $this->db->sql_query($sql);

        // Delete backup codes
        $this->backup_codes->delete_user_codes($user_id);

        // Clear 2FA session records
        $sql = 'DELETE FROM ' . $this->sessions_table . ' WHERE user_id = ' . $user_id;
        $this->db->sql_query($sql);

        // Revoke remembered devices
        $this->revoke_trusted_devices($user_id);

        unset($this->user_record_cache[$user_id]);

        if ($admin_user_id) {
            $this->log->add('admin', $admin_user_id, $this->user->ip, 'LOG_BOOSKIT_2FA_ADMIN_REMOVED', false, [(int)$user_id]);
        }
    }

    /**
     * Reset backup keys for a user and flag them to be displayed on next login
     *
     * @param int $user_id
     * @param int|null $admin_user_id
     * @return array Plaintext new backup keys
     */
    public function reset_backup_keys($user_id, $admin_user_id = null)
    {
        $user_id = (int)$user_id;

        // Generate 10 new codes
        $new_codes = $this->backup_codes->generate_codes($user_id, 10);

        // Set pending flag
        $sql = 'UPDATE ' . $this->users_table . '
                SET reset_backup_pending = 1
                WHERE user_id = ' . $user_id;
        $this->db->sql_query($sql);

        unset($this->user_record_cache[$user_id]);

        if ($admin_user_id) {
            $this->log->add('admin', $admin_user_id, $this->user->ip, 'LOG_BOOSKIT_2FA_ADMIN_RESET_BACKUP', false, [(int)$user_id]);
        }

        return $new_codes;
    }

    /**
     * Clear the reset_backup_pending flag once user has acknowledged their keys
     *
     * @param int $user_id
     */
    public function clear_reset_backup_pending($user_id)
    {
        $user_id = (int)$user_id;
        $sql = 'UPDATE ' . $this->users_table . '
                SET reset_backup_pending = 0
                WHERE user_id = ' . $user_id;
        $this->db->sql_query($sql);
        unset($this->user_record_cache[$user_id]);
    }

    /**
     * Check if user has reset backup keys pending acknowledgment
     *
     * @param int $user_id
     * @return bool
     */
    public function is_reset_backup_pending($user_id)
    {
        $record = $this->get_user_record($user_id);
        return !empty($record) && !empty($record['reset_backup_pending']);
    }

    /**
     * Dismiss 2FA suggestion prompt for 30 days
     *
     * @param int $user_id
     * @param int $days Default: 30 days
     */
    public function dismiss_suggestion($user_id, $days = 30)
    {
        $user_id = (int)$user_id;
        $until = time() + ($days * 86400);

        $record = $this->get_user_record($user_id);
        if ($record) {
            $sql = 'UPDATE ' . $this->users_table . "
                    SET suggestion_dismissed_until = {$until}
                    WHERE user_id = {$user_id}";
            $this->db->sql_query($sql);
        } else {
            $sql_ary = [
                'user_id'                    => $user_id,
                'secret'                     => '',
                'is_enabled'                 => 0,
                'enabled_at'                 => 0,
                'last_login_at'              => 0,
                'last_login_ip'              => '',
                'last_login_method'          => '',
                'suggestion_dismissed_until' => $until,
                'reset_backup_pending'       => 0,
            ];
            $sql = 'INSERT INTO ' . $this->users_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);
        }

        unset($this->user_record_cache[$user_id]);
    }

    /**
     * Get all phpBB groups for settings selection
     *
     * @return array
     */
    public function get_all_groups()
    {
        $sql = 'SELECT group_id, group_name, group_type
                FROM ' . $this->table_prefix . "groups
                WHERE group_name NOT IN ('BOTS', 'GUESTS')
                ORDER BY group_type DESC, group_name ASC";
        $result = $this->db->sql_query($sql);

        $groups = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $name = $row['group_name'];
            if ($row['group_type'] == GROUP_SPECIAL) {
                $name = isset($this->user->lang['G_' . $name]) ? $this->user->lang['G_' . $name] : $name;
            }
            $groups[] = [
                'id'   => (int)$row['group_id'],
                'name' => $name,
                'type' => (int)$row['group_type'],
            ];
        }
        $this->db->sql_freeresult($result);

        return $groups;
    }

    /**
     * Get existing pending secret or generate a new one and persist in DB/session (is_enabled = 0)
     *
     * @param string $session_id
     * @param int $user_id
     * @return string
     */
    public function get_or_create_pending_secret($session_id, $user_id)
    {
        $user_id = (int)$user_id;
        $session_id = (string)$session_id;

        // 1. Check if user already has an enabled secret
        $record = $this->get_user_record($user_id);
        if ($record && !empty($record['secret']) && !empty($record['is_enabled'])) {
            return $record['secret'];
        }

        // 2. Check if current session already has a pending secret in sessions table
        if (!empty($session_id)) {
            $sql = 'SELECT pending_secret FROM ' . $this->sessions_table . "
                    WHERE session_id = '" . $this->db->sql_escape($session_id) . "'";
            $result = $this->db->sql_query($sql);
            $session_secret = (string)$this->db->sql_fetchfield('pending_secret');
            $this->db->sql_freeresult($result);

            if (!empty($session_secret)) {
                return $session_secret;
            }
        }

        // 3. Check if user already has an existing pending secret in users table
        if ($record && !empty($record['secret'])) {
            $existing = $record['secret'];
            $this->save_session_pending_secret($session_id, $user_id, $existing);
            return $existing;
        }

        // 4. Generate new secret
        $new_secret = $this->totp->generate_secret(20);

        if ($record) {
            $sql = 'UPDATE ' . $this->users_table . "
                    SET secret = '" . $this->db->sql_escape($new_secret) . "'
                    WHERE user_id = " . $user_id;
            $this->db->sql_query($sql);
        } else {
            $sql_ary = [
                'user_id'                    => $user_id,
                'secret'                     => $new_secret,
                'is_enabled'                 => 0,
                'enabled_at'                 => 0,
                'last_login_at'              => 0,
                'last_login_ip'              => '',
                'last_login_method'          => '',
                'suggestion_dismissed_until' => 0,
                'reset_backup_pending'       => 0,
            ];
            $sql = 'INSERT INTO ' . $this->users_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);
        }

        unset($this->user_record_cache[$user_id]);
        $this->save_session_pending_secret($session_id, $user_id, $new_secret);

        return $new_secret;
    }

    /**
     * Save pending secret in sessions table
     *
     * @param string $session_id
     * @param int $user_id
     * @param string $secret
     */
    protected function save_session_pending_secret($session_id, $user_id, $secret)
    {
        if (empty($session_id)) {
            return;
        }

        $sql = 'SELECT session_id FROM ' . $this->sessions_table . "
                WHERE session_id = '" . $this->db->sql_escape($session_id) . "'";
        $result = $this->db->sql_query($sql);
        $exists = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($exists) {
            $sql = 'UPDATE ' . $this->sessions_table . "
                    SET pending_secret = '" . $this->db->sql_escape($secret) . "'
                    WHERE session_id = '" . $this->db->sql_escape($session_id) . "'";
            $this->db->sql_query($sql);
        } else {
            $sql_ary = [
                'session_id'     => $session_id,
                'user_id'        => (int)$user_id,
                'is_verified'    => 0,
                'verified_at'    => 0,
                'ip_hash'        => md5($this->user->ip),
                'pending_secret' => $secret,
            ];
            $sql = 'INSERT INTO ' . $this->sessions_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);
        }
    }

    /**
     * Get secret for user or current session
     *
     * @param string $session_id
     * @param int $user_id
     * @return string
     */
    public function get_user_secret($session_id, $user_id)
    {
        if (!empty($session_id)) {
            $sql = 'SELECT pending_secret FROM ' . $this->sessions_table . "
                    WHERE session_id = '" . $this->db->sql_escape($session_id) . "'";
            $result = $this->db->sql_query($sql);
            $session_secret = (string)$this->db->sql_fetchfield('pending_secret');
            $this->db->sql_freeresult($result);

            if (!empty($session_secret)) {
                return $session_secret;
            }
        }

        $record = $this->get_user_record($user_id);
        return ($record && !empty($record['secret'])) ? $record['secret'] : '';
    }

    /**
     * Force generate and save a fresh pending secret for session
     *
     * @param string $session_id
     * @param int $user_id
     * @return string
     */
    public function reset_pending_secret($session_id, $user_id)
    {
        $user_id = (int)$user_id;
        $session_id = (string)$session_id;
        $new_secret = $this->totp->generate_secret(20);
        $record = $this->get_user_record($user_id);

        if ($record) {
            $sql = 'UPDATE ' . $this->users_table . "
                    SET secret = '" . $this->db->sql_escape($new_secret) . "',
                        is_enabled = 0
                    WHERE user_id = " . $user_id;
            $this->db->sql_query($sql);
        } else {
            $sql_ary = [
                'user_id'                    => $user_id,
                'secret'                     => $new_secret,
                'is_enabled'                 => 0,
                'enabled_at'                 => 0,
                'last_login_at'              => 0,
                'last_login_ip'              => '',
                'last_login_method'          => '',
                'suggestion_dismissed_until' => 0,
                'reset_backup_pending'       => 0,
            ];
            $sql = 'INSERT INTO ' . $this->users_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);
        }

        unset($this->user_record_cache[$user_id]);
        $this->save_session_pending_secret($session_id, $user_id, $new_secret);

        return $new_secret;
    }

    /**
     * Get username by user_id
     *
     * @param int $user_id
     * @return string
     */
    public function get_username_by_id($user_id)
    {
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            return 'Anonymous';
        }
        if ((int)$this->user->data['user_id'] === $user_id && !empty($this->user->data['username'])) {
            return $this->user->data['username'];
        }

        $sql = 'SELECT username FROM ' . $this->table_prefix . 'users WHERE user_id = ' . $user_id;
        $result = $this->db->sql_query($sql);
        $username = (string)$this->db->sql_fetchfield('username');
        $this->db->sql_freeresult($result);

        return !empty($username) ? $username : 'User #' . $user_id;
    }

    /**
     * Log a 2FA audit action to both the admin log and the user log
     *
     * @param string $log_operation Language key (e.g. LOG_BOOSKIT_2FA_AUTH_SUCCESS)
     * @param int $target_user_id The user ID whose account is affected
     * @param string $target_username The username of the affected account (optional, looked up if blank)
     * @param array $args Additional sprintf arguments for the log message
     * @param int|null $actor_user_id User ID performing the action (default: currently logged-in user)
     */
    public function log_2fa_action($log_operation, $target_user_id, $target_username = '', array $args = [], $actor_user_id = null)
    {
        $actor_user_id = $actor_user_id !== null ? (int)$actor_user_id : (int)$this->user->data['user_id'];
        $ip = $this->user->ip;
        $target_user_id = (int)$target_user_id;

        if (empty($target_username) && $target_user_id > 0) {
            $target_username = $this->get_username_by_id($target_user_id);
        }
        if (empty($target_username)) {
            $target_username = 'User #' . $target_user_id;
        }

        $log_data = array_merge([$target_username], $args);

        // 1. Add to Admin Log
        $this->log->add('admin', $actor_user_id, $ip, $log_operation, false, $log_data);

        // 2. Add to User Log (attached to target user profile)
        if ($target_user_id > 0) {
            $this->log->add('user', $actor_user_id, $ip, $log_operation, false, array_merge([
                'reportee_id' => $target_user_id,
            ], $log_data));
        }
    }

    /**
     * Create a pending login token for pre-session 2FA verification
     *
     * @param int $user_id
     * @param bool $autologin
     * @param int $viewonline
     * @param string $redirect
     * @param bool $is_oauth
     * @param bool $admin
     * @return string 64-character hex token
     */
    public function create_pending_login($user_id, $autologin = false, $viewonline = 1, $redirect = '', $is_oauth = false, $admin = false)
    {
        $user_id = (int)$user_id;
        $time = time();
        $expires_at = $time + 600; // 10 minutes expiry
        $ip = $this->user->ip;

        try {
            $token = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            $token = md5(uniqid(mt_rand(), true) . microtime() . $user_id) . md5($ip . $time);
        }

        // Clean up expired tokens periodically
        $this->cleanup_expired_pending_logins();

        // Also clean up any existing pending logins for this user to avoid stale tokens
        $this->delete_user_pending_logins($user_id);

        $sql_ary = [
            'login_token'    => $token,
            'user_id'        => $user_id,
            'autologin'      => $autologin ? 1 : 0,
            'viewonline'     => $viewonline ? 1 : 0,
            'admin'          => $admin ? 1 : 0,
            'redirect_url'   => (string)$redirect,
            'auth_via_oauth' => $is_oauth ? 1 : 0,
            'created_at'     => $time,
            'expires_at'     => $expires_at,
            'ip_address'     => (string)$ip,
        ];

        $sql = 'INSERT INTO ' . $this->pending_logins_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
        $this->db->sql_query($sql);

        return $token;
    }

    /**
     * Retrieve active pending login data by token and optionally validate client IP
     *
     * @param string $token
     * @param string $ip Optional client IP to match against creator IP
     * @return array|null
     */
    public function get_pending_login($token, $ip = '')
    {
        $token = trim((string)$token);
        if (empty($token) || strlen($token) !== 64 || !ctype_xdigit($token)) {
            return null;
        }

        $time = time();
        $sql = 'SELECT * FROM ' . $this->pending_logins_table . "
                WHERE login_token = '" . $this->db->sql_escape($token) . "'
                AND expires_at > {$time}";
        $result = $this->db->sql_query_limit($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$row) {
            return null;
        }

        // If IP is provided, ensure creator IP matches current client IP
        if (!empty($ip) && !empty($row['ip_address']) && $row['ip_address'] !== (string)$ip) {
            return null;
        }

        return $row;
    }

    /**
     * Invalidate and delete a specific pending login token
     *
     * @param string $token
     */
    public function delete_pending_login($token)
    {
        $token = trim((string)$token);
        if (!empty($token)) {
            $sql = 'DELETE FROM ' . $this->pending_logins_table . "
                    WHERE login_token = '" . $this->db->sql_escape($token) . "'";
            $this->db->sql_query($sql);
        }
    }

    /**
     * Delete all pending logins for a specific user ID
     *
     * @param int $user_id
     */
    public function delete_user_pending_logins($user_id)
    {
        $user_id = (int)$user_id;
        if ($user_id > 0) {
            $sql = 'DELETE FROM ' . $this->pending_logins_table . ' WHERE user_id = ' . $user_id;
            $this->db->sql_query($sql);
        }
    }

    /**
     * Delete expired pending login tokens from DB
     */
    public function cleanup_expired_pending_logins()
    {
        $time = time();
        $sql = 'DELETE FROM ' . $this->pending_logins_table . ' WHERE expires_at < ' . $time;
        $this->db->sql_query($sql);
    }
}

