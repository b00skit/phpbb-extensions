<?php
namespace booskit\gtawoauth\migrations;

class release_1_0_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['auth_oauth_gtaw_key']);
    }

    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v330\v330');
    }

    public function update_data()
    {
        return array(
            array('config.add', array('auth_oauth_gtaw_key', '')),
            array('config.add', array('auth_oauth_gtaw_secret', '')),

            // Add ACP Module
            array('module.add', array(
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_GTAW_OAUTH'
            )),
            array('module.add', array(
                'acp',
                'ACP_GTAW_OAUTH',
                array(
                    'module_basename'   => '\booskit\gtawoauth\acp\gtaw_module',
                    'modes'             => array('settings'),
                ),
            )),
        );
    }
}
