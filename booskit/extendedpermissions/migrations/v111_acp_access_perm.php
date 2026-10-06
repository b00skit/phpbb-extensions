<?php
/**
 *
 * Extended Permissions. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\extendedpermissions\migrations;

class v111_acp_access_perm extends \phpbb\db\migration\migration
{
	/**
	 * {@inheritDoc}
	 */
	public static function depends_on()
	{
		return ['\booskit\extendedpermissions\migrations\v110_custom_extensions'];
	}

	/**
	 * {@inheritDoc}
	 */
	public function update_data()
	{
		return [
			['permission.add', ['a_acp_access', true]],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function revert_data()
	{
		return [
			['permission.remove', ['a_acp_access']],
		];
	}
}
