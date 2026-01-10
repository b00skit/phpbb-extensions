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
	protected $config_text;
	protected $log;
	protected $disciplinary_manager;
	protected $root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\config\db_text $config_text, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth, \phpbb\log\log_interface $log, \booskit\disciplinary\service\disciplinary_manager $disciplinary_manager, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->config_text = $config_text;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->auth = $auth;
		$this->log = $log;
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

			// Validation: Check if user has access to issue this type
			$def = $this->disciplinary_manager->get_definition($type_id);
			if ($def && isset($def['access_level']) && $viewer_level < $def['access_level'])
			{
				trigger_error('NOT_AUTHORISED');
			}

			// Parse BBCode
			$reason_uid = $reason_bitfield = $reason_options = '';
			$evidence_uid = $evidence_bitfield = $evidence_options = '';
			$allow_bbcode = $allow_urls = $allow_smilies = true;

			generate_text_for_storage($reason, $reason_uid, $reason_bitfield, $reason_options, $allow_bbcode, $allow_urls, $allow_smilies);
			generate_text_for_storage($evidence, $evidence_uid, $evidence_bitfield, $evidence_options, $allow_bbcode, $allow_urls, $allow_smilies);

			$this->disciplinary_manager->add_record($user_id, $type_id, $issue_date, $reason, $evidence, $this->user->data['user_id'],
				$reason_uid, $reason_bitfield, $reason_options,
				$evidence_uid, $evidence_bitfield, $evidence_options);

			$user_row = $this->disciplinary_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_DISCIPLINARY_ADDED', time(), array($user_row));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['DISCIPLINARY_ADDED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$this->assign_form_vars($user_id, null, false, $viewer_level);

		add_form_key('add_disciplinary');

		// Ruleset
		$ruleset_text = $this->config_text->get('booskit_disciplinary_ruleset');
		$ruleset_uid = isset($this->config['booskit_disciplinary_ruleset_uid']) ? $this->config['booskit_disciplinary_ruleset_uid'] : '';
		$ruleset_bitfield = isset($this->config['booskit_disciplinary_ruleset_bitfield']) ? $this->config['booskit_disciplinary_ruleset_bitfield'] : '';
		$ruleset_options = isset($this->config['booskit_disciplinary_ruleset_options']) ? $this->config['booskit_disciplinary_ruleset_options'] : 7;
		$ruleset_html = generate_text_for_display($ruleset_text, $ruleset_uid, $ruleset_bitfield, $ruleset_options);

		$this->template->assign_vars(array(
			'BOOSKIT_DISCIPLINARY_RULESET' => $ruleset_html,
		));

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

		// Access Check Logic:
		// 1. Full Access (4) -> ALLOW
		// 2. Issuer (Self-correction) -> ALLOW (Bypasses hierarchy check)
		// 3. Others -> Must be > target AND have permission to edit others?
		//    Currently, disciplinary only allows Issuer or Full Access to edit.
		//    So, we just enforce that.

		$is_issuer = ($this->user->data['user_id'] == $record['issuer_user_id']);
		$has_access = false;

		if ($viewer_level === 4)
		{
			$has_access = true;
		}
		elseif ($is_issuer)
		{
			// Issuer allowed to edit their own record
			$has_access = true;
		}

		if (!$has_access)
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

			// Validation: Check if user has access to issue this type
			$def = $this->disciplinary_manager->get_definition($type_id);
			if ($def && isset($def['access_level']) && $viewer_level < $def['access_level'])
			{
				trigger_error('NOT_AUTHORISED');
			}

			// Parse BBCode
			$reason_uid = $reason_bitfield = $reason_options = '';
			$evidence_uid = $evidence_bitfield = $evidence_options = '';
			$allow_bbcode = $allow_urls = $allow_smilies = true;

			generate_text_for_storage($reason, $reason_uid, $reason_bitfield, $reason_options, $allow_bbcode, $allow_urls, $allow_smilies);
			generate_text_for_storage($evidence, $evidence_uid, $evidence_bitfield, $evidence_options, $allow_bbcode, $allow_urls, $allow_smilies);

			$this->disciplinary_manager->update_record($record_id, $type_id, $issue_date, $reason, $evidence,
				$reason_uid, $reason_bitfield, $reason_options,
				$evidence_uid, $evidence_bitfield, $evidence_options);

			$user_row = $this->disciplinary_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_DISCIPLINARY_EDITED', time(), array($user_row));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['DISCIPLINARY_UPDATED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$this->assign_form_vars($user_id, $record, true, $viewer_level);

		add_form_key('edit_disciplinary');

		// Ruleset
		$ruleset_text = $this->config_text->get('booskit_disciplinary_ruleset');
		$ruleset_uid = isset($this->config['booskit_disciplinary_ruleset_uid']) ? $this->config['booskit_disciplinary_ruleset_uid'] : '';
		$ruleset_bitfield = isset($this->config['booskit_disciplinary_ruleset_bitfield']) ? $this->config['booskit_disciplinary_ruleset_bitfield'] : '';
		$ruleset_options = isset($this->config['booskit_disciplinary_ruleset_options']) ? $this->config['booskit_disciplinary_ruleset_options'] : 7;
		$ruleset_html = generate_text_for_display($ruleset_text, $ruleset_uid, $ruleset_bitfield, $ruleset_options);

		$this->template->assign_vars(array(
			'BOOSKIT_DISCIPLINARY_RULESET' => $ruleset_html,
		));

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

		// Access Check Logic (Same as Edit)
		$is_issuer = ($this->user->data['user_id'] == $record['issuer_user_id']);
		$has_access = false;

		if ($viewer_level === 4)
		{
			$has_access = true;
		}
		elseif ($is_issuer)
		{
			$has_access = true;
		}

		if (!$has_access)
		{
			trigger_error('NOT_AUTHORISED');
		}

		if (confirm_box(true))
		{
			$this->disciplinary_manager->delete_record($record_id);

			$user_row = $this->disciplinary_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_DISCIPLINARY_DELETED', time(), array($user_row));

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

	protected function assign_form_vars($user_id, $record = null, $is_edit = false, $viewer_level = 0)
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

			// Decode BBCode
			$reason_uid = isset($record['reason_bbcode_uid']) ? $record['reason_bbcode_uid'] : '';
			$reason_options = isset($record['reason_bbcode_options']) ? $record['reason_bbcode_options'] : 7;
			$reason_data = generate_text_for_edit($record['reason'], $reason_uid, $reason_options);
			$current_reason = $reason_data['text'];

			$evidence_uid = isset($record['evidence_bbcode_uid']) ? $record['evidence_bbcode_uid'] : '';
			$evidence_options = isset($record['evidence_bbcode_options']) ? $record['evidence_bbcode_options'] : 7;
			$evidence_data = generate_text_for_edit($record['evidence'], $evidence_uid, $evidence_options);
			$current_evidence = $evidence_data['text'];
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
			'S_BBCODE_ALLOWED' => true,
			'S_BBCODE_QUOTE'   => true,
			'S_BBCODE_IMG'     => true,
			'S_LINKS_ALLOWED'  => true,
			'S_SMILIES_ALLOWED'=> true,
		));

		foreach ($definitions as $def) {
			// Filter by access level
			if (isset($def['access_level']) && $viewer_level < $def['access_level'])
			{
				if ($def['id'] != $current_type)
				{
					continue;
				}
			}

			$this->template->assign_block_vars('types', array(
				'ID' 		=> $def['id'],
				'NAME' 		=> $def['name'],
				'SELECTED' 	=> ($def['id'] == $current_type),
			));
		}
	}
}
