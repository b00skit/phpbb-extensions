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
    protected $table_prefix;

    public function __construct($config, $request, $template, $user, $provider, $db, $helper, $language, $table_prefix)
    {
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->provider = $provider;
        $this->db = $db;
        $this->helper = $helper;
        $this->language = $language;
        $this->table_prefix = $table_prefix;
    }

    public function handle($id, $mode, $u_action)
    {
        $this->language->add_lang('common', 'booskit/gtawoauth');

        // We don't rely on local redirect URI anymore for the callback logic
        // but we might need it for constructing some URLs?
        // Actually, the auth flow now redirects to the unified callback, which then redirects back here.
        // So we just need to render the status.

        // Note: The callback controller handles the code exchange and DB updates.
        // After that, it redirects back to this page (u_action).

        if ($this->request->is_set('unlink')) {
             if (check_link_hash($this->request->variable('hash', ''), 'unlink_gtaw')) {
                 $this->unlink_account();
             } else {
                 trigger_error('FORM_INVALID');
             }
             // Redirect back to UCP to refresh state
             redirect($u_action);
        }

        $this->show_status($u_action);
    }

    protected function unlink_account()
    {
        $sql = 'DELETE FROM ' . $this->table_prefix . 'oauth_accounts
                WHERE user_id = ' . (int) $this->user->data['user_id'] . '
                AND provider = \'gtaw\'';
        $this->db->sql_query($sql);
    }

    protected function show_status($u_action)
    {
        // Check if linked
        $sql = 'SELECT oauth_provider_id FROM ' . $this->table_prefix . 'oauth_accounts
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
            // We use the default redirect URI from the provider (which is now the unified callback)

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
