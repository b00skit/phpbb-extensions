<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\service;

class career_manager
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
	protected $table_definitions;

	protected $root_path;
	protected $php_ext;

	protected $cached_definitions = null;
	protected $cached_role_groups = null;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\cache\driver\driver_interface $cache, \phpbb\auth\auth $auth, $table, $table_definitions, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->cache = $cache;
		$this->auth = $auth;
		$this->table = $table;
		$this->table_definitions = $table_definitions;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	public function get_definitions()
	{
		if ($this->cached_definitions !== null)
		{
			return $this->cached_definitions;
		}

		$definitions = [];
		$source = isset($this->config['booskit_career_source']) ? $this->config['booskit_career_source'] : 'url';

		if ($source === 'local')
		{
			// Fetch from database
			$sql = 'SELECT * FROM ' . $this->table_definitions . ' ORDER BY def_id ASC';
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$definitions[] = [
					'id' => $row['career_id'],
					'name' => $row['career_name'],
					'description' => $row['career_desc'],
					'icon' => $row['career_icon'],
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
			$cache_key = 'booskit_career_definitions';
			$definitions = $this->cache->get($cache_key);

			if ($definitions === false)
			{
				$json_url = $this->config['booskit_career_json_url'];
				$definitions = [];

				if (!empty($json_url))
				{
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
							'id' => 'namechange',
							'name' => 'Namechange',
							'description' => 'User changed their name.',
							'icon' => 'fa-id-card',
						],
						[
							'id' => 'promotion',
							'name' => 'Promotion',
							'description' => 'User was promoted.',
							'icon' => 'fa-arrow-up',
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

	public function add_local_definition($id, $name, $desc, $icon, $enable_public_posting = 0, $poster_id = 0, $forum_id = 0, $subject_tpl = '', $body_tpl = '', $fields = '')
	{
		$sql_ary = [
			'career_id' => $id,
			'career_name' => $name,
			'career_desc' => $desc,
			'career_icon' => $icon,
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

	public function update_local_definition($def_id, $id, $name, $desc, $icon, $enable_public_posting = 0, $poster_id = 0, $forum_id = 0, $subject_tpl = '', $body_tpl = '', $fields = '')
	{
		$sql_ary = [
			'career_id' => $id,
			'career_name' => $name,
			'career_desc' => $desc,
			'career_icon' => $icon,
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

	public function get_user_notes($user_id, $limit = 0)
	{
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE user_id = ' . (int) $user_id . ' ORDER BY note_date DESC';
		if ($limit > 0)
		{
			$result = $this->db->sql_query_limit($sql, $limit);
		}
		else
		{
			$result = $this->db->sql_query($sql);
		}

		$notes = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$notes[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $notes;
	}

	public function get_note($note_id)
	{
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE note_id = ' . (int) $note_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	public function add_note($user_id, $career_type_id, $note_date, $description, $issuer_user_id, $bbcode_uid, $bbcode_bitfield, $bbcode_options)
	{
		$sql_ary = [
			'user_id' => (int) $user_id,
			'career_type_id' => $career_type_id,
			'note_date' => (int) $note_date,
			'description' => $description,
			'issuer_user_id' => (int) $issuer_user_id,
			'bbcode_uid' => $bbcode_uid,
			'bbcode_bitfield' => $bbcode_bitfield,
			'bbcode_options' => $bbcode_options,
		];

		$sql = 'INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		return $this->db->sql_nextid();
	}

	public function update_note($note_id, $career_type_id, $note_date, $description, $bbcode_uid, $bbcode_bitfield, $bbcode_options)
	{
		$sql_ary = [
			'career_type_id' => $career_type_id,
			'note_date' => (int) $note_date,
			'description' => $description,
			'bbcode_uid' => $bbcode_uid,
			'bbcode_bitfield' => $bbcode_bitfield,
			'bbcode_options' => $bbcode_options,
		];

		$sql = 'UPDATE ' . $this->table . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE note_id = ' . (int) $note_id;
		$this->db->sql_query($sql);
	}

	public function delete_note($note_id)
	{
		$sql = 'DELETE FROM ' . $this->table . ' WHERE note_id = ' . (int) $note_id;
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
				'l1' => $this->parse_groups('booskit_career_access_l1'),
				'l2' => $this->parse_groups('booskit_career_access_l2'),
				'l3' => $this->parse_groups('booskit_career_access_l3'),
				'full' => $this->parse_groups('booskit_career_access_full'),
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

	public function get_user_view_access($user_id, $target_user_id = null)
	{
		// Check inheritence: If they have role level >= 1, they have global view access.
		$role_level = $this->get_user_role_level($user_id);
		if ($role_level >= 1)
		{
			return true;
		}

		$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . (int) $user_id . ' AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$user_groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		// Global View Access
		$global_view_groups = $this->parse_groups('booskit_career_access_view_global');
		if (!empty($global_view_groups))
		{
			if (array_intersect($user_groups, $global_view_groups)) {
				return true;
			}
		}

		// Local View Access
		if ($target_user_id !== null && $user_id == $target_user_id)
		{
			$local_view_groups = $this->parse_groups('booskit_career_access_view');
			if (!empty($local_view_groups))
			{
				if (array_intersect($user_groups, $local_view_groups)) {
					return true;
				}
			}
		}

		return false;
	}

	public function create_public_post($forum_id, $poster_id, $subject, $body)
	{
		if (!function_exists('submit_post'))
		{
			include($this->root_path . 'includes/functions_posting.' . $this->php_ext);
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

		// If poster_id is different from current user, submit_post might log it as current user unless we trick it.
		// submit_post calculates permissions based on $user->data.
		// If we want to post as another user, the cleanest way in phpBB is usually to overwrite $user->data temporarily or ensure $data['poster_id'] is set (which it is).
		// However, submit_post uses $user->data['username'] for the author name if poster_id is current user.
		// If poster_id != current user, we might need to fetch the username.

		if ($poster_id != $this->user->data['user_id'])
		{
			$sql = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $poster_id;
			$result = $this->db->sql_query($sql);
			$username = $this->db->sql_fetchfield('username');
			$this->db->sql_freeresult($result);
		}
		else
		{
			$username = $this->user->data['username'];
		}

		submit_post('post', $subject, $username, POST_NORMAL, $poll, $data);

		return $data['post_id'];
	}
}
