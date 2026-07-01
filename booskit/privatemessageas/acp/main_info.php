<?php
/**
 *
 * Send As. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\privatemessageas\acp;

/**
 * Private Message As ACP module info.
 */
class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\\booskit\\privatemessageas\\acp\\main_module',
			'title' => 'ACP_PRIVATEMESSAGEAS_TITLE',
			'modes' => array(
				'settings' => array(
					'title' => 'ACP_PRIVATEMESSAGEAS_SETTINGS',
					'auth' => 'acl_a_board',
					'cat' => array('ACP_CAT_DOT_MODS'),
				),
			),
		);
	}
}
