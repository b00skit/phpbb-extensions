<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\migrations;

class v108_add_clipboard_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_disciplinary_clipboard_tpl']);
	}

	static public function depends_on()
	{
		return ['\booskit\disciplinary\migrations\v107_add_record_archive'];
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_disciplinary_clipboard_tpl', "{METADATA}\n\n{CONTENT}\n\n{PRIVATE}")),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_disciplinary_clipboard_tpl')),
		);
	}
}
