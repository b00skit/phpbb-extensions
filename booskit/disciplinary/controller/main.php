<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\controller;

class main
{
	protected $config;
	protected $request;
	protected $template;
	protected $user;
	protected $helper;
	protected $auth;
	protected $disciplinary_manager;
	protected $root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth, \booskit\disciplinary\service\disciplinary_manager $disciplinary_manager, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->auth = $auth;
		$this->disciplinary_manager = $disciplinary_manager;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	protected function check_auth()
	{
		$viewer_level = $this->disciplinary_manager->get_user_role_level($this->user->data['user_id']);
		if ($viewer_level === 0)
		{
			trigger_error('NOT_AUTHORISED');
		}
		return $viewer_level;
	}

	public function add_record($user_id)
	{
		$viewer_level = $this->check_auth();

		// Determine Target Level
		$target_level = $this->disciplinary_manager->get_user_role_level($user_id);

		// Access Check:
		// Full Access (4) can target everyone (including 4).
		// Others must be strictly higher level than target.
		if ($viewer_level !== 4 && $viewer_level <= $target_level)
		{
			trigger_error('NOT_AUTHORISED');
		}

		$this->user->add_lang_ext('booskit/disciplinary', 'disciplinary');
		$this->user->add_lang('common');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('add_disciplinary'))
			{
				trigger_error('FORM_INVALID');
			}

			$type_id = $this->request->variable('disciplinary_type_id', '');
			$reason = $this->request->variable('reason', '', true);
			$evidence = $this->request->variable('evidence', '', true);

			$issue_date_raw = $this->request->variable('issue_date', '');
			$issue_date = time();
			if (!empty($issue_date_raw))
			{
				$issue_date = strtotime($issue_date_raw);
			}

			if (empty($type_id))
			{
				trigger_error($this->user->lang['NO_DISCIPLINARY_TYPE_SELECTED'] . $this->helper->previous_route(), E_USER_WARNING);
			}

			$this->disciplinary_manager->add_record($user_id, $type_id, $issue_date, $reason, $evidence, $this->user->data['user_id']);

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['DISCIPLINARY_ADDED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$this->assign_form_vars($user_id);

		add_form_key('add_disciplinary');

		return $this->helper->render('add_disciplinary.html', $this->user->lang['ADD_DISCIPLINARY']);
	}

	public function edit_record($record_id)
	{
		$viewer_level = $this->check_auth();

		$this->user->add_lang_ext('booskit/disciplinary', 'disciplinary');
		$this->user->add_lang('common');

		$record = $this->disciplinary_manager->get_record($record_id);
		if (!$record)
		{
			trigger_error('NO_DISCIPLINARY_RECORD');
		}
		$user_id = $record['user_id'];

		// Determine Target Level
		$target_level = $this->disciplinary_manager->get_user_role_level($user_id);

		// Access Check
		if ($viewer_level !== 4 && $viewer_level <= $target_level)
		{
			trigger_error('NOT_AUTHORISED');
		}

		// Ownership check: Full Access (4) can edit all; others only their own
		// Assuming L3 cannot edit others (replacing "Founder (3)" logic with Full Access (4))
		if ($viewer_level < 4 && $this->user->data['user_id'] != $record['issuer_user_id'])
		{
			trigger_error('NOT_AUTHORISED');
		}

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('edit_disciplinary'))
			{
				trigger_error('FORM_INVALID');
			}

			$type_id = $this->request->variable('disciplinary_type_id', '');
			$reason = $this->request->variable('reason', '', true);
			$evidence = $this->request->variable('evidence', '', true);

			$issue_date_raw = $this->request->variable('issue_date', '');
			$issue_date = time();
			if (!empty($issue_date_raw))
			{
				$issue_date = strtotime($issue_date_raw);
			}

			if (empty($type_id))
			{
				trigger_error($this->user->lang['NO_DISCIPLINARY_TYPE_SELECTED'] . $this->helper->previous_route(), E_USER_WARNING);
			}

			$this->disciplinary_manager->update_record($record_id, $type_id, $issue_date, $reason, $evidence);

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['DISCIPLINARY_UPDATED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$this->assign_form_vars($user_id, $record, true);

		add_form_key('edit_disciplinary');

		return $this->helper->render('add_disciplinary.html', $this->user->lang['EDIT_DISCIPLINARY']);
	}

	public function delete_record($record_id)
	{
		$viewer_level = $this->check_auth();

		$this->user->add_lang_ext('booskit/disciplinary', 'disciplinary');

		$record = $this->disciplinary_manager->get_record($record_id);
		if (!$record)
		{
			trigger_error('NO_DISCIPLINARY_RECORD');
		}
		$user_id = $record['user_id'];

		// Determine Target Level
		$target_level = $this->disciplinary_manager->get_user_role_level($user_id);

		// Access Check
		if ($viewer_level !== 4 && $viewer_level <= $target_level)
		{
			trigger_error('NOT_AUTHORISED');
		}

		// Ownership check: Full Access (4) can delete all; others only their own
		if ($viewer_level < 4 && $this->user->data['user_id'] != $record['issuer_user_id'])
		{
			trigger_error('NOT_AUTHORISED');
		}

		if (confirm_box(true))
		{
			$this->disciplinary_manager->delete_record($record_id);

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);
			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['DISCIPLINARY_DELETED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}
		else
		{
			confirm_box(false, 'DELETE_DISCIPLINARY_CONFIRM', build_hidden_fields(array(
				'record_id'	=> $record_id,
			)));
		}
	}

	protected function assign_form_vars($user_id, $record = null, $is_edit = false)
	{
		$definitions = $this->disciplinary_manager->get_definitions();

		$default_date = date('Y-m-d');
		$current_type = '';
		$current_reason = '';
		$current_evidence = '';

		if ($record)
		{
			$default_date = date('Y-m-d', $record['issue_date']);
			$current_type = $record['disciplinary_type_id'];
			$current_reason = $record['reason'];
			$current_evidence = $record['evidence'];
		}

		$this->template->assign_vars(array(
			'S_ISSUE_DATE' 		=> $default_date,
			'REASON' 			=> $current_reason,
			'EVIDENCE' 			=> $current_evidence,
			'S_EDIT'			=> $is_edit,
			'U_ACTION'			=> $is_edit
				? $this->helper->route('booskit_disciplinary_edit_record', array('record_id' => $record['record_id']))
				: $this->helper->route('booskit_disciplinary_add_record', array('user_id' => $user_id)),
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
