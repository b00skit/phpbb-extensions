<?php
namespace booskit\gtawoauth\acp;

class gtaw_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;

    public function main($id, $mode)
    {
        global $phpbb_container, $phpbb_root_path, $phpEx;

        $this->tpl_name = 'acp_settings';
        $this->page_title = 'ACP_GTAW_OAUTH_SETTINGS';

        $this->u_action = append_sid($phpbb_root_path . 'adm/index.' . $phpEx, "i={$id}&mode={$mode}");

        $controller = $phpbb_container->get('booskit.gtawoauth.controller.acp');
        $controller->handle($id, $mode, $this->u_action);
    }
}
