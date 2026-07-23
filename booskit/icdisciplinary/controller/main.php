<?php
/**
 *
 * @package booskit/icdisciplinary
 * @license MIT
 *
 */

namespace booskit\icdisciplinary\controller;

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
	protected $ic_manager;
	protected $root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth, \phpbb\log\log_interface $log, \booskit\icdisciplinary\service\ic_manager $ic_manager, $root_path, $php_ext, $table_prefix)
	{
		$this->config = $config;
		$this->config_text = new \phpbb\config\db_text($db, $table_prefix . 'config_text');
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->auth = $auth;
		$this->log = $log;
		$this->ic_manager = $ic_manager;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	// --- Character Management ---

	public function add_character($user_id)
	{
		if (!$this->ic_manager->can_create_character($this->user->data['user_id'], $user_id))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$this->user->add_lang_ext('booskit/icdisciplinary', 'icdisciplinary');
		$this->user->add_lang('common');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('add_ic_character'))
			{
				trigger_error('FORM_INVALID');
			}

			$name = $this->request->variable('character_name', '', true);

			if (empty($name))
			{
				trigger_error($this->user->lang['CHARACTER_NAME_EMPTY'] . $this->helper->previous_route(), E_USER_WARNING);
			}

			$this->ic_manager->add_character($user_id, $name);

			$user_row = $this->ic_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_IC_CHARACTER_ADDED', time(), array($name));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['CHARACTER_ADDED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		add_form_key('add_ic_character');

		$this->template->assign_vars(array(
			'U_ACTION' => $this->helper->route('booskit_icdisciplinary_add_character', array('user_id' => $user_id)),
			'U_BACK' => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id),
		));

		return $this->helper->render('add_character.html', $this->user->lang['ADD_CHARACTER']);
	}

	public function archive_character($character_id)
	{
		$character = $this->ic_manager->get_character($character_id);
		if (!$character)
		{
			trigger_error('NO_CHARACTER');
		}
		$user_id = $character['user_id'];

		if (!$this->ic_manager->can_archive_character($this->user->data['user_id'], $user_id))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$this->user->add_lang_ext('booskit/icdisciplinary', 'icdisciplinary');

		if (confirm_box(true))
		{
			// Toggle
			$new_state = !$character['is_archived'];
			$this->ic_manager->archive_character($character_id, $new_state);

			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_IC_CHARACTER_ARCHIVED', time(), array($character['character_name']));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id . '&character_id=' . $character_id);
			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['CHARACTER_ARCHIVED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}
		else
		{
			confirm_box(false, 'CONFIRM_ARCHIVE_CHARACTER', build_hidden_fields(array(
				'character_id'	=> $character_id,
			)));
		}
	}

	public function delete_character($character_id)
	{
		$character = $this->ic_manager->get_character($character_id);
		if (!$character)
		{
			trigger_error('NO_CHARACTER');
		}
		$user_id = $character['user_id'];

		if (!$this->ic_manager->can_delete_character($this->user->data['user_id'], $user_id))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$this->user->add_lang_ext('booskit/icdisciplinary', 'icdisciplinary');

		if (confirm_box(true))
		{
			$this->ic_manager->delete_character($character_id);

			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_IC_CHARACTER_DELETED', time(), array($character['character_name']));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);
			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['CHARACTER_DELETED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}
		else
		{
			confirm_box(false, 'CONFIRM_DELETE_CHARACTER', build_hidden_fields(array(
				'character_id'	=> $character_id,
			)));
		}
	}

	// --- Record Management ---

	public function add_record($character_id)
	{
		$character = $this->ic_manager->get_character($character_id);
		if (!$character)
		{
			trigger_error('NO_CHARACTER');
		}
		$target_user_id = $character['user_id'];

		if (!$this->ic_manager->can_add_record($this->user->data['user_id'], $target_user_id))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$this->user->add_lang_ext('booskit/icdisciplinary', 'icdisciplinary');
		$this->user->add_lang('common');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('add_ic_record'))
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
			if (!$this->ic_manager->can_issue_type($this->user->data['user_id'], $target_user_id, $type_id))
			{
				trigger_error('NOT_AUTHORISED');
			}

			// Parse BBCode
			$reason_uid = $reason_bitfield = $reason_options = '';
			$evidence_uid = $evidence_bitfield = $evidence_options = '';
			$allow_bbcode = $allow_urls = $allow_smilies = true;

			generate_text_for_storage($reason, $reason_uid, $reason_bitfield, $reason_options, $allow_bbcode, $allow_urls, $allow_smilies);
			generate_text_for_storage($evidence, $evidence_uid, $evidence_bitfield, $evidence_options, $allow_bbcode, $allow_urls, $allow_smilies);

			$this->ic_manager->add_record($character_id, $type_id, $issue_date, $reason, $evidence, $this->user->data['user_id'],
				$reason_uid, $reason_bitfield, $reason_options,
				$evidence_uid, $evidence_bitfield, $evidence_options);

			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_IC_RECORD_ADDED', time(), array($type_id, $character['character_name']));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $target_user_id . '&character_id=' . $character_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['IC_RECORD_ADDED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$this->assign_form_vars($target_user_id, $character_id, null, false);

		add_form_key('add_ic_record');

		// Ruleset
		$ruleset_text = $this->config_text->get('booskit_icdisciplinary_ruleset');
		$ruleset_uid = isset($this->config['booskit_icdisciplinary_ruleset_uid']) ? $this->config['booskit_icdisciplinary_ruleset_uid'] : '';
		$ruleset_bitfield = isset($this->config['booskit_icdisciplinary_ruleset_bitfield']) ? $this->config['booskit_icdisciplinary_ruleset_bitfield'] : '';
		$ruleset_options = isset($this->config['booskit_icdisciplinary_ruleset_options']) ? $this->config['booskit_icdisciplinary_ruleset_options'] : 7;
		$ruleset_html = generate_text_for_display($ruleset_text, $ruleset_uid, $ruleset_bitfield, $ruleset_options);

		$this->template->assign_vars(array(
			'BOOSKIT_ICDISCIPLINARY_RULESET' => $ruleset_html,
			'CHARACTER_NAME' => $character['character_name'],
		));

		return $this->helper->render('add_ic_record.html', $this->user->lang['ADD_IC_RECORD']);
	}

	public function edit_record($record_id)
	{
		$this->user->add_lang_ext('booskit/icdisciplinary', 'icdisciplinary');
		$this->user->add_lang('common');

		$record = $this->ic_manager->get_record($record_id);
		if (!$record)
		{
			trigger_error('NO_IC_RECORDS');
		}

		$character = $this->ic_manager->get_character($record['character_id']);
		$target_user_id = $character['user_id'];

		if (!$this->ic_manager->can_edit_record($this->user->data['user_id'], $record, $target_user_id))
		{
			trigger_error('NOT_AUTHORISED');
		}

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('edit_ic_record'))
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
			if (!$this->ic_manager->can_issue_type($this->user->data['user_id'], $target_user_id, $type_id))
			{
				trigger_error('NOT_AUTHORISED');
			}

			// Parse BBCode
			$reason_uid = $reason_bitfield = $reason_options = '';
			$evidence_uid = $evidence_bitfield = $evidence_options = '';
			$allow_bbcode = $allow_urls = $allow_smilies = true;

			generate_text_for_storage($reason, $reason_uid, $reason_bitfield, $reason_options, $allow_bbcode, $allow_urls, $allow_smilies);
			generate_text_for_storage($evidence, $evidence_uid, $evidence_bitfield, $evidence_options, $allow_bbcode, $allow_urls, $allow_smilies);

			$this->ic_manager->update_record($record_id, $type_id, $issue_date, $reason, $evidence,
				$reason_uid, $reason_bitfield, $reason_options,
				$evidence_uid, $evidence_bitfield, $evidence_options,
				$this->user->data['user_id'], time());

			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_IC_RECORD_EDITED', time(), array($type_id, $character['character_name']));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $target_user_id . '&character_id=' . $record['character_id']);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['IC_RECORD_UPDATED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$this->assign_form_vars($target_user_id, $record['character_id'], $record, true);

		add_form_key('edit_ic_record');

		// Ruleset
		$ruleset_text = $this->config_text->get('booskit_icdisciplinary_ruleset');
		$ruleset_uid = isset($this->config['booskit_icdisciplinary_ruleset_uid']) ? $this->config['booskit_icdisciplinary_ruleset_uid'] : '';
		$ruleset_bitfield = isset($this->config['booskit_icdisciplinary_ruleset_bitfield']) ? $this->config['booskit_icdisciplinary_ruleset_bitfield'] : '';
		$ruleset_options = isset($this->config['booskit_icdisciplinary_ruleset_options']) ? $this->config['booskit_icdisciplinary_ruleset_options'] : 7;
		$ruleset_html = generate_text_for_display($ruleset_text, $ruleset_uid, $ruleset_bitfield, $ruleset_options);

		$this->template->assign_vars(array(
			'BOOSKIT_ICDISCIPLINARY_RULESET' => $ruleset_html,
			'CHARACTER_NAME' => $character['character_name'],
		));

		return $this->helper->render('add_ic_record.html', $this->user->lang['EDIT_IC_RECORD']);
	}

	public function delete_record($record_id)
	{
		$this->user->add_lang_ext('booskit/icdisciplinary', 'icdisciplinary');

		$record = $this->ic_manager->get_record($record_id);
		if (!$record)
		{
			trigger_error('NO_IC_RECORDS');
		}
		$character = $this->ic_manager->get_character($record['character_id']);
		$target_user_id = $character['user_id'];

		if (!$this->ic_manager->can_delete_record($this->user->data['user_id'], $record, $target_user_id))
		{
			trigger_error('NOT_AUTHORISED');
		}

		if (confirm_box(true))
		{
			$this->ic_manager->delete_record($record_id);

			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_IC_RECORD_DELETED', time(), array($record['disciplinary_type_id'], $character['character_name']));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $target_user_id . '&character_id=' . $record['character_id']);
			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['IC_RECORD_DELETED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}
		else
		{
			confirm_box(false, 'CONFIRM_DELETE_IC_RECORD', build_hidden_fields(array(
				'record_id'	=> $record_id,
			)));
		}
	}

	protected function assign_form_vars($user_id, $character_id, $record = null, $is_edit = false)
	{
		$definitions = $this->ic_manager->get_definitions();

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

		$types_json_data = [];
		foreach ($definitions as $def) {
			if (!$this->ic_manager->can_issue_type($this->user->data['user_id'], $user_id, $def['id']))
			{
				if ($def['id'] != $current_type)
				{
					continue;
				}
			}

			$can_notes = $this->ic_manager->can_issue_private_notes($this->user->data['user_id'], $user_id, $def['id']);
			$types_json_data[$def['id']] = [
				'can_issue_private_notes' => $can_notes,
			];

			$this->template->assign_block_vars('types', array(
				'ID' 		=> $def['id'],
				'NAME' 		=> $def['name'],
				'SELECTED' 	=> ($def['id'] == $current_type),
				'CAN_ISSUE_PRIVATE_NOTES' => $can_notes ? 1 : 0,
			));
		}

		$this->template->assign_vars(array(
			'S_ISSUE_DATE' 		=> $default_date,
			'REASON' 			=> $current_reason,
			'EVIDENCE' 			=> $current_evidence,
			'S_EDIT'			=> $is_edit,
			'TYPES_JSON'		=> json_encode($types_json_data),
			'U_ACTION'			=> $is_edit
				? $this->helper->route('booskit_icdisciplinary_edit_record', array('record_id' => $record['record_id']))
				: $this->helper->route('booskit_icdisciplinary_add_record', array('character_id' => $character_id)),
			'U_BACK'			=> append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id . '&character_id=' . $character_id),
			'S_BBCODE_ALLOWED' => true,
			'S_BBCODE_QUOTE'   => true,
			'S_BBCODE_IMG'     => true,
			'S_LINKS_ALLOWED'  => true,
			'S_SMILIES_ALLOWED'=> true,
		));
	}
}
