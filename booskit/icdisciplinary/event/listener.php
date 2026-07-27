<?php
/**
 *
 * @package booskit/icdisciplinary
 * @license MIT
 *
 */

namespace booskit\icdisciplinary\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $template;
	protected $user;
	protected $request;
	protected $ic_manager;
	protected $helper;
	protected $auth;
	protected $root_path;
	protected $php_ext;

	public function __construct(\phpbb\template\template $template, \phpbb\user $user, \phpbb\request\request_interface $request, \booskit\icdisciplinary\service\ic_manager $ic_manager, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth, $root_path, $php_ext)
	{
		$this->template = $template;
		$this->user = $user;
		$this->request = $request;
		$this->ic_manager = $ic_manager;
		$this->helper = $helper;
		$this->auth = $auth;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup' => 'load_language_on_setup',
			'core.memberlist_view_profile' => 'view_profile',
		);
	}

	public function load_language_on_setup($event)
	{
		$this->user->add_lang_ext('booskit/icdisciplinary', 'icdisciplinary');
	}

	public function view_profile($event)
	{
		$user_id = (int) $event['member']['user_id'];
		$viewer_id = (int) $this->user->data['user_id'];

		$this->user->add_lang_ext('booskit/icdisciplinary', 'icdisciplinary');

		$can_add_character = $this->ic_manager->can_create_character($viewer_id, $user_id);
		$can_delete_perm = $this->ic_manager->can_delete_character($viewer_id, $user_id);

		// Include archived characters if user has delete permission or full access (legacy level 4)
		$include_archived = $can_delete_perm || ($this->ic_manager->get_user_role_level($viewer_id) >= 4);
		$characters = $this->ic_manager->get_user_characters($user_id, $include_archived);

		$current_character_id = $this->request->variable('character_id', 0);
		$current_character = null;

		$options = '';
		foreach ($characters as $char)
		{
			$selected = ($char['character_id'] == $current_character_id) ? 'selected="selected"' : '';
			$name = $char['character_name'];
			if ($char['is_archived'])
			{
				$name .= ' ' . $this->user->lang['CHARACTER_ARCHIVED_STATUS'];
			}
			$options .= '<option value="' . $char['character_id'] . '" ' . $selected . '>' . $name . '</option>';

			if ($char['character_id'] == $current_character_id)
			{
				$current_character = $char;
			}
		}

		if ($current_character_id && !$current_character)
		{
			$current_character_id = 0;
		}

		$can_archive_character = ($current_character && $this->ic_manager->can_archive_character($viewer_id, $user_id));
		$can_delete_character = ($current_character && $can_delete_perm);
		$can_add_record = ($current_character && $this->ic_manager->can_add_record($viewer_id, $user_id));

		// Check basic access to show IC disciplinary block
		if ($this->ic_manager->get_perm_system() === 'legacy')
		{
			$viewer_level = $this->ic_manager->get_user_role_level($viewer_id);
			$target_level = $this->ic_manager->get_user_role_level($user_id);
			if ($viewer_level === 0 || ($viewer_level !== 4 && $viewer_level <= $target_level))
			{
				return;
			}
		}
		else
		{
			// Groups mode: check if viewer has any capability
			if (!$can_add_character && !$can_add_record && !$can_archive_character && !$can_delete_character && empty($characters))
			{
				return;
			}
		}

		$this->template->assign_vars(array(
			'S_IC_DISCIPLINARY' => true,
			'S_CAN_ADD_CHARACTER' => $can_add_character,
			'S_CAN_ARCHIVE_CHARACTER' => $can_archive_character,
			'S_CAN_DELETE_CHARACTER' => $can_delete_character,
			'U_ADD_CHARACTER' => $this->helper->route('booskit_icdisciplinary_add_character', array('user_id' => $user_id)),
			'U_ARCHIVE_CHARACTER' => ($current_character) ? $this->helper->route('booskit_icdisciplinary_archive_character', array('character_id' => $current_character_id)) : '',
			'U_DELETE_CHARACTER' => ($current_character) ? $this->helper->route('booskit_icdisciplinary_delete_character', array('character_id' => $current_character_id)) : '',
			'CHARACTER_OPTIONS' => $options,
			'S_HAS_CHARACTERS' => !empty($characters),
			'S_CHARACTER_SELECTED' => ($current_character_id > 0),
			'U_IC_ACTION' => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id),
		));

		if ($current_character)
		{
			$records = $this->ic_manager->get_character_records($current_character_id);

			$user_ids_to_fetch = array_unique(array_filter(array_merge(
				array_column($records, 'issuer_user_id'),
				array_column($records, 'edited_by_user_id'),
				array_column($records, 'archived_by_user_id')
			)));
			$user_names = $this->ic_manager->get_usernames($user_ids_to_fetch);

			foreach ($records as $record)
			{
				$definition = $this->ic_manager->get_definition($record['disciplinary_type_id']);
				$access = $this->ic_manager->check_view_access($viewer_id, $user_id, $definition);

				if (!$access['allowed'])
				{
					continue;
				}

				// Check Archived Access
				if (!empty($record['is_archived']) && !$this->ic_manager->can_view_archived_record($viewer_id, $record, $user_id))
				{
					continue;
				}

				$type_name = $definition ? $definition['name'] : $record['disciplinary_type_id'];
				$color = isset($definition['color']) ? $definition['color'] : '';
				$issuer_name = isset($user_names[$record['issuer_user_id']]) ? $user_names[$record['issuer_user_id']] : $this->user->lang['GUEST'];

				$can_edit = $this->ic_manager->can_edit_record($viewer_id, $record, $user_id);
				$can_delete = $this->ic_manager->can_delete_record($viewer_id, $record, $user_id);
				$can_archive = empty($record['is_archived']) && $this->ic_manager->can_archive_record($viewer_id, $record, $user_id);
				$can_unarchive = !empty($record['is_archived']) && $this->ic_manager->can_unarchive_record($viewer_id, $record, $user_id);

				$was_edited = !empty($record['edited_by_user_id']) && !empty($record['last_edited_time']);
				$edited_by_name = $was_edited ? (isset($user_names[$record['edited_by_user_id']]) ? $user_names[$record['edited_by_user_id']] : $this->user->lang['GUEST']) : '';
				$last_edited_time = $was_edited ? $this->user->format_date($record['last_edited_time'], 'D M d, Y H:i') : '';

				$is_archived = !empty($record['is_archived']);
				$archived_by_name = $is_archived ? (isset($user_names[$record['archived_by_user_id']]) ? $user_names[$record['archived_by_user_id']] : $this->user->lang['GUEST']) : '';
				$archive_date = ($is_archived && !empty($record['archive_date'])) ? $this->user->format_date($record['archive_date'], 'D M d, Y H:i') : '';
				$archive_reason = $is_archived ? (isset($record['archive_reason']) ? $record['archive_reason'] : '') : '';

				// Parse BBCode
				$reason_uid = isset($record['reason_bbcode_uid']) ? $record['reason_bbcode_uid'] : '';
				$reason_bitfield = isset($record['reason_bbcode_bitfield']) ? $record['reason_bbcode_bitfield'] : '';
				$reason_options = isset($record['reason_bbcode_options']) ? $record['reason_bbcode_options'] : 7;
				$reason_html = generate_text_for_display($record['reason'], $reason_uid, $reason_bitfield, $reason_options);

				$evidence_html = '';
				if (!empty($access['show_evidence']))
				{
					$evidence_uid = isset($record['evidence_bbcode_uid']) ? $record['evidence_bbcode_uid'] : '';
					$evidence_bitfield = isset($record['evidence_bbcode_bitfield']) ? $record['evidence_bbcode_bitfield'] : '';
					$evidence_options = isset($record['evidence_bbcode_options']) ? $record['evidence_bbcode_options'] : 7;
					$evidence_html = generate_text_for_display($record['evidence'], $evidence_uid, $evidence_bitfield, $evidence_options);
				}

				$this->template->assign_block_vars('ic_records', array(
					'ID' => $record['record_id'],
					'TYPE' => utf8_htmlspecialchars($type_name),
					'DATE' => $this->user->format_date($record['issue_date'], 'D M d, Y'),
					'REASON' => $reason_html,
					'EVIDENCE' => $evidence_html,
					'ISSUER_ID' => $record['issuer_user_id'],
					'ISSUER_NAME' => $issuer_name,
					'COLOR' => $color,
					'EDITED_BY_NAME' => $edited_by_name,
					'LAST_EDITED_TIME' => $last_edited_time,
					'S_WAS_EDITED' => $was_edited,
					'S_IS_ARCHIVED' => $is_archived,
					'ARCHIVE_REASON' => utf8_htmlspecialchars($archive_reason),
					'ARCHIVED_BY_NAME' => $archived_by_name,
					'ARCHIVE_DATE' => $archive_date,
					'U_EDIT' => $can_edit ? $this->helper->route('booskit_icdisciplinary_edit_record', array('record_id' => $record['record_id'])) : '',
					'U_DELETE' => $can_delete ? $this->helper->route('booskit_icdisciplinary_delete_record', array('record_id' => $record['record_id'])) : '',
					'U_ARCHIVE' => $can_archive ? $this->helper->route('booskit_icdisciplinary_archive_record', array('record_id' => $record['record_id'])) : '',
					'U_UNARCHIVE' => $can_unarchive ? $this->helper->route('booskit_icdisciplinary_unarchive_record', array('record_id' => $record['record_id'])) : '',
				));
			}

			$this->template->assign_vars(array(
				'U_ADD_IC_RECORD' => $can_add_record ? $this->helper->route('booskit_icdisciplinary_add_record', array('character_id' => $current_character_id)) : '',
			));
		}
	}
}
