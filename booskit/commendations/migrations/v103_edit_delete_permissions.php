<?php
/**
 *
 * @package booskit/commendations
 * @license MIT
 *
 */

namespace booskit\commendations\migrations;

class v103_edit_delete_permissions extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['booskit_commendations_perm_v103']);
	}

	static public function depends_on()
	{
		return array('\booskit\commendations\migrations\v102_add_clipboard_config');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('booskit_commendations_perm_v103', 1)),
			array('custom', array(array($this, 'update_permission_groups_data'))),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('booskit_commendations_perm_v103')),
		);
	}

	public function update_permission_groups_data()
	{
		$table_name = $this->table_prefix . 'booskit_commendations_perm_groups';
		if (!$this->db_tools->sql_table_exists($table_name))
		{
			return;
		}

		$sql = 'SELECT perm_group_id, permissions FROM ' . $table_name;
		$result = $this->db->sql_query($sql);
		$groups = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$groups[] = $row;
		}
		$this->db->sql_freeresult($result);

		foreach ($groups as $group)
		{
			$perms = !empty($group['permissions']) ? json_decode($group['permissions'], true) : array();
			if (is_array($perms))
			{
				$modified = false;
				if (!empty($perms['submit']))
				{
					if (!isset($perms['edit_own'])) { $perms['edit_own'] = 1; $modified = true; }
					if (!isset($perms['delete_own'])) { $perms['delete_own'] = 1; $modified = true; }
					if (!isset($perms['edit'])) { $perms['edit'] = 1; $modified = true; }
					if (!isset($perms['delete'])) { $perms['delete'] = 1; $modified = true; }
				}
				if ($modified)
				{
					$sql = 'UPDATE ' . $table_name . '
						SET permissions = \'' . $this->db->sql_escape(json_encode($perms)) . '\'
						WHERE perm_group_id = ' . (int) $group['perm_group_id'];
					$this->db->sql_query($sql);
				}
			}
		}
	}
}
