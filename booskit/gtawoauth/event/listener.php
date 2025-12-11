<?php
namespace booskit\gtawoauth\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    protected $template;
    protected $user;

    public function __construct(\phpbb\template\template $template, \phpbb\user $user)
    {
        $this->template = $template;
        $this->user = $user;
    }

    static public function getSubscribedEvents()
    {
        return [
            'core.user_setup' => 'load_language_on_setup',
        ];
    }

    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'booskit/gtawoauth',
            'lang_set' => 'common',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }
}