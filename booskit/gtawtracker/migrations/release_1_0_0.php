<?php
namespace booskit\gtawtracker\migrations;

class release_1_0_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['booskit_gtawtracker_faction_id']);
    }

    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v330\v330', '\booskit\gtawoauth\migrations\add_token_storage');
    }

    public function update_data()
    {
        return array(
            array('config.add', array('booskit_gtawtracker_faction_id', 0)),
            array('config.add', array('booskit_gtawtracker_view_groups', '')),

            array('module.add', array(
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_GTAW_TRACKER'
            )),
            array('module.add', array(
                'acp',
                'ACP_GTAW_TRACKER',
                array(
                    'module_basename'   => '\booskit\gtawtracker\acp\main_module',
                    'modes'             => array('settings'),
                ),
            )),
        );
    }
}
