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
			'core.memberlist_view_profile' => 'memberlist_view_profile',
		);
	}

	public function memberlist_view_profile($event)
	{
		$this->user->add_lang_ext('booskit/awards', 'awards');

		$member_id = $event['member']['user_id'];

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
					'DATE' => $this->user->format_date($award['issue_date']),
					'COMMENT' => $award['comment'],
				));
			}
		}

		// Add link to add award if moderator
		if ($this->auth->acl_get('m_') || $this->auth->acl_get('a_'))
		{
			$this->template->assign_vars(array(
				'U_ADD_AWARD' => $this->helper->route('booskit_awards_add_award', array('user_id' => $member_id)),
			));
		}
	}
}
