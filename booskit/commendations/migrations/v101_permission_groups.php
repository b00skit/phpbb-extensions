<?php
/**
 *
 * @package booskit/commendations
 * @license MIT
 *
 */

namespace booskit\commendations\migrations;

class v101_permission_groups extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_commendations_perm_system']) && $this->db_tools->sql_table_exists($this->table_prefix . 'booskit_commendations_perm_groups');
	}

	static public function depends_on()
	{
		return array('\booskit\commendations\migrations\v100_initial');
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'booskit_commendations_perm_groups' => array(
					'COLUMNS' => array(
						'perm_group_id'		=> array('UINT', null, 'auto_increment'),
						'group_name'		=> array('VCHAR:255', ''),
						'applies_to'		=> array('TEXT_UNI', ''),
						'power_over_all'	=> array('TINT:1', 0),
						'power_over_self'	=> array('TINT:1', 0),
						'power_over_groups'	=> array('TEXT_UNI', ''),
						'exclude_groups'	=> array('TEXT_UNI', ''),
						'permissions'		=> array('TEXT_UNI', ''),
					),
					'PRIMARY_KEY' => 'perm_group_id',
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'booskit_commendations_perm_groups',
			),
		);
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_commendations_perm_system', 'legacy')),
			array('config.add', array('booskit_commendations_enable_public_posting', 0)),
			array('config.add', array('booskit_commendations_public_posting_mode', 'forum')),
			array('config.add', array('booskit_commendations_public_posting_forum_id', 0)),
			array('config.add', array('booskit_commendations_public_posting_post_id', 0)),
			array('config.add', array('booskit_commendations_public_posting_poster_id', 0)),
			array('config.add', array('booskit_commendations_public_posting_subject_tpl', 'Commendation: {CHARACTER_NAME}')),
			array('config.add', array('booskit_commendations_public_posting_body_tpl', '')),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_commendations_perm_system')),
			array('config.remove', array('booskit_commendations_enable_public_posting')),
			array('config.remove', array('booskit_commendations_public_posting_mode')),
			array('config.remove', array('booskit_commendations_public_posting_forum_id')),
			array('config.remove', array('booskit_commendations_public_posting_post_id')),
			array('config.remove', array('booskit_commendations_public_posting_poster_id')),
			array('config.remove', array('booskit_commendations_public_posting_subject_tpl')),
			array('config.remove', array('booskit_commendations_public_posting_body_tpl')),
		);
	}
}
