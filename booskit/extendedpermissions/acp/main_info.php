<?php
/**
 *
 * Extended Permissions. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\extendedpermissions\acp;

class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\\booskit\\extendedpermissions\\acp\\main_module',
			'title'		=> 'ACP_EXTENDEDPERMISSIONS_TITLE',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_EXTENDEDPERMISSIONS_SETTINGS',
					'auth'	=> 'acl_a_board || acl_a_extensions_manage',
					'cat'	=> array('ACP_CAT_DOT_MODS'),
				),
			),
		);
	}
}
