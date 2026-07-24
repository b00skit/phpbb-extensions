<?php
/**
 *
 * @package booskit/commendations
 * @license MIT
 *
 */

namespace booskit\commendations\service;

class commendations_manager
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

	/** @var string */
	protected $table_perm_groups;

	protected $root_path;
	protected $php_ext;

	protected $cached_role_groups = null;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\cache\driver\driver_interface $cache, \phpbb\auth\auth $auth, $table_prefix, $root_path = '', $php_ext = '')
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->cache = $cache;
		$this->auth = $auth;
		$this->table = $table_prefix . 'booskit_commendations';
		$this->table_perm_groups = $table_prefix . 'booskit_commendations_perm_groups';
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	public function get_perm_system()
	{
		return isset($this->config['booskit_commendations_perm_system']) ? $this->config['booskit_commendations_perm_system'] : 'legacy';
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
		$user_groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);
		return $user_groups;
	}

	public function get_effective_permissions($viewer_id, $target_user_id)
	{
		$viewer_groups = $this->get_user_groups($viewer_id);
		$target_groups = $this->get_user_groups($target_user_id);
		$perm_groups = $this->get_permission_groups();

		$effective = [
			'view' => false,
			'submit' => false,
		];

		foreach ($perm_groups as $pg)
		{
			if (empty($pg['applies_to_array']) || !array_intersect($viewer_groups, $pg['applies_to_array']))
			{
				continue;
			}

			if (!empty($pg['exclude_groups_array']) && array_intersect($target_groups, $pg['exclude_groups_array']))
			{
				continue;
			}

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

			$perms = $pg['permissions_array'];
			if (is_array($perms))
			{
				if (!empty($perms['view'])) $effective['view'] = true;
				if (!empty($perms['submit'])) $effective['submit'] = true;
			}
		}

		return $effective;
	}

	public function can_view_commendations($user_id, $target_user_id = null)
	{
		if ($this->get_perm_system() === 'groups')
		{
			if ($target_user_id !== null)
			{
				$effective = $this->get_effective_permissions($user_id, $target_user_id);
				return !empty($effective['view']);
			}
			else
			{
				$role_level = $this->get_user_role_level($user_id);
				if ($role_level >= 1) return true;
				$perm_groups = $this->get_permission_groups();
				$user_groups = $this->get_user_groups($user_id);
				foreach ($perm_groups as $pg)
				{
					if (!empty($pg['applies_to_array']) && array_intersect($user_groups, $pg['applies_to_array']))
					{
						$perms = $pg['permissions_array'];
						if (!empty($perms['view'])) return true;
					}
				}
				return false;
			}
		}

		return $this->get_user_view_access_legacy($user_id, $target_user_id);
	}

	public function can_add_commendation($viewer_id, $target_user_id)
	{
		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['submit']);
		}

		$viewer_level = $this->get_user_role_level($viewer_id);
		if ($viewer_level === 0) return false;
		$target_level = $this->get_user_role_level($target_user_id);
		return ($viewer_level === 4 || $viewer_level > $target_level);
	}

	public function can_edit_commendation($viewer_id, $target_user_id, $issuer_id)
	{
		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			if (!empty($effective['submit'])) return true;
			return false;
		}

		$viewer_level = $this->get_user_role_level($viewer_id);
		if ($viewer_level === 0) return false;
		$target_level = $this->get_user_role_level($target_user_id);
		$is_issuer = ($issuer_id == $viewer_id);
		if ($viewer_level === 4 || $is_issuer || ($viewer_level >= 2 && $viewer_level > $target_level))
		{
			return true;
		}
		return false;
	}

	public function can_delete_commendation($viewer_id, $target_user_id, $issuer_id)
	{
		return $this->can_edit_commendation($viewer_id, $target_user_id, $issuer_id);
	}

	public function get_user_view_access($user_id, $target_user_id = null)
	{
		if ($this->get_perm_system() === 'groups')
		{
			return $this->can_view_commendations($user_id, $target_user_id);
		}

		return $this->get_user_view_access_legacy($user_id, $target_user_id);
	}

	public function get_user_view_access_legacy($user_id, $target_user_id = null)
	{
		// Check inheritence: If they have role level >= 1, they have global view access.
		$role_level = $this->get_user_role_level($user_id);
		if ($role_level >= 1)
		{
			return true;
		}

		$user_groups = $this->get_user_groups($user_id);

		// Global View Access
		$global_view_groups = $this->parse_groups('booskit_commendations_access_view_global');
		if (!empty($global_view_groups))
		{
			if (array_intersect($user_groups, $global_view_groups)) {
				return true;
			}
		}

		// Local View Access
		if ($target_user_id !== null && $user_id == $target_user_id)
		{
			$local_view_groups = $this->parse_groups('booskit_commendations_access_view');
			if (!empty($local_view_groups))
			{
				if (array_intersect($user_groups, $local_view_groups)) {
					return true;
				}
			}
		}

		return false;
	}

	public function create_public_post($forum_id, $poster_id, $subject, $body, $mode = 'forum', $post_id = 0)
	{
		if (empty($poster_id))
		{
			$poster_id = $this->user->data['user_id'];
		}

		if (!function_exists('submit_post'))
		{
			include($this->root_path . 'includes/functions_posting.' . $this->php_ext);
		}

		$subject = utf8_normalize_nfc($subject);
		$text = utf8_normalize_nfc($body);

		$uid = $bitfield = $options = '';
		generate_text_for_storage($text, $uid, $bitfield, $options, true, true, true);

		$poll = $data = [];
		$submit_mode = 'post';
		$topic_id = 0;

		if ($mode === 'reply' && $post_id > 0)
		{
			$sql = 'SELECT topic_id, forum_id FROM ' . POSTS_TABLE . ' WHERE post_id = ' . (int) $post_id;
			$result = $this->db->sql_query($sql);
			$post_row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if ($post_row)
			{
				$topic_id = (int) $post_row['topic_id'];
				$forum_id = (int) $post_row['forum_id'];
				$submit_mode = 'reply';
			}
		}

		$data = [
			'topic_title'			=> $subject,
			'topic_first_post_id'	=> 0,
			'topic_last_post_id'	=> 0,
			'topic_time_limit'		=> 0,
			'topic_attachment'		=> 0,
			'post_id'				=> 0,
			'topic_id'				=> $topic_id,
			'forum_id'				=> $forum_id,
			'icon_id'				=> 0,
			'poster_id'				=> $poster_id,
			'enable_sig'			=> true,
			'enable_bbcode'			=> true,
			'enable_smilies'		=> true,
			'enable_urls'			=> true,
			'enable_indexing'		=> true,
			'message_md5'			=> md5($text),
			'post_time'				=> time(),
			'post_checksum'			=> '',
			'post_edit_reason'		=> '',
			'post_edit_user'		=> 0,
			'forum_parents'			=> '',
			'forum_name'			=> '',
			'post_subject'			=> $subject,
			'message'				=> $text,
			'post_text'				=> $text,
			'bbcode_uid'			=> $uid,
			'bbcode_bitfield'		=> $bitfield,
			'bbcode_options'		=> $options,
			'poster_ip'				=> $this->user->ip,
			'post_approve'          => 1,
			'post_edit_locked'		=> 0,
			'notify_set'			=> false,
			'notify'				=> false,
		];

		$user_data_backup = $this->user->data;

		if ($poster_id != $this->user->data['user_id'])
		{
			$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $poster_id;
			$result = $this->db->sql_query($sql);
			$poster_row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if ($poster_row)
			{
				$this->user->data = array_merge($this->user->data, $poster_row);
			}
		}

		$username = $this->user->data['username'];

		submit_post($submit_mode, $subject, $username, POST_NORMAL, $poll, $data);

		$this->user->data = $user_data_backup;

		return isset($data['post_id']) ? $data['post_id'] : 0;
	}

	public function get_commendations($user_id, $limit = 0)
	{
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE user_id = ' . (int) $user_id . ' ORDER BY commendation_date DESC';
		if ($limit > 0)
		{
			$result = $this->db->sql_query_limit($sql, $limit);
		}
		else
		{
			$result = $this->db->sql_query($sql);
		}

		$items = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$items[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $items;
	}

	public function get_commendation($commendation_id)
	{
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE commendation_id = ' . (int) $commendation_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	public function add_commendation($user_id, $type, $date, $character_name, $reason, $issuer_user_id, $bbcode_uid, $bbcode_bitfield, $bbcode_options)
	{
		$sql_ary = [
			'user_id' => (int) $user_id,
			'commendation_type' => $type,
			'commendation_date' => (int) $date,
			'character_name' => $character_name,
			'reason' => $reason,
			'issuer_user_id' => (int) $issuer_user_id,
			'bbcode_uid' => $bbcode_uid,
			'bbcode_bitfield' => $bbcode_bitfield,
			'bbcode_options' => $bbcode_options,
		];

		$sql = 'INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		return $this->db->sql_nextid();
	}

	public function update_commendation($commendation_id, $type, $date, $character_name, $reason, $bbcode_uid, $bbcode_bitfield, $bbcode_options)
	{
		$sql_ary = [
			'commendation_type' => $type,
			'commendation_date' => (int) $date,
			'character_name' => $character_name,
			'reason' => $reason,
			'bbcode_uid' => $bbcode_uid,
			'bbcode_bitfield' => $bbcode_bitfield,
			'bbcode_options' => $bbcode_options,
		];

		$sql = 'UPDATE ' . $this->table . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE commendation_id = ' . (int) $commendation_id;
		$this->db->sql_query($sql);
	}

	public function delete_commendation($commendation_id)
	{
		$sql = 'DELETE FROM ' . $this->table . ' WHERE commendation_id = ' . (int) $commendation_id;
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

	public function get_username_string($user_id)
	{
		$usernames = $this->get_usernames([$user_id]);
		return isset($usernames[$user_id]) ? $usernames[$user_id] : 'Unknown';
	}

	protected function parse_groups($config_key)
	{
		$raw = isset($this->config[$config_key]) ? $this->config[$config_key] : '';
		if (empty($raw))
		{
			return [];
		}
		return array_map('intval', array_map('trim', explode(',', $raw)));
	}

	public function get_user_role_level($user_id)
	{
		// 0 = Regular, 1 = L1, 2 = L2, 3 = L3, 4 = Full Access

		if ($this->cached_role_groups === null)
		{
			$this->cached_role_groups = [
				'l1' => $this->parse_groups('booskit_commendations_access_l1'),
				'l2' => $this->parse_groups('booskit_commendations_access_l2'),
				'l3' => $this->parse_groups('booskit_commendations_access_l3'),
				'full' => $this->parse_groups('booskit_commendations_access_full'),
			];
		}

		$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . (int) $user_id . ' AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$user_groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		if (array_intersect($user_groups, $this->cached_role_groups['full'])) {
			return 4;
		}
		if (array_intersect($user_groups, $this->cached_role_groups['l3'])) {
			return 3;
		}
		if (array_intersect($user_groups, $this->cached_role_groups['l2'])) {
			return 2;
		}
		if (array_intersect($user_groups, $this->cached_role_groups['l1'])) {
			return 1;
		}

		return 0;
	}
}
