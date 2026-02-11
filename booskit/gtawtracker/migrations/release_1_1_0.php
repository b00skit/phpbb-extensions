<?php
namespace booskit\gtawtracker\migrations;

class release_1_1_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['booskit_gtawtracker_min_abas']);
    }

    static public function depends_on()
    {
        return array('\booskit\gtawtracker\migrations\release_1_0_0');
    }

    public function update_data()
    {
        return array(
            array('config.add', array('booskit_gtawtracker_min_abas', '0.0')),
        );
    }
}
