<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\acp;

class awards_module
{
	public $u_action;

	public function main($id, $mode)
	{
		global $user, $template, $request;

		$user->add_lang_ext('booskit/awards', 'awards');

		$this->tpl_name = 'acp_settings';
		$this->page_title = 'ACP_BOOSKIT_AWARDS_TITLE';

		$form_key = 'acp_booskit_awards';
		add_form_key($form_key);

		if ($mode === 'settings')
		{
			// Load the service via the container
			global $phpbb_container;
			$controller = $phpbb_container->get('booskit.awards.controller.acp.settings');
			$controller->handle($this->u_action);
		}
	}
}
