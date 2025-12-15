<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\controller\acp;

class settings
{
	protected $config;
	protected $request;
	protected $template;
	protected $user;
	protected $log;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\log\log $log)
	{
		$this->config = $config;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->log = $log;
	}

	public function handle($u_action)
	{
		$form_key = 'acp_booskit_awards';

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($u_action), E_USER_WARNING);
			}

			$json_url = $this->request->variable('booskit_awards_json_url', '');
			$access_l1 = $this->request->variable('booskit_awards_access_l1', '');
			$access_l2 = $this->request->variable('booskit_awards_access_l2', '');
			$access_full = $this->request->variable('booskit_awards_access_full', '');

			$this->config->set('booskit_awards_json_url', $json_url);
			$this->config->set('booskit_awards_access_l1', $access_l1);
			$this->config->set('booskit_awards_access_l2', $access_l2);
			$this->config->set('booskit_awards_access_full', $access_full);

			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_AWARDS_SETTINGS_UPDATED');
			trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
		}

		$this->template->assign_vars(array(
			'U_ACTION' => $u_action,
			'BOOSKIT_AWARDS_JSON_URL' => $this->config['booskit_awards_json_url'],
			'BOOSKIT_AWARDS_ACCESS_L1' => isset($this->config['booskit_awards_access_l1']) ? $this->config['booskit_awards_access_l1'] : '',
			'BOOSKIT_AWARDS_ACCESS_L2' => isset($this->config['booskit_awards_access_l2']) ? $this->config['booskit_awards_access_l2'] : '',
			'BOOSKIT_AWARDS_ACCESS_FULL' => isset($this->config['booskit_awards_access_full']) ? $this->config['booskit_awards_access_full'] : '',
		));
	}
}
