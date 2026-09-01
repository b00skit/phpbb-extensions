<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\acp;

class twofactor_info
{
    public function module()
    {
        return [
            'filename' => '\booskit\twofactor\acp\twofactor_module',
            'title'    => 'ACP_BOOSKIT_TWOFACTOR_TITLE',
            'modes'    => [
                'settings' => [
                    'title' => 'ACP_BOOSKIT_TWOFACTOR_SETTINGS',
                    'auth'  => 'ext_booskit/twofactor && acl_a_board',
                    'cat'   => ['ACP_BOOSKIT_TWOFACTOR_TITLE'],
                ],
            ],
        ];
    }
}
