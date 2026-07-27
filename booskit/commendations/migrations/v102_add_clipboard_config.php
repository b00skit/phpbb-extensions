<?php
/**
 *
 * @package booskit/commendations
 * @license MIT
 *
 */

namespace booskit\commendations\migrations;

class v102_add_clipboard_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_commendations_clipboard_tpl']);
	}

	static public function depends_on()
	{
		return array('\booskit\commendations\migrations\v101_permission_groups');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_commendations_clipboard_tpl', "{METADATA}\n\n{CONTENT}")),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_commendations_clipboard_tpl')),
		);
	}
}
