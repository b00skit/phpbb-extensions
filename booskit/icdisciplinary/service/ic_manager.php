<?php
/**
 *
 * @package booskit/icdisciplinary
 * @license MIT
 *
 */

namespace booskit\icdisciplinary\service;

class ic_manager
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
	protected $table_characters;

	/** @var string */
	protected $table_records;

	/** @var string */
	protected $table_definitions;

	/** @var string */
	protected $table_perm_groups;

	protected $cached_definitions = null;
	protected $cached_role_groups = null;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\cache\driver\driver_interface $cache, \phpbb\auth\auth $auth, $table_characters, $table_records, $table_definitions, $table_perm_groups = '')
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->cache = $cache;
		$this->auth = $auth;
		$this->table_characters = $table_characters;
		$this->table_records = $table_records;
		$this->table_definitions = $table_definitions;
		$this->table_perm_groups = !empty($table_perm_groups) ? $table_perm_groups : $this->table_records . '_perm_groups';
	}

	public function get_perm_system()
	{
		return isset($this->config['booskit_icdisciplinary_perm_system']) ? $this->config['booskit_icdisciplinary_perm_system'] : 'legacy';
	}

	public function get_definitions()
	{
		if ($this->cached_definitions !== null)
		{
			return $this->cached_definitions;
		}

		$definitions = [];
		$source = isset($this->config['booskit_icdisciplinary_source']) ? $this->config['booskit_icdisciplinary_source'] : 'url';

		if ($source === 'local')
		{
			// Fetch from database
			$sql = 'SELECT * FROM ' . $this->table_definitions . ' ORDER BY def_id ASC';
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$definitions[] = [
					'id' => $row['disc_id'],
					'name' => $row['disc_name'],
					'description' => $row['disc_desc'],
					'color' => $row['disc_color'],
					'access_level' => (int)$row['access_level'],
					// Internal DB ID
					'def_id' => $row['def_id'],
				];
			}
			$this->db->sql_freeresult($result);
		}
		else
		{
			$cache_key = 'booskit_icdisciplinary_definitions';
			$definitions = $this->cache->get($cache_key);

			if ($definitions === false)
			{
				$json_url = isset($this->config['booskit_icdisciplinary_json_url']) ? $this->config['booskit_icdisciplinary_json_url'] : '';
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
							'access_level' => 1,
						],
						[
							'id' => 'arrest',
							'name' => 'Arrest',
							'description' => 'Arrested.',
							'color' => '#e74c3c',
							'access_level' => 1,
						]
					];
				}

				// Cache for 1 hour
				$this->cache->put($cache_key, $definitions, 3600);
			}
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

	public function get_local_definitions()
	{
		$sql = 'SELECT * FROM ' . $this->table_definitions . ' ORDER BY def_id ASC';
		$result = $this->db->sql_query($sql);
		$definitions = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$definitions[] = $row;
		}
		$this->db->sql_freeresult($result);
		return $definitions;
	}

	public function add_local_definition($id, $name, $desc, $color, $access_level)
	{
		$sql_ary = [
			'disc_id' => $id,
			'disc_name' => $name,
			'disc_desc' => $desc,
			'disc_color' => $color,
			'access_level' => (int)$access_level,
		];
		$sql = 'INSERT INTO ' . $this->table_definitions . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
		$this->cached_definitions = null; // Clear cache
	}

	public function update_local_definition($def_id, $id, $name, $desc, $color, $access_level)
	{
		$sql_ary = [
			'disc_id' => $id,
			'disc_name' => $name,
			'disc_desc' => $desc,
			'disc_color' => $color,
			'access_level' => (int)$access_level,
		];
		$sql = 'UPDATE ' . $this->table_definitions . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE def_id = ' . (int) $def_id;
		$this->db->sql_query($sql);
		$this->cached_definitions = null;
	}

	public function delete_local_definition($def_id)
	{
		$sql = 'DELETE FROM ' . $this->table_definitions . ' WHERE def_id = ' . (int) $def_id;
		$this->db->sql_query($sql);
		$this->cached_definitions = null;
	}

	// --- Character Management ---

	public function get_user_characters($user_id, $include_archived = false)
	{
		$sql = 'SELECT * FROM ' . $this->table_characters . ' WHERE user_id = ' . (int) $user_id;
		if (!$include_archived)
		{
			$sql .= ' AND is_archived = 0';
		}
		$sql .= ' ORDER BY character_name ASC';

		$result = $this->db->sql_query($sql);
		$characters = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$characters[] = $row;
		}
		$this->db->sql_freeresult($result);
		return $characters;
	}

	public function get_character($character_id)
	{
		$sql = 'SELECT * FROM ' . $this->table_characters . ' WHERE character_id = ' . (int) $character_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row;
	}

	public function add_character($user_id, $name)
	{
		$sql_ary = [
			'user_id' => (int) $user_id,
			'character_name' => $name,
			'is_archived' => 0,
		];
		$sql = 'INSERT INTO ' . $this->table_characters . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
		return $this->db->sql_nextid();
	}

	public function archive_character($character_id, $state = true)
	{
		$sql = 'UPDATE ' . $this->table_characters . ' SET is_archived = ' . ($state ? 1 : 0) . ' WHERE character_id = ' . (int) $character_id;
		$this->db->sql_query($sql);
	}

	public function delete_character($character_id)
	{
		// Delete records first
		$sql = 'DELETE FROM ' . $this->table_records . ' WHERE character_id = ' . (int) $character_id;
		$this->db->sql_query($sql);

		// Delete character
		$sql = 'DELETE FROM ' . $this->table_characters . ' WHERE character_id = ' . (int) $character_id;
		$this->db->sql_query($sql);
	}

	// --- Record Management ---

	public function get_character_records($character_id)
	{
		$sql = 'SELECT * FROM ' . $this->table_records . ' WHERE character_id = ' . (int) $character_id . ' ORDER BY issue_date DESC';
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
		$sql = 'SELECT * FROM ' . $this->table_records . ' WHERE record_id = ' . (int) $record_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row;
	}

	public function add_record($character_id, $disciplinary_type_id, $issue_date, $reason, $evidence, $issuer_user_id,
		$reason_bbcode_uid, $reason_bbcode_bitfield, $reason_bbcode_options,
		$evidence_bbcode_uid, $evidence_bbcode_bitfield, $evidence_bbcode_options)
	{
		$sql_ary = [
			'character_id' => (int) $character_id,
			'disciplinary_type_id' => $disciplinary_type_id,
			'issue_date' => (int) $issue_date,
			'reason' => $reason,
			'evidence' => $evidence,
			'issuer_user_id' => (int) $issuer_user_id,
			'reason_bbcode_uid' => $reason_bbcode_uid,
			'reason_bbcode_bitfield' => $reason_bbcode_bitfield,
			'reason_bbcode_options' => $reason_bbcode_options,
			'evidence_bbcode_uid' => $evidence_bbcode_uid,
			'evidence_bbcode_bitfield' => $evidence_bbcode_bitfield,
			'evidence_bbcode_options' => $evidence_bbcode_options,
			'archive_reason' => '',
		];

		$sql = 'INSERT INTO ' . $this->table_records . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		return $this->db->sql_nextid();
	}

	public function update_record($record_id, $disciplinary_type_id, $issue_date, $reason, $evidence,
		$reason_bbcode_uid, $reason_bbcode_bitfield, $reason_bbcode_options,
		$evidence_bbcode_uid, $evidence_bbcode_bitfield, $evidence_bbcode_options,
		$edited_by_user_id = 0, $last_edited_time = 0)
	{
		$sql_ary = [
			'disciplinary_type_id' => $disciplinary_type_id,
			'issue_date' => (int) $issue_date,
			'reason' => $reason,
			'evidence' => $evidence,
			'reason_bbcode_uid' => $reason_bbcode_uid,
			'reason_bbcode_bitfield' => $reason_bbcode_bitfield,
			'reason_bbcode_options' => $reason_bbcode_options,
			'evidence_bbcode_uid' => $evidence_bbcode_uid,
			'evidence_bbcode_bitfield' => $evidence_bbcode_bitfield,
			'evidence_bbcode_options' => $evidence_bbcode_options,
		];

		if (!empty($edited_by_user_id))
		{
			$sql_ary['edited_by_user_id'] = (int) $edited_by_user_id;
			$sql_ary['last_edited_time'] = (int) ($last_edited_time ?: time());
		}

		$sql = 'UPDATE ' . $this->table_records . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE record_id = ' . (int) $record_id;
		$this->db->sql_query($sql);
	}

	public function delete_record($record_id)
	{
		$sql = 'DELETE FROM ' . $this->table_records . ' WHERE record_id = ' . (int) $record_id;
		$this->db->sql_query($sql);
	}

	// --- Utilities ---

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

	public function get_username_string($user_id)
	{
		$usernames = $this->get_usernames([$user_id]);
		return isset($usernames[$user_id]) ? $usernames[$user_id] : 'Unknown';
	}

	public function get_user_role_level($user_id)
	{
		// 0 = Regular, 1 = L1, 2 = L2, 3 = L3, 4 = Full Access

		if ($this->cached_role_groups === null)
		{
			// Helper to parse CSV group IDs
			$parse_groups = function($config_key) {
				$raw = isset($this->config[$config_key]) ? $this->config[$config_key] : '';
				if (empty($raw)) {
					return [];
				}
				return array_map('intval', array_map('trim', explode(',', $raw)));
			};

			$this->cached_role_groups = [
				'l1' => $parse_groups('booskit_icdisciplinary_access_l1'),
				'l2' => $parse_groups('booskit_icdisciplinary_access_l2'),
				'full' => $parse_groups('booskit_icdisciplinary_access_full'),
			];
		}

		// Fetch user's groups
		$user_groups = $this->get_user_groups($user_id);

		// Determine level (highest match wins)
		if (array_intersect($user_groups, $this->cached_role_groups['full'])) {
			return 4;
		}
		if (array_intersect($user_groups, $this->cached_role_groups['l2'])) {
			return 2;
		}
		if (array_intersect($user_groups, $this->cached_role_groups['l1'])) {
			return 1;
		}

		return 0;
	}

	public function get_permission_groups()
	{
		$sql = 'SELECT * FROM ' . $this->table_perm_groups . ' ORDER BY perm_group_id ASC';
		$result = $this->db->sql_query($sql);
		$groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$row['applies_to_array'] = !empty($row['applies_to']) ? array_map('intval', explode(',', $row['applies_to'])) : [];
			$row['power_over_groups_array'] = !empty($row['power_over_groups']) ? array_map('intval', explode(',', $row['power_over_groups'])) : [];
			$row['exclude_groups_array'] = !empty($row['exclude_groups']) ? array_map('intval', explode(',', $row['exclude_groups'])) : [];
			$row['permissions_array'] = !empty($row['permissions']) ? json_decode($row['permissions'], true) : [];
			$groups[] = $row;
		}
		$this->db->sql_freeresult($result);
		return $groups;
	}

	public function get_permission_group($perm_group_id)
	{
		$sql = 'SELECT * FROM ' . $this->table_perm_groups . ' WHERE perm_group_id = ' . (int) $perm_group_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		if ($row)
		{
			$row['applies_to_array'] = !empty($row['applies_to']) ? array_map('intval', explode(',', $row['applies_to'])) : [];
			$row['power_over_groups_array'] = !empty($row['power_over_groups']) ? array_map('intval', explode(',', $row['power_over_groups'])) : [];
			$row['exclude_groups_array'] = !empty($row['exclude_groups']) ? array_map('intval', explode(',', $row['exclude_groups'])) : [];
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
			'group_name' => $group_name,
			'applies_to' => $applies_str,
			'power_over_all' => (int) $power_over_all,
			'power_over_self' => (int) $power_over_self,
			'power_over_groups' => $power_groups_str,
			'exclude_groups' => $exclude_groups_str,
			'permissions' => $perms_json,
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
			'group_name' => $group_name,
			'applies_to' => $applies_str,
			'power_over_all' => (int) $power_over_all,
			'power_over_self' => (int) $power_over_self,
			'power_over_groups' => $power_groups_str,
			'exclude_groups' => $exclude_groups_str,
			'permissions' => $perms_json,
		];
		$sql = 'UPDATE ' . $this->table_perm_groups . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE perm_group_id = ' . (int) $perm_group_id;
		$this->db->sql_query($sql);
	}

	public function delete_permission_group($perm_group_id)
	{
		$sql = 'DELETE FROM ' . $this->table_perm_groups . ' WHERE perm_group_id = ' . (int) $perm_group_id;
		$this->db->sql_query($sql);
	}

	public function get_phpbb_groups()
	{
		$sql = 'SELECT group_id, group_name, group_type FROM ' . GROUPS_TABLE . ' ORDER BY group_type DESC, group_name ASC';
		$result = $this->db->sql_query($sql);
		$groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$name = isset($this->user->lang['G_' . $row['group_name']]) ? $this->user->lang['G_' . $row['group_name']] : $row['group_name'];
			$groups[] = [
				'group_id' => (int) $row['group_id'],
				'group_name' => $name,
			];
		}
		$this->db->sql_freeresult($result);
		return $groups;
	}

	public function get_user_groups($user_id)
	{
		$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . (int) $user_id . ' AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);
		return $groups;
	}

	public function get_effective_permissions($viewer_id, $target_user_id)
	{
		$viewer_groups = $this->get_user_groups($viewer_id);
		$target_groups = $this->get_user_groups($target_user_id);
		$perm_groups = $this->get_permission_groups();

		$effective = [
			'char_create' => false,
			'char_archive' => false,
			'char_delete' => false,
			'edit_own' => false,
			'delete_own' => false,
			'types' => [],
		];
		$definitions = $this->get_definitions();
		foreach ($definitions as $def)
		{
			$id = $def['id'];
			$effective['types'][$id] = [
				'view' => false,
				'view_private_notes' => false,
				'issue' => false,
				'issue_private_notes' => false,
				'archive' => false,
				'view_archived' => false,
				'unarchive' => false,
				'edit' => false,
				'delete' => false,
				'copy' => false,
			];
		}

		foreach ($perm_groups as $pg)
		{
			// Check if pg applies to viewer
			if (empty($pg['applies_to_array']) || !array_intersect($viewer_groups, $pg['applies_to_array']))
			{
				continue;
			}

			// Check if target user has a group in exclude_groups for this permission group
			if (!empty($pg['exclude_groups_array']) && array_intersect($target_groups, $pg['exclude_groups_array']))
			{
				continue;
			}

			// Check if pg has power over target
			$has_power = false;
			if (!empty($pg['power_over_all']))
			{
				$has_power = true;
			}
			if (!$has_power && !empty($pg['power_over_self']))
			{
				if ($viewer_id == $target_user_id)
				{
					$has_power = true;
				}
			}
			if (!$has_power && !empty($pg['power_over_groups_array']))
			{
				if (array_intersect($target_groups, $pg['power_over_groups_array']))
				{
					$has_power = true;
				}
			}

			if (!$has_power)
			{
				continue;
			}

			// Merge character permissions
			$perms = $pg['permissions_array'];
			if (is_array($perms))
			{
				if (!empty($perms['char_create']))
				{
					$effective['char_create'] = true;
				}
				if (!empty($perms['char_archive']))
				{
					$effective['char_archive'] = true;
				}
				if (!empty($perms['char_delete']))
				{
					$effective['char_delete'] = true;
				}
				if (!empty($perms['edit_own']))
				{
					$effective['edit_own'] = true;
				}
				if (!empty($perms['delete_own']))
				{
					$effective['delete_own'] = true;
				}

				// Merge definition permissions
				$types = isset($perms['types']) ? $perms['types'] : $perms;
				if (is_array($types))
				{
					foreach ($types as $def_id => $p)
					{
						if (!is_array($p)) continue;

						if (!isset($effective['types'][$def_id]))
						{
							$effective['types'][$def_id] = [
								'view' => false,
								'view_private_notes' => false,
								'issue' => false,
								'issue_private_notes' => false,
								'archive' => false,
								'view_archived' => false,
								'unarchive' => false,
								'edit' => false,
								'delete' => false,
								'copy' => false,
							];
						}
						if (!empty($p['view']))
						{
							$effective['types'][$def_id]['view'] = true;
						}
						if (!empty($p['view_private_notes']))
						{
							$effective['types'][$def_id]['view_private_notes'] = true;
						}
						if (!empty($p['issue']))
						{
							$effective['types'][$def_id]['issue'] = true;
						}
						if (!empty($p['issue_private_notes']))
						{
							$effective['types'][$def_id]['issue_private_notes'] = true;
						}
						if (!empty($p['archive']))
						{
							$effective['types'][$def_id]['archive'] = true;
						}
						if (!empty($p['view_archived']))
						{
							$effective['types'][$def_id]['view_archived'] = true;
						}
						if (!empty($p['unarchive']))
						{
							$effective['types'][$def_id]['unarchive'] = true;
						}
						if (!empty($p['edit']))
						{
							$effective['types'][$def_id]['edit'] = true;
						}
						if (!empty($p['delete']))
						{
							$effective['types'][$def_id]['delete'] = true;
						}
						if (!empty($p['copy']))
						{
							$effective['types'][$def_id]['copy'] = true;
						}
					}
				}
			}
		}

		return $effective;
	}

	public function can_create_character($viewer_id, $target_user_id)
	{
		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['char_create']);
		}

		$viewer_level = $this->get_user_role_level($viewer_id);
		return ($viewer_level >= 1);
	}

	public function can_archive_character($viewer_id, $target_user_id)
	{
		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['char_archive']);
		}

		$viewer_level = $this->get_user_role_level($viewer_id);
		return ($viewer_level >= 2);
	}

	public function can_delete_character($viewer_id, $target_user_id)
	{
		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['char_delete']);
		}

		$viewer_level = $this->get_user_role_level($viewer_id);
		return ($viewer_level >= 4);
	}

	public function can_add_record($viewer_id, $target_user_id)
	{
		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			if (isset($effective['types']))
			{
				foreach ($effective['types'] as $def_id => $p)
				{
					if (!empty($p['issue']))
					{
						return true;
					}
				}
			}
			return false;
		}

		$viewer_level = $this->get_user_role_level($viewer_id);
		$target_level = $this->get_user_role_level($target_user_id);
		return ($viewer_level > 0 && ($viewer_level === 4 || $viewer_level > $target_level));
	}

	public function can_issue_type($viewer_id, $target_user_id, $def_id)
	{
		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['types'][$def_id]['issue']);
		}

		$viewer_level = $this->get_user_role_level($viewer_id);
		$def = $this->get_definition($def_id);
		return ($def && isset($def['access_level']) && $viewer_level >= $def['access_level']);
	}

	public function can_issue_private_notes($viewer_id, $target_user_id, $def_id)
	{
		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['types'][$def_id]['issue_private_notes']);
		}

		return true;
	}

	public function can_edit_record($viewer_id, $record, $target_user_id)
	{
		$def_id = $record['disciplinary_type_id'];

		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			$is_issuer = ($viewer_id == $record['issuer_user_id']);
			if ($is_issuer)
			{
				return !empty($effective['edit_own']) || !empty($effective['types'][$def_id]['edit']);
			}
			return !empty($effective['types'][$def_id]['edit']);
		}

		$viewer_level = $this->get_user_role_level($viewer_id);
		$is_issuer = ($viewer_id == $record['issuer_user_id']);
		return ($viewer_level == 4 || ($viewer_level > 0 && $is_issuer));
	}

	public function can_delete_record($viewer_id, $record, $target_user_id)
	{
		$def_id = $record['disciplinary_type_id'];

		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			$is_issuer = ($viewer_id == $record['issuer_user_id']);
			if ($is_issuer)
			{
				return !empty($effective['delete_own']) || !empty($effective['types'][$def_id]['delete']);
			}
			return !empty($effective['types'][$def_id]['delete']);
		}

		$viewer_level = $this->get_user_role_level($viewer_id);
		$is_issuer = ($viewer_id == $record['issuer_user_id']);
		return ($viewer_level == 4 || ($viewer_level > 0 && $is_issuer));
	}

	public function can_archive_record($viewer_id, $record, $target_user_id)
	{
		$def_id = $record['disciplinary_type_id'];

		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['types'][$def_id]['archive']);
		}

		$viewer_level = $this->get_user_role_level($viewer_id);
		$is_issuer = ($viewer_id == $record['issuer_user_id']);
		return ($viewer_level == 4 || ($viewer_level > 0 && $is_issuer));
	}

	public function can_unarchive_record($viewer_id, $record, $target_user_id)
	{
		$def_id = $record['disciplinary_type_id'];

		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['types'][$def_id]['unarchive']);
		}

		$viewer_level = $this->get_user_role_level($viewer_id);
		$is_issuer = ($viewer_id == $record['issuer_user_id']);
		return ($viewer_level == 4 || ($viewer_level > 0 && $is_issuer));
	}

	public function can_view_archived_record($viewer_id, $record, $target_user_id)
	{
		if (empty($record['is_archived']))
		{
			return true;
		}

		$def_id = $record['disciplinary_type_id'];

		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['types'][$def_id]['view_archived']);
		}

		return true;
	}

	public function archive_record($record_id, $reason, $archived_by_user_id)
	{
		$sql_ary = [
			'is_archived' => 1,
			'archive_reason' => $reason,
			'archived_by_user_id' => (int) $archived_by_user_id,
			'archive_date' => time(),
		];
		$sql = 'UPDATE ' . $this->table_records . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE record_id = ' . (int) $record_id;
		$this->db->sql_query($sql);
	}

	public function unarchive_record($record_id)
	{
		$sql_ary = [
			'is_archived' => 0,
			'archive_reason' => '',
			'archived_by_user_id' => 0,
			'archive_date' => 0,
		];
		$sql = 'UPDATE ' . $this->table_records . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE record_id = ' . (int) $record_id;
		$this->db->sql_query($sql);
	}

	public function can_modify_record($viewer_id, $record, $target_user_id)
	{
		return $this->can_edit_record($viewer_id, $record, $target_user_id);
	}

	public function check_view_access($viewer_id, $target_user_id, $definition)
	{
		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			$def_id = isset($definition['id']) ? $definition['id'] : '';
			$can_view = !empty($effective['types'][$def_id]['view']);
			$can_view_pn = !empty($effective['types'][$def_id]['view_private_notes']);
			return ['allowed' => $can_view, 'show_evidence' => $can_view_pn];
		}

		$viewer_level = $this->get_user_role_level($viewer_id);
		$target_level = $this->get_user_role_level($target_user_id);

		if ($viewer_level > 0 && ($viewer_level === 4 || $viewer_level > $target_level))
		{
			return ['allowed' => true, 'show_evidence' => true];
		}

		return ['allowed' => false, 'show_evidence' => false];
	}

	public function can_copy_record($viewer_id, $record, $target_user_id)
	{
		$def_id = $record['disciplinary_type_id'];

		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['types'][$def_id]['copy']);
		}

		// Legacy system
		$viewer_level = $this->get_user_role_level($viewer_id);
		return ($viewer_level > 0);
	}

	public function format_clipboard_text($record, $character_name, $issuer_username, $definition = null, $can_view_private = false)
	{
		$tpl = isset($this->config['booskit_icdisciplinary_clipboard_tpl']) ? $this->config['booskit_icdisciplinary_clipboard_tpl'] : "{METADATA}\n\n{CONTENT}\n\n{PRIVATE}";
		$date_str = $this->user->format_date($record['issue_date'], 'D M d, Y');
		$type_id = isset($record['disciplinary_type_id']) ? $record['disciplinary_type_id'] : '';
		$type_name = $definition ? $definition['name'] : $type_id;
		$type_desc = $definition ? (isset($definition['description']) ? $definition['description'] : '') : '';
		$type_color = $definition ? (isset($definition['color']) ? $definition['color'] : '') : '';
		$metadata = "Issued to: " . $character_name . " - Issued by: " . $issuer_username . " - Date Issued: " . $date_str . " - " . $type_name;

		$reason_uid = isset($record['reason_bbcode_uid']) ? $record['reason_bbcode_uid'] : '';
		$reason_options = isset($record['reason_bbcode_options']) ? (int) $record['reason_bbcode_options'] : 7;
		$reason_data = generate_text_for_edit($record['reason'], $reason_uid, $reason_options);
		$reason_raw = isset($reason_data['text']) ? $reason_data['text'] : (isset($record['reason']) ? $record['reason'] : '');

		$private_notes = '';
		if (!$can_view_private)
		{
			$private_notes = isset($this->user->lang['RESTRICTED_PRIVATE_NOTES']) ? $this->user->lang['RESTRICTED_PRIVATE_NOTES'] : 'Restricted Private Notes';
		}
		else if (!empty($record['evidence']))
		{
			$evidence_uid = isset($record['evidence_bbcode_uid']) ? $record['evidence_bbcode_uid'] : '';
			$evidence_options = isset($record['evidence_bbcode_options']) ? (int) $record['evidence_bbcode_options'] : 7;
			$evidence_data = generate_text_for_edit($record['evidence'], $evidence_uid, $evidence_options);
			$private_notes = isset($evidence_data['text']) ? $evidence_data['text'] : $record['evidence'];
		}

		$replacements = [
			'{METADATA}'         => $metadata,
			'{CONTENT}'          => $reason_raw,
			'{PRIVATE}'          => $private_notes,
			'{TYPE_COLOR}'       => $type_color,
			'{TYPE_ID}'          => $type_id,
			'{TYPE_NAME}'        => $type_name,
			'{TYPE_DESCRIPTION}' => $type_desc,
			'{TARGET_NAME}'      => $character_name,
			'{RECIPIENT_NAME}'   => $character_name,
			'{ISSUER_NAME}'      => $issuer_username,
			'{DATE}'             => $date_str,
			'{RECORD_ID}'        => isset($record['record_id']) ? $record['record_id'] : '',
			'{CHARACTER_NAME}'   => $character_name,
		];

		return str_replace(array_keys($replacements), array_values($replacements), $tpl);
	}
}
