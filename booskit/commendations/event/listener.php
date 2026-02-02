<?php
/**
 *
 * @package booskit/commendations
 * @license MIT
 *
 */

namespace booskit\commendations\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $template;
	protected $user;
	protected $commendations_manager;
	protected $helper;
	protected $root_path;
	protected $php_ext;

	public function __construct(\phpbb\template\template $template, \phpbb\user $user, \booskit\commendations\service\commendations_manager $commendations_manager, \phpbb\controller\helper $helper, $root_path, $php_ext)
	{
		$this->template = $template;
		$this->user = $user;
		$this->commendations_manager = $commendations_manager;
		$this->helper = $helper;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup' => 'load_language_on_setup',
			'core.memberlist_view_profile' => 'display_commendations',
		);
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'booskit/commendations',
			'lang_set' => 'commendations',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function display_commendations($event)
	{
		$user_id = $event['member']['user_id'];

		// Viewer permissions - check view access first
		if (!$this->commendations_manager->get_user_view_access($this->user->data['user_id'], $user_id))
		{
			return; // Don't show anything if no view access
		}

		$viewer_level = $this->commendations_manager->get_user_role_level($this->user->data['user_id']);
		$target_level = $this->commendations_manager->get_user_role_level($user_id);

		// Get latest 5 commendations
		$commendations = $this->commendations_manager->get_commendations($user_id, 5);

		// Collect issuer IDs
		$issuer_ids = [];
		foreach ($commendations as $comm)
		{
			$issuer_ids[] = $comm['issuer_user_id'];
		}
		$issuer_names = $this->commendations_manager->get_usernames(array_unique($issuer_ids));

		foreach ($commendations as $comm)
		{
			$is_issuer = ($comm['issuer_user_id'] == $this->user->data['user_id']);
			$has_access = false;

			if ($viewer_level === 4) {
				$has_access = true;
			} elseif ($is_issuer && $viewer_level >= 1) {
				$has_access = true;
			} elseif ($viewer_level >= 2 && $viewer_level > $target_level) {
				$has_access = true;
			}

			// Render BBCode
			$bbcode_uid = isset($comm['bbcode_uid']) ? $comm['bbcode_uid'] : '';
			$bbcode_bitfield = isset($comm['bbcode_bitfield']) ? $comm['bbcode_bitfield'] : '';
			$bbcode_options = isset($comm['bbcode_options']) ? $comm['bbcode_options'] : 7;
			$reason_html = generate_text_for_display($comm['reason'], $bbcode_uid, $bbcode_bitfield, $bbcode_options);

			$this->template->assign_block_vars('commendations', array(
				'ID' => $comm['commendation_id'],
				'TYPE' => $comm['commendation_type'],
				'TYPE_LANG' => ($comm['commendation_type'] == 'IC') ? $this->user->lang['COMMENDATION_TYPE_IC'] : $this->user->lang['COMMENDATION_TYPE_OOC'],
				'CHARACTER' => $comm['character_name'],
				'REASON' => $reason_html,
				'DATE' => $this->user->format_date($comm['commendation_date']),
				'ISSUER' => isset($issuer_names[$comm['issuer_user_id']]) ? $issuer_names[$comm['issuer_user_id']] : 'Unknown',
				'U_ISSUER' => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $comm['issuer_user_id']),
				'U_EDIT' => $has_access ? $this->helper->route('booskit_commendations_edit', array('commendation_id' => $comm['commendation_id'])) : '',
				'U_REMOVE' => $has_access ? $this->helper->route('booskit_commendations_remove', array('commendation_id' => $comm['commendation_id'])) : '',
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
			'U_COMMENDATION_ADD' => $can_add ? $this->helper->route('booskit_commendations_add', array('user_id' => $user_id)) : '',
			'U_COMMENDATION_VIEW_MORE' => $this->helper->route('booskit_commendations_view_all', array('user_id' => $user_id)),
			'S_HAS_COMMENDATIONS' => !empty($commendations),
			'S_COMMENDATIONS_VIEW_ACCESS' => true,
		));
	}
}
