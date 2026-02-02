<?php
/**
 *
 * @package booskit/commendations
 * @license MIT
 *
 */

namespace booskit\commendations\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_commendations');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_commendations_access_l1', '')),
			array('config.add', array('booskit_commendations_access_l2', '')),
			array('config.add', array('booskit_commendations_access_l3', '')),
			array('config.add', array('booskit_commendations_access_full', '')),
			array('config.add', array('booskit_commendations_access_view_global', '')),
			array('config.add', array('booskit_commendations_access_view', '')),
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_COMMENDATIONS_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_BOOSKIT_COMMENDATIONS_TITLE',
				array(
					'module_basename'	=> '\booskit\commendations\acp\main_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_commendations' => array(
					'COLUMNS' => array(
						'commendation_id'	=> array('UINT', null, 'auto_increment'),
						'user_id'			=> array('UINT', 0),
						'commendation_type' => array('VCHAR:255', ''),
						'commendation_date'	=> array('TIMESTAMP', 0),
						'character_name'    => array('VCHAR:255', ''),
						'reason'			=> array('TEXT_UNI', ''),
						'issuer_user_id'	=> array('UINT', 0),
						'bbcode_uid'        => array('VCHAR:8', ''),
						'bbcode_bitfield'   => array('VCHAR:255', ''),
						'bbcode_options'    => array('UINT', 7),
					),
					'PRIMARY_KEY' => 'commendation_id',
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
			array('config.remove', array('booskit_commendations_access_l1')),
			array('config.remove', array('booskit_commendations_access_l2')),
			array('config.remove', array('booskit_commendations_access_l3')),
			array('config.remove', array('booskit_commendations_access_full')),
			array('config.remove', array('booskit_commendations_access_view_global')),
			array('config.remove', array('booskit_commendations_access_view')),
			array('module.remove', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_BOOSKIT_COMMENDATIONS_TITLE'
			)),
			array('module.remove', array(
				'acp',
				'ACP_BOOSKIT_COMMENDATIONS_TITLE',
				array(
					'module_basename'	=> '\booskit\commendations\acp\main_module',
					'modes'				=> array('settings'),
				),
			)),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_commendations',
			),
		);
	}
}
