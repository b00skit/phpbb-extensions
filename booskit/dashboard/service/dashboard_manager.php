<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\service;

class dashboard_manager
{
	protected $config;
	protected $db;
	protected $extension_manager;
	protected $cache;
	protected $auth;
	protected $table_prefix;
	protected $table_perm_groups;
	protected $group_avatars_cache = null;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\extension\manager $extension_manager,
		$cache = null,
		$auth = null,
		$table_prefix = ''
	) {
		$this->config = $config;
		$this->db = $db;
		$this->extension_manager = $extension_manager;
		$this->cache = is_object($cache) ? $cache : null;
		$this->auth = is_object($auth) ? $auth : null;
		$this->table_prefix = (string) $table_prefix;
		$this->table_perm_groups = $this->table_prefix . 'booskit_dashboard_perm_groups';
	}

	public function is_ext_enabled($ext_name)
	{
		return $this->extension_manager->is_enabled($ext_name);
	}

	public function get_db()
	{
		return $this->db;
	}

	public function get_table_prefix()
	{
		return $this->table_prefix;
	}

	public function get_perm_system()
	{
		return isset($this->config['booskit_dashboard_perm_system']) ? $this->config['booskit_dashboard_perm_system'] : 'groups';
	}

	public function get_user_groups($user_id)
	{
		$user_id = (int) $user_id;
		if ($user_id <= 0)
		{
			return [];
		}

		$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . $user_id . ' AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);
		return $groups;
	}

	public function is_admin($user_id = 0)
	{
		if ($this->auth !== null && $user_id === 0)
		{
			return (bool) $this->auth->acl_get('a_');
		}

		if ($this->auth !== null && $user_id > 0)
		{
			return (bool) $this->auth->acl_get('a_');
		}

		return false;
	}

	public function get_phpbb_groups()
	{
		global $user;
		$sql = 'SELECT group_id, group_name, group_type FROM ' . GROUPS_TABLE . ' ORDER BY group_type DESC, group_name ASC';
		$result = $this->db->sql_query($sql);
		$groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$name = ($row['group_type'] == GROUP_SPECIAL && isset($user->lang['G_' . $row['group_name']])) ? $user->lang['G_' . $row['group_name']] : $row['group_name'];
			$groups[] = [
				'group_id'   => (int) $row['group_id'],
				'group_name' => $name,
			];
		}
		$this->db->sql_freeresult($result);
		return $groups;
	}

	/* =========================================================================
	 * PERMISSION GROUPS MANAGEMENT
	 * ========================================================================= */

	public function get_permission_groups()
	{
		$sql = 'SELECT * FROM ' . $this->table_perm_groups . ' ORDER BY perm_group_id ASC';
		$result = @$this->db->sql_query($sql);
		$groups = [];
		if ($result)
		{
			while ($row = $this->db->sql_fetchrow($result))
			{
				$row['applies_to_array'] = !empty($row['applies_to']) ? array_map('intval', array_filter(array_map('trim', explode(',', $row['applies_to'])))) : [];
				$row['power_over_groups_array'] = !empty($row['power_over_groups']) ? array_map('intval', array_filter(array_map('trim', explode(',', $row['power_over_groups'])))) : [];
				$row['exclude_groups_array'] = !empty($row['exclude_groups']) ? array_map('intval', array_filter(array_map('trim', explode(',', $row['exclude_groups'])))) : [];
				$row['permissions_array'] = !empty($row['permissions']) ? json_decode($row['permissions'], true) : [];
				$groups[] = $row;
			}
			$this->db->sql_freeresult($result);
		}
		return $groups;
	}

	public function get_permission_group($perm_group_id)
	{
		$sql = 'SELECT * FROM ' . $this->table_perm_groups . ' WHERE perm_group_id = ' . (int) $perm_group_id;
		$result = @$this->db->sql_query($sql);
		$row = $result ? $this->db->sql_fetchrow($result) : null;
		if ($result)
		{
			$this->db->sql_freeresult($result);
		}
		if ($row)
		{
			$row['applies_to_array'] = !empty($row['applies_to']) ? array_map('intval', array_filter(array_map('trim', explode(',', $row['applies_to'])))) : [];
			$row['power_over_groups_array'] = !empty($row['power_over_groups']) ? array_map('intval', array_filter(array_map('trim', explode(',', $row['power_over_groups'])))) : [];
			$row['exclude_groups_array'] = !empty($row['exclude_groups']) ? array_map('intval', array_filter(array_map('trim', explode(',', $row['exclude_groups'])))) : [];
			$row['permissions_array'] = !empty($row['permissions']) ? json_decode($row['permissions'], true) : [];
		}
		return $row;
	}

	public function add_permission_group($group_name, $applies_to, $power_over_all, $power_over_self, $power_over_groups, $exclude_groups, $permissions)
	{
		$applies_str = is_array($applies_to) ? implode(',', array_map('intval', $applies_to)) : (string) $applies_to;
		$power_groups_str = is_array($power_over_groups) ? implode(',', array_map('intval', $power_over_groups)) : (string) $power_over_groups;
		$exclude_groups_str = is_array($exclude_groups) ? implode(',', array_map('intval', $exclude_groups)) : (string) $exclude_groups;
		$perms_json = is_array($permissions) ? json_encode($permissions) : (string) $permissions;

		$sql_ary = [
			'group_name'        => $group_name,
			'applies_to'        => $applies_str,
			'power_over_all'    => (int) $power_over_all,
			'power_over_self'   => (int) $power_over_self,
			'power_over_groups' => $power_groups_str,
			'exclude_groups'    => $exclude_groups_str,
			'permissions'       => $perms_json,
		];
		$sql = 'INSERT INTO ' . $this->table_perm_groups . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
	}

	public function update_permission_group($perm_group_id, $group_name, $applies_to, $power_over_all, $power_over_self, $power_over_groups, $exclude_groups, $permissions)
	{
		$applies_str = is_array($applies_to) ? implode(',', array_map('intval', $applies_to)) : (string) $applies_to;
		$power_groups_str = is_array($power_over_groups) ? implode(',', array_map('intval', $power_over_groups)) : (string) $power_over_groups;
		$exclude_groups_str = is_array($exclude_groups) ? implode(',', array_map('intval', $exclude_groups)) : (string) $exclude_groups;
		$perms_json = is_array($permissions) ? json_encode($permissions) : (string) $permissions;

		$sql_ary = [
			'group_name'        => $group_name,
			'applies_to'        => $applies_str,
			'power_over_all'    => (int) $power_over_all,
			'power_over_self'   => (int) $power_over_self,
			'power_over_groups' => $power_groups_str,
			'exclude_groups'    => $exclude_groups_str,
			'permissions'       => $perms_json,
		];
		$sql = 'UPDATE ' . $this->table_perm_groups . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE perm_group_id = ' . (int) $perm_group_id;
		$this->db->sql_query($sql);
	}

	public function delete_permission_group($perm_group_id)
	{
		$sql = 'DELETE FROM ' . $this->table_perm_groups . ' WHERE perm_group_id = ' . (int) $perm_group_id;
		$this->db->sql_query($sql);
	}

	/* =========================================================================
	 * EFFECTIVE PERMISSION EVALUATION
	 * ========================================================================= */

	public function get_effective_permissions($viewer_id, $target_user_id = 0)
	{
		$viewer_id = (int) $viewer_id;
		$target_user_id = (int) $target_user_id;

		$default_perms = [
			'view_dashboard'           => false,
			'view_stats'               => false,
			'view_active_users'        => false,
			'view_hot_topics'          => false,
			'view_feeds'               => false,
			'view_feed_disciplinary'   => false,
			'view_feed_ic_disciplinary'=> false,
			'view_feed_awards'         => false,
			'view_feed_career'         => false,
			'view_feed_commendations'  => false,
			'search_users'             => false,
			'view_profile'             => false,
			'view_issued'              => false,
			'view_visited_topics'      => false,
			'view_visited_forums'      => false,
			'view_visited_users'       => false,
			'view_visited_profiles'    => false,
			'view_disciplinary'        => false,
			'view_ic_disciplinary'     => false,
			'view_awards'              => false,
			'view_career'              => false,
			'view_commendations'       => false,
			'view_gtaw'                => false,
		];

		if (empty($this->config['booskit_dashboard_enabled']))
		{
			return $default_perms;
		}

		$perm_system = $this->get_perm_system();

		// Legacy mode
		if ($perm_system === 'legacy')
		{
			$can_dash = $this->can_view_dashboard_legacy($viewer_id);
			$can_prof = ($target_user_id > 0) ? $this->can_view_user_profile_legacy($viewer_id, $target_user_id) : true;
			$can_iss = ($target_user_id > 0) ? $this->can_view_issued_actions_legacy($viewer_id, $target_user_id) : true;
			$can_top = ($target_user_id > 0) ? $this->can_view_recent_topics_legacy($viewer_id, $target_user_id) : true;

			return [
				'view_dashboard'           => $can_dash,
				'view_stats'               => $can_dash,
				'view_active_users'        => $can_dash,
				'view_hot_topics'          => $can_dash,
				'view_feeds'               => $can_dash,
				'view_feed_disciplinary'   => $can_dash,
				'view_feed_ic_disciplinary'=> $can_dash,
				'view_feed_awards'         => $can_dash,
				'view_feed_career'         => $can_dash,
				'view_feed_commendations'  => $can_dash,
				'search_users'             => $can_dash,
				'view_profile'             => $can_dash && $can_prof,
				'view_issued'              => $can_dash && $can_iss,
				'view_visited_topics'      => $can_dash && $can_top,
				'view_visited_forums'      => $can_dash && $can_top,
				'view_visited_users'       => $can_dash && $can_top,
				'view_visited_profiles'    => $can_dash && $can_top,
				'view_disciplinary'        => $can_dash,
				'view_ic_disciplinary'     => $can_dash,
				'view_awards'              => $can_dash,
				'view_career'              => $can_dash,
				'view_commendations'       => $can_dash,
				'view_gtaw'                => $can_dash,
			];
		}

		// Advanced Groups mode
		$perm_groups = $this->get_permission_groups();
		if (empty($perm_groups))
		{
			return $default_perms;
		}

		$viewer_groups = $this->get_user_groups($viewer_id);
		$target_groups = ($target_user_id > 0) ? $this->get_user_groups($target_user_id) : [];

		$effective = $default_perms;

		foreach ($perm_groups as $pg)
		{
			// Check if permission group applies to viewer
			if (empty($pg['applies_to_array']) || !array_intersect($viewer_groups, $pg['applies_to_array']))
			{
				continue;
			}

			$perms = !empty($pg['permissions_array']) ? $pg['permissions_array'] : [];

			// Dashboard General permissions (not target dependent)
			foreach (['view_dashboard', 'view_stats', 'view_active_users', 'view_hot_topics', 'view_feeds', 'search_users'] as $k)
			{
				if (!empty($perms[$k]))
				{
					$effective[$k] = true;
				}
			}

			// Feeds granular permissions
			if (!empty($perms['view_feeds']))
			{
				foreach (['view_feed_disciplinary', 'view_feed_ic_disciplinary', 'view_feed_awards', 'view_feed_career', 'view_feed_commendations'] as $fk)
				{
					if (!isset($perms[$fk]) || !empty($perms[$fk]))
					{
						$effective[$fk] = true;
					}
				}
			}

			// Target-dependent profile permissions
			if ($target_user_id > 0)
			{
				// Check exclude groups
				if (!empty($pg['exclude_groups_array']) && array_intersect($target_groups, $pg['exclude_groups_array']))
				{
					continue;
				}

				$has_power = false;
				if (!empty($pg['power_over_all']))
				{
					$has_power = true;
				}
				if (!$has_power && !empty($pg['power_over_self']) && $viewer_id === $target_user_id)
				{
					$has_power = true;
				}
				if (!$has_power && !empty($pg['power_over_groups_array']) && array_intersect($target_groups, $pg['power_over_groups_array']))
				{
					$has_power = true;
				}

				if ($has_power)
				{
					foreach (['view_profile', 'view_issued', 'view_visited_topics', 'view_visited_forums', 'view_visited_users', 'view_visited_profiles', 'view_disciplinary', 'view_ic_disciplinary', 'view_awards', 'view_career', 'view_commendations', 'view_gtaw'] as $k)
					{
						if (!empty($perms[$k]))
						{
							$effective[$k] = true;
						}
					}
				}
			}
			else
			{
				// Grant capability flags in general scope if enabled
				foreach (['view_profile', 'view_issued', 'view_visited_topics', 'view_visited_forums', 'view_visited_users', 'view_visited_profiles', 'view_disciplinary', 'view_ic_disciplinary', 'view_awards', 'view_career', 'view_commendations', 'view_gtaw'] as $k)
				{
					if (!empty($perms[$k]))
					{
						$effective[$k] = true;
					}
				}
			}
		}

		return $effective;
	}

	public function is_user_online($user_id)
	{
		$user_id = (int) $user_id;
		if ($user_id <= 0 || $user_id === ANONYMOUS)
		{
			return false;
		}

		$online_window = time() - ((int) $this->config['load_online_time'] * 60);
		$sql = 'SELECT 1 FROM ' . SESSIONS_TABLE . '
				WHERE session_user_id = ' . $user_id . '
				  AND session_time >= ' . $online_window . '
				  AND session_user_id <> ' . ANONYMOUS;
		$result = $this->db->sql_query_limit($sql, 1);
		$is_online = (bool) $this->db->sql_fetchfield('1');
		$this->db->sql_freeresult($result);
		return $is_online;
	}

	/* =========================================================================
	 * CROSS-EXTENSION ISSUE PERMISSION HELPERS
	 * ========================================================================= */

	public function can_issue_disciplinary($viewer_id, $target_user_id)
	{
		global $phpbb_container;
		if (!$this->is_ext_enabled('booskit/disciplinary'))
		{
			return false;
		}
		if ($phpbb_container !== null && $phpbb_container->has('booskit.disciplinary.service.disciplinary_manager'))
		{
			try {
				$mgr = $phpbb_container->get('booskit.disciplinary.service.disciplinary_manager');
				if (method_exists($mgr, 'can_add_disciplinary'))
				{
					return (bool) $mgr->can_add_disciplinary($viewer_id, $target_user_id);
				}
			} catch (\Throwable $e) {
				// fallback
			}
		}
		return $this->is_admin($viewer_id);
	}


	public function can_issue_award($viewer_id, $target_user_id)
	{
		global $phpbb_container;
		if (!$this->is_ext_enabled('booskit/awards'))
		{
			return false;
		}
		if ($phpbb_container !== null && $phpbb_container->has('booskit.awards.service.award_manager'))
		{
			try {
				$mgr = $phpbb_container->get('booskit.awards.service.award_manager');
				if (method_exists($mgr, 'can_add_award'))
				{
					return (bool) $mgr->can_add_award($viewer_id, $target_user_id);
				}
			} catch (\Throwable $e) {
				// fallback
			}
		}
		return $this->is_admin($viewer_id);
	}

	public function can_issue_career($viewer_id, $target_user_id)
	{
		global $phpbb_container;
		if (!$this->is_ext_enabled('booskit/usercareer'))
		{
			return false;
		}
		if ($phpbb_container !== null && $phpbb_container->has('booskit.usercareer.service.career_manager'))
		{
			try {
				$mgr = $phpbb_container->get('booskit.usercareer.service.career_manager');
				if (method_exists($mgr, 'can_add_career_note'))
				{
					return (bool) $mgr->can_add_career_note($viewer_id, $target_user_id);
				}
			} catch (\Throwable $e) {
				// fallback
			}
		}
		return $this->is_admin($viewer_id);
	}

	public function can_issue_commendation($viewer_id, $target_user_id)
	{
		global $phpbb_container;
		if (!$this->is_ext_enabled('booskit/commendations'))
		{
			return false;
		}
		if ($phpbb_container !== null && $phpbb_container->has('booskit.commendations.service.commendations_manager'))
		{
			try {
				$mgr = $phpbb_container->get('booskit.commendations.service.commendations_manager');
				if (method_exists($mgr, 'can_add_commendation'))
				{
					return (bool) $mgr->can_add_commendation($viewer_id, $target_user_id);
				}
			} catch (\Throwable $e) {
				// fallback
			}
		}
		return $this->is_admin($viewer_id);
	}

	public function can_view_dashboard($viewer_id)
	{
		$perms = $this->get_effective_permissions($viewer_id);
		return !empty($perms['view_dashboard']);
	}

	public function can_view_user_profile($viewer_id, $target_user_id)
	{
		$perms = $this->get_effective_permissions($viewer_id, $target_user_id);
		return !empty($perms['view_profile']);
	}

	public function can_view_issued_actions($viewer_id, $target_user_id)
	{
		$perms = $this->get_effective_permissions($viewer_id, $target_user_id);
		return !empty($perms['view_issued']);
	}

	public function can_view_recent_topics($viewer_id, $target_user_id)
	{
		$perms = $this->get_effective_permissions($viewer_id, $target_user_id);
		return !empty($perms['view_visited_topics']);
	}

	public function can_view_visited_forums($viewer_id, $target_user_id)
	{
		$perms = $this->get_effective_permissions($viewer_id, $target_user_id);
		return !empty($perms['view_visited_forums']);
	}

	public function can_view_visited_users($viewer_id, $target_user_id)
	{
		$perms = $this->get_effective_permissions($viewer_id, $target_user_id);
		return !empty($perms['view_visited_users']);
	}

	public function can_view_visited_profiles($viewer_id, $target_user_id)
	{
		$perms = $this->get_effective_permissions($viewer_id, $target_user_id);
		return !empty($perms['view_visited_profiles']);
	}

	/* Legacy permission helpers */
	protected function can_view_dashboard_legacy($viewer_id)
	{
		$raw = isset($this->config['booskit_dashboard_allowed_groups']) ? $this->config['booskit_dashboard_allowed_groups'] : '';
		if (empty($raw))
		{
			return true;
		}
		$allowed = array_map('intval', array_filter(array_map('trim', explode(',', $raw))));
		$groups = $this->get_user_groups($viewer_id);
		return (bool) array_intersect($groups, $allowed);
	}

	protected function can_view_user_profile_legacy($viewer_id, $target_user_id)
	{
		if ($viewer_id === $target_user_id)
		{
			return true;
		}
		if (!empty($this->config['booskit_dashboard_profile_admin_override']) && $this->is_admin($viewer_id))
		{
			return true;
		}
		$raw_map = isset($this->config['booskit_dashboard_group_profile_access']) ? trim($this->config['booskit_dashboard_group_profile_access']) : '';
		if (empty($raw_map))
		{
			return true;
		}

		$viewer_groups = $this->get_user_groups($viewer_id);
		$target_groups = $this->get_user_groups($target_user_id);

		$lines = preg_split('/[\r\n]+/', $raw_map);
		$allowed_targets = [];

		foreach ($lines as $line)
		{
			$parts = explode(':', trim($line));
			if (count($parts) === 2)
			{
				$v_gid = (int) trim($parts[0]);
				if (in_array($v_gid, $viewer_groups, true))
				{
					$targets = array_map('intval', array_filter(array_map('trim', explode(',', $parts[1]))));
					foreach ($targets as $t_gid)
					{
						$allowed_targets[$t_gid] = true;
					}
				}
			}
		}

		if (empty($allowed_targets))
		{
			return false;
		}

		foreach ($target_groups as $tg)
		{
			if (isset($allowed_targets[$tg]))
			{
				return true;
			}
		}

		return false;
	}

	protected function can_view_issued_actions_legacy($viewer_id, $target_user_id)
	{
		if ($viewer_id === $target_user_id && !empty($this->config['booskit_dashboard_issued_allow_self']))
		{
			return true;
		}
		if ($this->is_admin($viewer_id))
		{
			return true;
		}
		$raw = isset($this->config['booskit_dashboard_issued_groups']) ? $this->config['booskit_dashboard_issued_groups'] : '';
		if (empty($raw))
		{
			return false;
		}
		$allowed = array_map('intval', array_filter(array_map('trim', explode(',', $raw))));
		$viewer_groups = $this->get_user_groups($viewer_id);
		return (bool) array_intersect($viewer_groups, $allowed);
	}

	protected function can_view_recent_topics_legacy($viewer_id, $target_user_id)
	{
		if ($viewer_id === $target_user_id && !empty($this->config['booskit_dashboard_recent_topics_allow_self']))
		{
			return true;
		}
		if ($this->is_admin($viewer_id))
		{
			return true;
		}
		$raw = isset($this->config['booskit_dashboard_recent_topics_groups']) ? $this->config['booskit_dashboard_recent_topics_groups'] : '';
		if (empty($raw))
		{
			return false;
		}
		$allowed = array_map('intval', array_filter(array_map('trim', explode(',', $raw))));
		$viewer_groups = $this->get_user_groups($viewer_id);
		return (bool) array_intersect($viewer_groups, $allowed);
	}

	/* =========================================================================
	 * METRICS & STATISTICS
	 * ========================================================================= */

	public function get_group_metric_stats()
	{
		$configured_group = (int) (isset($this->config['booskit_dashboard_group_metric_group']) ? $this->config['booskit_dashboard_group_metric_group'] : 0);
		$custom_label = isset($this->config['booskit_dashboard_group_metric_label']) ? trim($this->config['booskit_dashboard_group_metric_label']) : '';

		$count = 0;
		$group_name = '';

		if ($configured_group > 0)
		{
			$sql = 'SELECT group_name, group_type FROM ' . GROUPS_TABLE . ' WHERE group_id = ' . $configured_group;
			$res = $this->db->sql_query($sql);
			$grow = $this->db->sql_fetchrow($res);
			$this->db->sql_freeresult($res);

			if ($grow)
			{
				global $user;
				$group_name = ($grow['group_type'] == GROUP_SPECIAL && isset($user->lang['G_' . $grow['group_name']])) ? $user->lang['G_' . $grow['group_name']] : $grow['group_name'];
			}

			$sql = 'SELECT COUNT(ug.user_id) as cnt
					FROM ' . USER_GROUP_TABLE . ' ug
					JOIN ' . USERS_TABLE . ' u ON ug.user_id = u.user_id
					WHERE ug.group_id = ' . $configured_group . '
					  AND ug.user_pending = 0
					  AND u.user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ')';
			$res = $this->db->sql_query($sql);
			$count = (int) $this->db->sql_fetchfield('cnt');
			$this->db->sql_freeresult($res);
		}
		else
		{
			$count = (int) (isset($this->config['num_users']) ? $this->config['num_users'] : 0);
		}

		$label = !empty($custom_label) ? $custom_label : (!empty($group_name) ? $group_name : 'Total Members');

		return [
			'value'    => $count,
			'label'    => $label,
			'group_id' => $configured_group,
		];
	}

	public function get_total_actions()
	{
		$total = 0;

		// Disciplinary records
		if ($this->is_ext_enabled('booskit/disciplinary'))
		{
			try
			{
				$table = $this->table_prefix . 'booskit_disciplinary_users';
				$sql = 'SELECT COUNT(*) as cnt FROM ' . $table;
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					$total += (int) $this->db->sql_fetchfield('cnt');
					$this->db->sql_freeresult($res);
				}
			}
			catch (\Exception $e) {}
		}

		// IC Disciplinary records
		if ($this->is_ext_enabled('booskit/icdisciplinary'))
		{
			try
			{
				$table = $this->table_prefix . 'booskit_ic_records';
				$sql = 'SELECT COUNT(*) as cnt FROM ' . $table;
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					$total += (int) $this->db->sql_fetchfield('cnt');
					$this->db->sql_freeresult($res);
				}
			}
			catch (\Exception $e) {}
		}

		// Commendations
		if ($this->is_ext_enabled('booskit/commendations'))
		{
			try
			{
				$table = $this->table_prefix . 'booskit_commendations';
				$sql = 'SELECT COUNT(*) as cnt FROM ' . $table;
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					$total += (int) $this->db->sql_fetchfield('cnt');
					$this->db->sql_freeresult($res);
				}
			}
			catch (\Exception $e) {}
		}

		// Awards
		if ($this->is_ext_enabled('booskit/awards'))
		{
			try
			{
				$table = $this->table_prefix . 'booskit_awards_users';
				$sql = 'SELECT COUNT(*) as cnt FROM ' . $table;
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					$total += (int) $this->db->sql_fetchfield('cnt');
					$this->db->sql_freeresult($res);
				}
			}
			catch (\Exception $e) {}
		}

		// Career notes
		if ($this->is_ext_enabled('booskit/usercareer'))
		{
			try
			{
				$table = $this->table_prefix . 'booskit_career_notes';
				$sql = 'SELECT COUNT(*) as cnt FROM ' . $table;
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					$total += (int) $this->db->sql_fetchfield('cnt');
					$this->db->sql_freeresult($res);
				}
			}
			catch (\Exception $e) {}
		}

		// GTAW OAuth tokens
		if ($this->is_ext_enabled('booskit/gtawoauth'))
		{
			try
			{
				$table = $this->table_prefix . 'booskit_oauth_tokens';
				$sql = 'SELECT COUNT(*) as cnt FROM ' . $table;
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					$total += (int) $this->db->sql_fetchfield('cnt');
					$this->db->sql_freeresult($res);
				}
			}
			catch (\Exception $e) {}
		}

		return $total;
	}

	public function get_overview_stats()
	{
		$group_metric = $this->get_group_metric_stats();
		$total_actions = $this->get_total_actions();

		$stats = [
			'group_metric_value' => $group_metric['value'],
			'group_metric_label' => $group_metric['label'],
			'total_actions'      => $total_actions,
			'total_users'        => (int) (isset($this->config['num_users']) ? $this->config['num_users'] : 0),
			'total_topics'       => (int) (isset($this->config['num_topics']) ? $this->config['num_topics'] : 0),
			'total_posts'        => (int) (isset($this->config['num_posts']) ? $this->config['num_posts'] : 0),
			'newest_user_id'     => (int) (isset($this->config['newest_user_id']) ? $this->config['newest_user_id'] : 0),
			'newest_username'    => isset($this->config['newest_username']) ? $this->config['newest_username'] : '',
			'newest_user_colour' => isset($this->config['newest_user_colour']) ? $this->config['newest_user_colour'] : '',
			'active_today'       => 0,
			'online_registered'  => 0,
			'online_guests'      => 0,
		];

		// Active in past 24 hours
		$past_24h = time() - 86400;
		$sql = 'SELECT COUNT(user_id) as cnt FROM ' . USERS_TABLE . ' WHERE user_lastvisit >= ' . $past_24h . ' AND user_type <> ' . USER_IGNORE;
		$res = $this->db->sql_query($sql);
		$stats['active_today'] = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($res);

		// Currently active in session window
		$online_window = time() - ((int) $this->config['load_online_time'] * 60);

		$sql = 'SELECT COUNT(DISTINCT session_user_id) as cnt FROM ' . SESSIONS_TABLE . ' WHERE session_time >= ' . $online_window . ' AND session_user_id <> ' . ANONYMOUS;
		$res = $this->db->sql_query($sql);
		$stats['online_registered'] = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($res);

		$sql = 'SELECT COUNT(DISTINCT session_ip) as cnt FROM ' . SESSIONS_TABLE . ' WHERE session_time >= ' . $online_window . ' AND session_user_id = ' . ANONYMOUS;
		$res = $this->db->sql_query($sql);
		$stats['online_guests'] = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($res);

		return $stats;
	}

	/* =========================================================================
	 * AVATAR / GROUP LOGO / INITIAL FALLBACK
	 * ========================================================================= */

	public function get_user_avatar_or_group_or_initial($user_row, $username = '', $group_id = 0)
	{
		$username = !empty($username) ? $username : (isset($user_row['username']) ? $user_row['username'] : '');
		$group_id = (int) ($group_id ?: (isset($user_row['group_id']) ? $user_row['group_id'] : 0));

		// 1. Check if group has a group avatar/logo
		if ($group_id > 0)
		{
			$group_avatar = $this->get_group_avatar_html($group_id);
			if (!empty($group_avatar))
			{
				return $group_avatar;
			}
		}

		// 2. Fallback: First character of username in styled circle
		$first_char = '';
		if (!empty($username))
		{
			$clean = trim($username);
			$first_char = mb_strtoupper(mb_substr($clean, 0, 1, 'UTF-8'), 'UTF-8');
		}
		if (empty($first_char))
		{
			$first_char = '?';
		}

		$user_colour = isset($user_row['user_colour']) ? trim($user_row['user_colour']) : '';
		$bg_color = !empty($user_colour) ? '#' . ltrim($user_colour, '#') : $this->get_initial_color($username);

		return '<span class="dash-avatar-initial" style="background-color: ' . htmlspecialchars($bg_color, ENT_QUOTES, 'UTF-8') . ';" title="' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($first_char, ENT_QUOTES, 'UTF-8') . '</span>';
	}

	public function get_group_avatar_html($group_id)
	{
		$group_id = (int) $group_id;
		if ($group_id <= 0)
		{
			return '';
		}

		if ($this->group_avatars_cache === null)
		{
			$this->group_avatars_cache = [];
			$sql = 'SELECT group_id, group_avatar, group_avatar_type, group_avatar_width, group_avatar_height FROM ' . GROUPS_TABLE;
			$res = @$this->db->sql_query($sql);
			if ($res)
			{
				while ($row = $this->db->sql_fetchrow($res))
				{
					$this->group_avatars_cache[(int) $row['group_id']] = $row;
				}
				$this->db->sql_freeresult($res);
			}
		}

		if (isset($this->group_avatars_cache[$group_id]))
		{
			$grow = $this->group_avatars_cache[$group_id];
			if (!empty($grow['group_avatar']))
			{
				return phpbb_get_group_avatar($grow);
			}
		}

		return '';
	}

	public function get_initial_color($str)
	{
		$colors = ['#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed', '#db2777', '#0891b2', '#4f46e5', '#0d9488', '#ea580c'];
		$idx = abs(crc32((string) $str)) % count($colors);
		return $colors[$idx];
	}

	/* =========================================================================
	 * ACTIVE USERS & BROWSING
	 * ========================================================================= */

	public function get_active_users_browsing($viewer_id, $limit = 40)
	{
		$online_window = time() - ((int) $this->config['load_online_time'] * 60);

		$sql = 'SELECT s.session_id, s.session_user_id, s.session_time, s.session_page, s.session_forum_id, s.session_viewonline,
				       u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.group_id
				FROM ' . SESSIONS_TABLE . ' s
				JOIN ' . USERS_TABLE . ' u ON s.session_user_id = u.user_id
				WHERE s.session_time >= ' . $online_window . '
				  AND s.session_user_id <> ' . ANONYMOUS . '
				ORDER BY s.session_time DESC';
		$result = $this->db->sql_query($sql);

		$users = [];
		$seen_users = [];
		$topic_ids_to_fetch = [];
		$forum_ids_to_fetch = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$uid = (int) $row['user_id'];
			if (isset($seen_users[$uid]))
			{
				continue;
			}
			$seen_users[$uid] = true;

			$topic_id = 0;
			$forum_id = (int) $row['session_forum_id'];

			if (preg_match('/[?&]t=([0-9]+)/', $row['session_page'], $m))
			{
				$topic_id = (int) $m[1];
				$topic_ids_to_fetch[$topic_id] = true;
			}
			if ($forum_id > 0)
			{
				$forum_ids_to_fetch[$forum_id] = true;
			}

			$row['parsed_topic_id'] = $topic_id;
			$row['parsed_forum_id'] = $forum_id;
			$users[] = $row;

			if (count($users) >= $limit)
			{
				break;
			}
		}
		$this->db->sql_freeresult($result);

		$topics_info = [];
		if (!empty($topic_ids_to_fetch))
		{
			$sql = 'SELECT topic_id, forum_id, topic_title FROM ' . TOPICS_TABLE . ' WHERE ' . $this->db->sql_in_set('topic_id', array_keys($topic_ids_to_fetch));
			$res = $this->db->sql_query($sql);
			while ($trow = $this->db->sql_fetchrow($res))
			{
				$topics_info[(int) $trow['topic_id']] = $trow;
				$forum_ids_to_fetch[(int) $trow['forum_id']] = true;
			}
			$this->db->sql_freeresult($res);
		}

		$forums_info = [];
		if (!empty($forum_ids_to_fetch))
		{
			$sql = 'SELECT forum_id, forum_name FROM ' . FORUMS_TABLE . ' WHERE ' . $this->db->sql_in_set('forum_id', array_keys($forum_ids_to_fetch));
			$res = $this->db->sql_query($sql);
			while ($frow = $this->db->sql_fetchrow($res))
			{
				$forums_info[(int) $frow['forum_id']] = $frow['forum_name'];
			}
			$this->db->sql_freeresult($res);
		}

		$final_users = [];
		foreach ($users as $u)
		{
			$browsing_label = 'Browsing forum';
			$browsing_url = '';

			if (!empty($u['parsed_topic_id']) && isset($topics_info[$u['parsed_topic_id']]))
			{
				$tinfo = $topics_info[$u['parsed_topic_id']];
				$fid = (int) $tinfo['forum_id'];
				if ($this->auth === null || $this->auth->acl_get('f_read', $fid))
				{
					$browsing_label = 'Viewing: ' . $tinfo['topic_title'];
					$browsing_url = 'viewtopic.php?t=' . $u['parsed_topic_id'];
				}
			}
			else if (!empty($u['parsed_forum_id']) && isset($forums_info[$u['parsed_forum_id']]))
			{
				$fid = (int) $u['parsed_forum_id'];
				if ($this->auth === null || $this->auth->acl_get('f_read', $fid))
				{
					$browsing_label = 'In: ' . $forums_info[$fid];
					$browsing_url = 'viewforum.php?f=' . $fid;
				}
			}
			else if (strpos($u['session_page'], 'app.php/dashboard') !== false)
			{
				$browsing_label = 'Viewing Dashboard';
			}
			else if (strpos($u['session_page'], 'ucp.php') !== false)
			{
				$browsing_label = 'User Control Panel';
			}
			else if (strpos($u['session_page'], 'memberlist.php') !== false)
			{
				$browsing_label = 'Memberlist';
			}

			$can_view_prof = $this->can_view_user_profile($viewer_id, $u['user_id']);
			$avatar_html = $this->get_user_avatar_or_group_or_initial($u, $u['username'], $u['group_id']);

			$final_users[] = [
				'user_id'          => (int) $u['user_id'],
				'username'         => $u['username'],
				'user_colour'      => $u['user_colour'],
				'avatar_html'      => $avatar_html,
				'time_ago'         => $this->format_time_ago($u['session_time']),
				'browsing_label'   => $browsing_label,
				'browsing_url'     => $browsing_url,
				'can_view_profile' => $can_view_prof,
			];
		}

		return $final_users;
	}

	/* =========================================================================
	 * HOT TOPICS
	 * ========================================================================= */

	public function get_hot_topics($viewer_id, $period = 'day', $limit = 10)
	{
		$now = time();
		switch ($period)
		{
			case 'week':
				$since = $now - (7 * 86400);
				break;
			case 'month':
				$since = $now - (30 * 86400);
				break;
			case 'day':
			default:
				$since = $now - 86400;
				break;
		}

		$sql = 'SELECT p.topic_id, COUNT(p.post_id) as period_posts
				FROM ' . POSTS_TABLE . ' p
				WHERE p.post_time >= ' . $since . '
				  AND p.post_visibility = ' . ITEM_APPROVED . '
				GROUP BY p.topic_id
				ORDER BY period_posts DESC';
		$res = $this->db->sql_query_limit($sql, $limit * 3);

		$topic_counts = [];
		while ($row = $this->db->sql_fetchrow($res))
		{
			$topic_counts[(int) $row['topic_id']] = (int) $row['period_posts'];
		}
		$this->db->sql_freeresult($res);

		if (empty($topic_counts))
		{
			return [];
		}

		$sql = 'SELECT t.topic_id, t.forum_id, t.topic_title, t.topic_poster, t.topic_first_poster_name,
				       t.topic_first_poster_colour, t.topic_last_post_time, t.topic_last_poster_name,
				       t.topic_last_poster_colour, t.topic_views, t.topic_posts_approved,
				       f.forum_name
				FROM ' . TOPICS_TABLE . ' t
				JOIN ' . FORUMS_TABLE . ' f ON t.forum_id = f.forum_id
				WHERE ' . $this->db->sql_in_set('t.topic_id', array_keys($topic_counts)) . '
				  AND t.topic_visibility = ' . ITEM_APPROVED;
		$res = $this->db->sql_query($sql);

		$hot_topics = [];
		while ($row = $this->db->sql_fetchrow($res))
		{
			$fid = (int) $row['forum_id'];
			if ($this->auth !== null && !$this->auth->acl_get('f_read', $fid))
			{
				continue;
			}
			$tid = (int) $row['topic_id'];
			$row['period_posts'] = isset($topic_counts[$tid]) ? $topic_counts[$tid] : 0;
			$hot_topics[] = $row;
		}
		$this->db->sql_freeresult($res);

		usort($hot_topics, function ($a, $b) {
			if ($a['period_posts'] === $b['period_posts'])
			{
				return $b['topic_last_post_time'] <=> $a['topic_last_post_time'];
			}
			return $b['period_posts'] <=> $a['period_posts'];
		});

		return array_slice($hot_topics, 0, $limit);
	}

	/* =========================================================================
	 * VISITED TRACKING & PAGINATION (TOPICS, FORUMS, USERS, PROFILES)
	 * ========================================================================= */

	protected function ensure_view_time_schema()
	{
		static $checked = false;
		if ($checked)
		{
			return;
		}
		$checked = true;

		$tables = [
			'booskit_dashboard_topic_views',
			'booskit_dashboard_forum_views',
			'booskit_dashboard_user_views',
			'booskit_dashboard_profile_views',
		];

		foreach ($tables as $t)
		{
			$full_table = $this->table_prefix . $t;
			try
			{
				@$this->db->sql_query('ALTER TABLE ' . $full_table . ' MODIFY view_time INT(11) UNSIGNED NOT NULL DEFAULT 0');
			}
			catch (\Exception $e)
			{
				// Ignore if already modified or restricted
			}
		}
	}

	public function log_topic_view($user_id, $topic_id, $forum_id)
	{
		$user_id = (int) $user_id;
		$topic_id = (int) $topic_id;
		$forum_id = (int) $forum_id;

		if ($user_id <= 0 || $topic_id <= 0)
		{
			return;
		}

		$this->ensure_view_time_schema();
		$now = time();
		$table = $this->table_prefix . 'booskit_dashboard_topic_views';

		try
		{
			$sql = 'SELECT view_id, view_time FROM ' . $table . ' WHERE user_id = ' . $user_id . ' AND topic_id = ' . $topic_id;
			$res = @$this->db->sql_query($sql);
			$row = $res ? $this->db->sql_fetchrow($res) : null;
			if ($res) { $this->db->sql_freeresult($res); }

			if ($row)
			{
				if ($now - (int)$row['view_time'] > 60)
				{
					$sql = 'UPDATE ' . $table . ' SET view_time = ' . $now . ', forum_id = ' . $forum_id . ' WHERE view_id = ' . (int)$row['view_id'];
					@$this->db->sql_query($sql);
				}
			}
			else
			{
				$sql_ary = [
					'user_id'   => $user_id,
					'topic_id'  => $topic_id,
					'forum_id'  => $forum_id,
					'view_time' => $now,
				];
				@$this->db->sql_query('INSERT INTO ' . $table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
			}
		}
		catch (\Exception $e)
		{
			// Safe fallback
		}
	}

	public function log_forum_view($user_id, $forum_id)
	{
		$user_id = (int) $user_id;
		$forum_id = (int) $forum_id;
		if ($user_id <= 0 || $forum_id <= 0) { return; }

		$this->ensure_view_time_schema();
		$now = time();
		$table = $this->table_prefix . 'booskit_dashboard_forum_views';

		try
		{
			$sql = 'SELECT view_id, view_time, view_count FROM ' . $table . ' WHERE user_id = ' . $user_id . ' AND forum_id = ' . $forum_id;
			$res = @$this->db->sql_query($sql);
			$row = $res ? $this->db->sql_fetchrow($res) : null;
			if ($res) { $this->db->sql_freeresult($res); }

			if ($row)
			{
				if ($now - (int)$row['view_time'] > 60)
				{
					$sql = 'UPDATE ' . $table . ' SET view_time = ' . $now . ', view_count = view_count + 1 WHERE view_id = ' . (int)$row['view_id'];
					@$this->db->sql_query($sql);
				}
			}
			else
			{
				$sql_ary = [
					'user_id'    => $user_id,
					'forum_id'   => $forum_id,
					'view_time'  => $now,
					'view_count' => 1,
				];
				@$this->db->sql_query('INSERT INTO ' . $table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
			}
		}
		catch (\Exception $e)
		{
			// Safe fallback
		}
	}

	public function log_user_view($user_id, $viewed_user_id)
	{
		$user_id = (int) $user_id;
		$viewed_user_id = (int) $viewed_user_id;
		if ($user_id <= 0 || $viewed_user_id <= 0 || $user_id === $viewed_user_id) { return; }

		$this->ensure_view_time_schema();
		$now = time();
		$table = $this->table_prefix . 'booskit_dashboard_user_views';

		try
		{
			$sql = 'SELECT view_id, view_time, view_count FROM ' . $table . ' WHERE user_id = ' . $user_id . ' AND viewed_user_id = ' . $viewed_user_id;
			$res = @$this->db->sql_query($sql);
			$row = $res ? $this->db->sql_fetchrow($res) : null;
			if ($res) { $this->db->sql_freeresult($res); }

			if ($row)
			{
				if ($now - (int)$row['view_time'] > 60)
				{
					$sql = 'UPDATE ' . $table . ' SET view_time = ' . $now . ', view_count = view_count + 1 WHERE view_id = ' . (int)$row['view_id'];
					@$this->db->sql_query($sql);
				}
			}
			else
			{
				$sql_ary = [
					'user_id'        => $user_id,
					'viewed_user_id' => $viewed_user_id,
					'view_time'      => $now,
					'view_count'     => 1,
				];
				@$this->db->sql_query('INSERT INTO ' . $table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
			}
		}
		catch (\Exception $e)
		{
			// Safe fallback
		}
	}

	public function log_dashboard_profile_view($user_id, $viewed_user_id)
	{
		$user_id = (int) $user_id;
		$viewed_user_id = (int) $viewed_user_id;
		if ($user_id <= 0 || $viewed_user_id <= 0 || $user_id === $viewed_user_id) { return; }

		$this->ensure_view_time_schema();
		$now = time();
		$table = $this->table_prefix . 'booskit_dashboard_profile_views';

		try
		{
			$sql = 'SELECT view_id, view_time, view_count FROM ' . $table . ' WHERE user_id = ' . $user_id . ' AND viewed_user_id = ' . $viewed_user_id;
			$res = @$this->db->sql_query($sql);
			$row = $res ? $this->db->sql_fetchrow($res) : null;
			if ($res) { $this->db->sql_freeresult($res); }

			if ($row)
			{
				if ($now - (int)$row['view_time'] > 60)
				{
					$sql = 'UPDATE ' . $table . ' SET view_time = ' . $now . ', view_count = view_count + 1 WHERE view_id = ' . (int)$row['view_id'];
					@$this->db->sql_query($sql);
				}
			}
			else
			{
				$sql_ary = [
					'user_id'        => $user_id,
					'viewed_user_id' => $viewed_user_id,
					'view_time'      => $now,
					'view_count'     => 1,
				];
				@$this->db->sql_query('INSERT INTO ' . $table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
			}
		}
		catch (\Exception $e)
		{
			// Safe fallback
		}
	}

	/* Visited Topics */
	public function sync_historical_views($target_user_id)
	{
		$target_user_id = (int) $target_user_id;
		if ($target_user_id <= 0)
		{
			return;
		}

		$this->ensure_view_time_schema();

		$topics_track = defined('TOPICS_TRACK_TABLE') ? TOPICS_TRACK_TABLE : $this->table_prefix . 'topics_track';
		$forums_track = defined('FORUMS_TRACK_TABLE') ? FORUMS_TRACK_TABLE : $this->table_prefix . 'forums_track';
		$log_table    = defined('LOG_TABLE') ? LOG_TABLE : $this->table_prefix . 'log';

		// 1. Backfill topic views from phpbb_topics_track
		try
		{
			$sql = 'INSERT INTO ' . $this->table_prefix . 'booskit_dashboard_topic_views (user_id, topic_id, forum_id, view_time)
					SELECT tt.user_id, tt.topic_id, tt.forum_id, tt.mark_time
					FROM ' . $topics_track . ' tt
					WHERE tt.user_id = ' . $target_user_id . '
					  AND NOT EXISTS (
						  SELECT 1 FROM ' . $this->table_prefix . 'booskit_dashboard_topic_views dt
						  WHERE dt.user_id = tt.user_id AND dt.topic_id = tt.topic_id
					  )';
			@$this->db->sql_query($sql);
		}
		catch (\Exception $e) {}

		// 2. Backfill topic views from phpbb_log (e.g. topiclogviews or moderator log)
		try
		{
			$sql = 'INSERT INTO ' . $this->table_prefix . 'booskit_dashboard_topic_views (user_id, topic_id, forum_id, view_time)
					SELECT l.user_id, l.topic_id, l.forum_id, MAX(l.log_time)
					FROM ' . $log_table . ' l
					WHERE l.user_id = ' . $target_user_id . ' AND l.topic_id > 0
					  AND NOT EXISTS (
						  SELECT 1 FROM ' . $this->table_prefix . 'booskit_dashboard_topic_views dt
						  WHERE dt.user_id = l.user_id AND dt.topic_id = l.topic_id
					  )
					GROUP BY l.user_id, l.topic_id, l.forum_id';
			@$this->db->sql_query($sql);
		}
		catch (\Exception $e) {}

		// 3. Backfill forum views from phpbb_forums_track
		try
		{
			$sql = 'INSERT INTO ' . $this->table_prefix . 'booskit_dashboard_forum_views (user_id, forum_id, view_time, view_count)
					SELECT ft.user_id, ft.forum_id, ft.mark_time, 1
					FROM ' . $forums_track . ' ft
					WHERE ft.user_id = ' . $target_user_id . '
					  AND NOT EXISTS (
						  SELECT 1 FROM ' . $this->table_prefix . 'booskit_dashboard_forum_views df
						  WHERE df.user_id = ft.user_id AND df.forum_id = ft.forum_id
					  )';
			@$this->db->sql_query($sql);
		}
		catch (\Exception $e) {}

		// 4. Backfill forum views from topics track (if forum views not yet recorded)
		try
		{
			$sql = 'INSERT INTO ' . $this->table_prefix . 'booskit_dashboard_forum_views (user_id, forum_id, view_time, view_count)
					SELECT tt.user_id, tt.forum_id, MAX(tt.mark_time), COUNT(tt.topic_id)
					FROM ' . $topics_track . ' tt
					WHERE tt.user_id = ' . $target_user_id . ' AND tt.forum_id > 0
					  AND NOT EXISTS (
						  SELECT 1 FROM ' . $this->table_prefix . 'booskit_dashboard_forum_views df
						  WHERE df.user_id = tt.user_id AND df.forum_id = tt.forum_id
					  )
					GROUP BY tt.user_id, tt.forum_id';
			@$this->db->sql_query($sql);
		}
		catch (\Exception $e) {}
	}

	public function get_user_visited_topics_count($viewer_id, $target_user_id)
	{
		if (!$this->can_view_recent_topics($viewer_id, $target_user_id)) { return 0; }
		$this->sync_historical_views($target_user_id);
		$table = $this->table_prefix . 'booskit_dashboard_topic_views';
		$sql = 'SELECT COUNT(view_id) as cnt FROM ' . $table . ' WHERE user_id = ' . (int)$target_user_id;
		$res = @$this->db->sql_query($sql);
		$cnt = $res ? (int)$this->db->sql_fetchfield('cnt') : 0;
		if ($res) { $this->db->sql_freeresult($res); }
		return $cnt;
	}

	public function get_user_visited_topics($viewer_id, $target_user_id, $start = 0, $limit = 30)
	{
		if (!$this->can_view_recent_topics($viewer_id, $target_user_id)) { return []; }
		$this->sync_historical_views($target_user_id);

		$table = $this->table_prefix . 'booskit_dashboard_topic_views';
		$sql = 'SELECT v.topic_id, v.forum_id, v.view_time,
				       t.topic_title, t.topic_poster, t.topic_first_poster_name, t.topic_first_poster_colour,
				       f.forum_name
				FROM ' . $table . ' v
				JOIN ' . TOPICS_TABLE . ' t ON v.topic_id = t.topic_id
				JOIN ' . FORUMS_TABLE . ' f ON t.forum_id = f.forum_id
				WHERE v.user_id = ' . (int)$target_user_id . '
				ORDER BY v.view_time DESC';
		$res = @$this->db->sql_query_limit($sql, $limit, $start);

		$topics = [];
		if ($res)
		{
			while ($row = $this->db->sql_fetchrow($res))
			{
				$fid = (int) $row['forum_id'];
				if ($this->auth !== null && !$this->auth->acl_get('f_read', $fid))
				{
					continue;
				}
				$topics[] = $row;
			}
			$this->db->sql_freeresult($res);
		}

		return $topics;
	}

	/* Visited Forums */
	public function get_user_visited_forums_count($viewer_id, $target_user_id)
	{
		if (!$this->can_view_visited_forums($viewer_id, $target_user_id)) { return 0; }
		$this->sync_historical_views($target_user_id);
		$table = $this->table_prefix . 'booskit_dashboard_forum_views';
		$sql = 'SELECT COUNT(view_id) as cnt FROM ' . $table . ' WHERE user_id = ' . (int)$target_user_id;
		$res = @$this->db->sql_query($sql);
		$cnt = $res ? (int)$this->db->sql_fetchfield('cnt') : 0;
		if ($res) { $this->db->sql_freeresult($res); }
		return $cnt;
	}

	public function get_user_visited_forums($viewer_id, $target_user_id, $start = 0, $limit = 30)
	{
		if (!$this->can_view_visited_forums($viewer_id, $target_user_id)) { return []; }
		$this->sync_historical_views($target_user_id);

		$table = $this->table_prefix . 'booskit_dashboard_forum_views';
		$sql = 'SELECT v.forum_id, v.view_time, v.view_count, f.forum_name, f.forum_desc
				FROM ' . $table . ' v
				JOIN ' . FORUMS_TABLE . ' f ON v.forum_id = f.forum_id
				WHERE v.user_id = ' . (int)$target_user_id . '
				ORDER BY v.view_time DESC';
		$res = @$this->db->sql_query_limit($sql, $limit, $start);

		$forums = [];
		if ($res)
		{
			while ($row = $this->db->sql_fetchrow($res))
			{
				$fid = (int) $row['forum_id'];
				if ($this->auth !== null && !$this->auth->acl_get('f_read', $fid))
				{
					continue;
				}
				$forums[] = $row;
			}
			$this->db->sql_freeresult($res);
		}

		return $forums;
	}

	/* Visited Users (Memberlist views) */
	public function get_user_visited_users_count($viewer_id, $target_user_id)
	{
		if (!$this->can_view_visited_users($viewer_id, $target_user_id)) { return 0; }
		$table = $this->table_prefix . 'booskit_dashboard_user_views';
		$sql = 'SELECT COUNT(view_id) as cnt FROM ' . $table . ' WHERE user_id = ' . (int)$target_user_id;
		$res = @$this->db->sql_query($sql);
		$cnt = $res ? (int)$this->db->sql_fetchfield('cnt') : 0;
		if ($res) { $this->db->sql_freeresult($res); }
		return $cnt;
	}

	public function get_user_visited_users($viewer_id, $target_user_id, $start = 0, $limit = 30)
	{
		if (!$this->can_view_visited_users($viewer_id, $target_user_id)) { return []; }

		$table = $this->table_prefix . 'booskit_dashboard_user_views';
		$sql = 'SELECT v.viewed_user_id, v.view_time, v.view_count,
				       u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.group_id
				FROM ' . $table . ' v
				JOIN ' . USERS_TABLE . ' u ON v.viewed_user_id = u.user_id
				WHERE v.user_id = ' . (int)$target_user_id . '
				ORDER BY v.view_time DESC';
		$res = @$this->db->sql_query_limit($sql, $limit, $start);

		$users = [];
		if ($res)
		{
			while ($row = $this->db->sql_fetchrow($res))
			{
				$row['avatar_html'] = $this->get_user_avatar_or_group_or_initial($row, $row['username'], $row['group_id']);
				$row['can_view_profile'] = $this->can_view_user_profile($viewer_id, $row['user_id']);
				$users[] = $row;
			}
			$this->db->sql_freeresult($res);
		}

		return $users;
	}

	/* Visited Dashboard Profiles */
	public function get_user_visited_profiles_count($viewer_id, $target_user_id)
	{
		if (!$this->can_view_visited_profiles($viewer_id, $target_user_id)) { return 0; }
		$table = $this->table_prefix . 'booskit_dashboard_profile_views';
		$sql = 'SELECT COUNT(view_id) as cnt FROM ' . $table . ' WHERE user_id = ' . (int)$target_user_id;
		$res = @$this->db->sql_query($sql);
		$cnt = $res ? (int)$this->db->sql_fetchfield('cnt') : 0;
		if ($res) { $this->db->sql_freeresult($res); }
		return $cnt;
	}

	public function get_user_visited_profiles($viewer_id, $target_user_id, $start = 0, $limit = 30)
	{
		if (!$this->can_view_visited_profiles($viewer_id, $target_user_id)) { return []; }

		$table = $this->table_prefix . 'booskit_dashboard_profile_views';
		$sql = 'SELECT v.viewed_user_id, v.view_time, v.view_count,
				       u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.group_id
				FROM ' . $table . ' v
				JOIN ' . USERS_TABLE . ' u ON v.viewed_user_id = u.user_id
				WHERE v.user_id = ' . (int)$target_user_id . '
				ORDER BY v.view_time DESC';
		$res = @$this->db->sql_query_limit($sql, $limit, $start);

		$profiles = [];
		if ($res)
		{
			while ($row = $this->db->sql_fetchrow($res))
			{
				$row['avatar_html'] = $this->get_user_avatar_or_group_or_initial($row, $row['username'], $row['group_id']);
				$row['can_view_profile'] = $this->can_view_user_profile($viewer_id, $row['user_id']);
				$profiles[] = $row;
			}
			$this->db->sql_freeresult($res);
		}

		return $profiles;
	}

	public function get_recent_dashboard_profile_views($viewer_id, $limit = 15)
	{
		$table = $this->table_prefix . 'booskit_dashboard_profile_views';
		$sql = 'SELECT v.user_id as viewer_user_id, v.viewed_user_id, v.view_time,
				       u1.username as viewer_username, u1.user_colour as viewer_colour, u1.group_id as viewer_group_id,
				       u2.username as viewed_username, u2.user_colour as viewed_colour, u2.group_id as viewed_group_id
				FROM ' . $table . ' v
				JOIN ' . USERS_TABLE . ' u1 ON v.user_id = u1.user_id
				JOIN ' . USERS_TABLE . ' u2 ON v.viewed_user_id = u2.user_id
				ORDER BY v.view_time DESC';
		$res = @$this->db->sql_query_limit($sql, $limit);

		$items = [];
		if ($res)
		{
			while ($row = $this->db->sql_fetchrow($res))
			{
				$row['viewer_avatar_html'] = $this->get_user_avatar_or_group_or_initial($row, $row['viewer_username'], $row['viewer_group_id']);
				$row['viewed_avatar_html'] = $this->get_user_avatar_or_group_or_initial($row, $row['viewed_username'], $row['viewed_group_id']);
				$row['can_view_target_profile'] = $this->can_view_user_profile($viewer_id, $row['viewed_user_id']);
				$row['time_ago'] = $this->format_time_ago($row['view_time']);
				$items[] = $row;
			}
			$this->db->sql_freeresult($res);
		}
		return $items;
	}

	/* Backward compatible wrapper */
	public function get_user_recent_topics($viewer_id, $target_user_id, $limit = 30)
	{
		return $this->get_user_visited_topics($viewer_id, $target_user_id, 0, $limit);
	}

	/* =========================================================================
	 * ISSUED ACTIONS
	 * ========================================================================= */

	public function get_user_issued_actions($viewer_id, $target_user_id)
	{
		if (!$this->can_view_issued_actions($viewer_id, $target_user_id))
		{
			return ['disciplinary' => [], 'ic_disciplinary' => [], 'commendations' => [], 'awards' => []];
		}

		$target_user_id = (int) $target_user_id;
		$issued = [
			'disciplinary'    => [],
			'ic_disciplinary' => [],
			'commendations'   => [],
			'awards'          => [],
		];

		// Disciplinary issued
		if ($this->is_ext_enabled('booskit/disciplinary'))
		{
			$disc_defs = $this->get_definitions('booskit/disciplinary');
			$sql = 'SELECT d.*, u.user_id, u.username, u.user_colour
					FROM ' . $this->table_prefix . 'booskit_disciplinary_users d
					JOIN ' . USERS_TABLE . ' u ON d.user_id = u.user_id
					WHERE d.issuer_user_id = ' . $target_user_id . '
					ORDER BY d.issue_date DESC';
			$res = @$this->db->sql_query_limit($sql, 30);
			if ($res)
			{
				while ($row = $this->db->sql_fetchrow($res))
				{
					$row['type_name'] = $this->get_definition_name('booskit/disciplinary', $row['disciplinary_type_id'], $disc_defs);
					$issued['disciplinary'][] = $row;
				}
				$this->db->sql_freeresult($res);
			}
		}

		// IC Disciplinary issued (Fix: explicitly select u.user_id)
		if ($this->is_ext_enabled('booskit/icdisciplinary'))
		{
			$ic_defs = $this->get_definitions('booskit/icdisciplinary');
			$sql = 'SELECT r.*, c.character_name, u.user_id, u.username, u.user_colour
					FROM ' . $this->table_prefix . 'booskit_ic_records r
					JOIN ' . $this->table_prefix . 'booskit_ic_characters c ON r.character_id = c.character_id
					JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
					WHERE r.issuer_user_id = ' . $target_user_id . '
					ORDER BY r.issue_date DESC';
			$res = @$this->db->sql_query_limit($sql, 30);
			if ($res)
			{
				while ($row = $this->db->sql_fetchrow($res))
				{
					$row['type_name'] = $this->get_definition_name('booskit/icdisciplinary', $row['disciplinary_type_id'], $ic_defs);
					$issued['ic_disciplinary'][] = $row;
				}
				$this->db->sql_freeresult($res);
			}
		}

		// Commendations issued
		if ($this->is_ext_enabled('booskit/commendations'))
		{
			$sql = 'SELECT c.*, u.user_id, u.username, u.user_colour
					FROM ' . $this->table_prefix . 'booskit_commendations c
					JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
					WHERE c.issuer_user_id = ' . $target_user_id . '
					ORDER BY c.commendation_date DESC';
			$res = @$this->db->sql_query_limit($sql, 30);
			if ($res)
			{
				$issued['commendations'] = $this->db->sql_fetchrowset($res);
				$this->db->sql_freeresult($res);
			}
		}

		// Awards issued
		if ($this->is_ext_enabled('booskit/awards'))
		{
			$award_defs = $this->get_definitions('booskit/awards');
			$sql = 'SELECT a.*, u.user_id, u.username, u.user_colour
					FROM ' . $this->table_prefix . 'booskit_awards_users a
					JOIN ' . USERS_TABLE . ' u ON a.user_id = u.user_id
					WHERE a.issuer_user_id = ' . $target_user_id . '
					ORDER BY a.issue_date DESC';
			$res = @$this->db->sql_query_limit($sql, 30);
			if ($res)
			{
				while ($row = $this->db->sql_fetchrow($res))
				{
					$row['type_name'] = $this->get_definition_name('booskit/awards', $row['award_definition_id'], $award_defs);
					$issued['awards'][] = $row;
				}
				$this->db->sql_freeresult($res);
			}
		}

		return $issued;
	}

	/* =========================================================================
	 * PROFILE DATA AGGREGATION
	 * ========================================================================= */

	public function get_user_profile_data($viewer_id, $target_user_id)
	{
		$target_user_id = (int) $target_user_id;

		$sql = 'SELECT u.*, g.group_name, g.group_colour
				FROM ' . USERS_TABLE . ' u
				LEFT JOIN ' . GROUPS_TABLE . ' g ON u.group_id = g.group_id
				WHERE u.user_id = ' . $target_user_id;
		$res = $this->db->sql_query($sql);
		$user_data = $this->db->sql_fetchrow($res);
		$this->db->sql_freeresult($res);

		if (!$user_data)
		{
			return null;
		}

		$perms = $this->get_effective_permissions($viewer_id, $target_user_id);

		$data = [
			'user'                     => $user_data,
			'avatar_html'              => $this->get_user_avatar_or_group_or_initial($user_data, $user_data['username'], $user_data['group_id']),
			'awards'                   => [],
			'career'                   => [],
			'commendations'            => [],
			'disciplinary'             => [],
			'ic_disciplinary'          => [],
			'gtaw_characters'          => [],
			'issued'                   => $this->get_user_issued_actions($viewer_id, $target_user_id),
			'can_view_issued'          => !empty($perms['view_issued']),
			'can_view_topics'          => !empty($perms['view_visited_topics']),
			'can_view_visited_forums'  => !empty($perms['view_visited_forums']),
			'can_view_visited_users'   => !empty($perms['view_visited_users']),
			'can_view_visited_profiles'=> !empty($perms['view_visited_profiles']),
		];

		// Awards
		if ($this->is_ext_enabled('booskit/awards') && !empty($this->config['booskit_dashboard_include_awards']) && !empty($perms['view_awards']))
		{
			$where = $this->get_module_where_clause('awards', $viewer_id);
			if ($where !== false)
			{
				$defs = $this->get_definitions('booskit/awards');
				$sql = 'SELECT a.*, i.username as issuer_name, i.user_colour as issuer_colour
						FROM ' . $this->table_prefix . 'booskit_awards_users a
						LEFT JOIN ' . USERS_TABLE . ' i ON a.issuer_user_id = i.user_id
						JOIN ' . USERS_TABLE . ' u ON a.user_id = u.user_id
						WHERE a.user_id = ' . $target_user_id . ' AND (' . $where . ')
						ORDER BY a.issue_date DESC';
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					while ($row = $this->db->sql_fetchrow($res))
					{
						$row['type_name'] = $this->get_definition_name('booskit/awards', $row['award_definition_id'], $defs);
						$data['awards'][] = $row;
					}
					$this->db->sql_freeresult($res);
				}
			}
		}

		// Career
		if ($this->is_ext_enabled('booskit/usercareer') && !empty($this->config['booskit_dashboard_include_career']) && !empty($perms['view_career']))
		{
			$where = $this->get_module_where_clause('career', $viewer_id);
			if ($where !== false)
			{
				$defs = $this->get_definitions('booskit/usercareer');
				$sql = 'SELECT n.*, i.username as issuer_name, i.user_colour as issuer_colour
						FROM ' . $this->table_prefix . 'booskit_career_notes n
						LEFT JOIN ' . USERS_TABLE . ' i ON n.issuer_user_id = i.user_id
						JOIN ' . USERS_TABLE . ' u ON n.user_id = u.user_id
						WHERE n.user_id = ' . $target_user_id . ' AND (' . $where . ')
						ORDER BY n.note_date DESC';
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					while ($row = $this->db->sql_fetchrow($res))
					{
						$row['type_name'] = $this->get_definition_name('booskit/usercareer', $row['career_type_id'], $defs);
						$data['career'][] = $row;
					}
					$this->db->sql_freeresult($res);
				}
			}
		}

		// Commendations
		if ($this->is_ext_enabled('booskit/commendations') && !empty($this->config['booskit_dashboard_include_commendations']) && !empty($perms['view_commendations']))
		{
			$where = $this->get_module_where_clause('commendations', $viewer_id);
			if ($where !== false)
			{
				$sql = 'SELECT c.*, i.username as issuer_name, i.user_colour as issuer_colour
						FROM ' . $this->table_prefix . 'booskit_commendations c
						LEFT JOIN ' . USERS_TABLE . ' i ON c.issuer_user_id = i.user_id
						JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
						WHERE c.user_id = ' . $target_user_id . ' AND (' . $where . ')
						ORDER BY c.commendation_date DESC';
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					$data['commendations'] = $this->db->sql_fetchrowset($res);
					$this->db->sql_freeresult($res);
				}
			}
		}

		// Disciplinary
		if ($this->is_ext_enabled('booskit/disciplinary') && !empty($this->config['booskit_dashboard_include_disciplinary']) && !empty($perms['view_disciplinary']))
		{
			$where = $this->get_module_where_clause('disciplinary', $viewer_id);
			if ($where !== false)
			{
				$defs = $this->get_definitions('booskit/disciplinary');
				$sql = 'SELECT d.*, i.username as issuer_name, i.user_colour as issuer_colour,
						       a.username as archived_by_name, a.user_colour as archived_by_colour
						FROM ' . $this->table_prefix . 'booskit_disciplinary_users d
						LEFT JOIN ' . USERS_TABLE . ' i ON d.issuer_user_id = i.user_id
						LEFT JOIN ' . USERS_TABLE . ' a ON d.archived_by_user_id = a.user_id
						JOIN ' . USERS_TABLE . ' u ON d.user_id = u.user_id
						LEFT JOIN ' . $this->table_prefix . 'booskit_disciplinary_definitions def ON d.disciplinary_type_id = def.disc_id
						WHERE d.user_id = ' . $target_user_id . ' AND (' . $where . ')
						ORDER BY d.issue_date DESC';
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					while ($row = $this->db->sql_fetchrow($res))
					{
						$row['type_name'] = $this->get_definition_name('booskit/disciplinary', $row['disciplinary_type_id'], $defs);
						$row['can_view_evidence'] = $this->can_view_private_notes('disciplinary', $viewer_id, $target_user_id, $row['disciplinary_type_id']);
						$data['disciplinary'][] = $row;
					}
					$this->db->sql_freeresult($res);
				}
			}
		}

		// IC Disciplinary
		if ($this->is_ext_enabled('booskit/icdisciplinary') && !empty($this->config['booskit_dashboard_include_ic_disciplinary']) && !empty($perms['view_ic_disciplinary']))
		{
			$where = $this->get_module_where_clause('ic_disciplinary', $viewer_id);
			if ($where !== false)
			{
				$defs = $this->get_definitions('booskit/icdisciplinary');
				$sql = 'SELECT r.*, c.character_name, i.username as issuer_name, i.user_colour as issuer_colour,
						       a.username as archived_by_name, a.user_colour as archived_by_colour
						FROM ' . $this->table_prefix . 'booskit_ic_records r
						JOIN ' . $this->table_prefix . 'booskit_ic_characters c ON r.character_id = c.character_id
						LEFT JOIN ' . USERS_TABLE . ' i ON r.issuer_user_id = i.user_id
						LEFT JOIN ' . USERS_TABLE . ' a ON r.archived_by_user_id = a.user_id
						JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
						LEFT JOIN ' . $this->table_prefix . 'booskit_ic_definitions def ON r.disciplinary_type_id = def.disc_id
						WHERE c.user_id = ' . $target_user_id . ' AND (' . $where . ')
						ORDER BY r.issue_date DESC';
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					while ($row = $this->db->sql_fetchrow($res))
					{
						$row['type_name'] = $this->get_definition_name('booskit/icdisciplinary', $row['disciplinary_type_id'], $defs);
						$row['can_view_evidence'] = $this->can_view_private_notes('ic_disciplinary', $viewer_id, $target_user_id, $row['disciplinary_type_id']);
						$data['ic_disciplinary'][] = $row;
					}
					$this->db->sql_freeresult($res);
				}
			}
		}

		// GTAW Characters
		if ($this->is_ext_enabled('booskit/gtawtracker') && !empty($perms['view_gtaw']))
		{
			$sql = 'SELECT * FROM ' . $this->table_prefix . 'booskit_gtaw_characters WHERE user_id = ' . $target_user_id . ' ORDER BY character_name ASC';
			$res = @$this->db->sql_query($sql);
			if ($res)
			{
				$data['gtaw_characters'] = $this->db->sql_fetchrowset($res);
				$this->db->sql_freeresult($res);
			}
		}

		return $data;
	}

	/* =========================================================================
	 * FEED & LISTING HELPERS (UCC PARITY)
	 * ========================================================================= */

	public function get_latest_awards($viewer_id, $limit = 6, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/awards') || empty($this->config['booskit_dashboard_include_awards'])) return [];
		$where = $this->get_module_where_clause('awards', $viewer_id);
		if ($where === false) return [];

		$sql = 'SELECT a.*, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_awards_users a
				JOIN ' . USERS_TABLE . ' u ON a.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON a.issuer_user_id = i.user_id
				WHERE ' . $where . '
				ORDER BY a.issue_date DESC';
		$res = @$this->db->sql_query_limit($sql, $limit, $start);
		$items = [];
		if ($res)
		{
			$defs = $this->get_definitions('booskit/awards');
			while ($row = $this->db->sql_fetchrow($res))
			{
				$row['type_name'] = $this->get_definition_name('booskit/awards', $row['award_definition_id'], $defs);
				$items[] = $row;
			}
			$this->db->sql_freeresult($res);
		}
		return $items;
	}

	public function get_total_awards($viewer_id)
	{
		if (!$this->is_ext_enabled('booskit/awards') || empty($this->config['booskit_dashboard_include_awards'])) return 0;
		$where = $this->get_module_where_clause('awards', $viewer_id);
		if ($where === false) return 0;
		$sql = 'SELECT COUNT(a.issue_id) as total FROM ' . $this->table_prefix . 'booskit_awards_users a JOIN ' . USERS_TABLE . ' u ON a.user_id = u.user_id WHERE ' . $where;
		$res = @$this->db->sql_query($sql);
		$cnt = $res ? (int)$this->db->sql_fetchfield('total') : 0;
		if ($res) { $this->db->sql_freeresult($res); }
		return $cnt;
	}

	public function get_latest_career($viewer_id, $limit = 6, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/usercareer') || empty($this->config['booskit_dashboard_include_career'])) return [];
		$where = $this->get_module_where_clause('career', $viewer_id);
		if ($where === false) return [];

		$sql = 'SELECT n.*, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_career_notes n
				JOIN ' . USERS_TABLE . ' u ON n.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON n.issuer_user_id = i.user_id
				WHERE ' . $where . '
				ORDER BY n.note_date DESC';
		$res = @$this->db->sql_query_limit($sql, $limit, $start);
		$items = [];
		if ($res)
		{
			$defs = $this->get_definitions('booskit/usercareer');
			while ($row = $this->db->sql_fetchrow($res))
			{
				$row['type_name'] = $this->get_definition_name('booskit/usercareer', $row['career_type_id'], $defs);
				$items[] = $row;
			}
			$this->db->sql_freeresult($res);
		}
		return $items;
	}

	public function get_total_career($viewer_id)
	{
		if (!$this->is_ext_enabled('booskit/usercareer') || empty($this->config['booskit_dashboard_include_career'])) return 0;
		$where = $this->get_module_where_clause('career', $viewer_id);
		if ($where === false) return 0;
		$sql = 'SELECT COUNT(n.note_id) as total FROM ' . $this->table_prefix . 'booskit_career_notes n JOIN ' . USERS_TABLE . ' u ON n.user_id = u.user_id WHERE ' . $where;
		$res = @$this->db->sql_query($sql);
		$cnt = $res ? (int)$this->db->sql_fetchfield('total') : 0;
		if ($res) { $this->db->sql_freeresult($res); }
		return $cnt;
	}

	public function get_latest_commendations($viewer_id, $limit = 6, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/commendations') || empty($this->config['booskit_dashboard_include_commendations'])) return [];
		$where = $this->get_module_where_clause('commendations', $viewer_id);
		if ($where === false) return [];

		$sql = 'SELECT c.*, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_commendations c
				JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON c.issuer_user_id = i.user_id
				WHERE ' . $where . '
				ORDER BY c.commendation_date DESC';
		$res = @$this->db->sql_query_limit($sql, $limit, $start);
		$items = $res ? $this->db->sql_fetchrowset($res) : [];
		if ($res) { $this->db->sql_freeresult($res); }
		return $items;
	}

	public function get_total_commendations($viewer_id)
	{
		if (!$this->is_ext_enabled('booskit/commendations') || empty($this->config['booskit_dashboard_include_commendations'])) return 0;
		$where = $this->get_module_where_clause('commendations', $viewer_id);
		if ($where === false) return 0;
		$sql = 'SELECT COUNT(c.commendation_id) as total FROM ' . $this->table_prefix . 'booskit_commendations c JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id WHERE ' . $where;
		$res = @$this->db->sql_query($sql);
		$cnt = $res ? (int)$this->db->sql_fetchfield('total') : 0;
		if ($res) { $this->db->sql_freeresult($res); }
		return $cnt;
	}

	public function get_latest_disciplinary($viewer_id, $limit = 6, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/disciplinary') || empty($this->config['booskit_dashboard_include_disciplinary'])) return [];
		$where = $this->get_module_where_clause('disciplinary', $viewer_id);
		if ($where === false) return [];

		$sql = 'SELECT d.*, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_disciplinary_users d
				JOIN ' . USERS_TABLE . ' u ON d.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON d.issuer_user_id = i.user_id
				LEFT JOIN ' . $this->table_prefix . 'booskit_disciplinary_definitions def ON d.disciplinary_type_id = def.disc_id
				WHERE ' . $where . '
				ORDER BY d.issue_date DESC';
		$res = @$this->db->sql_query_limit($sql, $limit, $start);
		$items = [];
		if ($res)
		{
			$defs = $this->get_definitions('booskit/disciplinary');
			while ($row = $this->db->sql_fetchrow($res))
			{
				$row['type_name'] = $this->get_definition_name('booskit/disciplinary', $row['disciplinary_type_id'], $defs);
				$items[] = $row;
			}
			$this->db->sql_freeresult($res);
		}
		return $items;
	}

	public function get_total_disciplinary($viewer_id)
	{
		if (!$this->is_ext_enabled('booskit/disciplinary') || empty($this->config['booskit_dashboard_include_disciplinary'])) return 0;
		$where = $this->get_module_where_clause('disciplinary', $viewer_id);
		if ($where === false) return 0;
		$sql = 'SELECT COUNT(d.record_id) as total FROM ' . $this->table_prefix . 'booskit_disciplinary_users d JOIN ' . USERS_TABLE . ' u ON d.user_id = u.user_id LEFT JOIN ' . $this->table_prefix . 'booskit_disciplinary_definitions def ON d.disciplinary_type_id = def.disc_id WHERE ' . $where;
		$res = @$this->db->sql_query($sql);
		$cnt = $res ? (int)$this->db->sql_fetchfield('total') : 0;
		if ($res) { $this->db->sql_freeresult($res); }
		return $cnt;
	}

	public function get_latest_ic_disciplinary($viewer_id, $limit = 6, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/icdisciplinary') || empty($this->config['booskit_dashboard_include_ic_disciplinary'])) return [];
		$where = $this->get_module_where_clause('ic_disciplinary', $viewer_id);
		if ($where === false) return [];

		$sql = 'SELECT r.*, c.character_name, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_ic_records r
				JOIN ' . $this->table_prefix . 'booskit_ic_characters c ON r.character_id = c.character_id
				JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON r.issuer_user_id = i.user_id
				LEFT JOIN ' . $this->table_prefix . 'booskit_ic_definitions def ON r.disciplinary_type_id = def.disc_id
				WHERE ' . $where . '
				ORDER BY r.issue_date DESC';
		$res = @$this->db->sql_query_limit($sql, $limit, $start);
		$items = [];
		if ($res)
		{
			$defs = $this->get_definitions('booskit/icdisciplinary');
			while ($row = $this->db->sql_fetchrow($res))
			{
				$row['type_name'] = $this->get_definition_name('booskit/icdisciplinary', $row['disciplinary_type_id'], $defs);
				$items[] = $row;
			}
			$this->db->sql_freeresult($res);
		}
		return $items;
	}

	public function get_total_ic_disciplinary($viewer_id)
	{
		if (!$this->is_ext_enabled('booskit/icdisciplinary') || empty($this->config['booskit_dashboard_include_ic_disciplinary'])) return 0;
		$where = $this->get_module_where_clause('ic_disciplinary', $viewer_id);
		if ($where === false) return 0;
		$sql = 'SELECT COUNT(r.record_id) as total FROM ' . $this->table_prefix . 'booskit_ic_records r JOIN ' . $this->table_prefix . 'booskit_ic_characters c ON r.character_id = c.character_id JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id LEFT JOIN ' . $this->table_prefix . 'booskit_ic_definitions def ON r.disciplinary_type_id = def.disc_id WHERE ' . $where;
		$res = @$this->db->sql_query($sql);
		$cnt = $res ? (int)$this->db->sql_fetchfield('total') : 0;
		if ($res) { $this->db->sql_freeresult($res); }
		return $cnt;
	}

	public function get_module_where_clause($module, $viewer_id)
	{
		$viewer_groups = $this->get_user_groups($viewer_id);
		$is_admin = $this->is_admin($viewer_id);

		if ($is_admin)
		{
			return '1=1';
		}

		switch ($module)
		{
			case 'awards':
				$full = $this->get_config_groups('booskit_awards_access_full');
				if (array_intersect($viewer_groups, $full)) return '1=1';

				$l3 = $this->get_config_groups('booskit_awards_access_l3');
				$l2 = $this->get_config_groups('booskit_awards_access_l2');
				$l1 = $this->get_config_groups('booskit_awards_access_l1');

				$max_level = 0;
				if (array_intersect($viewer_groups, $l3)) $max_level = 3;
				else if (array_intersect($viewer_groups, $l2)) $max_level = 2;
				else if (array_intersect($viewer_groups, $l1)) $max_level = 1;

				if ($max_level > 0)
				{
					return '1=1';
				}
				return 'u.user_id = ' . (int)$viewer_id;

			case 'career':
				$full = $this->get_config_groups('booskit_career_access_full');
				if (array_intersect($viewer_groups, $full)) return '1=1';

				$l2 = $this->get_config_groups('booskit_career_access_l2');
				$l1 = $this->get_config_groups('booskit_career_access_l1');

				$max_level = 0;
				if (array_intersect($viewer_groups, $l2)) $max_level = 2;
				else if (array_intersect($viewer_groups, $l1)) $max_level = 1;

				if ($max_level > 0)
				{
					return '1=1';
				}
				return 'u.user_id = ' . (int)$viewer_id;

			case 'commendations':
				$full = $this->get_config_groups('booskit_commendations_access_full');
				if (array_intersect($viewer_groups, $full)) return '1=1';

				$l2 = $this->get_config_groups('booskit_commendations_access_l2');
				$l1 = $this->get_config_groups('booskit_commendations_access_l1');

				if (array_intersect($viewer_groups, $l2) || array_intersect($viewer_groups, $l1))
				{
					return '1=1';
				}
				return 'u.user_id = ' . (int)$viewer_id;

			case 'disciplinary':
			case 'ic_disciplinary':
				$perm_system = ($module === 'disciplinary')
					? (isset($this->config['booskit_disciplinary_perm_system']) ? $this->config['booskit_disciplinary_perm_system'] : 'legacy')
					: (isset($this->config['booskit_icdisciplinary_perm_system']) ? $this->config['booskit_icdisciplinary_perm_system'] : 'legacy');

				if ($perm_system === 'groups')
				{
					return $this->get_groups_module_where_clause($module, $viewer_id, $viewer_groups);
				}

				if ($module === 'disciplinary')
				{
					$global_groups = $this->get_config_groups('booskit_disciplinary_access_view_global');
					if (array_intersect($viewer_groups, $global_groups))
					{
						$defs = $this->get_definitions('booskit/disciplinary');
						$globally_viewable_ids = [];
						if ($this->config['booskit_disciplinary_source'] === 'local')
						{
							$sql = 'SELECT disc_id FROM ' . $this->table_prefix . 'booskit_disciplinary_definitions WHERE globally_viewable = 1';
							$res = @$this->db->sql_query($sql);
							if ($res)
							{
								while ($row = $this->db->sql_fetchrow($res)) $globally_viewable_ids[] = $row['disc_id'];
								$this->db->sql_freeresult($res);
							}
						}
						if (!empty($globally_viewable_ids))
						{
							return $this->db->sql_in_set('d.disciplinary_type_id', $globally_viewable_ids);
						}
					}

					$full = $this->get_config_groups('booskit_disciplinary_access_full');
					if (array_intersect($viewer_groups, $full)) return '1=1';

					$l3 = $this->get_config_groups('booskit_disciplinary_access_l3');
					$l2 = $this->get_config_groups('booskit_disciplinary_access_l2');
					$l1 = $this->get_config_groups('booskit_disciplinary_access_l1');

					$viewer_level = 0;
					if (array_intersect($viewer_groups, $l3)) $viewer_level = 3;
					else if (array_intersect($viewer_groups, $l2)) $viewer_level = 2;
					else if (array_intersect($viewer_groups, $l1)) $viewer_level = 1;

					if ($viewer_level > 0)
					{
						return '1=1';
					}

					$local_groups = $this->get_config_groups('booskit_disciplinary_access_view_local');
					if (array_intersect($viewer_groups, $local_groups))
					{
						$user_groups = $this->get_user_groups($viewer_id);
						if (!empty($user_groups))
						{
							return 'u.user_id IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $user_groups) . ')';
						}
					}

					$limited_groups = $this->get_config_groups('booskit_disciplinary_access_view_limited');
					if (array_intersect($viewer_groups, $limited_groups))
					{
						$map = $this->get_limited_view_map();
						$target_groups = [];
						foreach ($viewer_groups as $vg)
						{
							if (isset($map[$vg]))
							{
								$target_groups = array_merge($target_groups, $map[$vg]);
							}
						}
						$target_groups = array_unique($target_groups);
						if (!empty($target_groups))
						{
							return 'u.user_id IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $target_groups) . ')';
						}
					}

					$exempted = $this->get_config_groups('booskit_disciplinary_access_view_exempted');
					if (array_intersect($viewer_groups, $exempted))
					{
						return 'u.user_id = ' . (int)$viewer_id;
					}

					return false;
				}
				else
				{
					$full = $this->get_config_groups('booskit_icdisciplinary_access_full');
					if (array_intersect($viewer_groups, $full)) return '1=1';

					$l2 = $this->get_config_groups('booskit_icdisciplinary_access_l2');
					$l1 = $this->get_config_groups('booskit_icdisciplinary_access_l1');

					if (array_intersect($viewer_groups, $l2) || array_intersect($viewer_groups, $l1))
					{
						return '1=1';
					}

					return 'u.user_id = ' . (int)$viewer_id;
				}
		}

		return '1=1';
	}

	protected function get_groups_module_where_clause($module, $viewer_id, $viewer_groups)
	{
		$table = ($module === 'disciplinary') ? $this->table_prefix . 'booskit_disciplinary_perm_groups' : $this->table_prefix . 'booskit_icdisciplinary_perm_groups';
		$type_column = ($module === 'disciplinary') ? 'd.disciplinary_type_id' : 'r.disciplinary_type_id';

		$sql = 'SELECT * FROM ' . $table . ' ORDER BY perm_group_id ASC';
		$result = @$this->db->sql_query($sql);
		if (!$result)
		{
			return false;
		}

		$where_clauses = [];

		while ($pg = $this->db->sql_fetchrow($result))
		{
			$applies_to = !empty($pg['applies_to']) ? array_map('intval', array_filter(array_map('trim', explode(',', $pg['applies_to'])))) : [];
			if (empty($applies_to) || !array_intersect($viewer_groups, $applies_to))
			{
				continue;
			}

			$perms = !empty($pg['permissions']) ? json_decode($pg['permissions'], true) : [];
			$types = isset($perms['types']) ? $perms['types'] : $perms;

			$allowed_types = [];
			$allowed_types_archived = [];
			if (is_array($types))
			{
				foreach ($types as $def_id => $p)
				{
					if (!empty($p['view']))
					{
						$allowed_types[] = $def_id;
					}
					if (!empty($p['view_archived']))
					{
						$allowed_types_archived[] = $def_id;
					}
				}
			}

			if (empty($allowed_types) && empty($allowed_types_archived))
			{
				continue;
			}

			$exclude_groups = !empty($pg['exclude_groups']) ? array_map('intval', array_filter(array_map('trim', explode(',', $pg['exclude_groups'])))) : [];
			$power_parts = [];

			if (!empty($pg['power_over_all']))
			{
				$power_parts[] = '1=1';
			}
			if (!empty($pg['power_over_self']))
			{
				$user_groups = $this->get_user_groups($viewer_id);
				if (!empty($user_groups))
				{
					$power_parts[] = '(u.user_id = ' . (int)$viewer_id . ' OR u.user_id IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $user_groups) . '))';
				}
				else
				{
					$power_parts[] = 'u.user_id = ' . (int)$viewer_id;
				}
			}
			if (!empty($pg['power_over_groups']))
			{
				$power_over_groups = array_map('intval', array_filter(array_map('trim', explode(',', $pg['power_over_groups']))));
				if (!empty($power_over_groups))
				{
					$power_parts[] = 'u.user_id IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $power_over_groups) . ')';
				}
			}

			if (empty($power_parts))
			{
				continue;
			}

			$pg_clause = '(' . implode(' OR ', $power_parts) . ')';

			if (!empty($exclude_groups))
			{
				$pg_clause .= ' AND u.user_id NOT IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $exclude_groups) . ')';
			}

			$arch_column = ($module === 'disciplinary') ? 'd.is_archived' : 'r.is_archived';
			$type_conds = [];
			if (!empty($allowed_types))
			{
				$type_conds[] = '(' . $arch_column . ' = 0 AND ' . $this->db->sql_in_set($type_column, $allowed_types) . ')';
			}
			if (!empty($allowed_types_archived))
			{
				$type_conds[] = '(' . $arch_column . ' = 1 AND ' . $this->db->sql_in_set($type_column, $allowed_types_archived) . ')';
			}

			if (!empty($type_conds))
			{
				$pg_clause .= ' AND (' . implode(' OR ', $type_conds) . ')';
			}

			$where_clauses[] = '(' . $pg_clause . ')';
		}
		$this->db->sql_freeresult($result);

		if (empty($where_clauses))
		{
			return false;
		}

		return '(' . implode(' OR ', $where_clauses) . ')';
	}

	protected function get_config_groups($key)
	{
		$raw = isset($this->config[$key]) ? $this->config[$key] : '';
		if (empty($raw)) return [];
		return array_map('intval', array_map('trim', explode(',', $raw)));
	}

	protected function get_limited_view_map()
	{
		$raw = isset($this->config['booskit_disciplinary_access_view_limited_map']) ? $this->config['booskit_disciplinary_access_view_limited_map'] : '';
		$lines = explode("\n", $raw);
		$map = [];
		foreach ($lines as $line)
		{
			$parts = explode(':', $line);
			if (count($parts) == 2)
			{
				$viewer_gid = (int)trim($parts[0]);
				$targets = array_map('intval', array_map('trim', explode(',', $parts[1])));
				$map[$viewer_gid] = $targets;
			}
		}
		return $map;
	}

	public function get_definitions($ext_name)
	{
		$cache_key = 'booskit_dashboard_defs_' . str_replace('/', '_', $ext_name);
		$definitions = ($this->cache) ? $this->cache->get($cache_key) : false;
		if ($definitions !== false) return $definitions;

		$definitions = [];
		$table = '';
		$source_config = '';
		$url_config = '';

		switch ($ext_name)
		{
			case 'booskit/awards':
				$table = $this->table_prefix . 'booskit_awards_definitions';
				$source_config = 'booskit_awards_source';
				$url_config = 'booskit_awards_json_url';
				break;
			case 'booskit/usercareer':
				$table = $this->table_prefix . 'booskit_career_definitions';
				$source_config = 'booskit_career_source';
				$url_config = 'booskit_career_json_url';
				break;
			case 'booskit/disciplinary':
				$table = $this->table_prefix . 'booskit_disciplinary_definitions';
				$source_config = 'booskit_disciplinary_source';
				$url_config = 'booskit_disciplinary_json_url';
				break;
			case 'booskit/icdisciplinary':
				$table = $this->table_prefix . 'booskit_ic_definitions';
				$source_config = 'booskit_icdisciplinary_source';
				$url_config = 'booskit_icdisciplinary_json_url';
				break;
		}

		if (!$table) return [];

		$source = isset($this->config[$source_config]) ? $this->config[$source_config] : 'url';

		if ($source === 'local')
		{
			$sql = 'SELECT * FROM ' . $table;
			$result = @$this->db->sql_query($sql);
			if ($result)
			{
				while ($row = $this->db->sql_fetchrow($result))
				{
					$id = isset($row['disc_id']) ? $row['disc_id'] : (isset($row['award_id']) ? $row['award_id'] : (isset($row['career_id']) ? $row['career_id'] : ''));
					$name = isset($row['disc_name']) ? $row['disc_name'] : (isset($row['award_name']) ? $row['award_name'] : (isset($row['career_name']) ? $row['career_name'] : ''));
					if ($id) $definitions[$id] = $name;
				}
				$this->db->sql_freeresult($result);
			}
		}
		else
		{
			$json_url = isset($this->config[$url_config]) ? $this->config[$url_config] : '';
			if ($json_url)
			{
				$context = stream_context_create(['http' => ['timeout' => 5]]);
				$content = @file_get_contents($json_url, false, $context);
				if ($content !== false)
				{
					$data = json_decode($content, true);
					if (is_array($data))
					{
						foreach ($data as $item)
						{
							if (isset($item['id']) && isset($item['name']))
							{
								$definitions[$item['id']] = $item['name'];
							}
						}
					}
				}
			}
		}

		if ($this->cache)
		{
			$this->cache->put($cache_key, $definitions, 3600);
		}
		return $definitions;
	}

	public function get_definition_name($ext_name, $id, $definitions)
	{
		return isset($definitions[$id]) ? $definitions[$id] : $id;
	}

	public function can_view_private_notes($module, $viewer_id, $target_user_id, $def_id)
	{
		$viewer_groups = $this->get_user_groups($viewer_id);

		if ($module === 'disciplinary')
		{
			if (isset($this->config['booskit_disciplinary_perm_system']) && $this->config['booskit_disciplinary_perm_system'] === 'groups')
			{
				return $this->check_groups_private_notes_perm('disciplinary', $viewer_id, $target_user_id, $def_id, $viewer_groups);
			}

			$l1 = $this->get_config_groups('booskit_disciplinary_access_l1');
			$l2 = $this->get_config_groups('booskit_disciplinary_access_l2');
			$l3 = $this->get_config_groups('booskit_disciplinary_access_l3');
			$full = $this->get_config_groups('booskit_disciplinary_access_full');

			$viewer_level = 0;
			if (array_intersect($viewer_groups, $full)) $viewer_level = 4;
			else if (array_intersect($viewer_groups, $l3)) $viewer_level = 3;
			else if (array_intersect($viewer_groups, $l2)) $viewer_level = 2;
			else if (array_intersect($viewer_groups, $l1)) $viewer_level = 1;

			$target_groups = $this->get_user_groups($target_user_id);
			$target_level = 0;
			if (array_intersect($target_groups, $full)) $target_level = 4;
			else if (array_intersect($target_groups, $l3)) $target_level = 3;
			else if (array_intersect($target_groups, $l2)) $target_level = 2;
			else if (array_intersect($target_groups, $l1)) $target_level = 1;

			if ($viewer_level > 0 && ($viewer_level === 4 || $viewer_level > $target_level))
			{
				return true;
			}

			$exempted = $this->get_config_groups('booskit_disciplinary_access_view_exempted');
			if ($viewer_id == $target_user_id && array_intersect($viewer_groups, $exempted))
			{
				return true;
			}

			return false;
		}
		else if ($module === 'ic_disciplinary')
		{
			if (isset($this->config['booskit_icdisciplinary_perm_system']) && $this->config['booskit_icdisciplinary_perm_system'] === 'groups')
			{
				return $this->check_groups_private_notes_perm('ic_disciplinary', $viewer_id, $target_user_id, $def_id, $viewer_groups);
			}

			$l1 = $this->get_config_groups('booskit_icdisciplinary_access_l1');
			$l2 = $this->get_config_groups('booskit_icdisciplinary_access_l2');
			$full = $this->get_config_groups('booskit_icdisciplinary_access_full');

			$viewer_level = 0;
			if (array_intersect($viewer_groups, $full)) $viewer_level = 4;
			else if (array_intersect($viewer_groups, $l2)) $viewer_level = 2;
			else if (array_intersect($viewer_groups, $l1)) $viewer_level = 1;

			$target_groups = $this->get_user_groups($target_user_id);
			$target_level = 0;
			if (array_intersect($target_groups, $full)) $target_level = 4;
			else if (array_intersect($target_groups, $l2)) $target_level = 2;
			else if (array_intersect($target_groups, $l1)) $target_level = 1;

			if ($viewer_level > 0 && ($viewer_level === 4 || $viewer_level > $target_level))
			{
				return true;
			}

			return false;
		}

		return false;
	}

	protected function check_groups_private_notes_perm($module, $viewer_id, $target_user_id, $def_id, $viewer_groups)
	{
		$table = ($module === 'disciplinary') ? $this->table_prefix . 'booskit_disciplinary_perm_groups' : $this->table_prefix . 'booskit_icdisciplinary_perm_groups';
		
		$sql = 'SELECT * FROM ' . $table . ' ORDER BY perm_group_id ASC';
		$result = @$this->db->sql_query($sql);
		if (!$result)
		{
			return false;
		}

		$target_groups = null;

		while ($pg = $this->db->sql_fetchrow($result))
		{
			$applies_to = !empty($pg['applies_to']) ? array_map('intval', array_filter(array_map('trim', explode(',', $pg['applies_to'])))) : [];
			if (empty($applies_to) || !array_intersect($viewer_groups, $applies_to))
			{
				continue;
			}

			$perms = !empty($pg['permissions']) ? json_decode($pg['permissions'], true) : [];
			if (empty($perms['types'][$def_id]['view_private_notes']))
			{
				continue;
			}

			$has_power = false;
			if (!empty($pg['power_over_all']))
			{
				$has_power = true;
			}
			if (!$has_power && !empty($pg['power_over_self']) && $viewer_id == $target_user_id)
			{
				$has_power = true;
			}
			if (!$has_power && !empty($pg['power_over_groups']))
			{
				if ($target_groups === null)
				{
					$target_groups = $this->get_user_groups($target_user_id);
				}
				$power_over_groups = array_map('intval', array_filter(array_map('trim', explode(',', $pg['power_over_groups']))));
				if (array_intersect($target_groups, $power_over_groups))
				{
					$has_power = true;
				}
			}

			if ($has_power)
			{
				$exclude_groups = !empty($pg['exclude_groups']) ? array_map('intval', array_filter(array_map('trim', explode(',', $pg['exclude_groups'])))) : [];
				if (!empty($exclude_groups))
				{
					if ($target_groups === null)
					{
						$target_groups = $this->get_user_groups($target_user_id);
					}
					if (array_intersect($target_groups, $exclude_groups))
					{
						continue;
					}
				}

				$this->db->sql_freeresult($result);
				return true;
			}
		}
		$this->db->sql_freeresult($result);

		return false;
	}

	protected function format_time_ago($timestamp)
	{
		$diff = time() - $timestamp;
		if ($diff < 60)
		{
			return 'Just now';
		}
		if ($diff < 3600)
		{
			$mins = max(1, (int) round($diff / 60));
			return $mins . 'm ago';
		}
		if ($diff < 86400)
		{
			$hours = (int) round($diff / 3600);
			return $hours . 'h ago';
		}
		$days = (int) round($diff / 86400);
		return $days . 'd ago';
	}
}
