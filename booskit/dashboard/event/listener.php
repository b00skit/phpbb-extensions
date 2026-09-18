<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $config;
	protected $user;
	protected $template;
	protected $helper;
	protected $db;
	protected $dashboard_manager;
	protected $table_prefix;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\user $user,
		\phpbb\template\template $template,
		\phpbb\controller\helper $helper,
		\phpbb\db\driver\driver_interface $db,
		\booskit\dashboard\service\dashboard_manager $dashboard_manager,
		$table_prefix
	) {
		$this->config = $config;
		$this->user = $user;
		$this->template = $template;
		$this->helper = $helper;
		$this->db = $db;
		$this->dashboard_manager = $dashboard_manager;
		$this->table_prefix = $table_prefix;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'                           => 'load_language_on_setup',
			'core.page_header'                          => 'add_navigation_links',
			'core.viewtopic_assign_template_vars_before' => 'log_topic_view',
			'core.viewforum_assign_template_vars_before' => 'log_forum_view',
			'core.memberlist_view_profile'              => 'log_user_view',
		];
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'booskit/dashboard',
			'lang_set' => 'dashboard',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function add_navigation_links()
	{
		if (empty($this->config['booskit_dashboard_enabled']))
		{
			return;
		}

		$viewer_id = (int) $this->user->data['user_id'];
		$can_view = $this->dashboard_manager->can_view_dashboard($viewer_id);

		$this->template->assign_vars([
			'U_BOOSKIT_DASHBOARD'         => $this->helper->route('booskit_dashboard_home'),
			'S_BOOSKIT_DASHBOARD_ALLOWED' => $can_view,
		]);
	}

	public function log_topic_view($event)
	{
		$user_id = (int) $this->user->data['user_id'];
		if ($user_id <= 0 || $user_id == ANONYMOUS || !empty($this->user->data['is_bot']))
		{
			return;
		}

		$topic_id = isset($event['topic_id']) ? (int) $event['topic_id'] : 0;
		$forum_id = isset($event['forum_id']) ? (int) $event['forum_id'] : 0;

		if ($topic_id > 0)
		{
			$this->dashboard_manager->log_topic_view($user_id, $topic_id, $forum_id);
		}
	}

	public function log_forum_view($event)
	{
		$user_id = (int) $this->user->data['user_id'];
		if ($user_id <= 0 || $user_id == ANONYMOUS || !empty($this->user->data['is_bot']))
		{
			return;
		}

		$forum_id = isset($event['forum_id']) ? (int) $event['forum_id'] : 0;
		if ($forum_id > 0)
		{
			$this->dashboard_manager->log_forum_view($user_id, $forum_id);
		}
	}

	public function log_user_view($event)
	{
		$member = isset($event['member']) ? $event['member'] : [];
		$viewed_user_id = isset($member['user_id']) ? (int) $member['user_id'] : 0;
		$user_id = (int) $this->user->data['user_id'];

		if ($viewed_user_id > 0)
		{
			// Check if viewer can view the dashboard profile for this user
			if (!empty($this->config['booskit_dashboard_enabled']) && $this->dashboard_manager->can_view_user_profile($user_id, $viewed_user_id))
			{
				$this->template->assign_vars([
					'U_BOOSKIT_DASHBOARD_PROFILE'  => $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $viewed_user_id]),
					'S_CAN_VIEW_DASHBOARD_PROFILE' => true,
				]);
			}

			// Log profile view if not anonymous/bot and not self
			if ($user_id > 0 && $user_id !== ANONYMOUS && empty($this->user->data['is_bot']) && $viewed_user_id !== $user_id)
			{
				$this->dashboard_manager->log_user_view($user_id, $viewed_user_id);
			}
		}
	}
}
