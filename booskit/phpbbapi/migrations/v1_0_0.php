<?php
namespace booskit\phpbbapi\migrations;

class v1_0_0 extends \phpbb\db\migration\migration
{
    static public function depends_on()
    {
        return ['\phpbb\db\migration\data\v310\dev'];
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
            // Config: global API key (empty by default)
            ['config.add', ['booskit_phpbbapi_key', '']],

            // Add ACP module under Extensions
            ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_BOOSKIT_PHPBBAPI_TITLE'
            ]],
            ['module.add', [
                'acp',
                'ACP_BOOSKIT_PHPBBAPI_TITLE',
                [
                    'module_basename' => '\\booskit\\phpbbapi\\acp\\main_module',
                    'modes'          => ['settings'],
                ]
            ]],
        ];
    }
}