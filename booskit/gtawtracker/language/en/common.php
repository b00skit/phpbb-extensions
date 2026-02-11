<?php
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACP_GTAW_TRACKER'             => 'GTA:W Tracker',
	'ACP_GTAW_TRACKER_SETTINGS'    => 'GTA:W Tracker Settings',
	'GTAW_FACTION_ID'              => 'Faction ID',
	'GTAW_FACTION_ID_EXPLAIN'      => 'The ID of the faction to track characters from.',
	'GTAW_VIEW_GROUPS'             => 'View Access Groups',
	'GTAW_VIEW_GROUPS_EXPLAIN'     => 'Comma separated list of group IDs that can view the tracker on profiles.',
    'GTAW_MIN_ABAS'                => 'Minimum ABAS',
    'GTAW_MIN_ABAS_EXPLAIN'        => 'The minimum ABAS amount required. Values below this will be highlighted in red. Accepts period or comma as decimal separator.',

    'GTAW_TRACKER'                 => 'GTA: World Character Tracker',
    'GTAW_TRACKER_LOADING'         => 'Loading character data...',
    'GTAW_TRACKER_ERROR'           => 'Error loading data.',
    'GTAW_TRACKER_NO_LINK'         => 'You must link your GTA:W account to view this.',
    'GTAW_TRACKER_NO_ACCESS'       => 'You do not have permission to view this.',
    'GTAW_TRACKER_NO_CHARACTER'    => 'No character found in the configured faction.',
    'GTAW_TRACKER_TOTAL_ABAS'      => 'Total ABAS',
));
