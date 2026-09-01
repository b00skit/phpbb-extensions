<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\controller\acp;

class settings
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\request\request */
    protected $request;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\user */
    protected $user;

    /** @var \phpbb\log\log */
    protected $log;

    /** @var \booskit\twofactor\service\twofactor_manager */
    protected $manager;

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\request\request $request,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\log\log $log,
        \booskit\twofactor\service\twofactor_manager $manager
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->log = $log;
        $this->manager = $manager;
    }

    public function handle($u_action)
    {
        $this->user->add_lang_ext('booskit/twofactor', 'info_acp_twofactor');
        $this->user->add_lang_ext('booskit/twofactor', 'common');

        if ($this->request->is_set_post('submit')) {
            if (!check_form_key('acp_booskit_twofactor')) {
                trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($u_action), E_USER_WARNING);
            }

            $enabled = $this->request->variable('booskit_2fa_enabled', 0);
            $issuer = $this->request->variable('booskit_2fa_issuer', '', true);
            $color = $this->request->variable('booskit_2fa_color', '#2563eb');
            $logo_url = $this->request->variable('booskit_2fa_logo_url', '', true);
            $shared_session = $this->request->variable('booskit_2fa_shared_session', 0);

            $groups_suggest  = $this->request->variable('booskit_2fa_groups_suggest', [0]);
            $groups_enforce  = $this->request->variable('booskit_2fa_groups_enforce', [0]);
            $groups_remember = $this->request->variable('booskit_2fa_groups_remember', [0]);
            $groups_ucp      = $this->request->variable('booskit_2fa_groups_ucp', [0]);
            $groups_mcp      = $this->request->variable('booskit_2fa_groups_mcp', [0]);
            $groups_acp      = $this->request->variable('booskit_2fa_groups_acp', [0]);
            $groups_oauth    = $this->request->variable('booskit_2fa_groups_oauth', [0]);

            $ucp_ignore_remember = $this->request->variable('booskit_2fa_ucp_ignore_remember', 0);
            $mcp_ignore_remember = $this->request->variable('booskit_2fa_mcp_ignore_remember', 0);
            $acp_ignore_remember = $this->request->variable('booskit_2fa_acp_ignore_remember', 0);

            $this->config->set('booskit_2fa_enabled', $enabled);
            $this->config->set('booskit_2fa_issuer', $issuer);
            $this->config->set('booskit_2fa_color', $color);
            $this->config->set('booskit_2fa_logo_url', $logo_url);
            $this->config->set('booskit_2fa_shared_session', $shared_session);
            $this->config->set('booskit_2fa_groups_suggest', implode(',', array_filter($groups_suggest)));
            $this->config->set('booskit_2fa_groups_enforce', implode(',', array_filter($groups_enforce)));
            $this->config->set('booskit_2fa_groups_remember', implode(',', array_filter($groups_remember)));
            $this->config->set('booskit_2fa_groups_ucp', implode(',', array_filter($groups_ucp)));
            $this->config->set('booskit_2fa_groups_mcp', implode(',', array_filter($groups_mcp)));
            $this->config->set('booskit_2fa_groups_acp', implode(',', array_filter($groups_acp)));
            $this->config->set('booskit_2fa_groups_oauth', implode(',', array_filter($groups_oauth)));
            $this->config->set('booskit_2fa_ucp_ignore_remember', $ucp_ignore_remember);
            $this->config->set('booskit_2fa_mcp_ignore_remember', $mcp_ignore_remember);
            $this->config->set('booskit_2fa_acp_ignore_remember', $acp_ignore_remember);

            $this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_2FA_CONFIG_UPDATED');
            trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
        }

        add_form_key('acp_booskit_twofactor');

        $groups = $this->manager->get_all_groups();

        $selected_suggest  = array_map('intval', explode(',', (string)@$this->config['booskit_2fa_groups_suggest']));
        $selected_enforce  = array_map('intval', explode(',', (string)@$this->config['booskit_2fa_groups_enforce']));
        $selected_remember = array_map('intval', explode(',', (string)@$this->config['booskit_2fa_groups_remember']));
        $selected_ucp      = array_map('intval', explode(',', (string)@$this->config['booskit_2fa_groups_ucp']));
        $selected_mcp      = array_map('intval', explode(',', (string)@$this->config['booskit_2fa_groups_mcp']));
        $selected_acp      = array_map('intval', explode(',', (string)@$this->config['booskit_2fa_groups_acp']));
        $selected_oauth    = array_map('intval', explode(',', (string)@$this->config['booskit_2fa_groups_oauth']));

        foreach ($groups as $group) {
            $gid = $group['id'];
            $this->template->assign_block_vars('group_options', [
                'ID'             => $gid,
                'NAME'           => $group['name'],
                'S_SUGGEST'      => in_array($gid, $selected_suggest),
                'S_ENFORCE'      => in_array($gid, $selected_enforce),
                'S_REMEMBER'     => in_array($gid, $selected_remember),
                'S_UCP'          => in_array($gid, $selected_ucp),
                'S_MCP'          => in_array($gid, $selected_mcp),
                'S_ACP'          => in_array($gid, $selected_acp),
                'S_OAUTH'        => in_array($gid, $selected_oauth),
            ]);
        }

        $is_gtawoauth_active = $this->manager->is_gtawoauth_enabled();

        $this->template->assign_vars([
            'S_2FA_ENABLED'           => !empty($this->config['booskit_2fa_enabled']),
            'ISSUER_NAME'             => isset($this->config['booskit_2fa_issuer']) ? $this->config['booskit_2fa_issuer'] : '',
            'BOOSKIT_2FA_COLOR'       => isset($this->config['booskit_2fa_color']) ? $this->config['booskit_2fa_color'] : '#2563eb',
            'BOOSKIT_2FA_LOGO_URL'    => isset($this->config['booskit_2fa_logo_url']) ? $this->config['booskit_2fa_logo_url'] : '',
            'S_SHARED_SESSION'        => !empty($this->config['booskit_2fa_shared_session']),
            'S_UCP_IGNORE_REMEMBER'   => !empty($this->config['booskit_2fa_ucp_ignore_remember']),
            'S_MCP_IGNORE_REMEMBER'   => !empty($this->config['booskit_2fa_mcp_ignore_remember']),
            'S_ACP_IGNORE_REMEMBER'   => !empty($this->config['booskit_2fa_acp_ignore_remember']),
            'S_GTAW_OAUTH_ACTIVE'     => $is_gtawoauth_active,
            'U_ACTION'                => $u_action,
        ]);
    }
}
