<?php
namespace booskit\gtawoauth\migrations;

class add_token_storage extends \phpbb\db\migration\migration
{
    static public function depends_on()
    {
        return array('\booskit\gtawoauth\migrations\release_1_0_0');
    }

    public function update_schema()
    {
        return array(
            'add_tables' => array(
                $this->table_prefix . 'booskit_oauth_tokens' => array(
                    'COLUMNS' => array(
                        'user_id'       => array('UINT', 0),
                        'provider'      => array('VCHAR:255', ''),
                        'access_token'  => array('TEXT', ''),
                        'refresh_token' => array('TEXT', ''),
                        'expires_at'    => array('UINT:11', 0),
                    ),
                    'PRIMARY_KEY' => 'user_id',
                ),
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables' => array(
                $this->table_prefix . 'booskit_oauth_tokens',
            ),
        );
    }
}
