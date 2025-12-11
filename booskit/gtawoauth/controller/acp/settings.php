<?php
namespace booskit\gtawoauth\controller\acp;

class settings
{
    protected $config;
    protected $request;
    protected $template;
    protected $user;
    protected $log;
    protected $u_action;

    public function __construct(\phpbb\config\config $config, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\log\log $log)
    {
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->log = $log;
    }

    public function handle($id, $mode, $u_action)
    {
        // Setup the action URL
        $this->u_action = $u_action;

        if ($this->request->is_set_post('submit')) {
            if (!check_form_key('acp_gtaw_oauth')) {
                trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
            }

            $client_id = $this->request->variable('gtaw_client_id', '');
            $client_secret = $this->request->variable('gtaw_client_secret', '');
            $base_url = $this->request->variable('gtaw_base_url', '');
            $login_enable = $this->request->variable('gtaw_login_enable', 0);

            // Ensure base URL doesn't have trailing slash
            $base_url = rtrim($base_url, '/');

            $this->config->set('auth_oauth_gtaw_key', $client_id);
            $this->config->set('auth_oauth_gtaw_secret', $client_secret);
            $this->config->set('auth_oauth_gtaw_base_url', $base_url);
            $this->config->set('auth_oauth_gtaw_login_enable', $login_enable);

            $this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_GTAW_OAUTH');
            trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
        }

        add_form_key('acp_gtaw_oauth');

        $this->template->assign_vars(array(
            'GTAW_CLIENT_ID'     => $this->config['auth_oauth_gtaw_key'],
            'GTAW_CLIENT_SECRET' => $this->config['auth_oauth_gtaw_secret'],
            'GTAW_BASE_URL'      => isset($this->config['auth_oauth_gtaw_base_url']) ? $this->config['auth_oauth_gtaw_base_url'] : '',
            'GTAW_LOGIN_ENABLE'  => isset($this->config['auth_oauth_gtaw_login_enable']) ? $this->config['auth_oauth_gtaw_login_enable'] : 0,
            'U_ACTION'           => $this->u_action,
        ));
    }
}
