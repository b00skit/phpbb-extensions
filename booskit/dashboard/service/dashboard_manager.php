<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\service;

class dashboard_manager
{
	protected $config;
	protected $db;
	protected $extension_manager;
	protected $cache;
	protected $auth;
	protected $table_prefix;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\extension\manager $extension_manager,
		$cache = null,
		$auth = null,
		$table_prefix = ''
	) {
		$this->config = $config;
		$this->db = $db;
		$this->extension_manager = $extension_manager;
		$this->cache = is_object($cache) ? $cache : null;
		$this->auth = is_object($auth) ? $auth : null;
		$this->table_prefix = (string) $table_prefix;
	}

	public function is_ext_enabled($ext_name)
	{
		return $this->extension_manager->is_enabled($ext_name);
	}

	public function get_db()
	{
		return $this->db;
	}

	public function get_table_prefix()
	{
		return $this->table_prefix;
	}

	public function get_allowed_groups()
	{
		$raw = isset($this->config['booskit_dashboard_allowed_groups']) ? $this->config['booskit_dashboard_allowed_groups'] : '';
		if (empty($raw))
		{
			return [];
		}
		return array_map('intval', array_filter(array_map('trim', explode(',', $raw))));
	}

	public function get_user_groups($user_id)
	{
		$user_id = (int) $user_id;
		if ($user_id <= 0)
		{
			return [];
		}

		$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . $user_id . ' AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);
		return $groups;
	}

	public function is_admin($user_id = 0)
	{
		if ($this->auth !== null && $user_id === 0)
		{
			return $this->auth->acl_get('a_');
		}

		if ($this->auth !== null && $user_id > 0)
		{
			return $this->auth->acl_get('a_');
		}

		return false;
	}

	public function can_view_dashboard($viewer_id)
	{
		if (empty($this->config['booskit_dashboard_enabled']))
		{
			return false;
		}

		$allowed_groups = $this->get_allowed_groups();
		if (empty($allowed_groups))
		{
			return true;
		}

		$viewer_groups = $this->get_user_groups($viewer_id);
		return (bool) array_intersect($viewer_groups, $allowed_groups);
	}

	/**
	 * Check if a viewer has permission to access a target user's dashboard profile
	 */
	public function can_view_user_profile($viewer_id, $target_user_id)
	{
		$viewer_id = (int) $viewer_id;
		$target_user_id = (int) $target_user_id;

		if ($viewer_id === $target_user_id)
		{
			return true;
		}

		// Admin override
		if (!empty($this->config['booskit_dashboard_profile_admin_override']) && $this->is_admin($viewer_id))
		{
			return true;
		}

		$raw_map = isset($this->config['booskit_dashboard_group_profile_access']) ? trim($this->config['booskit_dashboard_group_profile_access']) : '';
		if (empty($raw_map))
		{
			// If no group matrix restrictions are configured, allow users with dashboard access
			return true;
		}

		$viewer_groups = $this->get_user_groups($viewer_id);
		$target_groups = $this->get_user_groups($target_user_id);

		// Parse group mapping: ViewerGroupID:TargetGroupID1,TargetGroupID2,...
		$lines = preg_split('/[\r\n]+/', $raw_map);
		$allowed_targets_for_viewer = [];

		foreach ($lines as $line)
		{
			$parts = explode(':', trim($line));
			if (count($parts) === 2)
			{
				$v_gid = (int) trim($parts[0]);
				if (in_array($v_gid, $viewer_groups, true))
				{
					$targets = array_map('intval', array_filter(array_map('trim', explode(',', $parts[1]))));
					foreach ($targets as $t_gid)
					{
						$allowed_targets_for_viewer[$t_gid] = true;
					}
				}
			}
		}

		if (empty($allowed_targets_for_viewer))
		{
			return false;
		}

		foreach ($target_groups as $tg)
		{
			if (isset($allowed_targets_for_viewer[$tg]))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if viewer can view what target user ISSUED on their profile
	 */
	public function can_view_issued_actions($viewer_id, $target_user_id)
	{
		$viewer_id = (int) $viewer_id;
		$target_user_id = (int) $target_user_id;

		if ($viewer_id === $target_user_id && !empty($this->config['booskit_dashboard_issued_allow_self']))
		{
			return true;
		}

		if ($this->is_admin($viewer_id))
		{
			return true;
		}

		$raw = isset($this->config['booskit_dashboard_issued_groups']) ? $this->config['booskit_dashboard_issued_groups'] : '';
		if (empty($raw))
		{
			return false;
		}

		$allowed_groups = array_map('intval', array_filter(array_map('trim', explode(',', $raw))));
		$viewer_groups = $this->get_user_groups($viewer_id);

		return (bool) array_intersect($viewer_groups, $allowed_groups);
	}

	/**
	 * Check if viewer can view recent topics visited by target user
	 */
	public function can_view_recent_topics($viewer_id, $target_user_id)
	{
		$viewer_id = (int) $viewer_id;
		$target_user_id = (int) $target_user_id;

		if ($viewer_id === $target_user_id && !empty($this->config['booskit_dashboard_recent_topics_allow_self']))
		{
			return true;
		}

		if ($this->is_admin($viewer_id))
		{
			return true;
		}

		$raw = isset($this->config['booskit_dashboard_recent_topics_groups']) ? $this->config['booskit_dashboard_recent_topics_groups'] : '';
		if (empty($raw))
		{
			return false;
		}

		$allowed_groups = array_map('intval', array_filter(array_map('trim', explode(',', $raw))));
		$viewer_groups = $this->get_user_groups($viewer_id);

		return (bool) array_intersect($viewer_groups, $allowed_groups);
	}

	/**
	 * Retrieve overall system statistics
	 */
	public function get_overview_stats()
	{
		$stats = [
			'total_users'        => (int) (isset($this->config['num_users']) ? $this->config['num_users'] : 0),
			'total_topics'       => (int) (isset($this->config['num_topics']) ? $this->config['num_topics'] : 0),
			'total_posts'        => (int) (isset($this->config['num_posts']) ? $this->config['num_posts'] : 0),
			'newest_user_id'     => (int) (isset($this->config['newest_user_id']) ? $this->config['newest_user_id'] : 0),
			'newest_username'    => isset($this->config['newest_username']) ? $this->config['newest_username'] : '',
			'newest_user_colour' => isset($this->config['newest_user_colour']) ? $this->config['newest_user_colour'] : '',
			'active_today'       => 0,
			'online_registered'  => 0,
			'online_guests'      => 0,
		];

		// Active in past 24 hours
		$past_24h = time() - 86400;
		$sql = 'SELECT COUNT(user_id) as cnt FROM ' . USERS_TABLE . ' WHERE user_lastvisit >= ' . $past_24h . ' AND user_type <> ' . USER_IGNORE;
		$res = $this->db->sql_query($sql);
		$stats['active_today'] = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($res);

		// Currently active in session window
		$online_window = time() - ((int) $this->config['load_online_time'] * 60);

		$sql = 'SELECT COUNT(DISTINCT session_user_id) as cnt FROM ' . SESSIONS_TABLE . ' WHERE session_time >= ' . $online_window . ' AND session_user_id <> ' . ANONYMOUS;
		$res = $this->db->sql_query($sql);
		$stats['online_registered'] = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($res);

		$sql = 'SELECT COUNT(DISTINCT session_ip) as cnt FROM ' . SESSIONS_TABLE . ' WHERE session_time >= ' . $online_window . ' AND session_user_id = ' . ANONYMOUS;
		$res = $this->db->sql_query($sql);
		$stats['online_guests'] = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($res);

		return $stats;
	}

	/**
	 * Retrieve active users and what they are currently browsing
	 */
	public function get_active_users_browsing($viewer_id, $limit = 40)
	{
		$online_window = time() - ((int) $this->config['load_online_time'] * 60);

		$sql = 'SELECT s.session_id, s.session_user_id, s.session_time, s.session_page, s.session_forum_id, s.session_viewonline,
				       u.user_id, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.group_id
				FROM ' . SESSIONS_TABLE . ' s
				JOIN ' . USERS_TABLE . ' u ON s.session_user_id = u.user_id
				WHERE s.session_time >= ' . $online_window . '
				  AND s.session_user_id <> ' . ANONYMOUS . '
				ORDER BY s.session_time DESC';
		$result = $this->db->sql_query($sql);

		$users = [];
		$seen_users = [];
		$topic_ids_to_fetch = [];
		$forum_ids_to_fetch = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$uid = (int) $row['user_id'];
			if (isset($seen_users[$uid]))
			{
				continue;
			}
			$seen_users[$uid] = true;

			// Extract topic_id and forum_id from session_page if not present
			$topic_id = 0;
			$forum_id = (int) $row['session_forum_id'];

			if (preg_match('/[?&]t=([0-9]+)/', $row['session_page'], $m))
			{
				$topic_id = (int) $m[1];
				$topic_ids_to_fetch[$topic_id] = true;
			}
			if ($forum_id > 0)
			{
				$forum_ids_to_fetch[$forum_id] = true;
			}

			$row['parsed_topic_id'] = $topic_id;
			$row['parsed_forum_id'] = $forum_id;
			$users[] = $row;

			if (count($users) >= $limit)
			{
				break;
			}
		}
		$this->db->sql_freeresult($result);

		// Resolve topics and their forums
		$topics_info = [];
		if (!empty($topic_ids_to_fetch))
		{
			$sql = 'SELECT topic_id, forum_id, topic_title FROM ' . TOPICS_TABLE . ' WHERE ' . $this->db->sql_in_set('topic_id', array_keys($topic_ids_to_fetch));
			$res = $this->db->sql_query($sql);
			while ($trow = $this->db->sql_fetchrow($res))
			{
				$topics_info[(int) $trow['topic_id']] = $trow;
				$forum_ids_to_fetch[(int) $trow['forum_id']] = true;
			}
			$this->db->sql_freeresult($res);
		}

		// Resolve forum names
		$forums_info = [];
		if (!empty($forum_ids_to_fetch))
		{
			$sql = 'SELECT forum_id, forum_name FROM ' . FORUMS_TABLE . ' WHERE ' . $this->db->sql_in_set('forum_id', array_keys($forum_ids_to_fetch));
			$res = $this->db->sql_query($sql);
			while ($frow = $this->db->sql_fetchrow($res))
			{
				$forums_info[(int) $frow['forum_id']] = $frow['forum_name'];
			}
			$this->db->sql_freeresult($res);
		}

		// Build user items with browsing descriptions
		$items = [];
		foreach ($users as $u)
		{
			$f_id = (int) $u['parsed_forum_id'];
			$t_id = (int) $u['parsed_topic_id'];

			if ($t_id > 0 && isset($topics_info[$t_id]))
			{
				$f_id = (int) $topics_info[$t_id]['forum_id'];
			}

			$can_read_forum = true;
			if ($f_id > 0 && $this->auth !== null)
			{
				$can_read_forum = $this->auth->acl_get('f_read', $f_id) || $this->auth->acl_get('f_list', $f_id);
			}

			$browsing_label = 'Browsing the forum';
			$browsing_url = '';
			$page = $u['session_page'];

			if (strpos($page, 'viewtopic') !== false || $t_id > 0)
			{
				if ($can_read_forum && isset($topics_info[$t_id]))
				{
					$browsing_label = 'Viewing Topic: ' . $topics_info[$t_id]['topic_title'];
					$browsing_url = 'viewtopic.php?t=' . $t_id;
				}
				else if ($can_read_forum && $f_id > 0 && isset($forums_info[$f_id]))
				{
					$browsing_label = 'Viewing Topic in ' . $forums_info[$f_id];
					$browsing_url = 'viewforum.php?f=' . $f_id;
				}
				else
				{
					$browsing_label = 'Viewing Topic';
				}
			}
			else if (strpos($page, 'viewforum') !== false || $f_id > 0)
			{
				if ($can_read_forum && isset($forums_info[$f_id]))
				{
					$browsing_label = 'Browsing Forum: ' . $forums_info[$f_id];
					$browsing_url = 'viewforum.php?f=' . $f_id;
				}
				else
				{
					$browsing_label = 'Browsing Forum';
				}
			}
			else if (strpos($page, 'posting') !== false)
			{
				if ($can_read_forum && isset($forums_info[$f_id]))
				{
					$browsing_label = 'Posting in ' . $forums_info[$f_id];
				}
				else
				{
					$browsing_label = 'Writing a Post';
				}
			}
			else if (strpos($page, 'dashboard') !== false || strpos($page, 'ucc') !== false)
			{
				$browsing_label = 'Viewing Dashboard';
			}
			else if (strpos($page, 'memberlist') !== false)
			{
				$browsing_label = 'Browsing Members / Profile';
			}
			else if (strpos($page, 'search') !== false)
			{
				$browsing_label = 'Searching Forums';
			}
			else if (strpos($page, 'adm') !== false)
			{
				$browsing_label = 'Administration Control Panel';
			}
			else if (strpos($page, 'index') !== false || empty($page))
			{
				$browsing_label = 'Viewing Board Index';
				$browsing_url = 'index.php';
			}

			$items[] = [
				'user_id'          => (int) $u['user_id'],
				'username'         => $u['username'],
				'user_colour'      => $u['user_colour'],
				'avatar'           => $u['user_avatar'],
				'avatar_type'      => $u['user_avatar_type'],
				'avatar_width'     => $u['user_avatar_width'],
				'avatar_height'    => $u['user_avatar_height'],
				'session_time'     => (int) $u['session_time'],
				'time_ago'         => $this->format_time_ago((int) $u['session_time']),
				'browsing_label'   => $browsing_label,
				'browsing_url'     => $browsing_url,
				'can_view_profile' => $this->can_view_user_profile($viewer_id, (int) $u['user_id']),
			];
		}

		return $items;
	}

	/**
	 * Retrieve hot topics for Day, Week, or Month
	 */
	public function get_hot_topics($viewer_id, $period = 'day', $limit = 10)
	{
		$since = time() - 86400; // default 1 day
		if ($period === 'week')
		{
			$since = time() - (86400 * 7);
		}
		else if ($period === 'month')
		{
			$since = time() - (86400 * 30);
		}

		// Find forums readable by viewer
		$readable_forums = [];
		$sql = 'SELECT forum_id FROM ' . FORUMS_TABLE . ' WHERE forum_type = ' . FORUM_POST;
		$res = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($res))
		{
			$fid = (int) $row['forum_id'];
			if ($this->auth === null || $this->auth->acl_get('f_read', $fid))
			{
				$readable_forums[] = $fid;
			}
		}
		$this->db->sql_freeresult($res);

		if (empty($readable_forums))
		{
			return [];
		}

		// Query topics with recent posts in period
		$sql = 'SELECT t.topic_id, t.forum_id, t.topic_title, t.topic_poster, t.topic_first_poster_name, t.topic_first_poster_colour,
				       t.topic_views, t.topic_posts_approved, t.topic_time, t.topic_last_post_time, t.topic_last_poster_name, t.topic_last_poster_colour,
				       f.forum_name, COUNT(p.post_id) as period_posts
				FROM ' . POSTS_TABLE . ' p
				JOIN ' . TOPICS_TABLE . ' t ON p.topic_id = t.topic_id
				JOIN ' . FORUMS_TABLE . ' f ON t.forum_id = f.forum_id
				WHERE p.post_time >= ' . $since . '
				  AND ' . $this->db->sql_in_set('t.forum_id', $readable_forums) . '
				  AND t.topic_visibility = 1
				GROUP BY t.topic_id, t.forum_id, t.topic_title, t.topic_poster, t.topic_first_poster_name, t.topic_first_poster_colour,
				         t.topic_views, t.topic_posts_approved, t.topic_time, t.topic_last_post_time, t.topic_last_poster_name, t.topic_last_poster_colour,
				         f.forum_name
				ORDER BY period_posts DESC, t.topic_views DESC';
		$result = $this->db->sql_query_limit($sql, $limit);
		$topics = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		// If no posts in selected period, fallback to active topics
		if (empty($topics))
		{
			$sql = 'SELECT t.topic_id, t.forum_id, t.topic_title, t.topic_poster, t.topic_first_poster_name, t.topic_first_poster_colour,
					       t.topic_views, t.topic_posts_approved, t.topic_time, t.topic_last_post_time, t.topic_last_poster_name, t.topic_last_poster_colour,
					       f.forum_name, 0 as period_posts
					FROM ' . TOPICS_TABLE . ' t
					JOIN ' . FORUMS_TABLE . ' f ON t.forum_id = f.forum_id
					WHERE ' . $this->db->sql_in_set('t.forum_id', $readable_forums) . '
					  AND t.topic_visibility = 1
					ORDER BY t.topic_last_post_time DESC';
			$result = $this->db->sql_query_limit($sql, $limit);
			$topics = $this->db->sql_fetchrowset($result);
			$this->db->sql_freeresult($result);
		}

		return $topics;
	}

	/**
	 * Log a topic view to custom tracking table
	 */
	public function log_topic_view($user_id, $topic_id, $forum_id)
	{
		$user_id = (int) $user_id;
		$topic_id = (int) $topic_id;
		$forum_id = (int) $forum_id;

		if ($user_id <= 0 || $topic_id <= 0)
		{
			return;
		}

		$table = $this->table_prefix . 'booskit_dashboard_topic_views';
		$now = time();

		// Check existing entry to update timestamp or insert
		$sql = 'SELECT view_id FROM ' . $table . ' WHERE user_id = ' . $user_id . ' AND topic_id = ' . $topic_id;
		$result = @$this->db->sql_query($sql);
		if ($result && ($row = $this->db->sql_fetchrow($result)))
		{
			$this->db->sql_freeresult($result);
			$sql = 'UPDATE ' . $table . ' SET view_time = ' . $now . ', forum_id = ' . $forum_id . ' WHERE view_id = ' . (int) $row['view_id'];
			@$this->db->sql_query($sql);
		}
		else
		{
			if ($result)
			{
				$this->db->sql_freeresult($result);
			}
			$sql_ary = [
				'user_id'   => $user_id,
				'topic_id'  => $topic_id,
				'forum_id'  => $forum_id,
				'view_time' => $now,
			];
			@$this->db->sql_query('INSERT INTO ' . $table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
		}
	}

	/**
	 * Retrieve recent topics viewed by target user
	 */
	public function get_user_recent_topics($viewer_id, $target_user_id, $limit = 20)
	{
		if (!$this->can_view_recent_topics($viewer_id, $target_user_id))
		{
			return [];
		}

		$target_user_id = (int) $target_user_id;

		// Collect topics from dashboard views table
		$table = $this->table_prefix . 'booskit_dashboard_topic_views';
		$sql = 'SELECT v.topic_id, v.forum_id, v.view_time,
				       t.topic_title, t.topic_poster, t.topic_first_poster_name, t.topic_first_poster_colour,
				       t.topic_views, t.topic_posts_approved, t.topic_last_post_time,
				       f.forum_name
				FROM ' . $table . ' v
				JOIN ' . TOPICS_TABLE . ' t ON v.topic_id = t.topic_id
				JOIN ' . FORUMS_TABLE . ' f ON t.forum_id = f.forum_id
				WHERE v.user_id = ' . $target_user_id . '
				ORDER BY v.view_time DESC';
		$result = @$this->db->sql_query_limit($sql, $limit);
		$topics = [];
		$seen_topics = [];

		if ($result)
		{
			while ($row = $this->db->sql_fetchrow($result))
			{
				$fid = (int) $row['forum_id'];
				if ($this->auth !== null && !$this->auth->acl_get('f_read', $fid))
				{
					continue;
				}
				$tid = (int) $row['topic_id'];
				$seen_topics[$tid] = true;
				$topics[] = $row;
			}
			$this->db->sql_freeresult($result);
		}

		// Fallback to TOPICS_TRACK_TABLE if fewer than 5 records
		if (count($topics) < 5)
		{
			$sql = 'SELECT tt.topic_id, tt.mark_time as view_time,
					       t.forum_id, t.topic_title, t.topic_poster, t.topic_first_poster_name, t.topic_first_poster_colour,
					       t.topic_views, t.topic_posts_approved, t.topic_last_post_time,
					       f.forum_name
					FROM ' . TOPICS_TRACK_TABLE . ' tt
					JOIN ' . TOPICS_TABLE . ' t ON tt.topic_id = t.topic_id
					JOIN ' . FORUMS_TABLE . ' f ON t.forum_id = f.forum_id
					WHERE tt.user_id = ' . $target_user_id . '
					ORDER BY tt.mark_time DESC';
			$res = @$this->db->sql_query_limit($sql, $limit);
			if ($res)
			{
				while ($row = $this->db->sql_fetchrow($res))
				{
					$fid = (int) $row['forum_id'];
					if ($this->auth !== null && !$this->auth->acl_get('f_read', $fid))
					{
						continue;
					}
					$tid = (int) $row['topic_id'];
					if (isset($seen_topics[$tid]))
					{
						continue;
					}
					$seen_topics[$tid] = true;
					$topics[] = $row;
					if (count($topics) >= $limit)
					{
						break;
					}
				}
				$this->db->sql_freeresult($res);
			}
		}

		return $topics;
	}

	/**
	 * Retrieve records ISSUED by the target user across all extensions
	 */
	public function get_user_issued_actions($viewer_id, $target_user_id)
	{
		if (!$this->can_view_issued_actions($viewer_id, $target_user_id))
		{
			return [];
		}

		$target_user_id = (int) $target_user_id;
		$issued = [
			'disciplinary'    => [],
			'ic_disciplinary' => [],
			'commendations'   => [],
			'awards'          => [],
		];

		// Disciplinary issued
		if ($this->is_ext_enabled('booskit/disciplinary'))
		{
			$disc_defs = $this->get_definitions('booskit/disciplinary');
			$sql = 'SELECT d.*, u.username, u.user_colour
					FROM ' . $this->table_prefix . 'booskit_disciplinary_users d
					JOIN ' . USERS_TABLE . ' u ON d.user_id = u.user_id
					WHERE d.issuer_user_id = ' . $target_user_id . '
					ORDER BY d.issue_date DESC';
			$res = @$this->db->sql_query_limit($sql, 20);
			if ($res)
			{
				while ($row = $this->db->sql_fetchrow($res))
				{
					$row['type_name'] = $this->get_definition_name('booskit/disciplinary', $row['disciplinary_type_id'], $disc_defs);
					$issued['disciplinary'][] = $row;
				}
				$this->db->sql_freeresult($res);
			}
		}

		// IC Disciplinary issued
		if ($this->is_ext_enabled('booskit/icdisciplinary'))
		{
			$ic_defs = $this->get_definitions('booskit/icdisciplinary');
			$sql = 'SELECT r.*, c.character_name, u.username, u.user_colour
					FROM ' . $this->table_prefix . 'booskit_ic_records r
					JOIN ' . $this->table_prefix . 'booskit_ic_characters c ON r.character_id = c.character_id
					JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
					WHERE r.issuer_user_id = ' . $target_user_id . '
					ORDER BY r.issue_date DESC';
			$res = @$this->db->sql_query_limit($sql, 20);
			if ($res)
			{
				while ($row = $this->db->sql_fetchrow($res))
				{
					$row['type_name'] = $this->get_definition_name('booskit/icdisciplinary', $row['disciplinary_type_id'], $ic_defs);
					$issued['ic_disciplinary'][] = $row;
				}
				$this->db->sql_freeresult($res);
			}
		}

		// Commendations issued
		if ($this->is_ext_enabled('booskit/commendations'))
		{
			$sql = 'SELECT c.*, u.username, u.user_colour
					FROM ' . $this->table_prefix . 'booskit_commendations c
					JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
					WHERE c.issuer_user_id = ' . $target_user_id . '
					ORDER BY c.commendation_date DESC';
			$res = @$this->db->sql_query_limit($sql, 20);
			if ($res)
			{
				$issued['commendations'] = $this->db->sql_fetchrowset($res);
				$this->db->sql_freeresult($res);
			}
		}

		// Awards issued
		if ($this->is_ext_enabled('booskit/awards'))
		{
			$award_defs = $this->get_definitions('booskit/awards');
			$sql = 'SELECT a.*, u.username, u.user_colour
					FROM ' . $this->table_prefix . 'booskit_awards_users a
					JOIN ' . USERS_TABLE . ' u ON a.user_id = u.user_id
					WHERE a.issuer_user_id = ' . $target_user_id . '
					ORDER BY a.issue_date DESC';
			$res = @$this->db->sql_query_limit($sql, 20);
			if ($res)
			{
				while ($row = $this->db->sql_fetchrow($res))
				{
					$row['type_name'] = $this->get_definition_name('booskit/awards', $row['award_definition_id'], $award_defs);
					$issued['awards'][] = $row;
				}
				$this->db->sql_freeresult($res);
			}
		}

		return $issued;
	}

	/**
	 * Retrieve comprehensive profile data for a target user respecting all extension permissions
	 */
	public function get_user_profile_data($viewer_id, $target_user_id)
	{
		$target_user_id = (int) $target_user_id;

		// Fetch user core data
		$sql = 'SELECT u.*, g.group_name, g.group_colour
				FROM ' . USERS_TABLE . ' u
				LEFT JOIN ' . GROUPS_TABLE . ' g ON u.group_id = g.group_id
				WHERE u.user_id = ' . $target_user_id;
		$res = $this->db->sql_query($sql);
		$user_data = $this->db->sql_fetchrow($res);
		$this->db->sql_freeresult($res);

		if (!$user_data)
		{
			return null;
		}

		$data = [
			'user'            => $user_data,
			'awards'          => [],
			'career'          => [],
			'commendations'   => [],
			'disciplinary'    => [],
			'ic_disciplinary' => [],
			'gtaw_characters' => [],
			'issued'          => $this->get_user_issued_actions($viewer_id, $target_user_id),
			'recent_topics'   => $this->get_user_recent_topics($viewer_id, $target_user_id),
			'can_view_issued' => $this->can_view_issued_actions($viewer_id, $target_user_id),
			'can_view_topics' => $this->can_view_recent_topics($viewer_id, $target_user_id),
		];

		// Awards
		if ($this->is_ext_enabled('booskit/awards') && !empty($this->config['booskit_dashboard_include_awards']))
		{
			$where = $this->get_module_where_clause('awards', $viewer_id);
			if ($where !== false)
			{
				$defs = $this->get_definitions('booskit/awards');
				$sql = 'SELECT a.*, i.username as issuer_name, i.user_colour as issuer_colour
						FROM ' . $this->table_prefix . 'booskit_awards_users a
						LEFT JOIN ' . USERS_TABLE . ' i ON a.issuer_user_id = i.user_id
						JOIN ' . USERS_TABLE . ' u ON a.user_id = u.user_id
						WHERE a.user_id = ' . $target_user_id . ' AND (' . $where . ')
						ORDER BY a.issue_date DESC';
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					while ($row = $this->db->sql_fetchrow($res))
					{
						$row['type_name'] = $this->get_definition_name('booskit/awards', $row['award_definition_id'], $defs);
						$data['awards'][] = $row;
					}
					$this->db->sql_freeresult($res);
				}
			}
		}

		// Career
		if ($this->is_ext_enabled('booskit/usercareer') && !empty($this->config['booskit_dashboard_include_career']))
		{
			$where = $this->get_module_where_clause('career', $viewer_id);
			if ($where !== false)
			{
				$defs = $this->get_definitions('booskit/usercareer');
				$sql = 'SELECT n.*, i.username as issuer_name, i.user_colour as issuer_colour
						FROM ' . $this->table_prefix . 'booskit_career_notes n
						LEFT JOIN ' . USERS_TABLE . ' i ON n.issuer_user_id = i.user_id
						JOIN ' . USERS_TABLE . ' u ON n.user_id = u.user_id
						WHERE n.user_id = ' . $target_user_id . ' AND (' . $where . ')
						ORDER BY n.note_date DESC';
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					while ($row = $this->db->sql_fetchrow($res))
					{
						$row['type_name'] = $this->get_definition_name('booskit/usercareer', $row['career_type_id'], $defs);
						$data['career'][] = $row;
					}
					$this->db->sql_freeresult($res);
				}
			}
		}

		// Commendations
		if ($this->is_ext_enabled('booskit/commendations') && !empty($this->config['booskit_dashboard_include_commendations']))
		{
			$where = $this->get_module_where_clause('commendations', $viewer_id);
			if ($where !== false)
			{
				$sql = 'SELECT c.*, i.username as issuer_name, i.user_colour as issuer_colour
						FROM ' . $this->table_prefix . 'booskit_commendations c
						LEFT JOIN ' . USERS_TABLE . ' i ON c.issuer_user_id = i.user_id
						JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
						WHERE c.user_id = ' . $target_user_id . ' AND (' . $where . ')
						ORDER BY c.commendation_date DESC';
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					$data['commendations'] = $this->db->sql_fetchrowset($res);
					$this->db->sql_freeresult($res);
				}
			}
		}

		// Disciplinary
		if ($this->is_ext_enabled('booskit/disciplinary') && !empty($this->config['booskit_dashboard_include_disciplinary']))
		{
			$where = $this->get_module_where_clause('disciplinary', $viewer_id);
			if ($where !== false)
			{
				$defs = $this->get_definitions('booskit/disciplinary');
				$sql = 'SELECT d.*, i.username as issuer_name, i.user_colour as issuer_colour,
						       a.username as archived_by_name, a.user_colour as archived_by_colour
						FROM ' . $this->table_prefix . 'booskit_disciplinary_users d
						LEFT JOIN ' . USERS_TABLE . ' i ON d.issuer_user_id = i.user_id
						LEFT JOIN ' . USERS_TABLE . ' a ON d.archived_by_user_id = a.user_id
						JOIN ' . USERS_TABLE . ' u ON d.user_id = u.user_id
						LEFT JOIN ' . $this->table_prefix . 'booskit_disciplinary_definitions def ON d.disciplinary_type_id = def.disc_id
						WHERE d.user_id = ' . $target_user_id . ' AND (' . $where . ')
						ORDER BY d.issue_date DESC';
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					while ($row = $this->db->sql_fetchrow($res))
					{
						$row['type_name'] = $this->get_definition_name('booskit/disciplinary', $row['disciplinary_type_id'], $defs);
						$row['can_view_evidence'] = $this->can_view_private_notes('disciplinary', $viewer_id, $target_user_id, $row['disciplinary_type_id']);
						$data['disciplinary'][] = $row;
					}
					$this->db->sql_freeresult($res);
				}
			}
		}

		// IC Disciplinary
		if ($this->is_ext_enabled('booskit/icdisciplinary') && !empty($this->config['booskit_dashboard_include_ic_disciplinary']))
		{
			$where = $this->get_module_where_clause('ic_disciplinary', $viewer_id);
			if ($where !== false)
			{
				$defs = $this->get_definitions('booskit/icdisciplinary');
				$sql = 'SELECT r.*, c.character_name, i.username as issuer_name, i.user_colour as issuer_colour,
						       a.username as archived_by_name, a.user_colour as archived_by_colour
						FROM ' . $this->table_prefix . 'booskit_ic_records r
						JOIN ' . $this->table_prefix . 'booskit_ic_characters c ON r.character_id = c.character_id
						JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
						LEFT JOIN ' . USERS_TABLE . ' i ON r.issuer_user_id = i.user_id
						LEFT JOIN ' . USERS_TABLE . ' a ON r.archived_by_user_id = a.user_id
						WHERE c.user_id = ' . $target_user_id . ' AND (' . $where . ')
						ORDER BY r.issue_date DESC';
				$res = @$this->db->sql_query($sql);
				if ($res)
				{
					while ($row = $this->db->sql_fetchrow($res))
					{
						$row['type_name'] = $this->get_definition_name('booskit/icdisciplinary', $row['disciplinary_type_id'], $defs);
						$row['can_view_evidence'] = $this->can_view_private_notes('ic_disciplinary', $viewer_id, $target_user_id, $row['disciplinary_type_id']);
						$data['ic_disciplinary'][] = $row;
					}
					$this->db->sql_freeresult($res);
				}
			}
		}

		// GTAW Tracker characters
		if ($this->is_ext_enabled('booskit/gtawtracker'))
		{
			$sql = 'SELECT * FROM ' . $this->table_prefix . 'booskit_gtaw_characters WHERE user_id = ' . $target_user_id;
			$res = @$this->db->sql_query($sql);
			if ($res)
			{
				$data['gtaw_characters'] = $this->db->sql_fetchrowset($res);
				$this->db->sql_freeresult($res);
			}
		}

		return $data;
	}

	/**
	 * UCC Aggregation methods (feeds for dashboard)
	 */
	public function get_latest_awards($viewer_id, $limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/awards') || empty($this->config['booskit_dashboard_include_awards'])) return [];

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
				JOIN ' . USERS_TABLE . ' u ON a.user_id = u.user_id
				WHERE ' . $where;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_latest_career($viewer_id, $limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/usercareer') || empty($this->config['booskit_dashboard_include_career'])) return [];

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
				JOIN ' . USERS_TABLE . ' u ON n.user_id = u.user_id
				WHERE ' . $where;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_latest_commendations($viewer_id, $limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/commendations') || empty($this->config['booskit_dashboard_include_commendations'])) return [];

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
				JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
				WHERE ' . $where;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_latest_disciplinary($viewer_id, $limit = 5, $start = 0)
	{
		if (!$this->is_ext_enabled('booskit/disciplinary') || empty($this->config['booskit_dashboard_include_disciplinary'])) return [];

		$where = $this->get_module_where_clause('disciplinary', $viewer_id);
		if ($where === false) return [];

		$sql = 'SELECT d.*, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour, a.username as archived_by_name, a.user_colour as archived_by_colour
				FROM ' . $this->table_prefix . 'booskit_disciplinary_users d
				JOIN ' . USERS_TABLE . ' u ON d.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON d.issuer_user_id = i.user_id
				LEFT JOIN ' . USERS_TABLE . ' a ON d.archived_by_user_id = a.user_id
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
		if (!$this->is_ext_enabled('booskit/icdisciplinary') || empty($this->config['booskit_dashboard_include_ic_disciplinary'])) return [];

		$where = $this->get_module_where_clause('ic_disciplinary', $viewer_id);
		if ($where === false) return [];

		$sql = 'SELECT r.*, c.character_name, u.user_id, u.username, u.user_colour, i.username as issuer_name, i.user_colour as issuer_colour, a.username as archived_by_name, a.user_colour as archived_by_colour
				FROM ' . $this->table_prefix . 'booskit_ic_records r
				JOIN ' . $this->table_prefix . 'booskit_ic_characters c ON r.character_id = c.character_id
				JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
				LEFT JOIN ' . USERS_TABLE . ' i ON r.issuer_user_id = i.user_id
				LEFT JOIN ' . USERS_TABLE . ' a ON r.archived_by_user_id = a.user_id
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
				JOIN ' . USERS_TABLE . ' u ON c.user_id = u.user_id
				WHERE ' . $where;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);
		return $total;
	}

	public function get_module_where_clause($module, $viewer_id)
	{
		$viewer_id = (int) $viewer_id;
		$user_groups = $this->get_user_groups($viewer_id);

		switch ($module)
		{
			case 'awards':
				if (isset($this->config['booskit_awards_perm_system']) && $this->config['booskit_awards_perm_system'] === 'groups')
				{
					return $this->get_groups_perm_where_clause('awards', $viewer_id, $user_groups);
				}

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
					$protected_groups = $full;
					if ($viewer_level == 1) $protected_groups = array_merge($protected_groups, $l2, $l1);
					else if ($viewer_level == 2) $protected_groups = array_merge($protected_groups, $l2);

					$where .= ' OR (u.user_id NOT IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $protected_groups) . '))';
				}
				return $where;

			case 'career':
				if (isset($this->config['booskit_career_perm_system']) && $this->config['booskit_career_perm_system'] === 'groups')
				{
					return $this->get_groups_perm_where_clause('career', $viewer_id, $user_groups);
				}

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
				if (isset($this->config['booskit_commendations_perm_system']) && $this->config['booskit_commendations_perm_system'] === 'groups')
				{
					return $this->get_groups_perm_where_clause('commendations', $viewer_id, $user_groups);
				}

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
				if (isset($this->config['booskit_disciplinary_perm_system']) && $this->config['booskit_disciplinary_perm_system'] === 'groups')
				{
					return $this->get_groups_perm_where_clause('disciplinary', $viewer_id, $user_groups);
				}

				$l1 = $this->get_config_groups('booskit_disciplinary_access_l1');
				$l2 = $this->get_config_groups('booskit_disciplinary_access_l2');
				$l3 = $this->get_config_groups('booskit_disciplinary_access_l3');
				$full = $this->get_config_groups('booskit_disciplinary_access_full');
				
				$viewer_level = 0;
				if (array_intersect($user_groups, $full)) $viewer_level = 4;
				else if (array_intersect($user_groups, $l3)) $viewer_level = 3;
				else if (array_intersect($user_groups, $l2)) $viewer_level = 2;
				else if (array_intersect($user_groups, $l1)) $viewer_level = 1;

				if ($viewer_level == 4) return '1=1';

				$where_parts = [];
				if ($viewer_level > 0)
				{
					$protected_groups = $full;
					if ($viewer_level <= 3) $protected_groups = array_merge($protected_groups, $l3);
					if ($viewer_level <= 2) $protected_groups = array_merge($protected_groups, $l2);
					if ($viewer_level <= 1) $protected_groups = array_merge($protected_groups, $l1);
					
					$where_parts[] = '(u.user_id NOT IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $protected_groups) . '))';
				}

				$global = $this->get_config_groups('booskit_disciplinary_access_view_global');
				if (array_intersect($user_groups, $global)) return '1=1';

				$exempted = $this->get_config_groups('booskit_disciplinary_access_view_exempted');
				$local = $this->get_config_groups('booskit_disciplinary_access_view_local');
				if (array_intersect($user_groups, array_merge($exempted, $local))) 
				{
					$where_parts[] = '(u.user_id = ' . $viewer_id . ' AND (def.locally_viewable = 1 OR def.locally_viewable IS NULL))';
				}

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
				if (isset($this->config['booskit_icdisciplinary_perm_system']) && $this->config['booskit_icdisciplinary_perm_system'] === 'groups')
				{
					return $this->get_groups_perm_where_clause('ic_disciplinary', $viewer_id, $user_groups);
				}

				$l1 = $this->get_config_groups('booskit_icdisciplinary_access_l1');
				$l2 = $this->get_config_groups('booskit_icdisciplinary_access_l2');
				$full = $this->get_config_groups('booskit_icdisciplinary_access_full');

				$viewer_level = 0;
				if (array_intersect($user_groups, $full)) $viewer_level = 4;
				else if (array_intersect($user_groups, $l2)) $viewer_level = 2;
				else if (array_intersect($user_groups, $l1)) $viewer_level = 1;

				if ($viewer_level == 4) return '1=1';
				if ($viewer_level == 0) return false;

				$protected_groups = $full;
				if ($viewer_level <= 2) $protected_groups = array_merge($protected_groups, $l2);
				if ($viewer_level <= 1) $protected_groups = array_merge($protected_groups, $l1);

				return '(u.user_id NOT IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $protected_groups) . '))';
		}

		return '1=1';
	}

	protected function get_groups_perm_where_clause($module, $viewer_id, $user_groups)
	{
		$table = '';
		$type_column = '';

		switch ($module)
		{
			case 'awards':
				$table = $this->table_prefix . 'booskit_awards_perm_groups';
				break;
			case 'career':
				$table = $this->table_prefix . 'booskit_career_perm_groups';
				break;
			case 'commendations':
				$table = $this->table_prefix . 'booskit_commendations_perm_groups';
				break;
			case 'disciplinary':
				$table = $this->table_prefix . 'booskit_disciplinary_perm_groups';
				$type_column = 'd.disciplinary_type_id';
				break;
			case 'ic_disciplinary':
				$table = $this->table_prefix . 'booskit_icdisciplinary_perm_groups';
				$type_column = 'r.disciplinary_type_id';
				break;
		}

		if (empty($table))
		{
			return '1=1';
		}

		$sql = 'SELECT * FROM ' . $table . ' ORDER BY perm_group_id ASC';
		$result = @$this->db->sql_query($sql);
		if (!$result)
		{
			return false;
		}

		$where_clauses = [];

		while ($pg = $this->db->sql_fetchrow($result))
		{
			$applies_to = !empty($pg['applies_to']) ? array_map('intval', array_filter(array_map('trim', explode(',', $pg['applies_to'])))) : [];
			if (empty($applies_to) || !array_intersect($user_groups, $applies_to))
			{
				continue;
			}

			$perms = !empty($pg['permissions']) ? json_decode($pg['permissions'], true) : [];

			$allowed_types = [];
			$allowed_types_archived = [];
			if ($module === 'disciplinary' || $module === 'ic_disciplinary')
			{
				if (empty($perms['types']) || !is_array($perms['types']))
				{
					continue;
				}

				foreach ($perms['types'] as $def_id => $type_perms)
				{
					if (!empty($type_perms['view']))
					{
						$allowed_types[] = $def_id;
					}
					if (!empty($type_perms['view_archived']))
					{
						$allowed_types_archived[] = $def_id;
					}
				}

				if (empty($allowed_types) && empty($allowed_types_archived))
				{
					continue;
				}
			}
			else
			{
				if (empty($perms['view']))
				{
					continue;
				}
			}

			$exclude_groups = !empty($pg['exclude_groups']) ? array_map('intval', array_filter(array_map('trim', explode(',', $pg['exclude_groups'])))) : [];
			
			$power_parts = [];
			if (!empty($pg['power_over_all']))
			{
				$power_parts[] = '1=1';
			}
			if (!empty($pg['power_over_self']))
			{
				if ($module === 'awards')
				{
					$power_parts[] = '(u.user_id = ' . (int)$viewer_id . ' OR u.user_id IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $user_groups) . '))';
				}
				else
				{
					$power_parts[] = 'u.user_id = ' . (int)$viewer_id;
				}
			}
			if (!empty($pg['power_over_groups']))
			{
				$power_over_groups = array_map('intval', array_filter(array_map('trim', explode(',', $pg['power_over_groups']))));
				if (!empty($power_over_groups))
				{
					$power_parts[] = 'u.user_id IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $power_over_groups) . ')';
				}
			}

			if (empty($power_parts))
			{
				continue;
			}

			$pg_clause = '(' . implode(' OR ', $power_parts) . ')';

			if (!empty($exclude_groups))
			{
				$pg_clause .= ' AND u.user_id NOT IN (SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE ' . $this->db->sql_in_set('group_id', $exclude_groups) . ')';
			}

			if (!empty($type_column))
			{
				$arch_column = ($module === 'disciplinary') ? 'd.is_archived' : 'r.is_archived';
				$type_conds = [];
				if (!empty($allowed_types))
				{
					$type_conds[] = '(' . $arch_column . ' = 0 AND ' . $this->db->sql_in_set($type_column, $allowed_types) . ')';
				}
				if (!empty($allowed_types_archived))
				{
					$type_conds[] = '(' . $arch_column . ' = 1 AND ' . $this->db->sql_in_set($type_column, $allowed_types_archived) . ')';
				}

				if (!empty($type_conds))
				{
					$pg_clause .= ' AND (' . implode(' OR ', $type_conds) . ')';
				}
			}

			$where_clauses[] = '(' . $pg_clause . ')';
		}
		$this->db->sql_freeresult($result);

		if (empty($where_clauses))
		{
			return false;
		}

		return '(' . implode(' OR ', $where_clauses) . ')';
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
		$cache_key = 'booskit_dashboard_defs_' . str_replace('/', '_', $ext_name);
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
			$result = @$this->db->sql_query($sql);
			if ($result)
			{
				while ($row = $this->db->sql_fetchrow($result))
				{
					$id = isset($row['disc_id']) ? $row['disc_id'] : (isset($row['award_id']) ? $row['award_id'] : (isset($row['career_id']) ? $row['career_id'] : ''));
					$name = isset($row['disc_name']) ? $row['disc_name'] : (isset($row['award_name']) ? $row['award_name'] : (isset($row['career_name']) ? $row['career_name'] : ''));
					if ($id) $definitions[$id] = $name;
				}
				$this->db->sql_freeresult($result);
			}
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

	public function can_view_private_notes($module, $viewer_id, $target_user_id, $def_id)
	{
		$viewer_groups = $this->get_user_groups($viewer_id);

		if ($module === 'disciplinary')
		{
			if (isset($this->config['booskit_disciplinary_perm_system']) && $this->config['booskit_disciplinary_perm_system'] === 'groups')
			{
				return $this->check_groups_private_notes_perm('disciplinary', $viewer_id, $target_user_id, $def_id, $viewer_groups);
			}

			$l1 = $this->get_config_groups('booskit_disciplinary_access_l1');
			$l2 = $this->get_config_groups('booskit_disciplinary_access_l2');
			$l3 = $this->get_config_groups('booskit_disciplinary_access_l3');
			$full = $this->get_config_groups('booskit_disciplinary_access_full');

			$viewer_level = 0;
			if (array_intersect($viewer_groups, $full)) $viewer_level = 4;
			else if (array_intersect($viewer_groups, $l3)) $viewer_level = 3;
			else if (array_intersect($viewer_groups, $l2)) $viewer_level = 2;
			else if (array_intersect($viewer_groups, $l1)) $viewer_level = 1;

			$target_groups = $this->get_user_groups($target_user_id);
			$target_level = 0;
			if (array_intersect($target_groups, $full)) $target_level = 4;
			else if (array_intersect($target_groups, $l3)) $target_level = 3;
			else if (array_intersect($target_groups, $l2)) $target_level = 2;
			else if (array_intersect($target_groups, $l1)) $target_level = 1;

			if ($viewer_level > 0 && ($viewer_level === 4 || $viewer_level > $target_level))
			{
				return true;
			}

			$exempted = $this->get_config_groups('booskit_disciplinary_access_view_exempted');
			if ($viewer_id == $target_user_id && array_intersect($viewer_groups, $exempted))
			{
				return true;
			}

			return false;
		}
		else if ($module === 'ic_disciplinary')
		{
			if (isset($this->config['booskit_icdisciplinary_perm_system']) && $this->config['booskit_icdisciplinary_perm_system'] === 'groups')
			{
				return $this->check_groups_private_notes_perm('ic_disciplinary', $viewer_id, $target_user_id, $def_id, $viewer_groups);
			}

			$l1 = $this->get_config_groups('booskit_icdisciplinary_access_l1');
			$l2 = $this->get_config_groups('booskit_icdisciplinary_access_l2');
			$full = $this->get_config_groups('booskit_icdisciplinary_access_full');

			$viewer_level = 0;
			if (array_intersect($viewer_groups, $full)) $viewer_level = 4;
			else if (array_intersect($viewer_groups, $l2)) $viewer_level = 2;
			else if (array_intersect($viewer_groups, $l1)) $viewer_level = 1;

			$target_groups = $this->get_user_groups($target_user_id);
			$target_level = 0;
			if (array_intersect($target_groups, $full)) $target_level = 4;
			else if (array_intersect($target_groups, $l2)) $target_level = 2;
			else if (array_intersect($target_groups, $l1)) $target_level = 1;

			if ($viewer_level > 0 && ($viewer_level === 4 || $viewer_level > $target_level))
			{
				return true;
			}

			return false;
		}

		return false;
	}

	protected function check_groups_private_notes_perm($module, $viewer_id, $target_user_id, $def_id, $viewer_groups)
	{
		$table = ($module === 'disciplinary') ? $this->table_prefix . 'booskit_disciplinary_perm_groups' : $this->table_prefix . 'booskit_icdisciplinary_perm_groups';
		
		$sql = 'SELECT * FROM ' . $table . ' ORDER BY perm_group_id ASC';
		$result = @$this->db->sql_query($sql);
		if (!$result)
		{
			return false;
		}

		$target_groups = null;

		while ($pg = $this->db->sql_fetchrow($result))
		{
			$applies_to = !empty($pg['applies_to']) ? array_map('intval', array_filter(array_map('trim', explode(',', $pg['applies_to'])))) : [];
			if (empty($applies_to) || !array_intersect($viewer_groups, $applies_to))
			{
				continue;
			}

			$perms = !empty($pg['permissions']) ? json_decode($pg['permissions'], true) : [];
			if (empty($perms['types'][$def_id]['view_private_notes']))
			{
				continue;
			}

			$has_power = false;
			if (!empty($pg['power_over_all']))
			{
				$has_power = true;
			}
			if (!$has_power && !empty($pg['power_over_self']) && $viewer_id == $target_user_id)
			{
				$has_power = true;
			}
			if (!$has_power && !empty($pg['power_over_groups']))
			{
				if ($target_groups === null)
				{
					$target_groups = $this->get_user_groups($target_user_id);
				}
				$power_over_groups = array_map('intval', array_filter(array_map('trim', explode(',', $pg['power_over_groups']))));
				if (array_intersect($target_groups, $power_over_groups))
				{
					$has_power = true;
				}
			}

			if ($has_power)
			{
				$exclude_groups = !empty($pg['exclude_groups']) ? array_map('intval', array_filter(array_map('trim', explode(',', $pg['exclude_groups'])))) : [];
				if (!empty($exclude_groups))
				{
					if ($target_groups === null)
					{
						$target_groups = $this->get_user_groups($target_user_id);
					}
					if (array_intersect($target_groups, $exclude_groups))
					{
						continue;
					}
				}

				$this->db->sql_freeresult($result);
				return true;
			}
		}
		$this->db->sql_freeresult($result);

		return false;
	}

	protected function format_time_ago($timestamp)
	{
		$diff = time() - $timestamp;
		if ($diff < 60)
		{
			return 'Just now';
		}
		if ($diff < 3600)
		{
			$mins = max(1, (int) round($diff / 60));
			return $mins . 'm ago';
		}
		if ($diff < 86400)
		{
			$hours = (int) round($diff / 3600);
			return $hours . 'h ago';
		}
		$days = (int) round($diff / 86400);
		return $days . 'd ago';
	}
}
