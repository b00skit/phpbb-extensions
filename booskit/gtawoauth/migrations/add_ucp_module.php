<?php
namespace booskit\gtawoauth\migrations;

class add_ucp_module extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        // Check if module exists
        $sql = 'SELECT module_id FROM ' . $this->table_prefix . 'modules
            WHERE module_langname = \'UCP_GTAW_TITLE\'';
        $result = $this->db->sql_query($sql);
        $module_id = $this->db->sql_fetchfield('module_id');
        $this->db->sql_freeresult($result);

        return $module_id !== false;
    }

    public function update_data()
    {
        return array(
            array('module.add', array(
                'ucp',
                'UCP_PROFILE',
                array(
                    'module_basename'   => '\booskit\gtawoauth\ucp\gtaw_module',
                    'modes'             => array('link'),
                ),
            )),
        );
    }
}
