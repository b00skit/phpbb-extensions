<?php
/**
 *
 * Send As. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\sendas\migrations;

class add_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['sendas_show_original']);
	}

	public static function depends_on()
	{
		return ['\booskit\sendas\migrations\install_sendas_table'];
	}

	public function update_data()
	{
		return [
			['config.add', ['sendas_show_original', 1]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['sendas_show_original']],
		];
	}
}
