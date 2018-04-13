<?php

// must be run within DokuWiki
if (!defined('DOKU_INC')) die();

require_once __DIR__ . '/vendor/autoload.php';


class action_plugin_authskautis extends DokuWiki_Action_Plugin
{
    /**
     * Registers the event handlers.
     */
    function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('HTML_LOGINFORM_OUTPUT', 'BEFORE', $this, 'hook_html_loginform_output', []);
        $controller->register_hook('HTML_UPDATEPROFILEFORM_OUTPUT', 'BEFORE', $this, 'hook_updateprofileform_output', []);
    }

    function hook_updateprofileform_output(&$event, $param)
    {
        global $USERINFO;

        if ($USERINFO['is_skautis']) {
            $elem = $event->data->getElementAt(2);
            $elem['disabled'] = 'disabled';
            $event->data->replaceElement(2, $elem);

            $elem = $event->data->getElementAt(3);
            $elem['disabled'] = 'disabled';
            $event->data->replaceElement(3, $elem);

            $event->data->replaceElement(10, NULL);
            $event->data->replaceElement(9, NULL);
            $event->data->replaceElement(8, NULL);
            $event->data->replaceElement(7, NULL);
            $event->data->replaceElement(6, NULL);
            $event->data->replaceElement(5, NULL);
            $event->data->replaceElement(4, NULL);
        }
    }

    /**
     * Handles the login form rendering.
     */
    function hook_html_loginform_output(&$event, $param)
    {
        $skautIsAppId = $this->getConf('skautis_app_id');
        $skautIsTestmode = $this->getConf('skautis_test_mode');

        if ($skautIsAppId != '') {
            $skautIs = Skautis\Skautis::getInstance($skautIsAppId, $skautIsTestmode);
            $loginUrl = $skautIs->getLoginUrl();
            $buttonIcon = "
                <svg xmlns='http://www.w3.org/2000/svg' width='17' height='17' class='login-button-logo' xmlns:xlink='http://www.w3.org/1999/xlink'>
                    <svg viewBox='0 0 763 800'>
                        <image width='763' height='800' xlink:href='" . DOKU_URL . "lib/plugins/authskautis/images/skautis.svg' />
                    </svg>
                </svg>";
            $buttonText = $this->getLang('enter_skautis');
            echo "<a href='$loginUrl' class='login-button' title='$buttonText'>$buttonIcon $buttonText</a><br><br>";
        }
    }
}

?>
