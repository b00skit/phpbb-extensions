<?php
/**
 *
 * @package booskit/mention
 * @license MIT
 *
 */

namespace booskit\mention\service;

class mention_manager
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\config\db_text */
	protected $config_text;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $table_prefix;

	/** @var array Cache of user groups */
	protected $user_groups_cache = [];

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		$table_prefix
	) {
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->table_prefix = $table_prefix;
		$this->config_text = new \phpbb\config\db_text($db, $table_prefix . 'config_text');
	}

	/**
	 * Is group mentioning enabled?
	 *
	 * @return bool
	 */
	public function is_group_mention_enabled()
	{
		return !empty($this->config['booskit_mention_group_enabled']);
	}

	/**
	 * Set group mentioning enabled status
	 *
	 * @param bool $enabled
	 */
	public function set_group_mention_enabled($enabled)
	{
		$this->config->set('booskit_mention_group_enabled', $enabled ? 1 : 0);
	}

	/**
	 * Get group-to-group mention matrix
	 * Format: [ source_group_id => [ target_group_id1, target_group_id2, ... ] ]
	 *
	 * @return array
	 */
	public function get_group_matrix()
	{
		$json = '';
		try {
			$json = (string) $this->config_text->get('booskit_mention_group_matrix');
		} catch (\Exception $e) {
			$json = '';
		}

		if (empty($json) || $json === '{}')
		{
			return [];
		}

		$data = json_decode($json, true);
		return is_array($data) ? $data : [];
	}

	/**
	 * Save group-to-group mention matrix
	 *
	 * @param array $matrix
	 */
	public function set_group_matrix(array $matrix)
	{
		$cleaned = [];
		foreach ($matrix as $source_gid => $target_gids)
		{
			$s_id = (int) $source_gid;
			if ($s_id > 0)
			{
				if ($target_gids === '*' || $target_gids === 'all')
				{
					$cleaned[$s_id] = '*';
				}
				else if (is_array($target_gids))
				{
					$targets = array_values(array_filter(array_map('intval', $target_gids), function ($tid) {
						return $tid > 0;
					}));
					$cleaned[$s_id] = $targets;
				}
			}
		}

		$this->config_text->set('booskit_mention_group_matrix', json_encode($cleaned));
	}

	/**
	 * Get all active group IDs for a user
	 *
	 * @param int $user_id
	 * @return int[]
	 */
	public function get_user_group_ids($user_id)
	{
		$user_id = (int) $user_id;
		if ($user_id <= 0)
		{
			return [];
		}

		if (isset($this->user_groups_cache[$user_id]))
		{
			return $this->user_groups_cache[$user_id];
		}

		$user_group_table = defined('USER_GROUP_TABLE') ? USER_GROUP_TABLE : $this->table_prefix . 'user_group';
		$sql = 'SELECT group_id
			FROM ' . $user_group_table . '
			WHERE user_id = ' . (int) $user_id . '
				AND user_pending = 0';
		$result = $this->db->sql_query($sql);

		$groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		$this->user_groups_cache[$user_id] = $groups;
		return $groups;
	}

	/**
	 * Can user mention all groups?
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function can_user_mention_all_groups($user_id)
	{
		if (!$this->is_group_mention_enabled())
		{
			return false;
		}

		$matrix = $this->get_group_matrix();
		// If nothing is defined, it is a free-for-all
		if (empty($matrix))
		{
			return true;
		}

		// When at least 1 group is defined, limit strictly to defined groups with wildcard
		$user_groups = $this->get_user_group_ids($user_id);
		foreach ($user_groups as $gid)
		{
			if (isset($matrix[$gid]) && ($matrix[$gid] === '*' || $matrix[$gid] === 'all'))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Can a user mention a specific target group?
	 * If nothing is defined then everyone can mention everyone (free-for-all).
	 * As soon as at least 1 group is defined, group mentioning is limited strictly to what is defined.
	 *
	 * @param int $user_id
	 * @param int $target_group_id
	 * @return bool
	 */
	public function can_user_mention_group($user_id, $target_group_id)
	{
		if (!$this->is_group_mention_enabled())
		{
			return false;
		}

		$target_group_id = (int) $target_group_id;
		if ($target_group_id <= 0)
		{
			return false;
		}

		$matrix = $this->get_group_matrix();

		// If nothing is defined, then everyone can mention everyone (free-for-all)
		if (empty($matrix))
		{
			return true;
		}

		$user_groups = $this->get_user_group_ids($user_id);
		if (empty($user_groups))
		{
			return false;
		}

		// As soon as at least 1 group is defined, only explicitly defined groups have mention rights
		foreach ($user_groups as $gid)
		{
			if (isset($matrix[$gid]))
			{
				$allowed = $matrix[$gid];
				if ($allowed === '*' || $allowed === 'all' || (is_array($allowed) && in_array($target_group_id, $allowed)))
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Add a group to the mention matrix
	 *
	 * @param int $source_group_id
	 * @param mixed $targets '*' or array of target IDs
	 */
	public function add_group_to_matrix($source_group_id, $targets = '*')
	{
		$source_group_id = (int) $source_group_id;
		if ($source_group_id <= 0)
		{
			return;
		}

		$matrix = $this->get_group_matrix();
		$matrix[$source_group_id] = $targets;
		$this->set_group_matrix($matrix);
	}

	/**
	 * Remove a group from the mention matrix
	 *
	 * @param int $source_group_id
	 */
	public function remove_group_from_matrix($source_group_id)
	{
		$source_group_id = (int) $source_group_id;
		$matrix = $this->get_group_matrix();
		if (isset($matrix[$source_group_id]))
		{
			unset($matrix[$source_group_id]);
			$this->set_group_matrix($matrix);
		}
	}


	/**
	 * Get list of all mentionable board groups (excluding Bots/Guests if desired)
	 *
	 * @return array
	 */
	public function get_all_mentionable_groups()
	{
		$groups_table = defined('GROUPS_TABLE') ? GROUPS_TABLE : $this->table_prefix . 'groups';
		$sql = 'SELECT group_id, group_name, group_type, group_colour
			FROM ' . $groups_table . "
			WHERE group_name <> 'BOTS'
			ORDER BY group_name ASC";
		$result = $this->db->sql_query($sql);

		$groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$gid = (int) $row['group_id'];
			// Format group display name using language if special group
			$name = $row['group_name'];
			if (isset($this->user->lang['G_' . $row['group_name']]))
			{
				$name = $this->user->lang['G_' . $row['group_name']];
			}

			$groups[] = [
				'group_id'     => $gid,
				'group_name'   => $name,
				'group_clean'  => strtolower($name),
				'group_type'   => (int) $row['group_type'],
				'group_colour' => !empty($row['group_colour']) ? '#' . $row['group_colour'] : '',
			];
		}
		$this->db->sql_freeresult($result);

		return $groups;
	}
}
