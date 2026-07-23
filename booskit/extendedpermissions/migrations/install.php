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

class install extends \phpbb\db\migration\migration
{
	/**
	 * {@inheritDoc}
	 */
	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	/**
	 * {@inheritDoc}
	 */
	public function update_data()
	{
		return [
			// Add custom administration permission 'a_extensions_manage' (global permission, copy defaults from 'a_board')
			['permission.add', ['a_extensions_manage', true, 'a_board']],

			// Add custom moderator permissions (global moderator permissions, disabled by default)
			['permission.add', ['m_mod_logs', true]],
			['permission.add', ['m_last_actions', true]],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function revert_data()
	{
		return [
			['permission.remove', ['a_extensions_manage']],
			['permission.remove', ['m_mod_logs']],
			['permission.remove', ['m_last_actions']],
		];
	}
}

