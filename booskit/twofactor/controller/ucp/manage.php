<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\controller\ucp;

class manage
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\request\request */
    protected $request;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\user */
    protected $user;

    /** @var \phpbb\passwords\manager */
    protected $passwords_manager;

    /** @var \booskit\twofactor\service\twofactor_manager */
    protected $manager;

    /** @var \booskit\twofactor\service\totp */
    protected $totp;

    /** @var \booskit\twofactor\service\qr_code */
    protected $qr_code;

    /** @var \booskit\twofactor\service\backup_code_manager */
    protected $backup_codes;

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\request\request $request,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\passwords\manager $passwords_manager,
        \booskit\twofactor\service\twofactor_manager $manager,
        \booskit\twofactor\service\totp $totp,
        \booskit\twofactor\service\qr_code $qr_code,
        \booskit\twofactor\service\backup_code_manager $backup_codes
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->passwords_manager = $passwords_manager;
        $this->manager = $manager;
        $this->totp = $totp;
        $this->qr_code = $qr_code;
        $this->backup_codes = $backup_codes;
    }

    public function handle($id, $mode, $u_action)
    {
        $this->user->add_lang_ext('booskit/twofactor', 'common');

        $user_id = (int)$this->user->data['user_id'];
        $is_enabled = $this->manager->is_user_2fa_enabled($user_id);
        $record = $this->manager->get_user_record($user_id);

        $error = '';
        $success = '';
        $plain_backup_codes = [];
        $show_backup_modal = false;

        $session_key = 'booskit_2fa_ucp_pending_' . $user_id;

        // 1. Handle Setup Submission (when not enabled)
        if (!$is_enabled && $this->request->is_set_post('submit_setup')) {
            if (!check_form_key('ucp_booskit_2fa')) {
                $error = $this->user->lang['FORM_INVALID'];
            } else {
                $pending_secret = $this->manager->get_user_secret($this->user->data['session_id'], $user_id);
                $submitted_code = $this->request->variable('code', '');
                $username = $this->user->data['username'];

                if (!empty($pending_secret) && $this->totp->verify_code($pending_secret, $submitted_code, 2)) {
                    $this->manager->enable_user_2fa($user_id, $pending_secret);
                    $plain_backup_codes = $this->backup_codes->generate_codes($user_id, 10);
                    $this->manager->mark_session_verified($this->user->data['session_id'], $user_id, 'totp', 'all');
                    $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_SETUP_ACTIVATED', $user_id, $username);

                    $is_enabled = true;
                    $record = $this->manager->get_user_record($user_id);
                    $show_backup_modal = true;
                    $success = $this->user->lang['BOOSKIT_2FA_SETUP_SUCCESS'];
                } else {
                    $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_SETUP_FAILED', $user_id, $username);
                    $error = $this->user->lang['BOOSKIT_2FA_INVALID_CODE'];
                }
            }
        }

        // 2. Handle Disable 2FA
        if ($is_enabled && $this->request->is_set_post('submit_disable')) {
            if (!check_form_key('ucp_booskit_2fa')) {
                $error = $this->user->lang['FORM_INVALID'];
            } else {
                $input = $this->request->variable('confirm_password', '', true);
                if (empty($input)) {
                    $input = $this->request->variable('confirm_code', '');
                }
                $input = trim($input);
                $username = $this->user->data['username'];

                $secret = isset($record['secret']) ? $record['secret'] : '';
                $clean_code = str_replace(' ', '', $input);
                $valid = false;

                // Check 6-digit TOTP code
                if (!empty($secret) && preg_match('/^\d{6}$/', $clean_code) && $this->totp->verify_code($secret, $clean_code, 2)) {
                    $valid = true;
                }
                // Check backup key
                elseif ($this->backup_codes->verify_and_consume_code($user_id, $input)) {
                    $valid = true;
                }
                // Check forum password
                elseif (!empty($input) && $this->passwords_manager->check($input, $this->user->data['user_password'])) {
                    $valid = true;
                }

                if ($valid) {
                    $this->manager->disable_user_2fa($user_id);
                    $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_USER_DISABLED', $user_id, $username);
                    $is_enabled = false;
                    $record = null;
                    $success = $this->user->lang['BOOSKIT_2FA_DISABLED_SUCCESS'];
                } else {
                    $error = $this->user->lang['BOOSKIT_2FA_CONFIRM_FAILED'];
                }
            }
        }

        // 3. Handle Regenerate Backup Codes
        if ($is_enabled && $this->request->is_set_post('submit_regenerate_backup')) {
            if (!check_form_key('ucp_booskit_2fa')) {
                $error = $this->user->lang['FORM_INVALID'];
            } else {
                $input = $this->request->variable('confirm_password', '', true);
                if (empty($input)) {
                    $input = $this->request->variable('confirm_code', '');
                }
                $input = trim($input);
                $username = $this->user->data['username'];

                $secret = isset($record['secret']) ? $record['secret'] : '';
                $clean_code = str_replace(' ', '', $input);
                $valid = false;

                // Check 6-digit TOTP code
                if (!empty($secret) && preg_match('/^\d{6}$/', $clean_code) && $this->totp->verify_code($secret, $clean_code, 2)) {
                    $valid = true;
                }
                // Check backup key
                elseif ($this->backup_codes->verify_and_consume_code($user_id, $input)) {
                    $valid = true;
                }
                // Check forum password
                elseif (!empty($input) && $this->passwords_manager->check($input, $this->user->data['user_password'])) {
                    $valid = true;
                }

                if ($valid) {
                    $plain_backup_codes = $this->backup_codes->generate_codes($user_id, 10);
                    $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_BACKUP_REGENERATED', $user_id, $username);
                    $show_backup_modal = true;
                    $success = $this->user->lang['BOOSKIT_2FA_REGEN_BACKUP_SUCCESS'];
                } else {
                    $error = $this->user->lang['BOOSKIT_2FA_CONFIRM_FAILED'];
                }
            }
        }

        // 4. Handle Revoke Remembered Devices
        if ($is_enabled && $this->request->is_set_post('submit_revoke_devices')) {
            if (!check_form_key('ucp_booskit_2fa')) {
                $error = $this->user->lang['FORM_INVALID'];
            } else {
                $username = $this->user->data['username'];
                $this->manager->revoke_trusted_devices($user_id);
                $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_DEVICES_REVOKED', $user_id, $username);
                $success = $this->user->lang['BOOSKIT_2FA_REVOKE_DEVICES_SUCCESS'];
            }
        }

        add_form_key('ucp_booskit_2fa');

        $pending_secret = '';
        $qr_svg_data_uri = '';
        $formatted_secret = '';

        if (!$is_enabled) {
            $pending_secret = $this->manager->get_or_create_pending_secret($this->user->data['session_id'], $user_id);

            $issuer = $this->manager->get_issuer_name();
            $account_name = $this->user->data['username'];
            $otpauth_uri = $this->totp->get_provisioning_uri($pending_secret, $account_name, $issuer);
            $qr_svg_data_uri = $this->qr_code->generate_data_uri($otpauth_uri, 180);
            $formatted_secret = $this->totp->format_secret_for_display($pending_secret);
        }

        $remaining_backup_count = $is_enabled ? $this->backup_codes->get_remaining_count($user_id) : 0;
        $enabled_date = ($is_enabled && !empty($record['enabled_at'])) ? $this->user->format_date($record['enabled_at']) : '';
        $last_login_date = ($is_enabled && !empty($record['last_login_at'])) ? $this->user->format_date($record['last_login_at']) : $this->user->lang['NEVER'];
        $last_login_method = ($is_enabled && !empty($record['last_login_method'])) ? $record['last_login_method'] : '';

        $is_enforced = $this->manager->is_user_enforced($user_id);

        $this->template->assign_vars([
            'S_2FA_ENABLED'            => $is_enabled,
            'S_ENFORCED'               => $is_enforced,
            'ERROR'                    => $error,
            'SUCCESS'                  => $success,
            'U_ACTION'                 => $u_action,
            'SECRET_KEY'               => $pending_secret,
            'SECRET_KEY_FORMATTED'     => $formatted_secret,
            'QR_DATA_URI'              => $qr_svg_data_uri,
            'REMAINING_BACKUP_COUNT'   => $remaining_backup_count,
            'ENABLED_DATE'             => $enabled_date,
            'LAST_LOGIN_DATE'          => $last_login_date,
            'LAST_LOGIN_METHOD'        => $last_login_method,
            'SHOW_BACKUP_MODAL'        => $show_backup_modal,
            'TRUSTED_DEVICES_COUNT'    => $this->manager->get_trusted_devices_count($user_id),
            'S_CAN_REMEMBER_DEVICE'    => $this->manager->is_remember_device_permitted($user_id),
            'BOOSKIT_2FA_COLOR'        => $this->manager->get_theme_color(),
            'BOOSKIT_2FA_LOGO_URL'     => $this->manager->get_logo_url(),
        ]);

        if (!empty($plain_backup_codes)) {
            foreach ($plain_backup_codes as $bcode) {
                $this->template->assign_block_vars('backup_keys', [
                    'KEY' => $bcode,
                ]);
            }
            $this->template->assign_vars([
                'BACKUP_KEYS_TEXT' => implode("\n", $plain_backup_codes),
            ]);
        }
    }
}
