<?php
/**
 * Install ACP module for Send As
 */

namespace booskit\privatemessageas\migrations;

class install_acp_module extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        $sql = 'SELECT module_id
            FROM ' . $this->table_prefix . "modules
            WHERE module_class = 'acp'
                AND module_langname = 'ACP_PRIVATEMESSAGEAS_TITLE'";
        $result = $this->db->sql_query($sql);
        $module_id = $this->db->sql_fetchfield('module_id');
        $this->db->sql_freeresult($result);

        return $module_id !== false;
    }

    public static function depends_on()
    {
        return ['\\booskit\\privatemessageas\\migrations\\install_privatemessageas_table'];
    }

    public function update_data()
    {
        return [
            // Add module under Extensions tab (ACP_CAT_DOT_MODS)
            ['module.add', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_PRIVATEMESSAGEAS_TITLE']],
            // Add settings mode for our module
            ['module.add', ['acp', 'ACP_PRIVATEMESSAGEAS_TITLE', [
                'module_basename' => '\\booskit\\privatemessageas\\acp\\main_module',
                'module_langname' => 'ACP_PRIVATEMESSAGEAS_SETTINGS',
                'module_mode'     => 'settings',
                'module_auth'     => 'acl_a_board',
            ]]],
        ];
    }
}
