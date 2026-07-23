<?php
/**
 *
 * Topic Log Views extension for phpBB.
 *
 * @copyright (c) 2026 Booskit
 * @license MIT
 *
 */

namespace booskit\topiclogviews\migrations;

class v100_initial extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_topiclogviews_enable']);
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v330\v330');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_topiclogviews_enable', 1)),
			array('config.add', array('booskit_topiclogviews_log_guests', 0)),
			array('config.add', array('booskit_topiclogviews_exclude_bots', 1)),
			array('config.add', array('booskit_topiclogviews_session_once', 1)),
			array('config.add', array('booskit_topiclogviews_mod_only', 0)),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_TOPICLOGVIEWS_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_TOPICLOGVIEWS_TITLE',
				array(
					'module_basename' => '\booskit\topiclogviews\acp\main_module',
					'modes'           => array('settings'),
				)
			)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_topiclogviews_enable')),
			array('config.remove', array('booskit_topiclogviews_log_guests')),
			array('config.remove', array('booskit_topiclogviews_exclude_bots')),
			array('config.remove', array('booskit_topiclogviews_session_once')),
			array('config.remove', array('booskit_topiclogviews_mod_only')),

			array('module.remove', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_TOPICLOGVIEWS_TITLE'
			)),
		);
	}
}
