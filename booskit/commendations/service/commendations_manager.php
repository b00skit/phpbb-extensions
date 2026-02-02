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

	protected $cached_role_groups = null;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\cache\driver\driver_interface $cache, \phpbb\auth\auth $auth, $table_prefix)
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->cache = $cache;
		$this->auth = $auth;
		$this->table = $table_prefix . 'booskit_commendations';
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
}
