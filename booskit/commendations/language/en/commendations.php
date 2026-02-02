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
	'COMMENDATION_ADD'				=> 'Add Commendation',
	'COMMENDATION_EDIT'				=> 'Edit Commendation',
	'COMMENDATION_ADDED'			=> 'Commendation successfully added.',
	'COMMENDATION_UPDATED'			=> 'Commendation successfully updated.',
	'COMMENDATION_DELETED'			=> 'Commendation successfully deleted.',
	'NO_COMMENDATION_RECORD'		=> 'No commendation found.',
	'DELETE_COMMENDATION_CONFIRM'	=> 'Are you sure you want to delete this commendation?',

	'COMMENDATION_DATE'				=> 'Date',
	'COMMENDATION_TYPE'				=> 'Type',
	'COMMENDATION_CHARACTER'		=> 'Character Name',
	'COMMENDATION_REASON'			=> 'Reason',
	'COMMENDATION_ISSUER'			=> 'Issued By',

	'COMMENDATION_TYPE_IC'			=> 'In-Character (IC)',
	'COMMENDATION_TYPE_OOC'			=> 'Out-of-Character (OOC)',
	'COMMENDATION_TYPE_ALL'			=> 'All',

	'COMMENDATIONS_FOR'				=> 'Commendations for %s',
	'VIEW_MORE_COMMENDATIONS'		=> 'View all commendations',

	'LOG_COMMENDATION_ADDED'		=> '<strong>Added commendation for</strong><br />» %s',
	'LOG_COMMENDATION_EDITED'		=> '<strong>Edited commendation for</strong><br />» %s',
	'LOG_COMMENDATION_DELETED'		=> '<strong>Deleted commendation for</strong><br />» %s',
));
