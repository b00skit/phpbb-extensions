<?php
/**
 *
 * @package booskit/forumprivacy
 * @license MIT
 *
 */

namespace booskit\forumprivacy\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\user $user,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		$phpbb_root_path,
		$php_ext
	) {
		$this->auth = $auth;
		$this->user = $user;
		$this->db = $db;
		$this->request = $request;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.permissions'						=> 'add_permissions',
			'core.viewforum_get_topic_ids_data'		=> 'filter_viewforum_topic_ids',
			'core.viewforum_modify_topic_list_sql'	=> 'filter_viewforum_topics',
			'core.viewforum_get_topic_data'			=> 'filter_viewforum_count',
			'core.viewforum_modify_sort_data_sql'	=> 'filter_viewforum_count_deprecated',
			'core.viewtopic_modify_forum_id'		=> 'check_viewtopic_auth',
			'core.search_get_posts_data'			=> 'filter_search_posts',
			'core.search_get_topic_data'			=> 'filter_search_topics',
			'core.modify_posting_auth'				=> 'filter_posting_auth',
			'core.display_forums_modify_sql'		=> 'modify_display_forums_sql',
			'core.display_forums_modify_forum_rows'	=> 'filter_index_last_post',
		);
	}

	public function add_permissions($event)
	{
		$permissions = $event['permissions'];
		$permissions['f_view_others_topics'] = array('lang' => 'ACL_F_VIEW_OTHERS_TOPICS', 'cat' => 'post');
		$permissions['f_post_others_topics'] = array('lang' => 'ACL_F_POST_OTHERS_TOPICS', 'cat' => 'post');
		$permissions['f_search_others_topics'] = array('lang' => 'ACL_F_SEARCH_OTHERS_TOPICS', 'cat' => 'post');
		$event['permissions'] = $permissions;
	}

	/**
	 * Filter topic IDs for the initial query in viewforum.php
	 */
	public function filter_viewforum_topic_ids($event)
	{
		$forum_data = $event['forum_data'];
		$forum_id = (int) $forum_data['forum_id'];

		if (!$this->auth->acl_get('f_view_others_topics', $forum_id))
		{
			$sql_ary = $event['sql_ary'];
			$user_id = (int) $this->user->data['user_id'];
			
			// Topic types: 0 = Normal, 1 = Sticky, 2 = Announce, 3 = Global
			$sql_ary['WHERE'] .= " AND (t.topic_type > 0 OR t.topic_poster = $user_id)";
			
			$event['sql_ary'] = $sql_ary;
		}
	}

	/**
	 * Filter the topic list and data query. 
	 * Handles cases where the ID query wasn't filtered (e.g. stale cache).
	 */
	public function filter_viewforum_topics($event)
	{
		$forum_id = (int) $event['forum_id'];
		if (!$this->auth->acl_get('f_view_others_topics', $forum_id))
		{
			$topic_list = $event['topic_list'];
			$user_id = (int) $this->user->data['user_id'];

			if (!empty($topic_list))
			{
				// If IDs were already fetched, we must remove those we don't have access to
				$sql = 'SELECT topic_id 
					FROM ' . TOPICS_TABLE . '
					WHERE ' . $this->db->sql_in_set('topic_id', $topic_list) . "
						AND topic_type = 0
						AND topic_poster != $user_id";
				$result = $this->db->sql_query($sql);
				
				$hidden_ids = array();
				while ($row = $this->db->sql_fetchrow($result))
				{
					$hidden_ids[] = (int) $row['topic_id'];
				}
				$this->db->sql_freeresult($result);

				if (!empty($hidden_ids))
				{
					$event['topic_list'] = array_values(array_diff($topic_list, $hidden_ids));
				}
			}

			// Also filter the SQL query for topic data
			$sql_array = $event['sql_array'];
			$sql_array['WHERE'] .= " AND (t.topic_type > 0 OR t.topic_poster = $user_id)";
			$event['sql_array'] = $sql_array;
		}
	}

	/**
	 * Correct the total topic count for viewforum.php
	 */
	public function filter_viewforum_count($event)
	{
		$forum_id = (int) $event['forum_id'];
		if (!$this->auth->acl_get('f_view_others_topics', $forum_id))
		{
			$user_id = (int) $this->user->data['user_id'];
			
			$sql = 'SELECT COUNT(topic_id) as total_topics
				FROM ' . TOPICS_TABLE . '
				WHERE forum_id = ' . $forum_id . '
					AND (topic_type > 0 OR topic_poster = ' . $user_id . ')
					AND topic_visibility = ' . ITEM_APPROVED;
			
			$result = $this->db->sql_query($sql);
			$total_topics = (int) $this->db->sql_fetchfield('total_topics');
			$this->db->sql_freeresult($result);
			
			$event['total_topic_count'] = $total_topics;
		}
	}

	/**
	 * Deprecated count filter for stale cache support (core.viewforum_modify_sort_data_sql)
	 */
	public function filter_viewforum_count_deprecated($event)
	{
		$forum_id = (int) $this->request->variable('f', 0);
		if ($forum_id && !$this->auth->acl_get('f_view_others_topics', $forum_id))
		{
			$sql_array = $event['sql_array'];
			$user_id = (int) $this->user->data['user_id'];
			$sql_array['WHERE'] .= " AND (t.topic_type > 0 OR t.topic_poster = $user_id)";
			$event['sql_array'] = $sql_array;
		}
	}

	public function check_viewtopic_auth($event)
	{
		$topic_data = $event['topic_data'];
		$forum_id = (int) $event['forum_id'];
		
		if ($topic_data['topic_type'] == 0 && $topic_data['topic_poster'] != $this->user->data['user_id'])
		{
			if (!$this->auth->acl_get('f_view_others_topics', $forum_id))
			{
				trigger_error('NOT_AUTHORISED');
			}
		}
	}

	public function filter_search_posts($event)
	{
		$sql_array = $event['sql_array'];
		$user_id = (int) $this->user->data['user_id'];
		
		$allowed_forums = array_keys($this->auth->acl_getf('f_search_others_topics', true));
		
		if (empty($allowed_forums))
		{
			$sql_array['WHERE'] .= " AND (t.topic_type > 0 OR t.topic_poster = $user_id)";
		}
		else
		{
			$sql_array['WHERE'] .= " AND (f.forum_id IN (" . implode(',', $allowed_forums) . ") OR t.topic_type > 0 OR t.topic_poster = $user_id)";
		}
		
		$event['sql_array'] = $sql_array;
	}

	public function filter_search_topics($event)
	{
		$sql_where = $event['sql_where'];
		$user_id = (int) $this->user->data['user_id'];
		
		$allowed_forums = array_keys($this->auth->acl_getf('f_search_others_topics', true));
		
		if (empty($allowed_forums))
		{
			$sql_where .= " AND (t.topic_type > 0 OR t.topic_poster = $user_id)";
		}
		else
		{
			$sql_where .= " AND (t.forum_id IN (" . implode(',', $allowed_forums) . ") OR t.topic_type > 0 OR t.topic_poster = $user_id)";
		}
		
		$event['sql_where'] = $sql_where;
	}

	public function filter_posting_auth($event)
	{
		$forum_id = (int) $event['forum_id'];
		$topic_data = $event['topic_data'];
		$auth_ary = $event['auth_ary'];
		$mode = $this->request->variable('mode', '');

		// If we don't have topic_data but we have a topic_id in request, try to fetch it
		if (!$topic_data)
		{
			$topic_id = $this->request->variable('t', 0);
			if (!$topic_id && $this->request->variable('p', 0))
			{
				$post_id = $this->request->variable('p', 0);
				$sql = 'SELECT topic_id FROM ' . POSTS_TABLE . ' WHERE post_id = ' . (int) $post_id;
				$result = $this->db->sql_query($sql);
				$topic_id = (int) $this->db->sql_fetchfield('topic_id');
				$this->db->sql_freeresult($result);
			}

			if ($topic_id)
			{
				$sql = 'SELECT topic_poster, topic_type FROM ' . TOPICS_TABLE . ' WHERE topic_id = ' . (int) $topic_id;
				$result = $this->db->sql_query($sql);
				$topic_data = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);
			}
		}

		if ($topic_data && isset($topic_data['topic_poster']))
		{
			if (!$this->auth->acl_get('f_post_others_topics', $forum_id))
			{
				if ($topic_data['topic_type'] == 0 && $topic_data['topic_poster'] != $this->user->data['user_id'])
				{
					// If we are actually in posting.php and trying to reply/quote, stop it
					if (in_array($mode, ['reply', 'quote']))
					{
						trigger_error('NOT_AUTHORISED');
					}

					$auth_ary['f_reply'] = false;
				}
			}
		}

		$event['auth_ary'] = $auth_ary;
	}

	public function modify_display_forums_sql($event)
	{
		$sql_ary = $event['sql_ary'];
		
		$sql_ary['SELECT'] .= ', t.topic_type as forum_last_topic_type';
		$sql_ary['LEFT_JOIN'][] = array(
			'FROM'	=> array(TOPICS_TABLE => 't'),
			'ON'	=> 'f.forum_last_post_id = t.topic_last_post_id'
		);
		
		$event['sql_ary'] = $sql_ary;
	}

	public function filter_index_last_post($event)
	{
		$forum_rows = $event['forum_rows'];
		$user_id = (int) $this->user->data['user_id'];
		
		foreach ($forum_rows as $key => $row)
		{
			$forum_id = (int) $row['forum_id'];
			if (!$this->auth->acl_get('f_view_others_topics', $forum_id))
			{
				// We only hide if it's a normal topic AND not from the user
				if (isset($row['forum_last_topic_type']) && $row['forum_last_topic_type'] !== null && (int) $row['forum_last_topic_type'] === 0)
				{
					if ($row['forum_last_poster_id'] != $user_id)
					{
						$row['forum_last_post_id'] = 0;
						$row['forum_last_post_subject'] = '';
						$row['forum_last_post_time'] = 0;
						$row['forum_last_poster_id'] = 0;
						$row['forum_last_poster_name'] = '';
						$row['forum_last_poster_colour'] = '';
						
						$forum_rows[$key] = $row;
					}
				}
			}
		}
		
		$event['forum_rows'] = $forum_rows;
	}
}
