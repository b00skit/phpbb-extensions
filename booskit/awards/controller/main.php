<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\controller;

class main
{
	protected $config;
	protected $request;
	protected $template;
	protected $user;
	protected $helper;
	protected $auth;
	protected $award_manager;
	protected $root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth, \booskit\awards\service\award_manager $award_manager, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->auth = $auth;
		$this->award_manager = $award_manager;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	public function add_award($user_id)
	{
		// Permission check: m_ or a_
		if (!$this->auth->acl_get('m_') && !$this->auth->acl_get('a_'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$this->user->add_lang_ext('booskit/awards', 'awards');
		$this->user->add_lang('common');

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('add_award'))
			{
				trigger_error('FORM_INVALID');
			}

			$award_def_id = $this->request->variable('award_definition_id', '');
			$comment = $this->request->variable('comment', '', true);

			// Date handling
			$issue_date_raw = $this->request->variable('issue_date', '');
            // Simple date parsing assuming YYYY-MM-DD from html5 date input
            $issue_date = time();
            if (!empty($issue_date_raw))
            {
                $issue_date = strtotime($issue_date_raw);
            }

			if (empty($award_def_id))
			{
				trigger_error($this->user->lang['NO_AWARD_SELECTED'] . $this->helper->previous_route(), E_USER_WARNING);
			}

			$this->award_manager->add_award($user_id, $award_def_id, $issue_date, $comment, $this->user->data['user_id']);

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id);

			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['AWARD_ADDED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}

		$definitions = $this->award_manager->get_definitions();

		// Pass definitions to template as JSON for JS preview
		$this->template->assign_vars(array(
			'AWARDS_JSON' => json_encode($definitions),
			'U_ACTION' => $this->helper->route('booskit_awards_add_award', array('user_id' => $user_id)),
            'S_ISSUE_DATE' => date('Y-m-d'), // Default to today
		));

        foreach ($definitions as $def) {
            $this->template->assign_block_vars('awards', array(
                'ID' => $def['id'],
                'NAME' => $def['name']
            ));
        }

		add_form_key('add_award');

		return $this->helper->render('add_award.html', $this->user->lang['ADD_AWARD']);
	}
}
