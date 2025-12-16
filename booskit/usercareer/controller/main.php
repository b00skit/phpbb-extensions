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
	protected $log;
	protected $career_manager;
	protected $root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth, \phpbb\log\log_interface $log, \booskit\usercareer\service\career_manager $career_manager, $root_path, $php_ext)
	{
		$this->config = $config;
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

			$this->career_manager->add_note($user_id, $type_id, $note_date, $description, $this->user->data['user_id']);

			$user_row = $this->career_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_CAREER_ADDED', time(), array($user_row));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['CAREER_NOTE_ADDED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$this->assign_form_vars($user_id, null, false, $viewer_level);
		add_form_key('add_career');

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

			$this->career_manager->update_note($note_id, $type_id, $note_date, $description);

			$user_row = $this->career_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_CAREER_EDITED', time(), array($user_row));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['CAREER_NOTE_UPDATED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$this->assign_form_vars($user_id, $note, true, $viewer_level);
		add_form_key('edit_career');

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

			$this->template->assign_block_vars('career_notes', array(
				'ID' => $note['note_id'],
				'TYPE' => isset($def['name']) ? $def['name'] : $note['career_type_id'],
				'DESCRIPTION' => $note['description'],
				'DATE' => $this->user->format_date($note['note_date']),
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

		$default_date = date('Y-m-d');
		$current_type = '';
		$current_description = '';

		if ($note)
		{
			$default_date = date('Y-m-d', $note['note_date']);
			$current_type = $note['career_type_id'];
			$current_description = $note['description'];
		}

		$this->template->assign_vars(array(
			'S_NOTE_DATE' 		=> $default_date,
			'DESCRIPTION' 		=> $current_description,
			'S_EDIT'			=> $is_edit,
			'U_ACTION'			=> $is_edit
				? $this->helper->route('booskit_usercareer_edit_note', array('note_id' => $note['note_id']))
				: $this->helper->route('booskit_usercareer_add_note', array('user_id' => $user_id)),
			'U_BACK'			=> append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id),
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
