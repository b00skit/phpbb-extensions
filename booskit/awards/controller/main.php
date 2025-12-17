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
	protected $log;
	protected $award_manager;
	protected $root_path;
	protected $php_ext;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth, \phpbb\log\log_interface $log, \booskit\awards\service\award_manager $award_manager, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->auth = $auth;
		$this->log = $log;
		$this->award_manager = $award_manager;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	public function add_award($user_id)
	{
		$issuer_level = $this->award_manager->get_user_role_level($this->user->data['user_id']);
		// Permission check: Level > 0
		if ($issuer_level < 1)
		{
			trigger_error('NOT_AUTHORISED');
		}

		$target_level = $this->award_manager->get_user_role_level($user_id);

		// Logic:
		// L1 (1) -> Target < 1 (0)
		// L2 (2) -> Target < 2 (0, 1)
		// Full (3) -> Everyone
		if ($issuer_level < 3 && $target_level >= $issuer_level)
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

			// Parse BBCode
			$uid = $bitfield = $options = '';
			$allow_bbcode = $allow_urls = $allow_smilies = true;
			generate_text_for_storage($comment, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

			$award_id = $this->award_manager->add_award($user_id, $award_def_id, $issue_date, $comment, $this->user->data['user_id'], $uid, $bitfield, $options);

			$user_row = $this->award_manager->get_username_string($user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_AWARD_ADDED', time(), array($user_row));

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
            'S_BBCODE_ALLOWED' => true,
			'S_BBCODE_QUOTE'   => true,
			'S_BBCODE_IMG'     => true,
			'S_LINKS_ALLOWED'  => true,
			'S_SMILIES_ALLOWED'=> true,
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

	public function remove_award($award_id)
	{
		$issuer_level = $this->award_manager->get_user_role_level($this->user->data['user_id']);
		// Permission check: Level >= 2
		if ($issuer_level < 2)
		{
			trigger_error('NOT_AUTHORISED');
		}

		$award = $this->award_manager->get_award($award_id);
		if (!$award)
		{
			trigger_error('NO_AWARD_SELECTED');
		}

		$target_user_id = $award['user_id'];
		$target_level = $this->award_manager->get_user_role_level($target_user_id);

		if ($issuer_level < 3 && $target_level >= $issuer_level)
		{
			trigger_error('NOT_AUTHORISED');
		}

		if (confirm_box(true))
		{
			$this->award_manager->remove_award($award_id);

			$user_row = $this->award_manager->get_username_string($target_user_id);
			$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_AWARD_REMOVED', time(), array($user_row));

			$u_profile = append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $target_user_id);
			meta_refresh(3, $u_profile);
			trigger_error($this->user->lang['AWARD_REMOVED'] . '<br><br>' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $u_profile . '">', '</a>'));
		}
		else
		{
			confirm_box(false, $this->user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
				'award_id' => $award_id,
			)));
		}
	}
}
