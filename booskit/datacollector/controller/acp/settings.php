<?php

namespace booskit\datacollector\controller\acp;

class settings
{
	protected $config;
	protected $request;
	protected $template;
	protected $user;
	protected $log;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\log\log $log)
	{
		$this->config = $config;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->log = $log;
	}

	public function handle($u_action)
	{
		$form_key = 'acp_datacollector_settings';

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($this->user->lang('FORM_INVALID') . adm_back_link($u_action), E_USER_WARNING);
			}

			$this->config->set('booskit_datacollector_post_url', $this->request->variable('datacollector_post_url', ''));
			$this->config->set('booskit_datacollector_group_id', $this->request->variable('datacollector_group_id', 0));
			$this->config->set('booskit_datacollector_forum_id', $this->request->variable('datacollector_forum_id', 0));

			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_DATACOLLECTOR_SETTINGS_UPDATED');
			trigger_error($this->user->lang('CONFIG_UPDATED') . adm_back_link($u_action));
		}

		add_form_key($form_key);

		$this->template->assign_vars([
			'U_ACTION' => $u_action,
			'DATACOLLECTOR_POST_URL' => $this->config['booskit_datacollector_post_url'],
			'DATACOLLECTOR_GROUP_ID' => $this->config['booskit_datacollector_group_id'],
			'DATACOLLECTOR_FORUM_ID' => $this->config['booskit_datacollector_forum_id'],
		]);
	}
}
