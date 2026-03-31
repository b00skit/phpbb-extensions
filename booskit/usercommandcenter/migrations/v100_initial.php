<?php
/**
 *
 * @package booskit/usercommandcenter
 * @license MIT
 *
 */

namespace booskit\usercommandcenter\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_ucc_enabled']);
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_ucc_enabled', 1)),
			array('config.add', array('booskit_ucc_allowed_groups', '')),
			array('config.add', array('booskit_ucc_include_awards', 1)),
			array('config.add', array('booskit_ucc_include_career', 1)),
			array('config.add', array('booskit_ucc_include_commendations', 1)),
			array('config.add', array('booskit_ucc_include_disciplinary', 1)),
			array('config.add', array('booskit_ucc_include_ic_disciplinary', 1)),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_UCC_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_BOOSKIT_UCC_TITLE',
				array(
					'module_basename'	=> '\booskit\usercommandcenter\acp\main_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_ucc_enabled')),
			array('config.remove', array('booskit_ucc_allowed_groups')),
			array('config.remove', array('booskit_ucc_include_awards')),
			array('config.remove', array('booskit_ucc_include_career')),
			array('config.remove', array('booskit_ucc_include_commendations')),
			array('config.remove', array('booskit_ucc_include_disciplinary')),
			array('config.remove', array('booskit_ucc_include_ic_disciplinary')),

			array('module.remove', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_UCC_TITLE'
			)),
			array('module.remove', array(
				'acp',
				'ACP_BOOSKIT_UCC_TITLE',
				array(
					'module_basename'	=> '\booskit\usercommandcenter\acp\main_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}
}
