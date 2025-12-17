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

	protected $cached_definitions = null;
	protected $cached_role_groups = null;

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

	public function get_user_view_access($user_id)
	{
		// Check inheritence: If they have role level >= 1, they have view access.
		$role_level = $this->get_user_role_level($user_id);
		if ($role_level >= 1)
		{
			return true;
		}

		// Otherwise check 'booskit_career_access_view' groups
		$view_groups = $this->parse_groups('booskit_career_access_view');
		if (empty($view_groups))
		{
			// If empty, perhaps default to no access or all access?
			// "any groups that have view access ... others shouldn't" implies strict check.
			return false;
		}

		$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . (int) $user_id . ' AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$user_groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		if (array_intersect($user_groups, $view_groups)) {
			return true;
		}

		return false;
	}
}
