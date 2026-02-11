<?php
namespace booskit\gtawtracker\acp;

class main_module
{
    var $u_action;

    function main($id, $mode)
    {
        global $config, $request, $template, $user;

        $user->add_lang_ext('booskit/gtawtracker', 'common');

        $this->tpl_name = 'acp_settings';
        $this->page_title = 'ACP_GTAW_TRACKER_SETTINGS';

        if ($request->is_set_post('submit')) {
            $faction_id = $request->variable('booskit_gtawtracker_faction_id', 0);
            $view_groups = $request->variable('booskit_gtawtracker_view_groups', '');

            $config->set('booskit_gtawtracker_faction_id', $faction_id);
            $config->set('booskit_gtawtracker_view_groups', $view_groups);

            trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
        }

        $template->assign_vars([
            'BOOSKIT_GTAWTRACKER_FACTION_ID' => $config['booskit_gtawtracker_faction_id'],
            'BOOSKIT_GTAWTRACKER_VIEW_GROUPS' => $config['booskit_gtawtracker_view_groups'],
            'U_ACTION' => $this->u_action,
        ]);
    }
}
