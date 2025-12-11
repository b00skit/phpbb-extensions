<?php
namespace booskit\gtawoauth\controller\ucp;

class link
{
    protected $config;
    protected $request;
    protected $template;
    protected $user;
    protected $provider;
    protected $db;
    protected $helper;
    protected $language;

    public function __construct($config, $request, $template, $user, $provider, $db, $helper, $language)
    {
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->provider = $provider;
        $this->db = $db;
        $this->helper = $helper;
        $this->language = $language;
    }

    public function handle($id, $mode, $u_action)
    {
        $this->language->add_lang('common', 'booskit/gtawoauth');

        // Construct the redirect URI.
        // We use a clean URL without session ID for the OAuth redirect.
        global $phpEx;
        $redirect_uri = generate_board_url() . '/ucp.' . $phpEx . '?i=' . $id . '&mode=' . $mode;

        if ($this->request->is_set('code')) {
            // Verify State for CSRF protection
            $state = $this->request->variable('state', '');
            if (!check_link_hash($state, 'gtaw_oauth_link')) {
                trigger_error('FORM_INVALID');
            }

            $this->handle_callback($redirect_uri);
            // After handling callback, we should probably redirect to the clean UCP page to avoid re-submitting code
            redirect($u_action);
            return;
        }

        if ($this->request->is_set('unlink')) {
             if (check_link_hash($this->request->variable('hash', ''), 'unlink_gtaw')) {
                 $this->unlink_account();
             } else {
                 trigger_error('FORM_INVALID');
             }
             // Redirect back to UCP to refresh state
             redirect($u_action);
        }

        $this->show_status($u_action, $redirect_uri);
    }

    protected function handle_callback($redirect_uri)
    {
        $code = $this->request->variable('code', '');

        $this->provider->set_redirect_uri($redirect_uri);

        $token = $this->provider->perform_token_exchange($code);
        if (!$token) {
            trigger_error($this->language->lang('GTAW_LINK_FAILED_TOKEN'), E_USER_WARNING);
        }

        $user_info = $this->provider->fetch_user_info($token);
        if (!$user_info) {
             trigger_error($this->language->lang('GTAW_LINK_FAILED_USER'), E_USER_WARNING);
        }

        $user_details = $this->provider->get_user_details($user_info);
        if (!$user_details || !isset($user_details['user_id'])) {
             trigger_error($this->language->lang('GTAW_LINK_FAILED_USER'), E_USER_WARNING);
        }

        $external_id = $user_details['user_id'];

        // Check if this external ID is already linked
        $sql = 'SELECT user_id FROM ' . $this->db->get_table_prefix() . 'oauth_accounts
                WHERE provider = \'gtaw\' AND oauth_provider_id = \'' . $this->db->sql_escape($external_id) . '\'';
        $result = $this->db->sql_query($sql);
        $existing = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($existing) {
            if ($existing['user_id'] == $this->user->data['user_id']) {
                 // Already linked to this account, do nothing or show message
                 // trigger_error($this->language->lang('GTAW_ALREADY_LINKED'), E_USER_NOTICE);
            } else {
                 // Linked to another account
                 trigger_error($this->language->lang('GTAW_LINKED_TO_OTHER'), E_USER_WARNING);
            }
        } else {
            // Check if user is already linked to ANY gtaw account (limit one link per user?)
            // Usually one user can have one link to a specific provider.
            $sql = 'SELECT oauth_provider_id FROM ' . $this->db->get_table_prefix() . 'oauth_accounts
                WHERE user_id = ' . (int) $this->user->data['user_id'] . '
                AND provider = \'gtaw\'';
            $result = $this->db->sql_query($sql);
            $existing_link = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if ($existing_link) {
                 // User already linked to a GTAW account. Should we overwrite? Or ask to unlink first?
                 // Let's assume we replace it or error.
                 // "Link your GTA:W account" implies one.
                 // Let's delete old one and insert new one to be safe/easy.
                 $sql = 'DELETE FROM ' . $this->db->get_table_prefix() . 'oauth_accounts
                    WHERE user_id = ' . (int) $this->user->data['user_id'] . '
                    AND provider = \'gtaw\'';
                 $this->db->sql_query($sql);
            }

            // Link it
            $sql_ary = [
                'user_id' => (int) $this->user->data['user_id'],
                'provider' => 'gtaw',
                'oauth_provider_id' => (string) $external_id,
            ];
            $sql = 'INSERT INTO ' . $this->db->get_table_prefix() . 'oauth_accounts ' . $this->db->sql_build_array('INSERT', $sql_ary);
            $this->db->sql_query($sql);

            // Success message handled by redirect and displaying status?
            // Or we can add a log or success notice.
            // Since we redirect immediately after handle_callback, we can't show a message unless we add it to sessions flash message (if phpBB has that, usually meta_refresh).
            // But just redirecting back to UCP will show the new state (Linked).
        }
    }

    protected function unlink_account()
    {
        $sql = 'DELETE FROM ' . $this->db->get_table_prefix() . 'oauth_accounts
                WHERE user_id = ' . (int) $this->user->data['user_id'] . '
                AND provider = \'gtaw\'';
        $this->db->sql_query($sql);
    }

    protected function show_status($u_action, $redirect_uri)
    {
        // Check if linked
        $sql = 'SELECT oauth_provider_id FROM ' . $this->db->get_table_prefix() . 'oauth_accounts
                WHERE user_id = ' . (int) $this->user->data['user_id'] . '
                AND provider = \'gtaw\'';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($row) {
            $this->template->assign_vars([
                'GTAW_IS_LINKED' => true,
                'GTAW_EXTERNAL_ID' => $row['oauth_provider_id'],
                'U_UNLINK' => $u_action . '&unlink=1&hash=' . generate_link_hash('unlink_gtaw'),
            ]);
        } else {
            // Generate Link URL
            $this->provider->set_redirect_uri($redirect_uri);

            $url = $this->provider->get_auth_endpoint();
            $params = [
                'response_type' => 'code',
                'client_id' => $this->config['auth_oauth_gtaw_key'],
                'redirect_uri' => $this->provider->get_redirect_uri(),
                'state' => generate_link_hash('gtaw_oauth_link'),
            ];

            $link_url = $url . '?' . http_build_query($params);

            $this->template->assign_vars([
                'GTAW_IS_LINKED' => false,
                'U_LINK' => $link_url,
            ]);
        }
    }
}
