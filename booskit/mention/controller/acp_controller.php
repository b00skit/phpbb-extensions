<?php
/**
 *
 * @package booskit/mention
 * @license MIT
 *
 */

namespace booskit\mention\controller;

class acp_controller
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\log\log_interface */
	protected $log;

	/** @var \booskit\mention\service\mention_manager */
	protected $mention_manager;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\request\request_interface $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\log\log_interface $log,
		\booskit\mention\service\mention_manager $mention_manager
	) {
		$this->config = $config;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->log = $log;
		$this->mention_manager = $mention_manager;
	}

	public function handle($u_action)
	{
		$form_key = 'acp_booskit_mention';
		add_form_key($form_key);
		$this->user->add_lang_ext('booskit/mention', 'info_acp_mention');

		$all_groups = $this->mention_manager->get_all_mentionable_groups();
		$all_groups_by_id = [];
		foreach ($all_groups as $grp)
		{
			$all_groups_by_id[$grp['group_id']] = $grp;
		}

		$is_submitting = $this->request->is_set_post('submit')
			|| $this->request->is_set_post('add_group_btn')
			|| $this->request->is_set_post('delete_source_group');

		if ($is_submitting)
		{
			if (!check_form_key($form_key))
			{
				trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($u_action), E_USER_WARNING);
			}

			$group_enabled = (bool) $this->request->variable('booskit_mention_group_enabled', 0);
			$this->mention_manager->set_group_mention_enabled($group_enabled);

			$delete_gid = (int) $this->request->variable('delete_source_group', 0);
			$add_gid = (int) $this->request->variable('add_source_group_id', 0);

			// Read all configured groups that were submitted in the form
			$configured_groups = $this->request->variable('configured_groups', [0]);
			$configured_groups = array_values(array_filter(array_map('intval', $configured_groups), function ($id) {
				return $id > 0;
			}));

			// Fallback if configured_groups field was not present in POST at all (e.g. legacy or direct API post)
			$has_configured_groups_field = $this->request->is_set_post('configured_groups_present') || $this->request->is_set_post('configured_groups');
			if (!$has_configured_groups_field && !$delete_gid)
			{
				$existing_matrix = $this->mention_manager->get_group_matrix();
				$candidates = array_keys($existing_matrix);
				foreach ($all_groups as $grp)
				{
					$gid = $grp['group_id'];
					if ($this->request->is_set_post('mention_targets_all_' . $gid) || $this->request->is_set_post('mention_targets_' . $gid))
					{
						$candidates[] = $gid;
					}
				}
				$configured_groups = array_unique(array_filter(array_map('intval', $candidates)));
			}

			// Build updated matrix
			$matrix = [];
			foreach ($configured_groups as $sgid)
			{
				// Skip if this group was deleted
				if ($delete_gid > 0 && $sgid === $delete_gid)
				{
					continue;
				}

				$all_targets_allowed = (bool) $this->request->variable('mention_targets_all_' . $sgid, 0);

				if ($all_targets_allowed)
				{
					$matrix[$sgid] = '*';
				}
				else
				{
					$target_ids = $this->request->variable('mention_targets_' . $sgid, [0]);
					$target_ids = array_values(array_filter(array_map('intval', $target_ids), function ($tid) {
						return $tid > 0;
					}));
					$matrix[$sgid] = $target_ids;
				}
			}

			// If adding a group and it's valid and not currently in matrix
			if ($add_gid > 0 && isset($all_groups_by_id[$add_gid]) && !isset($matrix[$add_gid]) && $add_gid !== $delete_gid)
			{
				// Default newly added group to '*' (can mention all groups)
				$matrix[$add_gid] = '*';
			}

			$this->mention_manager->set_group_matrix($matrix);

			if (method_exists($this->log, 'add'))
			{
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_MENTION_SETTINGS_SAVED');
			}

			trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
		}

		$group_enabled = $this->mention_manager->is_group_mention_enabled();
		$matrix = $this->mention_manager->get_group_matrix();
		$matrix_is_empty = empty($matrix);

		// Assign general settings
		$this->template->assign_vars([
			'U_ACTION'                      => $u_action,
			'BOOSKIT_MENTION_GROUP_ENABLED' => $group_enabled,
			'S_MATRIX_IS_EMPTY'             => $matrix_is_empty,
		]);

		// Assign only explicitly defined matrix source groups
		$assigned_ids = [];
		foreach ($matrix as $sgid => $targets)
		{
			$sgid = (int) $sgid;
			if (!isset($all_groups_by_id[$sgid]))
			{
				continue;
			}

			$assigned_ids[$sgid] = true;
			$s_group = $all_groups_by_id[$sgid];
			$is_all_targets = ($targets === '*' || $targets === 'all');
			$target_ids = is_array($targets) ? $targets : [];

			$this->template->assign_block_vars('source_groups', [
				'ID'             => $sgid,
				'NAME'           => $s_group['group_name'],
				'COLOUR'         => $s_group['group_colour'],
				'IS_ALL_TARGETS' => $is_all_targets,
			]);

			foreach ($all_groups as $t_group)
			{
				$tgid = $t_group['group_id'];
				$this->template->assign_block_vars('source_groups.target_options', [
					'ID'       => $tgid,
					'NAME'     => $t_group['group_name'],
					'SELECTED' => $is_all_targets || in_array($tgid, $target_ids),
				]);
			}
		}

		// Assign available groups (mentionable groups not yet added to matrix)
		foreach ($all_groups as $grp)
		{
			$gid = $grp['group_id'];
			if (!isset($assigned_ids[$gid]))
			{
				$this->template->assign_block_vars('available_groups', [
					'ID'   => $gid,
					'NAME' => $grp['group_name'],
				]);
			}
		}
	}
}
