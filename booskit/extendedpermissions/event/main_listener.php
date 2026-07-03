<?php
/**
 *
 * Extended Permissions. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\extendedpermissions\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
	/** @var string The base name of the MCP Moderator Logs module. */
	const LOGS_MODULE = 'mcp_logs';

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var array|null Cached user groups */
	protected $user_group_ids = null;

	/** @var array|null Cached extension module auth strings from DB */
	protected $extension_acp_auths = null;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\config\config				$config		Config object
	 * @param \phpbb\auth\auth					$auth		Auth object
	 * @param \phpbb\request\request			$request	Request object
	 * @param \phpbb\template\template			$template	Template object
	 * @param \phpbb\db\driver\driver_interface	$db			Database driver
	 * @param \phpbb\user						$user		User object
	 */
	public function __construct(
		\phpbb\config\config $config,
		\phpbb\auth\auth $auth,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user
	) {
		$this->config   = $config;
		$this->auth     = $auth;
		$this->request  = $request;
		$this->template = $template;
		$this->db       = $db;
		$this->user     = $user;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents()
	{
		return [
			'core.permissions'                  => 'add_permissions',
			'core.module_auth'                  => 'check_module_auth',
			'core.modify_module_row'            => 'hide_mod_logs_tab',
			'core.mcp_global_f_read_auth_after' => 'restrict_mod_logs',
			'core.page_header'                  => 'hide_latest_logs',
		];
	}

	/**
	 * Register the custom administrative permission `a_extensions_manage`.
	 *
	 * @param \phpbb\event\data $event Event object
	 * @return void
	 */
	public function add_permissions($event)
	{
		$permissions = $event['permissions'];
		$permissions['a_extensions_manage'] = ['lang' => 'ACL_A_EXTENSIONS_MANAGE', 'cat' => 'misc'];
		$event['permissions'] = $permissions;
	}

	/**
	 * Dynamic override for extension ACP modules checking acl_a_board.
	 * Allows users with `a_extensions_manage` permission to access them.
	 *
	 * @param \phpbb\event\data $event Event object
	 * @return void
	 */
	public function check_module_auth($event)
	{
		$module_auth = $event['module_auth'];

		if (strpos($module_auth, 'acl_a_board') === false)
		{
			return;
		}

		$is_extension = false;

		// 1. Check if the auth string checks an extension explicitly (starts with ext_)
		if (strpos($module_auth, 'ext_') !== false)
		{
			$is_extension = true;
		}
		else
		{
			// 2. Fetch non-standard extension modules by finding fully-qualified basenames in the DB.
			if ($this->extension_acp_auths === null)
			{
				$this->extension_acp_auths = [];
				$sql = "SELECT module_auth FROM " . MODULES_TABLE . "
					WHERE module_class = 'acp'
						AND module_basename LIKE '%\\\\%'
						AND module_auth LIKE '%acl_a_board%'";
				$result = $this->db->sql_query($sql);
				while ($row = $this->db->sql_fetchrow($result))
				{
					$this->extension_acp_auths[] = trim($row['module_auth']);
				}
				$this->db->sql_freeresult($result);
			}

			if (in_array(trim($module_auth), $this->extension_acp_auths, true))
			{
				$is_extension = true;
			}
		}

		if ($is_extension)
		{
			// Prepend/OR the manage extensions check
			$module_auth = str_replace('acl_a_board', '(acl_a_board || acl_a_extensions_manage)', $module_auth);
			$event['module_auth'] = $module_auth;
		}
	}

	/**
	 * Hide the "Latest 5 logged actions" list on the MCP front page from
	 * users who do not belong to the allowed groups.
	 *
	 * @return void
	 */
	public function hide_latest_logs()
	{
		$last_actions_groups_str = $this->config['extendedpermissions_last_actions_groups'];
		if (empty($last_actions_groups_str))
		{
			return; // No group restriction configured -> default access
		}

		$allowed_groups = array_map('intval', explode(',', $last_actions_groups_str));
		if (empty($allowed_groups))
		{
			return;
		}

		if (!$this->current_user_in_groups($allowed_groups))
		{
			$this->template->assign_vars([
				'S_SHOW_LOGS' => false,
				'S_HAS_LOGS'  => false,
			]);
		}
	}

	/**
	 * Hide the Moderator Logs tab from the MCP navigation if the current
	 * user is restricted.
	 *
	 * @param \phpbb\event\data $event Event object
	 * @return void
	 */
	public function hide_mod_logs_tab($event)
	{
		if (!$this->logs_are_restricted())
		{
			return;
		}

		$row = $event['row'];

		if ($row['module_basename'] === self::LOGS_MODULE)
		{
			$module_row = $event['module_row'];
			$module_row['display'] = 0;
			$event['module_row'] = $module_row;
		}
	}

	/**
	 * Deny direct access to the MCP Moderator Logs for restricted users.
	 *
	 * @param \phpbb\event\data $event Event object
	 * @return void
	 */
	public function restrict_mod_logs($event)
	{
		if (!$this->logs_are_restricted())
		{
			return;
		}

		$mode = $event['mode'];
		$module_id = $this->request->variable('i', '');

		$is_logs = ($mode === 'forum_logs' || $mode === 'topic_logs' || in_array($module_id, ['logs', 'mcp_logs'], true));

		if (!$is_logs)
		{
			return;
		}

		send_status_line(403, 'Forbidden');
		trigger_error('NOT_AUTHORISED');
	}

	/**
	 * Check whether the current user is restricted from viewing moderator logs.
	 * Restricted if groups are specified and the user is NOT in any of them.
	 *
	 * @return bool
	 */
	protected function logs_are_restricted()
	{
		$mod_logs_groups_str = $this->config['extendedpermissions_mod_logs_groups'];
		if (empty($mod_logs_groups_str))
		{
			return false; // No group restriction configured -> default access
		}

		$allowed_groups = array_map('intval', explode(',', $mod_logs_groups_str));
		if (empty($allowed_groups))
		{
			return false;
		}

		return !$this->current_user_in_groups($allowed_groups);
	}

	/**
	 * Helper to check if current user is in a list of group IDs.
	 *
	 * @param array $allowed_groups Array of group IDs
	 * @return bool
	 */
	protected function current_user_in_groups(array $allowed_groups)
	{
		if (empty($allowed_groups))
		{
			return false;
		}

		if ($this->user_group_ids === null)
		{
			$this->user_group_ids = [];
			$user_id = (int) $this->user->data['user_id'];

			$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . '
				WHERE user_id = ' . $user_id . '
					AND user_pending = 0';
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->user_group_ids[] = (int) $row['group_id'];
			}
			$this->db->sql_freeresult($result);
		}

		return (bool) array_intersect($this->user_group_ids, $allowed_groups);
	}
}
