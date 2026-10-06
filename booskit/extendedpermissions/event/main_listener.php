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

	/** @var \booskit\extendedpermissions\service\permission_manager|null */
	protected $permission_manager;

	/** @var array|null Cached extension module auth strings from DB */
	protected $extension_acp_auths = null;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\config\config                                      $config             Config object
	 * @param \phpbb\auth\auth                                          $auth               Auth object
	 * @param \phpbb\request\request                                    $request            Request object
	 * @param \phpbb\template\template                                  $template           Template object
	 * @param \phpbb\db\driver\driver_interface                         $db                 Database driver
	 * @param \phpbb\user                                               $user               User object
	 * @param \booskit\extendedpermissions\service\permission_manager|null $permission_manager  Permission manager service
	 */
	public function __construct(
		\phpbb\config\config $config,
		\phpbb\auth\auth $auth,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		$permission_manager = null
	) {
		$this->config             = $config;
		$this->auth               = $auth;
		$this->request            = $request;
		$this->template           = $template;
		$this->db                 = $db;
		$this->user               = $user;
		$this->permission_manager = $permission_manager;
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
			'core.user_setup'                   => 'user_setup',
			'core.adm_page_header'              => 'restrict_acp_extension_access',
		];
	}

	/**
	 * Load language files on user setup and dynamically set auth cache if member of allowed group.
	 *
	 * @param \phpbb\event\data $event
	 * @return void
	 */
	public function user_setup($event)
	{
		$this->user->add_lang_ext('booskit/extendedpermissions', 'permissions_extendedpermissions');
		$this->user->add_lang_ext('booskit/extendedpermissions', 'info_acp_custom_extensions');

		if ($this->permission_manager !== null && !empty($this->user->data['user_id']))
		{
			$user_id = (int) $this->user->data['user_id'];
			// If user can access custom extensions module or any extension, ensure they have ACP access
			if ($this->permission_manager->can_user_access_module($user_id) || $this->permission_manager->get_user_allowed_extensions($user_id) !== [])
			{
				if (is_array($this->auth->cache))
				{
					if (!isset($this->auth->cache[0]))
					{
						$this->auth->cache[0] = [];
					}
					$this->auth->cache[0]['a_'] = 1;
				}
			}
		}
	}

	/**
	 * Register custom permissions:
	 * - `a_extensions_manage`: Administrative permission to manage extensions in ACP.
	 * - `m_mod_logs`: Moderator permission to view Moderator Logs in MCP (disabled by default).
	 * - `m_last_actions`: Moderator permission to view Latest 5 Actions in MCP (disabled by default).
	 *
	 * @param \phpbb\event\data $event Event object
	 * @return void
	 */
	public function add_permissions($event)
	{
		$permissions = $event['permissions'];
		$permissions['a_extensions_manage'] = ['lang' => 'ACL_A_EXTENSIONS_MANAGE', 'cat' => 'misc'];
		$permissions['a_acp_access']        = ['lang' => 'ACL_A_ACP_ACCESS', 'cat' => 'settings'];
		$permissions['m_mod_logs']          = ['lang' => 'ACL_M_MOD_LOGS', 'cat' => 'misc'];
		$permissions['m_last_actions']      = ['lang' => 'ACL_M_LAST_ACTIONS', 'cat' => 'misc'];
		$event['permissions'] = $permissions;
	}

	/**
	 * Dynamic override for extension ACP modules checking acl_a_board.
	 * Allows users with access permission (via custom permission groups) to access them.
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
		$ext_name = '';

		// 1. Check if the auth string checks an extension explicitly (starts with ext_)
		if (preg_match('#ext_([a-zA-Z0-9_\-]+/[a-zA-Z0-9_\-]+)#', $module_auth, $matches))
		{
			$is_extension = true;
			$ext_name = $matches[1];
		}
		else
		{
			// 2. Fetch non-standard extension modules by finding fully-qualified basenames in the DB.
			if ($this->extension_acp_auths === null)
			{
				$this->extension_acp_auths = [];
				$sql = "SELECT module_auth, module_basename FROM " . MODULES_TABLE . "
					WHERE module_class = 'acp'
						AND module_basename LIKE '%\\\\%'
						AND module_auth LIKE '%acl_a_board%'";
				$result = $this->db->sql_query($sql);
				while ($row = $this->db->sql_fetchrow($result))
				{
					$this->extension_acp_auths[trim($row['module_auth'])] = $this->extract_extension_from_basename($row['module_basename']);
				}
				$this->db->sql_freeresult($result);
			}

			$trimmed_auth = trim($module_auth);
			if (isset($this->extension_acp_auths[$trimmed_auth]))
			{
				$is_extension = true;
				$ext_name = $this->extension_acp_auths[$trimmed_auth];
			}
		}

		if ($is_extension)
		{
			$can_access = true;
			if ($this->permission_manager !== null)
			{
				$user_id = !empty($this->user->data['user_id']) ? (int) $this->user->data['user_id'] : 0;
				if ($ext_name === 'booskit/extendedpermissions')
				{
					$can_access = $this->permission_manager->can_user_access_module($user_id);
				}
				else if (!empty($ext_name))
				{
					$can_access = $this->permission_manager->can_user_access_extension($user_id, $ext_name);
				}
			}

			if ($can_access)
			{
				// Prepend/OR the manage extensions check
				$module_auth = str_replace('acl_a_board', '(acl_a_board || acl_a_extensions_manage)', $module_auth);
				$event['module_auth'] = $module_auth;

				// Override valid_tokens for phpBB's module_auth eval
				if (isset($event['valid_tokens']) && is_array($event['valid_tokens']))
				{
					$valid_tokens = $event['valid_tokens'];
					$valid_tokens = ['acl_a_board' => '1'] + $valid_tokens;
					$event['valid_tokens'] = $valid_tokens;
				}
			}
		}
	}

	/**
	 * Hide the "Latest 5 logged actions" list on the MCP front page if the current
	 * user does not have the `m_last_actions` moderator permission.
	 *
	 * @return void
	 */
	public function hide_latest_logs()
	{
		$this->user->add_lang_ext('booskit/extendedpermissions', 'permissions_extendedpermissions');

		if (!$this->auth->acl_get('m_last_actions'))
		{
			$this->template->assign_vars([
				'S_SHOW_LOGS' => false,
				'S_HAS_LOGS'  => false,
			]);
		}
	}

	/**
	 * Hide Moderator Logs tab from the MCP navigation if restricted,
	 * and hide extension ACP tabs/modules if user is not authorized.
	 *
	 * @param \phpbb\event\data $event Event object
	 * @return void
	 */
	public function hide_mod_logs_tab($event)
	{
		$row = $event['row'];

		if (!empty($row['module_basename']) && $row['module_basename'] === self::LOGS_MODULE)
		{
			if ($this->logs_are_restricted())
			{
				$module_row = $event['module_row'];
				$module_row['display'] = 0;
				$event['module_row'] = $module_row;
			}
			return;
		}

		// Check ACP extension module visibility
		if ($this->permission_manager !== null && !empty($row['module_basename']))
		{
			$ext_name = $this->extract_extension_from_basename($row['module_basename']);
			if (!empty($ext_name))
			{
				$user_id = !empty($this->user->data['user_id']) ? (int) $this->user->data['user_id'] : 0;
				$allowed = true;

				if ($ext_name === 'booskit/extendedpermissions')
				{
					$allowed = $this->permission_manager->can_user_access_module($user_id);
				}
				else
				{
					$allowed = $this->permission_manager->can_user_access_extension($user_id, $ext_name);
				}

				if (!$allowed)
				{
					$module_row = $event['module_row'];
					$module_row['display'] = 0;
					$event['module_row'] = $module_row;
				}
			}
		}
	}

	/**
	 * Deny direct access to unauthorized ACP extension modules.
	 *
	 * @param \phpbb\event\data $event
	 * @return void
	 */
	public function restrict_acp_extension_access($event)
	{
		if ($this->permission_manager === null)
		{
			return;
		}

		$user_id = !empty($this->user->data['user_id']) ? (int) $this->user->data['user_id'] : 0;

		// Board Founder or full admin skips restriction
		if ($this->auth->acl_get('a_board') || (!empty($this->user->data['user_type']) && defined('USER_FOUNDER') && (int)$this->user->data['user_type'] === USER_FOUNDER))
		{
			return;
		}

		$module_param = $this->request->variable('i', '');
		if (empty($module_param))
		{
			return;
		}

		$ext_name = '';
		if (strpos($module_param, '\\') !== false)
		{
			$ext_name = $this->extract_extension_from_basename($module_param);
		}
		else if (strpos($module_param, '-') !== false)
		{
			$converted = str_replace('-', '\\', $module_param);
			$ext_name = $this->extract_extension_from_basename($converted);
		}

		if (!empty($ext_name))
		{
			$allowed = ($ext_name === 'booskit/extendedpermissions')
				? $this->permission_manager->can_user_access_module($user_id)
				: $this->permission_manager->can_user_access_extension($user_id, $ext_name);

			if (!$allowed)
			{
				send_status_line(403, 'Forbidden');
				trigger_error('NOT_AUTHORISED');
			}
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
	 * Restricted if the user does NOT have the `m_mod_logs` permission.
	 *
	 * @return bool
	 */
	protected function logs_are_restricted()
	{
		return !$this->auth->acl_get('m_mod_logs');
	}

	/**
	 * Extract extension vendor/name from a module basename.
	 * e.g. '\booskit\disciplinary\acp\disciplinary_module' -> 'booskit/disciplinary'
	 *
	 * @param string $basename
	 * @return string Extension name or empty string if not an extension
	 */
	public function extract_extension_from_basename($basename)
	{
		$basename = ltrim($basename, '\\');
		$parts = explode('\\', $basename);
		if (count($parts) >= 2)
		{
			return $parts[0] . '/' . $parts[1];
		}
		return '';
	}
}
