<?php

if (!defined('DOKU_INC')) die();

require_once __DIR__ . '/vendor/autoload.php';


class action_plugin_authskautis extends DokuWiki_Action_Plugin {

    /**
     * Registers the event handlers.
     */
    function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('HTML_LOGINFORM_OUTPUT', 'BEFORE',  $this, 'hook_html_loginform_output', []);
        $controller->register_hook('HTML_UPDATEPROFILEFORM_OUTPUT', 'BEFORE', $this, 'hook_updateprofileform_output', []);
    }

    function hook_updateprofileform_output(&$event, $param) {
        global $USERINFO;

        if ($USERINFO['is_skautis']) {
            $elem = $event->data->getElementAt(2);
            $elem['disabled'] = 'disabled';
            $event->data->replaceElement(2, $elem);

            $elem = $event->data->getElementAt(3);
            $elem['disabled'] = 'disabled';
            $event->data->replaceElement(3, $elem);

            $event->data->replaceElement(10, null);
            $event->data->replaceElement(9, null);
            $event->data->replaceElement(8, null);
            $event->data->replaceElement(7, null);
            $event->data->replaceElement(6, null);
            $event->data->replaceElement(5, null);
            $event->data->replaceElement(4, null);
        }
    }

    /**
     * Handles the login form rendering.
     */
    function hook_html_loginform_output(&$event, $param) {
        $skautIsAppId = $this->getConf('skautis_app_id');
        $skautIsTestmode = $this->getConf('skautis_test_mode');

        if($skautIsAppId!=''){
            $loginUrl = $skautIs->getLoginUrl();
            $buttonText = $this->getLang('enter_skautis');
            echo "<a href='$loginUrl' class='login-button' title='$buttonText'><span class='login-button-logo'>&#x00ac;</span> $buttonText</a>";

        }
    }
}
?>
