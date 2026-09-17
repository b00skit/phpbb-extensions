<?php
/**
 *
 * @package booskit/mention
 * @license MIT
 *
 */

namespace booskit\mention\acp;

class mention_module
{
	public $u_action;
	public $tpl_name;
	public $page_title;

	public function main($id, $mode)
	{
		global $phpbb_container, $user;

		$user->add_lang_ext('booskit/mention', 'info_acp_mention');

		$this->tpl_name = 'acp_mention_settings';
		$this->page_title = 'ACP_BOOSKIT_MENTION_TITLE';

		if ($mode === 'settings')
		{
			$controller = $phpbb_container->get('booskit.mention.controller.acp.settings');
			$controller->handle($this->u_action);
		}
	}
}
