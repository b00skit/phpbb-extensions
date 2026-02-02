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
));
