<?php
namespace booskit\phpbbapi\acp;

class main_info
{
    public function module()
    {
        return [
            'filename'  => '\\booskit\\phpbbapi\\acp\\main_module',
            'title'     => 'ACP_BOOSKIT_PHPBBAPI_TITLE',
            'version'   => '1.0.0',
            'modes'     => [
                'settings' => [
                    'title' => 'ACP_BOOSKIT_PHPBBAPI_SETTINGS',
                    'auth'  => 'ext_booskit/phpbbapi && acl_a_board',
                    'cat'   => ['ACP_BOOSKIT_PHPBBAPI_TITLE'],
                ],
            ],
        ];
    }
}