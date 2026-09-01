<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\user */
    protected $user;

    /** @var \phpbb\request\request */
    protected $request;

    /** @var \phpbb\controller\helper */
    protected $helper;

    /** @var \booskit\twofactor\service\twofactor_manager */
    protected $manager;

    /** @var \booskit\twofactor\service\backup_code_manager */
    protected $backup_codes;

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\request\request $request,
        \phpbb\controller\helper $helper,
        \booskit\twofactor\service\twofactor_manager $manager,
        \booskit\twofactor\service\backup_code_manager $backup_codes
    ) {
        $this->config = $config;
        $this->template = $template;
        $this->user = $user;
        $this->request = $request;
        $this->helper = $helper;
        $this->manager = $manager;
        $this->backup_codes = $backup_codes;
    }

    public static function getSubscribedEvents()
    {
        return [
            'core.user_setup'                 => 'load_language_on_setup',
            'core.page_header'                => 'handle_page_header',
            'core.adm_page_header'            => 'handle_page_header',
            'core.session_kill_after'         => 'handle_session_kill',
            'core.acp_users_display_overview' => 'acp_users_display_overview',
            'core.acp_users_overview_before'  => 'acp_users_overview_before',
        ];
    }

    public function handle_session_kill($event)
    {
        $user_id = isset($event['user_id']) ? (int)$event['user_id'] : (int)$this->user->data['user_id'];
        $session_id = isset($event['session_id']) ? (string)$event['session_id'] : (string)$this->user->data['session_id'];

        if ($user_id > 0) {
            $this->manager->clear_user_sessions($user_id);
        } elseif (!empty($session_id)) {
            $this->manager->clear_session($session_id);
        }
    }

    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'booskit/twofactor',
            'lang_set' => 'common',
        ];
        $lang_set_ext[] = [
            'ext_name' => 'booskit/twofactor',
            'lang_set' => 'info_acp_twofactor',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }

    public function handle_page_header($event)
    {
        if (!$this->manager->is_globally_enabled()) {
            return;
        }

        $user_id = (int)$this->user->data['user_id'];
        if ($user_id === ANONYMOUS) {
            return;
        }

        // Determine current route/script
        $script_name = strtolower((string)@$this->user->page['page_name']);
        $current_url = strtolower((string)$this->request->server('REQUEST_URI', ''));
        $php_self = strtolower((string)$this->request->server('PHP_SELF', ''));
        $script_filename = strtolower((string)$this->request->server('SCRIPT_NAME', ''));
        $mode = $this->request->variable('mode', '');

        // Allow logout unconditionally and clean up 2FA session records
        if ($mode === 'logout') {
            $this->manager->clear_user_sessions($user_id);
            return;
        }

        // Check if current URL is one of our 2FA routes to prevent redirect loops
        if (
            strpos($current_url, '2fa/verify') !== false ||
            strpos($current_url, '2fa/setup') !== false ||
            strpos($current_url, '2fa/backup-keys') !== false ||
            strpos($current_url, '2fa/dismiss-suggestion') !== false
        ) {
            return;
        }

        // Identify destination area
        $is_ucp = (
            strpos($script_name, 'ucp') !== false ||
            strpos($current_url, 'ucp.php') !== false ||
            strpos($php_self, 'ucp.php') !== false ||
            strpos($script_filename, 'ucp.php') !== false ||
            $this->request->variable('i', '') === 'ucp' ||
            strpos($this->request->variable('i', ''), 'ucp_') === 0
        );

        $is_mcp = (
            strpos($script_name, 'mcp') !== false ||
            strpos($current_url, 'mcp.php') !== false ||
            strpos($php_self, 'mcp.php') !== false ||
            strpos($script_filename, 'mcp.php') !== false ||
            $this->request->variable('i', '') === 'mcp' ||
            strpos($this->request->variable('i', ''), 'mcp_') === 0
        );

        $is_acp = (
            strpos($script_name, 'adm') !== false ||
            strpos($current_url, '/adm/') !== false ||
            strpos($php_self, 'adm/index.php') !== false ||
            strpos($script_filename, 'adm/index.php') !== false
        );

        // Allow UCP 2FA management module itself so users can always setup/manage 2FA
        $i_param = $this->request->variable('i', '');
        if ($is_ucp && strpos($i_param, 'twofactor') !== false) {
            return;
        }

        $is_2fa_enabled = $this->manager->is_user_2fa_enabled($user_id);
        $session_id = $this->user->data['session_id'];

        // If device is remembered for 30 days, auto-verify all modules for this session
        if ($is_2fa_enabled && $this->manager->is_device_remembered($user_id)) {
            if (!$this->manager->is_session_verified($session_id, $user_id, 'login')) {
                $this->manager->mark_session_verified($session_id, $user_id, 'trusted_device', 'all');
            }
            if ($this->manager->is_reset_backup_pending($user_id)) {
                $backup_url = $this->helper->route('booskit_twofactor_backup_keys');
                redirect($backup_url);
                return;
            }
            return;
        }

        // For users who have NOT enabled 2FA yet:
        if (!$is_2fa_enabled) {
            // Check if user belongs to an enforced group
            if ($this->manager->is_user_enforced($user_id)) {
                $setup_url = $this->helper->route('booskit_twofactor_setup');
                redirect($setup_url);
                return;
            }

            // Check if user should receive a 2FA suggestion prompt
            if ($this->manager->is_user_suggested($user_id)) {
                $this->template->assign_vars([
                    'S_SHOW_2FA_SUGGESTION' => true,
                    'U_2FA_SETUP'           => $this->helper->route('booskit_twofactor_setup'),
                    'U_2FA_DISMISS'         => $this->helper->route('booskit_twofactor_dismiss_suggestion', [
                        'redirect' => $this->get_current_url_clean(),
                    ]),
                    'BOOSKIT_2FA_COLOR'     => $this->manager->get_theme_color(),
                    'BOOSKIT_2FA_LOGO_URL'  => $this->manager->get_logo_url(),
                ]);
            }
            return;
        }

        // For users who HAVE 2FA enabled:

        // 1. Check MCP 2FA requirement
        if ($is_mcp && $this->manager->is_2fa_required_for_mcp($user_id)) {
            if (!$this->manager->is_session_verified($session_id, $user_id, 'mcp')) {
                $verify_url = $this->helper->route('booskit_twofactor_verify', [
                    'redirect' => $this->get_current_url_clean(),
                    'module'   => 'mcp',
                ]);
                redirect($verify_url);
                return;
            }
        }

        // 2. Check UCP 2FA requirement
        if ($is_ucp && $this->manager->is_2fa_required_for_ucp($user_id)) {
            if (!$this->manager->is_session_verified($session_id, $user_id, 'ucp')) {
                $verify_url = $this->helper->route('booskit_twofactor_verify', [
                    'redirect' => $this->get_current_url_clean(),
                    'module'   => 'ucp',
                ]);
                redirect($verify_url);
                return;
            }
        }

        // 3. Check ACP 2FA requirement
        if ($is_acp && $this->manager->is_2fa_required_for_acp($user_id)) {
            // Only require 2FA after the administrator has passed the password re-authentication (session_admin == 1)
            if (!empty($this->user->data['session_admin']) && !$this->manager->is_session_verified($session_id, $user_id, 'acp')) {
                $verify_url = $this->helper->route('booskit_twofactor_verify', [
                    'redirect' => $this->get_current_url_clean(),
                    'module'   => 'acp',
                ]);
                redirect($verify_url);
                return;
            }
        }

        // 4. Check standard Login 2FA requirement
        if (!$is_acp) {
            $is_login_verified = $this->manager->is_session_verified($session_id, $user_id, 'login');
            $is_oauth_session = $this->manager->is_session_oauth($session_id);

            if (!$is_login_verified && $this->manager->is_2fa_required_for_login($user_id, $is_oauth_session)) {
                $verify_url = $this->helper->route('booskit_twofactor_verify', [
                    'redirect' => $this->get_current_url_clean(),
                    'module'   => 'login',
                ]);
                redirect($verify_url);
                return;
            }
        }

        // If any pending reset backup keys acknowledgment
        if ($this->manager->is_reset_backup_pending($user_id)) {
            $backup_url = $this->helper->route('booskit_twofactor_backup_keys');
            redirect($backup_url);
            return;
        }
    }

    public function acp_users_display_overview($event)
    {
        $user_row = $event['user_row'];
        $user_id = (int)$user_row['user_id'];

        $this->user->add_lang_ext('booskit/twofactor', 'info_acp_twofactor');
        $this->user->add_lang_ext('booskit/twofactor', 'common');

        $record = $this->manager->get_user_record($user_id);
        $is_enabled = !empty($record) && !empty($record['is_enabled']);
        $is_enforced = $this->manager->is_user_enforced($user_id);
        $remaining_backup = $is_enabled ? $this->backup_codes->get_remaining_count($user_id) : 0;
        $trusted_count = $this->manager->get_trusted_devices_count($user_id);

        $last_login = ($is_enabled && !empty($record['last_login_at'])) ? $this->user->format_date($record['last_login_at']) : $this->user->lang['NEVER'];
        $last_login_ip = ($is_enabled && !empty($record['last_login_ip'])) ? $record['last_login_ip'] : '-';
        $last_login_method = ($is_enabled && !empty($record['last_login_method'])) ? $record['last_login_method'] : '-';

        $this->template->assign_vars([
            'S_BOOSKIT_2FA_USER_ENABLED'   => $is_enabled,
            'S_BOOSKIT_2FA_USER_ENFORCED'  => $is_enforced,
            'BOOSKIT_2FA_LAST_LOGIN'       => $last_login,
            'BOOSKIT_2FA_LAST_IP'          => $last_login_ip,
            'BOOSKIT_2FA_LAST_METHOD'      => $last_login_method,
            'BOOSKIT_2FA_REMAINING_BACKUP' => $remaining_backup,
            'BOOSKIT_2FA_TRUSTED_COUNT'    => $trusted_count,
            'BOOSKIT_2FA_USER_ID'          => $user_id,
        ]);
    }

    public function acp_users_overview_before($event)
    {
        $user_row = $event['user_row'];
        $user_id = (int)$user_row['user_id'];
        $u_action = append_sid(generate_board_url() . '/adm/index.php', 'i=users&amp;mode=overview&amp;u=' . $user_id);

        $this->user->add_lang_ext('booskit/twofactor', 'info_acp_twofactor');
        $this->user->add_lang_ext('booskit/twofactor', 'common');

        // Check which action was submitted
        $action = '';
        if ($this->request->is_set_post('booskit_2fa_action_disable')) {
            $action = 'disable';
        } elseif ($this->request->is_set_post('booskit_2fa_action_reset_backup')) {
            $action = 'reset_backup';
        } elseif ($this->request->is_set_post('booskit_2fa_action_reset_remember')) {
            $action = 'reset_remember';
        } elseif ($this->request->variable('booskit_2fa_confirmed_action', '') !== '') {
            $action = $this->request->variable('booskit_2fa_confirmed_action', '');
        }

        if (empty($action)) {
            return;
        }

        $target_username = isset($user_row['username']) ? $user_row['username'] : $this->manager->get_username_by_id($user_id);

        if ($action === 'disable') {
            if (confirm_box(true)) {
                $this->manager->disable_user_2fa($user_id);
                $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_ADMIN_REMOVED', $user_id, $target_username, [], $this->user->data['user_id']);
                trigger_error($this->user->lang['BOOSKIT_2FA_ADMIN_REMOVED_SUCCESS'] . adm_back_link($u_action));
            } else {
                confirm_box(false, $this->user->lang['BOOSKIT_2FA_ADMIN_CONFIRM_REMOVE'], build_hidden_fields([
                    'u'                            => $user_id,
                    'booskit_2fa_confirmed_action' => 'disable',
                ]));
            }
        } elseif ($action === 'reset_backup') {
            if (confirm_box(true)) {
                $this->manager->reset_backup_keys($user_id);
                $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_ADMIN_RESET_BACKUP', $user_id, $target_username, [], $this->user->data['user_id']);
                trigger_error($this->user->lang['BOOSKIT_2FA_ADMIN_RESET_BACKUP_SUCCESS'] . adm_back_link($u_action));
            } else {
                confirm_box(false, $this->user->lang['BOOSKIT_2FA_ADMIN_CONFIRM_RESET_BACKUP'], build_hidden_fields([
                    'u'                            => $user_id,
                    'booskit_2fa_confirmed_action' => 'reset_backup',
                ]));
            }
        } elseif ($action === 'reset_remember') {
            if (confirm_box(true)) {
                $this->manager->revoke_trusted_devices($user_id);
                $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_ADMIN_RESET_REMEMBER', $user_id, $target_username, [], $this->user->data['user_id']);
                trigger_error($this->user->lang['BOOSKIT_2FA_ADMIN_RESET_REMEMBER_SUCCESS'] . adm_back_link($u_action));
            } else {
                confirm_box(false, $this->user->lang['BOOSKIT_2FA_ADMIN_CONFIRM_RESET_REMEMBER'], build_hidden_fields([
                    'u'                            => $user_id,
                    'booskit_2fa_confirmed_action' => 'reset_remember',
                ]));
            }
        }
    }

    protected function get_current_url_clean()
    {
        $uri = $this->request->server('REQUEST_URI', '');
        if (empty($uri)) {
            return generate_board_url() . '/index.php';
        }

        $uri = htmlspecialchars_decode($uri, ENT_QUOTES);
        $uri = preg_replace('/amp(%3B|;)/i', '', $uri);
        $uri = str_replace(["\r", "\n"], '', $uri);

        $parts = parse_url($uri);
        $path = isset($parts['path']) ? $parts['path'] : '';
        $query = isset($parts['query']) ? $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        if (empty($path)) {
            $path = '/index.php';
        }

        $clean_params = [];
        if (!empty($query)) {
            parse_str($query, $parsed);
            foreach ($parsed as $k => $v) {
                $k_clean = trim($k, '&; ');
                if ($k_clean === 'sid' || empty($k_clean) || $k_clean === 'amp') {
                    continue;
                }
                $clean_params[$k_clean] = $v;
            }
        }

        $rebuilt = http_build_query($clean_params, '', '&');
        return $path . (!empty($rebuilt) ? '?' . $rebuilt : '') . $fragment;
    }
}
