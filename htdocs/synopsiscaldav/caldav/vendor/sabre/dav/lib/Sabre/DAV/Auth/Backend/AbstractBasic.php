<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\DAV;
use Sabre\HTTP;

/**
 * HTTP Basic authentication backend class
 *
 * This class can be used by authentication objects wishing to use HTTP Basic
 * Most of the digest logic is handled, implementors just need to worry about
 * the validateUserPass method.
 *
 * @copyright Copyright (C) 2007-2013 fruux GmbH (https://fruux.com/).
 * @author James David Low (http://jameslow.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class AbstractBasic implements BackendInterface {

    /**
     * This variable holds the currently logged in username.
     *
     * @var string|null
     */
    protected $currentUser;

    /**
     * Validates a username and password
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    abstract protected function validateUserPass($username, $password);

    /**
     * Returns information about the currently logged in username.
     *
     * If nobody is currently logged in, this method should return null.
     *
     * @return string|null
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }


    /**
     * Authenticates the user based on the current request.
     *
     * If authentication is successful, true must be returned.
     * If authentication fails, an exception must be thrown.
     *
     * @param DAV\Server $server
     * @param string $realm
     * @throws DAV\Exception\NotAuthenticated
     * @return bool
     */
    public function authenticate(DAV\Server $server, $realm) {

        $auth = new HTTP\BasicAuth();
        $auth->setHTTPRequest($server->httpRequest);
        $auth->setHTTPResponse($server->httpResponse);
        $auth->setRealm($realm);
        $userpass = $auth->getUserPass();
        if (!$userpass) {
            $auth->requireLogin();
            throw new DAV\Exception\NotAuthenticated('No basic authentication headers were found NO LOG');
        }

        // Authenticates the user
        global $db;
        $tmpuser = new \User($db);
        if (!$this->validateUserPass($userpass[0],$userpass[1])) {
            \BimpTools::secuAddEchec('Auth caldav echec, login : '.$userpass[0]);
            $auth->requireLogin();
            throw new DAV\Exception\NotAuthenticated('Username or password does not match '.$userpass[0]." !! ".$userpass[1]);
        }
        else{
            $tmpuser->fetch('', $userpass[0]);
            if($tmpuser->id < 1){
                $tmpuser->fetch('', '','',0,-1, $userpass[0]);
                if($tmpuser->id < 1){
                    \BimpTools::secuAddEchec('Auth caldav echec (compte ok mais pas de compte gle), login : '.$userpass[0]);
                    $auth->requireLogin();
                    throw new DAV\Exception\NotAuthenticated('Username or password does not match '.$userpass[0]." !! ".$userpass[1]);
                }
            }
        }
        $this->currentUser = $tmpuser->login;
        
        
        /*moddrsi (20.2 ??)*/
        if(isset($_SERVER['PATH_INFO'])){
            $tabT = explode("/", $_SERVER['PATH_INFO']);
            $userCalendar = strtolower($tabT[2]);
            global $db;
            $user = new \User($db);
            $user->fetch("",$this->currentUser);
            $user->getrights("agenda");
            global $USER_CONNECT;
            $USER_CONNECT = $user;
            if(!isset($user->rights->agenda))
                return false;
            if($user->rights->agenda->allactions->read)
                $this->currentUser = $userCalendar;
            
//            echo "<pre>";
//            print_r($user);die;
        }
        /*fmoddrsi*/
        
        


        return true;
    }


}

