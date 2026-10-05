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
			'core.text_formatter_s9e_configure_after'   => 'configure_s9e_bbcode',
			'core.text_formatter_s9e_render_after'      => 'render_s9e_bbcode',
			'core.submit_post_end'                      => 'submit_post_end',
			'core.delete_posts_after'                   => 'delete_posts_after',
			'core.approve_posts_after'                  => 'approve_posts_after',
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

	public function configure_s9e_bbcode($event)
	{
		$configurator = $event['configurator'];

		// Silent stat tag: [stat=tag][/stat] or [stat=tag]...[/stat]
		if (!isset($configurator->BBCodes['stat']))
		{
			$configurator->BBCodes->addCustom(
				'[stat={IDENTIFIER?}]{TEXT?}[/stat]',
				'<span class="dashboard-stat-hidden" style="display:none;" data-stat="{@stat}"></span>'
			);
		}

		// Visible badge stat tag: [visiblestat=tag][/visiblestat] or [visiblestat=tag]Custom Label[/visiblestat]
		if (!isset($configurator->BBCodes['visiblestat']))
		{
			$configurator->BBCodes->addCustom(
				'[visiblestat={IDENTIFIER?}]{TEXT?}[/visiblestat]',
				'<span class="dashboard-visiblestat-tag" data-stat="{@stat}"><xsl:choose><xsl:when test=". != \'\'"><xsl:apply-templates/></xsl:when><xsl:otherwise><xsl:value-of select="@stat"/></xsl:otherwise></xsl:choose></span>'
			);
		}
	}

	public function render_s9e_bbcode($event)
	{
		if (isset($event['html']))
		{
			$html = $event['html'];

			// Empty out silent [stat] tags from rendered HTML completely
			if (strpos($html, 'dashboard-stat-hidden') !== false)
			{
				$html = preg_replace('#<span class="dashboard-stat-hidden"[^>]*?>.*?</span>#is', '', $html);
			}

			// Render styled badge for [visiblestat] tags
			if (strpos($html, 'dashboard-visiblestat-tag') !== false)
			{
				$viewer_id = (int) $this->user->data['user_id'];
				$html = preg_replace_callback(
					'#<span class="dashboard-visiblestat-tag" data-stat="([a-zA-Z0-9_\-]+)">(.*?)</span>#is',
					function($matches) use ($viewer_id) {
						$tag = strtolower($matches[1]);
						$text = trim($matches[2]);
						$def = $this->dashboard_manager->get_stat_definition_by_tag($tag);
						if (!$def || !$this->dashboard_manager->can_view_stat_definition($viewer_id, $def))
						{
							return '';
						}
						$color = !empty($def['stat_color']) ? $def['stat_color'] : '#2563eb';
						$title = !empty($def['stat_title']) ? $def['stat_title'] : $tag;
						$display = !empty($text) ? $text : $title;
						return '<span class="dashboard-stat-badge" style="background-color: ' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . '; color: #ffffff; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 600; display: inline-block; vertical-align: middle;" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"><i class="icon fa-tag fa-fw"></i> ' . htmlspecialchars($display, ENT_QUOTES, 'UTF-8') . '</span>';
					},
					$html
				);
			}

			// Remove legacy/cached dashboard-stat-tag if encountered
			if (strpos($html, 'dashboard-stat-tag') !== false)
			{
				$html = preg_replace('#<span class="dashboard-stat-tag"[^>]*?>.*?</span>#is', '', $html);
			}

			$event['html'] = $html;
		}
	}

	public function submit_post_end($event)
	{
		$data = $event['data'];
		$post_id = isset($data['post_id']) ? (int) $data['post_id'] : 0;
		$topic_id = isset($data['topic_id']) ? (int) $data['topic_id'] : 0;
		$forum_id = isset($data['forum_id']) ? (int) $data['forum_id'] : 0;
		$poster_id = isset($data['poster_id']) ? (int) $data['poster_id'] : (int) $this->user->data['user_id'];
		$post_time = isset($data['post_time']) ? (int) $data['post_time'] : time();
		$message = isset($data['message']) ? $data['message'] : '';

		if ($post_id > 0)
		{
			$current_groups = $this->dashboard_manager->get_user_groups($poster_id);
			$this->dashboard_manager->sync_post_stats($post_id, $message, $topic_id, $forum_id, $poster_id, $post_time, $current_groups);
		}
	}

	public function delete_posts_after($event)
	{
		$post_ids = isset($event['post_ids']) ? $event['post_ids'] : [];
		if (!empty($post_ids))
		{
			$this->dashboard_manager->delete_post_stats($post_ids, true);
		}
	}

	public function approve_posts_after($event)
	{
		$post_info = isset($event['post_info']) ? $event['post_info'] : [];
		foreach ($post_info as $post)
		{
			$post_id = isset($post['post_id']) ? (int) $post['post_id'] : 0;
			$message = isset($post['post_text']) ? $post['post_text'] : '';
			$topic_id = isset($post['topic_id']) ? (int) $post['topic_id'] : 0;
			$forum_id = isset($post['forum_id']) ? (int) $post['forum_id'] : 0;
			$poster_id = isset($post['poster_id']) ? (int) $post['poster_id'] : 0;
			$post_time = isset($post['post_time']) ? (int) $post['post_time'] : time();

			if ($post_id > 0)
			{
				$current_groups = $this->dashboard_manager->get_user_groups($poster_id);
				$this->dashboard_manager->sync_post_stats($post_id, $message, $topic_id, $forum_id, $poster_id, $post_time, $current_groups);
			}
		}
	}
}
