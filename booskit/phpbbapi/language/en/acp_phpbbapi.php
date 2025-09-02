<?php
/**
* English language file for booskit/phpbbapi ACP
*/

if (!defined('IN_PHPBB')) {
    exit;
}

$lang = array_merge($lang, [
    'ACP_BOOSKIT_PHPBBAPI_TITLE'    => 'booskit phpbbapi',
    'ACP_BOOSKIT_PHPBBAPI_SETTINGS' => 'Settings',
    'ACP_BOOSKIT_PHPBBAPI_SAVED'    => 'Settings saved successfully.',

    'ACP_BOOSKIT_PHPBBAPI_KEY_LABEL'       => 'Global API key',
    'ACP_BOOSKIT_PHPBBAPI_KEY_EXPLAIN'     => 'Provide this key in the header <code>X-API-Key</code> or as a <code>?key=</code> query parameter to access the JSON endpoints.',
]);