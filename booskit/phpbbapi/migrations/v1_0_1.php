<?php
namespace booskit\phpbbapi\migrations;

class v1_0_1 extends \phpbb\db\migration\migration
{
    static public function depends_on()
    {
        return ['\\booskit\\phpbbapi\\migrations\\v1_0_0'];
    }

    public function update_schema()
    {
        return [];
    }

    public function revert_schema()
    {
        return [];
    }

    public function update_data()
    {
        return [
            ['config.add', ['booskit_phpbbapi_allowed_forum_ids', '']],
        ];
    }
}
