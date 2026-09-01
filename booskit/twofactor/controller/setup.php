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

class setup
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

    /** @var \booskit\twofactor\service\qr_code */
    protected $qr_code;

    /** @var \booskit\twofactor\service\backup_code_manager */
    protected $backup_codes;

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\request\request $request,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\controller\helper $helper,
        \booskit\twofactor\service\twofactor_manager $manager,
        \booskit\twofactor\service\totp $totp,
        \booskit\twofactor\service\qr_code $qr_code,
        \booskit\twofactor\service\backup_code_manager $backup_codes
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->helper = $helper;
        $this->manager = $manager;
        $this->totp = $totp;
        $this->qr_code = $qr_code;
        $this->backup_codes = $backup_codes;
    }

    public function handle()
    {
        $this->user->add_lang_ext('booskit/twofactor', 'common');

        $user_id = (int)$this->user->data['user_id'];
        if ($user_id == ANONYMOUS) {
            login_box();
        }

        // If already enabled and session verified, redirect
        if ($this->manager->is_user_2fa_enabled($user_id) && $this->manager->is_session_verified($this->user->data['session_id'], $user_id)) {
            $url = append_sid(generate_board_url() . '/index.php', false, false);
            return new RedirectResponse(str_replace('&amp;', '&', $url));
        }

        $error = '';
        $step = 'wizard'; // 'wizard' or 'backup_keys'
        $plain_backup_codes = [];

        // Retrieve or create persistent pending secret bound to session
        $pending_secret = $this->manager->get_or_create_pending_secret($this->user->data['session_id'], $user_id);

        if ($this->request->is_set_post('submit_verify')) {
            if (!check_form_key('booskit_2fa_setup')) {
                $error = $this->user->lang['FORM_INVALID'];
            } else {
                $submitted_code = $this->request->variable('code', '');
                $username = $this->user->data['username'];
                if (!empty($pending_secret) && $this->totp->verify_code($pending_secret, $submitted_code, 2)) {
                    // Activate 2FA
                    $this->manager->enable_user_2fa($user_id, $pending_secret);

                    // Generate 10 backup codes
                    $plain_backup_codes = $this->backup_codes->generate_codes($user_id, 10);

                    // Mark session verified for all modules
                    $this->manager->mark_session_verified($this->user->data['session_id'], $user_id, 'totp', 'all');

                    // Audit log
                    $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_SETUP_ACTIVATED', $user_id, $username);

                    $step = 'backup_keys';
                } else {
                    $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_SETUP_FAILED', $user_id, $username);
                    $error = $this->user->lang['BOOSKIT_2FA_INVALID_CODE'];
                }
            }
        }

        add_form_key('booskit_2fa_setup');

        $issuer = $this->manager->get_issuer_name();
        $account_name = $this->user->data['username'];
        $otpauth_uri = $this->totp->get_provisioning_uri($pending_secret, $account_name, $issuer);
        $qr_svg_data_uri = $this->qr_code->generate_data_uri($otpauth_uri, 200);
        $formatted_secret = $this->totp->format_secret_for_display($pending_secret);

        $is_enforced = $this->manager->is_user_enforced($user_id);

        $this->template->assign_vars([
            'S_STEP'               => $step,
            'ERROR'                => $error,
            'QR_DATA_URI'          => $qr_svg_data_uri,
            'SECRET_KEY'           => $pending_secret,
            'SECRET_KEY_FORMATTED' => $formatted_secret,
            'OTPAUTH_URI'          => $otpauth_uri,
            'ISSUER'               => $issuer,
            'ACCOUNT_NAME'         => $account_name,
            'S_ENFORCED'           => $is_enforced,
            'U_ACTION'             => $this->helper->route('booskit_twofactor_setup'),
            'U_LOGOUT'             => append_sid(generate_board_url() . '/ucp.php', 'mode=logout&amp;sid=' . $this->user->session_id),
            'U_INDEX'              => append_sid(generate_board_url() . '/index.php'),
            'BOOSKIT_2FA_COLOR'    => $this->manager->get_theme_color(),
            'BOOSKIT_2FA_LOGO_URL' => $this->manager->get_logo_url(),
        ]);

        if ($step === 'backup_keys') {
            foreach ($plain_backup_codes as $bcode) {
                $this->template->assign_block_vars('backup_keys', [
                    'KEY' => $bcode,
                ]);
            }
            $this->template->assign_vars([
                'BACKUP_KEYS_TEXT' => implode("\n", $plain_backup_codes),
            ]);
        }

        return $this->helper->render('twofactor_setup.html', $this->user->lang['BOOSKIT_2FA_SETUP_TITLE']);
    }
}
