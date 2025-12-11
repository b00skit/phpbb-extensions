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
    'GTAW_BASE_URL'                    => 'Base Website URL',
    'GTAW_BASE_URL_EXPLAIN'            => 'Enter the base URL of your forum (e.g., https://your-domain.com/phpbb). Required to construct the absolute callback URL correctly.',
    'LOG_CONFIG_GTAW_OAUTH'            => '<strong>Updated GTA: World OAuth settings</strong>',

    // UCP
    'UCP_GTAW_TITLE'                   => 'GTA: World Link',
    'UCP_GTAW_LINK_TITLE'              => 'Link GTA: World Account',
    'UCP_GTAW_LINK_EXPLAIN'            => 'Link your forum account with your GTA: World game account.',
    'GTAW_LINK_ACCOUNT'                => 'Link Account',
    'GTAW_UNLINK_ACCOUNT'              => 'Unlink Account',
    'GTAW_LINKED_STATUS'               => 'Your account is currently linked to GTA: World user ID:',
    'GTAW_NOT_LINKED_STATUS'           => 'Your account is not linked to GTA: World.',
    'GTAW_LINK_SUCCESS'                => 'Account linked successfully.',
    'GTAW_UNLINK_SUCCESS'              => 'Account unlinked successfully.',
    'GTAW_LINK_FAILED_TOKEN'           => 'Failed to retrieve access token from GTA: World.',
    'GTAW_LINK_FAILED_USER'            => 'Failed to retrieve user info from GTA: World.',
    'GTAW_ALREADY_LINKED'              => 'This GTA: World account is already linked to your forum account.',
    'GTAW_LINKED_TO_OTHER'             => 'This GTA: World account is already linked to another forum account.',
    'GTAW_NO_LINKED_ACCOUNT'           => 'No linked account found. Please login normally and link your account in the UCP first.',
    'LOGIN_WITH_GTAW'                  => 'Login with GTAW',
));
