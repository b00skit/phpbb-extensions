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

	public function get_latest_awards($viewer_id, $limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/awards') || !$this->config['booskit_ucc_include_awards']) return [];

		$where = $this->get_module_where_clause('awards', $viewer_id);
		if ($where === false) return [];

		$sql = 'SELECT a.*, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_awards_users a
				JOIN ' . USERS_TABLE . ' u ON a.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON a.issuer_user_id = i.user_id
				WHERE ' . $where . '
				ORDER BY a.issue_date DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$data = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $data;
	}

	public function get_total_awards($viewer_id)
	{
		if (!$this->is_ext_enabled('booskit/awards')) return 0;

		$where = $this->get_module_where_clause('awards', $viewer_id);
		if ($where === false) return 0;

		$sql = 'SELECT COUNT(a.award_id) as total 
				FROM ' . $this->table_prefix . 'booskit_awards_users a
				WHERE ' . $where;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_latest_career($viewer_id, $limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/usercareer') || !$this->config['booskit_ucc_include_career']) return [];

		$where = $this->get_module_where_clause('career', $viewer_id);
		if ($where === false) return [];

		$sql = 'SELECT n.*, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_career_notes n
				JOIN ' . USERS_TABLE . ' u ON n.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON n.issuer_user_id = i.user_id
				WHERE ' . $where . '
				ORDER BY n.note_date DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$data = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $data;
	}

	public function get_total_career($viewer_id)
	{
		if (!$this->is_ext_enabled('booskit/usercareer')) return 0;

		$where = $this->get_module_where_clause('career', $viewer_id);
		if ($where === false) return 0;

		$sql = 'SELECT COUNT(n.note_id) as total 
				FROM ' . $this->table_prefix . 'booskit_career_notes n
				WHERE ' . $where;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_latest_commendations($viewer_id, $limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/commendations') || !$this->config['booskit_ucc_include_commendations']) return [];

		$where = $this->get_module_where_clause('commendations', $viewer_id);
		if ($where === false) return [];

		$sql = 'SELECT c.*, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_commendations c
				JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON c.issuer_user_id = i.user_id
				WHERE ' . $where . '
				ORDER BY c.commendation_date DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$data = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $data;
	}

	public function get_total_commendations($viewer_id)
	{
		if (!$this->is_ext_enabled('booskit/commendations')) return 0;

		$where = $this->get_module_where_clause('commendations', $viewer_id);
		if ($where === false) return 0;

		$sql = 'SELECT COUNT(c.commendation_id) as total 
				FROM ' . $this->table_prefix . 'booskit_commendations c
				WHERE ' . $where;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_latest_disciplinary($viewer_id, $limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/disciplinary') || !$this->config['booskit_ucc_include_disciplinary']) return [];

		$where = $this->get_module_where_clause('disciplinary', $viewer_id);
		if ($where === false) return [];

		$sql = 'SELECT d.*, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_disciplinary_users d
				JOIN ' . USERS_TABLE . ' u ON d.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON d.issuer_user_id = i.user_id
				LEFT JOIN ' . $this->table_prefix . 'booskit_disciplinary_definitions def ON d.disciplinary_type_id = def.disc_id
				WHERE ' . $where . '
				ORDER BY d.issue_date DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$data = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $data;
	}

	public function get_total_disciplinary($viewer_id)
	{
		if (!$this->is_ext_enabled('booskit/disciplinary')) return 0;

		$where = $this->get_module_where_clause('disciplinary', $viewer_id);
		if ($where === false) return 0;

		$sql = 'SELECT COUNT(d.record_id) as total 
				FROM ' . $this->table_prefix . 'booskit_disciplinary_users d
				JOIN ' . USERS_TABLE . ' u ON d.user_id = u.user_id
				LEFT JOIN ' . $this->table_prefix . 'booskit_disciplinary_definitions def ON d.disciplinary_type_id = def.disc_id
				WHERE ' . $where;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_latest_ic_disciplinary($viewer_id, $limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/icdisciplinary') || !$this->config['booskit_ucc_include_ic_disciplinary']) return [];

		$where = $this->get_module_where_clause('ic_disciplinary', $viewer_id);
		if ($where === false) return [];

		$sql = 'SELECT r.*, c.character_name, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour
				FROM ' . $this->table_prefix . 'booskit_ic_records r
				JOIN ' . $this->table_prefix . 'booskit_ic_characters c ON r.character_id = c.character_id
				JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON r.issuer_user_id = i.user_id
				WHERE ' . $where . '
				ORDER BY r.issue_date DESC';
		$result = $this->db->sql_query_limit($sql, $limit, $start);
		$data = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $data;
	}

	public function get_total_ic_disciplinary($viewer_id)
	{
		if (!$this->is_ext_enabled('booskit/icdisciplinary')) return 0;

		$where = $this->get_module_where_clause('ic_disciplinary', $viewer_id);
		if ($where === false) return 0;

		$sql = 'SELECT COUNT(r.record_id) as total 
				FROM ' . $this->table_prefix . 'booskit_ic_records r
				JOIN ' . $this->table_prefix . 'booskit_ic_characters c ON r.character_id = c.character_id
				WHERE ' . $where;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	protected function get_module_where_clause($module, $viewer_id)
	{
		$viewer_id = (int) $viewer_id;
		$user_groups = $this->get_user_groups($viewer_id);

		switch ($module)
		{
			case 'awards':
				$l1 = $this->get_config_groups('booskit_awards_access_l1');
				$l2 = $this->get_config_groups('booskit_awards_access_l2');
				$full = $this->get_config_groups('booskit_awards_access_full');
				
				$viewer_level = 0;
				if (array_intersect($user_groups, $full)) $viewer_level = 3;
				else if (array_intersect($user_groups, $l2)) $viewer_level = 2;
				else if (array_intersect($user_groups, $l1)) $viewer_level = 1;

				if ($viewer_level >= 3) return '1=1';
				
				$where = 'u.user_id = ' . $viewer_id;
				if ($viewer_level > 0)
				{
					// Staff can see targets with lower level
					$protected_groups = $full;
					if ($viewer_level == 1) $protected_groups = array_merge($protected_groups, $l2, $l1);
					else if ($viewer_level == 2) $protected_groups = array_merge($protected_groups, $l2);

					$where .= ' OR (u.user_id NOT IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $protected_groups) . '))';
				}
				return $where;

			case 'career':
				$l1 = $this->get_config_groups('booskit_career_access_l1');
				$l2 = $this->get_config_groups('booskit_career_access_l2');
				$l3 = $this->get_config_groups('booskit_career_access_l3');
				$full = $this->get_config_groups('booskit_career_access_full');
				
				$viewer_level = 0;
				if (array_intersect($user_groups, $full)) $viewer_level = 4;
				else if (array_intersect($user_groups, $l3)) $viewer_level = 3;
				else if (array_intersect($user_groups, $l2)) $viewer_level = 2;
				else if (array_intersect($user_groups, $l1)) $viewer_level = 1;

				if ($viewer_level >= 1) return '1=1';

				$global = $this->get_config_groups('booskit_career_access_view_global');
				if (array_intersect($user_groups, $global)) return '1=1';

				$local = $this->get_config_groups('booskit_career_access_view');
				if (array_intersect($user_groups, $local)) return 'u.user_id = ' . $viewer_id;
				return false;

			case 'commendations':
				$l1 = $this->get_config_groups('booskit_commendations_access_l1');
				$l2 = $this->get_config_groups('booskit_commendations_access_l2');
				$l3 = $this->get_config_groups('booskit_commendations_access_l3');
				$full = $this->get_config_groups('booskit_commendations_access_full');
				
				$viewer_level = 0;
				if (array_intersect($user_groups, $full)) $viewer_level = 4;
				else if (array_intersect($user_groups, $l3)) $viewer_level = 3;
				else if (array_intersect($user_groups, $l2)) $viewer_level = 2;
				else if (array_intersect($user_groups, $l1)) $viewer_level = 1;

				if ($viewer_level >= 1) return '1=1';

				$global = $this->get_config_groups('booskit_commendations_access_view_global');
				if (array_intersect($user_groups, $global)) return '1=1';

				$local = $this->get_config_groups('booskit_commendations_access_view');
				if (array_intersect($user_groups, $local)) return 'u.user_id = ' . $viewer_id;
				return false;

			case 'disciplinary':
				$l1 = $this->get_config_groups('booskit_disciplinary_access_l1');
				$l2 = $this->get_config_groups('booskit_disciplinary_access_l2');
				$l3 = $this->get_config_groups('booskit_disciplinary_access_l3');
				$full = $this->get_config_groups('booskit_disciplinary_access_full');
				
				$viewer_level = 0;
				if (array_intersect($user_groups, $full)) $viewer_level = 4;
				else if (array_intersect($user_groups, $l3)) $viewer_level = 3;
				else if (array_intersect($user_groups, $l2)) $viewer_level = 2;
				else if (array_intersect($user_groups, $l1)) $viewer_level = 1;

				// Staff Tiered Access (L1-4) sees everything (authorized by level)
				if ($viewer_level == 4) return '1=1';

				$where_parts = [];
				if ($viewer_level > 0)
				{
					// Tiered access: Viewer Level > Target Level. Sees everything (OOC).
					$protected_groups = $full;
					if ($viewer_level <= 3) $protected_groups = array_merge($protected_groups, $l3);
					if ($viewer_level <= 2) $protected_groups = array_merge($protected_groups, $l2);
					if ($viewer_level <= 1) $protected_groups = array_merge($protected_groups, $l1);
					
					$where_parts[] = '(u.user_id NOT IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $protected_groups) . '))';
				}

				// Global View Access -> Sees all records (OOC), NO evidence (handled in template/controller if needed, but here we just filter rows)
				$global = $this->get_config_groups('booskit_disciplinary_access_view_global');
				if (array_intersect($user_groups, $global)) return '1=1';

				// Self View Access (Local/Exempted) -> Own records, must be locally viewable
				$exempted = $this->get_config_groups('booskit_disciplinary_access_view_exempted');
				$local = $this->get_config_groups('booskit_disciplinary_access_view_local');
				if (array_intersect($user_groups, array_merge($exempted, $local))) 
				{
					// Must be locally viewable. If def is NULL (URL source), we might not know, so we'll be safe and hide if we don't know? 
					// Actually, the user says "not locally viewable", so they use local defs.
					$where_parts[] = '(u.user_id = ' . $viewer_id . ' AND (def.locally_viewable = 1 OR def.locally_viewable IS NULL))';
				}

				// Limited View Mapping -> Globally viewable records AND target in mapped group
				$limited = $this->get_config_groups('booskit_disciplinary_access_view_limited');
				if (array_intersect($user_groups, $limited))
				{
					$map = $this->get_limited_view_map();
					$target_group_ids = [];
					foreach ($user_groups as $g_id)
					{
						if (isset($map[$g_id]))
						{
							$target_group_ids = array_merge($target_group_ids, $map[$g_id]);
						}
					}
					
					if (!empty($target_group_ids))
					{
						$target_group_ids = array_unique($target_group_ids);
						$where_parts[] = '(def.globally_viewable = 1 AND u.user_id IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $target_group_ids) . '))';
					}
				}

				if (empty($where_parts)) return false;
				return '(' . implode(' OR ', $where_parts) . ')';

			case 'ic_disciplinary':
				$l1 = $this->get_config_groups('booskit_icdisciplinary_access_l1');
				$l2 = $this->get_config_groups('booskit_icdisciplinary_access_l2');
				$full = $this->get_config_groups('booskit_icdisciplinary_access_full');

				$viewer_level = 0;
				if (array_intersect($user_groups, $full)) $viewer_level = 4;
				else if (array_intersect($user_groups, $l2)) $viewer_level = 2;
				else if (array_intersect($user_groups, $l1)) $viewer_level = 1;

				if ($viewer_level == 4) return '1=1';
				if ($viewer_level == 0) return false; // IC module blocks non-staff from seeing even their own records

				$protected_groups = $full;
				if ($viewer_level <= 2) $protected_groups = array_merge($protected_groups, $l2);
				if ($viewer_level <= 1) $protected_groups = array_merge($protected_groups, $l1);

				// Staff can only see targets with a strictly lower level.
				return '(u.user_id NOT IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $protected_groups) . '))';
		}

		return '1=1';
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
