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

	protected $cached_definitions = null;
	protected $cached_role_groups = null;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, $table)
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->table = $table;
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

		$json_url = $this->config['booskit_awards_json_url'];
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
					'id' => 'example_award',
					'name' => 'Example Award',
					'description' => 'This is an internal example award.',
					'image' => 'https://via.placeholder.com/150',
					'max-height' => '50px',
					'max-width' => '50px',
				],
				[
					'id' => 'another_award',
					'name' => 'Another Award',
					'description' => 'Another internal example.',
					'image' => 'https://via.placeholder.com/150/0000FF/808080',
					'max-height' => '50px',
					'max-width' => '50px',
				]
			];
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

	public function add_award($user_id, $award_definition_id, $issue_date, $comment, $issuer_user_id)
	{
		$sql_ary = [
			'user_id' => (int) $user_id,
			'award_definition_id' => $award_definition_id,
			'issue_date' => (int) $issue_date,
			'comment' => $comment,
			'issuer_user_id' => (int) $issuer_user_id,
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
}
