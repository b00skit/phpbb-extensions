<?php
/**
 *
 * @package booskit/usercommandcenter
 * @license MIT
 *
 */

namespace booskit\usercommandcenter\controller;

class main
{
	protected $config;
	protected $template;
	protected $user;
	protected $helper;
	protected $request;
	protected $pagination;
	protected $ucc_manager;
	protected $php_ext;
	protected $root_path;

	protected $db;

	public function __construct(\phpbb\config\config $config, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \phpbb\request\request_interface $request, \phpbb\pagination $pagination, \booskit\usercommandcenter\service\ucc_manager $ucc_manager, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->request = $request;
		$this->pagination = $pagination;
		$this->ucc_manager = $ucc_manager;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;

		$this->db = $this->ucc_manager->get_db();
	}

	protected function check_access()
	{
		if (!$this->config['booskit_ucc_enabled'])
		{
			trigger_error('NOT_AUTHORISED');
		}

		$allowed_groups = $this->ucc_manager->get_allowed_groups();
		if (empty($allowed_groups))
		{
			return;
		}

		// Check user groups
		$user_groups = [];
		$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . (int) $this->user->data['user_id'] . ' AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		if (!array_intersect($user_groups, $allowed_groups))
		{
			trigger_error('NOT_AUTHORISED');
		}
	}

	public function dashboard()
	{
		$this->check_access();
		$this->user->add_lang_ext('booskit/usercommandcenter', 'ucc');

		// Awards
		$awards_defs = $this->ucc_manager->get_definitions('booskit/awards');
		$awards = $this->ucc_manager->get_latest_awards($this->user->data['user_id']);
		foreach ($awards as $row)
		{
			$this->template->assign_block_vars('awards', [
				'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'ISSUER' => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
				'DATE' => $this->user->format_date($row['issue_date']),
				'TYPE' => $this->ucc_manager->get_definition_name('booskit/awards', $row['award_definition_id'], $awards_defs),
				'CONTENT' => $this->truncate($row['comment']),
				'U_VIEW' => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $row['user_id']),
			]);
		}

		// Career
		$career_defs = $this->ucc_manager->get_definitions('booskit/usercareer');
		$career = $this->ucc_manager->get_latest_career($this->user->data['user_id']);
		foreach ($career as $row)
		{
			$this->template->assign_block_vars('career', [
				'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'ISSUER' => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
				'DATE' => $this->user->format_date($row['note_date']),
				'TYPE' => $this->ucc_manager->get_definition_name('booskit/usercareer', $row['career_type_id'], $career_defs),
				'CONTENT' => $this->truncate($row['description']),
				'U_VIEW' => $this->helper->route('booskit_usercareer_view_timeline', ['user_id' => $row['user_id']]),
			]);
		}

		// Commendations
		$commendations = $this->ucc_manager->get_latest_commendations($this->user->data['user_id']);
		foreach ($commendations as $row)
		{
			$this->template->assign_block_vars('commendations', [
				'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'ISSUER' => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
				'DATE' => $this->user->format_date($row['commendation_date']),
				'TYPE' => $row['commendation_type'],
				'CONTENT' => $this->truncate($row['reason']),
				'U_VIEW' => $this->helper->route('booskit_commendations_view_all', ['user_id' => $row['user_id']]),
			]);
		}

		// Disciplinary
		$disc_defs = $this->ucc_manager->get_definitions('booskit/disciplinary');
		$disciplinary = $this->ucc_manager->get_latest_disciplinary($this->user->data['user_id']);
		foreach ($disciplinary as $row)
		{
			$this->template->assign_block_vars('disciplinary', [
				'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'ISSUER' => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
				'DATE' => $this->user->format_date($row['issue_date']),
				'TYPE' => $this->ucc_manager->get_definition_name('booskit/disciplinary', $row['disciplinary_type_id'], $disc_defs),
				'CONTENT' => $this->truncate($row['reason']),
				'U_VIEW' => $this->helper->route('booskit_disciplinary_view_all', ['user_id' => $row['user_id']]),
			]);
		}

		// IC Disciplinary
		$ic_defs = $this->ucc_manager->get_definitions('booskit/icdisciplinary');
		$ic_disciplinary = $this->ucc_manager->get_latest_ic_disciplinary($this->user->data['user_id']);
		foreach ($ic_disciplinary as $row)
		{
			$this->template->assign_block_vars('ic_disciplinary', [
				'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'CHARACTER' => $row['character_name'],
				'ISSUER' => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
				'DATE' => $this->user->format_date($row['issue_date']),
				'TYPE' => $this->ucc_manager->get_definition_name('booskit/icdisciplinary', $row['disciplinary_type_id'], $ic_defs),
				'CONTENT' => $this->truncate($row['reason']),
				'U_VIEW' => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $row['user_id'] . '&character_id=' . $row['character_id']),
			]);
		}

		$this->template->assign_vars([
			'S_SHOW_AWARDS' => ($this->ucc_manager->is_ext_enabled('booskit/awards') && $this->config['booskit_ucc_include_awards']),
			'S_SHOW_CAREER' => ($this->ucc_manager->is_ext_enabled('booskit/usercareer') && $this->config['booskit_ucc_include_career']),
			'S_SHOW_COMMENDATIONS' => ($this->ucc_manager->is_ext_enabled('booskit/commendations') && $this->config['booskit_ucc_include_commendations']),
			'S_SHOW_DISCIPLINARY' => ($this->ucc_manager->is_ext_enabled('booskit/disciplinary') && $this->config['booskit_ucc_include_disciplinary']),
			'S_SHOW_IC_DISCIPLINARY' => ($this->ucc_manager->is_ext_enabled('booskit/icdisciplinary') && $this->config['booskit_ucc_include_ic_disciplinary']),

			'U_VIEW_ALL_AWARDS' => $this->helper->route('booskit_ucc_view_list', ['module' => 'awards']),
			'U_VIEW_ALL_CAREER' => $this->helper->route('booskit_ucc_view_list', ['module' => 'career']),
			'U_VIEW_ALL_COMMENDATIONS' => $this->helper->route('booskit_ucc_view_list', ['module' => 'commendations']),
			'U_VIEW_ALL_DISCIPLINARY' => $this->helper->route('booskit_ucc_view_list', ['module' => 'disciplinary']),
			'U_VIEW_ALL_IC_DISCIPLINARY' => $this->helper->route('booskit_ucc_view_list', ['module' => 'ic_disciplinary']),
		]);

		return $this->helper->render('dashboard.html', $this->user->lang['UCC_DASHBOARD']);
	}

	public function view_all($module)
	{
		$this->check_access();
		$this->user->add_lang_ext('booskit/usercommandcenter', 'ucc');

		$start = $this->request->variable('start', 0);
		$limit = 20;
		$total = 0;
		$items = [];
		$title = '';
		$template_block = 'items';

		switch ($module)
		{
			case 'awards':
				$total = $this->ucc_manager->get_total_awards($this->user->data['user_id']);
				$items = $this->ucc_manager->get_latest_awards($this->user->data['user_id'], $limit, $start);
				$defs = $this->ucc_manager->get_definitions('booskit/awards');
				$title = $this->user->lang['UCC_AWARDS_TITLE'];
				foreach ($items as $row)
				{
					$content = generate_text_for_display($row['comment'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']);
					$this->template->assign_block_vars($template_block, [
						'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'ISSUER' => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
						'DATE' => $this->user->format_date($row['issue_date']),
						'TYPE' => $this->ucc_manager->get_definition_name('booskit/awards', $row['award_definition_id'], $defs),
						'CONTENT' => $content,
						'U_VIEW' => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $row['user_id']),
					]);
				}
				break;
			case 'career':
				$total = $this->ucc_manager->get_total_career($this->user->data['user_id']);
				$items = $this->ucc_manager->get_latest_career($this->user->data['user_id'], $limit, $start);
				$defs = $this->ucc_manager->get_definitions('booskit/usercareer');
				$title = $this->user->lang['UCC_CAREER_TITLE'];
				foreach ($items as $row)
				{
					$content = generate_text_for_display($row['description'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']);
					$this->template->assign_block_vars($template_block, [
						'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'ISSUER' => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
						'DATE' => $this->user->format_date($row['note_date']),
						'TYPE' => $this->ucc_manager->get_definition_name('booskit/usercareer', $row['career_type_id'], $defs),
						'CONTENT' => $content,
						'U_VIEW' => $this->helper->route('booskit_usercareer_view_timeline', ['user_id' => $row['user_id']]),
					]);
				}
				break;
			case 'commendations':
				$total = $this->ucc_manager->get_total_commendations($this->user->data['user_id']);
				$items = $this->ucc_manager->get_latest_commendations($this->user->data['user_id'], $limit, $start);
				$title = $this->user->lang['UCC_COMMENDATIONS_TITLE'];
				foreach ($items as $row)
				{
					$content = generate_text_for_display($row['reason'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']);
					$this->template->assign_block_vars($template_block, [
						'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'ISSUER' => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
						'DATE' => $this->user->format_date($row['commendation_date']),
						'TYPE' => $row['commendation_type'],
						'CONTENT' => $content,
						'U_VIEW' => $this->helper->route('booskit_commendations_view_all', ['user_id' => $row['user_id']]),
					]);
				}
				break;
			case 'disciplinary':
				$total = $this->ucc_manager->get_total_disciplinary($this->user->data['user_id']);
				$items = $this->ucc_manager->get_latest_disciplinary($this->user->data['user_id'], $limit, $start);
				$defs = $this->ucc_manager->get_definitions('booskit/disciplinary');
				$title = $this->user->lang['UCC_DISCIPLINARY_TITLE'];
				foreach ($items as $row)
				{
					$content = generate_text_for_display($row['reason'], $row['reason_bbcode_uid'], $row['reason_bbcode_bitfield'], $row['reason_bbcode_options']);
					$this->template->assign_block_vars($template_block, [
						'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'ISSUER' => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
						'DATE' => $this->user->format_date($row['issue_date']),
						'TYPE' => $this->ucc_manager->get_definition_name('booskit/disciplinary', $row['disciplinary_type_id'], $defs),
						'CONTENT' => $content,
						'U_VIEW' => $this->helper->route('booskit_disciplinary_view_all', ['user_id' => $row['user_id']]),
					]);
				}
				break;
			case 'ic_disciplinary':
				$total = $this->ucc_manager->get_total_ic_disciplinary($this->user->data['user_id']);
				$items = $this->ucc_manager->get_latest_ic_disciplinary($this->user->data['user_id'], $limit, $start);
				$defs = $this->ucc_manager->get_definitions('booskit/icdisciplinary');
				$title = $this->user->lang['UCC_IC_DISCIPLINARY_TITLE'];
				foreach ($items as $row)
				{
					$content = generate_text_for_display($row['reason'], $row['reason_bbcode_uid'], $row['reason_bbcode_bitfield'], $row['reason_bbcode_options']);
					$this->template->assign_block_vars($template_block, [
						'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'CHARACTER' => $row['character_name'],
						'ISSUER' => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
						'DATE' => $this->user->format_date($row['issue_date']),
						'TYPE' => $this->ucc_manager->get_definition_name('booskit/icdisciplinary', $row['disciplinary_type_id'], $defs),
						'CONTENT' => $content,
						'U_VIEW' => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $row['user_id'] . '&character_id=' . $row['character_id']),
					]);
				}
				break;
		}

		$base_url = $this->helper->route('booskit_ucc_view_list', ['module' => $module]);
		$this->pagination->generate_template_pagination($base_url, 'pagination', 'start', $total, $limit, $start);

		$this->template->assign_vars([
			'UCC_VIEW_ALL_TITLE' => $title,
			'S_IC_DISCIPLINARY' => ($module === 'ic_disciplinary'),
			'U_BACK' => $this->helper->route('booskit_ucc_dashboard'),
		]);

		return $this->helper->render('view_all.html', $title);
	}

	protected function truncate($text, $limit = 100)
	{
		$text = strip_tags($text);
		if (mb_strlen($text) > $limit)
		{
			$text = mb_substr($text, 0, $limit) . '...';
		}
		return $text;
	}
}
