<?php
/**
 *
 * @package booskit/threadprefixes
 * @license MIT
 *
 */

namespace booskit\threadprefixes\controller\acp;

class settings
{
	protected $config;
	protected $db;
	protected $request;
	protected $template;
	protected $user;
	protected $log;
	protected $prefix_manager;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request_interface $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\log\log $log,
		\booskit\threadprefixes\service\prefix_manager $prefix_manager,
		$table_prefix
	) {
		$this->config = $config;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->log = $log;
		$this->prefix_manager = $prefix_manager;
	}

	public function handle($u_action)
	{
		$form_key = 'acp_booskit_threadprefixes';
		add_form_key($form_key);
		$this->user->add_lang_ext('booskit/threadprefixes', 'info_acp_threadprefixes');

		$action = $this->request->variable('action', '');
		$tag_id = $this->request->variable('tag_id', 0);

		// Handle Delete Action
		if ($action === 'delete' && $tag_id > 0)
		{
			if (confirm_box(true))
			{
				$tag = $this->prefix_manager->get_tag_by_id($tag_id);
				if ($tag)
				{
					$this->prefix_manager->delete_tag($tag_id);
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_THREADPREFIXES_TAG_DELETED', false, [$tag['tag_text']]);
				}
				trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
			}
			else
			{
				confirm_box(false, $this->user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
					'tag_id' => $tag_id,
					'action' => 'delete',
				)));
			}
		}

		// Handle Add / Edit Submissions (POST)
		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($u_action), E_USER_WARNING);
			}

			$text = $this->request->variable('tag_text', '', true);
			$color = $this->request->variable('tag_color', '#ffffff');
			$bg_color = $this->request->variable('tag_bg_color', '#000000');
			$forums = $this->request->variable('tag_forums', array(0));

			if (empty($text))
			{
				trigger_error($this->user->lang['BOOSKIT_THREADPREFIXES_TEXT_REQUIRED'] . adm_back_link($u_action), E_USER_WARNING);
			}

			if ($action === 'edit' && $tag_id > 0)
			{
				$this->prefix_manager->update_tag($tag_id, $text, $color, $bg_color, $forums);
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_THREADPREFIXES_TAG_UPDATED', false, [$text]);
			}
			else
			{
				$this->prefix_manager->add_tag($text, $color, $bg_color, $forums);
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_THREADPREFIXES_TAG_ADDED', false, [$text]);
			}

			trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
		}

		// Fetch all forum options formatted with depths
		$forums_list = $this->get_forums_hierarchy();

		// Check if we are in Edit Mode
		$edit_tag = null;
		if ($action === 'edit' && $tag_id > 0)
		{
			$edit_tag = $this->prefix_manager->get_tag_by_id($tag_id);
			if (!$edit_tag)
			{
				trigger_error('NO_TAG');
			}
		}

		// Load tags list
		$tags = $this->prefix_manager->get_tags();

		// Format allowed forums names for tags display
		foreach ($tags as &$tag)
		{
			$tag_forum_names = [];
			foreach ($tag['forums_array'] as $fid)
			{
				if (isset($forums_list[$fid]))
				{
					$tag_forum_names[] = $forums_list[$fid]['forum_name'];
				}
			}
			$tag['forum_names_list'] = !empty($tag_forum_names) ? implode(', ', $tag_forum_names) : $this->user->lang['BOOSKIT_THREADPREFIXES_NO_FORUMS'];
		}

		// Map forum list to template block
		foreach ($forums_list as $f)
		{
			$this->template->assign_block_vars('forum_options', [
				'VALUE' => $f['forum_id'],
				'NAME' => $f['name_formatted'],
				'SELECTED' => ($edit_tag && in_array($f['forum_id'], $edit_tag['forums_array'])),
			]);
		}

		// Pass data to template
		$this->template->assign_vars(array(
			'U_ACTION' => $u_action,
			'TAGS' => $tags,
			'S_EDIT' => ($edit_tag !== null),
			'EDIT_TAG_ID' => $tag_id,
			'EDIT_TEXT' => $edit_tag ? $edit_tag['tag_text'] : '',
			'EDIT_COLOR' => $edit_tag ? $edit_tag['tag_color'] : '#ffffff',
			'EDIT_BG_COLOR' => $edit_tag ? $edit_tag['tag_bg_color'] : '#007bff',
		));
	}

	protected function get_forums_hierarchy()
	{
		$sql = 'SELECT forum_id, forum_name, forum_type, left_id, right_id
			FROM ' . FORUMS_TABLE . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql);

		$forums = [];
		$right = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (count($right) > 0)
			{
				while (count($right) > 0 && $right[count($right) - 1] < $row['right_id'])
				{
					array_pop($right);
				}
			}
			$depth = count($right);
			$row['name_formatted'] = str_repeat('-- ', $depth) . $row['forum_name'];
			$forums[$row['forum_id']] = [
				'forum_id' => $row['forum_id'],
				'forum_name' => $row['forum_name'],
				'forum_type' => $row['forum_type'],
				'name_formatted' => $row['name_formatted']
			];
			$right[] = $row['right_id'];
		}
		$this->db->sql_freeresult($result);
		return $forums;
	}
}
