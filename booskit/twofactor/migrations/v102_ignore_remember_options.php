<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

namespace booskit\twofactor\migrations;

class v102_ignore_remember_options extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['booskit_2fa_ucp_ignore_remember']);
    }

    static public function depends_on()
    {
        return array('\booskit\twofactor\migrations\v101_pending_logins');
    }

    public function update_data()
    {
        return array(
            array('config.add', array('booskit_2fa_ucp_ignore_remember', 0)),
            array('config.add', array('booskit_2fa_mcp_ignore_remember', 0)),
            array('config.add', array('booskit_2fa_acp_ignore_remember', 0)),
        );
    }

    public function revert_data()
    {
        return array(
            array('config.remove', array('booskit_2fa_ucp_ignore_remember')),
            array('config.remove', array('booskit_2fa_mcp_ignore_remember')),
            array('config.remove', array('booskit_2fa_acp_ignore_remember')),
        );
    }
}
