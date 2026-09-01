<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['booskit_2fa_enabled']) && $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_2fa_users');
    }

    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v330\v330');
    }

    public function update_data()
    {
        return array(
            array('config.add', array('booskit_2fa_enabled', 1)),
            array('config.add', array('booskit_2fa_issuer', '')),
            array('config.add', array('booskit_2fa_color', '#2563eb')),
            array('config.add', array('booskit_2fa_logo_url', '')),
            array('config.add', array('booskit_2fa_groups_suggest', '')),
            array('config.add', array('booskit_2fa_groups_enforce', '')),
            array('config.add', array('booskit_2fa_groups_remember', '')),
            array('config.add', array('booskit_2fa_groups_login', '')),
            array('config.add', array('booskit_2fa_groups_ucp', '')),
            array('config.add', array('booskit_2fa_groups_mcp', '')),
            array('config.add', array('booskit_2fa_groups_acp', '')),
            array('config.add', array('booskit_2fa_groups_oauth', '')),
            array('config.add', array('booskit_2fa_shared_session', 0)),
            array('config.add', array('booskit_2fa_ucp_ignore_remember', 0)),
            array('config.add', array('booskit_2fa_mcp_ignore_remember', 0)),
            array('config.add', array('booskit_2fa_acp_ignore_remember', 0)),

            // ACP Module
            array('module.add', array(
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_BOOSKIT_TWOFACTOR_TITLE'
            )),
            array('module.add', array(
                'acp',
                'ACP_BOOSKIT_TWOFACTOR_TITLE',
                array(
                    'module_basename' => '\booskit\twofactor\acp\twofactor_module',
                    'modes'           => array('settings'),
                ),
            )),

            // UCP Module
            array('module.add', array(
                'ucp',
                'UCP_PROFILE',
                array(
                    'module_basename' => '\booskit\twofactor\ucp\twofactor_module',
                    'modes'           => array('manage'),
                ),
            )),
        );
    }

    public function update_schema()
    {
        return array(
            'add_tables' => array(
                $this->table_prefix . 'booskit_2fa_users' => array(
                    'COLUMNS' => array(
                        'user_id'                    => array('UINT', 0),
                        'secret'                     => array('VCHAR:64', ''),
                        'is_enabled'                 => array('UINT:1', 0),
                        'enabled_at'                 => array('TIMESTAMP', 0),
                        'last_login_at'              => array('TIMESTAMP', 0),
                        'last_login_ip'              => array('VCHAR:45', ''),
                        'last_login_method'          => array('VCHAR:20', ''),
                        'suggestion_dismissed_until' => array('TIMESTAMP', 0),
                        'reset_backup_pending'       => array('UINT:1', 0),
                    ),
                    'PRIMARY_KEY' => 'user_id',
                ),
                $this->table_prefix . 'booskit_2fa_backup_codes' => array(
                    'COLUMNS' => array(
                        'code_id'    => array('UINT', null, 'auto_increment'),
                        'user_id'    => array('UINT', 0),
                        'code_hash'  => array('VCHAR:255', ''),
                        'is_used'    => array('UINT:1', 0),
                        'used_at'    => array('TIMESTAMP', 0),
                        'created_at' => array('TIMESTAMP', 0),
                    ),
                    'PRIMARY_KEY' => 'code_id',
                    'KEYS' => array(
                        'user_id' => array('INDEX', 'user_id'),
                    ),
                ),
                $this->table_prefix . 'booskit_2fa_sessions' => array(
                    'COLUMNS' => array(
                        'session_id'     => array('CHAR:32', ''),
                        'user_id'        => array('UINT', 0),
                        'is_verified'    => array('UINT:1', 0),
                        'verified_ucp'   => array('UINT:1', 0),
                        'verified_mcp'   => array('UINT:1', 0),
                        'verified_acp'   => array('UINT:1', 0),
                        'verified_at'    => array('TIMESTAMP', 0),
                        'ip_hash'        => array('VCHAR:32', ''),
                        'pending_secret' => array('VCHAR:64', ''),
                        'auth_via_oauth' => array('UINT:1', 0),
                    ),
                    'PRIMARY_KEY' => 'session_id',
                    'KEYS' => array(
                        'user_id' => array('INDEX', 'user_id'),
                    ),
                ),
                $this->table_prefix . 'booskit_2fa_trusted_devices' => array(
                    'COLUMNS' => array(
                        'device_id'         => array('UINT', null, 'auto_increment'),
                        'user_id'           => array('UINT', 0),
                        'ip_address'        => array('VCHAR:45', ''),
                        'ip_hash'           => array('VCHAR:32', ''),
                        'created_at'        => array('TIMESTAMP', 0),
                        'expires_at'        => array('TIMESTAMP', 0),
                        'device_token_hash' => array('VCHAR:255', ''),
                    ),
                    'PRIMARY_KEY' => 'device_id',
                    'KEYS' => array(
                        'user_id'    => array('INDEX', 'user_id'),
                        'ip_hash'    => array('INDEX', 'ip_hash'),
                        'expires_at' => array('INDEX', 'expires_at'),
                    ),
                ),
            ),
        );
    }

    public function revert_data()
    {
        return array(
            array('config.remove', array('booskit_2fa_enabled')),
            array('config.remove', array('booskit_2fa_issuer')),
            array('config.remove', array('booskit_2fa_color')),
            array('config.remove', array('booskit_2fa_logo_url')),
            array('config.remove', array('booskit_2fa_groups_suggest')),
            array('config.remove', array('booskit_2fa_groups_enforce')),
            array('config.remove', array('booskit_2fa_groups_remember')),
            array('config.remove', array('booskit_2fa_groups_login')),
            array('config.remove', array('booskit_2fa_groups_ucp')),
            array('config.remove', array('booskit_2fa_groups_mcp')),
            array('config.remove', array('booskit_2fa_groups_acp')),
            array('config.remove', array('booskit_2fa_groups_oauth')),
            array('config.remove', array('booskit_2fa_shared_session')),
            array('config.remove', array('booskit_2fa_ucp_ignore_remember')),
            array('config.remove', array('booskit_2fa_mcp_ignore_remember')),
            array('config.remove', array('booskit_2fa_acp_ignore_remember')),

            // Remove UCP Module
            array('module.remove', array(
                'ucp',
                'UCP_PROFILE',
                array(
                    'module_basename' => '\booskit\twofactor\ucp\twofactor_module',
                    'modes'           => array('manage'),
                ),
            )),

            // Remove ACP Module
            array('module.remove', array(
                'acp',
                'ACP_BOOSKIT_TWOFACTOR_TITLE',
                array(
                    'module_basename' => '\booskit\twofactor\acp\twofactor_module',
                    'modes'           => array('settings'),
                ),
            )),
            array('module.remove', array(
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_BOOSKIT_TWOFACTOR_TITLE'
            )),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables' => array(
                $this->table_prefix . 'booskit_2fa_users',
                $this->table_prefix . 'booskit_2fa_backup_codes',
                $this->table_prefix . 'booskit_2fa_sessions',
                $this->table_prefix . 'booskit_2fa_trusted_devices',
            ),
        );
    }
}
