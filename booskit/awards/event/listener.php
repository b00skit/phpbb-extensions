<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $template;
	protected $user;
	protected $helper;
	protected $auth;
	protected $award_manager;

	public function __construct(\phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth, \booskit\awards\service\award_manager $award_manager)
	{
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->auth = $auth;
		$this->award_manager = $award_manager;
	}

	public static function getSubscribedEvents()
	{
		return array(
			'core.user_setup' => 'load_language_on_setup',
			'core.memberlist_view_profile' => 'memberlist_view_profile',
		);
	}

	public function load_language_on_setup($event)
	{
		$this->user->add_lang_ext('booskit/awards', 'awards');
	}

	public function memberlist_view_profile($event)
	{
		$this->user->add_lang_ext('booskit/awards', 'awards');

		$member_id = $event['member']['user_id'];

		$issuer_level = $this->award_manager->get_user_role_level($this->user->data['user_id']);
		$target_level = $this->award_manager->get_user_role_level($member_id);

		$can_remove = false;
		if ($issuer_level >= 2)
		{
			if ($issuer_level >= 3 || $target_level < $issuer_level)
			{
				$can_remove = true;
			}
		}

		// Load awards for this user
		$user_awards = $this->award_manager->get_user_awards($member_id);

		foreach ($user_awards as $award)
		{
			$definition = $this->award_manager->get_definition($award['award_definition_id']);
			if ($definition)
			{
				$this->template->assign_block_vars('user_awards', array(
					'NAME' => $definition['name'],
					'IMAGE' => $definition['image'],
					'MAX_WIDTH' => isset($definition['max-width']) ? $definition['max-width'] : '',
					'MAX_HEIGHT' => isset($definition['max-height']) ? $definition['max-height'] : '',
					'DATE' => $this->user->format_date($award['issue_date'], 'D M d, Y'),
					'COMMENT' => $award['comment'],
					'U_REMOVE' => $can_remove ? $this->helper->route('booskit_awards_remove_award', array('award_id' => $award['award_id'])) : '',
				));
			}
		}
		// Logic:
		// L1 (1) -> Target < 1 (0)
		// L2 (2) -> Target < 2 (0, 1)
		// Full (3) -> Everyone
		$can_add = false;
		if ($issuer_level >= 1)
		{
			if ($issuer_level >= 3 || $target_level < $issuer_level)
			{
				$can_add = true;
			}
		}

		if ($can_add)
		{
			$this->template->assign_vars(array(
				'U_ADD_AWARD' => $this->helper->route('booskit_awards_add_award', array('user_id' => $member_id)),
			));
		}
	}
}
