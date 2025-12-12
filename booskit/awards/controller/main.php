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
	protected $award_manager;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \booskit\awards\service\award_manager $award_manager)
	{
		$this->config = $config;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->award_manager = $award_manager;
	}

	public function add_award($user_id)
	{
		// Permission check: m_ or a_
		$auth = $this->user->get_auth();
		if (!$auth->acl_get('m_') && !$auth->acl_get('a_'))
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

			meta_refresh(3, $this->helper->route('phpbb_memberlist_view_profile', array('u' => $user_id)));
			trigger_error($this->user->lang['AWARD_ADDED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $this->helper->route('phpbb_memberlist_view_profile', array('u' => $user_id)) . '">', '</a>'));
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
