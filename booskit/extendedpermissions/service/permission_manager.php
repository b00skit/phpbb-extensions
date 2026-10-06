<?php
/**
 *
 * Extended Permissions. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\extendedpermissions\service;

class permission_manager
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\auth\auth|null */
	protected $auth;

	/** @var \phpbb\extension\manager|null */
	protected $extension_manager;

	/** @var string */
	protected $table_perm_groups;

	/** @var string */
	protected $root_path;

	/** @var array|null Cached user groups */
	protected $cached_user_groups = [];

	/** @var array|null Cached permission groups */
	protected $cached_perm_groups = null;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\config\config                  $config
	 * @param \phpbb\db\driver\driver_interface     $db
	 * @param \phpbb\user                           $user
	 * @param \phpbb\auth\auth|null                 $auth
	 * @param \phpbb\extension\manager|null         $extension_manager
	 * @param string                                $table_perm_groups
	 * @param string                                $root_path
	 */
	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		$auth = null,
		$extension_manager = null,
		$table_perm_groups = '',
		$root_path = ''
	) {
		$this->config            = $config;
		$this->db                = $db;
		$this->user              = $user;
		$this->auth              = $auth;
		$this->extension_manager = $extension_manager;
		$this->table_perm_groups = !empty($table_perm_groups) ? $table_perm_groups : 'phpbb_booskit_extperm_groups';
		$this->root_path         = $root_path;
	}

	/**
	 * Clear internal caches.
	 *
	 * @return void
	 */
	public function clear_cache()
	{
		$this->cached_user_groups = [];
		$this->cached_perm_groups = null;
	}

	/**
	 * Get current permission system mode ('groups' or 'disabled').
	 *
	 * @return string
	 */
	public function get_perm_system()
	{
		return isset($this->config['booskit_extperm_perm_system']) ? $this->config['booskit_extperm_perm_system'] : 'groups';
	}

	/**
	 * Check if custom group permissions system is enabled.
	 *
	 * @return bool
	 */
	public function is_system_enabled()
	{
		return $this->get_perm_system() !== 'disabled';
	}

	/**
	 * Retrieve all permission groups from database.
	 *
	 * @param bool $refresh
	 * @return array
	 */
	public function get_permission_groups($refresh = false)
	{
		if ($this->cached_perm_groups !== null && !$refresh)
		{
			return $this->cached_perm_groups;
		}

		$sql = 'SELECT * FROM ' . $this->table_perm_groups . ' ORDER BY perm_group_id ASC';
		$result = $this->db->sql_query($sql);
		$groups = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$row['applies_to_array'] = !empty($row['applies_to']) ? array_map('intval', explode(',', $row['applies_to'])) : [];
			$row['permissions_array'] = !empty($row['permissions']) ? json_decode($row['permissions'], true) : [];

			if (!empty($row['allowed_extensions']))
			{
				$allowed = json_decode($row['allowed_extensions'], true);
				$row['allowed_extensions_array'] = is_array($allowed) ? $allowed : array_filter(array_map('trim', explode(',', $row['allowed_extensions'])));
			}
			else if (!empty($row['permissions_array']['extensions']) && is_array($row['permissions_array']['extensions']))
			{
				$row['allowed_extensions_array'] = array_keys(array_filter($row['permissions_array']['extensions']));
			}
			else
			{
				$row['allowed_extensions_array'] = [];
			}

			$row['can_manage_module'] = !empty($row['can_manage_module']) || !empty($row['permissions_array']['can_manage_module']);
			$groups[] = $row;
		}
		$this->db->sql_freeresult($result);

		$this->cached_perm_groups = $groups;
		return $groups;
	}

	/**
	 * Get a specific permission group by ID.
	 *
	 * @param int $perm_group_id
	 * @return array|null
	 */
	public function get_permission_group($perm_group_id)
	{
		$sql = 'SELECT * FROM ' . $this->table_perm_groups . ' WHERE perm_group_id = ' . (int) $perm_group_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			$row['applies_to_array'] = !empty($row['applies_to']) ? array_map('intval', explode(',', $row['applies_to'])) : [];
			$row['permissions_array'] = !empty($row['permissions']) ? json_decode($row['permissions'], true) : [];

			if (!empty($row['allowed_extensions']))
			{
				$allowed = json_decode($row['allowed_extensions'], true);
				$row['allowed_extensions_array'] = is_array($allowed) ? $allowed : array_filter(array_map('trim', explode(',', $row['allowed_extensions'])));
			}
			else if (!empty($row['permissions_array']['extensions']) && is_array($row['permissions_array']['extensions']))
			{
				$row['allowed_extensions_array'] = array_keys(array_filter($row['permissions_array']['extensions']));
			}
			else
			{
				$row['allowed_extensions_array'] = [];
			}

			$row['can_manage_module'] = !empty($row['can_manage_module']) || !empty($row['permissions_array']['can_manage_module']);
		}

		return $row ?: null;
	}

	/**
	 * Add a new permission group.
	 *
	 * @param string $group_name
	 * @param array|string $applies_to
	 * @param int $can_manage_module
	 * @param array|string $allowed_extensions
	 * @param array $permissions
	 * @return int Inserted ID
	 */
	public function add_permission_group($group_name, $applies_to, $allowed_extensions = [], $permissions = [])
	{
		// Support legacy parameter order if 3rd arg is int/bool (can_manage_module)
		if (is_numeric($allowed_extensions) || is_bool($allowed_extensions))
		{
			$allowed_extensions = !empty($permissions) ? $permissions : [];
			$permissions = func_num_args() > 4 ? func_get_arg(4) : [];
		}

		$applies_str = is_array($applies_to) ? implode(',', array_map('intval', array_filter($applies_to))) : (string) $applies_to;

		$allowed_array = is_array($allowed_extensions) ? array_values(array_filter($allowed_extensions)) : [];
		if (empty($allowed_array) && is_string($allowed_extensions) && !empty($allowed_extensions))
		{
			$decoded = json_decode($allowed_extensions, true);
			$allowed_array = is_array($decoded) ? $decoded : explode(',', $allowed_extensions);
		}
		$allowed_str = json_encode($allowed_array);

		if (empty($permissions))
		{
			$ext_map = [];
			foreach ($allowed_array as $ext)
			{
				$ext_map[$ext] = 1;
			}
			$permissions = [
				'extensions' => $ext_map,
			];
		}
		$perms_str = json_encode($permissions);

		$sql_ary = [
			'group_name'         => (string) $group_name,
			'applies_to'         => $applies_str,
			'can_manage_module'  => 0,
			'allowed_extensions' => $allowed_str,
			'permissions'        => $perms_str,
		];

		$sql = 'INSERT INTO ' . $this->table_perm_groups . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
		$this->db->sql_query($sql);

		$this->cached_perm_groups = null;
		return (int) $this->db->sql_nextid();
	}

	/**
	 * Update an existing permission group.
	 *
	 * @param int $perm_group_id
	 * @param string $group_name
	 * @param array|string $applies_to
	 * @param array|string $allowed_extensions
	 * @param array $permissions
	 * @return void
	 */
	public function update_permission_group($perm_group_id, $group_name, $applies_to, $allowed_extensions = [], $permissions = [])
	{
		// Support legacy parameter order if 4th arg is int/bool (can_manage_module)
		if (is_numeric($allowed_extensions) || is_bool($allowed_extensions))
		{
			$allowed_extensions = !empty($permissions) ? $permissions : [];
			$permissions = func_num_args() > 5 ? func_get_arg(5) : [];
		}

		$applies_str = is_array($applies_to) ? implode(',', array_map('intval', array_filter($applies_to))) : (string) $applies_to;

		$allowed_array = is_array($allowed_extensions) ? array_values(array_filter($allowed_extensions)) : [];
		if (empty($allowed_array) && is_string($allowed_extensions) && !empty($allowed_extensions))
		{
			$decoded = json_decode($allowed_extensions, true);
			$allowed_array = is_array($decoded) ? $decoded : explode(',', $allowed_extensions);
		}
		$allowed_str = json_encode($allowed_array);

		if (empty($permissions))
		{
			$ext_map = [];
			foreach ($allowed_array as $ext)
			{
				$ext_map[$ext] = 1;
			}
			$permissions = [
				'extensions' => $ext_map,
			];
		}
		$perms_str = json_encode($permissions);

		$sql_ary = [
			'group_name'         => (string) $group_name,
			'applies_to'         => $applies_str,
			'can_manage_module'  => 0,
			'allowed_extensions' => $allowed_str,
			'permissions'        => $perms_str,
		];

		$sql = 'UPDATE ' . $this->table_perm_groups . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE perm_group_id = ' . (int) $perm_group_id;
		$this->db->sql_query($sql);

		$this->cached_perm_groups = null;
	}

	/**
	 * Delete a permission group.
	 *
	 * @param int $perm_group_id
	 * @return void
	 */
	public function delete_permission_group($perm_group_id)
	{
		$sql = 'DELETE FROM ' . $this->table_perm_groups . ' WHERE perm_group_id = ' . (int) $perm_group_id;
		$this->db->sql_query($sql);

		$this->cached_perm_groups = null;
	}

	/**
	 * Retrieve all phpBB groups for dropdowns/selects.
	 *
	 * @return array
	 */
	public function get_phpbb_groups()
	{
		$groups_table = defined('GROUPS_TABLE') ? GROUPS_TABLE : 'phpbb_groups';
		$sql = 'SELECT group_id, group_name, group_type FROM ' . $groups_table . ' ORDER BY group_type DESC, group_name ASC';
		$result = $this->db->sql_query($sql);
		$groups = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$name = isset($this->user->lang['G_' . $row['group_name']]) ? $this->user->lang['G_' . $row['group_name']] : $row['group_name'];
			$groups[] = [
				'group_id'   => (int) $row['group_id'],
				'group_name' => $name,
			];
		}
		$this->db->sql_freeresult($result);

		return $groups;
	}

	/**
	 * Get group IDs for a given user.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function get_user_groups($user_id)
	{
		$user_id = (int) $user_id;
		if (isset($this->cached_user_groups[$user_id]))
		{
			return $this->cached_user_groups[$user_id];
		}

		$ug_table = defined('USER_GROUP_TABLE') ? USER_GROUP_TABLE : 'phpbb_user_group';
		$sql = 'SELECT group_id FROM ' . $ug_table . ' WHERE user_id = ' . $user_id . ' AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$groups = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		$this->cached_user_groups[$user_id] = $groups;
		return $groups;
	}

	/**
	 * Helper to parse comma-separated group IDs from configuration.
	 *
	 * @param string $config_key
	 * @return array
	 */
	public function get_config_groups($config_key)
	{
		$raw = isset($this->config[$config_key]) ? $this->config[$config_key] : '';
		if (empty($raw))
		{
			return [];
		}
		return array_map('intval', array_filter(array_map('trim', explode(',', $raw))));
	}

	/**
	 * Retrieve list of all available / enabled extensions with metadata.
	 *
	 * @return array
	 */
	public function get_available_extensions()
	{
		$extensions = [];

		if ($this->extension_manager !== null)
		{
			try
			{
				$all_enabled = $this->extension_manager->all_enabled();
				foreach ($all_enabled as $name => $location)
				{
					$display_name = $name;
					$description = '';
					$version = '';

					try
					{
						$md_manager = $this->extension_manager->create_extension_metadata_manager($name);
						$meta = $md_manager->get_metadata('all');
						$display_name = $md_manager->get_metadata('display-name') ?: $name;
						$description = isset($meta['description']) ? $meta['description'] : '';
						$version = isset($meta['version']) ? $meta['version'] : '';
					}
					catch (\Exception $e)
					{
						// Ignore metadata manager exceptions
					}

					$extensions[$name] = [
						'name'         => $name,
						'display_name' => $display_name,
						'description'  => $description,
						'version'      => $version,
					];
				}
			}
			catch (\Exception $e)
			{
				// Ignore extension manager exceptions
			}
		}

		if (empty($extensions))
		{
			$ext_table = defined('EXT_TABLE') ? EXT_TABLE : 'phpbb_ext';
			try
			{
				$sql = 'SELECT ext_name, ext_active FROM ' . $ext_table;
				$result = $this->db->sql_query($sql);
				while ($row = $this->db->sql_fetchrow($result))
				{
					$name = $row['ext_name'];
					$extensions[$name] = [
						'name'         => $name,
						'display_name' => $name,
						'description'  => '',
						'version'      => '',
					];
				}
				$this->db->sql_freeresult($result);
			}
			catch (\Exception $e)
			{
				// In test environment or unmigrated DB
			}
		}

		// Sort by display name
		$ext_list = array_values($extensions);
		usort($ext_list, function($a, $b) {
			return strcasecmp($a['display_name'], $b['display_name']);
		});

		return $ext_list;
	}

	/**
	 * Check if a user can access the "Custom Extensions" ACP module itself.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function can_user_access_module($user_id)
	{
		$user_id = (int) $user_id;

		// Board Founder or full administrator always has access
		if ($this->is_user_founder_or_admin($user_id))
		{
			return true;
		}

		// Check global module access groups
		$user_groups = $this->get_user_groups($user_id);
		$allowed_groups = $this->get_config_groups('booskit_extperm_module_access');
		if (!empty($allowed_groups) && array_intersect($user_groups, $allowed_groups))
		{
			return true;
		}

		return false;
	}

	/**
	 * Get all allowed extensions for a user.
	 * Returns null if user has full unrestricted access.
	 *
	 * @param int $user_id
	 * @return array|null
	 */
	public function get_user_allowed_extensions($user_id)
	{
		$user_id = (int) $user_id;

		// Board Founder or full administrator has full access
		if ($this->is_user_founder_or_admin($user_id))
		{
			return null;
		}

		// If permission system is disabled, grant full access
		if (!$this->is_system_enabled())
		{
			return null;
		}

		$user_groups = $this->get_user_groups($user_id);
		$allowed = [];

		$perm_groups = $this->get_permission_groups();
		foreach ($perm_groups as $pg)
		{
			if (empty($pg['applies_to_array']) || !array_intersect($user_groups, $pg['applies_to_array']))
			{
				continue;
			}

			if (!empty($pg['allowed_extensions_array']))
			{
				$allowed = array_merge($allowed, $pg['allowed_extensions_array']);
			}
		}

		return array_values(array_unique($allowed));
	}

	/**
	 * Check if a user can access a specific extension in the ACP.
	 *
	 * @param int $user_id
	 * @param string $ext_name
	 * @return bool
	 */
	public function can_user_access_extension($user_id, $ext_name)
	{
		$user_id = (int) $user_id;

		// Founder or full admin always has access
		if ($this->is_user_founder_or_admin($user_id))
		{
			return true;
		}

		// Custom Extensions module itself
		if ($ext_name === 'booskit/extendedpermissions')
		{
			return $this->can_user_access_module($user_id);
		}

		// If system is disabled, check general a_extensions_manage permission
		if (!$this->is_system_enabled())
		{
			return $this->auth ? (bool) $this->auth->acl_get('a_extensions_manage') : true;
		}

		$allowed = $this->get_user_allowed_extensions($user_id);
		if ($allowed === null)
		{
			return true;
		}

		return in_array($ext_name, $allowed, true);
	}

	/**
	 * Helper to check if a user is a founder or has full administrative permissions.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	protected function is_user_founder_or_admin($user_id)
	{
		// Check founder
		if (!empty($this->user->data['user_id']) && (int)$this->user->data['user_id'] === (int)$user_id)
		{
			if (defined('USER_FOUNDER') && isset($this->user->data['user_type']) && (int)$this->user->data['user_type'] === USER_FOUNDER)
			{
				return true;
			}
		}

		// Check acl_a_board
		if ($this->auth !== null && $this->auth->acl_get('a_board'))
		{
			return true;
		}

		return false;
	}
}
