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
		global $user, $template, $request, $config, $phpbb_container;

		$user->add_lang_ext('booskit/dashboard', 'dashboard');
		$user->add_lang_ext('booskit/dashboard', 'info_acp_dashboard');

		$this->tpl_name = 'acp_dashboard_settings';
		$this->page_title = 'ACP_BOOSKIT_DASHBOARD_TITLE';

		$form_key = 'acp_dashboard';
		add_form_key($form_key);

		$dashboard_manager = $phpbb_container->get('booskit.dashboard.manager');
		$action = $request->variable('action', '');

		if ($mode === 'statistics')
		{
			$this->tpl_name = 'acp_dashboard_statistics';
			$this->page_title = 'ACP_BOOSKIT_DASHBOARD_STATISTICS';

			// Delete category
			if ($action === 'delete_cat')
			{
				$cat_id = $request->variable('cat_id', 0);
				if ($cat_id > 0)
				{
					$dashboard_manager->delete_stat_category($cat_id);
					trigger_error($user->lang['DASHBOARD_SETTINGS_SAVED'] . adm_back_link($this->u_action));
				}
			}

			// Delete stat definition
			if ($action === 'delete_stat')
			{
				$stat_id = $request->variable('stat_id', 0);
				if ($stat_id > 0)
				{
					$dashboard_manager->delete_stat_definition($stat_id);
					trigger_error($user->lang['DASHBOARD_SETTINGS_SAVED'] . adm_back_link($this->u_action));
				}
			}

			if ($request->is_set_post('submit'))
			{
				if (!check_form_key($form_key))
				{
					trigger_error('FORM_INVALID');
				}

				if ($action === 'add_cat')
				{
					$cat_name = $request->variable('new_cat_name', '', true);
					$cat_desc = $request->variable('new_cat_desc', '', true);
					$cat_order = $request->variable('new_cat_order', 0);

					if (!empty($cat_name))
					{
						$dashboard_manager->add_stat_category($cat_name, $cat_desc, $cat_order);
					}
					trigger_error($user->lang['DASHBOARD_SETTINGS_SAVED'] . adm_back_link($this->u_action));
				}

				if ($action === 'update_cat')
				{
					$cat_id = $request->variable('cat_id', 0);
					$cat_name = $request->variable('cat_name', '', true);
					$cat_desc = $request->variable('cat_desc', '', true);
					$cat_order = $request->variable('cat_order', 0);

					if ($cat_id > 0 && !empty($cat_name))
					{
						$dashboard_manager->update_stat_category($cat_id, $cat_name, $cat_desc, $cat_order);
					}
					trigger_error($user->lang['DASHBOARD_SETTINGS_SAVED'] . adm_back_link($this->u_action));
				}

				if ($action === 'add_stat')
				{
					$stat_tag = $request->variable('new_stat_tag', '', true);
					$stat_title = $request->variable('new_stat_title', '', true);
					$cat_id = $request->variable('new_cat_id', 0);
					$stat_color = $request->variable('new_stat_color', '#2563eb', true);
					$stat_desc = $request->variable('new_stat_desc', '', true);
					$stat_order = $request->variable('new_stat_order', 0);
					$allowed_groups = $request->variable('new_stat_allowed_groups', [0]);
					$use_allowed_groups = $request->variable('new_stat_use_allowed_groups', [0]);

					if (!empty($stat_tag) && !empty($stat_title))
					{
						$dashboard_manager->add_stat_definition($stat_tag, $stat_title, $cat_id, $stat_color, $stat_desc, $stat_order, $allowed_groups, $use_allowed_groups);
					}
					trigger_error($user->lang['DASHBOARD_SETTINGS_SAVED'] . adm_back_link($this->u_action));
				}

				if ($action === 'update_stat')
				{
					$stat_id = $request->variable('stat_id', 0);
					$stat_tag = $request->variable('stat_tag', '', true);
					$stat_title = $request->variable('stat_title', '', true);
					$cat_id = $request->variable('cat_id', 0);
					$stat_color = $request->variable('stat_color', '#2563eb', true);
					$stat_desc = $request->variable('stat_desc', '', true);
					$stat_order = $request->variable('stat_order', 0);
					$allowed_groups = $request->variable('allowed_groups', [0]);
					$use_allowed_groups = $request->variable('use_allowed_groups', [0]);

					if ($stat_id > 0 && !empty($stat_tag) && !empty($stat_title))
					{
						$dashboard_manager->update_stat_definition($stat_id, $stat_tag, $stat_title, $cat_id, $stat_color, $stat_desc, $stat_order, $allowed_groups, $use_allowed_groups);
					}
					trigger_error($user->lang['DASHBOARD_SETTINGS_SAVED'] . adm_back_link($this->u_action));
				}

				if ($action === 'resync_stats')
				{
					$count = $dashboard_manager->resync_all_stat_posts();
					trigger_error(sprintf($user->lang['DASHBOARD_RESYNC_SUCCESS'], $count) . adm_back_link($this->u_action));
				}
			}

			$phpbb_groups = $dashboard_manager->get_phpbb_groups();
			$categories = $dashboard_manager->get_stat_categories();
			$definitions = $dashboard_manager->get_stat_definitions();

			$template->assign_vars([
				'STAT_CATEGORIES'  => $categories,
				'STAT_DEFINITIONS' => $definitions,
				'PHPBB_GROUPS'     => $phpbb_groups,
				'U_ACTION'         => $this->u_action,
			]);

			return;
		}

		// Mode: settings (Default)
		// Handle permission group deletion
		if ($action === 'delete_perm_group')
		{
			$perm_group_id = $request->variable('perm_group_id', 0);
			if (confirm_box(true))
			{
				if ($perm_group_id > 0)
				{
					$dashboard_manager->delete_permission_group($perm_group_id);
				}
				trigger_error($user->lang['DASHBOARD_SETTINGS_SAVED'] . adm_back_link($this->u_action));
			}
			else
			{
				confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields([
					'perm_group_id' => $perm_group_id,
					'action'        => 'delete_perm_group',
				]));
			}
		}

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error('FORM_INVALID');
			}

			if ($action === 'add_perm_group')
			{
				$group_name = $request->variable('new_perm_group_name', '', true);
				$applies_to = $request->variable('new_applies_to', [0]);
				$power_over_all = $request->variable('new_power_over_all', 0);
				$power_over_self = $request->variable('new_power_over_self', 0);
				$power_over_groups = $request->variable('new_power_over_groups', [0]);
				$exclude_groups = $request->variable('new_exclude_groups', [0]);
				$perms_raw = $request->variable('new_perms', ['' => 0]);

				if (!empty($group_name))
				{
					$dashboard_manager->add_permission_group(
						$group_name,
						$applies_to,
						$power_over_all,
						$power_over_self,
						$power_over_groups,
						$exclude_groups,
						$perms_raw
					);
				}
				trigger_error($user->lang['DASHBOARD_SETTINGS_SAVED'] . adm_back_link($this->u_action));
			}

			if ($action === 'update_perm_group')
			{
				$perm_group_id = $request->variable('perm_group_id', 0);
				$group_names = $request->variable('perm_group_name', [0 => ''], true);
				$applies_to_all = $request->variable('applies_to', [0 => [0]]);
				$power_over_all_all = $request->variable('power_over_all', [0 => 0]);
				$power_over_self_all = $request->variable('power_over_self', [0 => 0]);
				$power_over_groups_all = $request->variable('power_over_groups', [0 => [0]]);
				$exclude_groups_all = $request->variable('exclude_groups', [0 => [0]]);
				$perms_all = $request->variable('perms', [0 => ['' => 0]]);

				if ($perm_group_id > 0 && isset($group_names[$perm_group_id]))
				{
					$group_name = $group_names[$perm_group_id];
					$applies_to = isset($applies_to_all[$perm_group_id]) ? $applies_to_all[$perm_group_id] : [];
					$power_over_all = isset($power_over_all_all[$perm_group_id]) ? $power_over_all_all[$perm_group_id] : 0;
					$power_over_self = isset($power_over_self_all[$perm_group_id]) ? $power_over_self_all[$perm_group_id] : 0;
					$power_over_groups = isset($power_over_groups_all[$perm_group_id]) ? $power_over_groups_all[$perm_group_id] : [];
					$exclude_groups = isset($exclude_groups_all[$perm_group_id]) ? $exclude_groups_all[$perm_group_id] : [];
					$perms = isset($perms_all[$perm_group_id]) ? $perms_all[$perm_group_id] : [];

					$dashboard_manager->update_permission_group(
						$perm_group_id,
						$group_name,
						$applies_to,
						$power_over_all,
						$power_over_self,
						$power_over_groups,
						$exclude_groups,
						$perms
					);
				}
				trigger_error($user->lang['DASHBOARD_SETTINGS_SAVED'] . adm_back_link($this->u_action));
			}

			if ($action === '')
			{
				$config->set('booskit_dashboard_enabled', $request->variable('booskit_dashboard_enabled', 0));
				$config->set('booskit_dashboard_perm_system', $request->variable('booskit_dashboard_perm_system', 'groups'));
				$config->set('booskit_dashboard_group_metric_group', $request->variable('booskit_dashboard_group_metric_group', 0));
				$config->set('booskit_dashboard_group_metric_label', $request->variable('booskit_dashboard_group_metric_label', '', true));

				$config->set('booskit_dashboard_include_stats_tab', $request->variable('booskit_dashboard_include_stats_tab', 0));
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
		}

		$phpbb_groups = $dashboard_manager->get_phpbb_groups();
		$permission_groups = $dashboard_manager->get_permission_groups();

		$template->assign_vars([
			'BOOSKIT_DASHBOARD_ENABLED'                  => (int) (isset($config['booskit_dashboard_enabled']) ? $config['booskit_dashboard_enabled'] : 1),
			'BOOSKIT_DASHBOARD_PERM_SYSTEM'              => isset($config['booskit_dashboard_perm_system']) ? $config['booskit_dashboard_perm_system'] : 'groups',
			'BOOSKIT_DASHBOARD_GROUP_METRIC_GROUP'       => (int) (isset($config['booskit_dashboard_group_metric_group']) ? $config['booskit_dashboard_group_metric_group'] : 0),
			'BOOSKIT_DASHBOARD_GROUP_METRIC_LABEL'       => isset($config['booskit_dashboard_group_metric_label']) ? $config['booskit_dashboard_group_metric_label'] : '',

			'BOOSKIT_DASHBOARD_INCLUDE_STATS_TAB'        => (int) (isset($config['booskit_dashboard_include_stats_tab']) ? $config['booskit_dashboard_include_stats_tab'] : 1),
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

			'PHPBB_GROUPS'                               => $phpbb_groups,
			'PERMISSION_GROUPS'                          => $permission_groups,
			'U_ACTION'                                   => $this->u_action,
		]);
	}
}
