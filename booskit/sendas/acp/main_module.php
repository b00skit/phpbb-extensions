<?php
/**
 *
 * Send As. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\sendas\acp;

/**
 * Send As ACP module.
 */
class main_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	/**
	 * Main ACP module
	 *
	 * @param int    $id   The module ID
	 * @param string $mode The module mode (for example: manage or settings)
	 * @throws \Exception
	 */
	public function main($id, $mode)
	{
		global $phpbb_container;

		/** @var \booskit\sendas\controller\acp_controller $acp_controller */
		$acp_controller = $phpbb_container->get('booskit.sendas.controller.acp');

		/** @var \phpbb\language\language $language */
		$language = $phpbb_container->get('language');
		// Ensure our language strings are available
		$language->add_lang('acp_sendas', 'booskit/sendas');

		// Load a template from adm/style for our ACP page
		$this->tpl_name = 'acp_sendas_body';

		// Set the page title for our ACP page
		$this->page_title = $language->lang('ACP_SENDAS_TITLE');

		// Make the $u_action url available in our ACP controller
		$acp_controller->set_page_url($this->u_action);

		// Load the display options handle in our ACP controller
		$acp_controller->display_options();
	}
}
