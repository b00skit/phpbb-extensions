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

	public function is_user_staff($user_id)
	{
		$is_staff = false;
		$perms = $this->auth->acl_get_list(array($user_id), false, false);

		if (isset($perms[$user_id]))
		{
			foreach ($perms[$user_id] as $forum_id => $options)
			{
				foreach ($options as $opt => $setting)
				{
					if ($setting == 1 && (strpos($opt, 'm_') === 0 || strpos($opt, 'a_') === 0))
					{
						$is_staff = true;
						break 2;
					}
				}
			}
		}

		return $is_staff;
	}
}
