<?php
/**
 *
 * @package booskit/commendations
 * @license MIT
 *
 */

namespace booskit\commendations\acp;

class main_module
{
	public $u_action;

	public function main($id, $mode)
	{
		global $user, $template, $request, $config;

		$user->add_lang_ext('booskit/commendations', 'info_acp_commendations');

		$this->tpl_name = 'acp_settings';
		$this->page_title = 'ACP_BOOSKIT_COMMENDATIONS_TITLE';

		$form_key = 'acp_commendations_settings';
		add_form_key($form_key);

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error('FORM_INVALID');
			}

			$config->set('booskit_commendations_access_view', $request->variable('booskit_commendations_access_view', ''));
			$config->set('booskit_commendations_access_view_global', $request->variable('booskit_commendations_access_view_global', ''));
			$config->set('booskit_commendations_access_l1', $request->variable('booskit_commendations_access_l1', ''));
			$config->set('booskit_commendations_access_l2', $request->variable('booskit_commendations_access_l2', ''));
			$config->set('booskit_commendations_access_l3', $request->variable('booskit_commendations_access_l3', ''));
			$config->set('booskit_commendations_access_full', $request->variable('booskit_commendations_access_full', ''));

			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'BOOSKIT_COMMENDATIONS_ACCESS_VIEW'	=> isset($config['booskit_commendations_access_view']) ? $config['booskit_commendations_access_view'] : '',
			'BOOSKIT_COMMENDATIONS_ACCESS_VIEW_GLOBAL'	=> isset($config['booskit_commendations_access_view_global']) ? $config['booskit_commendations_access_view_global'] : '',
			'BOOSKIT_COMMENDATIONS_ACCESS_L1'	=> $config['booskit_commendations_access_l1'],
			'BOOSKIT_COMMENDATIONS_ACCESS_L2'	=> $config['booskit_commendations_access_l2'],
			'BOOSKIT_COMMENDATIONS_ACCESS_L3'	=> $config['booskit_commendations_access_l3'],
			'BOOSKIT_COMMENDATIONS_ACCESS_FULL'	=> $config['booskit_commendations_access_full'],
			'U_ACTION'						=> $this->u_action,
		));
	}
}
