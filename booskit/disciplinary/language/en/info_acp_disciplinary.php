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
));
