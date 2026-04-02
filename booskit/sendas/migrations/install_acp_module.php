<?php
/**
 * Install ACP module for Send As
 */

namespace booskit\sendas\migrations;

class install_acp_module extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        $sql = 'SELECT module_id
            FROM ' . $this->table_prefix . "modules
            WHERE module_class = 'acp'
                AND module_langname = 'ACP_SENDAS_TITLE'";
        $result = $this->db->sql_query($sql);
        $module_id = $this->db->sql_fetchfield('module_id');
        $this->db->sql_freeresult($result);

        return $module_id !== false;
    }

    public static function depends_on()
    {
        return ['\\booskit\\sendas\\migrations\\install_sendas_table'];
    }

    public function update_data()
    {
        return [
            // Add module under Extensions tab (ACP_CAT_DOT_MODS)
            ['module.add', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_SENDAS_TITLE']],
            // Add settings mode for our module
            ['module.add', ['acp', 'ACP_SENDAS_TITLE', [
                'module_basename' => '\\booskit\\sendas\\acp\\main_module',
                'module_langname' => 'ACP_SENDAS_SETTINGS',
                'module_mode'     => 'settings',
                'module_auth'     => 'acl_a_board',
            ]]],
        ];
    }
}
