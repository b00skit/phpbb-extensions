<?php
namespace booskit\gtawoauth\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    protected $template;
    protected $user;
    protected $provider;

    public function __construct(\phpbb\template\template $template, \phpbb\user $user, \booskit\gtawoauth\auth\provider\gtaw $provider)
    {
        $this->template = $template;
        $this->user = $user;
        $this->provider = $provider;
    }

    static public function getSubscribedEvents()
    {
        return [
            'core.user_setup' => 'load_language_on_setup',
            'core.page_header' => 'inject_login_link',
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

    public function inject_login_link($event)
    {
        if ($this->user->data['user_id'] != ANONYMOUS) {
            return;
        }

        // Generate the OAuth login URL
        $creds = $this->provider->get_service_credentials();
        $client_id = $creds['key'];

        if (empty($client_id)) {
            return;
        }

        $redirect_uri = $this->provider->get_redirect_uri();
        $auth_endpoint = $this->provider->get_auth_endpoint();

        // Construct URL
        $params = [
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'state' => 'login', // We use 'login' to distinguish from 'link' (which uses a hash)
            // 'scope' => '', // Add scope if needed
        ];

        $login_url = $auth_endpoint . '?' . http_build_query($params);

        $this->template->assign_vars([
            'U_GTAW_LOGIN' => $login_url,
            'S_GTAW_LOGIN_ENABLED' => true,
        ]);
    }
}
