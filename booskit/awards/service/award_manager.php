<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\service;

class award_manager
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $table;

	/** @var string */
	protected $table_definitions;

	/** @var string */
	protected $table_perm_groups;

	protected $cached_definitions = null;
	protected $cached_role_groups = null;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, $table, $table_definitions, $table_perm_groups = '')
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->table = $table;
		$this->table_definitions = $table_definitions;
		$this->table_perm_groups = $table_perm_groups;
	}

	public function get_user_role_level($user_id)
	{
		// 0 = Regular, 1 = L1, 2 = L2, 3 = Full Access

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
				'l1' => $parse_groups('booskit_awards_access_l1'),
				'l2' => $parse_groups('booskit_awards_access_l2'),
				'full' => $parse_groups('booskit_awards_access_full'),
			];
		}

		// Fetch user's groups
		$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . (int) $user_id . ' AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$user_groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		// Determine level (highest match wins)
		if (array_intersect($user_groups, $this->cached_role_groups['full'])) {
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

	public function get_definitions()
	{
		if ($this->cached_definitions !== null)
		{
			return $this->cached_definitions;
		}

		$definitions = [];
		$source = isset($this->config['booskit_awards_source']) ? $this->config['booskit_awards_source'] : 'url';

		if ($source === 'local')
		{
			// Fetch from database
			$sql = 'SELECT * FROM ' . $this->table_definitions . ' ORDER BY def_id ASC';
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$definitions[] = [
					'id' => $row['award_id'],
					'name' => $row['award_name'],
					'description' => $row['award_desc'],
					'image' => $row['award_img'],
					'max-height' => $row['award_h'],
					'max-width' => $row['award_w'],
					// Internal DB ID
					'def_id' => $row['def_id'],
					'enable_public_posting' => isset($row['enable_public_posting']) ? (bool) $row['enable_public_posting'] : false,
					'public_posting_poster_id' => isset($row['public_posting_poster_id']) ? (int) $row['public_posting_poster_id'] : 0,
					'public_posting_forum_id' => isset($row['public_posting_forum_id']) ? (int) $row['public_posting_forum_id'] : 0,
					'public_posting_subject_tpl' => isset($row['public_posting_subject_tpl']) ? $row['public_posting_subject_tpl'] : '',
					'public_posting_body_tpl' => isset($row['public_posting_body_tpl']) ? $row['public_posting_body_tpl'] : '',
					'public_posting_fields' => isset($row['public_posting_fields']) ? $row['public_posting_fields'] : '',
				];
			}
			$this->db->sql_freeresult($result);
		}
		else
		{
			// Fetch from URL
			$json_url = $this->config['booskit_awards_json_url'];
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
						'id' => 'example_award',
						'name' => 'Example Award',
						'description' => 'This is an internal example award.',
						'image' => 'https://i.booskit.dev/u/6UPs0o.png',
						'max-height' => '150px',
						'max-width' => '150px',
					],
					[
						'id' => 'another_award',
						'name' => 'Another Award',
						'description' => 'Another internal example.',
						'image' => 'https://via.placeholder.com/150/0000FF/808080',
						'max-height' => '150px',
						'max-width' => '150px',
					]
				];
			}
		}

		$this->cached_definitions = $definitions;
		return $definitions;
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

	public function add_local_definition($id, $name, $desc, $img, $w, $h, $enable_public_posting = 0, $poster_id = 0, $forum_id = 0, $subject_tpl = '', $body_tpl = '', $fields = '')
	{
		$sql_ary = [
			'award_id' => $id,
			'award_name' => $name,
			'award_desc' => $desc,
			'award_img' => $img,
			'award_w' => $w,
			'award_h' => $h,
			'enable_public_posting' => (int) $enable_public_posting,
			'public_posting_poster_id' => (int) $poster_id,
			'public_posting_forum_id' => (int) $forum_id,
			'public_posting_subject_tpl' => $subject_tpl,
			'public_posting_body_tpl' => $body_tpl,
			'public_posting_fields' => $fields,
		];
		$sql = 'INSERT INTO ' . $this->table_definitions . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
		$this->cached_definitions = null; // Clear cache
	}

	public function update_local_definition($def_id, $id, $name, $desc, $img, $w, $h, $enable_public_posting = 0, $poster_id = 0, $forum_id = 0, $subject_tpl = '', $body_tpl = '', $fields = '')
	{
		$sql_ary = [
			'award_id' => $id,
			'award_name' => $name,
			'award_desc' => $desc,
			'award_img' => $img,
			'award_w' => $w,
			'award_h' => $h,
			'enable_public_posting' => (int) $enable_public_posting,
			'public_posting_poster_id' => (int) $poster_id,
			'public_posting_forum_id' => (int) $forum_id,
			'public_posting_subject_tpl' => $subject_tpl,
			'public_posting_body_tpl' => $body_tpl,
			'public_posting_fields' => $fields,
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

	public function get_user_awards($user_id)
	{
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE user_id = ' . (int) $user_id . ' ORDER BY issue_date DESC';
		$result = $this->db->sql_query($sql);

		$awards = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$awards[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $awards;
	}

	public function add_award($user_id, $award_definition_id, $issue_date, $comment, $issuer_user_id, $bbcode_uid, $bbcode_bitfield, $bbcode_options)
	{
		$sql_ary = [
			'user_id' => (int) $user_id,
			'award_definition_id' => $award_definition_id,
			'issue_date' => (int) $issue_date,
			'comment' => $comment,
			'issuer_user_id' => (int) $issuer_user_id,
			'bbcode_uid' => $bbcode_uid,
			'bbcode_bitfield' => $bbcode_bitfield,
			'bbcode_options' => $bbcode_options,
		];

		$sql = 'INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		return $this->db->sql_nextid();
	}

	public function get_award($award_id)
	{
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE award_id = ' . (int) $award_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	public function remove_award($award_id)
	{
		$sql = 'DELETE FROM ' . $this->table . ' WHERE award_id = ' . (int) $award_id;
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

	public function get_primary_group_name($user_id)
	{
		$sql = 'SELECT g.group_name, g.group_type
			FROM ' . USERS_TABLE . ' u
			JOIN ' . GROUPS_TABLE . ' g ON u.group_id = g.group_id
			WHERE u.user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			return ($row['group_type'] == GROUP_SPECIAL) ? $this->user->lang['G_' . $row['group_name']] : $row['group_name'];
		}

		return '';
	}

	public function create_public_post($forum_id, $poster_id, $subject, $body)
	{
		if (empty($poster_id))
		{
			$poster_id = $this->user->data['user_id'];
		}

		if (!function_exists('submit_post'))
		{
			global $phpbb_root_path, $phpEx;
			include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
		}

		$subject = utf8_normalize_nfc($subject);
		$text = utf8_normalize_nfc($body);

		$uid = $bitfield = $options = '';
		generate_text_for_storage($text, $uid, $bitfield, $options, true, true, true);

		// We need to submit the post.
		// submit_post($mode, $subject, $username, $topic_type, &$poll, &$data, $update_message = true, $update_search_index = true)

		$poll = $data = [];

		$data = [
			'topic_title'			=> $subject,
			'topic_first_post_id'	=> 0,
			'topic_last_post_id'	=> 0,
			'topic_time_limit'		=> 0,
			'topic_attachment'		=> 0,
			'post_id'				=> 0,
			'topic_id'				=> 0,
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
			'post_approve'          => 1, // Force approved? 1 = approved.
			'post_edit_locked'		=> 0,
			'notify_set'			=> false,
			'notify'				=> false,
		];

		// If poster_id is different from current user, we need to temporarily swap user data
		// so submit_post uses the correct poster_id and permissions.
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

		submit_post('post', $subject, $username, POST_NORMAL, $poll, $data);

		// Restore original user data
		$this->user->data = $user_data_backup;

		return $data['post_id'];
	}

	public function get_perm_system()
	{
		return isset($this->config['booskit_awards_perm_system']) ? $this->config['booskit_awards_perm_system'] : 'legacy';
	}

	public function get_permission_groups()
	{
		if (empty($this->table_perm_groups))
		{
			return [];
		}

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
				if ($viewer_id == $target_user_id || array_intersect($viewer_groups, $target_groups))
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
				if (!empty($perms['view']))
				{
					$effective['view'] = true;
				}
				if (!empty($perms['submit']))
				{
					$effective['submit'] = true;
				}
			}
		}

		return $effective;
	}

	public function can_view_awards($viewer_id, $target_user_id)
	{
		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['view']);
		}

		return true;
	}

	public function can_add_award($viewer_id, $target_user_id)
	{
		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['submit']);
		}

		$issuer_level = $this->get_user_role_level($viewer_id);
		$target_level = $this->get_user_role_level($target_user_id);
		return ($issuer_level >= 1 && ($issuer_level >= 3 || $target_level < $issuer_level));
	}

	public function can_remove_award($viewer_id, $target_user_id)
	{
		if ($this->get_perm_system() === 'groups')
		{
			$effective = $this->get_effective_permissions($viewer_id, $target_user_id);
			return !empty($effective['submit']);
		}

		$issuer_level = $this->get_user_role_level($viewer_id);
		$target_level = $this->get_user_role_level($target_user_id);
		return ($issuer_level >= 2 && ($issuer_level >= 3 || $target_level < $issuer_level));
	}

	public function render_user_awards_bbcode($username, $size = null)
	{
		$username_clean = utf8_clean_string(trim($username));
		if (empty($username_clean))
		{
			return '';
		}

		$sql = 'SELECT user_id FROM ' . USERS_TABLE . ' WHERE username_clean = \'' . $this->db->sql_escape($username_clean) . '\'';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			return '';
		}

		$target_user_id = (int) $row['user_id'];
		$user_awards = $this->get_user_awards($target_user_id);
		if (empty($user_awards))
		{
			return '';
		}

		$images = [];
		foreach ($user_awards as $award)
		{
			$def = $this->get_definition($award['award_definition_id']);
			if ($def && !empty($def['image']))
			{
				$img_src = htmlspecialchars($def['image'], ENT_QUOTES, 'UTF-8');
				$img_name = htmlspecialchars($def['name'], ENT_QUOTES, 'UTF-8');

				$style = '';
				if ($size !== null && $size !== '' && is_numeric($size) && (int) $size > 0)
				{
					$height_px = (int) $size;
					$style = 'style="height: ' . $height_px . 'px; width: auto;"';
				}
				else if (!empty($def['max-height']))
				{
					$h = is_numeric($def['max-height']) ? $def['max-height'] . 'px' : $def['max-height'];
					$style = 'style="height: ' . htmlspecialchars($h, ENT_QUOTES, 'UTF-8') . '; width: auto;"';
				}
				else
				{
					$style = 'style="height: auto; width: auto;"';
				}

				$images[] = '<img src="' . $img_src . '" alt="' . $img_name . '" title="' . $img_name . '" ' . $style . ' />';
			}
		}

		return implode(' ', $images);
	}
}

