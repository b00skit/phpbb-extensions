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
	'ADD_DISCIPLINARY'				=> 'Add Disciplinary Action',
	'EDIT_DISCIPLINARY'				=> 'Edit Disciplinary Action',
	'DELETE_DISCIPLINARY'			=> 'Delete Disciplinary Action',
	'DISCIPLINARY_ACTIONS'			=> 'OOC Disciplinary Actions',
	'VIEW_ALL_DISCIPLINARY'			=> 'View All Disciplinary Actions',
	'DISCIPLINARY_TYPE'				=> 'Disciplinary Type',
	'ISSUE_DATE'					=> 'Date Issued',
	'REASON'						=> 'Reason',
	'EVIDENCE'						=> 'Private Notes',
	'PRIVATE_NOTES'					=> 'Private Notes',
	'SELECT_DISCIPLINARY_TYPE'		=> 'Select a type...',
	'NO_DISCIPLINARY_TYPE_SELECTED' => 'You must select a disciplinary type.',
	'DISCIPLINARY_ADDED'			=> 'Disciplinary action successfully added.',
	'DISCIPLINARY_UPDATED'			=> 'Disciplinary action successfully updated.',
	'DISCIPLINARY_DELETED'			=> 'Disciplinary action successfully deleted.',
	'DELETE_DISCIPLINARY_CONFIRM'	=> 'Are you sure you want to delete this disciplinary action?',
	'NO_DISCIPLINARY_ACTIONS'		=> 'No disciplinary actions found for this user.',
	'NO_DISCIPLINARY_RECORD'		=> 'Disciplinary record not found.',
	'ACTION'						=> 'Action',
	'DATE'							=> 'Date',
	'ISSUED_BY'						=> 'Issued by',
	'LAST_EDITED_BY'				=> 'Last edited by',
	'ON'							=> 'on',

	'LOG_DISCIPLINARY_ADDED'		=> '<strong>Added disciplinary action to user</strong><br>» %s',
	'LOG_DISCIPLINARY_EDITED'		=> '<strong>Edited disciplinary action for user</strong><br>» %s',
	'LOG_DISCIPLINARY_DELETED'		=> '<strong>Deleted disciplinary action for user</strong><br>» %s',
	'LOG_DISCIPLINARY_ARCHIVED'		=> '<strong>Archived disciplinary action for user</strong><br>» %s',
	'LOG_DISCIPLINARY_UNARCHIVED'	=> '<strong>Unarchived disciplinary action for user</strong><br>» %s',

	'ARCHIVE_DISCIPLINARY'			=> 'Archive Disciplinary Action',
	'UNARCHIVE_DISCIPLINARY'		=> 'Unarchive Disciplinary Action',
	'ARCHIVE_REASON'				=> 'Reason for Archiving',
	'ARCHIVE_REASON_EXPLAIN'		=> 'Please provide a clear reason for archiving this disciplinary action.',
	'ARCHIVE_DISCIPLINARY_REASON_EXPLAIN' => 'Please provide a clear reason for archiving this disciplinary action.',
	'ARCHIVE_REASON_EMPTY'			=> 'You must enter a reason for archiving.',
	'DISCIPLINARY_ARCHIVED'			=> 'Disciplinary action successfully archived.',
	'DISCIPLINARY_UNARCHIVED'		=> 'Disciplinary action successfully unarchived.',
	'ARCHIVED_STATUS'				=> 'Archived',
	'ARCHIVED_BY'					=> 'Archived by',
	'UNARCHIVE'						=> 'Unarchive',
	'ARCHIVE'						=> 'Archive',
	'COPY_TO_CLIPBOARD'				=> 'Copy to Clipboard',
	'COPIED_TO_CLIPBOARD'			=> 'Copied to clipboard!',
	'RESTRICTED_PRIVATE_NOTES'		=> 'Restricted Private Notes',
	'FORM_ALREADY_SUBMITTED'		=> 'This form has already been submitted.',
));
