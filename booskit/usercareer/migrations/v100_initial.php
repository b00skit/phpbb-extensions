<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_career_json_url']) && $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_career_notes');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_career_json_url', '')),
			array('config.add', array('booskit_career_access_l1', '')),
			array('config.add', array('booskit_career_access_l2', '')),
			array('config.add', array('booskit_career_access_l3', '')),
			array('config.add', array('booskit_career_access_full', '')),
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_CAREER_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_BOOSKIT_CAREER_TITLE',
				array(
					'module_basename'	=> '\booskit\usercareer\acp\main_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_career_notes' => array(
					'COLUMNS' => array(
						'note_id'			=> array('UINT', null, 'auto_increment'),
						'user_id'			=> array('UINT', 0),
						'career_type_id'    => array('VCHAR:255', ''),
						'note_date'		    => array('TIMESTAMP', 0),
						'description'		=> array('TEXT_UNI', ''),
						'issuer_user_id'	=> array('UINT', 0),
					),
					'PRIMARY_KEY' => 'note_id',
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
			array('config.remove', array('booskit_career_json_url')),
			array('config.remove', array('booskit_career_access_l1')),
			array('config.remove', array('booskit_career_access_l2')),
			array('config.remove', array('booskit_career_access_l3')),
			array('config.remove', array('booskit_career_access_full')),
			array('module.remove', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_CAREER_TITLE'
			)),
			array('module.remove', array(
				'acp',
				'ACP_BOOSKIT_CAREER_TITLE',
				array(
					'module_basename'	=> '\booskit\usercareer\acp\main_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_career_notes',
			),
		);
	}
}
