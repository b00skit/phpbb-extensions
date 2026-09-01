<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class suggestion
{
    /** @var \phpbb\request\request */
    protected $request;

    /** @var \phpbb\user */
    protected $user;

    /** @var \phpbb\controller\helper */
    protected $helper;

    /** @var \booskit\twofactor\service\twofactor_manager */
    protected $manager;

    public function __construct(
        \phpbb\request\request $request,
        \phpbb\user $user,
        \phpbb\controller\helper $helper,
        \booskit\twofactor\service\twofactor_manager $manager
    ) {
        $this->request = $request;
        $this->user = $user;
        $this->helper = $helper;
        $this->manager = $manager;
    }

    public function dismiss()
    {
        $user_id = (int)$this->user->data['user_id'];
        if ($user_id != ANONYMOUS) {
            $this->manager->dismiss_suggestion($user_id, 30);
            $this->manager->log_2fa_action('LOG_BOOSKIT_2FA_SUGGESTION_DISMISSED', $user_id, $this->user->data['username']);
        }

        if ($this->request->is_ajax()) {
            return new JsonResponse(['success' => true]);
        }

        $redirect = $this->request->variable('redirect', '');
        if (!empty($redirect)) {
            $url = append_sid($redirect, false, false);
            return new RedirectResponse(str_replace('&amp;', '&', $url));
        }

        $url = append_sid(generate_board_url() . '/index.php', false, false);
        return new RedirectResponse(str_replace('&amp;', '&', $url));
    }
}
