<?php
namespace booskit\gtawoauth\acp;

class gtaw_module_info
{
    function module()
    {
        return array(
            'filename'    => '\booskit\gtawoauth\acp\gtaw_module',
            'title'       => 'ACP_GTAW_OAUTH',
            'modes'       => array(
                'settings'  => array(
                    'title' => 'ACP_GTAW_OAUTH_SETTINGS',
                    'auth'  => 'ext_booskit/gtawoauth && acl_a_board',
                    'cat'   => array('ACP_GTAW_OAUTH'),
                ),
            ),
        );
    }

    function install()
    {
    }

    function uninstall()
    {
    }
}
