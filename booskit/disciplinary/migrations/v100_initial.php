<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_disciplinary_json_url']) && $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_disciplinary_users');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_disciplinary_json_url', '')),
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_DISCIPLINARY_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_BOOSKIT_DISCIPLINARY_TITLE',
				array(
					'module_basename'	=> '\booskit\disciplinary\acp\disciplinary_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_disciplinary_users' => array(
					'COLUMNS' => array(
						'record_id'			=> array('UINT', null, 'auto_increment'),
						'user_id'			=> array('UINT', 0),
						'disciplinary_type_id' => array('VCHAR:255', ''),
						'issue_date'		=> array('TIMESTAMP', 0),
						'reason'			=> array('TEXT_UNI', ''),
						'evidence'			=> array('TEXT_UNI', ''),
						'issuer_user_id'	=> array('UINT', 0),
					),
					'PRIMARY_KEY' => 'record_id',
					'KEYS' => array(
						'user_id' => array('INDEX', 'user_id'),
					),
				),
			),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_disciplinary_json_url')),
			array('module.remove', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_DISCIPLINARY_TITLE'
			)),
			array('module.remove', array(
				'acp',
				'ACP_BOOSKIT_DISCIPLINARY_TITLE',
				array(
					'module_basename'	=> '\booskit\disciplinary\acp\disciplinary_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_disciplinary_users',
			),
		);
	}
}
