<?php
/**
 *
 * Hide Content Extension for phpBB.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\hidecontent\controller;

use Symfony\Component\HttpFoundation\Response;

class main
{
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

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\user $user,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\controller\helper $helper,
		$phpbb_root_path,
		$php_ext
	) {
		$this->auth = $auth;
		$this->user = $user;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->helper = $helper;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * Toggle hide/unhide state of a post.
	 *
	 * @param int    $id     Post ID
	 * @param string $action 'hide' or 'unhide'
	 * @return Response
	 */
	public function toggle_post($id, $action)
	{
		$post_id = (int) $id;
		if ($post_id <= 0)
		{
			trigger_error('NO_POST');
		}

		if (!check_link_hash($this->request->variable('hash', ''), 'hidecontent_post_' . $post_id))
		{
			trigger_error('FORM_INVALID');
		}

		$sql = 'SELECT p.post_id, p.topic_id, p.forum_id, p.post_hidden, t.topic_first_post_id, t.topic_hidden
			FROM ' . POSTS_TABLE . ' p
			INNER JOIN ' . TOPICS_TABLE . ' t ON (p.topic_id = t.topic_id)
			WHERE p.post_id = ' . $post_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			trigger_error('NO_POST');
		}

		$forum_id = (int) $row['forum_id'];
		if (!$this->auth->acl_get('m_hide', $forum_id))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$this->user->add_lang_ext('booskit/hidecontent', 'hidecontent');

		$new_state = ($action === 'hide') ? 1 : 0;
		$is_first_post = ((int) $row['post_id'] === (int) $row['topic_first_post_id']);

		// Update post status
		$sql = 'UPDATE ' . POSTS_TABLE . '
			SET post_hidden = ' . $new_state . '
			WHERE post_id = ' . $post_id;
		$this->db->sql_query($sql);

		// If first post of the topic, update topic_hidden status accordingly
		if ($is_first_post)
		{
			$sql = 'UPDATE ' . TOPICS_TABLE . '
				SET topic_hidden = ' . $new_state . '
				WHERE topic_id = ' . (int) $row['topic_id'];
			$this->db->sql_query($sql);
		}

		$message = ($new_state === 1) ? $this->user->lang('POST_HIDDEN_SUCCESS') : $this->user->lang('POST_UNHIDDEN_SUCCESS');
		$redirect_url = append_sid("{$this->phpbb_root_path}viewtopic.{$this->php_ext}", "p={$post_id}#p{$post_id}");

		meta_refresh(3, $redirect_url);
		trigger_error($message . '<br /><br />' . sprintf($this->user->lang['RETURN_TOPIC'], '<a href="' . $redirect_url . '">', '</a>'));
	}

	/**
	 * Toggle hide/unhide state of a topic.
	 *
	 * @param int    $id     Topic ID
	 * @param string $action 'hide' or 'unhide'
	 * @return Response
	 */
	public function toggle_topic($id, $action)
	{
		$topic_id = (int) $id;
		if ($topic_id <= 0)
		{
			trigger_error('NO_TOPIC');
		}

		if (!check_link_hash($this->request->variable('hash', ''), 'hidecontent_topic_' . $topic_id))
		{
			trigger_error('FORM_INVALID');
		}

		$sql = 'SELECT topic_id, forum_id, topic_first_post_id, topic_hidden
			FROM ' . TOPICS_TABLE . '
			WHERE topic_id = ' . $topic_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			trigger_error('NO_TOPIC');
		}

		$forum_id = (int) $row['forum_id'];
		if (!$this->auth->acl_get('m_hide', $forum_id))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$this->user->add_lang_ext('booskit/hidecontent', 'hidecontent');

		$new_state = ($action === 'hide') ? 1 : 0;

		// Update topic status
		$sql = 'UPDATE ' . TOPICS_TABLE . '
			SET topic_hidden = ' . $new_state . '
			WHERE topic_id = ' . $topic_id;
		$this->db->sql_query($sql);

		// If unhiding topic, also ensure first post is unhidden so it doesn't stay hidden via post_hidden
		if ($new_state === 0 && !empty($row['topic_first_post_id']))
		{
			$sql = 'UPDATE ' . POSTS_TABLE . '
				SET post_hidden = 0
				WHERE post_id = ' . (int) $row['topic_first_post_id'];
			$this->db->sql_query($sql);
		}
		// If hiding topic, mark first post as hidden as well
		else if ($new_state === 1 && !empty($row['topic_first_post_id']))
		{
			$sql = 'UPDATE ' . POSTS_TABLE . '
				SET post_hidden = 1
				WHERE post_id = ' . (int) $row['topic_first_post_id'];
			$this->db->sql_query($sql);
		}

		$message = ($new_state === 1) ? $this->user->lang('TOPIC_HIDDEN_SUCCESS') : $this->user->lang('TOPIC_UNHIDDEN_SUCCESS');
		$redirect_url = append_sid("{$this->phpbb_root_path}viewtopic.{$this->php_ext}", "t={$topic_id}");

		meta_refresh(3, $redirect_url);
		trigger_error($message . '<br /><br />' . sprintf($this->user->lang['RETURN_TOPIC'], '<a href="' . $redirect_url . '">', '</a>'));
	}
}
