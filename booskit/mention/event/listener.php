<?php
/**
 *
 * @package booskit/mention
 * @license MIT
 *
 */

namespace booskit\mention\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\notification\manager */
	protected $notification_manager;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \booskit\mention\service\mention_manager */
	protected $mention_manager;

	public function __construct(
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\controller\helper $helper,
		\phpbb\notification\manager $notification_manager,
		\phpbb\db\driver\driver_interface $db,
		\booskit\mention\service\mention_manager $mention_manager = null
	) {
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->notification_manager = $notification_manager;
		$this->db = $db;
		$this->mention_manager = $mention_manager;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'                         => 'user_setup',
			'core.page_header'                        => 'page_header',
			'core.text_formatter_s9e_configure_after' => 'configure_s9e_bbcode',
			'core.text_formatter_s9e_render_after'    => 'render_s9e_bbcode',
			'core.submit_post_end'                    => 'submit_post_end',
			'core.approve_posts_after'                => 'approve_posts_after',
		];
	}

	/**
	 * Load extension language pack
	 *
	 * @param \phpbb\event\data $event
	 */
	public function user_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'booskit/mention',
			'lang_set' => 'mention',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * Assign template variables for mention suggestions in frontend
	 *
	 * @param \phpbb\event\data $event
	 */
	public function page_header($event)
	{
		$this->template->assign_vars([
			'U_BOOSKIT_MENTION_FIND'     => $this->helper->route('booskit_mention_find_users'),
			'S_BOOSKIT_MENTION_ENABLED' => true,
			'S_BOOSKIT_MENTION_GROUP_ENABLED' => $this->mention_manager ? $this->mention_manager->is_group_mention_enabled() : true,
		]);
	}

	/**
	 * Configure [mention] and [mentiongroup] BBCodes in s9e TextFormatter
	 *
	 * @param \phpbb\event\data $event
	 */
	public function configure_s9e_bbcode($event)
	{
		$configurator = $event['configurator'];

		// User mention BBCode
		if (!isset($configurator->BBCodes['mention']))
		{
			$configurator->BBCodes->addCustom(
				'[mention={NUMBER?}]{TEXT}[/mention]',
				'<span class="mention"><xsl:choose><xsl:when test="@mention and @mention != \'\'"><a href="./memberlist.php?mode=viewprofile&amp;u={@mention}" class="mention-link">@<xsl:apply-templates/></a></xsl:when><xsl:otherwise><a href="./memberlist.php?mode=viewprofile&amp;un={.}" class="mention-link">@<xsl:apply-templates/></a></xsl:otherwise></xsl:choose></span>'
			);
		}

		// Group mention BBCode
		if (!isset($configurator->BBCodes['mentiongroup']))
		{
			$configurator->BBCodes->addCustom(
				'[mentiongroup={NUMBER?}]{TEXT}[/mentiongroup]',
				'<span class="mention mention-group"><xsl:choose><xsl:when test="@mentiongroup and @mentiongroup != \'\'"><a href="./memberlist.php?mode=group&amp;g={@mentiongroup}" class="mention-link mention-group-link"><span class="mention-group-icon">👥</span>@<xsl:apply-templates/></a></xsl:when><xsl:otherwise><a href="./memberlist.php?mode=group" class="mention-link mention-group-link"><span class="mention-group-icon">👥</span>@<xsl:apply-templates/></a></xsl:otherwise></xsl:choose></span>'
			);
		}
	}

	/**
	 * Fallback rendering for any unparsed mention tags
	 *
	 * @param \phpbb\event\data $event
	 */
	public function render_s9e_bbcode($event)
	{
		if (isset($event['html']))
		{
			$html = $event['html'];

			if (strpos($html, '[mentiongroup') !== false)
			{
				$html = preg_replace(
					'#\[mentiongroup=(\d+)\](.*?)\[/mentiongroup\]#is',
					'<span class="mention mention-group"><a href="./memberlist.php?mode=group&amp;g=$1" class="mention-link mention-group-link"><span class="mention-group-icon">👥</span>@$2</a></span>',
					$html
				);
				$html = preg_replace(
					'#\[mentiongroup\](.*?)\[/mentiongroup\]#is',
					'<span class="mention mention-group"><a href="./memberlist.php?mode=group" class="mention-link mention-group-link"><span class="mention-group-icon">👥</span>@$1</a></span>',
					$html
				);
			}

			if (strpos($html, '[mention') !== false)
			{
				$html = preg_replace(
					'#\[mention=(\d+)\](.*?)\[/mention\]#is',
					'<span class="mention"><a href="./memberlist.php?mode=viewprofile&amp;u=$1" class="mention-link">@$2</a></span>',
					$html
				);
				$html = preg_replace(
					'#\[mention\](.*?)\[/mention\]#is',
					'<span class="mention"><a href="./memberlist.php?mode=viewprofile&amp;un=$1" class="mention-link">@$1</a></span>',
					$html
				);
			}

			$event['html'] = $html;
		}
	}

	/**
	 * Send notification on creation of the post (not on edit)
	 *
	 * @param \phpbb\event\data $event
	 */
	public function submit_post_end($event)
	{
		$mode = $event['mode'];
		$post_visibility = $event['post_visibility'];
		$data = $event['data'];

		// Allowed creation modes only: 'post' (new topic), 'reply' (reply to topic), 'quote' (quote reply)
		// Explicitly excludes all edit modes ('edit', 'edit_first_post', 'edit_topic', 'edit_last_post')
		$creation_modes = ['post', 'reply', 'quote'];
		$approved_visibility = defined('ITEM_APPROVED') ? ITEM_APPROVED : 1;

		if ($post_visibility == $approved_visibility && in_array($mode, $creation_modes))
		{
			$post_text = '';
			if (!empty($data['message']))
			{
				$post_text = $data['message'];
			}
			else if (!empty($data['post_text']))
			{
				$post_text = $data['post_text'];
			}

			$notification_data = array_merge($data, [
				'topic_title'   => isset($data['topic_title']) ? $data['topic_title'] : $event['subject'],
				'post_username' => $event['username'],
				'poster_id'     => isset($data['poster_id']) ? (int) $data['poster_id'] : (int) $this->user->data['user_id'],
				'post_text'     => $post_text,
				'post_time'     => isset($data['post_time']) ? $data['post_time'] : time(),
				'post_subject'  => $event['subject'],
			]);

			$types = ['booskit.mention.notification.type.mention'];
			if (!$this->mention_manager || $this->mention_manager->is_group_mention_enabled())
			{
				$types[] = 'booskit.mention.notification.type.group_mention';
			}

			$this->notification_manager->add_notifications($types, $notification_data);
		}
	}

	/**
	 * Send notification when an unapproved post is approved in moderation queue
	 *
	 * @param \phpbb\event\data $event
	 */
	public function approve_posts_after($event)
	{
		if (isset($event['action']) && $event['action'] === 'approve' && !empty($event['post_info']))
		{
			$types = ['booskit.mention.notification.type.mention'];
			if (!$this->mention_manager || $this->mention_manager->is_group_mention_enabled())
			{
				$types[] = 'booskit.mention.notification.type.group_mention';
			}

			foreach ($event['post_info'] as $post_data)
			{
				$this->notification_manager->add_notifications($types, $post_data);
			}
		}
	}
}
