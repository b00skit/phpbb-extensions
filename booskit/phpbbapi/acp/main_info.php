<?php
namespace booskit\phpbbapi\acp;

class main_info
{
    public function module()
    {
        return [
            'filename'  => '\\booskit\\phpbbapi\\acp\\main_module',
            'title'     => 'booskit phpbbapi',
            'version'   => '1.0.0',
            'modes'     => [
                'settings' => [
                    'title' => 'phpbbapi settings',
                    'auth'  => 'ext_booskit/phpbbapi && acl_a_board',
                    'cat'   => ['booskit phpbbapi'],
                ],
            ],
        ];
    }
}