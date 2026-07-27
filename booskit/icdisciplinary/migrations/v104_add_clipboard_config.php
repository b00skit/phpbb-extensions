<?php
/**
 *
 * @package booskit/icdisciplinary
 * @license MIT
 *
 */

namespace booskit\icdisciplinary\migrations;

class v104_add_clipboard_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_icdisciplinary_clipboard_tpl']);
	}

	static public function depends_on()
	{
		return ['\booskit\icdisciplinary\migrations\v103_add_record_archive'];
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_icdisciplinary_clipboard_tpl', "{METADATA}\n\n{CONTENT}\n\n{PRIVATE}")),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_icdisciplinary_clipboard_tpl')),
		);
	}
}
