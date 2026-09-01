<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class verify
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\request\request */
    protected $request;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\user */
    protected $user;

    /** @var \phpbb\controller\helper */
    protected $helper;

    /** @var \booskit\twofactor\service\twofactor_manager */
    protected $manager;

    /** @var \booskit\twofactor\service\totp */
    protected $totp;

    /** @var \booskit\twofactor\service\backup_code_manager */
    protected $backup_codes;

    /** @var \phpbb\db\driver\driver_interface|null */
    protected $db;

    /** @var string */
    protected $table_prefix;

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\request\request $request,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\controller\helper $helper,
        \booskit\twofactor\service\twofactor_manager $manager,
        \booskit\twofactor\service\totp $totp,
        \booskit\twofactor\service\backup_code_manager $backup_codes,
        \phpbb\db\driver\driver_interface $db = null,
        $table_prefix = 'phpbb_'
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->helper = $helper;
        $this->manager = $manager;
        $this->totp = $totp;
        $this->backup_codes = $backup_codes;
        $this->db = $db;
        $this->table_prefix = $table_prefix;
    }

    public function handle()
    {
        $this->user->add_lang_ext('booskit/twofactor', 'common');

        $current_user_id = (int)$this->user->data['user_id'];
        $token = $this->request->variable('token', '');

        // ----------------------------------------------------
        // Flow A: Pre-Session 2FA Verification (Anonymous User)
        // ----------------------------------------------------
        if ($current_user_id == ANONYMOUS) {
            if (empty($token)) {
                login_box();
            }

            $pending = $this->manager->get_pending_login($token);
            if (!$pending) {
                // Token invalid or expired
                meta_refresh(3, append_sid(generate_board_url() . '/ucp.php', 'mode=login'));
                trigger_error($this->user->lang['BOOSKIT_2FA_SESSION_EXPIRED'] . '<br /><br />' . sprintf($this->user->lang['RETURN_INDEX'], '<a href="' . append_sid(generate_board_url() . '/index.php') . '">', '</a>'));
            }

            $user_id = (int)$pending['user_id'];
            $username = $this->manager->get_username_by_id($user_id);
            $redirect = !empty($pending['redirect_url']) ? $pending['redirect_url'] : $this->request->variable('redirect', 'index.php');
            $redirect = $this->clean_redirect_url($redirect);
            $error = '';

            if ($this->request->is_set_post('submit')) {
                if (!check_form_key('booskit_2fa_verify')) {
                    $error = $this->user->lang['FORM_INVALID'];
                } else {
                    $auth_mode = $this->request->variable('auth_mode', 'totp');
                    $totp_code = $this->request->variable('totp_code', '');
                    $backup_code = $this->request->variable('backup_code', '');
                    $remember_device = $this->request->variable('remember_device', 0);

                    $record = $this->manager->get_user_record($user_id);
                    $secret = isset($record['secret']) ? $record['secret'] : '';
                    $verified = false;
                    $method = 'totp';
                    $method_name = $this->user->lang['BOOSKIT_2FA_METHOD_TOTP'];

                    if ($auth_mode === 'backup' || (!empty($backup_code) && empty($totp_code))) {
                        $method = 'backup_code';
                        $method_name = $this->user->lang['BOOSKIT_2FA_METHOD_BACKUP'];
                        if ($this->backup_codes->verify_and_consume_code($user_id, $backup_code)) {
                            $verified = true;
                        } else {
                            $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_AUTH_FAILED', $user_id, $username, ['LOGIN', $method_name]);
                            $error = $this->user->lang['BOOSKIT_2FA_INVALID_BACKUP_CODE'];
                        }
                    } else {
                        if (!empty($secret) && $this->totp->verify_code($secret, $totp_code, 2)) {
                            $verified = true;
                        } else {
                            $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_AUTH_FAILED', $user_id, $username, ['LOGIN', $method_name]);
                            $error = $this->user->lang['BOOSKIT_2FA_INVALID_CODE'];
                        }
                    }

                    if ($verified) {
                        // 1. Remember device if opted-in and permitted
                        if ($remember_device && $this->manager->is_remember_device_permitted($user_id)) {
                            $this->manager->remember_device($user_id);
                            $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_DEVICE_REMEMBERED', $user_id, $username, [$this->user->ip]);
                        }

                        // 2. NOW establish phpBB user session
                        $this->user->session_create(
                            $user_id,
                            (bool)$pending['admin'],
                            (bool)$pending['autologin'],
                            (bool)$pending['viewonline']
                        );

                        // 3. Reset failed login attempts in users table if DB is available
                        if ($this->db) {
                            $sql = 'UPDATE ' . $this->table_prefix . 'users SET user_login_attempts = 0 WHERE user_id = ' . $user_id;
                            $this->db->sql_query($sql);
                        }

                        // 4. Mark session verified for login
                        $new_session_id = (string)$this->user->data['session_id'];
                        $this->manager->mark_session_verified($new_session_id, $user_id, $method, 'login');

                        // 5. If this was OAuth login, mark session as OAuth
                        if (!empty($pending['auth_via_oauth'])) {
                            $this->manager->mark_session_oauth($new_session_id, $user_id);
                        }

                        // 6. Delete used pending login token
                        $this->manager->delete_pending_login($token);

                        // 7. Log success
                        $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_AUTH_SUCCESS', $user_id, $username, ['LOGIN', $method_name]);

                        // 8. If reset backup keys is pending, redirect to backup keys screen
                        if ($this->manager->is_reset_backup_pending($user_id)) {
                            return new RedirectResponse($this->helper->route('booskit_twofactor_backup_keys'));
                        }

                        // 9. Redirect to target destination
                        $target = $this->get_safe_redirect_url($redirect);
                        return new RedirectResponse($target);
                    }
                }
            }

            add_form_key('booskit_2fa_verify');

            $this->template->assign_vars([
                'ERROR'                 => $error,
                'REDIRECT'              => $redirect,
                'MODULE'                => 'login',
                'TOKEN'                 => $token,
                'U_ACTION'              => $this->helper->route('booskit_twofactor_verify', ['token' => $token]),
                'U_LOGOUT'              => append_sid(generate_board_url() . '/ucp.php', 'mode=login'),
                'USERNAME'              => $username,
                'REMAINING_BACKUP'      => $this->backup_codes->get_remaining_count($user_id),
                'S_CAN_REMEMBER_DEVICE' => $this->manager->is_remember_device_permitted($user_id),
                'BOOSKIT_2FA_COLOR'     => $this->manager->get_theme_color(),
                'BOOSKIT_2FA_LOGO_URL'  => $this->manager->get_logo_url(),
            ]);

            return $this->helper->render('twofactor_verify.html', $this->user->lang['BOOSKIT_2FA_VERIFY_TITLE']);
        }

        // ----------------------------------------------------
        // Flow B: Post-Session Verification (Logged-in User: ACP/UCP/MCP)
        // ----------------------------------------------------
        $user_id = $current_user_id;

        // If 2FA not enabled on user account, check if enforced
        if (!$this->manager->is_user_2fa_enabled($user_id)) {
            if ($this->manager->is_user_enforced($user_id)) {
                return new RedirectResponse($this->helper->route('booskit_twofactor_setup'));
            }
            return new RedirectResponse(append_sid(generate_board_url() . '/index.php', false, false));
        }

        $module = $this->request->variable('module', 'login');
        if (!in_array($module, ['login', 'ucp', 'mcp', 'acp'])) {
            $module = 'login';
        }

        // Check if session is already verified for this module (or device remembered)
        if ($this->manager->is_device_remembered($user_id) || $this->manager->is_session_verified($this->user->data['session_id'], $user_id, $module)) {
            if ($this->manager->is_reset_backup_pending($user_id)) {
                return new RedirectResponse($this->helper->route('booskit_twofactor_backup_keys'));
            }
            $redirect_url = $this->get_safe_redirect_url();
            return new RedirectResponse($redirect_url);
        }

        $error = '';
        $redirect = $this->clean_redirect_url($this->request->variable('redirect', ''));

        if ($this->request->is_set_post('submit')) {
            if (!check_form_key('booskit_2fa_verify')) {
                $error = $this->user->lang['FORM_INVALID'];
            } else {
                $auth_mode = $this->request->variable('auth_mode', 'totp');
                $totp_code = $this->request->variable('totp_code', '');
                $backup_code = $this->request->variable('backup_code', '');

                $record = $this->manager->get_user_record($user_id);
                $secret = isset($record['secret']) ? $record['secret'] : '';
                $remember_device = $this->request->variable('remember_device', 0);
                $module_name = strtoupper($module);
                $username = $this->user->data['username'];

                if ($auth_mode === 'backup' || (!empty($backup_code) && empty($totp_code))) {
                    $method_name = $this->user->lang['BOOSKIT_2FA_METHOD_BACKUP'];

                    if ($this->backup_codes->verify_and_consume_code($user_id, $backup_code)) {
                        $this->manager->mark_session_verified($this->user->data['session_id'], $user_id, 'backup_code', $module);
                        $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_AUTH_SUCCESS', $user_id, $username, [$module_name, $method_name]);

                        if ($remember_device && $this->manager->is_remember_device_permitted($user_id)) {
                            $this->manager->remember_device($user_id);
                            $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_DEVICE_REMEMBERED', $user_id, $username, [$this->user->ip]);
                        }

                        if ($this->manager->is_reset_backup_pending($user_id)) {
                            return new RedirectResponse($this->helper->route('booskit_twofactor_backup_keys'));
                        }

                        $target = $this->get_safe_redirect_url($redirect);
                        return new RedirectResponse($target);
                    } else {
                        $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_AUTH_FAILED', $user_id, $username, [$module_name, $method_name]);
                        $error = $this->user->lang['BOOSKIT_2FA_INVALID_BACKUP_CODE'];
                    }
                } else {
                    $method_name = $this->user->lang['BOOSKIT_2FA_METHOD_TOTP'];

                    if (!empty($secret) && $this->totp->verify_code($secret, $totp_code, 2)) {
                        $this->manager->mark_session_verified($this->user->data['session_id'], $user_id, 'totp', $module);
                        $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_AUTH_SUCCESS', $user_id, $username, [$module_name, $method_name]);

                        if ($remember_device && $this->manager->is_remember_device_permitted($user_id)) {
                            $this->manager->remember_device($user_id);
                            $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_DEVICE_REMEMBERED', $user_id, $username, [$this->user->ip]);
                        }

                        if ($this->manager->is_reset_backup_pending($user_id)) {
                            return new RedirectResponse($this->helper->route('booskit_twofactor_backup_keys'));
                        }

                        $target = $this->get_safe_redirect_url($redirect);
                        return new RedirectResponse($target);
                    } else {
                        $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_AUTH_FAILED', $user_id, $username, [$module_name, $method_name]);
                        $error = $this->user->lang['BOOSKIT_2FA_INVALID_CODE'];
                    }
                }
            }
        }

        add_form_key('booskit_2fa_verify');

        $this->template->assign_vars([
            'ERROR'                 => $error,
            'REDIRECT'              => $redirect,
            'MODULE'                => $module,
            'TOKEN'                 => '',
            'U_ACTION'              => $this->helper->route('booskit_twofactor_verify', ['module' => $module]),
            'U_LOGOUT'              => append_sid(generate_board_url() . '/ucp.php', 'mode=logout&amp;sid=' . $this->user->session_id),
            'USERNAME'              => $this->user->data['username'],
            'REMAINING_BACKUP'      => $this->backup_codes->get_remaining_count($user_id),
            'S_CAN_REMEMBER_DEVICE' => $this->manager->is_remember_device_permitted($user_id),
            'BOOSKIT_2FA_COLOR'     => $this->manager->get_theme_color(),
            'BOOSKIT_2FA_LOGO_URL'  => $this->manager->get_logo_url(),
        ]);

        return $this->helper->render('twofactor_verify.html', $this->user->lang['BOOSKIT_2FA_VERIFY_TITLE']);
    }

    protected function get_safe_redirect_url($redirect = '')
    {
        if (empty($redirect)) {
            $redirect = $this->request->variable('redirect', '');
        }
        if (!empty($redirect)) {
            $redirect_clean = $this->clean_redirect_url($redirect);
            // Avoid looping to verify/setup route
            if (strpos($redirect_clean, '2fa/verify') === false && strpos($redirect_clean, '2fa/setup') === false) {
                if (strpos($redirect_clean, 'http://') === 0 || strpos($redirect_clean, 'https://') === 0) {
                    $url = append_sid($redirect_clean, false, false);
                    return str_replace('&amp;', '&', $url);
                }

                $board_url = rtrim(generate_board_url(), '/');
                $parsed_board = parse_url($board_url);
                $scheme = isset($parsed_board['scheme']) ? $parsed_board['scheme'] : 'http';
                $host = isset($parsed_board['host']) ? $parsed_board['host'] : 'localhost';
                $port = isset($parsed_board['port']) ? ':' . $parsed_board['port'] : '';
                $board_origin = $scheme . '://' . $host . $port;
                $board_path = isset($parsed_board['path']) ? rtrim($parsed_board['path'], '/') : '';

                $redirect_clean = '/' . ltrim($redirect_clean, '/');

                if (!empty($board_path) && strpos($redirect_clean, $board_path . '/') === 0) {
                    $target = $board_origin . $redirect_clean;
                } else {
                    $target = $board_url . $redirect_clean;
                }

                $url = append_sid($target, false, false);
                return str_replace('&amp;', '&', $url);
            }
        }
        $url = append_sid(generate_board_url() . '/index.php', false, false);
        return str_replace('&amp;', '&', $url);
    }

    protected function clean_redirect_url($url)
    {
        if (empty($url)) {
            return '/index.php';
        }

        $url = htmlspecialchars_decode($url, ENT_QUOTES);
        $url = preg_replace('/amp(%3B|;)/i', '', $url);
        $url = str_replace(["\r", "\n"], '', $url);

        $parts = parse_url($url);
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
