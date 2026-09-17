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
	'ACP_BOOSKIT_DASHBOARD_TITLE'           => 'Dashboard',
	'ACP_BOOSKIT_DASHBOARD_SETTINGS'        => 'Settings',
	'DASHBOARD_GENERAL_SETTINGS'            => 'General Settings',
	'DASHBOARD_ENABLED'                     => 'Enable Dashboard',
	'DASHBOARD_ALLOWED_GROUPS'              => 'Allowed Dashboard Groups',
	'DASHBOARD_ALLOWED_GROUPS_EXPLAIN'      => 'Comma-separated list of group IDs permitted to access the dashboard. Leave empty to allow all registered users.',
	'DASHBOARD_SETTINGS_SAVED'              => 'Dashboard settings have been successfully updated.',

	'DASHBOARD_MODULE_SETTINGS'             => 'Extension Integration Modules',
	'DASHBOARD_INCLUDE_AWARDS'              => 'Include Awards',
	'DASHBOARD_INCLUDE_CAREER'              => 'Include Career Timeline',
	'DASHBOARD_INCLUDE_COMMENDATIONS'       => 'Include Commendations',
	'DASHBOARD_INCLUDE_DISCIPLINARY'        => 'Include Disciplinary (OOC)',
	'DASHBOARD_INCLUDE_IC_DISCIPLINARY'     => 'Include IC Disciplinary',

	'DASHBOARD_PROFILE_ACCESS_SETTINGS'     => 'Group-to-Group Profile Access',
	'DASHBOARD_PROFILE_ACCESS_DESC'         => 'Define which user groups are allowed to view the dashboard profiles of other groups.',
	'DASHBOARD_ADMIN_OVERRIDE'              => 'Administrators Full Profile Access',
	'DASHBOARD_ADMIN_OVERRIDE_EXPLAIN'      => 'When enabled, administrators (users with admin permission) can view any user’s dashboard profile regardless of group restrictions.',
	'DASHBOARD_GROUP_PROFILE_ACCESS'        => 'Profile Access Rules',
	'DASHBOARD_GROUP_PROFILE_ACCESS_EXPLAIN'=> 'One rule per line formatted as <code>ViewerGroupID:TargetGroupID,TargetGroupID</code>. For example: <code>5:2,4,5</code> allows members of group 5 to view profiles of users in groups 2, 4, and 5.',

	'DASHBOARD_ISSUED_ACTIONS_SETTINGS'     => 'Issued Actions Permissions',
	'DASHBOARD_ISSUED_ACTIONS_DESC'         => 'Control who can view what a user has ISSUED on their profile (e.g. disciplinary actions, commendations, awards).',
	'DASHBOARD_ISSUED_GROUPS'               => 'Groups with Issued Actions Access',
	'DASHBOARD_ISSUED_GROUPS_EXPLAIN'       => 'Comma-separated group IDs allowed to view the "Issued Actions" section on user profiles. Leave empty to restrict to administrators.',
	'DASHBOARD_ISSUED_ALLOW_SELF'           => 'Allow Users to View Their Own Issued Actions',
	'DASHBOARD_ISSUED_ALLOW_SELF_EXPLAIN'   => 'If enabled, users can view what they have issued when viewing their own profile in the dashboard.',

	'DASHBOARD_RECENT_TOPICS_SETTINGS'      => 'Recently Visited Topics Permissions',
	'DASHBOARD_RECENT_TOPICS_DESC'          => 'Control who can view recent topics visited and viewed by a user.',
	'DASHBOARD_RECENT_TOPICS_GROUPS'        => 'Groups with Recent Topics Access',
	'DASHBOARD_RECENT_TOPICS_GROUPS_EXPLAIN'=> 'Comma-separated group IDs allowed to view recent visited topics on other users’ profiles. Leave empty to restrict to administrators.',
	'DASHBOARD_RECENT_TOPICS_ALLOW_SELF'    => 'Allow Users to View Their Own Recent Topics',
	'DASHBOARD_RECENT_TOPICS_ALLOW_SELF_EXPLAIN' => 'If enabled, users can see the list of recent topics they personally visited on their dashboard profile.',
));
