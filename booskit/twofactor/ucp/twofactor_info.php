<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\ucp;

class twofactor_info
{
    public function module()
    {
        return [
            'filename' => '\booskit\twofactor\ucp\twofactor_module',
            'title'    => 'UCP_BOOSKIT_TWOFACTOR_TITLE',
            'modes'    => [
                'manage' => [
                    'title' => 'UCP_BOOSKIT_TWOFACTOR_MANAGE',
                    'auth'  => 'ext_booskit/twofactor',
                    'cat'   => ['UCP_PROFILE'],
                ],
            ],
        ];
    }
}
