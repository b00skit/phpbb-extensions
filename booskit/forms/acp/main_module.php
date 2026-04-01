<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\acp;

class main_module
{
	public $u_action;
	public $tpl_name;
	public $page_title;

	public function main($id, $mode)
	{
		global $phpbb_container;

		$this->tpl_name = 'acp_forms_manage';
		$this->page_title = 'ACP_BOOSKIT_FORMS_TITLE';

		add_form_key('acp_booskit_forms');

		if ($mode === 'manage')
		{
			$controller = $phpbb_container->get('booskit.forms.controller.acp.main');
			$controller->handle($this->u_action);
		}
	}
}
