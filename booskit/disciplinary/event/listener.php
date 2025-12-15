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
			'core.memberlist_view_profile' => 'view_profile',
		);
	}

	public function view_profile($event)
	{
		$user_id = $event['member']['user_id'];
		$this->user->add_lang_ext('booskit/disciplinary', 'disciplinary');

		// Determine Viewer Level
		$viewer_level = $this->disciplinary_manager->get_user_role_level($this->user->data['user_id']);

		if ($viewer_level === 0)
		{
			return;
		}

		// Determine Target Level
		$target_level = $this->disciplinary_manager->get_user_role_level($user_id);

		// Hierarchical Access Check:
		// Full Access (4) can target everyone.
		// Others must be strictly higher level than target.
		if ($viewer_level !== 4 && $viewer_level <= $target_level)
		{
			return;
		}

		$records = $this->disciplinary_manager->get_user_records($user_id);
		$definitions = $this->disciplinary_manager->get_definitions();

		// Gather all issuer IDs to fetch usernames in bulk (optimization)
		$issuer_ids = array_unique(array_column($records, 'issuer_user_id'));
		$issuer_usernames = $this->disciplinary_manager->get_usernames($issuer_ids);

		foreach ($records as $record)
		{
			$definition = $this->disciplinary_manager->get_definition($record['disciplinary_type_id']);
			$type_name = $definition ? $definition['name'] : $record['disciplinary_type_id'];
			$color = isset($definition['color']) ? $definition['color'] : '';

			$issuer_name = isset($issuer_usernames[$record['issuer_user_id']]) ? $issuer_usernames[$record['issuer_user_id']] : $this->user->lang['GUEST'];

			// Edit/Delete Permission Check: Full Access (4) can edit all; others only their own
			$can_modify = ($viewer_level == 4 || $this->user->data['user_id'] == $record['issuer_user_id']);

			$this->template->assign_block_vars('disciplinary', array(
				'ID' => $record['record_id'],
				'TYPE' => utf8_htmlspecialchars($type_name),
				'DATE' => $this->user->format_date($record['issue_date']),
				'REASON' => utf8_htmlspecialchars($record['reason']),
				'EVIDENCE' => utf8_htmlspecialchars($record['evidence']),
				'ISSUER_ID' => $record['issuer_user_id'],
				'ISSUER_NAME' => $issuer_name,
				'COLOR' => $color,
				'U_EDIT' => $can_modify ? $this->helper->route('booskit_disciplinary_edit_record', array('record_id' => $record['record_id'])) : '',
				'U_DELETE' => $can_modify ? $this->helper->route('booskit_disciplinary_delete_record', array('record_id' => $record['record_id'])) : '',
			));
		}

		$this->template->assign_vars(array(
			'U_ADD_DISCIPLINARY' => $this->helper->route('booskit_disciplinary_add_record', array('user_id' => $user_id)),
		));
	}
}
