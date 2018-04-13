<?php
/**
 * DokuWiki Plugin skautis (Auth Component)
 *
 * @license GPL 3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @author  Jiri Dorazil <alex@skaut.cz>
 */

// must be run within DokuWiki
if (!defined('DOKU_INC')) die();

require_once __DIR__ . '/vendor/autoload.php';

// define cookie and session id, append server port when securecookie is configured
if (!defined('AUTHSKAUTIS_COOKIE')) {
    define('AUTHSKAUTIS_COOKIE', 'SPGG' . md5(DOKU_REL . (($conf['securecookie']) ? $_SERVER['SERVER_PORT'] : '')));
}


class auth_plugin_authskautis extends auth_plugin_authplain
{
    /**
     * @var \Skautis\Skautis
     */
    public $skautIs;


    /**
     * Constructor.
     */
    public function __construct()
    {
        global $conf, $config_cascade;

        parent::__construct(); // for compatibility

        $this->success = TRUE;

        $this->cando['addUser'] = TRUE; // can Users be created?
        $this->cando['external'] = TRUE; // does the module do external auth checking?
        $this->cando['logout'] = TRUE; // can the user logout again? (eg. not possible with HTTP auth)

        $skautIsAppId = $this->getConf('skautis_app_id');
        $skautIsTestmode = $this->getConf('skautis_test_mode');
        $this->skautIs = $skautIsAppId ? Skautis\Skautis::getInstance($skautIsAppId, $skautIsTestmode) : NULL;
    }

    /**
     * Do all authentication [ OPTIONAL ]
     *
     * @param   string $user Username
     * @param   string $pass Cleartext Password
     * @param   bool $sticky Cookie should not expire
     * @return  bool             true on successful auth
     */
    public function trustExternal($user, $pass, $sticky = FALSE)
    {
        global $USERINFO;

        //get user info in session
        if (!empty($_SESSION[DOKU_COOKIE]['authskautis']['info'])) {
            $USERINFO = $_SESSION[DOKU_COOKIE]['authskautis']['info'];
            $_SERVER['REMOTE_USER'] = $_SESSION[DOKU_COOKIE]['authskautis']['user'];
            return TRUE;
        }

        //get authplain form login info
        if (!empty($user)) {
            //var_dump($user,$pass);die;
            if ($this->checkPass($user, $pass)) {
                $uinfo = $this->getUserData($user);

                //set user info
                $USERINFO['name'] = $uinfo['name'];
                $USERINFO['mail'] = $uinfo['email'];
                $USERINFO['grps'] = $uinfo['grps'];
                $USERINFO['is_skautis'] = FALSE;
                $USERINFO['pass'] = "";

                //save data in session
                $_SERVER['REMOTE_USER'] = $user;
                $_SESSION[DOKU_COOKIE]['authskautis']['user'] = $user;
                $_SESSION[DOKU_COOKIE]['authskautis']['info'] = $USERINFO;

                return TRUE;
            } else {
                //invalid credentials - log off
                msg($this->getLang('badlogin'), -1);
                return FALSE;
            }
        }

        if (!empty($_POST) && isset($_POST['skautIS_Token'])) {
            $this->skautIs->setLoginData($_POST);
            $skautIsUser = $this->skautIs->getUser();

            if ($skautIsUser->isLoggedIn(TRUE)) {
                $loginId = $this->skautIs->getUser()->getLoginId();
                $userDetail = $this->skautIs->usr->userDetail();
                $personDetail = $this->skautIs->org->PersonDetail(['ID_Login' => $loginId, 'ID' => $userDetail->ID_Person]);

                $skautIsUserName = $userDetail->UserName;
                $skautIsEmail = $personDetail->Email;
                $skautIsFirstName = $personDetail->FirstName;
                $skautIsLastName = $personDetail->LastName;
                $skautIsNickName = $personDetail->NickName;

                $name = $skautIsFirstName . ' ' . $skautIsLastName . ($skautIsNickName ? ' - ' . $skautIsNickName : '');

                $login = iconv('UTF-8', 'ASCII//TRANSLIT', $skautIsUserName);
                $login = preg_replace('/[^a-zA-Z0-9_]/', '', $login);
                $login = $login . $userDetail->ID;

                $udata = $this->getUserData($login);

                //create and update user in base
                if ($this->getConf('skautis_allowed_add_user')) {
                    if (!$udata) {
                        //default groups
                        $grps = NULL;
                        if ($this->getConf('default_groups')) {
                            $grps = explode(' ', $this->getConf('default_groups'));
                        }
                        //create user
                        $this->createUser($login, md5(rand() . $login), $name, $skautIsEmail, $grps);
                        $udata = $this->getUserData($login);
                    } elseif ($udata['name'] != $name || $udata['email'] != $skautIsEmail) {
                        //update user
                        $this->modifyUser($login, ['name' => $name, 'email' => $skautIsEmail]);
                    }
                }

                if ($this->isUserValid($login)) {
                    //set user info
                    $USERINFO['pass'] = "";
                    $USERINFO['name'] = $name;
                    $USERINFO['mail'] = $skautIsEmail;
                    $USERINFO['grps'] = $udata['grps'];
                    $USERINFO['is_skautis'] = TRUE;
                    $_SERVER['REMOTE_USER'] = $login;

                    //save user info in session
                    $_SESSION[DOKU_COOKIE]['authskautis']['user'] = $login;
                    $_SESSION[DOKU_COOKIE]['authskautis']['info'] = $USERINFO;

                    //if login page - redirect to main page
                    if (isset($_GET['do']) && $_GET['do'] == 'login') {
                        header("Location: " . wl('start', '', TRUE));
                    }

                    return TRUE;
                } else {
                    msg($this->getLang('nouser'), -1);
                    $this->logOff();
                    return FALSE;
                }
            } else {
                msg($this->getLang('badskautis'), -1);
                $this->logOff();
                return FALSE;
            }
        }

        return FALSE;
    }

    function logOff()
    {
        $isSkautIs = $_SESSION[DOKU_COOKIE]['authskautis']['info']['is_skautis'];

        unset($_SESSION[DOKU_COOKIE]['authskautis']['user']);
        unset($_SESSION[DOKU_COOKIE]['authskautis']['info']);

        if ($isSkautIs) {
            header("Location: " . $this->skautIs->getLogoutUrl());
            exit();
        }
    }

    function isUserValid($login)
    {
        return isset($this->users[$login]) ? TRUE : FALSE;
    }
}
