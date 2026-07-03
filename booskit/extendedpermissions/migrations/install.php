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
	public function effectively_installed()
	{
		return isset($this->config['extendedpermissions_mod_logs_groups']);
	}

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
			// Group configs stored as comma-separated group IDs. Default empty (no restriction).
			['config.add', ['extendedpermissions_mod_logs_groups', '']],
			['config.add', ['extendedpermissions_last_actions_groups', '']],

			// Add custom administration permission 'a_extensions_manage' (global permission, copy defaults from 'a_board')
			['permission.add', ['a_extensions_manage', true, 'a_board']],

			// Add ACP module under Extensions tab.
			['module.add', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_EXTENDEDPERMISSIONS_TITLE']],
			['module.add', ['acp', 'ACP_EXTENDEDPERMISSIONS_TITLE', [
				'module_basename'	=> '\\booskit\\extendedpermissions\\acp\\main_module',
				'module_langname'	=> 'ACP_EXTENDEDPERMISSIONS_SETTINGS',
				'module_mode'		=> 'settings',
				'module_auth'		=> 'acl_a_board || acl_a_extensions_manage',
			]]],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function revert_data()
	{
		return [
			['module.remove', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_EXTENDEDPERMISSIONS_TITLE']],
			['permission.remove', ['a_extensions_manage']],
			['config.remove', ['extendedpermissions_mod_logs_groups']],
			['config.remove', ['extendedpermissions_last_actions_groups']],
		];
	}
}
