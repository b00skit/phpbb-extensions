<?php
/**
 *
 * @package booskit/commendations
 * @license MIT
 *
 */

namespace booskit\commendations\controller;

class main
{
	protected $config;
	protected $request;
	protected $template;
	protected $user;
	protected $helper;
	protected $log;
	protected $commendations_manager;
	protected $root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \phpbb\log\log_interface $log, \booskit\commendations\service\commendations_manager $commendations_manager, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->log = $log;
		$this->commendations_manager = $commendations_manager;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	public function add_commendation($user_id)
	{
		if (!$this->commendations_manager->can_add_commendation($this->user->data['user_id'], $user_id))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$this->user->add_lang_ext('booskit/commendations', 'commendations');
		$this->user->add_lang('common');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('add_commendation'))
			{
				trigger_error('FORM_INVALID');
			}

			$type = $this->request->variable('commendation_type', '');
			$character_name = $this->request->variable('character_name', '', true);
			$reason = $this->request->variable('reason', '', true);
			$date_raw = $this->request->variable('commendation_date', '');

			$date = time();
			if (!empty($date_raw))
			{
				$date = strtotime($date_raw);
			}

			if (empty($type))
			{
				trigger_error($this->user->lang['NO_COMMENDATION_TYPE_SELECTED'] . $this->helper->previous_route(), E_USER_WARNING);
			}

			// Parse BBCode
			$uid = $bitfield = $options = '';
			$allow_bbcode = $allow_urls = $allow_smilies = true;
			generate_text_for_storage($reason, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

			$this->commendations_manager->add_commendation($user_id, $type, $date, $character_name, $reason, $this->user->data['user_id'], $uid, $bitfield, $options);

			// Public Posting
			if (!empty($this->config['booskit_commendations_enable_public_posting']) && $this->request->variable('make_public_post', 0))
			{
				$target_username = $this->commendations_manager->get_username_string($user_id);
				$issuer_username = $this->user->data['username'];
				$date_str = $this->user->format_date($date, 'D M d, Y');
				$type_lang = ($type == 'IC') ? $this->user->lang['COMMENDATION_TYPE_IC'] : $this->user->lang['COMMENDATION_TYPE_OOC'];

				$subject_tpl = isset($this->config['booskit_commendations_public_posting_subject_tpl']) && !empty($this->config['booskit_commendations_public_posting_subject_tpl'])
					? $this->config['booskit_commendations_public_posting_subject_tpl']
					: 'Commendation: {CHARACTER_NAME}';

				$body_tpl = isset($this->config['booskit_commendations_public_posting_body_tpl']) && !empty($this->config['booskit_commendations_public_posting_body_tpl'])
					? $this->config['booskit_commendations_public_posting_body_tpl']
					: "Commendation Type: {TYPE}\nCharacter Name: {CHARACTER_NAME}\nRecipient: {TARGET_USERNAME}\nIssued By: {ISSUER_USERNAME}\nDate: {DATE}\n\nReason:\n{REASON}";

				$placeholders = [
					'{CHARACTER_NAME}'  => $character_name,
					'{TARGET_USERNAME}' => $target_username,
					'{ISSUER_USERNAME}' => $issuer_username,
					'{TYPE}'            => $type_lang,
					'{DATE}'            => $date_str,
					'{REASON}'          => $reason,
				];

				$subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject_tpl);
				$body = str_replace(array_keys($placeholders), array_values($placeholders), $body_tpl);

				$mode = isset($this->config['booskit_commendations_public_posting_mode']) ? $this->config['booskit_commendations_public_posting_mode'] : 'forum';
				$forum_id = isset($this->config['booskit_commendations_public_posting_forum_id']) ? (int) $this->config['booskit_commendations_public_posting_forum_id'] : 0;
				$post_id = isset($this->config['booskit_commendations_public_posting_post_id']) ? (int) $this->config['booskit_commendations_public_posting_post_id'] : 0;
				$poster_id = isset($this->config['booskit_commendations_public_posting_poster_id']) ? (int) $this->config['booskit_commendations_public_posting_poster_id'] : 0;

				$this->commendations_manager->create_public_post($forum_id, $poster_id, $subject, $body, $mode, $post_id);
			}

			$user_row = $this->commendations_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_COMMENDATION_ADDED', time(), array($user_row));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['COMMENDATION_ADDED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$this->assign_form_vars($user_id, null, false);
		add_form_key('add_commendation');

		page_header($this->user->lang['COMMENDATION_ADD']);
		return $this->helper->render('add_commendation.html', $this->user->lang['COMMENDATION_ADD']);
	}

	public function edit_commendation($commendation_id)
	{
		$this->user->add_lang_ext('booskit/commendations', 'commendations');
		$this->user->add_lang('common');

		$commendation = $this->commendations_manager->get_commendation($commendation_id);
		if (!$commendation)
		{
			trigger_error('NO_COMMENDATION_RECORD');
		}
		$user_id = $commendation['user_id'];

		if (!$this->commendations_manager->can_edit_commendation($this->user->data['user_id'], $user_id, $commendation['issuer_user_id']))
		{
			trigger_error('NOT_AUTHORISED');
		}

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('edit_commendation'))
			{
				trigger_error('FORM_INVALID');
			}

			$type = $this->request->variable('commendation_type', '');
			$character_name = $this->request->variable('character_name', '', true);
			$reason = $this->request->variable('reason', '', true);
			$date_raw = $this->request->variable('commendation_date', '');

			$date = time();
			if (!empty($date_raw))
			{
				$date = strtotime($date_raw);
			}

			if (empty($type))
			{
				trigger_error($this->user->lang['NO_COMMENDATION_TYPE_SELECTED'] . $this->helper->previous_route(), E_USER_WARNING);
			}

			// Parse BBCode
			$uid = $bitfield = $options = '';
			$allow_bbcode = $allow_urls = $allow_smilies = true;
			generate_text_for_storage($reason, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

			$this->commendations_manager->update_commendation($commendation_id, $type, $date, $character_name, $reason, $uid, $bitfield, $options);

			$user_row = $this->commendations_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_COMMENDATION_EDITED', time(), array($user_row));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['COMMENDATION_UPDATED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$this->assign_form_vars($user_id, $commendation, true);
		add_form_key('edit_commendation');

		page_header($this->user->lang['COMMENDATION_EDIT']);
		return $this->helper->render('add_commendation.html', $this->user->lang['COMMENDATION_EDIT']);
	}

	public function remove_commendation($commendation_id)
	{
		$this->user->add_lang_ext('booskit/commendations', 'commendations');

		$commendation = $this->commendations_manager->get_commendation($commendation_id);
		if (!$commendation)
		{
			trigger_error('NO_COMMENDATION_RECORD');
		}
		$user_id = $commendation['user_id'];

		if (!$this->commendations_manager->can_delete_commendation($this->user->data['user_id'], $user_id, $commendation['issuer_user_id']))
		{
			trigger_error('NOT_AUTHORISED');
		}

		if (confirm_box(true))
		{
			$this->commendations_manager->delete_commendation($commendation_id);

			$user_row = $this->commendations_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_COMMENDATION_DELETED', time(), array($user_row));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);
			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['COMMENDATION_DELETED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}
		else
		{
			confirm_box(false, 'DELETE_COMMENDATION_CONFIRM', build_hidden_fields(array(
				'commendation_id'	=> $commendation_id,
			)));
		}
	}

	public function view_all($user_id)
	{
		$this->user->add_lang_ext('booskit/commendations', 'commendations');

		// Check view access
		if (!$this->commendations_manager->get_user_view_access($this->user->data['user_id'], $user_id))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$target_username = $this->commendations_manager->get_username_string($user_id);
		$commendations = $this->commendations_manager->get_commendations($user_id); // No limit

		// Collect issuer IDs
		$issuer_ids = [];
		foreach ($commendations as $comm)
		{
			$issuer_ids[] = $comm['issuer_user_id'];
		}
		$issuer_names = $this->commendations_manager->get_usernames(array_unique($issuer_ids));

		$can_copy = $this->commendations_manager->can_copy_commendation($this->user->data['user_id'], $user_id);

		foreach ($commendations as $comm)
		{
			$has_access = $this->commendations_manager->can_edit_commendation($this->user->data['user_id'], $user_id, $comm['issuer_user_id']);
			$issuer_name = isset($issuer_names[$comm['issuer_user_id']]) ? $issuer_names[$comm['issuer_user_id']] : 'Unknown';
			$clipboard_text = $can_copy ? $this->commendations_manager->format_clipboard_text($comm, $target_username, $issuer_name) : '';

			// Render BBCode
			$bbcode_uid = isset($comm['bbcode_uid']) ? $comm['bbcode_uid'] : '';
			$bbcode_bitfield = isset($comm['bbcode_bitfield']) ? $comm['bbcode_bitfield'] : '';
			$bbcode_options = isset($comm['bbcode_options']) ? $comm['bbcode_options'] : 7;
			$reason_html = generate_text_for_display($comm['reason'], $bbcode_uid, $bbcode_bitfield, $bbcode_options);

			$this->template->assign_block_vars('commendations', array(
				'ID' => $comm['commendation_id'],
				'TYPE' => $comm['commendation_type'], // IC or OOC
				'TYPE_LANG' => ($comm['commendation_type'] == 'IC') ? $this->user->lang['COMMENDATION_TYPE_IC'] : $this->user->lang['COMMENDATION_TYPE_OOC'],
				'CHARACTER' => $comm['character_name'],
				'REASON' => $reason_html,
				'DATE' => $this->user->format_date($comm['commendation_date'], 'D M d, Y'),
				'ISSUER' => $issuer_name,
				'U_ISSUER' => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $comm['issuer_user_id']),
				'U_EDIT' => $has_access ? $this->helper->route('booskit_commendations_edit', array('commendation_id' => $comm['commendation_id'])) : '',
				'U_REMOVE' => $has_access ? $this->helper->route('booskit_commendations_remove', array('commendation_id' => $comm['commendation_id'])) : '',
				'S_CAN_COPY' => $can_copy,
				'CLIPBOARD_TEXT' => utf8_htmlspecialchars($clipboard_text),
			));
		}

		$this->template->assign_vars(array(
			'COMMENDATION_USER' => $target_username,
			'U_BACK' => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id),
		));

		page_header(sprintf($this->user->lang['COMMENDATIONS_FOR'], $target_username));
		return $this->helper->render('view_commendations.html', sprintf($this->user->lang['COMMENDATIONS_FOR'], $target_username));
	}

	protected function assign_form_vars($user_id, $commendation = null, $is_edit = false)
	{
		$default_date = date('Y-m-d');
		$current_type = 'IC';
		$current_character = '';
		$current_reason = '';

		if ($commendation)
		{
			$default_date = date('Y-m-d', $commendation['commendation_date']);
			$current_type = $commendation['commendation_type'];
			$current_character = $commendation['character_name'];

			// Decode BBCode for editing
			$bbcode_uid = isset($commendation['bbcode_uid']) ? $commendation['bbcode_uid'] : '';
			$bbcode_options = isset($commendation['bbcode_options']) ? $commendation['bbcode_options'] : 7;
			$text_data = generate_text_for_edit($commendation['reason'], $bbcode_uid, $bbcode_options);
			$current_reason = $text_data['text'];
		}

		$this->template->assign_vars(array(
			'S_COMMENDATION_DATE' 	=> $default_date,
			'CHARACTER_NAME' 		=> $current_character,
			'REASON' 				=> $current_reason,
			'COMMENDATION_TYPE'		=> $current_type,
			'S_EDIT'				=> $is_edit,
			'S_ENABLE_PUBLIC_POSTING' => !empty($this->config['booskit_commendations_enable_public_posting']),
			'U_ACTION'				=> $is_edit
				? $this->helper->route('booskit_commendations_edit', array('commendation_id' => $commendation['commendation_id']))
				: $this->helper->route('booskit_commendations_add', array('user_id' => $user_id)),
			'U_BACK'				=> append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id),
			'S_BBCODE_ALLOWED' => true,
			'S_BBCODE_QUOTE'   => true,
			'S_BBCODE_IMG'     => true,
			'S_LINKS_ALLOWED'  => true,
			'S_SMILIES_ALLOWED'=> true,
		));
	}
}
