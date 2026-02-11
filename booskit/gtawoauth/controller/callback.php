<?php
namespace booskit\gtawoauth\controller;

class callback
{
    protected $config;
    protected $request;
    protected $user;
    protected $auth;
    protected $provider;
    protected $db;
    protected $helper;
    protected $language;
    protected $table_prefix;

    public function __construct($config, $request, $user, $auth, $provider, $db, $helper, $language, $table_prefix)
    {
        $this->config = $config;
        $this->request = $request;
        $this->user = $user;
        $this->auth = $auth;
        $this->provider = $provider;
        $this->db = $db;
        $this->helper = $helper;
        $this->language = $language;
        $this->table_prefix = $table_prefix;
    }

    public function handle()
    {
        $this->language->add_lang('common', 'booskit/gtawoauth');

        $code = $this->request->variable('code', '');
        $state = $this->request->variable('state', '');

        // Check if this is a known linking state
        $linking_user_id = $this->get_user_from_state($state);

        if ($linking_user_id) {
            // It is a Linking attempt
            $this->handle_linking($code, $linking_user_id);
        } else {
            // Otherwise, treat it as a login attempt
            $this->handle_login();
        }

        // Should not happen, but return a response if it does
        return new \Symfony\Component\HttpFoundation\Response('');
    }

    protected function get_user_from_state($state)
    {
        if (empty($state)) {
            return 0;
        }

        $sql = 'SELECT user_id, expires_at FROM ' . $this->table_prefix . 'booskit_oauth_states
                WHERE state = \'' . $this->db->sql_escape($state) . '\'';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($row) {
            // Delete used state
            $sql = 'DELETE FROM ' . $this->table_prefix . 'booskit_oauth_states
                    WHERE state = \'' . $this->db->sql_escape($state) . '\'';
            $this->db->sql_query($sql);

            // Check expiry
            if (time() > $row['expires_at']) {
                return 0;
            }
            return (int) $row['user_id'];
        }

        return 0;
    }

    protected function handle_linking($code, $target_user_id)
    {
        // Ensure we are logged in as the correct user
        if ($this->user->data['user_id'] != $target_user_id) {
            // Session lost or mismatch. Force login.
            $this->user->session_create($target_user_id, false, true, true);
        }

        // For linking, we need to perform the token exchange manually using the provider
        // Note: The provider's get_redirect_uri() now returns the unified callback,
        // so we don't need to set a custom one (unless we want to verify it matches).
        // The provider uses get_redirect_uri() inside request_access_token().

        $token_data = $this->provider->perform_token_exchange($code);
        if (!$token_data || !isset($token_data['access_token'])) {
            trigger_error($this->language->lang('GTAW_LINK_FAILED_TOKEN'), E_USER_WARNING);
        }

        $access_token = $token_data['access_token'];
        $refresh_token = isset($token_data['refresh_token']) ? $token_data['refresh_token'] : '';
        $expires_in = isset($token_data['expires_in']) ? (int) $token_data['expires_in'] : 3600;
        $expires_at = time() + $expires_in;

        $user_info = $this->provider->fetch_user_info($access_token);
        if (!$user_info) {
             trigger_error($this->language->lang('GTAW_LINK_FAILED_USER'), E_USER_WARNING);
        }

        $user_details = $this->provider->get_user_details($user_info);
        if (!$user_details || !isset($user_details['user_id'])) {
             trigger_error($this->language->lang('GTAW_LINK_FAILED_USER'), E_USER_WARNING);
        }

        $external_id = $user_details['user_id'];

        // Check if this external ID is already linked
        $sql = 'SELECT user_id FROM ' . $this->table_prefix . 'oauth_accounts
                WHERE provider = \'gtaw\' AND oauth_provider_id = \'' . $this->db->sql_escape($external_id) . '\'';
        $result = $this->db->sql_query($sql);
        $existing = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($existing) {
            if ($existing['user_id'] == $this->user->data['user_id']) {
                 // Already linked to this account
            } else {
                 // Linked to another account
                 trigger_error($this->language->lang('GTAW_LINKED_TO_OTHER'), E_USER_WARNING);
            }
        } else {
            // Remove any existing link for this user/provider
            $sql = 'DELETE FROM ' . $this->table_prefix . 'oauth_accounts
                WHERE user_id = ' . (int) $this->user->data['user_id'] . '
                AND provider = \'gtaw\'';
            $this->db->sql_query($sql);

            // Link it
            $sql_ary = [
                'user_id' => (int) $this->user->data['user_id'],
                'provider' => 'gtaw',
                'oauth_provider_id' => (string) $external_id,
            ];
            $sql = 'INSERT INTO ' . $this->table_prefix . 'oauth_accounts ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);
        }

        // Store Token Data
        // Remove old tokens
        $sql = 'DELETE FROM ' . $this->table_prefix . 'booskit_oauth_tokens
            WHERE user_id = ' . (int) $this->user->data['user_id'] . '
            AND provider = \'gtaw\'';
        $this->db->sql_query($sql);

        // Add new tokens
        $sql_ary = [
            'user_id'       => (int) $this->user->data['user_id'],
            'provider'      => 'gtaw',
            'access_token'  => (string) $access_token,
            'refresh_token' => (string) $refresh_token,
            'expires_at'    => (int) $expires_at,
        ];
        $sql = 'INSERT INTO ' . $this->table_prefix . 'booskit_oauth_tokens ' . $this->db->sql_build_array('INSERT', $sql_ary);
        $this->db->sql_query($sql);

        // Redirect back to UCP
        global $phpEx;
        // Construct UCP URL for the module
        // We know the module name is likely booskit.gtawoauth.ucp.gtaw_module (from original files)
        // But the ID passed in ucp.php?i=... might be the module ID or the name.
        // Usually safe to use the fully qualified name if route allows, or build it manually.
        // The previous code used: ucp.php?i=-booskit-gtawoauth-ucp-gtaw_module&mode=link
        // Note the hyphens replacing dots.

        $redirect_url = append_sid(generate_board_url() . '/ucp.' . $phpEx, 'i=-booskit-gtawoauth-ucp-gtaw_module&mode=link', true, $this->user->session_id);
        redirect($redirect_url);
    }

    protected function handle_login()
    {
        // Manual token exchange to capture tokens
        $code = $this->request->variable('code', '');

        $token_data = $this->provider->perform_token_exchange($code);
        if (!$token_data || !isset($token_data['access_token'])) {
            trigger_error('LOGIN_ERROR_EXTERNAL_AUTH', E_USER_WARNING);
        }

        $access_token = $token_data['access_token'];
        $refresh_token = isset($token_data['refresh_token']) ? $token_data['refresh_token'] : '';
        $expires_in = isset($token_data['expires_in']) ? (int) $token_data['expires_in'] : 3600;
        $expires_at = time() + $expires_in;

        $user_info = $this->provider->fetch_user_info($access_token);
        if (!$user_info) {
             trigger_error('LOGIN_ERROR_EXTERNAL_AUTH', E_USER_WARNING);
        }

        $user_details = $this->provider->get_user_details($user_info);
        if (!$user_details || !isset($user_details['user_id'])) {
             trigger_error('LOGIN_ERROR_EXTERNAL_AUTH', E_USER_WARNING);
        }

        $external_id = $user_details['user_id'];

        // Check if there is a local user linked to this external ID
        $sql = 'SELECT user_id FROM ' . $this->table_prefix . 'oauth_accounts
                WHERE provider = \'gtaw\' AND oauth_provider_id = \'' . $this->db->sql_escape($external_id) . '\'';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($row) {
            // Found a linked user, log them in
            $user_id = (int) $row['user_id'];

            // Save tokens
            // Remove old tokens
            $sql = 'DELETE FROM ' . $this->table_prefix . 'booskit_oauth_tokens
                WHERE user_id = ' . (int) $user_id . '
                AND provider = \'gtaw\'';
            $this->db->sql_query($sql);

            // Add new tokens
            $sql_ary = [
                'user_id'       => (int) $user_id,
                'provider'      => 'gtaw',
                'access_token'  => (string) $access_token,
                'refresh_token' => (string) $refresh_token,
                'expires_at'    => (int) $expires_at,
            ];
            $sql = 'INSERT INTO ' . $this->table_prefix . 'booskit_oauth_tokens ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);

            // Create session
            $result = $this->user->session_create($user_id, false, true, true);

            if ($result === true) {
                 // Login successful
                 global $phpEx;
                 $redirect = $this->request->variable('redirect', "index.$phpEx");
                 $url = redirect($redirect, true);

                 if (!$url) {
                     $url = generate_board_url() . "/index.$phpEx";
                 }

                 // Force SID in URL to handle cross-site cookie restrictions
                 $url = append_sid($url, false, true, $this->user->session_id);

                 redirect($url);
            } else {
                 trigger_error('LOGIN_ERROR_UNKNOWN', E_USER_WARNING);
            }
        } else {
            // No linked account found.
            // Since registration via OAuth is disabled, we show an error.
            trigger_error('GTAW_NO_LINKED_ACCOUNT', E_USER_WARNING);
        }
    }
}
