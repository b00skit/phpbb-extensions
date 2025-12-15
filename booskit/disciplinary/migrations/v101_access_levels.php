<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\migrations;

class v101_access_levels extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\booskit\disciplinary\migrations\v100_initial');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_disciplinary_access_l1', '')),
			array('config.add', array('booskit_disciplinary_access_l2', '')),
			array('config.add', array('booskit_disciplinary_access_l3', '')),
			array('config.add', array('booskit_disciplinary_access_full', '')),
		);
	}
}
