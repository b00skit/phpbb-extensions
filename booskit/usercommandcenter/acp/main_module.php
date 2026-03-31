<?php
/**
 *
 * @package booskit/usercommandcenter
 * @license MIT
 *
 */

namespace booskit\usercommandcenter\acp;

class main_module
{
	public $u_action;

	public function main($id, $mode)
	{
		global $user, $template, $request, $config;

		$user->add_lang_ext('booskit/usercommandcenter', 'ucc');
		$user->add_lang_ext('booskit/usercommandcenter', 'info_acp_ucc');

		$this->tpl_name = 'acp_ucc_settings';
		$this->page_title = 'ACP_BOOSKIT_UCC_TITLE';

		$form_key = 'acp_ucc';
		add_form_key($form_key);

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error('FORM_INVALID');
			}

			$config->set('booskit_ucc_enabled', $request->variable('booskit_ucc_enabled', 0));
			$config->set('booskit_ucc_allowed_groups', $request->variable('booskit_ucc_allowed_groups', ''));
			$config->set('booskit_ucc_include_awards', $request->variable('booskit_ucc_include_awards', 0));
			$config->set('booskit_ucc_include_career', $request->variable('booskit_ucc_include_career', 0));
			$config->set('booskit_ucc_include_commendations', $request->variable('booskit_ucc_include_commendations', 0));
			$config->set('booskit_ucc_include_disciplinary', $request->variable('booskit_ucc_include_disciplinary', 0));
			$config->set('booskit_ucc_include_ic_disciplinary', $request->variable('booskit_ucc_include_ic_disciplinary', 0));

			trigger_error($user->lang['UCC_SETTINGS_SAVED'] . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'BOOSKIT_UCC_ENABLED' => $config['booskit_ucc_enabled'],
			'BOOSKIT_UCC_ALLOWED_GROUPS' => $config['booskit_ucc_allowed_groups'],
			'BOOSKIT_UCC_INCLUDE_AWARDS' => $config['booskit_ucc_include_awards'],
			'BOOSKIT_UCC_INCLUDE_CAREER' => $config['booskit_ucc_include_career'],
			'BOOSKIT_UCC_INCLUDE_COMMENDATIONS' => $config['booskit_ucc_include_commendations'],
			'BOOSKIT_UCC_INCLUDE_DISCIPLINARY' => $config['booskit_ucc_include_disciplinary'],
			'BOOSKIT_UCC_INCLUDE_IC_DISCIPLINARY' => $config['booskit_ucc_include_ic_disciplinary'],
			'U_ACTION' => $this->u_action,
		));
	}
}
