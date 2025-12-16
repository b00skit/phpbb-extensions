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
	'DISCIPLINARY_ACTIONS'			=> 'Disciplinary Actions',
	'DISCIPLINARY_TYPE'				=> 'Disciplinary Type',
	'ISSUE_DATE'					=> 'Date Issued',
	'REASON'						=> 'Reason',
	'EVIDENCE'						=> 'Evidence',
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

	'LOG_DISCIPLINARY_ADDED'		=> '<strong>Added disciplinary action to user</strong><br>» %s',
	'LOG_DISCIPLINARY_EDITED'		=> '<strong>Edited disciplinary action for user</strong><br>» %s',
	'LOG_DISCIPLINARY_DELETED'		=> '<strong>Deleted disciplinary action for user</strong><br>» %s',
));
