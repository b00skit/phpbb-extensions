<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\migrations;

class v101_pending_logins extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_2fa_pending_logins');
    }

    static public function depends_on()
    {
        return array('\booskit\twofactor\migrations\v100_initial');
    }

    public function update_schema()
    {
        return array(
            'add_tables' => array(
                $this->table_prefix . 'booskit_2fa_pending_logins' => array(
                    'COLUMNS' => array(
                        'login_token'    => array('VCHAR:64', ''),
                        'user_id'        => array('UINT', 0),
                        'autologin'      => array('UINT:1', 0),
                        'viewonline'     => array('UINT:1', 1),
                        'admin'          => array('UINT:1', 0),
                        'redirect_url'   => array('MTEXT', ''),
                        'auth_via_oauth' => array('UINT:1', 0),
                        'created_at'     => array('TIMESTAMP', 0),
                        'expires_at'     => array('TIMESTAMP', 0),
                        'ip_address'     => array('VCHAR:45', ''),
                    ),
                    'PRIMARY_KEY' => 'login_token',
                    'KEYS' => array(
                        'user_id'    => array('INDEX', 'user_id'),
                        'expires_at' => array('INDEX', 'expires_at'),
                    ),
                ),
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables' => array(
                $this->table_prefix . 'booskit_2fa_pending_logins',
            ),
        );
    }
}
