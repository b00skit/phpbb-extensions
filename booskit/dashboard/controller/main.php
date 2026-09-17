<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\controller;

class main
{
	protected $config;
	protected $template;
	protected $user;
	protected $helper;
	protected $request;
	protected $pagination;
	protected $dashboard_manager;
	protected $auth;
	protected $log;
	protected $root_path;
	protected $php_ext;
	protected $db;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\controller\helper $helper,
		\phpbb\request\request_interface $request,
		\phpbb\pagination $pagination,
		\booskit\dashboard\service\dashboard_manager $dashboard_manager,
		\phpbb\auth\auth $auth,
		\phpbb\log\log_interface $log,
		$root_path,
		$php_ext
	) {
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->request = $request;
		$this->pagination = $pagination;
		$this->dashboard_manager = $dashboard_manager;
		$this->auth = $auth;
		$this->log = $log;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;

		$this->db = $this->dashboard_manager->get_db();
	}

	protected function check_access()
	{
		$viewer_id = (int) $this->user->data['user_id'];
		if (!$this->dashboard_manager->can_view_dashboard($viewer_id))
		{
			trigger_error('NOT_AUTHORISED');
		}
	}

	public function dashboard()
	{
		$this->check_access();
		$this->user->add_lang_ext('booskit/dashboard', 'dashboard');

		// Handle jump to profile query
		$search_username = $this->request->variable('search_user', '', true);
		if (!empty($search_username))
		{
			$clean_name = utf8_clean_string($search_username);
			$sql = 'SELECT user_id FROM ' . USERS_TABLE . " WHERE username_clean = '" . $this->db->sql_escape($clean_name) . "'";
			$result = $this->db->sql_query($sql);
			$target_id = (int) $this->db->sql_fetchfield('user_id');
			$this->db->sql_freeresult($result);

			if ($target_id > 0)
			{
				return $this->helper->redirect($this->helper->route('booskit_dashboard_user_profile', ['user_id' => $target_id]));
			}
			else
			{
				$this->template->assign_var('S_SEARCH_USER_NOT_FOUND', true);
			}
		}

		$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_DASHBOARD_VIEWED', time());

		$viewer_id = (int) $this->user->data['user_id'];

		// Overview statistics
		$stats = $this->dashboard_manager->get_overview_stats();
		$newest_user_string = '';
		if (!empty($stats['newest_user_id']))
		{
			$newest_user_string = get_username_string('full', $stats['newest_user_id'], $stats['newest_username'], $stats['newest_user_colour']);
		}

		$this->template->assign_vars([
			'STATS_TOTAL_USERS'       => $stats['total_users'],
			'STATS_TOTAL_TOPICS'      => $stats['total_topics'],
			'STATS_TOTAL_POSTS'       => $stats['total_posts'],
			'STATS_ACTIVE_TODAY'      => $stats['active_today'],
			'STATS_ONLINE_REGISTERED' => $stats['online_registered'],
			'STATS_ONLINE_GUESTS'     => $stats['online_guests'],
			'STATS_NEWEST_USER'       => $newest_user_string,
		]);

		// Active users currently browsing
		$active_users = $this->dashboard_manager->get_active_users_browsing($viewer_id, 30);
		foreach ($active_users as $row)
		{
			$avatar_img = phpbb_get_user_avatar($row);
			$profile_url = $row['can_view_profile'] ? $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $row['user_id']]) : '';

			$this->template->assign_block_vars('active_users', [
				'USER_ID'          => $row['user_id'],
				'USERNAME'         => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'USERNAME_PLAIN'   => $row['username'],
				'AVATAR'           => !empty($avatar_img) ? $avatar_img : '<span class="default-avatar"><i class="icon fa-user fa-fw"></i></span>',
				'TIME_AGO'         => $row['time_ago'],
				'BROWSING_LABEL'   => $row['browsing_label'],
				'BROWSING_URL'     => !empty($row['browsing_url']) ? append_sid($this->root_path . $row['browsing_url']) : '',
				'U_VIEW_PROFILE'   => $profile_url,
				'CAN_VIEW_PROFILE' => $row['can_view_profile'],
			]);
		}

		// Hot topics (period filter: day, week, month)
		$period = $this->request->variable('period', 'day');
		if (!in_array($period, ['day', 'week', 'month'], true))
		{
			$period = 'day';
		}
		$hot_topics = $this->dashboard_manager->get_hot_topics($viewer_id, $period, 10);
		foreach ($hot_topics as $topic)
		{
			$this->template->assign_block_vars('hot_topics', [
				'TOPIC_ID'      => $topic['topic_id'],
				'TOPIC_TITLE'   => $topic['topic_title'],
				'FORUM_NAME'    => $topic['forum_name'],
				'POSTER'        => get_username_string('full', $topic['topic_poster'], $topic['topic_first_poster_name'], $topic['topic_first_poster_colour']),
				'PERIOD_POSTS'  => (int) $topic['period_posts'],
				'TOTAL_REPLIES' => (int) $topic['topic_posts_approved'] > 0 ? ((int) $topic['topic_posts_approved'] - 1) : 0,
				'VIEWS'         => (int) $topic['topic_views'],
				'LAST_POST_TIME'=> $this->user->format_date($topic['topic_last_post_time']),
				'LAST_POSTER'   => get_username_string('full', 0, $topic['topic_last_poster_name'], $topic['topic_last_poster_colour']),
				'U_VIEW_TOPIC'  => append_sid($this->root_path . 'viewtopic.' . $this->php_ext, 't=' . $topic['topic_id']),
				'U_VIEW_FORUM'  => append_sid($this->root_path . 'viewforum.' . $this->php_ext, 'f=' . $topic['forum_id']),
			]);
		}

		// Awards Feed
		$awards_defs = $this->dashboard_manager->get_definitions('booskit/awards');
		$awards = $this->dashboard_manager->get_latest_awards($viewer_id);
		foreach ($awards as $row)
		{
			$this->template->assign_block_vars('awards', [
				'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'ISSUER'   => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
				'DATE'     => $this->user->format_date($row['issue_date']),
				'TYPE'     => $this->dashboard_manager->get_definition_name('booskit/awards', $row['award_definition_id'], $awards_defs),
				'CONTENT'  => $this->truncate($row['comment']),
				'U_VIEW'   => $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $row['user_id']]),
			]);
		}

		// Career Feed
		$career_defs = $this->dashboard_manager->get_definitions('booskit/usercareer');
		$career = $this->dashboard_manager->get_latest_career($viewer_id);
		foreach ($career as $row)
		{
			$this->template->assign_block_vars('career', [
				'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'ISSUER'   => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
				'DATE'     => $this->user->format_date($row['note_date']),
				'TYPE'     => $this->dashboard_manager->get_definition_name('booskit/usercareer', $row['career_type_id'], $career_defs),
				'CONTENT'  => $this->truncate($row['description']),
				'U_VIEW'   => $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $row['user_id']]),
			]);
		}

		// Commendations Feed
		$commendations = $this->dashboard_manager->get_latest_commendations($viewer_id);
		foreach ($commendations as $row)
		{
			$this->template->assign_block_vars('commendations', [
				'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'ISSUER'   => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
				'DATE'     => $this->user->format_date($row['commendation_date']),
				'TYPE'     => $row['commendation_type'],
				'CONTENT'  => $this->truncate($row['reason']),
				'U_VIEW'   => $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $row['user_id']]),
			]);
		}

		// Disciplinary Feed
		$disc_defs = $this->dashboard_manager->get_definitions('booskit/disciplinary');
		$disciplinary = $this->dashboard_manager->get_latest_disciplinary($viewer_id);
		foreach ($disciplinary as $row)
		{
			$is_archived = !empty($row['is_archived']);
			$this->template->assign_block_vars('disciplinary', [
				'USERNAME'       => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'ISSUER'         => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
				'DATE'           => $this->user->format_date($row['issue_date']),
				'TYPE'           => $this->dashboard_manager->get_definition_name('booskit/disciplinary', $row['disciplinary_type_id'], $disc_defs),
				'CONTENT'        => $this->truncate($row['reason']),
				'IS_ARCHIVED'    => $is_archived,
				'ARCHIVE_REASON' => $is_archived ? utf8_htmlspecialchars($row['archive_reason']) : '',
				'U_VIEW'         => $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $row['user_id']]),
			]);
		}

		// IC Disciplinary Feed
		$ic_defs = $this->dashboard_manager->get_definitions('booskit/icdisciplinary');
		$ic_disciplinary = $this->dashboard_manager->get_latest_ic_disciplinary($viewer_id);
		foreach ($ic_disciplinary as $row)
		{
			$is_archived = !empty($row['is_archived']);
			$this->template->assign_block_vars('ic_disciplinary', [
				'USERNAME'       => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'CHARACTER'      => $row['character_name'],
				'ISSUER'         => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
				'DATE'           => $this->user->format_date($row['issue_date']),
				'TYPE'           => $this->dashboard_manager->get_definition_name('booskit/icdisciplinary', $row['disciplinary_type_id'], $ic_defs),
				'CONTENT'        => $this->truncate($row['reason']),
				'IS_ARCHIVED'    => $is_archived,
				'ARCHIVE_REASON' => $is_archived ? utf8_htmlspecialchars($row['archive_reason']) : '',
				'U_VIEW'         => $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $row['user_id']]),
			]);
		}

		$this->template->assign_vars([
			'CURRENT_PERIOD' => $period,
			'U_PERIOD_DAY'   => $this->helper->route('booskit_dashboard_hot_topics', ['period' => 'day']),
			'U_PERIOD_WEEK'  => $this->helper->route('booskit_dashboard_hot_topics', ['period' => 'week']),
			'U_PERIOD_MONTH' => $this->helper->route('booskit_dashboard_hot_topics', ['period' => 'month']),

			'S_SHOW_AWARDS'          => ($this->dashboard_manager->is_ext_enabled('booskit/awards') && !empty($this->config['booskit_dashboard_include_awards'])),
			'S_SHOW_CAREER'          => ($this->dashboard_manager->is_ext_enabled('booskit/usercareer') && !empty($this->config['booskit_dashboard_include_career'])),
			'S_SHOW_COMMENDATIONS'   => ($this->dashboard_manager->is_ext_enabled('booskit/commendations') && !empty($this->config['booskit_dashboard_include_commendations'])),
			'S_SHOW_DISCIPLINARY'    => ($this->dashboard_manager->is_ext_enabled('booskit/disciplinary') && !empty($this->config['booskit_dashboard_include_disciplinary'])),
			'S_SHOW_IC_DISCIPLINARY' => ($this->dashboard_manager->is_ext_enabled('booskit/icdisciplinary') && !empty($this->config['booskit_dashboard_include_ic_disciplinary'])),

			'U_VIEW_ALL_AWARDS'          => $this->helper->route('booskit_dashboard_view_list', ['module' => 'awards']),
			'U_VIEW_ALL_CAREER'          => $this->helper->route('booskit_dashboard_view_list', ['module' => 'career']),
			'U_VIEW_ALL_COMMENDATIONS'   => $this->helper->route('booskit_dashboard_view_list', ['module' => 'commendations']),
			'U_VIEW_ALL_DISCIPLINARY'    => $this->helper->route('booskit_dashboard_view_list', ['module' => 'disciplinary']),
			'U_VIEW_ALL_IC_DISCIPLINARY' => $this->helper->route('booskit_dashboard_view_list', ['module' => 'ic_disciplinary']),
		]);

		return $this->helper->render('dashboard.html', $this->user->lang['DASHBOARD_TITLE']);
	}

	public function hot_topics_view($period)
	{
		$this->request->overwrite('period', $period);
		return $this->dashboard();
	}

	public function user_profile($user_id)
	{
		$this->check_access();
		$this->user->add_lang_ext('booskit/dashboard', 'dashboard');

		$user_id = (int) $user_id;
		$viewer_id = (int) $this->user->data['user_id'];

		if (!$this->dashboard_manager->can_view_user_profile($viewer_id, $user_id))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$profile = $this->dashboard_manager->get_user_profile_data($viewer_id, $user_id);
		if (!$profile || empty($profile['user']))
		{
			trigger_error('NO_USER');
		}

		$u = $profile['user'];
		$avatar_img = phpbb_get_user_avatar($u);

		$is_online = false;
		$online_window = time() - ((int) $this->config['load_online_time'] * 60);
		$sql = 'SELECT session_time FROM ' . SESSIONS_TABLE . ' WHERE session_user_id = ' . $user_id . ' AND session_time >= ' . $online_window;
		$res = $this->db->sql_query($sql);
		if ($this->db->sql_fetchrow($res))
		{
			$is_online = true;
		}
		$this->db->sql_freeresult($res);

		// Assign Target User Core Data
		$this->template->assign_vars([
			'PROFILE_USER_ID'       => $user_id,
			'PROFILE_USERNAME'      => get_username_string('full', $user_id, $u['username'], $u['user_colour']),
			'PROFILE_USERNAME_PLAIN'=> $u['username'],
			'PROFILE_AVATAR'        => !empty($avatar_img) ? $avatar_img : '<span class="default-avatar-large"><i class="icon fa-user fa-fw"></i></span>',
			'PROFILE_GROUP_NAME'    => !empty($u['group_name']) ? $u['group_name'] : '',
			'PROFILE_JOINED'        => $this->user->format_date($u['user_regdate']),
			'PROFILE_LAST_ACTIVE'   => !empty($u['user_lastvisit']) ? $this->user->format_date($u['user_lastvisit']) : 'Never',
			'PROFILE_POSTS'         => (int) $u['user_posts'],
			'PROFILE_IS_ONLINE'     => $is_online,
			'U_MEMBERLIST_PROFILE'  => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id),
			'U_PM'                  => append_sid($this->root_path . 'ucp.' . $this->php_ext, 'i=pm&mode=compose&action=post&u=' . $user_id),
			'U_BACK_DASHBOARD'      => $this->helper->route('booskit_dashboard_home'),

			'S_CAN_VIEW_ISSUED'     => $profile['can_view_issued'],
			'S_CAN_VIEW_TOPICS'     => $profile['can_view_topics'],

			'COUNT_AWARDS'          => count($profile['awards']),
			'COUNT_CAREER'          => count($profile['career']),
			'COUNT_COMMENDATIONS'   => count($profile['commendations']),
			'COUNT_DISCIPLINARY'    => count($profile['disciplinary']),
			'COUNT_IC_DISCIPLINARY' => count($profile['ic_disciplinary']),
			'COUNT_GTAW'            => count($profile['gtaw_characters']),
			'COUNT_RECENT_TOPICS'   => count($profile['recent_topics']),
			'COUNT_ISSUED_TOTAL'    => count($profile['issued']['disciplinary']) + count($profile['issued']['ic_disciplinary']) + count($profile['issued']['commendations']) + count($profile['issued']['awards']),
		]);

		// Awards
		foreach ($profile['awards'] as $row)
		{
			$this->template->assign_block_vars('profile_awards', [
				'TYPE'    => $row['type_name'],
				'CONTENT' => !empty($row['comment']) ? $row['comment'] : '',
				'DATE'    => $this->user->format_date($row['issue_date']),
				'ISSUER'  => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
			]);
		}

		// Career
		foreach ($profile['career'] as $row)
		{
			$this->template->assign_block_vars('profile_career', [
				'TYPE'    => $row['type_name'],
				'CONTENT' => !empty($row['description']) ? $row['description'] : '',
				'DATE'    => $this->user->format_date($row['note_date']),
				'ISSUER'  => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
			]);
		}

		// Commendations
		foreach ($profile['commendations'] as $row)
		{
			$this->template->assign_block_vars('profile_commendations', [
				'TYPE'    => $row['commendation_type'],
				'CONTENT' => !empty($row['reason']) ? $row['reason'] : '',
				'DATE'    => $this->user->format_date($row['commendation_date']),
				'ISSUER'  => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
			]);
		}

		// Disciplinary
		foreach ($profile['disciplinary'] as $row)
		{
			$is_archived = !empty($row['is_archived']);
			$this->template->assign_block_vars('profile_disciplinary', [
				'TYPE'           => $row['type_name'],
				'CONTENT'        => $row['reason'],
				'DATE'           => $this->user->format_date($row['issue_date']),
				'ISSUER'         => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
				'EVIDENCE'       => (!empty($row['evidence']) && !empty($row['can_view_evidence'])) ? $row['evidence'] : '',
				'IS_ARCHIVED'    => $is_archived,
				'ARCHIVE_REASON' => $is_archived ? utf8_htmlspecialchars($row['archive_reason']) : '',
				'ARCHIVED_BY'    => ($is_archived && !empty($row['archived_by_user_id'])) ? get_username_string('full', $row['archived_by_user_id'], $row['archived_by_name'], $row['archived_by_colour']) : '',
				'ARCHIVE_DATE'   => ($is_archived && !empty($row['archive_date'])) ? $this->user->format_date($row['archive_date']) : '',
			]);
		}

		// IC Disciplinary
		foreach ($profile['ic_disciplinary'] as $row)
		{
			$is_archived = !empty($row['is_archived']);
			$this->template->assign_block_vars('profile_ic_disciplinary', [
				'TYPE'           => $row['type_name'],
				'CHARACTER'      => $row['character_name'],
				'CONTENT'        => $row['reason'],
				'DATE'           => $this->user->format_date($row['issue_date']),
				'ISSUER'         => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
				'EVIDENCE'       => (!empty($row['evidence']) && !empty($row['can_view_evidence'])) ? $row['evidence'] : '',
				'IS_ARCHIVED'    => $is_archived,
				'ARCHIVE_REASON' => $is_archived ? utf8_htmlspecialchars($row['archive_reason']) : '',
				'ARCHIVED_BY'    => ($is_archived && !empty($row['archived_by_user_id'])) ? get_username_string('full', $row['archived_by_user_id'], $row['archived_by_name'], $row['archived_by_colour']) : '',
				'ARCHIVE_DATE'   => ($is_archived && !empty($row['archive_date'])) ? $this->user->format_date($row['archive_date']) : '',
			]);
		}

		// GTAW Characters
		foreach ($profile['gtaw_characters'] as $char)
		{
			$this->template->assign_block_vars('profile_gtaw_characters', [
				'CHARACTER_NAME' => isset($char['character_name']) ? $char['character_name'] : '',
				'FACTION_NAME'   => isset($char['faction_name']) ? $char['faction_name'] : '',
				'RANK_NAME'      => isset($char['rank_name']) ? $char['rank_name'] : '',
			]);
		}

		// Recently visited topics
		if ($profile['can_view_topics'])
		{
			foreach ($profile['recent_topics'] as $top)
			{
				$this->template->assign_block_vars('profile_recent_topics', [
					'TOPIC_TITLE'  => $top['topic_title'],
					'FORUM_NAME'   => $top['forum_name'],
					'VIEW_TIME'    => $this->user->format_date($top['view_time']),
					'U_VIEW_TOPIC' => append_sid($this->root_path . 'viewtopic.' . $this->php_ext, 't=' . $top['topic_id']),
					'U_VIEW_FORUM' => append_sid($this->root_path . 'viewforum.' . $this->php_ext, 'f=' . $top['forum_id']),
				]);
			}
		}

		// Issued Records (if permitted)
		if ($profile['can_view_issued'])
		{
			foreach ($profile['issued']['disciplinary'] as $row)
			{
				$this->template->assign_block_vars('issued_disciplinary', [
					'RECIPIENT' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
					'TYPE'      => $row['type_name'],
					'REASON'    => $this->truncate($row['reason']),
					'DATE'      => $this->user->format_date($row['issue_date']),
				]);
			}
			foreach ($profile['issued']['ic_disciplinary'] as $row)
			{
				$this->template->assign_block_vars('issued_ic_disciplinary', [
					'RECIPIENT' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
					'CHARACTER' => $row['character_name'],
					'TYPE'      => $row['type_name'],
					'REASON'    => $this->truncate($row['reason']),
					'DATE'      => $this->user->format_date($row['issue_date']),
				]);
			}
			foreach ($profile['issued']['commendations'] as $row)
			{
				$this->template->assign_block_vars('issued_commendations', [
					'RECIPIENT' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
					'TYPE'      => $row['commendation_type'],
					'REASON'    => $this->truncate($row['reason']),
					'DATE'      => $this->user->format_date($row['commendation_date']),
				]);
			}
			foreach ($profile['issued']['awards'] as $row)
			{
				$this->template->assign_block_vars('issued_awards', [
					'RECIPIENT' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
					'TYPE'      => $row['type_name'],
					'COMMENT'   => $this->truncate($row['comment']),
					'DATE'      => $this->user->format_date($row['issue_date']),
				]);
			}
		}

		$page_title = sprintf($this->user->lang['DASHBOARD_USER_PROFILE'], $u['username']);
		return $this->helper->render('user_profile.html', $page_title);
	}

	public function view_all($module)
	{
		$this->check_access();
		$this->user->add_lang_ext('booskit/dashboard', 'dashboard');

		$start = $this->request->variable('start', 0);
		$limit = 20;
		$total = 0;
		$title = '';
		$template_block = 'items';
		$viewer_id = (int) $this->user->data['user_id'];

		switch ($module)
		{
			case 'awards':
				$total = $this->dashboard_manager->get_total_awards($viewer_id);
				$items = $this->dashboard_manager->get_latest_awards($viewer_id, $limit, $start);
				$defs = $this->dashboard_manager->get_definitions('booskit/awards');
				$title = $this->user->lang['DASHBOARD_AWARDS_TITLE'];
				foreach ($items as $row)
				{
					$content = generate_text_for_display($row['comment'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']);
					$this->template->assign_block_vars($template_block, [
						'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'ISSUER'   => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
						'DATE'     => $this->user->format_date($row['issue_date']),
						'TYPE'     => $this->dashboard_manager->get_definition_name('booskit/awards', $row['award_definition_id'], $defs),
						'CONTENT'  => $content,
						'U_VIEW'   => $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $row['user_id']]),
					]);
				}
				break;

			case 'career':
				$total = $this->dashboard_manager->get_total_career($viewer_id);
				$items = $this->dashboard_manager->get_latest_career($viewer_id, $limit, $start);
				$defs = $this->dashboard_manager->get_definitions('booskit/usercareer');
				$title = $this->user->lang['DASHBOARD_CAREER_TITLE'];
				foreach ($items as $row)
				{
					$content = generate_text_for_display($row['description'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']);
					$this->template->assign_block_vars($template_block, [
						'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'ISSUER'   => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
						'DATE'     => $this->user->format_date($row['note_date']),
						'TYPE'     => $this->dashboard_manager->get_definition_name('booskit/usercareer', $row['career_type_id'], $defs),
						'CONTENT'  => $content,
						'U_VIEW'   => $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $row['user_id']]),
					]);
				}
				break;

			case 'commendations':
				$total = $this->dashboard_manager->get_total_commendations($viewer_id);
				$items = $this->dashboard_manager->get_latest_commendations($viewer_id, $limit, $start);
				$title = $this->user->lang['DASHBOARD_COMMENDATIONS_TITLE'];
				foreach ($items as $row)
				{
					$content = generate_text_for_display($row['reason'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['bbcode_options']);
					$this->template->assign_block_vars($template_block, [
						'USERNAME' => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'ISSUER'   => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
						'DATE'     => $this->user->format_date($row['commendation_date']),
						'TYPE'     => $row['commendation_type'],
						'CONTENT'  => $content,
						'U_VIEW'   => $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $row['user_id']]),
					]);
				}
				break;

			case 'disciplinary':
				$total = $this->dashboard_manager->get_total_disciplinary($viewer_id);
				$items = $this->dashboard_manager->get_latest_disciplinary($viewer_id, $limit, $start);
				$defs = $this->dashboard_manager->get_definitions('booskit/disciplinary');
				$title = $this->user->lang['DASHBOARD_DISCIPLINARY_TITLE'];
				foreach ($items as $row)
				{
					$is_archived = !empty($row['is_archived']);
					$content = generate_text_for_display($row['reason'], $row['reason_bbcode_uid'], $row['reason_bbcode_bitfield'], $row['reason_bbcode_options']);

					$evidence_html = '';
					if (!empty($row['evidence']) && $this->dashboard_manager->can_view_private_notes('disciplinary', $viewer_id, $row['user_id'], $row['disciplinary_type_id']))
					{
						$evidence_uid = isset($row['evidence_bbcode_uid']) ? $row['evidence_bbcode_uid'] : '';
						$evidence_bitfield = isset($row['evidence_bbcode_bitfield']) ? $row['evidence_bbcode_bitfield'] : '';
						$evidence_options = isset($row['evidence_bbcode_options']) ? $row['evidence_bbcode_options'] : 7;
						$evidence_html = generate_text_for_display($row['evidence'], $evidence_uid, $evidence_bitfield, $evidence_options);
					}

					$this->template->assign_block_vars($template_block, [
						'USERNAME'       => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'ISSUER'         => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
						'DATE'           => $this->user->format_date($row['issue_date']),
						'TYPE'           => $this->dashboard_manager->get_definition_name('booskit/disciplinary', $row['disciplinary_type_id'], $defs),
						'CONTENT'        => $content,
						'EVIDENCE'       => $evidence_html,
						'IS_ARCHIVED'    => $is_archived,
						'ARCHIVE_REASON' => $is_archived ? utf8_htmlspecialchars($row['archive_reason']) : '',
						'ARCHIVED_BY'    => ($is_archived && !empty($row['archived_by_user_id'])) ? get_username_string('full', $row['archived_by_user_id'], $row['archived_by_name'], $row['archived_by_colour']) : '',
						'ARCHIVE_DATE'   => ($is_archived && !empty($row['archive_date'])) ? $this->user->format_date($row['archive_date']) : '',
						'U_VIEW'         => $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $row['user_id']]),
					]);
				}
				break;

			case 'ic_disciplinary':
				$total = $this->dashboard_manager->get_total_ic_disciplinary($viewer_id);
				$items = $this->dashboard_manager->get_latest_ic_disciplinary($viewer_id, $limit, $start);
				$defs = $this->dashboard_manager->get_definitions('booskit/icdisciplinary');
				$title = $this->user->lang['DASHBOARD_IC_DISCIPLINARY_TITLE'];
				foreach ($items as $row)
				{
					$is_archived = !empty($row['is_archived']);
					$content = generate_text_for_display($row['reason'], $row['reason_bbcode_uid'], $row['reason_bbcode_bitfield'], $row['reason_bbcode_options']);

					$evidence_html = '';
					if (!empty($row['evidence']) && $this->dashboard_manager->can_view_private_notes('ic_disciplinary', $viewer_id, $row['user_id'], $row['disciplinary_type_id']))
					{
						$evidence_uid = isset($row['evidence_bbcode_uid']) ? $row['evidence_bbcode_uid'] : '';
						$evidence_bitfield = isset($row['evidence_bbcode_bitfield']) ? $row['evidence_bbcode_bitfield'] : '';
						$evidence_options = isset($row['evidence_bbcode_options']) ? $row['evidence_bbcode_options'] : 7;
						$evidence_html = generate_text_for_display($row['evidence'], $evidence_uid, $evidence_bitfield, $evidence_options);
					}

					$this->template->assign_block_vars($template_block, [
						'USERNAME'       => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
						'CHARACTER'      => $row['character_name'],
						'ISSUER'         => get_username_string('full', $row['issuer_user_id'], $row['issuer_name'], $row['issuer_colour']),
						'DATE'           => $this->user->format_date($row['issue_date']),
						'TYPE'           => $this->dashboard_manager->get_definition_name('booskit/icdisciplinary', $row['disciplinary_type_id'], $defs),
						'CONTENT'        => $content,
						'EVIDENCE'       => $evidence_html,
						'IS_ARCHIVED'    => $is_archived,
						'ARCHIVE_REASON' => $is_archived ? utf8_htmlspecialchars($row['archive_reason']) : '',
						'ARCHIVED_BY'    => ($is_archived && !empty($row['archived_by_user_id'])) ? get_username_string('full', $row['archived_by_user_id'], $row['archived_by_name'], $row['archived_by_colour']) : '',
						'ARCHIVE_DATE'   => ($is_archived && !empty($row['archive_date'])) ? $this->user->format_date($row['archive_date']) : '',
						'U_VIEW'         => $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $row['user_id']]),
					]);
				}
				break;
		}

		$base_url = $this->helper->route('booskit_dashboard_view_list', ['module' => $module]);
		$this->pagination->generate_template_pagination($base_url, 'pagination', 'start', $total, $limit, $start);

		$this->template->assign_vars([
			'DASHBOARD_VIEW_ALL_TITLE' => $title,
			'S_IC_DISCIPLINARY'        => ($module === 'ic_disciplinary'),
			'U_BACK'                   => $this->helper->route('booskit_dashboard_home'),
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
