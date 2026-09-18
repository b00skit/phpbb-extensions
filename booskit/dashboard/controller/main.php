<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

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

		$viewer_id = (int) $this->user->data['user_id'];
		$perms = $this->dashboard_manager->get_effective_permissions($viewer_id);

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
				return new RedirectResponse($this->helper->route('booskit_dashboard_user_profile', ['user_id' => $target_id]));
			}
			else
			{
				$this->template->assign_var('S_SEARCH_USER_NOT_FOUND', true);
			}
		}

		$this->log->add('mod', $this->user->data['user_id'], $this->user->ip, 'LOG_DASHBOARD_VIEWED', time());

		// Overview statistics
		$stats = $this->dashboard_manager->get_overview_stats();
		$newest_user_string = '';
		if (!empty($stats['newest_user_id']))
		{
			$newest_user_string = get_username_string('full', $stats['newest_user_id'], $stats['newest_username'], $stats['newest_user_colour']);
		}

		$this->template->assign_vars([
			'STATS_GROUP_METRIC_VALUE' => $stats['group_metric_value'],
			'STATS_GROUP_METRIC_LABEL' => $stats['group_metric_label'],
			'STATS_TOTAL_ACTIONS'      => $stats['total_actions'],
			'STATS_TOTAL_USERS'        => $stats['total_users'],
			'STATS_ACTIVE_TODAY'       => $stats['active_today'],
			'STATS_ONLINE_REGISTERED'  => $stats['online_registered'],
			'STATS_ONLINE_GUESTS'      => $stats['online_guests'],
			'STATS_NEWEST_USER'        => $newest_user_string,

			'S_PERM_VIEW_STATS'        => !empty($perms['view_stats']),
			'S_PERM_VIEW_ACTIVE_USERS' => !empty($perms['view_active_users']),
			'S_PERM_VIEW_HOT_TOPICS'   => !empty($perms['view_hot_topics']),
			'S_PERM_VIEW_FEEDS'        => !empty($perms['view_feeds']),
			'S_PERM_SEARCH_USERS'      => !empty($perms['search_users']),
		]);

		// Active users currently browsing
		if (!empty($perms['view_active_users']))
		{
			$active_users = $this->dashboard_manager->get_active_users_browsing($viewer_id, 30);
			foreach ($active_users as $row)
			{
				$profile_url = $row['can_view_profile'] ? $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $row['user_id']]) : '';

				$this->template->assign_block_vars('active_users', [
					'USER_ID'          => $row['user_id'],
					'USERNAME'         => get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
					'USERNAME_PLAIN'   => $row['username'],
					'AVATAR'           => $row['avatar_html'],
					'TIME_AGO'         => $row['time_ago'],
					'BROWSING_LABEL'   => $row['browsing_label'],
					'BROWSING_URL'     => !empty($row['browsing_url']) ? append_sid($this->root_path . $row['browsing_url']) : '',
					'U_VIEW_PROFILE'   => $profile_url,
					'CAN_VIEW_PROFILE' => $row['can_view_profile'],
				]);
			}
		}

		// Hot topics (period filter: day, week, month)
		if (!empty($perms['view_hot_topics']))
		{
			$period = $this->request->variable('period', 'day');
			if (!in_array($period, ['day', 'week', 'month'], true))
			{
				$period = 'day';
			}
			$hot_topics = $this->dashboard_manager->get_hot_topics($viewer_id, $period, 10);
			foreach ($hot_topics as $topic)
			{
				$this->template->assign_block_vars('hot_topics', [
					'TOPIC_ID'       => $topic['topic_id'],
					'TOPIC_TITLE'    => $topic['topic_title'],
					'FORUM_NAME'     => $topic['forum_name'],
					'POSTER'         => get_username_string('full', $topic['topic_poster'], $topic['topic_first_poster_name'], $topic['topic_first_poster_colour']),
					'PERIOD_POSTS'   => (int) $topic['period_posts'],
					'TOTAL_REPLIES'  => (int) $topic['topic_posts_approved'] > 0 ? ((int) $topic['topic_posts_approved'] - 1) : 0,
					'VIEWS'          => (int) $topic['topic_views'],
					'LAST_POST_TIME' => $this->user->format_date($topic['topic_last_post_time']),
					'LAST_POSTER'    => get_username_string('full', 0, $topic['topic_last_poster_name'], $topic['topic_last_poster_colour']),
					'U_VIEW_TOPIC'   => append_sid($this->root_path . 'viewtopic.' . $this->php_ext, 't=' . $topic['topic_id']),
					'U_VIEW_FORUM'   => append_sid($this->root_path . 'viewforum.' . $this->php_ext, 'f=' . $topic['forum_id']),
				]);
			}

			$this->template->assign_vars([
				'CURRENT_PERIOD' => $period,
				'U_PERIOD_DAY'   => $this->helper->route('booskit_dashboard_home', ['period' => 'day']),
				'U_PERIOD_WEEK'  => $this->helper->route('booskit_dashboard_home', ['period' => 'week']),
				'U_PERIOD_MONTH' => $this->helper->route('booskit_dashboard_home', ['period' => 'month']),
			]);
		}

		// Recent Dashboard Profiles Visited widget
		$recent_profiles = $this->dashboard_manager->get_recent_dashboard_profile_views($viewer_id, 10);
		foreach ($recent_profiles as $pv)
		{
			$this->template->assign_block_vars('recent_profile_views', [
				'VIEWER_NAME'     => get_username_string('full', $pv['viewer_user_id'], $pv['viewer_username'], $pv['viewer_colour']),
				'VIEWER_AVATAR'   => $pv['viewer_avatar_html'],
				'VIEWED_NAME'     => get_username_string('full', $pv['viewed_user_id'], $pv['viewed_username'], $pv['viewed_colour']),
				'VIEWED_AVATAR'   => $pv['viewed_avatar_html'],
				'TIME_AGO'        => $pv['time_ago'],
				'U_VIEW_PROFILE'  => $pv['can_view_target_profile'] ? $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $pv['viewed_user_id']]) : '',
				'CAN_VIEW_TARGET' => $pv['can_view_target_profile'],
			]);
		}

		// Extension Feeds
		if (!empty($perms['view_feeds']))
		{
			$this->assign_feed_blocks($viewer_id, $perms);
		}

		return $this->helper->render('dashboard.html', $this->user->lang['DASHBOARD_TITLE']);
	}

	protected function get_safe_route($route_name, array $params = [])
	{
		try {
			return $this->helper->route($route_name, $params);
		} catch (\Exception $e) {
			return '';
		}
	}

	protected function format_text($text, $uid = '', $bitfield = '', $options = 7)
	{
		if (empty($text))
		{
			return '';
		}
		if (!function_exists('generate_text_for_display'))
		{
			include_once($this->root_path . 'includes/functions_content.' . $this->php_ext);
		}
		return generate_text_for_display($text, (string)$uid, (string)$bitfield, (int)$options);
	}

	protected function format_row_text($row, $field = 'reason', $prefix = '')
	{
		$text = isset($row[$field]) ? $row[$field] : '';
		if ($text === '')
		{
			return '';
		}

		$uid_key = $prefix ? "{$prefix}_bbcode_uid" : 'bbcode_uid';
		$bitfield_key = $prefix ? "{$prefix}_bbcode_bitfield" : 'bbcode_bitfield';
		$options_key = $prefix ? "{$prefix}_bbcode_options" : 'bbcode_options';

		$uid = isset($row[$uid_key]) ? $row[$uid_key] : (isset($row['bbcode_uid']) ? $row['bbcode_uid'] : '');
		$bitfield = isset($row[$bitfield_key]) ? $row[$bitfield_key] : (isset($row['bbcode_bitfield']) ? $row['bbcode_bitfield'] : '');
		$options = isset($row[$options_key]) ? $row[$options_key] : (isset($row['bbcode_options']) ? $row['bbcode_options'] : 7);

		return $this->format_text($text, $uid, $bitfield, $options);
	}

	protected function assign_feed_blocks($viewer_id, array $perms = [])
	{
		// Disciplinary Feed
		if (!empty($perms['view_feed_disciplinary']) && $this->dashboard_manager->is_ext_enabled('booskit/disciplinary') && !empty($this->config['booskit_dashboard_include_disciplinary']))
		{
			$items = $this->dashboard_manager->get_latest_disciplinary($viewer_id, 6);
			$this->template->assign_vars([
				'S_SHOW_DISCIPLINARY'      => true,
				'U_VIEW_ALL_DISCIPLINARY'  => $this->get_safe_route('booskit_dashboard_view_all', ['module' => 'disciplinary']),
			]);
			foreach ($items as $item)
			{
				$is_archived = !empty($item['is_archived']);
				$this->template->assign_block_vars('disciplinary', [
					'TYPE'           => isset($item['type_name']) ? $item['type_name'] : '',
					'USERNAME'       => get_username_string('full', isset($item['user_id']) ? $item['user_id'] : 0, isset($item['username']) ? $item['username'] : '', isset($item['user_colour']) ? $item['user_colour'] : ''),
					'ISSUER'         => get_username_string('full', isset($item['issuer_user_id']) ? $item['issuer_user_id'] : 0, isset($item['issuer_name']) ? $item['issuer_name'] : '', isset($item['issuer_colour']) ? $item['issuer_colour'] : ''),
					'DATE'           => isset($item['issue_date']) ? $this->user->format_date($item['issue_date']) : '',
					'CONTENT'        => $this->truncate($this->format_row_text($item, 'reason', 'reason')),
					'IS_ARCHIVED'    => $is_archived,
					'ARCHIVE_REASON' => $is_archived && isset($item['archive_reason']) ? utf8_htmlspecialchars($item['archive_reason']) : '',
				]);
			}
		}

		// IC Disciplinary Feed
		if (!empty($perms['view_feed_ic_disciplinary']) && $this->dashboard_manager->is_ext_enabled('booskit/icdisciplinary') && !empty($this->config['booskit_dashboard_include_ic_disciplinary']))
		{
			$items = $this->dashboard_manager->get_latest_ic_disciplinary($viewer_id, 6);
			$this->template->assign_vars([
				'S_SHOW_IC_DISCIPLINARY'      => true,
				'U_VIEW_ALL_IC_DISCIPLINARY'  => $this->get_safe_route('booskit_dashboard_view_all', ['module' => 'ic_disciplinary']),
			]);
			foreach ($items as $item)
			{
				$is_archived = !empty($item['is_archived']);
				$this->template->assign_block_vars('ic_disciplinary', [
					'TYPE'           => isset($item['type_name']) ? $item['type_name'] : '',
					'CHARACTER'      => isset($item['character_name']) ? $item['character_name'] : '',
					'USERNAME'       => get_username_string('full', isset($item['user_id']) ? $item['user_id'] : 0, isset($item['username']) ? $item['username'] : '', isset($item['user_colour']) ? $item['user_colour'] : ''),
					'ISSUER'         => get_username_string('full', isset($item['issuer_user_id']) ? $item['issuer_user_id'] : 0, isset($item['issuer_name']) ? $item['issuer_name'] : '', isset($item['issuer_colour']) ? $item['issuer_colour'] : ''),
					'DATE'           => isset($item['issue_date']) ? $this->user->format_date($item['issue_date']) : '',
					'CONTENT'        => $this->truncate($this->format_row_text($item, 'reason', 'reason')),
					'IS_ARCHIVED'    => $is_archived,
					'ARCHIVE_REASON' => $is_archived && isset($item['archive_reason']) ? utf8_htmlspecialchars($item['archive_reason']) : '',
				]);
			}
		}

		// Awards Feed
		if (!empty($perms['view_feed_awards']) && $this->dashboard_manager->is_ext_enabled('booskit/awards') && !empty($this->config['booskit_dashboard_include_awards']))
		{
			$items = $this->dashboard_manager->get_latest_awards($viewer_id, 6);
			$this->template->assign_vars([
				'S_SHOW_AWARDS'      => true,
				'U_VIEW_ALL_AWARDS'  => $this->get_safe_route('booskit_dashboard_view_all', ['module' => 'awards']),
			]);
			foreach ($items as $item)
			{
				$this->template->assign_block_vars('awards', [
					'TYPE'     => isset($item['type_name']) ? $item['type_name'] : '',
					'USERNAME' => get_username_string('full', isset($item['user_id']) ? $item['user_id'] : 0, isset($item['username']) ? $item['username'] : '', isset($item['user_colour']) ? $item['user_colour'] : ''),
					'ISSUER'   => get_username_string('full', isset($item['issuer_user_id']) ? $item['issuer_user_id'] : 0, isset($item['issuer_name']) ? $item['issuer_name'] : '', isset($item['issuer_colour']) ? $item['issuer_colour'] : ''),
					'DATE'     => isset($item['issue_date']) ? $this->user->format_date($item['issue_date']) : '',
					'CONTENT'  => $this->truncate($this->format_row_text($item, 'comment')),
				]);
			}
		}

		// Career Feed
		if (!empty($perms['view_feed_career']) && $this->dashboard_manager->is_ext_enabled('booskit/usercareer') && !empty($this->config['booskit_dashboard_include_career']))
		{
			$items = $this->dashboard_manager->get_latest_career($viewer_id, 6);
			$this->template->assign_vars([
				'S_SHOW_CAREER'      => true,
				'U_VIEW_ALL_CAREER'  => $this->get_safe_route('booskit_dashboard_view_all', ['module' => 'career']),
			]);
			foreach ($items as $item)
			{
				$this->template->assign_block_vars('career', [
					'TYPE'     => isset($item['type_name']) ? $item['type_name'] : '',
					'USERNAME' => get_username_string('full', isset($item['user_id']) ? $item['user_id'] : 0, isset($item['username']) ? $item['username'] : '', isset($item['user_colour']) ? $item['user_colour'] : ''),
					'ISSUER'   => get_username_string('full', isset($item['issuer_user_id']) ? $item['issuer_user_id'] : 0, isset($item['issuer_name']) ? $item['issuer_name'] : '', isset($item['issuer_colour']) ? $item['issuer_colour'] : ''),
					'DATE'     => isset($item['note_date']) ? $this->user->format_date($item['note_date']) : '',
					'CONTENT'  => $this->truncate($this->format_row_text($item, 'description')),
				]);
			}
		}

		// Commendations Feed
		if (!empty($perms['view_feed_commendations']) && $this->dashboard_manager->is_ext_enabled('booskit/commendations') && !empty($this->config['booskit_dashboard_include_commendations']))
		{
			$items = $this->dashboard_manager->get_latest_commendations($viewer_id, 6);
			$this->template->assign_vars([
				'S_SHOW_COMMENDATIONS'      => true,
				'U_VIEW_ALL_COMMENDATIONS'  => $this->get_safe_route('booskit_dashboard_view_all', ['module' => 'commendations']),
			]);
			foreach ($items as $item)
			{
				$this->template->assign_block_vars('commendations', [
					'TYPE'     => isset($item['commendation_type']) ? $item['commendation_type'] : '',
					'USERNAME' => get_username_string('full', isset($item['user_id']) ? $item['user_id'] : 0, isset($item['username']) ? $item['username'] : '', isset($item['user_colour']) ? $item['user_colour'] : ''),
					'ISSUER'   => get_username_string('full', isset($item['issuer_user_id']) ? $item['issuer_user_id'] : 0, isset($item['issuer_name']) ? $item['issuer_name'] : '', isset($item['issuer_colour']) ? $item['issuer_colour'] : ''),
					'DATE'     => isset($item['commendation_date']) ? $this->user->format_date($item['commendation_date']) : '',
					'CONTENT'  => $this->truncate($this->format_row_text($item, 'reason')),
				]);
			}
		}
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

		// Log dashboard profile view
		if ($viewer_id !== $user_id)
		{
			$this->dashboard_manager->log_dashboard_profile_view($viewer_id, $user_id);
		}

		$profile = $this->dashboard_manager->get_user_profile_data($viewer_id, $user_id);
		if (!$profile)
		{
			trigger_error('DASHBOARD_USER_NOT_FOUND');
		}

		$perms = $this->dashboard_manager->get_effective_permissions($viewer_id, $user_id);
		$u = $profile['user'];
		$is_online = $this->dashboard_manager->is_user_online($user_id);

		// Permissions to issue cross-extension actions
		$can_issue_disc = $this->dashboard_manager->can_issue_disciplinary($viewer_id, $user_id);
		$can_issue_award = $this->dashboard_manager->can_issue_award($viewer_id, $user_id);
		$can_issue_career = $this->dashboard_manager->can_issue_career($viewer_id, $user_id);
		$can_issue_comm = $this->dashboard_manager->can_issue_commendation($viewer_id, $user_id);

		$u_issue_disc = $can_issue_disc ? $this->get_safe_route('booskit_disciplinary_add_record', ['user_id' => $user_id]) : '';
		$u_issue_award = $can_issue_award ? $this->get_safe_route('booskit_awards_add_award', ['user_id' => $user_id]) : '';
		$u_issue_career = $can_issue_career ? $this->get_safe_route('booskit_usercareer_add_note', ['user_id' => $user_id]) : '';
		$u_issue_comm = $can_issue_comm ? $this->get_safe_route('booskit_commendations_add', ['user_id' => $user_id]) : '';

		$can_view_disc = !empty($perms['view_disciplinary']) && $this->dashboard_manager->is_ext_enabled('booskit/disciplinary') && !empty($this->config['booskit_dashboard_include_disciplinary']);
		$can_view_ic = !empty($perms['view_ic_disciplinary']) && $this->dashboard_manager->is_ext_enabled('booskit/icdisciplinary') && !empty($this->config['booskit_dashboard_include_ic_disciplinary']);
		$can_view_awards = !empty($perms['view_awards']) && $this->dashboard_manager->is_ext_enabled('booskit/awards') && !empty($this->config['booskit_dashboard_include_awards']);
		$can_view_career = !empty($perms['view_career']) && $this->dashboard_manager->is_ext_enabled('booskit/usercareer') && !empty($this->config['booskit_dashboard_include_career']);
		$can_view_comm = !empty($perms['view_commendations']) && $this->dashboard_manager->is_ext_enabled('booskit/commendations') && !empty($this->config['booskit_dashboard_include_commendations']);
		$can_view_gtaw = !empty($perms['view_gtaw']) && $this->dashboard_manager->is_ext_enabled('booskit/gtawtracker');

		// Pagination parameters for visited sections (30 per page)
		$limit = 30;
		$start_topics = $this->request->variable('start_topics', 0);
		$start_forums = $this->request->variable('start_forums', 0);
		$start_users = $this->request->variable('start_users', 0);
		$start_profiles = $this->request->variable('start_profiles', 0);

		// Visited Topics (paginated)
		$count_visited_topics = 0;
		if (!empty($profile['can_view_topics']))
		{
			$count_visited_topics = $this->dashboard_manager->get_user_visited_topics_count($viewer_id, $user_id);
			$visited_topics = $this->dashboard_manager->get_user_visited_topics($viewer_id, $user_id, $start_topics, $limit);

			foreach ($visited_topics as $top)
			{
				$this->template->assign_block_vars('profile_visited_topics', [
					'TOPIC_TITLE'  => $top['topic_title'],
					'FORUM_NAME'   => $top['forum_name'],
					'VIEW_TIME'    => $this->user->format_date($top['view_time']),
					'U_VIEW_TOPIC' => append_sid($this->root_path . 'viewtopic.' . $this->php_ext, 't=' . $top['topic_id']),
					'U_VIEW_FORUM' => append_sid($this->root_path . 'viewforum.' . $this->php_ext, 'f=' . $top['forum_id']),
				]);
			}

			$base_url_topics = $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $user_id]);
			$this->pagination->generate_template_pagination($base_url_topics, 'pagination_topics', 'start_topics', $count_visited_topics, $limit, $start_topics);
		}

		// Visited Forums (paginated)
		$count_visited_forums = 0;
		if (!empty($profile['can_view_visited_forums']))
		{
			$count_visited_forums = $this->dashboard_manager->get_user_visited_forums_count($viewer_id, $user_id);
			$visited_forums = $this->dashboard_manager->get_user_visited_forums($viewer_id, $user_id, $start_forums, $limit);

			foreach ($visited_forums as $forum)
			{
				$this->template->assign_block_vars('profile_visited_forums', [
					'FORUM_NAME'   => $forum['forum_name'],
					'FORUM_DESC'   => !empty($forum['forum_desc']) ? $this->truncate($forum['forum_desc']) : '',
					'VIEW_TIME'    => $this->user->format_date($forum['view_time']),
					'VIEW_COUNT'   => (int) $forum['view_count'],
					'U_VIEW_FORUM' => append_sid($this->root_path . 'viewforum.' . $this->php_ext, 'f=' . $forum['forum_id']),
				]);
			}

			$base_url_forums = $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $user_id]);
			$this->pagination->generate_template_pagination($base_url_forums, 'pagination_forums', 'start_forums', $count_visited_forums, $limit, $start_forums);
		}

		// Visited Users (paginated)
		$count_visited_users = 0;
		if (!empty($profile['can_view_visited_users']))
		{
			$count_visited_users = $this->dashboard_manager->get_user_visited_users_count($viewer_id, $user_id);
			$visited_users = $this->dashboard_manager->get_user_visited_users($viewer_id, $user_id, $start_users, $limit);

			foreach ($visited_users as $vu)
			{
				$this->template->assign_block_vars('profile_visited_users', [
					'USERNAME'       => get_username_string('full', $vu['user_id'], $vu['username'], $vu['user_colour']),
					'AVATAR'         => $vu['avatar_html'],
					'VIEW_TIME'      => $this->user->format_date($vu['view_time']),
					'VIEW_COUNT'     => (int) $vu['view_count'],
					'U_VIEW_PROFILE' => $vu['can_view_profile'] ? $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $vu['user_id']]) : '',
					'U_MEMBER_PROF'  => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $vu['user_id']),
				]);
			}

			$base_url_users = $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $user_id]);
			$this->pagination->generate_template_pagination($base_url_users, 'pagination_users', 'start_users', $count_visited_users, $limit, $start_users);
		}

		// Visited Dashboard Profiles (paginated)
		$count_visited_profiles = 0;
		if (!empty($profile['can_view_visited_profiles']))
		{
			$count_visited_profiles = $this->dashboard_manager->get_user_visited_profiles_count($viewer_id, $user_id);
			$visited_profiles = $this->dashboard_manager->get_user_visited_profiles($viewer_id, $user_id, $start_profiles, $limit);

			foreach ($visited_profiles as $vp)
			{
				$this->template->assign_block_vars('profile_visited_profiles', [
					'USERNAME'       => get_username_string('full', $vp['user_id'], $vp['username'], $vp['user_colour']),
					'AVATAR'         => $vp['avatar_html'],
					'VIEW_TIME'      => $this->user->format_date($vp['view_time']),
					'VIEW_COUNT'     => (int) $vp['view_count'],
					'U_VIEW_PROFILE' => $vp['can_view_profile'] ? $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $vp['user_id']]) : '',
				]);
			}

			$base_url_prof = $this->helper->route('booskit_dashboard_user_profile', ['user_id' => $user_id]);
			$this->pagination->generate_template_pagination($base_url_prof, 'pagination_profiles', 'start_profiles', $count_visited_profiles, $limit, $start_profiles);
		}

		$issued_total = count($profile['issued']['disciplinary']) + count($profile['issued']['ic_disciplinary']) + count($profile['issued']['commendations']) + count($profile['issued']['awards']);

		$this->template->assign_vars([
			'PROFILE_USER_ID'            => $user_id,
			'PROFILE_USERNAME'           => get_username_string('full', $user_id, $u['username'], $u['user_colour']),
			'PROFILE_AVATAR'             => $profile['avatar_html'],
			'PROFILE_GROUP_NAME'         => !empty($u['group_name']) ? $u['group_name'] : '',
			'PROFILE_JOINED'             => $this->user->format_date($u['user_regdate']),
			'PROFILE_LAST_ACTIVE'        => !empty($u['user_lastvisit']) ? $this->user->format_date($u['user_lastvisit']) : 'Never',
			'PROFILE_POSTS'              => (int) $u['user_posts'],
			'PROFILE_IS_ONLINE'          => $is_online,
			'U_MEMBERLIST_PROFILE'       => append_sid($this->root_path . 'memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . $user_id),
			'U_PM'                       => append_sid($this->root_path . 'ucp.' . $this->php_ext, 'i=pm&mode=compose&action=post&u=' . $user_id),
			'U_BACK_DASHBOARD'           => $this->helper->route('booskit_dashboard_home'),

			'S_CAN_VIEW_DISCIPLINARY'    => $can_view_disc,
			'S_CAN_VIEW_IC_DISCIPLINARY' => $can_view_ic,
			'S_CAN_VIEW_AWARDS'          => $can_view_awards,
			'S_CAN_VIEW_CAREER'          => $can_view_career,
			'S_CAN_VIEW_COMMENDATIONS'   => $can_view_comm,
			'S_CAN_VIEW_GTAW'            => $can_view_gtaw,

			'S_CAN_VIEW_ISSUED'          => !empty($profile['can_view_issued']),
			'S_CAN_VIEW_TOPICS'          => !empty($profile['can_view_topics']),
			'S_CAN_VIEW_VISITED_FORUMS'  => !empty($profile['can_view_visited_forums']),
			'S_CAN_VIEW_VISITED_USERS'   => !empty($profile['can_view_visited_users']),
			'S_CAN_VIEW_VISITED_PROFILES'=> !empty($profile['can_view_visited_profiles']),

			'S_CAN_ISSUE_DISCIPLINARY'   => $can_issue_disc,
			'U_ISSUE_DISCIPLINARY'       => $u_issue_disc,
			'S_CAN_ISSUE_AWARD'          => $can_issue_award,
			'U_ISSUE_AWARD'              => $u_issue_award,
			'S_CAN_ISSUE_CAREER'         => $can_issue_career,
			'U_ISSUE_CAREER'             => $u_issue_career,
			'S_CAN_ISSUE_COMMENDATION'   => $can_issue_comm,
			'U_ISSUE_COMMENDATION'       => $u_issue_comm,
			'S_CAN_ISSUE_ANY'            => ($can_issue_disc || $can_issue_award || $can_issue_career || $can_issue_comm),

			'COUNT_AWARDS'               => count($profile['awards']),
			'COUNT_CAREER'               => count($profile['career']),
			'COUNT_COMMENDATIONS'        => count($profile['commendations']),
			'COUNT_DISCIPLINARY'         => count($profile['disciplinary']),
			'COUNT_IC_DISCIPLINARY'      => count($profile['ic_disciplinary']),
			'COUNT_GTAW'                 => count($profile['gtaw_characters']),
			'COUNT_VISITED_TOPICS'       => $count_visited_topics,
			'COUNT_VISITED_FORUMS'       => $count_visited_forums,
			'COUNT_VISITED_USERS'        => $count_visited_users,
			'COUNT_VISITED_PROFILES'     => $count_visited_profiles,
			'COUNT_ISSUED_TOTAL'         => $issued_total,
		]);

		// Awards
		if ($can_view_awards)
		{
			foreach ($profile['awards'] as $row)
			{
				$this->template->assign_block_vars('profile_awards', [
					'TYPE'    => $row['type_name'],
					'CONTENT' => $this->format_row_text($row, 'comment'),
					'DATE'    => $this->user->format_date($row['issue_date']),
					'ISSUER'  => get_username_string('full', isset($row['issuer_user_id']) ? $row['issuer_user_id'] : 0, isset($row['issuer_name']) ? $row['issuer_name'] : '', isset($row['issuer_colour']) ? $row['issuer_colour'] : ''),
				]);
			}
		}

		// Career
		if ($can_view_career)
		{
			foreach ($profile['career'] as $row)
			{
				$this->template->assign_block_vars('profile_career', [
					'TYPE'    => $row['type_name'],
					'CONTENT' => $this->format_row_text($row, 'description'),
					'DATE'    => $this->user->format_date($row['note_date']),
					'ISSUER'  => get_username_string('full', isset($row['issuer_user_id']) ? $row['issuer_user_id'] : 0, isset($row['issuer_name']) ? $row['issuer_name'] : '', isset($row['issuer_colour']) ? $row['issuer_colour'] : ''),
				]);
			}
		}

		// Commendations
		if ($can_view_comm)
		{
			foreach ($profile['commendations'] as $row)
			{
				$this->template->assign_block_vars('profile_commendations', [
					'TYPE'    => isset($row['commendation_type']) ? $row['commendation_type'] : '',
					'CONTENT' => $this->format_row_text($row, 'reason'),
					'DATE'    => isset($row['commendation_date']) ? $this->user->format_date($row['commendation_date']) : '',
					'ISSUER'  => get_username_string('full', isset($row['issuer_user_id']) ? $row['issuer_user_id'] : 0, isset($row['issuer_name']) ? $row['issuer_name'] : '', isset($row['issuer_colour']) ? $row['issuer_colour'] : ''),
				]);
			}
		}

		// Disciplinary
		if ($can_view_disc)
		{
			foreach ($profile['disciplinary'] as $row)
			{
				$is_archived = !empty($row['is_archived']);
				$this->template->assign_block_vars('profile_disciplinary', [
					'TYPE'           => $row['type_name'],
					'CONTENT'        => $this->format_row_text($row, 'reason', 'reason'),
					'DATE'           => $this->user->format_date($row['issue_date']),
					'ISSUER'         => get_username_string('full', isset($row['issuer_user_id']) ? $row['issuer_user_id'] : 0, isset($row['issuer_name']) ? $row['issuer_name'] : '', isset($row['issuer_colour']) ? $row['issuer_colour'] : ''),
					'EVIDENCE'       => (!empty($row['evidence']) && !empty($row['can_view_evidence'])) ? $this->format_row_text($row, 'evidence', 'evidence') : '',
					'IS_ARCHIVED'    => $is_archived,
					'ARCHIVE_REASON' => $is_archived ? utf8_htmlspecialchars($row['archive_reason']) : '',
					'ARCHIVED_BY'    => ($is_archived && !empty($row['archived_by_user_id'])) ? get_username_string('full', $row['archived_by_user_id'], $row['archived_by_name'], $row['archived_by_colour']) : '',
					'ARCHIVE_DATE'   => ($is_archived && !empty($row['archive_date'])) ? $this->user->format_date($row['archive_date']) : '',
				]);
			}
		}

		// IC Disciplinary
		if ($can_view_ic)
		{
			foreach ($profile['ic_disciplinary'] as $row)
			{
				$is_archived = !empty($row['is_archived']);
				$this->template->assign_block_vars('profile_ic_disciplinary', [
					'TYPE'           => $row['type_name'],
					'CHARACTER'      => isset($row['character_name']) ? $row['character_name'] : '',
					'CONTENT'        => $this->format_row_text($row, 'reason', 'reason'),
					'DATE'           => $this->user->format_date($row['issue_date']),
					'ISSUER'         => get_username_string('full', isset($row['issuer_user_id']) ? $row['issuer_user_id'] : 0, isset($row['issuer_name']) ? $row['issuer_name'] : '', isset($row['issuer_colour']) ? $row['issuer_colour'] : ''),
					'EVIDENCE'       => (!empty($row['evidence']) && !empty($row['can_view_evidence'])) ? $this->format_row_text($row, 'evidence', 'evidence') : '',
					'IS_ARCHIVED'    => $is_archived,
					'ARCHIVE_REASON' => $is_archived ? utf8_htmlspecialchars($row['archive_reason']) : '',
					'ARCHIVED_BY'    => ($is_archived && !empty($row['archived_by_user_id'])) ? get_username_string('full', $row['archived_by_user_id'], $row['archived_by_name'], $row['archived_by_colour']) : '',
					'ARCHIVE_DATE'   => ($is_archived && !empty($row['archive_date'])) ? $this->user->format_date($row['archive_date']) : '',
				]);
			}
		}

		// GTAW Characters
		if ($can_view_gtaw)
		{
			foreach ($profile['gtaw_characters'] as $char)
			{
				$this->template->assign_block_vars('profile_gtaw_characters', [
					'CHARACTER_NAME' => isset($char['character_name']) ? $char['character_name'] : '',
					'FACTION_NAME'   => isset($char['faction_name']) ? $char['faction_name'] : '',
					'RANK_NAME'      => isset($char['rank_name']) ? $char['rank_name'] : '',
				]);
			}
		}

		// Issued Records (if permitted)
		if (!empty($profile['can_view_issued']))
		{
			// Disciplinary Issued (OOC)
			foreach ($profile['issued']['disciplinary'] as $row)
			{
				$this->template->assign_block_vars('issued_disciplinary', [
					'TYPE'      => isset($row['type_name']) ? $row['type_name'] : '',
					'RECIPIENT' => get_username_string('full', isset($row['user_id']) ? $row['user_id'] : 0, isset($row['username']) ? $row['username'] : '', isset($row['user_colour']) ? $row['user_colour'] : ''),
					'DATE'      => isset($row['issue_date']) ? $this->user->format_date($row['issue_date']) : '',
					'REASON'    => $this->format_row_text($row, 'reason', 'reason'),
				]);
			}

			// IC Disciplinary Issued
			foreach ($profile['issued']['ic_disciplinary'] as $row)
			{
				$this->template->assign_block_vars('issued_ic_disciplinary', [
					'TYPE'      => isset($row['type_name']) ? $row['type_name'] : '',
					'CHARACTER' => isset($row['character_name']) ? $row['character_name'] : '',
					'RECIPIENT' => get_username_string('full', isset($row['user_id']) ? $row['user_id'] : 0, isset($row['username']) ? $row['username'] : '', isset($row['user_colour']) ? $row['user_colour'] : ''),
					'DATE'      => isset($row['issue_date']) ? $this->user->format_date($row['issue_date']) : '',
					'REASON'    => $this->format_row_text($row, 'reason', 'reason'),
				]);
			}

			// Commendations Issued
			foreach ($profile['issued']['commendations'] as $row)
			{
				$this->template->assign_block_vars('issued_commendations', [
					'TYPE'      => isset($row['commendation_type']) ? $row['commendation_type'] : '',
					'RECIPIENT' => get_username_string('full', isset($row['user_id']) ? $row['user_id'] : 0, isset($row['username']) ? $row['username'] : '', isset($row['user_colour']) ? $row['user_colour'] : ''),
					'DATE'      => isset($row['commendation_date']) ? $this->user->format_date($row['commendation_date']) : '',
					'REASON'    => $this->format_row_text($row, 'reason'),
				]);
			}

			// Awards Issued
			foreach ($profile['issued']['awards'] as $row)
			{
				$this->template->assign_block_vars('issued_awards', [
					'TYPE'      => isset($row['type_name']) ? $row['type_name'] : '',
					'RECIPIENT' => get_username_string('full', isset($row['user_id']) ? $row['user_id'] : 0, isset($row['username']) ? $row['username'] : '', isset($row['user_colour']) ? $row['user_colour'] : ''),
					'DATE'      => isset($row['issue_date']) ? $this->user->format_date($row['issue_date']) : '',
					'COMMENT'   => $this->format_row_text($row, 'comment'),
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

		$viewer_id = (int) $this->user->data['user_id'];
		$perms = $this->dashboard_manager->get_effective_permissions($viewer_id);

		$module_perm_map = [
			'awards'          => 'view_feed_awards',
			'career'          => 'view_feed_career',
			'commendations'   => 'view_feed_commendations',
			'disciplinary'    => 'view_feed_disciplinary',
			'ic_disciplinary' => 'view_feed_ic_disciplinary',
		];

		if (!isset($module_perm_map[$module]) || empty($perms['view_feeds']) || empty($perms[$module_perm_map[$module]]))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$start = $this->request->variable('start', 0);
		$limit = 20;
		$total = 0;
		$title = '';

		switch ($module)
		{
			case 'awards':
				$total = $this->dashboard_manager->get_total_awards($viewer_id);
				$items = $this->dashboard_manager->get_latest_awards($viewer_id, $limit, $start);
				$title = $this->user->lang['DASHBOARD_AWARDS_TITLE'];
				foreach ($items as $row)
				{
					$this->template->assign_block_vars('items', [
						'TYPE'     => isset($row['type_name']) ? $row['type_name'] : '',
						'USERNAME' => get_username_string('full', isset($row['user_id']) ? $row['user_id'] : 0, isset($row['username']) ? $row['username'] : '', isset($row['user_colour']) ? $row['user_colour'] : ''),
						'ISSUER'   => get_username_string('full', isset($row['issuer_user_id']) ? $row['issuer_user_id'] : 0, isset($row['issuer_name']) ? $row['issuer_name'] : '', isset($row['issuer_colour']) ? $row['issuer_colour'] : ''),
						'DATE'     => isset($row['issue_date']) ? $this->user->format_date($row['issue_date']) : '',
						'CONTENT'  => $this->format_row_text($row, 'comment'),
					]);
				}
				break;

			case 'career':
				$total = $this->dashboard_manager->get_total_career($viewer_id);
				$items = $this->dashboard_manager->get_latest_career($viewer_id, $limit, $start);
				$title = $this->user->lang['DASHBOARD_CAREER_TITLE'];
				foreach ($items as $row)
				{
					$this->template->assign_block_vars('items', [
						'TYPE'     => isset($row['type_name']) ? $row['type_name'] : '',
						'USERNAME' => get_username_string('full', isset($row['user_id']) ? $row['user_id'] : 0, isset($row['username']) ? $row['username'] : '', isset($row['user_colour']) ? $row['user_colour'] : ''),
						'ISSUER'   => get_username_string('full', isset($row['issuer_user_id']) ? $row['issuer_user_id'] : 0, isset($row['issuer_name']) ? $row['issuer_name'] : '', isset($row['issuer_colour']) ? $row['issuer_colour'] : ''),
						'DATE'     => isset($row['note_date']) ? $this->user->format_date($row['note_date']) : '',
						'CONTENT'  => $this->format_row_text($row, 'description'),
					]);
				}
				break;

			case 'commendations':
				$total = $this->dashboard_manager->get_total_commendations($viewer_id);
				$items = $this->dashboard_manager->get_latest_commendations($viewer_id, $limit, $start);
				$title = $this->user->lang['DASHBOARD_COMMENDATIONS_TITLE'];
				foreach ($items as $row)
				{
					$this->template->assign_block_vars('items', [
						'TYPE'     => isset($row['commendation_type']) ? $row['commendation_type'] : '',
						'USERNAME' => get_username_string('full', isset($row['user_id']) ? $row['user_id'] : 0, isset($row['username']) ? $row['username'] : '', isset($row['user_colour']) ? $row['user_colour'] : ''),
						'ISSUER'   => get_username_string('full', isset($row['issuer_user_id']) ? $row['issuer_user_id'] : 0, isset($row['issuer_name']) ? $row['issuer_name'] : '', isset($row['issuer_colour']) ? $row['issuer_colour'] : ''),
						'DATE'     => isset($row['commendation_date']) ? $this->user->format_date($row['commendation_date']) : '',
						'CONTENT'  => $this->format_row_text($row, 'reason'),
					]);
				}
				break;

			case 'disciplinary':
				$total = $this->dashboard_manager->get_total_disciplinary($viewer_id);
				$items = $this->dashboard_manager->get_latest_disciplinary($viewer_id, $limit, $start);
				$title = $this->user->lang['DASHBOARD_DISCIPLINARY_TITLE'];
				foreach ($items as $row)
				{
					$is_archived = !empty($row['is_archived']);
					$this->template->assign_block_vars('items', [
						'TYPE'           => isset($row['type_name']) ? $row['type_name'] : '',
						'USERNAME'       => get_username_string('full', isset($row['user_id']) ? $row['user_id'] : 0, isset($row['username']) ? $row['username'] : '', isset($row['user_colour']) ? $row['user_colour'] : ''),
						'ISSUER'         => get_username_string('full', isset($row['issuer_user_id']) ? $row['issuer_user_id'] : 0, isset($row['issuer_name']) ? $row['issuer_name'] : '', isset($row['issuer_colour']) ? $row['issuer_colour'] : ''),
						'DATE'           => isset($row['issue_date']) ? $this->user->format_date($row['issue_date']) : '',
						'CONTENT'        => $this->format_row_text($row, 'reason', 'reason'),
						'EVIDENCE'       => (!empty($row['evidence']) && !empty($row['can_view_evidence'])) ? $this->format_row_text($row, 'evidence', 'evidence') : '',
						'IS_ARCHIVED'    => $is_archived,
						'ARCHIVE_REASON' => $is_archived && isset($row['archive_reason']) ? utf8_htmlspecialchars($row['archive_reason']) : '',
					]);
				}
				break;

			case 'ic_disciplinary':
				$total = $this->dashboard_manager->get_total_ic_disciplinary($viewer_id);
				$items = $this->dashboard_manager->get_latest_ic_disciplinary($viewer_id, $limit, $start);
				$title = $this->user->lang['DASHBOARD_IC_DISCIPLINARY_TITLE'];
				foreach ($items as $row)
				{
					$is_archived = !empty($row['is_archived']);
					$this->template->assign_block_vars('items', [
						'TYPE'           => (isset($row['type_name']) ? $row['type_name'] : '') . (isset($row['character_name']) ? ' (' . $row['character_name'] . ')' : ''),
						'USERNAME'       => get_username_string('full', isset($row['user_id']) ? $row['user_id'] : 0, isset($row['username']) ? $row['username'] : '', isset($row['user_colour']) ? $row['user_colour'] : ''),
						'ISSUER'         => get_username_string('full', isset($row['issuer_user_id']) ? $row['issuer_user_id'] : 0, isset($row['issuer_name']) ? $row['issuer_name'] : '', isset($row['issuer_colour']) ? $row['issuer_colour'] : ''),
						'DATE'           => isset($row['issue_date']) ? $this->user->format_date($row['issue_date']) : '',
						'CONTENT'        => $this->format_row_text($row, 'reason', 'reason'),
						'EVIDENCE'       => (!empty($row['evidence']) && !empty($row['can_view_evidence'])) ? $this->format_row_text($row, 'evidence', 'evidence') : '',
						'IS_ARCHIVED'    => $is_archived,
						'ARCHIVE_REASON' => $is_archived && isset($row['archive_reason']) ? utf8_htmlspecialchars($row['archive_reason']) : '',
					]);
				}
				break;

			default:
				trigger_error('NO_MODE');
		}

		$base_url = $this->helper->route('booskit_dashboard_view_all', ['module' => $module]);
		$this->pagination->generate_template_pagination($base_url, 'pagination', 'start', $total, $limit, $start);

		$this->template->assign_vars([
			'PAGE_TITLE'               => $title,
			'DASHBOARD_VIEW_ALL_TITLE' => $title,
			'U_BACK'                   => $this->helper->route('booskit_dashboard_home'),
			'U_BACK_DASHBOARD'         => $this->helper->route('booskit_dashboard_home'),
		]);

		return $this->helper->render('view_all.html', $title);
	}

	protected function truncate($text, $length = 120)
	{
		$clean = strip_tags((string)$text);
		if (mb_strlen($clean, 'UTF-8') <= $length)
		{
			return $clean;
		}
		return mb_substr($clean, 0, $length, 'UTF-8') . '...';
	}
}
