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

	/** @var string */
	protected $table_definitions;

	protected $cached_definitions = null;
	protected $cached_role_groups = null;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\cache\driver\driver_interface $cache, \phpbb\auth\auth $auth, $table, $table_definitions)
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->cache = $cache;
		$this->auth = $auth;
		$this->table = $table;
		$this->table_definitions = $table_definitions;
	}

	public function get_definitions()
	{
		if ($this->cached_definitions !== null)
		{
			return $this->cached_definitions;
		}

		$definitions = [];
		$source = isset($this->config['booskit_disciplinary_source']) ? $this->config['booskit_disciplinary_source'] : 'url';

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
					'locally_viewable' => (bool)$row['locally_viewable'],
					'globally_viewable' => (bool)$row['globally_viewable'],
					// Internal DB ID
					'def_id' => $row['def_id'],
				];
			}
			$this->db->sql_freeresult($result);
		}
		else
		{
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
							'id' => 'Faction Warning',
							'name' => 'Warning',
							'description' => 'A formal warning.',
							'color' => '#f1c40f',
							'access_level' => 1,
						],
						[
							'id' => 'ban',
							'name' => 'Ban',
							'description' => 'Account suspension.',
							'color' => '#e74c3c',
							'access_level' => 4,
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

	public function add_local_definition($id, $name, $desc, $color, $access_level, $locally_viewable, $globally_viewable)
	{
		$sql_ary = [
			'disc_id' => $id,
			'disc_name' => $name,
			'disc_desc' => $desc,
			'disc_color' => $color,
			'access_level' => (int)$access_level,
			'locally_viewable' => (int)$locally_viewable,
			'globally_viewable' => (int)$globally_viewable,
		];
		$sql = 'INSERT INTO ' . $this->table_definitions . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);
		$this->cached_definitions = null; // Clear cache
	}

	public function update_local_definition($def_id, $id, $name, $desc, $color, $access_level, $locally_viewable, $globally_viewable)
	{
		$sql_ary = [
			'disc_id' => $id,
			'disc_name' => $name,
			'disc_desc' => $desc,
			'disc_color' => $color,
			'access_level' => (int)$access_level,
			'locally_viewable' => (int)$locally_viewable,
			'globally_viewable' => (int)$globally_viewable,
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

	public function add_record($user_id, $disciplinary_type_id, $issue_date, $reason, $evidence, $issuer_user_id,
		$reason_bbcode_uid, $reason_bbcode_bitfield, $reason_bbcode_options,
		$evidence_bbcode_uid, $evidence_bbcode_bitfield, $evidence_bbcode_options)
	{
		$sql_ary = [
			'user_id' => (int) $user_id,
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
		];

		$sql = 'INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		return $this->db->sql_nextid();
	}

	public function update_record($record_id, $disciplinary_type_id, $issue_date, $reason, $evidence,
		$reason_bbcode_uid, $reason_bbcode_bitfield, $reason_bbcode_options,
		$evidence_bbcode_uid, $evidence_bbcode_bitfield, $evidence_bbcode_options)
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
				'l1' => $parse_groups('booskit_disciplinary_access_l1'),
				'l2' => $parse_groups('booskit_disciplinary_access_l2'),
				'l3' => $parse_groups('booskit_disciplinary_access_l3'),
				'full' => $parse_groups('booskit_disciplinary_access_full'),
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

	public function check_view_access($viewer_id, $target_user_id, $definition)
	{
		// 1. Check existing full access (L1-4)
		$viewer_level = $this->get_user_role_level($viewer_id);
		$target_level = $this->get_user_role_level($target_user_id);

		if ($viewer_level > 0)
		{
			// Existing logic: Full Access (4) or strictly higher than target
			if ($viewer_level === 4 || $viewer_level > $target_level)
			{
				return ['allowed' => true, 'show_evidence' => true];
			}
		}

		// 2. Check limited/public access
		$viewer_groups = $this->get_user_groups($viewer_id);

		$access_local = $this->get_config_groups('booskit_disciplinary_access_view_local');
		$access_exempted = $this->get_config_groups('booskit_disciplinary_access_view_exempted');
		$access_limited = $this->get_config_groups('booskit_disciplinary_access_view_limited');
		$access_global = $this->get_config_groups('booskit_disciplinary_access_view_global');

		// Exempted Local View -> All records, no evidence
		if (array_intersect($viewer_groups, $access_exempted))
		{
			return ['allowed' => true, 'show_evidence' => false];
		}

		// Global View -> All records, no evidence
		if (array_intersect($viewer_groups, $access_global))
		{
			return ['allowed' => true, 'show_evidence' => false];
		}

		// Local View -> Locally Viewable records, no evidence
		if (array_intersect($viewer_groups, $access_local))
		{
			if (!empty($definition['locally_viewable']))
			{
				return ['allowed' => true, 'show_evidence' => false];
			}
		}

		// Limited View -> Globally Viewable records AND target in mapped group, no evidence
		if (array_intersect($viewer_groups, $access_limited))
		{
			if (!empty($definition['globally_viewable']))
			{
				$map = $this->get_limited_view_map();
				$target_groups = $this->get_user_groups($target_user_id);

				// Iterate viewer groups to find which mapping applies
				foreach ($viewer_groups as $g_id)
				{
					if (isset($map[$g_id]))
					{
						if (array_intersect($target_groups, $map[$g_id]))
						{
							return ['allowed' => true, 'show_evidence' => false];
						}
					}
				}
			}
		}

		return ['allowed' => false, 'show_evidence' => false];
	}

	protected function get_user_groups($user_id)
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
			// Format: ViewerGroupID:TargetGroupID,TargetGroupID
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
}
