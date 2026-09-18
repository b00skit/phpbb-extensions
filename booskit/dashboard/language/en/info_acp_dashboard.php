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
	'DASHBOARD_ALLOWED_GROUPS'              => 'Allowed Dashboard Groups (Legacy)',
	'DASHBOARD_ALLOWED_GROUPS_EXPLAIN'      => 'Comma-separated list of group IDs permitted to access the dashboard. (Used when permission system is in Legacy mode).',
	'DASHBOARD_SETTINGS_SAVED'              => 'Dashboard settings have been successfully updated.',

	// Permission System Mode
	'BOOSKIT_DASHBOARD_PERM_SYSTEM'         => 'Permission System',
	'BOOSKIT_DASHBOARD_PERM_SYSTEM_EXPLAIN' => 'Choose between the advanced permission groups system or the legacy settings.',
	'BOOSKIT_DASHBOARD_PERM_SYSTEM_GROUPS'  => 'Advanced Permission Groups (Recommended)',
	'BOOSKIT_DASHBOARD_PERM_SYSTEM_LEGACY'  => 'Legacy Access Settings',

	// Group Metric
	'DASHBOARD_GROUP_METRIC_SETTINGS'       => 'Primary Group Metric Settings',
	'DASHBOARD_GROUP_METRIC_SETTINGS_DESC'  => 'Configure which group member count is highlighted on the primary dashboard card (replacing default Total Members).',
	'DASHBOARD_GROUP_METRIC_GROUP'          => 'Target Group',
	'DASHBOARD_GROUP_METRIC_GROUP_EXPLAIN'  => 'Select the group whose member count will be displayed in the overview card. Select "All Members" to show total board registrations.',
	'DASHBOARD_GROUP_METRIC_ALL_MEMBERS'    => 'All Members (Default)',
	'DASHBOARD_GROUP_METRIC_LABEL'          => 'Custom Metric Title / Label',
	'DASHBOARD_GROUP_METRIC_LABEL_EXPLAIN'  => 'Custom text to display beneath the calculated group count (e.g., "Active Officers", "Faction Members", "Staff"). Leave blank for default.',

	// Modules
	'DASHBOARD_MODULE_SETTINGS'             => 'Extension Integration Modules',
	'DASHBOARD_INCLUDE_AWARDS'              => 'Include Awards',
	'DASHBOARD_INCLUDE_CAREER'              => 'Include Career Timeline',
	'DASHBOARD_INCLUDE_COMMENDATIONS'       => 'Include Commendations',
	'DASHBOARD_INCLUDE_DISCIPLINARY'        => 'Include Disciplinary (OOC)',
	'DASHBOARD_INCLUDE_IC_DISCIPLINARY'     => 'Include IC Disciplinary',

	// Permission Groups UI
	'PERMISSION_GROUPS'                     => 'Dashboard Permission Groups',
	'PERMISSION_GROUPS_EXPLAIN'             => 'Define granular permission profiles for different user groups. Control which groups can access specific dashboard sections, member profiles, and tracking feeds.',
	'PERMISSION_GROUP_NAME'                 => 'Permission Group Name',
	'APPLIES_TO_GROUPS'                     => 'Applies to User Groups',
	'APPLIES_TO_GROUPS_EXPLAIN'             => 'Users belonging to any of the selected phpBB groups will inherit this permission set.',
	'POWER_OVER_GROUPS'                     => 'Power Over (Target Profiles)',
	'POWER_OVER_GROUPS_EXPLAIN'             => 'Controls which user profiles members of this permission group are allowed to view and inspect.',
	'ALL_GROUPS'                            => 'All Users / Groups',
	'SELF_GROUP'                            => 'Own Profile Only',
	'EXCLUDE_GROUPS'                        => 'Exclude Groups',
	'EXCLUDE_GROUPS_EXPLAIN'                => 'Target users in these groups are shielded and cannot be inspected by this permission group.',

	// Granular Permissions
	'DASHBOARD_PERMISSIONS_GENERAL'         => 'Dashboard General Access',
	'DASHBOARD_PERMISSIONS_FEEDS'           => 'Activity Feeds Permissions (When Feeds Enabled)',
	'DASHBOARD_PERMISSIONS_PROFILE'         => 'Profile & Tracking Permissions',
	'PERM_VIEW_DASHBOARD'                   => 'Access Dashboard',
	'PERM_VIEW_STATS'                       => 'View Statistics Cards',
	'PERM_VIEW_ACTIVE_USERS'                => 'View Live Active Browsing',
	'PERM_VIEW_HOT_TOPICS'                  => 'View Trending & Hot Topics',
	'PERM_VIEW_FEEDS'                       => 'View Activity Feeds (Master)',
	'PERM_VIEW_FEED_DISCIPLINARY'           => 'Disciplinary (OOC) Feed',
	'PERM_VIEW_FEED_IC_DISCIPLINARY'        => 'IC Disciplinary Feed',
	'PERM_VIEW_FEED_AWARDS'                 => 'Awards Feed',
	'PERM_VIEW_FEED_CAREER'                 => 'Career Timeline Feed',
	'PERM_VIEW_FEED_COMMENDATIONS'          => 'Commendations Feed',
	'PERM_SEARCH_USERS'                     => 'Search Users',
	'PERM_VIEW_PROFILE'                     => 'View Dashboard Profile',
	'PERM_VIEW_ISSUED'                      => 'View Issued Records',
	'PERM_VIEW_VISITED_TOPICS'              => 'View Topics Visited',
	'PERM_VIEW_VISITED_FORUMS'              => 'View Forums Visited',
	'PERM_VIEW_VISITED_USERS'               => 'View Users Visited',
	'PERM_VIEW_VISITED_PROFILES'            => 'View Profiles Visited',
	'PERM_VIEW_DISCIPLINARY'                => 'View Disciplinary (OOC)',
	'PERM_VIEW_IC_DISCIPLINARY'             => 'View IC Disciplinary',
	'PERM_VIEW_AWARDS'                      => 'View Awards',
	'PERM_VIEW_CAREER'                      => 'View Career Timeline',
	'PERM_VIEW_COMMENDATIONS'               => 'View Commendations',
	'PERM_VIEW_GTAW'                        => 'View GTAW Characters',
	'ADD_PERMISSION_GROUP'                  => 'Add New Permission Group',
	'NO_PERMISSION_GROUPS'                  => 'No permission groups defined yet.',

	// Legacy settings
	'DASHBOARD_PROFILE_ACCESS_SETTINGS'     => 'Legacy Group-to-Group Profile Access',
	'DASHBOARD_PROFILE_ACCESS_DESC'         => 'Define which user groups are allowed to view the dashboard profiles of other groups.',
	'DASHBOARD_ADMIN_OVERRIDE'              => 'Administrators Full Profile Access',
	'DASHBOARD_ADMIN_OVERRIDE_EXPLAIN'      => 'When enabled, administrators (users with admin permission) can view any user’s dashboard profile regardless of group restrictions.',
	'DASHBOARD_GROUP_PROFILE_ACCESS'        => 'Profile Access Rules',
	'DASHBOARD_GROUP_PROFILE_ACCESS_EXPLAIN'=> 'One rule per line formatted as <code>ViewerGroupID:TargetGroupID,TargetGroupID</code>. For example: <code>5:2,4,5</code> allows members of group 5 to view profiles of users in groups 2, 4, and 5.',

	'DASHBOARD_ISSUED_ACTIONS_SETTINGS'     => 'Legacy Issued Actions Permissions',
	'DASHBOARD_ISSUED_ACTIONS_DESC'         => 'Control who can view what a user has ISSUED on their profile.',
	'DASHBOARD_ISSUED_GROUPS'               => 'Groups with Issued Actions Access',
	'DASHBOARD_ISSUED_GROUPS_EXPLAIN'       => 'Comma-separated group IDs allowed to view the "Issued Actions" section on user profiles.',
	'DASHBOARD_ISSUED_ALLOW_SELF'           => 'Allow Users to View Their Own Issued Actions',
	'DASHBOARD_ISSUED_ALLOW_SELF_EXPLAIN'   => 'If enabled, users can view what they have issued when viewing their own profile in the dashboard.',

	'DASHBOARD_RECENT_TOPICS_SETTINGS'      => 'Legacy Recently Visited Topics Permissions',
	'DASHBOARD_RECENT_TOPICS_DESC'          => 'Control who can view recent topics visited and viewed by a user.',
	'DASHBOARD_RECENT_TOPICS_GROUPS'        => 'Groups with Recent Topics Access',
	'DASHBOARD_RECENT_TOPICS_GROUPS_EXPLAIN'=> 'Comma-separated group IDs allowed to view recent visited topics on other users’ profiles.',
	'DASHBOARD_RECENT_TOPICS_ALLOW_SELF'    => 'Allow Users to View Their Own Recent Topics',
	'DASHBOARD_RECENT_TOPICS_ALLOW_SELF_EXPLAIN' => 'If enabled, users can see the list of recent topics they personally visited on their dashboard profile.',
));
