<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\controller;

class main
{
	protected $config;
	protected $request;
	protected $template;
	protected $user;
	protected $helper;
	protected $auth;
	protected $config_text;
	protected $log;
	protected $career_manager;
	protected $root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth, \phpbb\log\log_interface $log, \booskit\usercareer\service\career_manager $career_manager, $root_path, $php_ext, $table_prefix)
	{
		$this->config = $config;
		$this->config_text = new \phpbb\config\db_text($db, $table_prefix . 'config_text');
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->auth = $auth;
		$this->log = $log;
		$this->career_manager = $career_manager;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	protected function check_auth()
	{
		$viewer_level = $this->career_manager->get_user_role_level($this->user->data['user_id']);
		if ($viewer_level === 0)
		{
			// If not L1+, maybe they have add access some other way? No, add access is tied to levels.
			// But for viewing, we have get_user_view_access.
			// This method is for "add/edit/remove" auth generally, which requires at least L1.
			trigger_error('NOT_AUTHORISED');
		}
		return $viewer_level;
	}

	public function add_note($user_id)
	{
		$viewer_level = $this->check_auth(); // Must be at least level 1

		$target_level = $this->career_manager->get_user_role_level($user_id);

		// Permissions for who can access who should be the same as disciplinary actions
		// Viewer must be strictly higher level than target, unless viewer is Full Access (4).
		if ($viewer_level !== 4 && $viewer_level <= $target_level)
		{
			trigger_error('NOT_AUTHORISED');
		}

		$this->user->add_lang_ext('booskit/usercareer', 'career');
		$this->user->add_lang('common');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('add_career'))
			{
				trigger_error('FORM_INVALID');
			}

			$type_id = $this->request->variable('career_type_id', '');
			$description = $this->request->variable('description', '', true);
			$note_date_raw = $this->request->variable('note_date', '');

			$note_date = time();
			if (!empty($note_date_raw))
			{
				$note_date = strtotime($note_date_raw);
			}

			if (empty($type_id))
			{
				trigger_error($this->user->lang['NO_CAREER_TYPE_SELECTED'] . $this->helper->previous_route(), E_USER_WARNING);
			}

			// Parse BBCode
			$uid = $bitfield = $options = '';
			$allow_bbcode = $allow_urls = $allow_smilies = true;
			generate_text_for_storage($description, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

			$this->career_manager->add_note($user_id, $type_id, $note_date, $description, $this->user->data['user_id'], $uid, $bitfield, $options);

			// Public Post Logic
			$make_public_post = $this->request->variable('make_public_post', 0);
			if ($make_public_post)
			{
				$def = $this->career_manager->get_definition($type_id);
				if ($def && !empty($def['enable_public_posting']))
				{
					$fields_json = isset($def['public_posting_fields']) ? $def['public_posting_fields'] : '[]';
					$fields_config = json_decode($fields_json, true);

					if (is_array($fields_config))
					{
						$custom_fields_data = $this->request->variable('custom_fields', array('' => ''), true);

						$replacements = [
							'{#type}' => $def['name'],
							'{#creator}' => $this->user->data['username'],
							'{#date}' => strtoupper(date('d/M/Y', $note_date)),
							'{#target}' => $this->career_manager->get_username_string($user_id),
							'{#userGroup}' => $this->career_manager->get_primary_group_name($user_id),
							'{#posterGroup}' => $this->career_manager->get_primary_group_name($def['public_posting_poster_id']),
						];

						foreach ($fields_config as $field)
						{
							$var = $field['variable'];
							$val = isset($custom_fields_data[$var]) ? $custom_fields_data[$var] : '';
							$replacements['{@' . $var . '}'] = $val;
						}

						$subject = strtr($def['public_posting_subject_tpl'], $replacements);
						$body = strtr($def['public_posting_body_tpl'], $replacements);

						$this->career_manager->create_public_post($def['public_posting_forum_id'], $def['public_posting_poster_id'], $subject, $body);
					}
				}
			}

			// Forum Group Actions
			$execute_group_action = $this->request->variable('execute_group_action', 0);
			if ($execute_group_action)
			{
				$def = $this->career_manager->get_definition($type_id);
				if ($def && !empty($def['enable_group_action']))
				{
					$this->career_manager->execute_group_actions($user_id, $def['group_action_add'], $def['group_action_remove']);
				}
			}

			$user_row = $this->career_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_CAREER_ADDED', time(), array($user_row));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['CAREER_NOTE_ADDED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$this->assign_form_vars($user_id, null, false, $viewer_level);
		add_form_key('add_career');

		// Ruleset
		$ruleset_text = $this->config_text->get('booskit_career_ruleset');
		$ruleset_uid = isset($this->config['booskit_career_ruleset_uid']) ? $this->config['booskit_career_ruleset_uid'] : '';
		$ruleset_bitfield = isset($this->config['booskit_career_ruleset_bitfield']) ? $this->config['booskit_career_ruleset_bitfield'] : '';
		$ruleset_options = isset($this->config['booskit_career_ruleset_options']) ? $this->config['booskit_career_ruleset_options'] : 7;
		$ruleset_html = generate_text_for_display($ruleset_text, $ruleset_uid, $ruleset_bitfield, $ruleset_options);

		$this->template->assign_vars(array(
			'BOOSKIT_CAREER_RULESET' => $ruleset_html,
		));

		page_header($this->user->lang['CAREER_ADD_NOTE']);
		return $this->helper->render('add_career_note.html', $this->user->lang['CAREER_ADD_NOTE']);
	}

	public function edit_note($note_id)
	{
		// Viewer must have at least L1 access to even be here (checked in check_auth)
		$viewer_level = $this->check_auth();

		$this->user->add_lang_ext('booskit/usercareer', 'career');
		$this->user->add_lang('common');

		$note = $this->career_manager->get_note($note_id);
		if (!$note)
		{
			trigger_error('NO_CAREER_NOTE_RECORD');
		}
		$user_id = $note['user_id'];
		$target_level = $this->career_manager->get_user_role_level($user_id);

		// Edit Permission Check:
		// 1. Viewer is Full Access (L4) -> ALLOW
		// 2. Viewer is L2+ AND viewer > target -> ALLOW
		// 3. Viewer is Issuer (L1+) -> ALLOW (Self-edit for issuer)

		$is_issuer = ($note['issuer_user_id'] == $this->user->data['user_id']);
		$can_edit = false;

		if ($viewer_level === 4)
		{
			$can_edit = true;
		}
		elseif ($is_issuer)
		{
			$can_edit = true;
		}
		elseif ($viewer_level >= 2 && $viewer_level > $target_level)
		{
			$can_edit = true;
		}

		if (!$can_edit)
		{
			trigger_error('NOT_AUTHORISED');
		}

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('edit_career'))
			{
				trigger_error('FORM_INVALID');
			}

			$type_id = $this->request->variable('career_type_id', '');
			$description = $this->request->variable('description', '', true);
			$note_date_raw = $this->request->variable('note_date', '');

			$note_date = time();
			if (!empty($note_date_raw))
			{
				$note_date = strtotime($note_date_raw);
			}

			if (empty($type_id))
			{
				trigger_error($this->user->lang['NO_CAREER_TYPE_SELECTED'] . $this->helper->previous_route(), E_USER_WARNING);
			}

			// Parse BBCode
			$uid = $bitfield = $options = '';
			$allow_bbcode = $allow_urls = $allow_smilies = true;
			generate_text_for_storage($description, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

			$this->career_manager->update_note($note_id, $type_id, $note_date, $description, $uid, $bitfield, $options);

			$user_row = $this->career_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_CAREER_EDITED', time(), array($user_row));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['CAREER_NOTE_UPDATED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$this->assign_form_vars($user_id, $note, true, $viewer_level);
		add_form_key('edit_career');

		// Ruleset
		$ruleset_text = $this->config_text->get('booskit_career_ruleset');
		$ruleset_text = $this->config_text->get('booskit_career_ruleset');
		$ruleset_uid = isset($this->config['booskit_career_ruleset_uid']) ? $this->config['booskit_career_ruleset_uid'] : '';
		$ruleset_bitfield = isset($this->config['booskit_career_ruleset_bitfield']) ? $this->config['booskit_career_ruleset_bitfield'] : '';
		$ruleset_options = isset($this->config['booskit_career_ruleset_options']) ? $this->config['booskit_career_ruleset_options'] : 7;
		$ruleset_html = generate_text_for_display($ruleset_text, $ruleset_uid, $ruleset_bitfield, $ruleset_options);

		$this->template->assign_vars(array(
			'BOOSKIT_CAREER_RULESET' => $ruleset_html,
		));

		page_header($this->user->lang['CAREER_EDIT_NOTE']);
		return $this->helper->render('add_career_note.html', $this->user->lang['CAREER_EDIT_NOTE']);
	}

	public function remove_note($note_id)
	{
		$viewer_level = $this->check_auth();

		$this->user->add_lang_ext('booskit/usercareer', 'career');

		$note = $this->career_manager->get_note($note_id);
		if (!$note)
		{
			trigger_error('NO_CAREER_NOTE_RECORD');
		}
		$user_id = $note['user_id'];
		$target_level = $this->career_manager->get_user_role_level($user_id);

		// Remove Permission Check (Same as Edit):
		$is_issuer = ($note['issuer_user_id'] == $this->user->data['user_id']);
		$can_remove = false;

		if ($viewer_level === 4)
		{
			$can_remove = true;
		}
		elseif ($is_issuer)
		{
			$can_remove = true;
		}
		elseif ($viewer_level >= 2 && $viewer_level > $target_level)
		{
			$can_remove = true;
		}

		if (!$can_remove)
		{
			trigger_error('NOT_AUTHORISED');
		}

		if (confirm_box(true))
		{
			$this->career_manager->delete_note($note_id);

			$user_row = $this->career_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_CAREER_DELETED', time(), array($user_row));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);
			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['CAREER_NOTE_DELETED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}
		else
		{
			confirm_box(false, 'DELETE_CAREER_CONFIRM', build_hidden_fields(array(
				'note_id'	=> $note_id,
			)));
		}
	}

	public function view_timeline($user_id)
	{
		$this->user->add_lang_ext('booskit/usercareer', 'career');

		// Check view access
		if (!$this->career_manager->get_user_view_access($this->user->data['user_id'], $user_id))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$target_username = $this->career_manager->get_username_string($user_id);

		$notes = $this->career_manager->get_user_notes($user_id); // No limit
		$definitions = $this->career_manager->get_definitions();

		// Map definitions by ID for easy lookup
		$defs_map = [];
		foreach ($definitions as $def) {
			$defs_map[$def['id']] = $def;
		}

		// Viewer level for edit/delete checks
		$viewer_level = $this->career_manager->get_user_role_level($this->user->data['user_id']);
		$target_level = $this->career_manager->get_user_role_level($user_id);

		foreach ($notes as $note)
		{
			$def = isset($defs_map[$note['career_type_id']]) ? $defs_map[$note['career_type_id']] : [];

			$is_issuer = ($note['issuer_user_id'] == $this->user->data['user_id']);
			$has_access = false;

			if ($viewer_level === 4)
			{
				$has_access = true;
			}
			elseif ($is_issuer && $viewer_level >= 1)
			{
				$has_access = true;
			}
			elseif ($viewer_level >= 2 && $viewer_level > $target_level)
			{
				$has_access = true;
			}

			// Render BBCode
			$bbcode_uid = isset($note['bbcode_uid']) ? $note['bbcode_uid'] : '';
			$bbcode_bitfield = isset($note['bbcode_bitfield']) ? $note['bbcode_bitfield'] : '';
			$bbcode_options = isset($note['bbcode_options']) ? $note['bbcode_options'] : 7;
			$description_html = generate_text_for_display($note['description'], $bbcode_uid, $bbcode_bitfield, $bbcode_options);

			$this->template->assign_block_vars('career_notes', array(
				'ID' => $note['note_id'],
				'TYPE' => isset($def['name']) ? $def['name'] : $note['career_type_id'],
				'DESCRIPTION' => $description_html,
				'DATE' => $this->user->format_date($note['note_date'], 'D M d, Y'),
				'ICON' => isset($def['icon']) ? $def['icon'] : 'fa-circle',
				'COLOR' => isset($def['color']) ? $def['color'] : '#333',
				'U_EDIT' => $has_access ? $this->helper->route('booskit_usercareer_edit_note', array('note_id' => $note['note_id'])) : '',
				'U_REMOVE' => $has_access ? $this->helper->route('booskit_usercareer_remove_note', array('note_id' => $note['note_id'])) : '',
			));
		}

		$this->template->assign_vars(array(
			'TIMELINE_USER' => $target_username,
			'U_BACK' => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id),
		));

		page_header(sprintf($this->user->lang['CAREER_TIMELINE_FOR'], $target_username));
		return $this->helper->render('timeline_view.html', sprintf($this->user->lang['CAREER_TIMELINE_FOR'], $target_username));
	}

	protected function assign_form_vars($user_id, $note = null, $is_edit = false, $viewer_level = 0)
	{
		$definitions = $this->career_manager->get_definitions();

		// Collect all group IDs referenced
		$all_group_ids = [];
		foreach ($definitions as $def)
		{
			if (!empty($def['enable_group_action']))
			{
				$g_add = array_filter(array_map('intval', explode(',', $def['group_action_add'])));
				$g_remove = array_map('trim', explode(',', $def['group_action_remove']));

				foreach ($g_add as $gid) $all_group_ids[] = $gid;
				foreach ($g_remove as $gid)
				{
					if (is_numeric($gid) && $gid > 0) $all_group_ids[] = (int) $gid;
				}
			}
		}
		$all_group_ids = array_unique($all_group_ids);
		$group_names = $this->career_manager->get_group_names($all_group_ids);

		$default_date = date('Y-m-d');
		$current_type = '';
		$current_description = '';

		if ($note)
		{
			$default_date = date('Y-m-d', $note['note_date']);
			$current_type = $note['career_type_id'];

			// Decode BBCode for editing
			$bbcode_uid = isset($note['bbcode_uid']) ? $note['bbcode_uid'] : '';
			$bbcode_options = isset($note['bbcode_options']) ? $note['bbcode_options'] : 7;
			$text_data = generate_text_for_edit($note['description'], $bbcode_uid, $bbcode_options);
			$current_description = $text_data['text'];
		}

		$this->template->assign_vars(array(
			'S_NOTE_DATE' 		=> $default_date,
			'DESCRIPTION' 		=> $current_description,
			'S_EDIT'			=> $is_edit,
			'U_ACTION'			=> $is_edit
				? $this->helper->route('booskit_usercareer_edit_note', array('note_id' => $note['note_id']))
				: $this->helper->route('booskit_usercareer_add_note', array('user_id' => $user_id)),
			'U_BACK'			=> append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id),
			'S_BBCODE_ALLOWED' => true,
			'S_BBCODE_QUOTE'   => true,
			'S_BBCODE_IMG'     => true,
			'S_LINKS_ALLOWED'  => true,
			'S_SMILIES_ALLOWED'=> true,
			'DEFINITIONS_JSON' => json_encode($definitions),
			'GROUP_NAMES_JSON' => json_encode($group_names),
		));

		foreach ($definitions as $def) {
			$this->template->assign_block_vars('types', array(
				'ID' 		=> $def['id'],
				'NAME' 		=> $def['name'],
				'SELECTED' 	=> ($def['id'] == $current_type),
			));
		}
	}
}
