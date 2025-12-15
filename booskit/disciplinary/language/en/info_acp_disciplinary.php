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
));
