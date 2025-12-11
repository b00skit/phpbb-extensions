<?php
namespace booskit\gtawoauth\ucp;

class gtaw_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;

    public function main($id, $mode)
    {
        global $phpbb_container, $phpbb_root_path, $phpEx;

        $this->tpl_name = 'ucp_gtaw_link';
        $this->page_title = 'UCP_GTAW_LINK_TITLE';

        $this->u_action = append_sid($phpbb_root_path . 'ucp.' . $phpEx, "i={$id}&mode={$mode}");

        $controller = $phpbb_container->get('booskit.gtawoauth.controller.ucp');
        $controller->handle($id, $mode, $this->u_action);
    }
}
