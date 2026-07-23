<?php
/**
 *
 * Topic Log Views extension for phpBB.
 *
 * @copyright (c) 2026 Booskit
 * @license MIT
 *
 */

namespace booskit\topiclogviews\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\log\log_interface */
	protected $log;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var array Guard against duplicate logs within the same request */
	protected static $logged_topics = array();

	/**
	 * Constructor
	 */
	public function __construct(
		\phpbb\config\config $config,
		\phpbb\user $user,
		\phpbb\log\log_interface $log,
		\phpbb\request\request_interface $request,
		\phpbb\auth\auth $auth
	) {
		$this->config = $config;
		$this->user = $user;
		$this->log = $log;
		$this->request = $request;
		$this->auth = $auth;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents()
	{
		return array(
			'core.user_setup'                         => 'load_language_on_setup',
			'core.viewtopic_assign_template_vars_before' => 'log_topic_view',
		);
	}

	/**
	 * Load extension language files
	 *
	 * @param \phpbb\event\data $event
	 */
	public function load_language_on_setup($event)
	{
		$this->user->add_lang_ext('booskit/topiclogviews', array('common', 'info_acp_topiclogviews'));
	}

	/**
	 * Log topic view into moderator logs
	 *
	 * @param \phpbb\event\data $event
	 */
	public function log_topic_view($event)
	{
		// Check if extension is enabled
		if (empty($this->config['booskit_topiclogviews_enable']))
		{
			return;
		}

		// Exclude search engine bots if configured
		if (!empty($this->config['booskit_topiclogviews_exclude_bots']) && !empty($this->user->data['is_bot']))
		{
			return;
		}

		// Exclude guests if guest logging is disabled
		if (empty($this->config['booskit_topiclogviews_log_guests']) && $this->user->data['user_id'] == ANONYMOUS)
		{
			return;
		}

		$forum_id   = (int) $event['forum_id'];
		$topic_id   = (int) $event['topic_id'];
		$topic_data = $event['topic_data'];

		if (!$topic_id || !$forum_id)
		{
			return;
		}

		// If moderator-only logging is enabled, check permissions
		if (!empty($this->config['booskit_topiclogviews_mod_only']) && !$this->auth->acl_get('m_', $forum_id))
		{
			return;
		}

		// Guard against duplicate logs within the current request
		if (isset(self::$logged_topics[$topic_id]))
		{
			return;
		}

		// Deduplicate per session if configured
		if (!empty($this->config['booskit_topiclogviews_session_once']))
		{
			if (session_status() === PHP_SESSION_NONE && !headers_sent())
			{
				@session_start();
			}

			if (isset($_SESSION) && is_array($_SESSION))
			{
				if (isset($_SESSION['booskit_tlv_viewed'][$topic_id]))
				{
					return;
				}
				$_SESSION['booskit_tlv_viewed'][$topic_id] = time();
			}
		}

		self::$logged_topics[$topic_id] = true;

		$topic_title = isset($topic_data['topic_title']) ? $topic_data['topic_title'] : '';

		// Record the log entry under Moderator Logs (LOG_MOD)
		$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_TOPIC_VIEWED', time(), array(
			'forum_id' => $forum_id,
			'topic_id' => $topic_id,
			$topic_title,
		));
	}
}
