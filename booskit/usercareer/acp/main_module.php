<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\acp;

class main_module
{
	public $u_action;

	public function main($id, $mode)
	{
		global $user, $template, $request, $config, $phpbb_container;

		$user->add_lang_ext('booskit/usercareer', 'info_acp_career');

		$this->tpl_name = 'acp_career_settings';
		$this->page_title = 'ACP_BOOSKIT_CAREER_TITLE';

		$form_key = 'acp_career_settings';
		add_form_key($form_key);

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error('FORM_INVALID');
			}

			$config->set('booskit_career_json_url', $request->variable('booskit_career_json_url', ''));
			$config->set('booskit_career_access_view', $request->variable('booskit_career_access_view', ''));
			$config->set('booskit_career_access_view_global', $request->variable('booskit_career_access_view_global', ''));
			$config->set('booskit_career_access_l1', $request->variable('booskit_career_access_l1', ''));
			$config->set('booskit_career_access_l2', $request->variable('booskit_career_access_l2', ''));
			$config->set('booskit_career_access_l3', $request->variable('booskit_career_access_l3', ''));
			$config->set('booskit_career_access_full', $request->variable('booskit_career_access_full', ''));

			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'BOOSKIT_CAREER_JSON_URL'	=> $config['booskit_career_json_url'],
			'BOOSKIT_CAREER_ACCESS_VIEW'	=> isset($config['booskit_career_access_view']) ? $config['booskit_career_access_view'] : '',
			'BOOSKIT_CAREER_ACCESS_VIEW_GLOBAL'	=> isset($config['booskit_career_access_view_global']) ? $config['booskit_career_access_view_global'] : '',
			'BOOSKIT_CAREER_ACCESS_L1'	=> $config['booskit_career_access_l1'],
			'BOOSKIT_CAREER_ACCESS_L2'	=> $config['booskit_career_access_l2'],
			'BOOSKIT_CAREER_ACCESS_L3'	=> $config['booskit_career_access_l3'],
			'BOOSKIT_CAREER_ACCESS_FULL'	=> $config['booskit_career_access_full'],
			'U_ACTION'						=> $this->u_action,
		));
	}
}
