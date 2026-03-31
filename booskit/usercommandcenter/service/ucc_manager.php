<?php
/**
 *
 * @package booskit/usercommandcenter
 * @license MIT
 *
 */

namespace booskit\usercommandcenter\service;

class ucc_manager
{
	protected $config;
	protected $db;
	protected $extension_manager;
	protected $cache;
	protected $table_prefix;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\extension\manager $extension_manager, $cache = null, $table_prefix = '')
	{
		$this->config = $config;
		$this->db = $db;
		$this->extension_manager = $extension_manager;

		if (is_object($cache))
		{
			$this->cache = $cache;
			$this->table_prefix = $table_prefix;
		}
		else
		{
			// Container passed 4 arguments and 4th was string (prefix)
			$this->cache = null;
			$this->table_prefix = (string) $cache;
		}
	}

	public function is_ext_enabled($ext_name)
	{
		return $this->extension_manager->is_enabled($ext_name);
	}

	public function get_db()
	{
		return $this->db;
	}

	public function get_allowed_groups()
	{
		$raw = isset($this->config['booskit_ucc_allowed_groups']) ? $this->config['booskit_ucc_allowed_groups'] : '';
		if (empty($raw)) return [];
		return array_map('intval', array_map('trim', explode(',', $raw)));
	}

	public function get_latest_awards($limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/awards') || !$this->config['booskit_ucc_include_awards']) return [];

		$sql = 'SELECT a.*, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_awards_users a
				JOIN ' . USERS_TABLE . ' u ON a.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON a.issuer_user_id = i.user_id
				ORDER BY a.issue_date DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$data = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $data;
	}

	public function get_total_awards()
	{
		if (!$this->is_ext_enabled('booskit/awards')) return 0;
		$sql = 'SELECT COUNT(award_id) as total FROM ' . $this->table_prefix . 'booskit_awards_users';
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_latest_career($limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/usercareer') || !$this->config['booskit_ucc_include_career']) return [];

		$sql = 'SELECT n.*, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_career_notes n
				JOIN ' . USERS_TABLE . ' u ON n.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON n.issuer_user_id = i.user_id
				ORDER BY n.note_date DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$data = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $data;
	}

	public function get_total_career()
	{
		if (!$this->is_ext_enabled('booskit/usercareer')) return 0;
		$sql = 'SELECT COUNT(note_id) as total FROM ' . $this->table_prefix . 'booskit_career_notes';
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_latest_commendations($limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/commendations') || !$this->config['booskit_ucc_include_commendations']) return [];

		$sql = 'SELECT c.*, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_commendations c
				JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON c.issuer_user_id = i.user_id
				ORDER BY c.commendation_date DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$data = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $data;
	}

	public function get_total_commendations()
	{
		if (!$this->is_ext_enabled('booskit/commendations')) return 0;
		$sql = 'SELECT COUNT(commendation_id) as total FROM ' . $this->table_prefix . 'booskit_commendations';
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_latest_disciplinary($limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/disciplinary') || !$this->config['booskit_ucc_include_disciplinary']) return [];

		$sql = 'SELECT d.*, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_disciplinary_users d
				JOIN ' . USERS_TABLE . ' u ON d.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON d.issuer_user_id = i.user_id
				ORDER BY d.issue_date DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$data = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $data;
	}

	public function get_total_disciplinary()
	{
		if (!$this->is_ext_enabled('booskit/disciplinary')) return 0;
		$sql = 'SELECT COUNT(record_id) as total FROM ' . $this->table_prefix . 'booskit_disciplinary_users';
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_latest_ic_disciplinary($limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/icdisciplinary') || !$this->config['booskit_ucc_include_ic_disciplinary']) return [];

		$sql = 'SELECT r.*, c.character_name, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_ic_records r
				JOIN ' . $this->table_prefix . 'booskit_ic_characters c ON r.character_id = c.character_id
				JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON r.issuer_user_id = i.user_id
				ORDER BY r.issue_date DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$data = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $data;
	}

	public function get_total_ic_disciplinary()
	{
		if (!$this->is_ext_enabled('booskit/icdisciplinary')) return 0;
		$sql = 'SELECT COUNT(record_id) as total FROM ' . $this->table_prefix . 'booskit_ic_records';
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_definitions($ext_name)
	{
		$cache_key = 'booskit_ucc_defs_' . str_replace('/', '_', $ext_name);
		$definitions = ($this->cache) ? $this->cache->get($cache_key) : false;
		if ($definitions !== false) return $definitions;

		$definitions = [];
		$table = '';
		$source_config = '';
		$url_config = '';

		switch ($ext_name)
		{
			case 'booskit/awards':
				$table = $this->table_prefix . 'booskit_awards_definitions';
				$source_config = 'booskit_awards_source';
				$url_config = 'booskit_awards_json_url';
				break;
			case 'booskit/usercareer':
				$table = $this->table_prefix . 'booskit_career_definitions';
				$source_config = 'booskit_career_source';
				$url_config = 'booskit_career_json_url';
				break;
			case 'booskit/disciplinary':
				$table = $this->table_prefix . 'booskit_disciplinary_definitions';
				$source_config = 'booskit_disciplinary_source';
				$url_config = 'booskit_disciplinary_json_url';
				break;
			case 'booskit/icdisciplinary':
				$table = $this->table_prefix . 'booskit_ic_definitions';
				$source_config = 'booskit_icdisciplinary_source';
				$url_config = 'booskit_icdisciplinary_json_url';
				break;
		}

		if (!$table) return [];

		$source = isset($this->config[$source_config]) ? $this->config[$source_config] : 'url';

		if ($source === 'local')
		{
			$sql = 'SELECT * FROM ' . $table;
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$id = isset($row['disc_id']) ? $row['disc_id'] : (isset($row['award_id']) ? $row['award_id'] : (isset($row['career_id']) ? $row['career_id'] : ''));
				$name = isset($row['disc_name']) ? $row['disc_name'] : (isset($row['award_name']) ? $row['award_name'] : (isset($row['career_name']) ? $row['career_name'] : ''));
				if ($id) $definitions[$id] = $name;
			}
			$this->db->sql_freeresult($result);
		}
		else
		{
			$json_url = isset($this->config[$url_config]) ? $this->config[$url_config] : '';
			if ($json_url)
			{
				$context = stream_context_create(['http' => ['timeout' => 5]]);
				$content = @file_get_contents($json_url, false, $context);
				if ($content !== false)
				{
					$data = json_decode($content, true);
					if (is_array($data))
					{
						foreach ($data as $item)
						{
							if (isset($item['id']) && isset($item['name']))
							{
								$definitions[$item['id']] = $item['name'];
							}
						}
					}
				}
			}
		}

		if ($this->cache)
		{
			$this->cache->put($cache_key, $definitions, 3600);
		}
		return $definitions;
	}

	public function get_definition_name($ext_name, $id, $definitions)
	{
		return isset($definitions[$id]) ? $definitions[$id] : $id;
	}

	public function get_usernames($user_ids)
	{
		if (empty($user_ids)) return [];
		$sql = 'SELECT user_id, username, user_colour FROM ' . USERS_TABLE . ' WHERE ' . $this->db->sql_in_set('user_id', $user_ids);
		$result = $this->db->sql_query($sql);
		$usernames = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$usernames[$row['user_id']] = $row;
		}
		$this->db->sql_freeresult($result);
		return $usernames;
	}
}
