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
		$user_id = $event['member']['user_id'];
		$this->user->add_lang_ext('booskit/icdisciplinary', 'icdisciplinary');

		// Determine Viewer Level
		$viewer_level = $this->ic_manager->get_user_role_level($this->user->data['user_id']);

		if ($viewer_level === 0)
		{
			return;
		}

		// Determine Target Level (User level)
		// Usually needed for record access check, but here we view characters first.
		// "listing... should work the exact same way".
		// Disciplinary listing check: "Full Access (4) can target everyone. Others must be strictly higher level than target."
		// So if Viewer Level <= Target Level AND Viewer != 4, then NO ACCESS to VIEW?
		// "The user page should display disciplinary action...".
		// I'll assume the same visibility restrictions apply to viewing the IC records block.

		$target_level = $this->ic_manager->get_user_role_level($user_id);

		if ($viewer_level !== 4 && $viewer_level <= $target_level)
		{
			// But maybe they can view their OWN characters?
			// Disciplinary doesn't explicitly allow own view in the check I saw: `if ($viewer_level !== 4 && $viewer_level <= $target_level) { return; }`
			// Wait, if I am Level 1 and I look at myself (Level 1), 1 <= 1 is true, so I return.
			// So existing disciplinary hides records from self?
			// Let's check `disciplinary` listener again.
			// `if ($viewer_level !== 4 && $viewer_level <= $target_level)`
			// Yes.
			// But wait, usually users can see their own disciplinary records?
			// The prompt says "function pretty much equally to the disciplinary actions plugin".
			// So I will stick to this logic.
			return;
		}

		// Fetch Characters
		// Full Access (4) sees archived. Others do not.
		$include_archived = ($viewer_level >= 4);
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

        // If current char is set but not found (e.g. archived and viewer < 4), reset
        if ($current_character_id && !$current_character)
        {
            $current_character_id = 0;
        }

		// Permissions for Buttons
		$can_add_character = ($viewer_level >= 2);
		$can_archive_character = ($viewer_level >= 2 && $current_character);
		$can_delete_character = ($viewer_level >= 4 && $current_character);
		$can_add_record = ($current_character && ($viewer_level == 4 || $viewer_level > $target_level)); // Logic verified above implies we are already authorized to view, so just need char selected.

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

			// Issuer Names
			$issuer_ids = array_unique(array_column($records, 'issuer_user_id'));
			$issuer_usernames = $this->ic_manager->get_usernames($issuer_ids);

			foreach ($records as $record)
			{
				$definition = $this->ic_manager->get_definition($record['disciplinary_type_id']);
				$type_name = $definition ? $definition['name'] : $record['disciplinary_type_id'];
				$color = isset($definition['color']) ? $definition['color'] : '';

				$issuer_name = isset($issuer_usernames[$record['issuer_user_id']]) ? $issuer_usernames[$record['issuer_user_id']] : $this->user->lang['GUEST'];

				$can_modify = ($viewer_level == 4 || $this->user->data['user_id'] == $record['issuer_user_id']);

				// Parse BBCode
				$reason_uid = isset($record['reason_bbcode_uid']) ? $record['reason_bbcode_uid'] : '';
				$reason_bitfield = isset($record['reason_bbcode_bitfield']) ? $record['reason_bbcode_bitfield'] : '';
				$reason_options = isset($record['reason_bbcode_options']) ? $record['reason_bbcode_options'] : 7;
				$reason_html = generate_text_for_display($record['reason'], $reason_uid, $reason_bitfield, $reason_options);

				$evidence_uid = isset($record['evidence_bbcode_uid']) ? $record['evidence_bbcode_uid'] : '';
				$evidence_bitfield = isset($record['evidence_bbcode_bitfield']) ? $record['evidence_bbcode_bitfield'] : '';
				$evidence_options = isset($record['evidence_bbcode_options']) ? $record['evidence_bbcode_options'] : 7;
				$evidence_html = generate_text_for_display($record['evidence'], $evidence_uid, $evidence_bitfield, $evidence_options);

				$this->template->assign_block_vars('ic_records', array(
					'ID' => $record['record_id'],
					'TYPE' => utf8_htmlspecialchars($type_name),
					'DATE' => $this->user->format_date($record['issue_date']),
					'REASON' => $reason_html,
					'EVIDENCE' => $evidence_html,
					'ISSUER_ID' => $record['issuer_user_id'],
					'ISSUER_NAME' => $issuer_name,
					'COLOR' => $color,
					'U_EDIT' => $can_modify ? $this->helper->route('booskit_icdisciplinary_edit_record', array('record_id' => $record['record_id'])) : '',
					'U_DELETE' => $can_modify ? $this->helper->route('booskit_icdisciplinary_delete_record', array('record_id' => $record['record_id'])) : '',
				));
			}

            $this->template->assign_vars(array(
                'U_ADD_IC_RECORD' => $can_add_record ? $this->helper->route('booskit_icdisciplinary_add_record', array('character_id' => $current_character_id)) : '',
            ));
		}
	}
}
