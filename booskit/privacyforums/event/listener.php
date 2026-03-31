<?php
/**
 *
 * @package booskit/privacyforums
 * @license MIT
 *
 */

namespace booskit\privacyforums\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $auth;
	protected $user;
	protected $db;
	protected $request;

	public function __construct(\phpbb\auth\auth $auth, \phpbb\user $user, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request)
	{
		$this->auth = $auth;
		$this->user = $user;
		$this->db = $db;
		$this->request = $request;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'					=> 'load_language',
			'core.permissions'					=> 'add_permissions',
			'core.viewforum_get_topic_data'		=> 'restrict_view_others',
			'core.viewtopic_get_post_data'		=> 'restrict_viewtopic',
			'core.posting_modify_submission_checks' => 'restrict_post_others',
		);
	}

	public function load_language($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'booskit/privacyforums',
			'lang_set' => 'permissions_privacyforums',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function add_permissions($event)
	{
		$categories = $event['categories'];
		$permissions = $event['permissions'];

		$permissions['f_view_others'] = array('lang' => 'ACL_F_VIEW_OTHERS', 'cat' => 'post');
		$permissions['f_post_others'] = array('lang' => 'ACL_F_POST_OTHERS', 'cat' => 'post');

		$event['categories'] = $categories;
		$event['permissions'] = $permissions;
	}

	public function restrict_view_others($event)
	{
		$forum_id = (int) $event['forum_id'];

		// Check if the user has the permission to view other's content in this forum
		if (!$this->auth->acl_get('f_view_others', $forum_id))
		{
			$sql_where = $event['sql_where'];

			// We need to modify the WHERE clause to only show:
			// 1. User's own topics
			// 2. Stickies
			// 3. Announcements
			// 4. Global announcements

			// Usually topic_type:
			// ITEM_NORMAL = 0
			// ITEM_STICKY = 1
			// ITEM_ANNOUNCE = 2
			// ITEM_GLOBAL = 3

			$user_id = (int) $this->user->data['user_id'];
			
			// We append the restriction.
			// (original_where) AND (topic_poster = user_id OR topic_type IN (1, 2, 3))
			
			$restriction = ' AND (t.topic_poster = ' . $user_id . ' OR t.topic_type IN (1, 2, 3))';
			
			$event['sql_where'] = $sql_where . $restriction;

			// Recalculate topics_count for pagination
			$sql = 'SELECT COUNT(t.topic_id) as count 
				FROM ' . TOPICS_TABLE . ' t 
				WHERE ' . $event['sql_where'];
			$result = $this->db->sql_query($sql);
			$count = (int) $this->db->sql_fetchfield('count');
			$this->db->sql_freeresult($result);

			$event['topics_count'] = $count;
		}
	}

	public function restrict_viewtopic($event)
	{
		$forum_id = (int) $event['forum_id'];

		if (!$this->auth->acl_get('f_view_others', $forum_id))
		{
			$topic_data = $event['topic_data'];
			$topic_poster = (int) $topic_data['topic_poster'];
			$topic_type = (int) $topic_data['topic_type'];
			$user_id = (int) $this->user->data['user_id'];

			// If it's not their topic AND it's a normal topic, block access.
			// Sticky (1), Announce (2), Global (3) are allowed.
			if ($topic_poster !== $user_id && $topic_type === 0)
			{
				trigger_error('NOT_AUTHORISED');
			}
		}
	}

	public function restrict_post_others($event)
	{
		$forum_id = (int) $event['forum_id'];
		$mode = $event['mode'];

		// If it's a reply and the user doesn't have permission to post on other's content
		if ($mode === 'reply' && !$this->auth->acl_get('f_post_others', $forum_id))
		{
			$topic_id = (int) $this->request->variable('t', 0);
			if ($topic_id)
			{
				$sql = 'SELECT topic_poster, topic_type FROM ' . TOPICS_TABLE . ' WHERE topic_id = ' . $topic_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if ($row)
				{
					$topic_poster = (int) $row['topic_poster'];
					$topic_type = (int) $row['topic_type'];
					$user_id = (int) $this->user->data['user_id'];

					// If it's not their topic AND it's not a special topic type (sticky/announce/global)
					// (Assuming they can reply to stickies/announcements if those are "public")
					// Actually the user said: "you should only see... sticky, announcements + global announcements... same for posting."
					// So if it's not their topic AND not a special type, block.
					if ($topic_poster !== $user_id && $topic_type === 0)
					{
						$error = $event['error'];
						$error[] = $this->user->lang('NOT_YOUR_TOPIC_REPLY');
						$event['error'] = $error;
					}
				}
			}
		}
	}
}
