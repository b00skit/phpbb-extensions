<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\service;

class form_manager
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $table_forms;

	/** @var string */
	protected $table_fields;

	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\user $user, $table_forms, $table_fields)
	{
		$this->db = $db;
		$this->user = $user;
		$this->table_forms = $table_forms;
		$this->table_fields = $table_fields;
	}

	public function get_forms($enabled_only = false)
	{
		$sql = 'SELECT * FROM ' . $this->table_forms . ($enabled_only ? ' WHERE enabled = 1' : '') . ' ORDER BY form_id ASC';
		$result = $this->db->sql_query($sql);
		$forms = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$forms[] = $row;
		}
		$this->db->sql_freeresult($result);
		return $forms;
	}

	public function get_form($form_identifier)
	{
		$column = is_numeric($form_identifier) ? 'form_id' : 'form_slug';
		$sql = 'SELECT * FROM ' . $this->table_forms . ' WHERE ' . $column . ' = \'' . $this->db->sql_escape($form_identifier) . '\'';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row;
	}

	public function check_access($user_id, $group_ids_str)
	{
		if (empty($group_ids_str))
		{
			return true;
		}

		$allowed_groups = array_map('intval', explode(',', $group_ids_str));
		if (empty($allowed_groups))
		{
			return true;
		}

		$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . ' 
			WHERE user_id = ' . (int) $user_id . ' 
			AND ' . $this->db->sql_in_set('group_id', $allowed_groups) . '
			AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	public function add_form($data)
	{
		$sql = 'INSERT INTO ' . $this->table_forms . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);
		return $this->db->sql_nextid();
	}

	public function update_form($form_id, $data)
	{
		$sql = 'UPDATE ' . $this->table_forms . ' SET ' . $this->db->sql_build_array('UPDATE', $data) . ' WHERE form_id = ' . (int) $form_id;
		$this->db->sql_query($sql);
	}

	public function delete_form($form_id)
	{
		// Delete fields first
		$sql = 'DELETE FROM ' . $this->table_fields . ' WHERE form_id = ' . (int) $form_id;
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->table_forms . ' WHERE form_id = ' . (int) $form_id;
		$this->db->sql_query($sql);
	}

	public function get_form_fields($form_id)
	{
		$sql = 'SELECT * FROM ' . $this->table_fields . ' WHERE form_id = ' . (int) $form_id . ' ORDER BY field_order ASC, field_id ASC';
		$result = $this->db->sql_query($sql);
		$fields = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$fields[] = $row;
		}
		$this->db->sql_freeresult($result);
		return $fields;
	}

	public function add_field($data)
	{
		$sql = 'INSERT INTO ' . $this->table_fields . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);
	}

	public function delete_form_fields($form_id)
	{
		$sql = 'DELETE FROM ' . $this->table_fields . ' WHERE form_id = ' . (int) $form_id;
		$this->db->sql_query($sql);
	}

	public function create_post($forum_id, $poster_id, $subject, $body)
	{
		if (empty($poster_id))
		{
			$poster_id = $this->user->data['user_id'];
		}

		if (!function_exists('submit_post'))
		{
			global $phpbb_root_path, $phpEx;
			include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
		}

		$subject = utf8_normalize_nfc($subject);
		$text = utf8_normalize_nfc($body);

		$uid = $bitfield = $options = '';
		generate_text_for_storage($text, $uid, $bitfield, $options, true, true, true);

		$poll = $data = [];

		$data = [
			'topic_title'			=> $subject,
			'topic_first_post_id'	=> 0,
			'topic_last_post_id'	=> 0,
			'topic_time_limit'		=> 0,
			'topic_attachment'		=> 0,
			'post_id'				=> 0,
			'topic_id'				=> 0,
			'forum_id'				=> $forum_id,
			'icon_id'				=> 0,
			'poster_id'				=> $poster_id,
			'enable_sig'			=> true,
			'enable_bbcode'			=> true,
			'enable_smilies'		=> true,
			'enable_urls'			=> true,
			'enable_indexing'		=> true,
			'message_md5'			=> md5($text),
			'post_time'				=> time(),
			'post_checksum'			=> '',
			'post_edit_reason'		=> '',
			'post_edit_user'		=> 0,
			'forum_parents'			=> '',
			'forum_name'			=> '',
			'post_subject'			=> $subject,
			'message'				=> $text,
			'post_text'				=> $text,
			'bbcode_uid'			=> $uid,
			'bbcode_bitfield'		=> $bitfield,
			'bbcode_options'		=> $options,
			'poster_ip'				=> $this->user->ip,
			'post_approve'          => 1,
			'post_edit_locked'		=> 0,
			'notify_set'			=> false,
			'notify'				=> false,
		];

		$user_data_backup = $this->user->data;

		if ($poster_id != $this->user->data['user_id'])
		{
			$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $poster_id;
			$result = $this->db->sql_query($sql);
			$poster_row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if ($poster_row)
			{
				$this->user->data = array_merge($this->user->data, $poster_row);
			}
		}

		$username = $this->user->data['username'];

		submit_post('post', $subject, $username, POST_NORMAL, $poll, $data);

		$this->user->data = $user_data_backup;

		return $data['post_id'];
	}
}
