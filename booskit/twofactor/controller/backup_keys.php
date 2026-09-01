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

class backup_keys
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

    /** @var \booskit\twofactor\service\backup_code_manager */
    protected $backup_codes;

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\request\request $request,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\controller\helper $helper,
        \booskit\twofactor\service\twofactor_manager $manager,
        \booskit\twofactor\service\backup_code_manager $backup_codes
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->helper = $helper;
        $this->manager = $manager;
        $this->backup_codes = $backup_codes;
    }

    public function handle()
    {
        $this->user->add_lang_ext('booskit/twofactor', 'common');

        $user_id = (int)$this->user->data['user_id'];
        if ($user_id == ANONYMOUS) {
            login_box();
        }

        if ($this->request->is_set_post('acknowledge')) {
            if (!check_form_key('booskit_2fa_ack_backup')) {
                trigger_error($this->user->lang['FORM_INVALID']);
            }
            $this->manager->clear_reset_backup_pending($user_id);
            $url = append_sid(generate_board_url() . '/index.php', false, false);
            return new RedirectResponse(str_replace('&amp;', '&', $url));
        }

        add_form_key('booskit_2fa_ack_backup');

        // Generate 10 fresh keys for the user
        $plain_keys = $this->backup_codes->generate_codes($user_id, 10);

        foreach ($plain_keys as $k) {
            $this->template->assign_block_vars('backup_keys', [
                'KEY' => $k,
            ]);
        }

        $this->template->assign_vars([
            'BACKUP_KEYS_TEXT'     => implode("\n", $plain_keys),
            'U_ACTION'             => $this->helper->route('booskit_twofactor_backup_keys'),
            'BOOSKIT_2FA_COLOR'    => $this->manager->get_theme_color(),
            'BOOSKIT_2FA_LOGO_URL' => $this->manager->get_logo_url(),
        ]);

        return $this->helper->render('twofactor_backup_keys.html', $this->user->lang['BOOSKIT_2FA_BACKUP_KEYS_TITLE']);
    }
}
