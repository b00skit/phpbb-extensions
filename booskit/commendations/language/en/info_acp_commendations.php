<?php
/**
 *
 * @package booskit/commendations
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
	'ACP_BOOSKIT_COMMENDATIONS_TITLE'			=> 'Commendations',
	'ACP_BOOSKIT_COMMENDATIONS_SETTINGS'		=> 'Commendation Settings',

	'BOOSKIT_COMMENDATIONS_ACCESS_VIEW'			=> 'Local View Access',
	'BOOSKIT_COMMENDATIONS_ACCESS_VIEW_EXPLAIN'	=> 'Comma separated group IDs that can view their own commendations. Users with Level 1+ access automatically inherit this.',
	'BOOSKIT_COMMENDATIONS_ACCESS_VIEW_GLOBAL'	=> 'Global View Access',
	'BOOSKIT_COMMENDATIONS_ACCESS_VIEW_GLOBAL_EXPLAIN' => 'Comma separated group IDs that can view everyone\'s commendations. Users with Level 1+ access automatically inherit this.',

	'BOOSKIT_COMMENDATIONS_ACCESS_L1'			=> 'Level 1 Access (Issue)',
	'BOOSKIT_COMMENDATIONS_ACCESS_L1_EXPLAIN'	=> 'Comma separated group IDs for Level 1 access. Can issue commendations to users with no level.',
	'BOOSKIT_COMMENDATIONS_ACCESS_L2'			=> 'Level 2 Access (Issue/Remove)',
	'BOOSKIT_COMMENDATIONS_ACCESS_L2_EXPLAIN'	=> 'Comma separated group IDs for Level 2 access. Can issue/remove for lower levels (Regular, L1).',
	'BOOSKIT_COMMENDATIONS_ACCESS_L3'			=> 'Level 3 Access',
	'BOOSKIT_COMMENDATIONS_ACCESS_L3_EXPLAIN'	=> 'Comma separated group IDs for Level 3 access. Can issue/remove for lower levels (Regular, L1, L2).',
	'BOOSKIT_COMMENDATIONS_ACCESS_FULL'			=> 'Full Access (Level 4)',
	'BOOSKIT_COMMENDATIONS_ACCESS_FULL_EXPLAIN'	=> 'Comma separated group IDs for Full Access. Can issue/remove for everyone.',

	'BOOSKIT_COMMENDATIONS_PERM_SYSTEM'			=> 'Permission System',
	'BOOSKIT_COMMENDATIONS_PERM_SYSTEM_EXPLAIN'	=> 'Choose between the legacy tier system and the flexible permission groups system.',
	'BOOSKIT_COMMENDATIONS_PERM_SYSTEM_LEGACY'	=> 'Legacy System (Level 1-4)',
	'BOOSKIT_COMMENDATIONS_PERM_SYSTEM_GROUPS'	=> 'Permission Groups System',

	'PERMISSION_GROUPS'							=> 'Permission Groups',
	'PERMISSION_GROUPS_EXPLAIN'					=> 'Define custom permission groups for Commendations.',
	'PERMISSION_GROUP_NAME'						=> 'Group Name',
	'APPLIES_TO_GROUPS'							=> 'Applies To User Groups',
	'APPLIES_TO_GROUPS_EXPLAIN'					=> 'Users in these groups will be granted the permissions defined below.',
	'POWER_OVER_GROUPS'							=> 'Target Scope / Power Over',
	'POWER_OVER_GROUPS_EXPLAIN'					=> 'Select which target users this permission group applies to.',
	'ALL_GROUPS'								=> 'All Users',
	'SELF_GROUP'								=> 'Self Only',
	'EXCLUDE_GROUPS'							=> 'Exclude Target Groups',
	'EXCLUDE_GROUPS_EXPLAIN'					=> 'Users in these groups are excluded as targets for this permission group.',
	'ENTRY_PERMISSIONS'							=> 'Permissions',
	'PERM_VIEW'									=> 'View Commendations',
	'PERM_SUBMIT'								=> 'Submit Commendations',
	'PERM_COPY'									=> 'Copy to Clipboard',
	'PERM_EDIT_OWN'								=> 'Can Edit Own Entries',
	'PERM_DELETE_OWN'							=> 'Can Delete Own Entries',
	'PERM_EDIT_OTHERS'							=> 'Edit Others Commendations',
	'PERM_DELETE_OTHERS'						=> 'Delete Others Commendations',
	'ADD_PERMISSION_GROUP'						=> 'Add Permission Group',

	'CLIPBOARD_SETTINGS'						=> 'Clipboard Settings',
	'BOOSKIT_COMMENDATIONS_CLIPBOARD_TPL'		=> 'Clipboard Format',
	'BOOSKIT_COMMENDATIONS_CLIPBOARD_TPL_EXPLAIN' => 'Format string when copying a commendation to the clipboard.<br />Available variables: {METADATA}, {CONTENT}, {PRIVATE}, {TYPE_COLOR}, {TYPE_ID}, {TYPE_NAME}, {TYPE_DESCRIPTION}, {TARGET_NAME}, {ISSUER_NAME}, {DATE}, {RECORD_ID}, {CHARACTER_NAME}.',

	'PUBLIC_POSTING_SETTINGS'					=> 'Public Posting Settings',
	'ENABLE_PUBLIC_POSTING'						=> 'Enable Public Posting',
	'ENABLE_PUBLIC_POSTING_EXPLAIN'				=> 'Allow posting to forum or replying to a topic when issuing commendations.',
	'PUBLIC_POSTING_MODE'						=> 'Posting Mode',
	'PUBLIC_POSTING_MODE_EXPLAIN'				=> 'Choose whether to create a new topic in a forum or reply to an existing post/topic.',
	'PUBLIC_POSTING_MODE_FORUM'					=> 'New Topic in Forum',
	'PUBLIC_POSTING_MODE_REPLY'					=> 'Reply to Specific Post/Topic',
	'PUBLIC_POSTING_FORUM_ID'					=> 'Forum ID',
	'PUBLIC_POSTING_FORUM_ID_EXPLAIN'			=> 'ID of the forum where new topics will be created.',
	'PUBLIC_POSTING_POST_ID'					=> 'Target Post/Topic ID',
	'PUBLIC_POSTING_POST_ID_EXPLAIN'			=> 'ID of the post or topic to reply to.',
	'PUBLIC_POSTING_POSTER_ID'					=> 'Poster User ID',
	'PUBLIC_POSTING_POSTER_ID_EXPLAIN'			=> 'User ID to post under (set to 0 to post as the user submitting the commendation).',
	'PUBLIC_POSTING_SUBJECT_TPL'				=> 'Subject Template',
	'PUBLIC_POSTING_SUBJECT_TPL_EXPLAIN'		=> 'Available tags: {CHARACTER_NAME}, {TARGET_USERNAME}, {ISSUER_USERNAME}, {TYPE}, {DATE}',
	'PUBLIC_POSTING_BODY_TPL'					=> 'Body Template',
	'PUBLIC_POSTING_BODY_TPL_EXPLAIN'			=> 'Available tags: {REASON}, {CHARACTER_NAME}, {TARGET_USERNAME}, {ISSUER_USERNAME}, {TYPE}, {DATE}. Leave empty for default.',

	'LOG_COMMENDATION_ADDED'		=> '<strong>Added commendation for</strong><br />» %s',
	'LOG_COMMENDATION_EDITED'		=> '<strong>Edited commendation for</strong><br />» %s',
	'LOG_COMMENDATION_DELETED'		=> '<strong>Deleted commendation for</strong><br />» %s',
));

