<?php
namespace booskit\gtawoauth\ucp;

class gtaw_module_info
{
    function module()
    {
        return array(
            'filename'    => '\booskit\gtawoauth\ucp\gtaw_module',
            'title'       => 'UCP_GTAW_TITLE',
            'modes'       => array(
                'link'  => array(
                    'title' => 'UCP_GTAW_LINK_TITLE',
                    'auth'  => '',
                    'cat'   => array('UCP_PROFILE'),
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
