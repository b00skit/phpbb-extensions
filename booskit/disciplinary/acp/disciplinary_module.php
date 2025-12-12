<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\acp;

class disciplinary_module
{
	public $u_action;

	public function main($id, $mode)
	{
		global $user, $template, $request, $config, $phpbb_container;

		$user->add_lang_ext('booskit/disciplinary', 'info_acp_disciplinary');

		$this->tpl_name = 'acp_disciplinary_settings';
		$this->page_title = 'ACP_BOOSKIT_DISCIPLINARY_TITLE';

		$form_key = 'acp_disciplinary_settings';
		add_form_key($form_key);

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error('FORM_INVALID');
			}

			$config->set('booskit_disciplinary_json_url', $request->variable('booskit_disciplinary_json_url', ''));

			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'BOOSKIT_DISCIPLINARY_JSON_URL'	=> $config['booskit_disciplinary_json_url'],
			'U_ACTION'						=> $this->u_action,
		));
	}
}
