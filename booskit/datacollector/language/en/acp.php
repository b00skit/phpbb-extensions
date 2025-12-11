<?php

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'ACP_DATACOLLECTOR_TITLE'			=> 'Data Collector',
	'ACP_DATACOLLECTOR_SETTINGS'		=> 'Settings',
	'DATACOLLECTOR_POST_URL'			=> 'POST API Link',
	'DATACOLLECTOR_POST_URL_EXPLAIN'	=> 'The URL where the data will be sent via POST.',
	'DATACOLLECTOR_GROUP_ID'			=> 'Group ID',
	'DATACOLLECTOR_GROUP_ID_EXPLAIN'	=> 'The ID of the user group to export users from.',
	'DATACOLLECTOR_FORUM_ID'			=> 'Forum ID',
	'DATACOLLECTOR_FORUM_ID_EXPLAIN'	=> 'The ID of the forum to export threads from.',
	'LOG_DATACOLLECTOR_SETTINGS_UPDATED'=> '<strong>Data Collector settings updated</strong>',
]);
