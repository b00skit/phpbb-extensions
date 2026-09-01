<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\acp;

class twofactor_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;

    public function main($id, $mode)
    {
        global $user, $phpbb_container;

        $user->add_lang_ext('booskit/twofactor', 'info_acp_twofactor');
        $user->add_lang_ext('booskit/twofactor', 'common');

        $this->tpl_name = 'acp_twofactor_settings';
        $this->page_title = 'ACP_BOOSKIT_TWOFACTOR_TITLE';

        if ($mode === 'settings') {
            $controller = $phpbb_container->get('booskit.twofactor.controller.acp.settings');
            $controller->handle($this->u_action);
        }
    }
}
