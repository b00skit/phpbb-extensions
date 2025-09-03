<?php
namespace booskit\phpbbapi\acp;

class main_module
{
    public $u_action;

    public function main($id, $mode)
    {
        global $config, $request, $template, $user;

        $user->add_lang_ext('booskit/phpbbapi', 'acp_phpbbapi');
        // Load the template from this extension's directory
        $this->tpl_name = '@booskit_phpbbapi/acp_phpbbapi_body';
        $this->page_title = $user->lang('ACP_BOOSKIT_PHPBBAPI_TITLE');

        $submit = $request->is_set_post('submit');
        if ($submit) {
            if (!check_form_key('booskit_phpbbapi')) {
                trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action));
            }

            $key = (string) $request->variable('booskit_phpbbapi_key', '', true);
            set_config('booskit_phpbbapi_key', $key);

            trigger_error($user->lang('ACP_BOOSKIT_PHPBBAPI_SAVED') . adm_back_link($this->u_action));
        }

        add_form_key('booskit_phpbbapi');

        $template->assign_vars([
            'U_ACTION'               => $this->u_action,
            'BOOSKIT_PHPBBAPI_KEY'   => $config['booskit_phpbbapi_key'],
        ]);
    }
}