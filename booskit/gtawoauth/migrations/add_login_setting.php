<?php
namespace booskit\gtawoauth\migrations;

class add_login_setting extends \phpbb\db\migration\migration
{
    static public function depends_on()
    {
        return array('\booskit\gtawoauth\migrations\release_1_0_0');
    }

    public function update_data()
    {
        return array(
            array('config.add', array('auth_oauth_gtaw_login_enable', 0)),
        );
    }
}
