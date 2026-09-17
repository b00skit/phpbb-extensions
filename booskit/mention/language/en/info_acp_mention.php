<?php
/**
 *
 * @package booskit/mention
 * @license MIT
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'ACP_BOOSKIT_MENTION_TITLE'            => 'User & Group Mentions',
	'ACP_BOOSKIT_MENTION_SETTINGS'         => 'Mention Settings',
	'ACP_BOOSKIT_MENTION_SETTINGS_EXPLAIN' => 'Configure user and group mention behaviors, enable or disable group mentioning, and customize group-to-group mention permissions.',

	'ACP_BOOSKIT_MENTION_GENERAL_SETTINGS' => 'General Settings',
	'BOOSKIT_MENTION_GROUP_ENABLED'        => 'Enable Group Mentions',
	'BOOSKIT_MENTION_GROUP_ENABLED_EXPLAIN'=> 'When enabled, users can mention entire groups via @groupname autocomplete in posts. Group members will be notified upon post creation.',

	'BOOSKIT_MENTION_GROUP_MATRIX_TITLE'   => 'Group Mention Permissions',
	'BOOSKIT_MENTION_GROUP_MATRIX_EXPLAIN' => 'Configure which groups can mention other groups. If no groups are defined below, group mentions are unrestricted (free-for-all). As soon as at least one group is added, group mentioning is restricted strictly to the defined groups.',
	'BOOSKIT_MENTION_STATUS_FREE_FOR_ALL'  => 'Status: Unrestricted (Free-for-all)',
	'BOOSKIT_MENTION_STATUS_FREE_FOR_ALL_EXPLAIN' => 'No group mention rules are currently defined. All users are permitted to mention any group. Add a group below to begin enforcing restrictions.',
	'BOOSKIT_MENTION_STATUS_RESTRICTED'    => 'Status: Restricted',
	'BOOSKIT_MENTION_STATUS_RESTRICTED_EXPLAIN' => 'Group mention permissions are active. Only users belonging to the explicitly defined groups below are permitted to mention groups.',
	'BOOSKIT_MENTION_NO_GROUPS_DEFINED'    => 'No group mention permissions are currently defined. Mentioning is unrestricted for all groups.',
	'BOOSKIT_MENTION_ADD_GROUP'            => 'Add Group Permission',
	'BOOSKIT_MENTION_SELECT_GROUP_TO_ADD'  => 'Select a group to add...',
	'BOOSKIT_MENTION_ALL_GROUPS_ADDED'     => 'All available groups have already been added.',
	'BOOSKIT_MENTION_REMOVE_GROUP'         => 'Remove',
	'BOOSKIT_MENTION_ACTION'               => 'Action',
	'BOOSKIT_MENTION_SOURCE_GROUP'         => 'Author Group',
	'BOOSKIT_MENTION_ALLOWED_TARGET_GROUPS'=> 'Permitted Target Groups',
	'BOOSKIT_MENTION_CAN_MENTION_ALL'      => 'Can mention all groups',
	'BOOSKIT_MENTION_SELECT_MULTIPLE_HINT' => 'Hold Ctrl (or Cmd on Mac) to select multiple target groups.',

	'LOG_BOOSKIT_MENTION_SETTINGS_SAVED'   => '<strong>Mentions settings updated</strong>',
]);
