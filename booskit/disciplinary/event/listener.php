<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $template;
	protected $user;
	protected $disciplinary_manager;
	protected $helper;
	protected $auth;

	public function __construct(\phpbb\template\template $template, \phpbb\user $user, \booskit\disciplinary\service\disciplinary_manager $disciplinary_manager, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth)
	{
		$this->template = $template;
		$this->user = $user;
		$this->disciplinary_manager = $disciplinary_manager;
		$this->helper = $helper;
		$this->auth = $auth;
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
		$this->user->add_lang_ext('booskit/disciplinary', 'disciplinary');
	}

	public function view_profile($event)
	{
		$user_id = $event['member']['user_id'];
		$this->user->add_lang_ext('booskit/disciplinary', 'disciplinary');

		$viewer_id = $this->user->data['user_id'];

		// Determine Add Permission
		if ($this->disciplinary_manager->can_add_disciplinary($viewer_id, $user_id))
		{
			$this->template->assign_vars(array(
				'U_ADD_DISCIPLINARY' => $this->helper->route('booskit_disciplinary_add_record', array('user_id' => $user_id)),
			));
		}

		$records = $this->disciplinary_manager->get_user_records($user_id);

		// Gather all issuer, editor, and archiver IDs to fetch usernames in bulk
		$user_ids_to_fetch = array_unique(array_filter(array_merge(
			array_column($records, 'issuer_user_id'),
			array_column($records, 'edited_by_user_id'),
			array_column($records, 'archived_by_user_id')
		)));
		$user_names = $this->disciplinary_manager->get_usernames($user_ids_to_fetch);

		$displayed_count = 0;
		$limit = 3;

		if (!empty($records))
		{
			$this->template->assign_vars(array(
				'U_VIEW_ALL_DISCIPLINARY' => $this->helper->route('booskit_disciplinary_view_all', array('user_id' => $user_id)),
			));
		}

		foreach ($records as $record)
		{
			$definition = $this->disciplinary_manager->get_definition($record['disciplinary_type_id']);

			// Check Access
			$access = $this->disciplinary_manager->check_view_access($viewer_id, $user_id, $definition);
			if (!$access['allowed'])
			{
				continue;
			}

			// Check Archived Access
			if (!empty($record['is_archived']) && !$this->disciplinary_manager->can_view_archived_record($viewer_id, $record))
			{
				continue;
			}

			$displayed_count++;
			if ($displayed_count > $limit)
			{
				break;
			}

			$type_name = $definition ? $definition['name'] : $record['disciplinary_type_id'];
			$color = isset($definition['color']) ? $definition['color'] : '';

			$issuer_name = isset($user_names[$record['issuer_user_id']]) ? $user_names[$record['issuer_user_id']] : $this->user->lang['GUEST'];

			$can_edit = $this->disciplinary_manager->can_edit_record($viewer_id, $record);
			$can_delete = $this->disciplinary_manager->can_delete_record($viewer_id, $record);
			$can_archive = empty($record['is_archived']) && $this->disciplinary_manager->can_archive_record($viewer_id, $record);
			$can_unarchive = !empty($record['is_archived']) && $this->disciplinary_manager->can_unarchive_record($viewer_id, $record);

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
			if ($access['show_evidence'])
			{
				$evidence_uid = isset($record['evidence_bbcode_uid']) ? $record['evidence_bbcode_uid'] : '';
				$evidence_bitfield = isset($record['evidence_bbcode_bitfield']) ? $record['evidence_bbcode_bitfield'] : '';
				$evidence_options = isset($record['evidence_bbcode_options']) ? $record['evidence_bbcode_options'] : 7;
				$evidence_html = generate_text_for_display($record['evidence'], $evidence_uid, $evidence_bitfield, $evidence_options);
			}

			$this->template->assign_block_vars('disciplinary', array(
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
				'U_EDIT' => $can_edit ? $this->helper->route('booskit_disciplinary_edit_record', array('record_id' => $record['record_id'])) : '',
				'U_DELETE' => $can_delete ? $this->helper->route('booskit_disciplinary_delete_record', array('record_id' => $record['record_id'])) : '',
				'U_ARCHIVE' => $can_archive ? $this->helper->route('booskit_disciplinary_archive_record', array('record_id' => $record['record_id'])) : '',
				'U_UNARCHIVE' => $can_unarchive ? $this->helper->route('booskit_disciplinary_unarchive_record', array('record_id' => $record['record_id'])) : '',
			));
		}
	}
}
