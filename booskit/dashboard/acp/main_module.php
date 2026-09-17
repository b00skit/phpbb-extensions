<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\acp;

class main_module
{
	public $u_action;
	public $tpl_name;
	public $page_title;

	public function main($id, $mode)
	{
		global $user, $template, $request, $config, $db;

		$user->add_lang_ext('booskit/dashboard', 'dashboard');
		$user->add_lang_ext('booskit/dashboard', 'info_acp_dashboard');

		$this->tpl_name = 'acp_dashboard_settings';
		$this->page_title = 'ACP_BOOSKIT_DASHBOARD_TITLE';

		$form_key = 'acp_dashboard';
		add_form_key($form_key);

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error('FORM_INVALID');
			}

			$config->set('booskit_dashboard_enabled', $request->variable('booskit_dashboard_enabled', 0));
			$config->set('booskit_dashboard_allowed_groups', $request->variable('booskit_dashboard_allowed_groups', ''));
			$config->set('booskit_dashboard_include_awards', $request->variable('booskit_dashboard_include_awards', 0));
			$config->set('booskit_dashboard_include_career', $request->variable('booskit_dashboard_include_career', 0));
			$config->set('booskit_dashboard_include_commendations', $request->variable('booskit_dashboard_include_commendations', 0));
			$config->set('booskit_dashboard_include_disciplinary', $request->variable('booskit_dashboard_include_disciplinary', 0));
			$config->set('booskit_dashboard_include_ic_disciplinary', $request->variable('booskit_dashboard_include_ic_disciplinary', 0));

			$config->set('booskit_dashboard_group_profile_access', $request->variable('booskit_dashboard_group_profile_access', '', true));
			$config->set('booskit_dashboard_profile_admin_override', $request->variable('booskit_dashboard_profile_admin_override', 0));

			$config->set('booskit_dashboard_issued_groups', $request->variable('booskit_dashboard_issued_groups', ''));
			$config->set('booskit_dashboard_issued_allow_self', $request->variable('booskit_dashboard_issued_allow_self', 0));

			$config->set('booskit_dashboard_recent_topics_groups', $request->variable('booskit_dashboard_recent_topics_groups', ''));
			$config->set('booskit_dashboard_recent_topics_allow_self', $request->variable('booskit_dashboard_recent_topics_allow_self', 0));

			trigger_error($user->lang['DASHBOARD_SETTINGS_SAVED'] . adm_back_link($this->u_action));
		}

		// Query available groups for reference in ACP
		$sql = 'SELECT group_id, group_name, group_type FROM ' . GROUPS_TABLE . ' ORDER BY group_name ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$group_name = ($row['group_type'] == GROUP_SPECIAL) ? $user->lang('G_' . $row['group_name']) : $row['group_name'];
			$template->assign_block_vars('groups', [
				'GROUP_ID'   => $row['group_id'],
				'GROUP_NAME' => $group_name,
			]);
		}
		$db->sql_freeresult($result);

		$template->assign_vars(array(
			'BOOSKIT_DASHBOARD_ENABLED'                  => (int) (isset($config['booskit_dashboard_enabled']) ? $config['booskit_dashboard_enabled'] : 1),
			'BOOSKIT_DASHBOARD_ALLOWED_GROUPS'           => isset($config['booskit_dashboard_allowed_groups']) ? $config['booskit_dashboard_allowed_groups'] : '',
			'BOOSKIT_DASHBOARD_INCLUDE_AWARDS'           => (int) (isset($config['booskit_dashboard_include_awards']) ? $config['booskit_dashboard_include_awards'] : 1),
			'BOOSKIT_DASHBOARD_INCLUDE_CAREER'           => (int) (isset($config['booskit_dashboard_include_career']) ? $config['booskit_dashboard_include_career'] : 1),
			'BOOSKIT_DASHBOARD_INCLUDE_COMMENDATIONS'    => (int) (isset($config['booskit_dashboard_include_commendations']) ? $config['booskit_dashboard_include_commendations'] : 1),
			'BOOSKIT_DASHBOARD_INCLUDE_DISCIPLINARY'     => (int) (isset($config['booskit_dashboard_include_disciplinary']) ? $config['booskit_dashboard_include_disciplinary'] : 1),
			'BOOSKIT_DASHBOARD_INCLUDE_IC_DISCIPLINARY'  => (int) (isset($config['booskit_dashboard_include_ic_disciplinary']) ? $config['booskit_dashboard_include_ic_disciplinary'] : 1),
			'BOOSKIT_DASHBOARD_GROUP_PROFILE_ACCESS'     => isset($config['booskit_dashboard_group_profile_access']) ? $config['booskit_dashboard_group_profile_access'] : '',
			'BOOSKIT_DASHBOARD_PROFILE_ADMIN_OVERRIDE'   => (int) (isset($config['booskit_dashboard_profile_admin_override']) ? $config['booskit_dashboard_profile_admin_override'] : 1),
			'BOOSKIT_DASHBOARD_ISSUED_GROUPS'            => isset($config['booskit_dashboard_issued_groups']) ? $config['booskit_dashboard_issued_groups'] : '',
			'BOOSKIT_DASHBOARD_ISSUED_ALLOW_SELF'        => (int) (isset($config['booskit_dashboard_issued_allow_self']) ? $config['booskit_dashboard_issued_allow_self'] : 1),
			'BOOSKIT_DASHBOARD_RECENT_TOPICS_GROUPS'     => isset($config['booskit_dashboard_recent_topics_groups']) ? $config['booskit_dashboard_recent_topics_groups'] : '',
			'BOOSKIT_DASHBOARD_RECENT_TOPICS_ALLOW_SELF' => (int) (isset($config['booskit_dashboard_recent_topics_allow_self']) ? $config['booskit_dashboard_recent_topics_allow_self'] : 1),
			'U_ACTION'                                   => $this->u_action,
		));
	}
}
