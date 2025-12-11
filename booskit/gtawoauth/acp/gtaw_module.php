<?php
namespace booskit\gtawoauth\acp;

class gtaw_module
{
    public function main($id, $mode)
    {
        global $phpbb_container;

        $controller = $phpbb_container->get('booskit.gtawoauth.controller.acp');
        return $controller->handle($id, $mode);
    }
}
