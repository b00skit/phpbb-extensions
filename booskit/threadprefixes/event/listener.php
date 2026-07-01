<?php
/**
 *
 * @package booskit/threadprefixes
 * @license MIT
 *
 */

namespace booskit\threadprefixes\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \booskit\threadprefixes\service\prefix_manager */
	protected $prefix_manager;

	/** @var array */
	protected $last_post_prefix_ids;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\booskit\threadprefixes\service\prefix_manager $prefix_manager
	) {
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->prefix_manager = $prefix_manager;
		$this->last_post_prefix_ids = [];
	}

	public static function getSubscribedEvents()
	{
		return array(
			'core.permissions'							=> 'add_permissions',
			'core.posting_modify_template_vars'			=> 'inject_prefix_selector',
			'core.submit_post_end'						=> 'save_prefix_selection',
			'core.viewforum_modify_topicrow'			=> 'modify_viewforum_topic_row',
			'core.search_modify_tpl_ary'				=> 'modify_search_topic_row',
			'core.viewtopic_assign_template_vars_before'=> 'modify_viewtopic_page',
			'core.display_forums_before'				=> 'load_last_post_prefix_ids',
			'core.display_forums_modify_template_vars'	=> 'modify_index_forum_row',
		);
	}

	public function add_permissions($event)
	{
		$permissions = $event['permissions'];
		$permissions['f_apply_prefix'] = array('lang' => 'ACL_F_APPLY_PREFIX', 'cat' => 'post');
		$event['permissions'] = $permissions;
	}

	public function inject_prefix_selector($event)
	{
		$post_data = $event['post_data'];
		$mode = $event['mode'];
		$forum_id = (int) $post_data['forum_id'];

		// Verify permission to apply prefixes in this forum
		if (!$this->auth->acl_get('f_apply_prefix', $forum_id))
		{
			return;
		}

		// Retrieve tags bound to this forum
		$all_tags = $this->prefix_manager->get_tags();
		$allowed_tags = [];
		foreach ($all_tags as $tag)
		{
			if (in_array($forum_id, $tag['forums_array']))
			{
				$allowed_tags[] = $tag;
			}
		}

		if (empty($allowed_tags))
		{
			return;
		}

		// Check if we are creating a new topic or editing the first post of a topic
		$is_first_post = ($mode === 'post' || ($mode === 'edit' && isset($post_data['topic_first_post_id']) && isset($post_data['post_id']) && $post_data['topic_first_post_id'] == $post_data['post_id']));

		if (!$is_first_post)
		{
			return;
		}

		// Determine the current prefix of the topic if we are editing
		$current_prefix_id = $this->request->variable('topic_prefix_id', -1);
		if ($current_prefix_id === -1)
		{
			$current_prefix_id = 0;
			$topic_id = isset($post_data['topic_id']) ? (int) $post_data['topic_id'] : $this->request->variable('t', 0);
			if ($mode === 'edit' && $topic_id > 0)
			{
				$sql = 'SELECT topic_prefix_id FROM ' . TOPICS_TABLE . ' WHERE topic_id = ' . $topic_id;
				$result = $this->db->sql_query($sql);
				$current_prefix_id = (int) $this->db->sql_fetchfield('topic_prefix_id');
				$this->db->sql_freeresult($result);
			}
		}

		// Load localization keys
		$this->user->add_lang_ext('booskit/threadprefixes', 'threadprefixes');

		// Populate select menu options block
		foreach ($allowed_tags as $tag)
		{
			$this->template->assign_block_vars('prefix_options', [
				'VALUE' => $tag['tag_id'],
				'NAME' => $tag['tag_text'],
				'SELECTED' => ((int) $tag['tag_id'] === (int) $current_prefix_id),
			]);
		}

		$this->template->assign_vars([
			'S_SHOW_PREFIX_SELECT' => true,
		]);
	}

	public function save_prefix_selection($event)
	{
		$mode = $event['mode'];
		$data = $event['data'];
		$forum_id = (int) $data['forum_id'];

		// Check if it is the first post of the topic
		$is_first_post = ($mode === 'post' || ($mode === 'edit' && isset($data['topic_first_post_id']) && isset($data['post_id']) && $data['topic_first_post_id'] == $data['post_id']));

		if (!$is_first_post)
		{
			return;
		}

		// Save the prefix selection if user has permissions and the field was submitted
		if ($this->auth->acl_get('f_apply_prefix', $forum_id) && $this->request->is_set('topic_prefix_id'))
		{
			$prefix_id = $this->request->variable('topic_prefix_id', 0);
			if ($prefix_id === 0 || $this->prefix_manager->is_tag_allowed_for_forum($prefix_id, $forum_id))
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_prefix_id = ' . (int) $prefix_id . '
					WHERE topic_id = ' . (int) $data['topic_id'];
				$this->db->sql_query($sql);
			}
		}
	}

	public function modify_viewforum_topic_row($event)
	{
		$row = $event['row'];
		$topic_row = $event['topic_row'];

		if (isset($row['topic_prefix_id']) && $row['topic_prefix_id'] > 0)
		{
			$tag = $this->prefix_manager->get_tag_by_id($row['topic_prefix_id']);
			if ($tag)
			{
				$topic_row['TOPIC_PREFIX_TEXT'] = $tag['tag_text'];
				$topic_row['TOPIC_PREFIX_COLOR'] = $tag['tag_color'];
				$topic_row['TOPIC_PREFIX_BG_COLOR'] = $tag['tag_bg_color'];
			}
		}
		$event['topic_row'] = $topic_row;
	}

	public function modify_search_topic_row($event)
	{
		$row = $event['row'];
		$tpl_ary = $event['tpl_ary'];

		if (isset($row['topic_prefix_id']) && $row['topic_prefix_id'] > 0)
		{
			$tag = $this->prefix_manager->get_tag_by_id($row['topic_prefix_id']);
			if ($tag)
			{
				$tpl_ary['TOPIC_PREFIX_TEXT'] = $tag['tag_text'];
				$tpl_ary['TOPIC_PREFIX_COLOR'] = $tag['tag_color'];
				$tpl_ary['TOPIC_PREFIX_BG_COLOR'] = $tag['tag_bg_color'];
			}
		}
		$event['tpl_ary'] = $tpl_ary;
	}

	public function modify_viewtopic_page($event)
	{
		$topic_data = $event['topic_data'];

		if (isset($topic_data['topic_prefix_id']) && $topic_data['topic_prefix_id'] > 0)
		{
			$tag = $this->prefix_manager->get_tag_by_id($topic_data['topic_prefix_id']);
			if ($tag)
			{
				$this->template->assign_vars(array(
					'TOPIC_PREFIX_TEXT' => $tag['tag_text'],
					'TOPIC_PREFIX_COLOR' => $tag['tag_color'],
					'TOPIC_PREFIX_BG_COLOR' => $tag['tag_bg_color'],
				));
			}
		}
	}

	public function load_last_post_prefix_ids($event)
	{
		$forum_rows = $event['forum_rows'];
		$last_post_ids = [];
		foreach ($forum_rows as $row)
		{
			if (!empty($row['forum_last_post_id']))
			{
				$last_post_ids[] = (int) $row['forum_last_post_id'];
			}
		}

		$this->last_post_prefix_ids = [];
		if (!empty($last_post_ids))
		{
			$sql = 'SELECT p.post_id, t.topic_prefix_id
				FROM ' . POSTS_TABLE . ' p
				LEFT JOIN ' . TOPICS_TABLE . ' t ON (p.topic_id = t.topic_id)
				WHERE ' . $this->db->sql_in_set('p.post_id', array_unique($last_post_ids));
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->last_post_prefix_ids[(int) $row['post_id']] = (int) $row['topic_prefix_id'];
			}
			$this->db->sql_freeresult($result);
		}
	}

	public function modify_index_forum_row($event)
	{
		$forum_row = $event['forum_row'];
		$row = $event['row'];
		$last_post_id = (int) $row['forum_last_post_id'];

		if ($last_post_id && isset($this->last_post_prefix_ids[$last_post_id]))
		{
			$prefix_id = $this->last_post_prefix_ids[$last_post_id];
			if ($prefix_id > 0)
			{
				$tag = $this->prefix_manager->get_tag_by_id($prefix_id);
				if ($tag)
				{
					$forum_row['LAST_POST_PREFIX_TEXT'] = $tag['tag_text'];
					$forum_row['LAST_POST_PREFIX_COLOR'] = $tag['tag_color'];
					$forum_row['LAST_POST_PREFIX_BG_COLOR'] = $tag['tag_bg_color'];
				}
			}
		}
		$event['forum_row'] = $forum_row;
	}
}
