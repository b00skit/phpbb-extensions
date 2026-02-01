<?php
/**
 *
 * @package booskit/icdisciplinary
 * @license MIT
 *
 */

namespace booskit\icdisciplinary\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_icdisciplinary_source']) && $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_ic_records');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_icdisciplinary_json_url', '')),
			array('config.add', array('booskit_icdisciplinary_source', 'url')),
			array('config.add', array('booskit_icdisciplinary_access_l1', '')),
			array('config.add', array('booskit_icdisciplinary_access_l2', '')),
			array('config.add', array('booskit_icdisciplinary_access_full', '')),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_ICDISCIPLINARY_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_BOOSKIT_ICDISCIPLINARY_TITLE',
				array(
					'module_basename'	=> '\booskit\icdisciplinary\acp\icdisciplinary_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_ic_characters' => array(
					'COLUMNS' => array(
						'character_id'		=> array('UINT', null, 'auto_increment'),
						'user_id'			=> array('UINT', 0),
						'character_name'	=> array('VCHAR:255', ''),
						'is_archived'		=> array('BOOL', 0),
					),
					'PRIMARY_KEY' => 'character_id',
					'KEYS' => array(
						'user_id' => array('INDEX', 'user_id'),
					),
				),
				$this->table_prefix . 'booskit_ic_records' => array(
					'COLUMNS' => array(
						'record_id'			=> array('UINT', null, 'auto_increment'),
						'character_id'		=> array('UINT', 0),
						'disciplinary_type_id' => array('VCHAR:255', ''),
						'issue_date'		=> array('TIMESTAMP', 0),
						'reason'			=> array('TEXT_UNI', ''),
						'evidence'			=> array('TEXT_UNI', ''),
						'issuer_user_id'	=> array('UINT', 0),

						'reason_bbcode_uid'      => array('VCHAR:8', ''),
						'reason_bbcode_bitfield' => array('VCHAR:255', ''),
						'reason_bbcode_options'  => array('UINT', 7),
						'evidence_bbcode_uid'      => array('VCHAR:8', ''),
						'evidence_bbcode_bitfield' => array('VCHAR:255', ''),
						'evidence_bbcode_options'  => array('UINT', 7),
					),
					'PRIMARY_KEY' => 'record_id',
					'KEYS' => array(
						'character_id' => array('INDEX', 'character_id'),
					),
				),
				$this->table_prefix . 'booskit_ic_definitions' => array(
					'COLUMNS' => array(
						'def_id'		=> array('UINT', null, 'auto_increment'),
						'disc_id'		=> array('VCHAR:255', ''),
						'disc_name'		=> array('VCHAR:255', ''),
						'disc_desc'		=> array('TEXT', ''),
						'disc_color'	=> array('VCHAR:20', ''),
						'access_level'	=> array('UINT', 0),
					),
					'PRIMARY_KEY' => 'def_id',
				),
			),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_icdisciplinary_json_url')),
			array('config.remove', array('booskit_icdisciplinary_source')),
			array('config.remove', array('booskit_icdisciplinary_access_l1')),
			array('config.remove', array('booskit_icdisciplinary_access_l2')),
			array('config.remove', array('booskit_icdisciplinary_access_full')),

			array('module.remove', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_ICDISCIPLINARY_TITLE'
			)),
			array('module.remove', array(
				'acp',
				'ACP_BOOSKIT_ICDISCIPLINARY_TITLE',
				array(
					'module_basename'	=> '\booskit\icdisciplinary\acp\icdisciplinary_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_ic_records',
				$this->table_prefix . 'booskit_ic_characters',
				$this->table_prefix . 'booskit_ic_definitions',
			),
		);
	}
}
