<?php
/**
 *
 * Topic Log Views extension for phpBB.
 *
 * @copyright (c) 2026 Booskit
 * @license MIT
 *
 */

namespace booskit\topiclogviews\controller\acp;

class settings
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\log\log */
	protected $log;

	/**
	 * Constructor
	 */
	public function __construct(
		\phpbb\config\config $config,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\log\log $log
	) {
		$this->config   = $config;
		$this->request  = $request;
		$this->template = $template;
		$this->user     = $user;
		$this->log      = $log;
	}

	/**
	 * Handle ACP settings request
	 *
	 * @param string $u_action Form action URL
	 */
	public function handle($u_action)
	{
		$this->user->add_lang_ext('booskit/topiclogviews', 'info_acp_topiclogviews');

		add_form_key('acp_topiclogviews_settings');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('acp_topiclogviews_settings'))
			{
				trigger_error($this->user->lang('FORM_INVALID') . adm_back_link($u_action), E_USER_WARNING);
			}

			$enable       = $this->request->variable('booskit_topiclogviews_enable', 0);
			$log_guests   = $this->request->variable('booskit_topiclogviews_log_guests', 0);
			$exclude_bots = $this->request->variable('booskit_topiclogviews_exclude_bots', 0);
			$session_once = $this->request->variable('booskit_topiclogviews_session_once', 0);
			$mod_only     = $this->request->variable('booskit_topiclogviews_mod_only', 0);

			$this->config->set('booskit_topiclogviews_enable', $enable);
			$this->config->set('booskit_topiclogviews_log_guests', $log_guests);
			$this->config->set('booskit_topiclogviews_exclude_bots', $exclude_bots);
			$this->config->set('booskit_topiclogviews_session_once', $session_once);
			$this->config->set('booskit_topiclogviews_mod_only', $mod_only);

			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_TOPICLOGVIEWS_SETTINGS_SAVED');

			trigger_error($this->user->lang('ACP_TOPICLOGVIEWS_SAVED') . adm_back_link($u_action));
		}

		$this->template->assign_vars(array(
			'U_ACTION'                       => $u_action,
			'BOOSKIT_TOPICLOGVIEWS_ENABLE'       => !empty($this->config['booskit_topiclogviews_enable']),
			'BOOSKIT_TOPICLOGVIEWS_LOG_GUESTS'   => !empty($this->config['booskit_topiclogviews_log_guests']),
			'BOOSKIT_TOPICLOGVIEWS_EXCLUDE_BOTS' => !empty($this->config['booskit_topiclogviews_exclude_bots']),
			'BOOSKIT_TOPICLOGVIEWS_SESSION_ONCE' => !empty($this->config['booskit_topiclogviews_session_once']),
			'BOOSKIT_TOPICLOGVIEWS_MOD_ONLY'     => !empty($this->config['booskit_topiclogviews_mod_only']),
		));
	}
}
