<?php
/**
 *
 * Hide Content Extension for phpBB.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\hidecontent\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	/** @var \phpbb\controller\helper */
	protected $controller_helper;

	/** @var int Cached topic first post ID for viewtopic */
	protected $topic_first_post_id = 0;

	/** @var int Cached current forum ID */
	protected $current_forum_id = 0;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\auth\auth $auth,
		\phpbb\user $user,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		$phpbb_root_path,
		$php_ext
	) {
		$this->config = $config;
		$this->auth = $auth;
		$this->user = $user;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * Set controller helper (injected or instantiated)
	 *
	 * @param \phpbb\controller\helper $controller_helper
	 */
	public function set_controller_helper(\phpbb\controller\helper $controller_helper)
	{
		$this->controller_helper = $controller_helper;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.permissions'                         => 'add_permissions',
			'core.page_header'                         => 'add_language',
			'core.posting_modify_template_vars'        => 'inject_posting_options',
			'core.submit_post_end'                     => 'save_posting_options',
			'core.viewtopic_get_post_data'             => 'check_viewtopic_access',
			'core.viewtopic_post_rowset_data'          => 'modify_post_rowset_data',
			'core.viewtopic_assign_template_vars_before' => 'assign_topic_template_vars',
			'core.viewtopic_modify_post_row'           => 'modify_post_row',
			'core.viewforum_get_topic_ids_data'        => 'filter_viewforum_topic_ids',
			'core.viewforum_modify_topic_list_sql'     => 'filter_viewforum_topics_sql',
			'core.viewforum_get_topic_data'            => 'filter_viewforum_count',
			'core.viewforum_modify_topicrow'           => 'modify_viewforum_topicrow',
			'core.display_forums_before'               => 'filter_index_last_post',
			'core.search_get_posts_data'               => 'filter_search_posts',
			'core.search_get_topic_data'               => 'filter_search_topics',
		);
	}

	/**
	 * Register custom moderator permissions.
	 */
	public function add_permissions($event)
	{
		$permissions = $event['permissions'];
		$permissions['m_hide'] = array('lang' => 'ACL_M_HIDE', 'cat' => 'post_actions');
		$permissions['m_view_hidden'] = array('lang' => 'ACL_M_VIEW_HIDDEN', 'cat' => 'post_actions');
		$event['permissions'] = $permissions;
	}

	/**
	 * Load language files on page load.
	 */
	public function add_language()
	{
		$this->user->add_lang_ext('booskit/hidecontent', array('permissions_hidecontent', 'hidecontent'));
	}

	/**
	 * Check if user has permission to hide content in a given forum.
	 *
	 * @param int $forum_id
	 * @return bool
	 */
	protected function can_hide($forum_id = 0)
	{
		$forum_id = (int) $forum_id;
		return $this->auth->acl_get('m_hide', $forum_id) || $this->auth->acl_get('m_hide') || $this->auth->acl_get('a_') || (isset($this->user->data['user_type']) && $this->user->data['user_type'] == USER_FOUNDER);
	}

	/**
	 * Inject hide content option checkbox into posting editor template.
	 */
	public function inject_posting_options($event)
	{
		$post_data = $event['post_data'];
		$mode = $event['mode'];
		$forum_id = isset($post_data['forum_id']) ? (int) $post_data['forum_id'] : $this->request->variable('f', 0);

		if (!$this->can_hide($forum_id))
		{
			return;
		}

		$is_checked = false;

		// Check if form was submitted with post_hidden value (e.g. preview or form validation re-display)
		if ($this->request->is_set_post('post_hidden'))
		{
			$is_checked = ($this->request->variable('post_hidden', 0) === 1);
		}
		else if ($mode === 'edit')
		{
			$post_id = isset($post_data['post_id']) ? (int) $post_data['post_id'] : $this->request->variable('p', 0);
			$topic_first_post_id = isset($post_data['topic_first_post_id']) ? (int) $post_data['topic_first_post_id'] : 0;
			$is_first_post = ($topic_first_post_id > 0 && $post_id === $topic_first_post_id);

			if ($is_first_post)
			{
				$is_checked = !empty($post_data['topic_hidden']);
			}
			else
			{
				$is_checked = !empty($post_data['post_hidden']);
			}
		}

		$this->user->add_lang_ext('booskit/hidecontent', 'hidecontent');

		$this->template->assign_vars(array(
			'S_CAN_HIDE_CONTENT'   => true,
			'S_POST_HIDDEN_CHECKED' => $is_checked,
		));
	}

	/**
	 * Save hide content state when a topic or post is submitted.
	 */
	public function save_posting_options($event)
	{
		$data = $event['data'];
		$mode = $event['mode'];
		$forum_id = (int) $data['forum_id'];

		if (!$this->can_hide($forum_id))
		{
			return;
		}

		$post_hidden_input = $this->request->variable('post_hidden', 0);
		$post_id = (int) $data['post_id'];
		$topic_id = (int) $data['topic_id'];

		if ($post_id <= 0)
		{
			return;
		}

		$is_first_post = ($mode === 'post' || ($mode === 'edit' && isset($data['topic_first_post_id']) && (int) $data['topic_first_post_id'] === $post_id));

		$new_state = ($post_hidden_input === 1) ? 1 : 0;

		if ($mode === 'post')
		{
			// Posting a new topic -> set both topic and first post as hidden if checked
			if ($new_state === 1)
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_hidden = 1
					WHERE topic_id = ' . $topic_id;
				$this->db->sql_query($sql);

				$sql = 'UPDATE ' . POSTS_TABLE . '
					SET post_hidden = 1
					WHERE post_id = ' . $post_id;
				$this->db->sql_query($sql);
			}
		}
		else if ($mode === 'reply' || $mode === 'quote')
		{
			// Replying to a topic -> set reply post as hidden if checked
			if ($new_state === 1)
			{
				$sql = 'UPDATE ' . POSTS_TABLE . '
					SET post_hidden = 1
					WHERE post_id = ' . $post_id;
				$this->db->sql_query($sql);
			}
		}
		else if ($mode === 'edit')
		{
			// Editing existing post / topic
			if ($is_first_post)
			{
				// Editing topic's first post -> update topic and post hidden state
				$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_hidden = ' . $new_state . '
					WHERE topic_id = ' . $topic_id;
				$this->db->sql_query($sql);

				$sql = 'UPDATE ' . POSTS_TABLE . '
					SET post_hidden = ' . $new_state . '
					WHERE post_id = ' . $post_id;
				$this->db->sql_query($sql);
			}
			else
			{
				// Editing reply post -> update post hidden state
				$sql = 'UPDATE ' . POSTS_TABLE . '
					SET post_hidden = ' . $new_state . '
					WHERE post_id = ' . $post_id;
				$this->db->sql_query($sql);
			}
		}
	}

	/**
	 * Access control & post filtering in viewtopic.php.
	 */
	public function check_viewtopic_access($event)
	{
		$topic_data = $event['topic_data'];
		$forum_id = (int) $event['forum_id'];

		$this->current_forum_id = $forum_id;
		if (isset($topic_data['topic_first_post_id']))
		{
			$this->topic_first_post_id = (int) $topic_data['topic_first_post_id'];
		}

		$can_view_hidden = $this->auth->acl_get('m_view_hidden', $forum_id) || $this->auth->acl_get('m_view_hidden') || $this->auth->acl_get('a_') || (isset($this->user->data['user_type']) && $this->user->data['user_type'] == USER_FOUNDER);

		// Check if the topic itself is hidden
		$is_topic_hidden = (!empty($topic_data['topic_hidden']));

		if ($is_topic_hidden && !$can_view_hidden)
		{
			send_status_line(403, 'Forbidden');
			trigger_error('NOT_AUTHORISED');
		}

		// Filter hidden posts from the post list for users without m_view_hidden
		if (!$can_view_hidden)
		{
			$post_list = $event['post_list'];
			if (!empty($post_list))
			{
				$sql = 'SELECT post_id FROM ' . POSTS_TABLE . '
					WHERE ' . $this->db->sql_in_set('post_id', $post_list) . '
						AND post_hidden = 1';
				$result = $this->db->sql_query($sql);

				$hidden_post_ids = array();
				while ($row = $this->db->sql_fetchrow($result))
				{
					$hidden_post_ids[] = (int) $row['post_id'];
				}
				$this->db->sql_freeresult($result);

				if (!empty($hidden_post_ids))
				{
					$new_post_list = array_values(array_diff($post_list, $hidden_post_ids));
					$event['post_list'] = $new_post_list;

					if (isset($event['sql_ary']))
					{
						$sql_ary = $event['sql_ary'];
						if (!empty($new_post_list))
						{
							$sql_ary['WHERE'] = $this->db->sql_in_set('p.post_id', $new_post_list) . ' AND u.user_id = p.poster_id';
						}
						else
						{
							$sql_ary['WHERE'] = 'p.post_id = 0';
						}
						$event['sql_ary'] = $sql_ary;
					}
				}
			}
		}
	}

	/**
	 * Preserve post_hidden status in rowset_data array.
	 */
	public function modify_post_rowset_data($event)
	{
		$row = $event['row'];
		$rowset_data = $event['rowset_data'];
		$rowset_data['post_hidden'] = !empty($row['post_hidden']);
		$event['rowset_data'] = $rowset_data;
	}

	/**
	 * Assign topic-level template variables for hiding/unhiding in viewtopic.php.
	 */
	public function assign_topic_template_vars($event)
	{
		$topic_data = $event['topic_data'];
		$forum_id = (int) $topic_data['forum_id'];
		$topic_id = (int) $topic_data['topic_id'];

		if (isset($topic_data['topic_first_post_id']))
		{
			$this->topic_first_post_id = (int) $topic_data['topic_first_post_id'];
		}

		$can_hide = $this->can_hide($forum_id);
		$is_topic_hidden = !empty($topic_data['topic_hidden']);

		$hash = generate_link_hash('hidecontent_topic_' . $topic_id);
		$u_hide_topic = append_sid("{$this->phpbb_root_path}app.{$this->php_ext}/hidecontent/topic/{$topic_id}/hide", "hash={$hash}");
		$u_unhide_topic = append_sid("{$this->phpbb_root_path}app.{$this->php_ext}/hidecontent/topic/{$topic_id}/unhide", "hash={$hash}");

		$this->template->assign_vars(array(
			'S_CAN_HIDE_TOPIC' => $can_hide,
			'S_TOPIC_HIDDEN'    => $is_topic_hidden,
			'U_HIDE_TOPIC'     => $u_hide_topic,
			'U_UNHIDE_TOPIC'   => $u_unhide_topic,
		));
	}

	/**
	 * Assign post-level template variables in viewtopic.php.
	 */
	public function modify_post_row($event)
	{
		$row = $event['row'];
		$post_row = $event['post_row'];
		$topic_data = isset($event['topic_data']) ? $event['topic_data'] : array();

		$forum_id = isset($event['forum_id']) ? (int) $event['forum_id'] : (isset($row['forum_id']) ? (int) $row['forum_id'] : $this->current_forum_id);
		$post_id = (int) $row['post_id'];

		$can_hide = $this->can_hide($forum_id);
		$is_topic_hidden = !empty($topic_data['topic_hidden']);
		$is_post_hidden  = !empty($row['post_hidden']);

		$topic_first_post_id = isset($topic_data['topic_first_post_id']) ? (int) $topic_data['topic_first_post_id'] : $this->topic_first_post_id;
		$is_first_post = ($topic_first_post_id > 0 && $post_id === $topic_first_post_id);

		$can_hide_post = ($can_hide && !$is_first_post);

		// Background marking applied to any post if topic is hidden OR if individual post is hidden
		$is_post_marked_hidden = ($is_topic_hidden || $is_post_hidden);

		// HIDDEN POST badge shown for individually hidden reply posts (never on first post)
		$show_hidden_badge = (!$is_first_post && $is_post_hidden);

		$hash = generate_link_hash('hidecontent_post_' . $post_id);
		$u_hide_post = append_sid("{$this->phpbb_root_path}app.{$this->php_ext}/hidecontent/post/{$post_id}/hide", "hash={$hash}");
		$u_unhide_post = append_sid("{$this->phpbb_root_path}app.{$this->php_ext}/hidecontent/post/{$post_id}/unhide", "hash={$hash}");

		$post_row['S_IS_FIRST_POST']          = $is_first_post;
		$post_row['S_CAN_HIDE_POST']          = $can_hide_post;
		$post_row['S_CAN_HIDE']               = $can_hide_post;
		$post_row['S_POST_HIDDEN_CUSTOM']     = $is_post_hidden;
		$post_row['S_POST_MARKED_HIDDEN']     = $is_post_marked_hidden;
		$post_row['S_SHOW_POST_HIDDEN_BADGE'] = $show_hidden_badge;
		$post_row['U_HIDE_POST']              = $u_hide_post;
		$post_row['U_UNHIDE_POST']            = $u_unhide_post;

		$event['post_row'] = $post_row;
	}

	/**
	 * Filter hidden topics from initial viewforum.php query for unauthorized users.
	 */
	public function filter_viewforum_topic_ids($event)
	{
		$forum_data = $event['forum_data'];
		$forum_id = (int) $forum_data['forum_id'];

		$can_view = $this->auth->acl_get('m_view_hidden', $forum_id) || $this->auth->acl_get('m_view_hidden') || $this->auth->acl_get('a_') || (isset($this->user->data['user_type']) && $this->user->data['user_type'] == USER_FOUNDER);

		if (!$can_view)
		{
			$sql_ary = $event['sql_ary'];
			$sql_ary['WHERE'] .= ' AND t.topic_hidden = 0';
			$event['sql_ary'] = $sql_ary;
		}
	}

	/**
	 * Filter hidden topics SQL in viewforum.php.
	 */
	public function filter_viewforum_topics_sql($event)
	{
		$forum_id = (int) $event['forum_id'];
		$can_view = $this->auth->acl_get('m_view_hidden', $forum_id) || $this->auth->acl_get('m_view_hidden') || $this->auth->acl_get('a_') || (isset($this->user->data['user_type']) && $this->user->data['user_type'] == USER_FOUNDER);

		if (!$can_view)
		{
			$topic_list = $event['topic_list'];
			if (!empty($topic_list))
			{
				$sql = 'SELECT topic_id FROM ' . TOPICS_TABLE . '
					WHERE ' . $this->db->sql_in_set('topic_id', $topic_list) . '
						AND topic_hidden = 1';
				$result = $this->db->sql_query($sql);

				$hidden_topic_ids = array();
				while ($row = $this->db->sql_fetchrow($result))
				{
					$hidden_topic_ids[] = (int) $row['topic_id'];
				}
				$this->db->sql_freeresult($result);

				if (!empty($hidden_topic_ids))
				{
					$event['topic_list'] = array_values(array_diff($topic_list, $hidden_topic_ids));
				}
			}

			$sql_array = $event['sql_array'];
			$sql_array['WHERE'] .= ' AND t.topic_hidden = 0';
			$event['sql_array'] = $sql_array;
		}
	}

	/**
	 * Recalculate viewforum pagination topic count for users without m_view_hidden.
	 */
	public function filter_viewforum_count($event)
	{
		$forum_id = (int) $event['forum_id'];
		$can_view = $this->auth->acl_get('m_view_hidden', $forum_id) || $this->auth->acl_get('m_view_hidden') || $this->auth->acl_get('a_') || (isset($this->user->data['user_type']) && $this->user->data['user_type'] == USER_FOUNDER);

		if (!$can_view)
		{
			$sql = 'SELECT COUNT(topic_id) as total_topics
				FROM ' . TOPICS_TABLE . '
				WHERE forum_id = ' . $forum_id . '
					AND topic_hidden = 0
					AND topic_visibility = ' . ITEM_APPROVED;

			$result = $this->db->sql_query($sql);
			$total_topics = (int) $this->db->sql_fetchfield('total_topics');
			$this->db->sql_freeresult($result);

			$event['total_topic_count'] = $total_topics;
		}
	}

	/**
	 * Modify topic row template array in viewforum.php (outline highlight & last post adjustment).
	 */
	public function modify_viewforum_topicrow($event)
	{
		$row = $event['row'];
		$topic_row = $event['topic_row'];
		$forum_id = (int) $row['forum_id'];
		$topic_id = (int) $row['topic_id'];

		$can_view_hidden = $this->auth->acl_get('m_view_hidden', $forum_id) || $this->auth->acl_get('m_view_hidden') || $this->auth->acl_get('a_') || (isset($this->user->data['user_type']) && $this->user->data['user_type'] == USER_FOUNDER);

		// If moderator can view hidden content and topic is hidden, set S_TOPIC_HIDDEN flag
		if ($can_view_hidden && !empty($row['topic_hidden']))
		{
			$topic_row['S_TOPIC_HIDDEN'] = true;
		}

		// Check if topic's last post is hidden and user lacks m_view_hidden
		if (!$can_view_hidden && !empty($row['topic_last_post_id']))
		{
			$sql = 'SELECT post_hidden FROM ' . POSTS_TABLE . '
				WHERE post_id = ' . (int) $row['topic_last_post_id'];
			$result = $this->db->sql_query($sql);
			$last_post_hidden = (bool) $this->db->sql_fetchfield('post_hidden');
			$this->db->sql_freeresult($result);

			if ($last_post_hidden)
			{
				// Find the latest visible post in this topic
				$sql = 'SELECT p.post_id, p.post_time, p.post_subject, p.poster_id, p.post_username, u.username, u.user_colour
					FROM ' . POSTS_TABLE . ' p
					LEFT JOIN ' . USERS_TABLE . ' u ON (p.poster_id = u.user_id)
					WHERE p.topic_id = ' . $topic_id . '
						AND p.post_hidden = 0
						AND p.post_visibility = ' . ITEM_APPROVED . '
					ORDER BY p.post_id DESC';
				$result = $this->db->sql_query_limit($sql, 1);
				$visible_last_post = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if ($visible_last_post)
				{
					$poster_name = (!empty($visible_last_post['post_username'])) ? $visible_last_post['post_username'] : $visible_last_post['username'];
					$user_colour = (!empty($visible_last_post['user_colour'])) ? ' style="color: #' . $visible_last_post['user_colour'] . ';" class="username-coloured"' : '';
					$poster_full = ($visible_last_post['poster_id'] != ANONYMOUS) ? get_username_string('full', $visible_last_post['poster_id'], $poster_name, $visible_last_post['user_colour']) : $poster_name;

					$topic_row['LAST_POST_TIME']          = $this->user->format_date($visible_last_post['post_time']);
					$topic_row['LAST_POST_AUTHOR']        = $poster_name;
					$topic_row['LAST_POST_AUTHOR_COLOUR'] = $user_colour;
					$topic_row['LAST_POST_AUTHOR_FULL']   = $poster_full;
					$topic_row['U_LAST_POST']             = append_sid("{$this->phpbb_root_path}viewtopic.{$this->php_ext}", 'p=' . $visible_last_post['post_id'] . '#p' . $visible_last_post['post_id']);
				}
				else
				{
					$topic_row['LAST_POST_TIME']          = '';
					$topic_row['LAST_POST_AUTHOR']        = '';
					$topic_row['LAST_POST_AUTHOR_COLOUR'] = '';
					$topic_row['LAST_POST_AUTHOR_FULL']   = '';
					$topic_row['U_LAST_POST']             = '';
				}
			}
		}

		$event['topic_row'] = $topic_row;
	}

	/**
	 * Filter index.php and subforum forum lists' "Last Post/Last Reply" areas for hidden posts.
	 */
	public function filter_index_last_post($event)
	{
		$forum_rows = $event['forum_rows'];

		$last_post_ids = array();
		foreach ($forum_rows as $row)
		{
			if (!empty($row['forum_last_post_id']))
			{
				$last_post_ids[] = (int) $row['forum_last_post_id'];
			}
		}

		if (empty($last_post_ids))
		{
			return;
		}

		// Fetch status of these last posts and topics
		$sql = 'SELECT p.post_id, p.forum_id, p.post_hidden, t.topic_hidden
			FROM ' . POSTS_TABLE . ' p
			LEFT JOIN ' . TOPICS_TABLE . ' t ON (p.topic_id = t.topic_id)
			WHERE ' . $this->db->sql_in_set('p.post_id', array_unique($last_post_ids));

		$result = $this->db->sql_query($sql);
		$posts_status = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$posts_status[(int) $row['post_id']] = array(
				'forum_id'     => (int) $row['forum_id'],
				'post_hidden'  => (bool) $row['post_hidden'],
				'topic_hidden' => (bool) $row['topic_hidden'],
			);
		}
		$this->db->sql_freeresult($result);

		// Batch check m_view_hidden perms
		$view_hidden_forums = $this->auth->acl_getf('m_view_hidden', true);
		$is_admin = $this->auth->acl_get('a_') || (isset($this->user->data['user_type']) && $this->user->data['user_type'] == USER_FOUNDER);

		foreach ($forum_rows as $key => $row)
		{
			$last_post_id = (int) $row['forum_last_post_id'];
			if (!$last_post_id || !isset($posts_status[$last_post_id]))
			{
				continue;
			}

			$status = $posts_status[$last_post_id];
			$forum_id = $status['forum_id'];

			$can_view_hidden = !empty($view_hidden_forums[$forum_id]) || $is_admin;

			// If last post or its topic is hidden and current user cannot view hidden content
			if (($status['post_hidden'] || $status['topic_hidden']) && !$can_view_hidden)
			{
				// Query the actual last visible post in this forum
				$sql = 'SELECT p.post_id, p.post_subject, p.post_time, p.poster_id, p.post_username, u.username, u.user_colour
					FROM ' . POSTS_TABLE . ' p
					INNER JOIN ' . TOPICS_TABLE . ' t ON (p.topic_id = t.topic_id)
					LEFT JOIN ' . USERS_TABLE . ' u ON (p.poster_id = u.user_id)
					WHERE p.forum_id = ' . $forum_id . '
						AND p.post_hidden = 0
						AND t.topic_hidden = 0
						AND p.post_visibility = ' . ITEM_APPROVED . '
						AND t.topic_visibility = ' . ITEM_APPROVED . '
					ORDER BY p.post_id DESC';
				$result = $this->db->sql_query_limit($sql, 1);
				$visible_post = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if ($visible_post)
				{
					$poster_name = (!empty($visible_post['post_username'])) ? $visible_post['post_username'] : $visible_post['username'];
					$poster_colour = (!empty($visible_post['user_colour'])) ? $visible_post['user_colour'] : '';

					$row['forum_last_post_id']       = (int) $visible_post['post_id'];
					$row['forum_last_post_subject']  = $visible_post['post_subject'];
					$row['forum_last_post_time']     = (int) $visible_post['post_time'];
					$row['forum_last_poster_id']     = (int) $visible_post['poster_id'];
					$row['forum_last_poster_name']   = $poster_name;
					$row['forum_last_poster_colour'] = $poster_colour;
				}
				else
				{
					$row['forum_last_post_id']       = 0;
					$row['forum_last_post_subject']  = '';
					$row['forum_last_post_time']     = 0;
					$row['forum_last_poster_id']     = 0;
					$row['forum_last_poster_name']   = '';
					$row['forum_last_poster_colour'] = '';
				}

				$forum_rows[$key] = $row;
			}
		}

		$event['forum_rows'] = $forum_rows;
	}

	/**
	 * Filter hidden posts and topics out of search.php post queries.
	 */
	public function filter_search_posts($event)
	{
		if ($this->auth->acl_get('a_') || (isset($this->user->data['user_type']) && $this->user->data['user_type'] == USER_FOUNDER))
		{
			return;
		}

		$sql_array = $event['sql_array'];
		$allowed_forums = array_keys($this->auth->acl_getf('m_view_hidden', true));

		if (empty($allowed_forums))
		{
			$sql_array['WHERE'] .= ' AND p.post_hidden = 0 AND t.topic_hidden = 0';
		}
		else
		{
			$sql_array['WHERE'] .= ' AND (f.forum_id IN (' . implode(',', $allowed_forums) . ') OR (p.post_hidden = 0 AND t.topic_hidden = 0))';
		}

		$event['sql_array'] = $sql_array;
	}

	/**
	 * Filter hidden topics out of search.php topic queries.
	 */
	public function filter_search_topics($event)
	{
		if ($this->auth->acl_get('a_') || (isset($this->user->data['user_type']) && $this->user->data['user_type'] == USER_FOUNDER))
		{
			return;
		}

		$sql_where = $event['sql_where'];
		$allowed_forums = array_keys($this->auth->acl_getf('m_view_hidden', true));

		if (empty($allowed_forums))
		{
			$sql_where .= ' AND t.topic_hidden = 0';
		}
		else
		{
			$sql_where .= ' AND (t.forum_id IN (' . implode(',', $allowed_forums) . ') OR t.topic_hidden = 0)';
		}

		$event['sql_where'] = $sql_where;
	}
}
