<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\ucp;

class twofactor_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;

    public function main($id, $mode)
    {
        global $user, $phpbb_container, $phpbb_root_path, $phpEx;

        $user->add_lang_ext('booskit/twofactor', 'info_ucp_twofactor');
        $user->add_lang_ext('booskit/twofactor', 'common');

        $this->tpl_name = 'ucp_twofactor_manage';
        $this->page_title = 'UCP_BOOSKIT_TWOFACTOR_TITLE';

        $this->u_action = append_sid($phpbb_root_path . 'ucp.' . $phpEx, "i={$id}&mode={$mode}");

        $controller = $phpbb_container->get('booskit.twofactor.controller.ucp.manage');
        $controller->handle($id, $mode, $this->u_action);
    }
}
