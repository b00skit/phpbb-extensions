<?php
/**
 *
 * Topic Log Views extension for phpBB.
 *
 * @copyright (c) 2026 Booskit
 * @license MIT
 *
 */

namespace booskit\topiclogviews\acp;

class main_module
{
	public $u_action;
	public $tpl_name;
	public $page_title;

	public function main($id, $mode)
	{
		global $user, $phpbb_container;

		$user->add_lang_ext('booskit/topiclogviews', 'info_acp_topiclogviews');

		$this->tpl_name   = 'acp_topiclogviews_settings';
		$this->page_title = $user->lang('ACP_TOPICLOGVIEWS_SETTINGS');

		$controller = $phpbb_container->get('booskit.topiclogviews.controller.acp.settings');
		$controller->handle($this->u_action);
	}
}
