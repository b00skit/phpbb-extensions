<?php
if (!defined('IN_PHPBB')) { exit; }

if (empty($lang) || !is_array($lang)) {
    $lang = array();
}

$lang = array_merge($lang, array(
    'AUTH_PROVIDER_OAUTH_SERVICE_GTAW' => 'GTA: World',
    'GTAW_LOGIN_TITLE'                 => 'Login with GTA: World',

    // ACP
    'ACP_GTAW_OAUTH'                   => 'GTA: World OAuth',
    'ACP_GTAW_OAUTH_SETTINGS'          => 'GTA: World OAuth Settings',
    'GTAW_CLIENT_ID'                   => 'Client ID',
    'GTAW_CLIENT_ID_EXPLAIN'           => 'Enter the Client ID provided by GTA: World UCP.',
    'GTAW_CLIENT_SECRET'               => 'Client Secret',
    'GTAW_CLIENT_SECRET_EXPLAIN'       => 'Enter the Client Secret provided by GTA: World UCP.',
    'LOG_CONFIG_GTAW_OAUTH'            => '<strong>Updated GTA: World OAuth settings</strong>',
));
