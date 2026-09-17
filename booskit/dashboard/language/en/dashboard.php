<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'DASHBOARD_TITLE'               => 'Command Center & Dashboard',
	'DASHBOARD_PAGE_SUBTITLE'       => 'Live forum activity, system statistics, and member oversight.',
	'DASHBOARD_USER_PROFILE'        => '%s’s Dashboard Profile',

	// Navigation & Search
	'DASHBOARD_SEARCH_USER'         => 'Jump to user profile...',
	'DASHBOARD_SEARCH_BUTTON'       => 'Search',
	'DASHBOARD_USER_NOT_FOUND'      => 'User was not found. Please verify the username and try again.',
	'DASHBOARD_BACK'                => 'Back to Dashboard',
	'DASHBOARD_VIEW_PROFILE'        => 'Open Profile',
	'DASHBOARD_MEMBERLIST_PROFILE'  => 'Standard Profile',
	'DASHBOARD_SEND_PM'             => 'Send Message',

	// Statistics
	'DASHBOARD_STAT_TOTAL_MEMBERS'  => 'Total Members',
	'DASHBOARD_STAT_ONLINE_NOW'     => 'Online Now',
	'DASHBOARD_STAT_ACTIVE_TODAY'   => 'Active Today',
	'DASHBOARD_STAT_TOPICS_POSTS'   => 'Topics & Posts',
	'DASHBOARD_STAT_NEWEST_MEMBER'  => 'Newest Member',
	'DASHBOARD_STAT_GUESTS'         => 'guests',
	'DASHBOARD_STAT_MEMBERS'        => 'members',

	// Active users section
	'DASHBOARD_ACTIVE_USERS_TITLE'  => 'Currently Active Users & Live Browsing',
	'DASHBOARD_NO_ACTIVE_USERS'     => 'No users are currently active in this session window.',
	'DASHBOARD_ONLINE_PULSE'        => 'Online',
	'DASHBOARD_BROWSING_LOCATION'   => 'Current Activity',

	// Hot Topics
	'DASHBOARD_HOT_TOPICS_TITLE'    => 'Trending & Hot Topics',
	'DASHBOARD_PERIOD_DAY'          => 'Past 24 Hours',
	'DASHBOARD_PERIOD_WEEK'         => 'Past 7 Days',
	'DASHBOARD_PERIOD_MONTH'        => 'Past 30 Days',
	'DASHBOARD_NO_HOT_TOPICS'       => 'No trending topics found in this time period.',
	'DASHBOARD_NEW_POSTS_BADGE'     => '%d new posts',
	'DASHBOARD_REPLIES'             => 'Replies',
	'DASHBOARD_VIEWS'               => 'Views',
	'DASHBOARD_LAST_POST'           => 'Last Post',

	// Profile Tabs
	'DASHBOARD_TAB_DISCIPLINARY'    => 'Disciplinary (OOC)',
	'DASHBOARD_TAB_IC_DISCIPLINARY' => 'IC Disciplinary',
	'DASHBOARD_TAB_AWARDS'          => 'Awards & Badges',
	'DASHBOARD_TAB_CAREER'          => 'Career Timeline',
	'DASHBOARD_TAB_COMMENDATIONS'   => 'Commendations',
	'DASHBOARD_TAB_GTAW'            => 'GTAW Characters',
	'DASHBOARD_TAB_ISSUED'          => 'Actions Issued',
	'DASHBOARD_TAB_RECENT_TOPICS'   => 'Recent Topics Visited',

	// Profile Labels & Details
	'DASHBOARD_MEMBER_SINCE'        => 'Joined',
	'DASHBOARD_LAST_ACTIVE'         => 'Last Active',
	'DASHBOARD_TOTAL_POSTS'         => 'Forum Posts',
	'DASHBOARD_STATUS_ONLINE'       => 'Online Now',
	'DASHBOARD_STATUS_OFFLINE'      => 'Offline',
	'DASHBOARD_ISSUED_BY'           => 'Issued by',
	'DASHBOARD_RECIPIENT'           => 'Recipient',
	'DASHBOARD_DATE'                => 'Date',
	'DASHBOARD_TYPE'                => 'Type',
	'DASHBOARD_REASON'              => 'Details',
	'DASHBOARD_PRIVATE_NOTES'       => 'Private Notes / Evidence',
	'DASHBOARD_ARCHIVED_BADGE'      => 'Archived',
	'DASHBOARD_VIEW_ALL'            => 'View All',
	'DASHBOARD_NO_RECORDS'          => 'No records found for this user in this category.',

	// Issued sub-sections
	'DASHBOARD_ISSUED_OOC_TITLE'    => 'OOC Disciplinary Actions Issued',
	'DASHBOARD_ISSUED_IC_TITLE'     => 'IC Disciplinary Actions Issued',
	'DASHBOARD_ISSUED_COMM_TITLE'   => 'Commendations Issued',
	'DASHBOARD_ISSUED_AWARDS_TITLE' => 'Awards Issued',

	// Recent topics visited
	'DASHBOARD_VISITED_AT'          => 'Visited',
	'DASHBOARD_IN_FORUM'            => 'in',

	// Module Feeds
	'DASHBOARD_AWARDS_TITLE'        => 'Recent Awards',
	'DASHBOARD_CAREER_TITLE'        => 'Recent Career Updates',
	'DASHBOARD_COMMENDATIONS_TITLE' => 'Recent Commendations',
	'DASHBOARD_DISCIPLINARY_TITLE'  => 'Recent Disciplinary Actions (OOC)',
	'DASHBOARD_IC_DISCIPLINARY_TITLE'=> 'Recent IC Disciplinary Actions',
	'DASHBOARD_NO_DATA'             => 'No recent items to display.',

	// Logs
	'LOG_DASHBOARD_VIEWED'          => '<strong>Viewed Command Center Dashboard</strong>',
));
