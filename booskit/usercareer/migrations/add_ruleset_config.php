<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\migrations;

class add_ruleset_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_career_ruleset_uid']);
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_career_ruleset_uid', '')),
			array('config.add', array('booskit_career_ruleset_bitfield', '')),
			array('config.add', array('booskit_career_ruleset_options', 7)),
		);
	}
}
