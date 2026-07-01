<?php
/**
 *
 * @package booskit/threadprefixes
 * @license MIT
 *
 */

namespace booskit\threadprefixes\acp;

class threadprefixes_module
{
	public $u_action;

	public function main($id, $mode)
	{
		global $user;

		$user->add_lang_ext('booskit/threadprefixes', 'info_acp_threadprefixes');

		$this->tpl_name = 'acp_settings';
		$this->page_title = 'ACP_BOOSKIT_THREADPREFIXES_TITLE';

		$form_key = 'acp_booskit_threadprefixes';
		add_form_key($form_key);

		if ($mode === 'settings')
		{
			global $phpbb_container;
			$controller = $phpbb_container->get('booskit.threadprefixes.controller.acp.settings');
			$controller->handle($this->u_action);
		}
	}
}
