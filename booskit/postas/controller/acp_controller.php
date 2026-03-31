<?php
/**
 *
 * Post As. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\postas\controller;

/**
 * Post As ACP controller.
 */
class acp_controller
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string Custom form action */
	protected $u_action;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\config\config		$config		Config object
	 * @param \phpbb\language\language	$language	Language object
	 * @param \phpbb\log\log			$log		Log object
	 * @param \phpbb\request\request	$request	Request object
	 * @param \phpbb\template\template	$template	Template object
	 * @param \phpbb\user				$user		User object
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\language\language $language, \phpbb\log\log $log, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user)
	{
		$this->config	= $config;
		$this->language	= $language;
		$this->log		= $log;
		$this->request	= $request;
		$this->template	= $template;
		$this->user		= $user;
	}

	/**
	 * Set page URL.
	 *
	 * @param string $u_action Custom form action
	 * @return void
	 */
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}

	/**
	 * Display the options a user can configure for this extension.
	 *
	 * @return void
	 */
	public function display_options()
	{
		// Add our extension language file
		$this->language->add_lang('acp_postas', 'booskit/postas');

		// Create a form key for preventing CSRF attacks
		add_form_key('postas_acp_config');

		// Create an array to collect errors that will be output to the user
		$errors = [];

		$postas_show_original = $this->request->variable('postas_show_original', (isset($this->config['postas_show_original'])) ? (int) $this->config['postas_show_original'] : 1);

		// Handle form submission
		if ($this->request->is_set_post('submit_config'))
		{
			if (!check_form_key('postas_acp_config'))
			{
				$errors[] = $this->language->lang('FORM_INVALID');
			}
			else
			{
				$this->config->set('postas_show_original', $postas_show_original);
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_POSTAS_CONFIG');
				trigger_error($this->language->lang('ACP_POSTAS_CONFIG_SAVED') . adm_back_link($this->u_action));
			}
		}

		// Assign template variables
		$this->template->assign_vars([
			'POSTAS_SHOW_ORIGINAL'		=> $postas_show_original,
			'U_ACTION'					=> $this->u_action,
			'ERROR_MSG'					=> !empty($errors) ? implode('<br />', $errors) : '',
		]);
	}
}
