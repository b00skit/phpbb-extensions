<?php
/**
 *
 * Topic Log Views extension for phpBB.
 *
 * @copyright (c) 2026 Booskit
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
	'ACP_TOPICLOGVIEWS_TITLE'            => 'Topic Log Views',
	'ACP_TOPICLOGVIEWS_SETTINGS'         => 'Topic Log Views Settings',
	'ACP_TOPICLOGVIEWS_SETTINGS_EXPLAIN' => 'Configure settings for logging topic views in Moderator Logs (MCP > Logs > Topic logs).',
	'LOG_TOPICLOGVIEWS_SETTINGS_SAVED'   => '<strong>Updated Topic Log Views settings</strong>',
	'ACP_TOPICLOGVIEWS_SAVED'            => 'Topic Log Views settings updated successfully.',

	'TOPICLOGVIEWS_ENABLE'               => 'Enable Topic View Logging',
	'TOPICLOGVIEWS_ENABLE_EXPLAIN'       => 'When enabled, topic views will be recorded under Moderator Logs.',
	'TOPICLOGVIEWS_LOG_GUESTS'           => 'Log Guest Views',
	'TOPICLOGVIEWS_LOG_GUESTS_EXPLAIN'   => 'Record topic views made by guest (anonymous) visitors.',
	'TOPICLOGVIEWS_EXCLUDE_BOTS'         => 'Exclude Search Engine Bots',
	'TOPICLOGVIEWS_EXCLUDE_BOTS_EXPLAIN' => 'Ignore search engine web crawlers and spiders.',
	'TOPICLOGVIEWS_SESSION_ONCE'         => 'Deduplicate Views per Session',
	'TOPICLOGVIEWS_SESSION_ONCE_EXPLAIN' => 'Record only one log entry per topic per user session to prevent duplicate logs on page refreshes.',
	'TOPICLOGVIEWS_MOD_ONLY'             => 'Log Moderators Only',
	'TOPICLOGVIEWS_MOD_ONLY_EXPLAIN'     => 'Only record views when performed by users with moderator privileges in the topic\'s forum.',
));
