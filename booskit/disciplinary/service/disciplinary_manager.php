<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\service;

class disciplinary_manager
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var string */
	protected $table;

	protected $cached_definitions = null;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\cache\driver\driver_interface $cache, \phpbb\auth\auth $auth, $table)
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->cache = $cache;
		$this->auth = $auth;
		$this->table = $table;
	}

	public function get_definitions()
	{
		if ($this->cached_definitions !== null)
		{
			return $this->cached_definitions;
		}

		$cache_key = 'booskit_disciplinary_definitions';
		$definitions = $this->cache->get($cache_key);

		if ($definitions === false)
		{
			$json_url = $this->config['booskit_disciplinary_json_url'];
			$definitions = [];

			if (!empty($json_url))
			{
				// Suppress errors and try to fetch
				$context = stream_context_create(['http' => ['timeout' => 5]]);
				$content = @file_get_contents($json_url, false, $context);
				if ($content !== false)
				{
					$data = json_decode($content, true);
					if (is_array($data))
					{
						$definitions = $data;
					}
				}
			}

			if (empty($definitions))
			{
				// Fallback example
				$definitions = [
					[
						'id' => 'warning',
						'name' => 'Warning',
						'description' => 'A formal warning.',
						'color' => '#f1c40f',
					],
					[
						'id' => 'ban',
						'name' => 'Ban',
						'description' => 'Account suspension.',
						'color' => '#e74c3c',
					]
				];
			}

			// Cache for 1 hour
			$this->cache->put($cache_key, $definitions, 3600);
		}

		$this->cached_definitions = $definitions;
		return $definitions;
	}

	public function get_definition($id)
	{
		$definitions = $this->get_definitions();
		foreach ($definitions as $def)
		{
			if (isset($def['id']) && $def['id'] == $id)
			{
				return $def;
			}
		}
		return null;
	}

	public function get_user_records($user_id)
	{
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE user_id = ' . (int) $user_id . ' ORDER BY issue_date DESC';
		$result = $this->db->sql_query($sql);

		$records = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$records[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $records;
	}

	public function get_record($record_id)
	{
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE record_id = ' . (int) $record_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	public function add_record($user_id, $disciplinary_type_id, $issue_date, $reason, $evidence, $issuer_user_id)
	{
		$sql_ary = [
			'user_id' => (int) $user_id,
			'disciplinary_type_id' => $disciplinary_type_id,
			'issue_date' => (int) $issue_date,
			'reason' => $reason,
			'evidence' => $evidence,
			'issuer_user_id' => (int) $issuer_user_id,
		];

		$sql = 'INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		return $this->db->sql_nextid();
	}

	public function update_record($record_id, $disciplinary_type_id, $issue_date, $reason, $evidence)
	{
		$sql_ary = [
			'disciplinary_type_id' => $disciplinary_type_id,
			'issue_date' => (int) $issue_date,
			'reason' => $reason,
			'evidence' => $evidence,
		];

		$sql = 'UPDATE ' . $this->table . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE record_id = ' . (int) $record_id;
		$this->db->sql_query($sql);
	}

	public function delete_record($record_id)
	{
		$sql = 'DELETE FROM ' . $this->table . ' WHERE record_id = ' . (int) $record_id;
		$this->db->sql_query($sql);
	}

	public function get_usernames($user_ids)
	{
		if (empty($user_ids))
		{
			return [];
		}

		$sql = 'SELECT user_id, username FROM ' . USERS_TABLE . ' WHERE ' . $this->db->sql_in_set('user_id', $user_ids);
		$result = $this->db->sql_query($sql);

		$usernames = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$usernames[$row['user_id']] = $row['username'];
		}
		$this->db->sql_freeresult($result);

		return $usernames;
	}

	public function get_user_role_level($user_id)
	{
		// 0 = User, 1 = Moderator, 2 = Administrator, 3 = Founder

		// 1. Check User Type (Founder is level 3)
		$sql = 'SELECT user_type FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$user_type = (int) $this->db->sql_fetchfield('user_type');
		$this->db->sql_freeresult($result);

		if ($user_type === 3) // USER_FOUNDER
		{
			return 3;
		}

		// 2. Check Permissions using acl_get_list which handles Roles and Groups correctly
		$has_admin = false;
		$has_mod = false;

		// Get User permissions
		$user_perms = $this->auth->acl_get_list(array($user_id), false, false);

		if (isset($user_perms[$user_id]))
		{
			foreach ($user_perms[$user_id] as $forum_id => $options)
			{
				foreach ($options as $opt => $setting)
				{
					if ($setting == 1)
					{
						if (strpos($opt, 'a_') === 0)
						{
							$has_admin = true;
						}
						elseif (strpos($opt, 'm_') === 0)
						{
							$has_mod = true;
						}
					}
				}
			}
		}

		// Get Group permissions manually because acl_get_list($user_id) ignores groups
		// Fetch group IDs
		$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . (int) $user_id . ' AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$group_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$group_ids[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		if (!empty($group_ids))
		{
			// acl_get_list accepts user_ids as first arg, but it treats them as IDs to look up in acl_users.
			// It does NOT have a mode to look up in acl_groups.
			// However, acl_raw_data DOES.

			// We must iterate groups and use a group-specific lookup or acl_raw_data.
			// But since we can't easily use the auth class to resolve group roles for us in a public API way,
			// we are stuck.

			// WAIT. The reviewer said "acl_get_list... correctly resolves roles".
			// But for GROUPS, acl_get_list($user_id) does NOT check the user's groups.
			// However, if we assume we just need to check permissions on the GROUPS themselves:
			// We can pass the Group IDs as if they were User IDs to acl_get_list?
			// NO, because it queries ACL_USERS_TABLE.

			// Let's use `acl_raw_data` logic? No, protected.

			// Backtrack:
			// The only robust way to resolve Roles (which is the reviewer's main complaint) AND Groups is:
			// 1. Query `acl_users` and `acl_groups` (like I did in the raw query).
			// 2. BUT also join `phpbb_acl_roles_data` if `auth_role_id > 0`.

			// Let's implement that query. It resolves the "Roles ignored" issue.

			// Query for User ACLs (Explicit + Role based)
			$sql_user = 'SELECT o.auth_option
				FROM ' . ACL_USERS_TABLE . ' au
				LEFT JOIN ' . ACL_OPTIONS_TABLE . ' o ON (au.auth_option_id = o.auth_option_id)
				LEFT JOIN ' . ACL_ROLES_DATA_TABLE . ' rd ON (au.auth_role_id = rd.role_id)
				LEFT JOIN ' . ACL_OPTIONS_TABLE . ' ro ON (rd.auth_option_id = ro.auth_option_id)
				WHERE au.user_id = ' . (int) $user_id . '
				AND (au.auth_setting = 1 OR rd.auth_setting = 1)';

			// Query for Group ACLs (Explicit + Role based)
			$sql_group = 'SELECT o.auth_option
				FROM ' . USER_GROUP_TABLE . ' ug
				JOIN ' . ACL_GROUPS_TABLE . ' ag ON (ug.group_id = ag.group_id)
				LEFT JOIN ' . ACL_OPTIONS_TABLE . ' o ON (ag.auth_option_id = o.auth_option_id)
				LEFT JOIN ' . ACL_ROLES_DATA_TABLE . ' rd ON (ag.auth_role_id = rd.role_id)
				LEFT JOIN ' . ACL_OPTIONS_TABLE . ' ro ON (rd.auth_option_id = ro.auth_option_id)
				WHERE ug.user_id = ' . (int) $user_id . '
				AND ug.user_pending = 0
				AND (ag.auth_setting = 1 OR rd.auth_setting = 1)';

			// We need to fetch `auth_option` which comes from `o` OR `ro`.
			// `COALESCE(o.auth_option, ro.auth_option)`

			$sql_user_fixed = 'SELECT COALESCE(o.auth_option, ro.auth_option) as auth_option
				FROM ' . ACL_USERS_TABLE . ' au
				LEFT JOIN ' . ACL_OPTIONS_TABLE . ' o ON (au.auth_option_id = o.auth_option_id)
				LEFT JOIN ' . ACL_ROLES_DATA_TABLE . ' rd ON (au.auth_role_id = rd.role_id)
				LEFT JOIN ' . ACL_OPTIONS_TABLE . ' ro ON (rd.auth_option_id = ro.auth_option_id)
				WHERE au.user_id = ' . (int) $user_id . '
				AND (au.auth_setting = 1 OR rd.auth_setting = 1)';

			$sql_group_fixed = 'SELECT COALESCE(o.auth_option, ro.auth_option) as auth_option
				FROM ' . USER_GROUP_TABLE . ' ug
				JOIN ' . ACL_GROUPS_TABLE . ' ag ON (ug.group_id = ag.group_id)
				LEFT JOIN ' . ACL_OPTIONS_TABLE . ' o ON (ag.auth_option_id = o.auth_option_id)
				LEFT JOIN ' . ACL_ROLES_DATA_TABLE . ' rd ON (ag.auth_role_id = rd.role_id)
				LEFT JOIN ' . ACL_OPTIONS_TABLE . ' ro ON (rd.auth_option_id = ro.auth_option_id)
				WHERE ug.user_id = ' . (int) $user_id . '
				AND ug.user_pending = 0
				AND (ag.auth_setting = 1 OR rd.auth_setting = 1)';

			$sql = '(' . $sql_user_fixed . ') UNION (' . $sql_group_fixed . ')';

			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$opt = (string) $row['auth_option'];
				if (strpos($opt, 'a_') === 0)
				{
					$has_admin = true;
				}
				elseif (strpos($opt, 'm_') === 0)
				{
					$has_mod = true;
				}

				if ($has_admin) break;
			}
			$this->db->sql_freeresult($result);
		}

		if ($has_admin) return 2;
		if ($has_mod) return 1;
		return 0;
	}
}
