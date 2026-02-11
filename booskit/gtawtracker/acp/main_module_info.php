<?php
namespace booskit\gtawtracker\acp;

class main_module_info
{
    function module()
    {
        return array(
            'filename'    => '\booskit\gtawtracker\acp\main_module',
            'title'       => 'ACP_GTAW_TRACKER',
            'modes'       => array(
                'settings'  => array(
                    'title' => 'ACP_GTAW_TRACKER_SETTINGS',
                    'auth'  => 'ext_booskit/gtawtracker && acl_a_board',
                    'cat'   => array('ACP_GTAW_TRACKER'),
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
