<?php

namespace booskit\datacollector\acp;

class main_module
{
	public $u_action;
	public $tpl_name;
	public $page_title;

	function main($id, $mode)
	{
		global $user, $request, $phpbb_container;

		$user->add_lang_ext('booskit/datacollector', 'acp');

		$this->tpl_name = 'acp_datacollector_settings';
		$this->page_title = $user->lang('ACP_DATACOLLECTOR_SETTINGS');

		$controller = $phpbb_container->get('booskit.datacollector.controller.acp.settings');
		$controller->handle($this->u_action);
	}
}
