<?php
namespace booskit\gtawoauth\migrations;

class add_state_storage extends \phpbb\db\migration\migration
{
    static public function depends_on()
    {
        return array('\booskit\gtawoauth\migrations\add_token_storage');
    }

    public function update_schema()
    {
        return array(
            'add_tables' => array(
                $this->table_prefix . 'booskit_oauth_states' => array(
                    'COLUMNS' => array(
                        'state'         => array('VCHAR:255', ''),
                        'user_id'       => array('UINT', 0),
                        'expires_at'    => array('UINT:11', 0),
                    ),
                    'PRIMARY_KEY' => 'state',
                ),
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables' => array(
                $this->table_prefix . 'booskit_oauth_states',
            ),
        );
    }
}
