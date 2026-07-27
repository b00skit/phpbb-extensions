<?php
/**
 *
 * @package booskit/disciplinary
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
	'ACP_BOOSKIT_DISCIPLINARY_TITLE'		=> 'User Disciplinary Actions',
	'ACP_BOOSKIT_DISCIPLINARY_SETTINGS'		=> 'Disciplinary Settings',

	'BOOSKIT_DISCIPLINARY_PERM_SYSTEM'			=> 'Permission System',
	'BOOSKIT_DISCIPLINARY_PERM_SYSTEM_EXPLAIN'	=> 'Choose between Legacy Access Levels (default) or New Permission Groups.',
	'BOOSKIT_DISCIPLINARY_PERM_SYSTEM_LEGACY'	=> 'Legacy (Access Levels & Group Mapping)',
	'BOOSKIT_DISCIPLINARY_PERM_SYSTEM_GROUPS'	=> 'New Permission Groups',

	'BOOSKIT_DISCIPLINARY_SOURCE' => 'Definitions Source',
	'BOOSKIT_DISCIPLINARY_SOURCE_EXPLAIN' => 'Choose whether to load disciplinary definitions from an external JSON URL or manage them locally.',
	'BOOSKIT_DISCIPLINARY_SOURCE_URL' => 'External JSON URL',
	'BOOSKIT_DISCIPLINARY_SOURCE_LOCAL' => 'Local Settings (Database)',

	'BOOSKIT_DISCIPLINARY_JSON_URL'			=> 'External JSON Definition URL',
	'BOOSKIT_DISCIPLINARY_JSON_URL_EXPLAIN'	=> 'URL to a JSON file containing disciplinary action definitions.',

	'BOOSKIT_DISCIPLINARY_ACCESS_L1'			=> 'Level 1 Access Groups',
	'BOOSKIT_DISCIPLINARY_ACCESS_L1_EXPLAIN'	=> 'Comma separated list of group IDs for Level 1 Access.',
	'BOOSKIT_DISCIPLINARY_ACCESS_L2'			=> 'Level 2 Access Groups',
	'BOOSKIT_DISCIPLINARY_ACCESS_L2_EXPLAIN'	=> 'Comma separated list of group IDs for Level 2 Access.',
	'BOOSKIT_DISCIPLINARY_ACCESS_L3'			=> 'Level 3 Access Groups',
	'BOOSKIT_DISCIPLINARY_ACCESS_L3_EXPLAIN'	=> 'Comma separated list of group IDs for Level 3 Access.',
	'BOOSKIT_DISCIPLINARY_ACCESS_FULL'			=> 'Full Access Groups',
	'BOOSKIT_DISCIPLINARY_ACCESS_FULL_EXPLAIN'	=> 'Comma separated list of group IDs for Full Access.',

	'RULES' => 'Ruleset',
	'RULES_EXPLAIN' => 'The message displayed at the top of the management form. BBCode is supported.',

	'BOOSKIT_DISCIPLINARY_LOCAL_DEFINITIONS' => 'Local Definitions',
	'BOOSKIT_DISCIPLINARY_LOCAL_DEFINITIONS_EXPLAIN' => 'Manage the disciplinary definitions here when "Local Settings" is selected.',

	'BOOSKIT_DISCIPLINARY_ACCESS_VIEW_LOCAL'			=> 'Self View Access (No Private Notes)',
	'BOOSKIT_DISCIPLINARY_ACCESS_VIEW_LOCAL_EXPLAIN'	=> 'Comma separated group IDs. Members can view their OWN disciplinary actions marked as "Locally Viewable" (private notes hidden).',
	'BOOSKIT_DISCIPLINARY_ACCESS_VIEW_EXEMPTED'			=> 'Self View Access (Full)',
	'BOOSKIT_DISCIPLINARY_ACCESS_VIEW_EXEMPTED_EXPLAIN'	=> 'Comma separated group IDs. Members can view their OWN disciplinary actions marked as "Locally Viewable" (private notes shown).',
	'BOOSKIT_DISCIPLINARY_ACCESS_VIEW_LIMITED'			=> 'Mapped Group View Access',
	'BOOSKIT_DISCIPLINARY_ACCESS_VIEW_LIMITED_EXPLAIN'	=> 'Comma separated group IDs. Members can view actions marked as "Globally Viewable" ONLY if the target user is in a group mapped to the viewer\'s group in the "Limited View Mapping" (private notes hidden).',
	'BOOSKIT_DISCIPLINARY_ACCESS_VIEW_GLOBAL'			=> 'Unrestricted View Access',
	'BOOSKIT_DISCIPLINARY_ACCESS_VIEW_GLOBAL_EXPLAIN'	=> 'Comma separated group IDs. Members can view ALL disciplinary actions for any user (private notes hidden).',
	'BOOSKIT_DISCIPLINARY_ACCESS_VIEW_LIMITED_MAP'			=> 'Mapped View Mapping',
	'BOOSKIT_DISCIPLINARY_ACCESS_VIEW_LIMITED_MAP_EXPLAIN'	=> 'Format: ViewerGroupID:TargetGroupID,TargetGroupID (one per line). Defines which target groups a "Mapped Group View Access" group is allowed to see. Example: 5:10,11 means Group 5 can see actions for members of Group 10 and 11.',

	'PERMISSION_GROUPS'					=> 'Permission Groups',
	'PERMISSION_GROUPS_EXPLAIN'			=> 'Configure custom permission groups to control access by user group, power over target groups, and per-action permissions.',
	'ADD_PERMISSION_GROUP'				=> 'Add Permission Group',
	'PERMISSION_GROUP_NAME'				=> 'Permission Group Name',
	'APPLIES_TO_GROUPS'					=> 'Applies To Groups',
	'APPLIES_TO_GROUPS_EXPLAIN'			=> 'Select which phpBB user groups receive these permissions.',
	'POWER_OVER_GROUPS'					=> 'Power Over Groups',
	'POWER_OVER_GROUPS_EXPLAIN'			=> 'Select target scopes or specific phpBB user groups this permission group has authority over.',
	'EXCLUDE_GROUPS'					=> 'Exclude Groups',
	'EXCLUDE_GROUPS_EXPLAIN'			=> 'Select target phpBB user groups that are explicitly excluded from this permission group. If a user belongs to an excluded group, this permission group will not grant any authority over them.',
	'ALL_GROUPS'						=> 'All Groups',
	'SELF_GROUP'						=> 'Self',
	'ENTRY_PERMISSIONS'					=> 'Entry Permissions',
	'PERM_EDIT_OWN'						=> 'Can Edit Own Entries',
	'PERM_DELETE_OWN'					=> 'Can Delete Own Entries',
	'DISCIPLINARY_ACTION_PERMISSIONS'	=> 'Action Access Matrix',
	'DISCIPLINARY_ACTION'				=> 'Disciplinary Action',
	'PERM_VIEW'							=> 'View',
	'PERM_VIEW_PRIVATE_NOTES'			=> 'View Private Notes',
	'PERM_ISSUE'						=> 'Issue',
	'PERM_ISSUE_PRIVATE_NOTES'			=> 'Issue Private Notes',
	'PERM_ARCHIVE'						=> 'Archive',
	'PERM_VIEW_ARCHIVED'				=> 'View Archived',
	'PERM_UNARCHIVE'					=> 'Unarchive',
	'PERM_EDIT'							=> 'Edit Others',
	'PERM_DELETE'						=> 'Delete Others',

	'ID' => 'ID',
	'NAME' => 'Name',
	'DESCRIPTION' => 'Description',
	'COLOR' => 'Color',
	'ACCESS_LEVEL' => 'Access Level',
	'ACTION' => 'Action',
	'ADD' => 'Add',
	'UPDATE' => 'Update',
	'DELETE' => 'Delete',

	'BOOSKIT_DISCIPLINARY_LOCALLY_VIEWABLE' => 'Self Viewable',
	'BOOSKIT_DISCIPLINARY_GLOBALLY_VIEWABLE' => 'Mapped Viewable',

	'LOG_DISCIPLINARY_ADDED'		=> '<strong>Added disciplinary action to user</strong><br>» %s',
	'LOG_DISCIPLINARY_EDITED'		=> '<strong>Edited disciplinary action for user</strong><br>» %s',
	'LOG_DISCIPLINARY_DELETED'		=> '<strong>Deleted disciplinary action for user</strong><br>» %s',
));
