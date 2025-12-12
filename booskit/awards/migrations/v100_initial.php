<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_awards_json_url']) && $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_awards_users');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v33x\v330');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_awards_json_url', '')),
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_AWARDS_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_BOOSKIT_AWARDS_TITLE',
				array(
					'module_basename'	=> '\booskit\awards\acp\awards_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_awards_users' => array(
					'COLUMNS' => array(
						'award_id'			=> array('UINT', null, 'auto_increment'),
						'user_id'			=> array('UINT', 0),
						'award_definition_id' => array('VCHAR:255', ''),
						'issue_date'		=> array('TIMESTAMP', 0),
						'comment'			=> array('TEXT_UNI', ''),
						'issuer_user_id'	=> array('UINT', 0),
					),
					'PRIMARY_KEY' => 'award_id',
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
			array('config.remove', array('booskit_awards_json_url')),
			array('module.remove', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_AWARDS_TITLE'
			)),
			array('module.remove', array(
				'acp',
				'ACP_BOOSKIT_AWARDS_TITLE',
				array(
					'module_basename'	=> '\booskit\awards\acp\awards_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_awards_users',
			),
		);
	}
}
