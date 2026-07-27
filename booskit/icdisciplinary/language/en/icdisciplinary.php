<?php
/**
 *
 * @package booskit/icdisciplinary
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
	'IC_DISCIPLINARY_RECORDS'	=> 'IC Disciplinary Records',
	'IC_CHARACTER'				=> 'Character',
	'IC_CHARACTERS'				=> 'Characters',
	'ADD_CHARACTER'				=> 'Add Character',
	'CHARACTER_NAME'			=> 'Character Name',
	'ARCHIVE_CHARACTER'			=> 'Archive Character',
	'DELETE_CHARACTER'			=> 'Delete Character',
	'CHARACTER_ADDED'			=> 'Character added successfully.',
	'CHARACTER_ARCHIVED'		=> 'Character archived successfully.',
	'CHARACTER_DELETED'			=> 'Character deleted successfully.',
	'CHARACTER_ARCHIVED_STATUS' => '(Archived)',

	'ADD_IC_RECORD'				=> 'Add IC Record',
	'EDIT_IC_RECORD'			=> 'Edit IC Record',
	'DELETE_IC_RECORD'			=> 'Delete IC Record',
	'NO_IC_RECORDS'				=> 'No IC disciplinary records found.',
	'IC_RECORD_ADDED'			=> 'IC Record added successfully.',
	'IC_RECORD_UPDATED'			=> 'IC Record updated successfully.',
	'IC_RECORD_DELETED'			=> 'IC Record deleted successfully.',

	'NO_CHARACTER_SELECTED'		=> 'Please select a character to view records.',
	'SELECT_CHARACTER'			=> 'Select Character',

	'CONFIRM_ARCHIVE_CHARACTER'	=> 'Are you sure you want to archive this character?',
	'CONFIRM_DELETE_CHARACTER'	=> 'Are you sure you want to delete this character and all their records?',
	'CONFIRM_DELETE_IC_RECORD'	=> 'Are you sure you want to delete this IC record?',

    'LOG_IC_CHARACTER_ADDED'    => '<strong>IC Character added</strong><br />» %s',
    'LOG_IC_CHARACTER_ARCHIVED' => '<strong>IC Character archived</strong><br />» %s',
    'LOG_IC_CHARACTER_DELETED'  => '<strong>IC Character deleted</strong><br />» %s',
    'LOG_IC_RECORD_ADDED'       => '<strong>IC Record added</strong><br />» %s (Character: %s)',
    'LOG_IC_RECORD_EDITED'      => '<strong>IC Record edited</strong><br />» %s (Character: %s)',
    'LOG_IC_RECORD_DELETED'     => '<strong>IC Record deleted</strong><br />» %s (Character: %s)',
    'LOG_IC_RECORD_ARCHIVED'    => '<strong>IC Record archived</strong><br />» %s (Character: %s)',
    'LOG_IC_RECORD_UNARCHIVED'  => '<strong>IC Record unarchived</strong><br />» %s (Character: %s)',

	'ARCHIVE_IC_RECORD'			=> 'Archive IC Record',
	'UNARCHIVE_IC_RECORD'		=> 'Unarchive IC Record',
	'ARCHIVE_REASON'			=> 'Reason for Archiving',
	'ARCHIVE_REASON_EXPLAIN'	=> 'Please provide a clear reason for archiving this IC record.',
	'ARCHIVE_IC_RECORD_REASON_EXPLAIN' => 'Please provide a clear reason for archiving this IC record.',
	'ARCHIVE_REASON_EMPTY'		=> 'You must enter a reason for archiving.',
	'IC_RECORD_ARCHIVED'		=> 'IC Record successfully archived.',
	'IC_RECORD_UNARCHIVED'		=> 'IC Record successfully unarchived.',
	'ARCHIVED_STATUS'			=> 'Archived',
	'ARCHIVED_BY'				=> 'Archived by',
	'UNARCHIVE'					=> 'Unarchive',
	'ARCHIVE'					=> 'Archive',

	'DISCIPLINARY_TYPE'			=> 'Type',
	'EVIDENCE'					=> 'Private Notes',
	'PRIVATE_NOTES'				=> 'Private Notes',
	'ISSUED_BY'					=> 'Issued By',
	'LAST_EDITED_BY'				=> 'Last edited by',
	'ON'							=> 'on',
	'REASON'					=> 'Reason',

	'ISSUE_DATE'					=> 'Date Issued',
	'SELECT_DISCIPLINARY_TYPE'		=> 'Select a type...',
	'NO_DISCIPLINARY_TYPE_SELECTED' => 'You must select a disciplinary type.',
	'ACTION'						=> 'Action',
	'DATE'							=> 'Date',
	'CHARACTER_NAME_EMPTY'			=> 'Character name cannot be empty.',
	'COPY_TO_CLIPBOARD'				=> 'Copy to Clipboard',
	'COPIED_TO_CLIPBOARD'			=> 'Copied to clipboard!',
	'RESTRICTED_PRIVATE_NOTES'		=> 'Restricted Private Notes',
));
