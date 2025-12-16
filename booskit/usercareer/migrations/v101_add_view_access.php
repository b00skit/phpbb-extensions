<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\migrations;

class v101_add_view_access extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_career_access_view']);
	}

	static public function depends_on()
	{
		return array('\booskit\usercareer\migrations\v100_initial');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_career_access_view', '')),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_career_access_view')),
		);
	}
}
