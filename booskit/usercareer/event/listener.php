<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $template;
	protected $user;
	protected $career_manager;
	protected $helper;
	protected $root_path;
	protected $php_ext;

	public function __construct(\phpbb\template\template $template, \phpbb\user $user, \booskit\usercareer\service\career_manager $career_manager, \phpbb\controller\helper $helper, $root_path, $php_ext)
	{
		$this->template = $template;
		$this->user = $user;
		$this->career_manager = $career_manager;
		$this->helper = $helper;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup' => 'load_language_on_setup',
			'core.memberlist_view_profile' => 'display_career_notes',
		);
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'booskit/usercareer',
			'lang_set' => 'career',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function display_career_notes($event)
	{
		$user_id = $event['member']['user_id'];

		// Viewer permissions - check view access first
		if (!$this->career_manager->get_user_view_access($this->user->data['user_id']))
		{
			return; // Don't show anything if no view access
		}

		$viewer_level = $this->career_manager->get_user_role_level($this->user->data['user_id']);
		$notes = $this->career_manager->get_user_notes($user_id, 5); // Limit to 5
		$definitions = $this->career_manager->get_definitions();

		// Map definitions
		$defs_map = [];
		foreach ($definitions as $def) {
			$defs_map[$def['id']] = $def;
		}

		$target_level = $this->career_manager->get_user_role_level($user_id);

		foreach ($notes as $note)
		{
			$def = isset($defs_map[$note['career_type_id']]) ? $defs_map[$note['career_type_id']] : [];

			$is_issuer = ($note['issuer_user_id'] == $this->user->data['user_id']);
			$has_access = false;

			if ($viewer_level === 4) {
				$has_access = true;
			} elseif ($is_issuer && $viewer_level >= 1) {
				$has_access = true;
			} elseif ($viewer_level >= 2 && $viewer_level > $target_level) {
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

		// Check if user can add
		$can_add = false;
		if ($viewer_level >= 1) {
			if ($viewer_level === 4 || $viewer_level > $target_level) {
				$can_add = true;
			}
		}

		$this->template->assign_vars(array(
			'U_CAREER_ADD' => $can_add ? $this->helper->route('booskit_usercareer_add_note', array('user_id' => $user_id)) : '',
			'U_CAREER_VIEW_MORE' => $this->helper->route('booskit_usercareer_view_timeline', array('user_id' => $user_id)),
			'S_HAS_CAREER_NOTES' => !empty($notes),
		));
	}
}
