<?php

/*
 * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
/* original class
 * zimbra.class.php
 *
 * === Modification History ===<br/>
 * ALPHA  25-Sep-2007  [zbt]  original<br/>
 * 1.0    10-Oct-2007  [zbt]  Added calendar, mail, and admin functionailty<br/>
 * 1.1    30-Oct-2007  [zbt]  Added on vacation, and split admin functionality out into child class<br/>
 * 1.2    23-Oct-2008  [zbt]  Added stuff for tasks
 * 1.3    24-Oct-2008  [nrp]  Addded phpDoc goodness
 *
 */

/**
 * zimbra.class.php
 *
 * Zimbra API
 *
 * @version        1.3
 * @module        zimbra.class.php
 * @author        Zachary Tirrell <zbtirrell@plymouth.edu>
 * @GPL 2007, Plymouth State University, ITS
 */
class Zimbra {

    public $debug = false;
    public $_admin_password = "";
    public $error;
    public $langs;
    protected $_connected = false; // boolean to determine if the connect function has been called
    protected static $_num_soap_calls = 0; // the number of times a SOAP call has been made
    protected $_preAuthKey; // key for doing pre-authentication
    protected $_lcached_assets = array(); // an array to hold assets that have been cached
    protected $_preauth_expiration = 0; // 0 indicates using the default preauth expiration as defined on the server
    protected $_dev; // boolean indicating whether this is development or not
    public $_protocol; // which protocol to use when building the URL
    public $_server; // hostname of zimbra server
    public $_path = '/service/soap';
    protected $_timestamp;
    protected $_domain;
    protected $_account_info;
    protected $_admin = false; // operating as an admin
    protected $_curl;
    protected $_auth_token; // used for repeat calls to zimbra through soap
    protected $_session_id; // used for repeat calls to zimbra through soap
    protected $_idm;  // IDMObject
    protected $_username; // the user we are operating as
    public $appointmentFolderId = array();
    public $appointmentFolderName = array();
    public $appointmentFolderDesc = array();
    public $contactFolderId = array();
    public $contactFolderName = array();
    public $contactFolderDesc = array();
    public $wikiFolderDesc = array();
    public $wikiFolderId = array();
    public $wikiFolderName = array();
    public $documentFolderDesc = array();
    public $documentFolderId = array();
    public $documentFolderName = array();
    public $db;

    /**
     * __construct
     *
     * constructor sets up connectivity to servers
     *
     * @since        version 1.0
     * @acess    public
     * @param string $username username
     * @param string $which defaults to prod
     */
    public function __construct($username) {
        global $conf;
        $this->_protocol = $conf->global->ZIMBRA_PROTO . "://";
        $this->_server = $conf->global->ZIMBRA_HOST;
        $this->_domain = $conf->global->ZIMBRA_DOMAIN;
        $this->_preAuthKey = $conf->global->ZIMBRA_PREAUTH;

//        $this->_preAuthKey="b7053b891fd512fae4caad99077eab78952be0e2e13868794a40b348dde49a46";
//        $this->_domain = "synopsis-erp.com";
//        $this->_server = "10.91.130.61";
//        $this->_protocol = "http://";
        // end of PSU proprietary configuration load

        /*         * *** if not PSU, do something similar to the following:
          $this->_preAuthKey = '<insert key string acquired from Zimbra server>';
          $this->_protocol = 'https://'; // could also be http://
          $this->_server = 'zimbra.hostname.edu';
         * ** */
        $this->_username = $username;

        $this->_timestamp = time() . '000';
    }

    public function getIdFolder($db, $nom) {
        $requete = "SELECT `folder_uid` FROM `" . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder` WHERE `folder_name` = '" . $nom . "'";
        $resql = $db->query($requete);
        if ($res = $db->fetch_object($resql))
            return $res->folder_uid;
        return false;
    }

// end __construct

    /**
     * sso
     *
     * sso to Zimbra
     *
     * @since        version 1.0
     * @access    public
     * @param    string $options options for sso
     * @return    boolean
     */
    public function isConnected() {
        return($this->_connected);
    }

    public function sso($options = '') {
        if ($this->_username) {
            //setcookie('ZM_SKIN','plymouth',time()+60*60*24*30,'/','.plymouth.edu');

            $pre_auth = $this->getPreAuth($this->_username);

            $url = $this->_protocol . $this->_server . '/service/preauth?account=' . $this->_username . '@' . $this->_domain . '&expires=' . $this->_preauth_expiration . '&timestamp=' . $this->_timestamp . '&preauth=' . $pre_auth . '&' . $options;
            header("Location: $url");
            exit;
        } else {
            return false;
        }
    }

// end sso

    /**
     * getPreAuth
     *
     * get the preauth key needed for single-sign on
     *
     * @since        version1.0
     * @access    public
     * @param    string $username username
     * @return    string preauthentication key in hmacsha1 format
     */
    private function getPreAuth($username) {
        $account_identifier = $username . '@' . $this->_domain;
        $by_value = 'name';
        $expires = $this->_preauth_expiration;
        $timestamp = $this->_timestamp;
        $string = $account_identifier . '|' . $by_value . '|' . $expires . '|' . $timestamp;
//    $preauthToken=hash_hmac("sha1",$email."|name|0|".$timestamp,$PREAUTH_KEY);
        return $this->hmacsha1($this->_preAuthKey, $string);
        //  return ($this->_preAuthKey);
    }

// end getPreAuth

    /**
     * hmacsha1
     *
     * generate an HMAC using SHA1, required for preauth
     *
     * @since        version 1.0
     * @access    public
     * @param    int $key encryption key
     * @param    string $data data to encrypt
     * @return    string converted to hmac sha1 format
     */
    private function hmacsha1($key, $data) {
        $blocksize = 64;
        $hashfunc = 'sha1';
        if (strlen($key) > $blocksize)
            $key = pack('H*', $hashfunc($key));
        $key = str_pad($key, $blocksize, chr(0x00));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5c), $blocksize);
        $hmac = pack(
                'H*', $hashfunc(
                        ($key ^ $opad) . pack(
                                'H*', $hashfunc(
                                        ($key ^ $ipad) . $data
                                )
                        )
                )
        );
        return bin2hex($hmac);
    }

// end hmacsha1

    /**
     * connect
     *
     * connect to the Zimbra SOAP service
     *
     * @since    version 1.0
     * @access    public
     * @return    array associative array of account information
     */
    public static function getZimbraCred($db, $id) {
        $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_Zimbra_li_User.ZimbraLogin," .
                "        " . MAIN_DB_PREFIX . "Synopsis_Zimbra_li_User.ZimbraPass" .
                "   FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_li_User " .
                "  WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_li_User.User_refid=" . $id;
        $resql = $db->query($requete);
//        $this->ZimbraLogin=false;
//        $this->ZimbraPass=false;
        if ($resql) {
            if ($db->num_rows($resql)) {
                $res = $db->fetch_object($resql);
//                $this->ZimbraLogin = $res->ZimbraLogin;
//                $this->ZimbraPass = $res->ZimbraPass;
                return $res->ZimbraLogin;
            }
        }
        return false;
    }

    public static function getZimbraId($db, $id) {
        $requete = "SELECT *
                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_li_User" .
                "  WHERE User_refid=" . $id;
        $resql = $db->query($requete);
        if ($resql) {
            if ($db->num_rows($resql)) {
                $res = $db->fetch_object($resql);
                return $res->ZimbraId;
            }
        }
        return false;
    }

    public function connected() {
        return $this->_connected;
    }

    public function connect() {
        if ($this->_connected) {
            return $this->_account_info;
        }
        $this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_URL, $this->_protocol . $this->_server . $this->_path);
        curl_setopt($this->_curl, CURLOPT_POST, true);
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->_curl, CURLOPT_TIMEOUT, 5);

        $preauth = $this->getPreAuth($this->_username);
        $header = '<context xmlns="urn:zimbra' . (($this->_admin) ? 'Admin' : '') . '"/>';
        $body = "";


//
//        $this->_admin = true;
//        $this->_admin_username = "cconstantin";
//        $this->_admin_password = "Lucie97";



        if ($this->_admin) {
            $body = '<AuthRequest xmlns="urn:zimbraAdmin">
                        <name>' . $this->_admin_username . '</name>
                        <password>' . $this->_admin_password . '</password>
                    </AuthRequest>';
        } else {
            $body = '<AuthRequest xmlns="urn:zimbraAccount">
                        <account by="name">' . $this->_username . '@' . $this->_domain . '</account>
                        <preauth timestamp="' . $this->_timestamp . '" expires="' . $this->_preauth_expiration . '">' . $preauth . '</preauth>
                    </AuthRequest>';
        }

        $response = $this->soapRequest($body, $header, true);
        if ($response) {
            $tmp = $this->makeXMLTree($response);
            $this->_account_info = $tmp['soap:Envelope'][0]['soap:Header'][0]['context'][0]['refresh'][0]['folder'][0];

            $this->session_id = $this->extractSessionID($response);
            $this->auth_token = $this->extractAuthToken($response);

            $this->_connected = true;

            return $this->_account_info;
        } else {
            die("Problém de connexion Zimbra : REq  " . $header . " ||| " . $body . "  Result :" . $response);
            $this->_connected = false;
            $this->error = "err" . $response;
            return false;
        }
    }

// end connect

    public function connectAdmin($userpass, $adminPass) {
        $this->_admin = true;
        $this->_admin_username = $userpass;
        $this->_admin_password = $adminPass;
        //$this->_protocol='https://';
        $this->_protocol = 'https://';
        $this->_server = preg_replace('/:[0-9]*$/', '', $this->_server);
        $this->_server.=':7071';
        $this->_path = '/service/admin/soap/';
        $this->_connected = false;
        $this->_connecting = true;
        $this->connect();
    }

// end connect

    /**
     * administerUser
     *
     * set the user you are administering (experimental)
     *
     * @since        version 1.0
     * @access    public
     * @param    string $username username to administer
     * @return    boolean
     */
    public function administerUser($username) {
        if (!$this->_admin) {
            return false;
        }

        $this->_username = $username;

        $body = '<DelegateAuthRequest xmlns="urn:zimbraAdmin">
           <account by="name">' . $this->_username . '@' . $this->_server . '</account>
         </DelegateAuthRequest>';
        $response = $this->soapRequest($body, $header);
        if ($response) {
            $tmp = $this->makeXMLTree($response);
            $this->_account_info = $tmp['soap:Envelope'][0]['soap:Header'][0]['context'][0]['refresh'][0]['folder'][0];

            $this->session_id = $this->extractSessionID($response);
            $this->auth_token = $this->extractAuthToken($response);

            return true;
        } else {
            return false;
        }
    }

// end administerUser

    /**
     * getInfo
     *
     * generic function to get information on mailbox, preferences, attributes, properties, and more!
     *
     * @since        version 1.0
     * @access    public
     * @param    string $options options for info retrieval, defaults to null
     * @return    array information
     */
    public function getInfo($options = '') {
        // valid sections: mbox,prefs,attrs,zimlets,props,idents,sigs,dsrcs,children
        $option_string = $this->buildOptionString($options);

        $soap = '<GetInfoRequest xmlns="urn:zimbraAccount"' . $option_string . '></GetInfoRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
            return $array['soap:Envelope'][0]['soap:Body'][0]['GetInfoResponse'][0];
        } else {
            return false;
        }
    }

// end getInfo

    /**
     * getMessages
     *
     * get the messages in folder, deafults to inbox
     *
     * @since        version 1.0
     * @access    public
     * @param    string $search folder to retrieve from, defaults to in:inbox
     * @param    array $options options to apply to retrieval
     * @return    array array of messages
     */
    public function getMessages($search = 'in:inbox', $options = array('limit' => 5, 'fetch' => 'none')) {
        $option_string = $this->buildOptionString($options);

        $soap = '<SearchRequest xmlns="urn:zimbraMail" types="message"' . $option_string . '>
                    <query>' . $search . '</query>
                </SearchRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
            return $array['soap:Envelope'][0]['soap:Body'][0]['SearchResponse'][0];
        } else {
            return false;
        }
    }

// end getMessages
    //
    public function getAptBabel($search = 'in:inbox', $options = array('limit' => 5, 'fetch' => 'none')) {
        $option_string = $this->buildOptionString($options);

        $soap = '<SearchRequest xmlns="urn:zimbraMail" types="appointment"' . $option_string . '>
                    <query>' . $search . '</query>
                </SearchRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
            return $array['soap:Envelope'][0]['soap:Body'][0]['SearchResponse'][0];
        } else {
            return false;
        }
    }

// end getAptBabel

    public function getDocBabel($search = 'in:inbox', $options = array('limit' => 100)) {
        $option_string = $this->buildOptionString($options);

        $soap = '<SearchRequest xmlns="urn:zimbraMail" types="contact" ' . $option_string . '>
                    <query>' . $search . '</query>
                </SearchRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
            return $array['soap:Envelope'][0]['soap:Body'][0]['SearchResponse'][0];
        } else {
            return false;
        }
    }

// end getDocument

    public function createContBabel($contArr, $option_string = "") {
        /*
         * <CreateContactRequest>
          <cn l="7" t="65">
          <a n="lastName">Khavari</a>
          <a n="firstName">Brunhilde</a>
          <a n="email1">bk@hotmail.com</a>
          </cn>


         */
        $soap = '<CreateContactRequest xmlns="urn:zimbraMail"' . $option_string . '>';
        $stringStr = false;
        if ($contArr['t'] . "x" != "x") {
            $stringStr = " t = '" . $contArr['t'] . "' ";
        }
        $soap .='<cn l="' . $contArr["l"] . '"  ' . $stringStr . ' >';

        foreach ($contArr['contactDet'] as $key => $val) {
            $soap .= "<a n='" . $key . "'><![CDATA[" . $val . "]]></a>";
        }

        $soap .= '
                    </cn>
                </CreateContactRequest>';

        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            return (is_array($array['soap:Envelope'][0]['soap:Body'][0]['CreateContactResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['CreateContactResponse'][0]['cn'][0] : $array['soap:Envelope'][0]['soap:Body'][0];
//            return $array['soap:Envelope'][0]['soap:Body'][0]['CreateContactResponse'][0];
        } else {
            return false;
        }
    }

    public function modifyApptBabel($aptArr, $id, $option_string = "") {
        $datenow = time();

        if ("x" . $id == "x") {
            return (false);
        }

        $datestart = "";
        $dateend = "";

        if ($aptArr['allDay'] == 0) {
            $datestart = $aptArr["start"]["year"];
            if (strlen($aptArr["start"]["month"]) == 1) {
                $datestart .= "0" . $aptArr["start"]["month"];
            } else {
                $datestart .= "" . $aptArr["start"]["month"];
            }
            if (strlen($aptArr["start"]["day"]) == 1) {
                $datestart .= "0" . $aptArr["start"]["day"];
            } else {
                $datestart .= "" . $aptArr["start"]["day"];
            }
            $datestart.="T";
            if (strlen($aptArr["start"]["hour"] - 1) == 1) {
                //correct to GMT
                $datestart .= "0" . ($aptArr["start"]["hour"] - 1);
            } else {
                //correct to GMT
                $datestart .= $aptArr["start"]["hour"] - 1;
            }if (strlen($aptArr["start"]["min"]) == 1) {
                $datestart .= "0" . $aptArr["start"]["min"];
            } else {
                $datestart .= "" . $aptArr["start"]["min"];
            }
            $datestart .= "00Z";

            $dateend = $aptArr["end"]["year"];
            if (strlen($aptArr["end"]["month"]) == 1) {
                $dateend .= "0" . $aptArr["end"]["month"];
            } else {
                $dateend .= "" . $aptArr["end"]["month"];
            }
            if (strlen($aptArr["end"]["day"]) == 1) {
                $dateend .= "0" . $aptArr["end"]["day"];
            } else {
                $dateend .= "" . $aptArr["end"]["day"];
            }
            $dateend.="T";
            if (strlen($aptArr["end"]["hour"] - 1) == 1) {
                //correct to GMT
                $dateend .= "0" . ($aptArr["end"]["hour"] - 1);
            } else {
                //correct to GMT
                $dateend .= $aptArr["end"]["hour"] - 1;
            }if (strlen($aptArr["end"]["min"]) == 1) {
                $dateend .= "0" . $aptArr["end"]["min"];
            } else {
                $dateend .= "" . $aptArr["end"]["min"];
            }
            $dateend .= "00Z";
        } else {
            $datestart = $aptArr["start"]["year"];
            if (strlen($aptArr["start"]["month"]) == 1) {
                $datestart .= "0" . $aptArr["start"]["month"];
            } else {
                $datestart .= "" . $aptArr["start"]["month"];
            }
            if (strlen($aptArr["start"]["day"]) == 1) {
                $datestart .= "0" . $aptArr["start"]["day"];
            } else {
                $datestart .= "" . $aptArr["start"]["day"];
            }
            $dateend = $aptArr["end"]["year"];
            if (strlen($aptArr["end"]["month"]) == 1) {
                $dateend .= "0" . $aptArr["end"]["month"];
            } else {
                $dateend .= "" . $aptArr["end"]["month"];
            }
            if (strlen($aptArr["end"]["day"]) == 1) {
                $dateend .= "0" . $aptArr["end"]["day"];
            } else {
                $dateend .= "" . $aptArr["end"]["day"];
            }
        }
        $type = 'event'; //useless ?

        $apptCont = "";
        $trad = 'D&eacute;tails';
        if (is_object($this->langs)) {
            $trad = $this->langs->Trans('D&eacute;tails');
        }
        $urlAdd = "<p><a href='" . $aptArr["url"] . "'>" . $trad . "</a> ";
        if ($aptArr["descHtml"] . "x" != "x") {
            $apptCont = '<![CDATA[ ' . utf8_encode(utf8_decode($aptArr["descHtml"] . $urlAdd)) . ' ]]>';
        } else if ($aptArr["desc"] . "x" != "x") {
            $apptCont = '<![CDATA[ ' . utf8_encode(utf8_decode($aptArr["desc"] . $urlAdd)) . ' ]]>';
        } else {
            $apptCont = '<![CDATA[ ' . utf8_encode(utf8_decode($aptArr["name"] . $urlAdd)) . ' ]]>';
        }
        $zimTag = false;
        if ($aptArr["t"] . "x" != "x") {
            $zimTag = " t='" . $aptArr['t'] . "' ";
        }


        $soap = '<ModifyAppointmentRequest id="' . $id . '" comp="0" xmlns="urn:zimbraMail" ' . $option_string . '>
                    <m l="' . $aptArr["l"] . '" d="' . $datenow . '" ' . $zimTag . ' >

                        <inv  method="REQUEST" type="' . $type . '"
                                               fb="' . $aptArr["fb"] . '"
                                               transp="' . $aptArr["transp"] . '"
                                               status="' . $aptArr["status"] . '"
                                               allDay="' . $aptArr["allDay"] . '"
                                               name="' . utf8_encode(utf8_decode($aptArr["name"])) . '"
                                               loc="' . $aptArr["loc"] . '"
                                               isOrg="' . $aptArr["isOrg"] . '"
                                               url="' . $aptArr["url"] . '"
                                               l="' . $aptArr["l"] . '" ' . $zimTag . '
                         >
                                               <s  d= "' . $datestart . '"/>
                                               <e  d= "' . $dateend . '" />
                        </inv>
                        <mp ct="multipart/mixed">
                            <mp body="1" cd="inline" ct="text/html" part="1" s="' . strlen($apptCont) . '">
                                <content>' . $apptCont . '
                                </content>
                            </mp>
                        </mp>
                    </m>
                </ModifyAppointmentRequest>';

        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
            return (is_array($array['soap:Envelope'][0]['soap:Body'][0]['ModifyAppointmentResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['ModifyAppointmentResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0];
        } else {
            return false;
        }
    }

    public function createApptBabel($aptArr, $option_string = "") {

        //$aptArr=               array( "start"    => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>12 , "min" => 30 ),
        //                              "end"      => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>12 , "min" => 30 ),
        //                              "fb"       => "B",
        //                              "transp"   => "O",
        //                              "status"   => "TENT",
        //                              "allDay"   => "0",
        //                              "name"     => "Test Zimbra 2",
        //                              "loc"      => "Aix en Provence",
        //                              "isOrg"    => "1",
        //                              "url"      => "http://10.91.130.1/test.php",
        //                              "noBlob"   => "1", // no use
        //                              "l"        => "19", // no use
        //                              "desc"     => "test",
        //                              "descHtml" => "test"
        //                 ));
        $datenow = time();

        //        $datestart = "20090226T130000"; //14H
        $datestart = "";
        $dateend = "";

        if ($aptArr['allDay'] == 0) {
            $datestart = $aptArr["start"]["year"];
            if (strlen($aptArr["start"]["month"]) == 1) {
                $datestart .= "0" . $aptArr["start"]["month"];
            } else {
                $datestart .= "" . $aptArr["start"]["month"];
            }
            if (strlen($aptArr["start"]["day"]) == 1) {
                $datestart .= "0" . $aptArr["start"]["day"];
            } else {
                $datestart .= "" . $aptArr["start"]["day"];
            }
            $datestart.="T";
            if (strlen($aptArr["start"]["hour"] - 1) == 1) {
                //correct to GMT
                $datestart .= "0" . ($aptArr["start"]["hour"] - 1);
            } else {
                //correct to GMT
                if (($aptArr["start"]["hour"] - 1) < 0) {
                    $datestart .= 23;
                } else {
                    $datestart .= $aptArr["start"]["hour"] - 1;
                }
            }if (strlen($aptArr["start"]["min"]) == 1) {
                $datestart .= "0" . $aptArr["start"]["min"];
            } else {
                $datestart .= "" . $aptArr["start"]["min"];
            }
            $datestart .= "00Z";

            $dateend = $aptArr["end"]["year"];
            if (strlen($aptArr["end"]["month"]) == 1) {
                $dateend .= "0" . $aptArr["end"]["month"];
            } else {
                $dateend .= "" . $aptArr["end"]["month"];
            }
            if (strlen($aptArr["end"]["day"]) == 1) {
                $dateend .= "0" . $aptArr["end"]["day"];
            } else {
                $dateend .= "" . $aptArr["end"]["day"];
            }
            $dateend.="T";
            if (strlen($aptArr["end"]["hour"] - 1) == 1) {
                //correct to GMT
                $dateend .= "0" . ($aptArr["end"]["hour"] - 1);
            } else {
                //correct to GMT
                if (($aptArr["start"]["hour"] - 1) < 0) {
                    $dateend .= 23;
                } else {
                    $dateend .= $aptArr["end"]["hour"] - 1;
                }
            }if (strlen($aptArr["end"]["min"]) == 1) {
                $dateend .= "0" . $aptArr["end"]["min"];
            } else {
                $dateend .= "" . $aptArr["end"]["min"];
            }
            $dateend .= "00Z";
        } else {
            $datestart = $aptArr["start"]["year"];
            if (strlen($aptArr["start"]["month"]) == 1) {
                $datestart .= "0" . $aptArr["start"]["month"];
            } else {
                $datestart .= "" . $aptArr["start"]["month"];
            }
            if (strlen($aptArr["start"]["day"]) == 1) {
                $datestart .= "0" . $aptArr["start"]["day"];
            } else {
                $datestart .= "" . $aptArr["start"]["day"];
            }
            $dateend = $aptArr["end"]["year"];
            if (strlen($aptArr["end"]["month"]) == 1) {
                $dateend .= "0" . $aptArr["end"]["month"];
            } else {
                $dateend .= "" . $aptArr["end"]["month"];
            }
            if (strlen($aptArr["end"]["day"]) == 1) {
                $dateend .= "0" . $aptArr["end"]["day"];
            } else {
                $dateend .= "" . $aptArr["end"]["day"];
            }
        }
        $type = 'event'; //useless ?
        //$type = 'appt';
        //Avant la 5.0.15, zimbra ne sait pas gérer le noBlob :(
        //        $soap ='<CreateAppointmentRequest xmlns="urn:zimbraMail"'.$option_string.'>
        //                    <m l="'.$aptArr["l"].'" d="'.$datenow.'" >
        //
        //                    <inv  method="REQUEST" type="'.$type.'"
        //                                           fb="'.$aptArr["fb"].'"
        //                                           transp="'.$aptArr["transp"].'"
        //                                           status="'.$aptArr["status"].'"
        //                                           allDay="'.$aptArr["allDay"].'"
        //                                           name="'.$aptArr["name"].'"
        //                                           loc="'.$aptArr["loc"].'"
        //                                           isOrg="'.$aptArr["isOrg"].'"
        //                                           url="'.$aptArr["url"].'"
        //                                           noBlob="'.$aptArr["noBlob"].'"
        //                                           l="'.$aptArr["l"].'"
        //                     >
        //                    <desc>tete
        //                        '.$aptArr["desc"].'
        //                    </desc>
        //                    <descHtml>
        //                        '.$aptArr["desc"].'
        //                    </descHtml>
        //                                           <s  d= "'.$datestart.'"/>
        //                                           <e  d= "'.$dateend.'" />
        //                    </inv>
        //                    </m>
        //                </CreateAppointmentRequest>';
        $apptCont = "";
        $trad = 'D&eacute;tails';
        if (is_object($this->langs)) {
            $trad = $this->langs->Trans('D&eacute;tails');
        }
        $urlAdd = "<p><a href='" . $aptArr["url"] . "'>" . $trad . "</a> ";
        if ($aptArr["descHtml"] . "x" != "x") {
            $apptCont = '<![CDATA[ ' . utf8_encode(utf8_decode($aptArr["descHtml"] . $urlAdd)) . ' ]]>';
        } else if ($aptArr["desc"] . "x" != "x") {
            $apptCont = '<![CDATA[ ' . utf8_encode(utf8_decode($aptArr["desc"] . $urlAdd)) . ' ]]>';
        } else {
            $apptCont = '<![CDATA[ ' . utf8_encode(utf8_decode($aptArr["name"] . $urlAdd)) . ' ]]>';
        }
        $zimTag = false;
        if ($aptArr["t"] . "x" != "x") {
            $zimTag = " t='" . $aptArr['t'] . "' ";
        }

        $soap = '<CreateAppointmentRequest xmlns="urn:zimbraMail" ' . $option_string . '>
                    <m l="' . $aptArr["l"] . '" d="' . $datenow . '" ' . $zimTag . ' >

                        <inv  method="REQUEST" type="' . $type . '"
                                               fb="' . $aptArr["fb"] . '"
                                               transp="' . $aptArr["transp"] . '"
                                               status="' . $aptArr["status"] . '"
                                               allDay="' . $aptArr["allDay"] . '"
                                               name="' . utf8_encode(utf8_decode(html_entity_decode($aptArr["name"], ENT_NOQUOTES, "UTF-8"))) . '"
                                               loc="' . $aptArr["loc"] . '"
                                               isOrg="' . $aptArr["isOrg"] . '"
                                               url="' . $aptArr["url"] . '"
                                               l="' . $aptArr["l"] . '" ' . $zimTag . '
                         >
                                               <s  d= "' . $datestart . '"/>
                                               <e  d= "' . $dateend . '" />
                        </inv>
                        <mp ct="multipart/mixed">
                            <mp body="1" cd="inline" ct="text/html" part="1" s="' . strlen($apptCont) . '">
                                <content>' . $apptCont . '
                                </content>
                            </mp>
                        </mp>
                    </m>
                </CreateAppointmentRequest>';


        $response = $this->soapRequest($soap);

        if ($response) {
            $array = $this->makeXMLTree($response);
            return (is_array($array['soap:Envelope'][0]['soap:Body'][0]['CreateAppointmentResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['CreateAppointmentResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0];
        } else {
            return false;
        }
    }

    public function getListWikiFolder($folderId) {//TODO
        $soap = '<GetFolderRequest xmlns="urn:zimbraMail" visible="1">
                    <folder l="' . $folderId . '"/>
                </GetFolderRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $folder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0]['folder'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0]['folder'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0];
            return $folder;
        } else {
            return false;
        }
    }

    /**
     * getAppointments
     *
     * get appointments in a calendar
     *
     * @since        version 1.0
     * @access    public
     * @param    array $options array of options to apply to retrieval from calendar
     * @return    array associative array of appointments
     */
    public function getAppointments($options = array()) {
        $option_string = $this->buildOptionString($options);

        $soap = '<BatchRequest xmlns="urn:zimbra" onerror="continue"><GetApptSummariesRequest xmlns="urn:zimbraMail" ' . $option_string . '/></BatchRequest>';

        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            return $array['soap:Envelope'][0]['soap:Body'][0]['BatchResponse'][0]['GetApptSummariesResponse'][0]['appt'];
        } else {
            return false;
        }
    }

// end getAppointments

    /**
     * getTasks
     *
     * get tasks in a task list
     *
     * @since        version 1.0
     * @access    public
     * @param    string $search search paramaters, defaults to *
     * @param    array $options options to control retrieval
     * @return    array associative array of tasks
     */
    public function getTasks($search = '*', $options = array('limit' => 1000)) {
        $option_string = $this->buildOptionString($options);

        $soap = '<SearchRequest xmlns="urn:zimbraMail" types="task"' . $option_string . '>
                    <query>in:"' . $search . '"</query>
                </SearchRequest>';
        $response = $this->soapRequest($soap);

        if ($response) {
            $array = $this->makeXMLTree($response);

            $tasks = $array['soap:Envelope'][0]['soap:Body'][0]['SearchResponse'][0]['task'];

            $task_list = array();
            $task_list['INPR'] = array();
            $task_list['WAITING'] = array();
            $task_list['DEFERRED'] = array();
            $task_list['NEED'] = array();
            $task_list['COMP'] = array();
            $task_list['CONFIG'] = array('ti' => $search, 'in' => 'In Progress', 'wt' => 'Waiting', 'df' => 'Deferred', 'nd' => 'Not Started', 'cp' => 'Completed');

            foreach ($tasks as $task) {
                if ($task['name'] != 'META_CONFIG_DO_NOT_DELETE') {
                    $task['start'] = ($task['dur']) ? date('n/j/y', ($task['dueDate'] - $task['dur']) / 1000) : '[not scheduled]';
                    $task['end'] = ($task['dueDate']) ? date('n/j/y', $task['dueDate'] / 1000) : '[no date]';
                    $task['t_percent_complete'] = (int) $task['percentComplete'] . '%';
                    $task_list[$task['status']][] = $task;
                } else {
                    $temp = explode('|', $task['fr'][0]);
                    foreach ($temp as $tmp) {
                        list($k, $v) = explode('::', $tmp);
                        $task_list['CONFIG'][$k] = $v;
                    }
                }
            }

            usort($task_list['INPR'], 'zimbra_dueSort');
            usort($task_list['WAITING'], 'zimbra_startSort');
            usort($task_list['DEFERRED'], 'zimbra_startSort');
            usort($task_list['NEED'], 'zimbra_nameSort');
            usort($task_list['COMP'], 'zimbra_dueSort');

            return $task_list;
        } else {
            return false;
        }
    }

// end getTasks

    /**
     * getMessageContent
     *
     * get the content from a message
     *
     * @since        version 1.0
     * @access    public
     * @param    int $id id number of message to retrieve content of
     * @return    array associative array with message content, valid for tasks, calendar entries, and email messages.
     */
    public function getMessageContent($id) {
        $soap = '<GetMsgRequest xmlns="urn:zimbraMail">
                    <m id="' . $id . '" html="1">*</m>
                </GetMsgRequest>';
        $response = $this->soapRequest($soap);

        if ($response) {
            $array = $this->makeXMLTree($response);
            $temp = $array['soap:Envelope'][0]['soap:Body'][0]['GetMsgResponse'][0]['m'][0];

            $message = $temp['inv'][0]['comp'][0];

            // content with no attachment
            $message['content'] = $temp['mp'][0]['mp'][1]['content'][0];

            // content with attachment
            $message['content'] .= $temp['mp'][0]['mp'][0]['mp'][1]['content'][0];

            return $message;
        } else {
            return false;
        }
    }

    /**
     * getSubscribedCalendars
     *
     * get the calendars the user is subscribed to
     *
     * @since        version 1.0
     * @access    public
     * @return    array $subscribed
     */
    public function getSubscribedCalendars() {
        $subscribed = array();
        if (is_array($this->_account_info['link_attribute_name'])) {
            foreach ($this->_account_info['link_attribute_name'] as $i => $name) {
                if ($this->_account_info['link_attribute_view'][$i] == 'appointment')
                    $subscribed[$this->_account_info['link_attribute_id'][$i]] = $name;
            }
        }
        return $subscribed;
    }

// end getSubscribedCalendars

    /**
     * getSubscribedTaskLists
     *
     * get the task lists the user is subscribed to
     *
     * @since        version 1.0
     * @access    public
     * @return    array $subscribed or false
     */
    public function getSubscribedTaskLists() {
        $subscribed = array();
        if (is_array($this->_account_info['link_attribute_name'])) {
            foreach ($this->_account_info['link_attribute_name'] as $i => $name) {
                if ($this->_account_info['link_attribute_view'][$i] == 'task')
                    $subscribed[$this->_account_info['link_attribute_id'][$i]] = $name;
            }
        }
        return $subscribed;
    }

// end getSubscribedCalendars

    /**
     * getFolder
     *
     * get a folder (experimental)
     *
     * @since        version 1.0
     * @access    public
     * @param    string $folder_options options for folder retrieval
     * @return    array $folder or false
     */
    public function getFolder($folder_options = '') {
        $folder_option_string = $this->buildOptionString($folder_options);

        $soap = '<GetFolderRequest xmlns="urn:zimbraMail" visible="1">
                    <folder path="Inbox"/>
                </GetFolderRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $folder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0]['folder'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0]['folder'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0];

            $folder['u'] = (!isset($folder['u'])) ? $folder['folder_attribute_u'][0] : $folder['u'];
            $folder['n'] = (!isset($folder['n'])) ? $folder['folder_attribute_n'][0] : $folder['n'];

            return $folder;
        } else {
            return false;
        }
    }

    public function getAllFolder() {
        $soap = '<GetFolderRequest xmlns="urn:zimbraMail" visible="1">
                </GetFolderRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $folder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0]['folder'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0]['folder'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0];

            $folder['u'] = (!isset($folder['u'])) ? $folder['folder_attribute_u'][0] : $folder['u'];
            $folder['n'] = (!isset($folder['n'])) ? $folder['folder_attribute_n'][0] : $folder['n'];

            return $folder;
        } else {
            return false;
        }
    }

// end getFolder

    public function getFolderAppt($folderId, $folder_options = '') {
        $folder_option_string = $this->buildOptionString($folder_options);

        $soap = '<GetFolderRequest xmlns="urn:zimbraMail" visible="1">
                    <folder l="' . $folderId . '"/>
                </GetFolderRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $folder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0]['folder'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0]['folder'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0];
            return $folder;
        } else {
            return false;
        }
    }

// end getFolder

    public function getFolderCont($folderId, $folder_options = '') {
        $ret = $this->getFolderAppt($folderId, $folder_options = '');
        return($ret);
    }

// end getFolder

    public function getCalEvent($folderId, $folder_options = '') {
        $folder_option_string = $this->buildOptionString($folder_options);

        $datestart = mktime(0, 0, 0, 2, 25, 9) - 1;
        $dateend = $datestart + (366 * 24 * 60 * 60) - 1;
        $soap = '<GetMiniCalRequest xmlns="urn:zimbraMail"  s="' . $datestart . '000" e="' . $dateend . '000">
                    <folder id="' . $folderId . '"/>
                </GetMiniCalRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $folder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0]['folder'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0]['folder'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['GetFolderResponse'][0];
//
//            $folder['u'] = (!isset($folder['u']))?$folder['folder_attribute_u'][0]:$folder['u'];
//            $folder['n'] = (!isset($folder['n']))?$folder['folder_attribute_n'][0]:$folder['n'];

            return $array;
        } else {
            return false;
        }
    }

// end getFolder

    /**
     * getPrefrences
     *
     * get preferences
     *
     * @since        version 1.0
     * @access    public
     * @example    example XML: <GetPrefsRequest> <!-- get only the specified prefs --> [<pref name="{name1}"/> <pref name="{name2}"/>] </GetPrefsRequest>
     * @return    array $prefs or false
     */
    public function getPreferences() {
        $soap = '<GetPrefsRequest xmlns="urn:zimbraAccount" />';
        $response = $this->soapRequest($soap);
        if ($response) {
            $prefs = array();
            $array = $this->makeXMLTree($response);
            foreach ($array['soap:Envelope'][0]['soap:Body'][0]['GetPrefsResponse'][0]['pref'] as $k => $value) {
                $prefs[$array['soap:Envelope'][0]['soap:Body'][0]['GetPrefsResponse'][0]['pref_attribute_name'][$k]] = $value;
            }
            return $prefs;
        } else {
            return false;
        }
    }

// end getPreferences

    /**
     * setPrefrences
     *
     * modify preferences
     *
     * @since        version 1.0
     * @access    public
     * @param    string $options options to set the prefrences
     * @example    example XML: <ModifyPrefsRequest> [<pref name="{name}">{value}</pref>...]+ </ModifyPrefsRequest>
     * @return    boolean
     */
    public function setPreferences($options = '') {
        $option_string = '';
        foreach ($options as $name => $value) {
            $option_string .= '<pref name="' . $name . '">' . $value . '</pref>';
        }

        $soap = '<ModifyPrefsRequest xmlns="urn:zimbraAccount">
                    ' . $option_string . '
                </ModifyPrefsRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            return true;
        } else {
            return false;
        }
    }

// end setPreferences

    /**
     * emailChannel
     *
     * build the email channel
     *
     * @since        version 1.0
     * @access    public
     */
    public function emailChannel() {
//        require_once 'xtemplate.php';
//        $tpl = new XTemplate('/web/pscpages/webapp/portal/channel/email/templates/index.tpl');
//
//        $tpl->parse('main.transition');

        $total_messages = 0;
        $unread_messages = 0;

        $messages = $this->getMessages('in:inbox');
        if (is_array($messages)) {
            $more = $messages['more'];
            foreach ($messages['m'] as $message) {
                $clean_message = array();

                $clean_message['subject'] = (isset($message['su'][0]) && $message['su'][0] != '') ? htmlentities($message['su'][0]) : '[None]';
                $clean_message['subject'] = (strlen($clean_message['subject']) > 20) ? substr($clean_message['subject'], 0, 17) . '...' : $clean_message['subject'];

                $clean_message['body_fragment'] = $message['fr'][0];
                $clean_message['from_email'] = $message['e_attribute_a'][0];
                $clean_message['from'] = ($message['e_attribute_p'][0]) ? htmlspecialchars($message['e_attribute_p'][0]) : $clean_message['from_email'];
                $clean_message['size'] = $this->makeBytesPretty($message['s'], 40 * 1024 * 1024);
                $clean_message['date'] = date('n/j/y', ($message['d'] / 1000));
                $clean_message['id'] = $message['id'];
                $clean_message['url'] = 'http://go.plymouth.edu/mymail/msg/' . $clean_message['id'];

                $clean_message['attachment'] = false;
                $clean_message['status'] = 'read';
                $clean_message['deleted'] = false;
                $clean_message['flagged'] = false;
                if (isset($message['f'])) {
                    $clean_message['attachment'] = (strpos($message['f'], 'a') !== false) ? true : false;
                    $clean_message['status'] = (strpos($message['f'], 'u') !== false) ? 'unread' : 'read';
                    ;
                    $clean_message['deleted'] = (strpos($message['f'], '2') !== false) ? true : false;
                    $clean_message['flagged'] = (strpos($message['f'], 'f') !== false) ? true : false;
                }

//                $tpl->assign('message', $clean_message);
//                $tpl->parse('main.message');
            }
            $inbox = $this->getFolder(array('l' => 2));

            $total_messages = (int) $inbox['n'];
            $unread_messages = (int) $inbox['u'];
        }

//        $tpl->assign('total_messages', $total_messages);
//        $tpl->assign('unread_messages', $unread_messages);

        $info = $this->getInfo(array('sections' => 'mbox'));
        if (is_array($info['attrs'][0]['attr_attribute_name'])) {
            $quota = $info['attrs'][0]['attr'][array_search('zimbraMailQuota', $info['attrs'][0]['attr_attribute_name'])];
            $size_text = $this->makeBytesPretty($info['used'][0], ($quota * 0.75)) . ' out of ' . $this->makeBytesPretty($quota);
//            $tpl->assign('size', $size_text);
        }

        /* include_once 'portal_functions.php';
          $roles = getRoles($this->_username);

          if(in_array('faculty', $roles) || in_array('employee', $roles))
          {
          $tpl->parse('main.away_message');
          } */

        $tpl->parse('main');
        $tpl->out('main');
    }

// end emailChannel

    /**
     * builOptionString
     *
     * make an option string that will be placed as attributes inside an XML tag
     *
     * @since        version 1.0
     * @access    public
     * @param    array $options array of options to be parsed into a string
     * @return    string $options_string
     */
    protected function buildOptionString($options) {
        $options_string = '';
        if (is_array($options)) {
            foreach ($options as $k => $v) {
                $options_string .= ' ' . $k . '="' . $v . '"';
            }
        } else {
            $options_string = $options;
        }
        return $options_string;
    }

// end buildOptionString

    /**
     *    extractAuthToken
     *
     * get the Auth Token out of the XML
     *
     * @since        version 1.0
     * @access    public
     * @param string $xml xml to have the auth token pulled from
     * @return string $auth_token
     */
    private function extractAuthToken($xml) {
        $auth_token = strstr($xml, "<authToken");
        $auth_token = strstr($auth_token, ">");
        $auth_token = substr($auth_token, 1, strpos($auth_token, "<") - 1);
        return $auth_token;
    }

    /**
     * extractSessionID
     *
     * get the Session ID out of the XML
     *
     * @since        version 1.0
     * @access    public
     * @param    string $xml xml to have the session id pulled from
     * @return int $session_id
     */
    private function extractSessionID($xml) {
        $session_id = strstr($xml, "<sessionId");
        $session_id = strstr($session_id, ">");
        $session_id = substr($session_id, 1, strpos($session_id, "<") - 1);
        return $session_id;
    }

// end extractSessionID

    /**
     * extractErrorCode
     *
     * get the error code out of the XML
     *
     * @since        version 1.0
     * @access    public
     * @param    string $xml xml to have the error code pulled from
     * @return int $session_id
     */
    private function extractErrorCode($xml) {
        $session_id = strstr($xml, "<Code");
        $session_id = strstr($session_id, ">");
        $session_id = substr($session_id, 1, strpos($session_id, "<") - 1);
        return $session_id;
    }

// end extractErrorCode

    /**
     * makeBytesPretty
     *
     * turns byte numbers into a more readable format with KB or MB
     *
     * @since        version 1.0
     * @access    public
     * @param    int $bytes bytes to be worked with
     * @param    boolean $redlevel
     * @return int $size
     */
    private function makeBytesPretty($bytes, $redlevel = false) {
        if ($bytes < 1024)
            $size = $bytes . ' B';
        elseif ($bytes < 1024 * 1024)
            $size = round($bytes / 1024, 1) . ' KB';
        else
            $size = round(($bytes / 1024) / 1024, 1) . ' MB';

        if ($redlevel && $bytes > $redlevel) {
            $size = '<span style="color:red">' . $size . '</span>';
        }

        return $size;
    }

// end makeBytesPretty

    /**
     * message
     *
     * if debug is on, show a message
     *
     * @since        version 1.0
     * @access    public
     * @param    string $message message for debug
     */
    protected function message($message) {
        if ($this->debug) {
            echo $message;
        }
    }

// end message

    /**
     * soapRequest
     *
     * make a SOAP request to Zimbra server, returns the XML
     *
     * @since        version 1.0
     * @access    public
     * @param    string $body body of page
     * @param    boolean $header
     * @param    boolean $footer
     * @return    string $response
     */
    protected function soapRequest($body, $header = false, $connecting = false) {
        if (!$connecting && !$this->_connected) {
            throw new Exception('Not Connected to Zimbra Server');
        }

        if ($header == false) {
            $header = '<context xmlns="urn:zimbra">
                            <authToken>' . $this->auth_token . '</authToken>
                            <sessionId id="' . $this->session_id . '">' . $this->session_id . '</sessionId>
                        </context>';
        }

        $soap_message = '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
                            <soap:Header>' . $header . '</soap:Header>
                            <soap:Body>' . $body . '</soap:Body>
                        </soap:Envelope>';
//        $this->message('SOAP message:<textarea>' . $soap_message . '</textarea>');

        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $soap_message);
        $timeout = 2000;
        curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($this->_curl, CURLOPT_TIMEOUT, $timeout);
        if (!($response = curl_exec($this->_curl))) {

            die('ERROR: curl_exec - (' . curl_errno($this->_curl) . ') ' . curl_error($this->_curl));
            //print $this->error;
            return false;
        } elseif (strpos($response, '<soap:Body><soap:Fault>') !== false) {
            $error_code = $this->extractErrorCode($response);

            $this->error = 'ERROR: ' . $error_code . ':<textarea>' . $response . '</textarea>';
            $this->message($this->error);
//            die($soap_message."<br/><br/>/n/n".$response);
            return false;
        }
//        $this->message('SOAP response:<textarea>' . $response . '</textarea><br/><br/>');

        self::$_num_soap_calls++;
        return $response;
    }

// end soapRequest

    /**
     * getNumSOAPCalls
     *
     * get the number of SOAP calls that have been made.  This is for debugging and performancing
     *
     * @since        version 1.0
     * @access    public
     * @return int $this->_num_soap_calls
     */
    public function getNumSOAPCalls() {
        return self::$_num_soap_calls;
    }

// end getNumSOAPCalls

    /**
     * makeXMLTree
     *
     * turns XML into an array
     *
     * @since        version 1.0
     * @access    public
     * @param    string $data data to be built into an array
     * @return     array $ret
     */
    protected function makeXMLTree($data) {
        // create parser
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $data, $values, $tags);
        xml_parser_free($parser);

        // we store our path here
        $hash_stack = array();

        // this is our target
        $ret = array();
        foreach ($values as $key => $val) {

            switch ($val['type']) {
                case 'open':
                    array_push($hash_stack, $val['tag']);
                    if (isset($val['attributes']))
                        $ret = $this->composeArray($ret, $hash_stack, $val['attributes']);
                    else
                        $ret = $this->composeArray($ret, $hash_stack);
                    break;

                case 'close':
                    array_pop($hash_stack);
                    break;

                case 'complete':
                    array_push($hash_stack, $val['tag']);
                    $ret = $this->composeArray($ret, $hash_stack, $val['value']);
                    array_pop($hash_stack);

                    // handle attributes
                    if (isset($val['attributes'])) {
                        foreach ($val['attributes'] as $a_k => $a_v) {
                            $hash_stack[] = $val['tag'] . '_attribute_' . $a_k;
                            $ret = $this->composeArray($ret, $hash_stack, $a_v);
                            array_pop($hash_stack);
                        }
                    }

                    break;
            }
        }

        return $ret;
    }

// end makeXMLTree

    /**
     * &composeArray
     *
     * function used exclusively by makeXMLTree to help turn XML into an array
     *
     * @since        version 1.0
     * @access    public
     * @param    array $array
     * @param    array $elements
     * @param    array $value
     * @return    array $array
     */
    private function &composeArray($array, $elements, $value = array()) {
        global $XML_LIST_ELEMENTS;

        // get current element
        $element = array_shift($elements);

        // does the current element refer to a list
        if (sizeof($elements) > 0) {
            $array[$element][sizeof($array[$element]) - 1] = &$this->composeArray($array[$element][sizeof($array[$element]) - 1], $elements, $value);
        } else { // if (is_array($value))
            $array[$element][sizeof($array[$element])] = $value;
        }

        return $array;
    }

// end composeArray

    /**
     * noop
     *
     * keeps users session alive
     *
     * @since        version 1.0
     * @access    public
     * @return    string xml response from the noop
     */
    public function noop() {
        return $this->soapRequest('<NoOpRequest xmlns="urn:zimbraMail"/>');
    }

    public function BabelCreateFolder($createArray, $folder_options = "", $acl = '') {
//        $this->debug=true;
        $folder_option_string = $this->buildOptionString($folder_options);
        //$view ="appointment"; //search request type
        //$name = "testFolder1";
        //$color = 1; // 0..7
        $flag = ""; // (u)nread, (f)lagged, has (a)ttachment, (r)eplied, (s)ent by me, for(w)arded, calendar in(v)ite,
        //(d)raft, IMAP-\Deleted (x), (n)otification sent, urgent (!), low-priority (?)
        //note ACL : [<acl> <grant perm="{rights}" gt="{grantee-type}" zid="{zimbra-id}" d="{grantee-name}" [args="{args}"] [key="{access-key}"]/>* </acl>]
        $soap = '<CreateFolderRequest  xmlns="urn:zimbraMail" visible="1">';
        $soap .='  <folder name="' . htmlspecialchars($createArray['name']) . '"';
        $soap .='          l="' . $createArray['where'] . '"';
        $soap .='          fie="1"';
        $soap .='          view="' . $createArray['view'] . '"';
        if ("x" . $createArray['color'] != 'x') {
            $soap .= ' color="' . $createArray['color'] . '"';
        }
        $soap.=' f="' . $createArray['flag'] . '"';
        $soap .= '>';
        if ('x' . $acl != "x") {
            $soap.=$acl;
        }

        $soap .='   </folder>';
        $soap .=' </CreateFolderRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['CreateFolderResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['CreateFolderResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['CreateFolderResponse'][0];
            $nFolder = array();
            $nFolder['id'] = $newFolder["folder_attribute_id"][0];
            $nFolder['name'] = $newFolder['folder_attribute_name'][0];
            $nFolder['view'] = $newFolder['folder_attribute_view'][0];
            $nFolder['color'] = $newFolder['folder_attribute_color'][0];
            $nFolder['n'] = $newFolder['folder_attribute_n'][0];
            $nFolder['parent'] = $newFolder['folder_attribute_l'][0];
            $nFolder['s'] = $newFolder['folder_attribute_s'][0];
            $nFolder['rev'] = $newFolder['folder_attribute_rev'][0];
            $nFolder['isFolder'] = $newFolder['folder'][0];
//            $folder['u'] = (!isset($folder['u']))?$folder['folder_attribute_u'][0]:$folder['u'];
//            $folder['n'] = (!isset($folder['n']))?$folder['folder_attribute_n'][0]:$folder['n'];
            return $nFolder;
        } else {
            return false;
        }
    }

    public function BabelGetAppointment($pId) {
//offset => depart
        $soap = '  <GetAppointmentRequest  xmlns="urn:zimbraMail" id="' . $pId . '" >';
        $soap .= '</GetAppointmentRequest>';

        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['GetAppointmentResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['GetAppointmentResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['GetAppointmentResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelSeachGAL($inpt, $limit = 100) {

        $soap = '        <AutoCompleteGalRequest  xmlns="urn:zimbraAccount" visible="1"  limit="' . $limit . '" type="account">
                         <name>' . $inpt . '</name>
        </AutoCompleteGalRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['AutoCompleteGalResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['AutoCompleteGalResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['AutoCompleteGalResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelgetAccountInfo($userName) {
        //$folder_option_string = $this->buildOptionString($folder_options);
        $flag = ""; // (u)nread, (f)lagged, has (a)ttachment, (r)eplied, (s)ent by me, for(w)arded, calendar in(v)ite,
        //(d)raft, IMAP-\Deleted (x), (n)otification sent, urgent (!), low-priority (?)
//note ACL : [<acl> <grant perm="{rights}" gt="{grantee-type}" zid="{zimbra-id}" d="{grantee-name}" [args="{args}"] [key="{access-key}"]/>* </acl>]
        $soap = '        <GetAccountInfoRequest  xmlns="urn:zimbraAccount" visible="1">
                         <account by="name">' . $userName . '@' . $this->_domain . '</account>
        </GetAccountInfoRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['GetAccountInfoResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['GetAccountInfoResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['GetAccountInfoResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelCreateAccount($userName, $pass, $detail = array()) {
        /*
         *
          <CreateAccountRequest>
          <name>...</name>
          <password>...</password>*
          <a n="attr-name">...</a>+
          </CreateAccountRequest>

          <CreateAccountResponse>
          <account name="{name}" id="{id}">
          <a n="...">...</a>+
          </account>
          </CreateAccountResponse>

          Notes:

          accounts without passwords can't be logged into

          name must include domain (uid@name), and domain specified in name must exist

          default value for zimbraAccountStatus is "active"
          attribute are :
          # cn            - full name, common name
          # co            - country friendly name
          # company       - company (company name)
          # displayName   - name to display in admin tool, outlook uses as well
          #                 (cn is multi-valued)
          # gn            - first name (given name)
          # initials      - middle initial
          # l             - city (locality)
          # ou            - organizational unit
          # physicalDeliveryOfficeName - office
          # street        - street address
          # postalCode    - zip code
          # sn            - last name (sir name)
          # st            - state
          # telephoneNumber - phone


          Access: domain admin sufficient
         *
         * */
        $soap = '        <CreateAccountRequest  xmlns="urn:zimbraAdmin" visible="1">';
        $soap .='                 <name>' . $userName . '@' . $this->_domain . '</name>';
        $soap .='                 <password>' . $pass . '</password>';
        foreach (array('cn', 'co', 'company', 'displayName', 'gn', 'initials', 'l', 'ou', 'physicalDeliveryOfficeName', 'street', 'postalCode', 'sn', 'st', 'telephoneNumber') as $key) {
            if ($detail[$key] . "x" != "x") {
                $soap .= '     <a n="' . $key . '">' . htmlspecialchars($detail[$key]) . '</a>';
            }
        }
        $soap .='</CreateAccountRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
//            var_dump($response);
            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['CreateAccountResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['CreateAccountResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['CreateAccountResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelCreateRessources($userName, $detail = array()) {
        /*
         *
          1493 <CreateCalendarResourceRequest>
          1494   <name>...</name>
          1495   <password>...</password>*
          1496   <a n="attr-name">...</a>+
          1497 </CreateCalendarResourceRequest>
          1498
          1499 <CreateCalendarResourceResponse>
          1500   <calresource name="{name}" id="{id}">
          1501     <a n="...">...</a>+
          1502   </calresource>
          1503 </CreateCalendarResourceResponse>
          1504
          1505 Notes:
          1506
          1507   name must include domain (uid@name), and domain specified in name must exist
          1508
          1509   a calendar resource does not have a password (you can't login as a resource)
          1510
          1511 Access: domain admin sufficient

         * */

        $soap = '        <CreateCalendarResourceRequest xmlns="urn:zimbraAdmin" visible="1">';
        $soap .='                 <name>' . $userName . '@' . $this->_domain . '</name>';



        foreach (array('cn', 'co', 'company', 'displayName', 'gn', 'initials', 'l', 'ou', 'physicalDeliveryOfficeName', 'street', 'postalCode', 'sn', 'st',
    'telephoneNumber', 'zimbraCalResType', 'zimbraCalResBuilding', 'zimbraCalResContactEmail', 'zimbraCalResFloor',
    'zimbraAccountStatus', 'zimbraCalResRoom', 'zimbraCalResSite'
    , 'zimbraCalResAutoDeclineIfBusy', 'zimbraAllowAnyFromAddress', 'zimbraCalResContactName'
    , 'zimbraCalResContactPhone', 'zimbraCalResCapacity',
        ) as $key) {
            if ($detail[$key] . "x" != "x") {
                $soap .= '     <a n="' . $key . '">' . htmlspecialchars($detail[$key]) . '</a>';
            }
        }
        $soap .='</CreateCalendarResourceRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
            $newFolder = $array['soap:Envelope'][0]['soap:Body'][0]['CreateCalendarResourceResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelModRessources($pId, $detail = array()) {
        /*
         *
          //<ModifyAccountRequest>
          //  <id>{value-of-zimbraId}</id>
          //  <a n="...">...</a>+
          //</ModifyAccountRequest>
         *
         * */
        $soap = '        <ModifyCalendarResourceRequest  xmlns="urn:zimbraAdmin" visible="1">';
        $soap .='                 <id>' . $pId . '</id>';
        //foreach(array('cn','co','company','displayName','gn','initials','l','ou','physicalDeliveryOfficeName','street','postalCode','sn','st','telephoneNumber') as $key)
        foreach ($detail as $key => $val) {
            if ($val . "x" != "x") {
                $soap .= '     <a n="' . $key . '">' . htmlspecialchars($val) . '</a>';
            } else {
                $soap .= '     <a n="' . $key . '"/>';
            }
        }
        $soap .='</ModifyCalendarResourceRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['ModifyCalendarResourceResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['ModifyCalendarResourceResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['ModifyCalendarResourceResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelSearchRessources($searchOpt = array(), $where = 'anywhere', $type = 'Equipment', $limit = 300, $sortBy = 'nameAsc', $offset = 0, $fetch = 'all') {
//offset => depart
        $soap = '  <SearchCalendarResourcesRequest  xmlns="urn:zimbraAdmin"  visible="1"
                                   limit="' . $limit . '"
                                   offset="' . $offset . '"
                                   sortBy="' . $sortBy . '"
                                   fetch="' . $fetch . '">';
        foreach ($searchOpt as $key => $val) {
            if ($val . "x" != "x") {
                $soap .= '     <a n="' . $key . '">' . htmlspecialchars($val) . '</a>';
            } else {
                $soap .= '     <a n="' . $key . '"/>';
            }
        }
        $soap .= "<searchFilter>";

        $soap .= '<cond not="0" attr="zimbraCalResType" op="eq" value="' . $type . '" />';
        $soap .= "</searchFilter>";

        $soap .='</SearchCalendarResourcesRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['SearchCalendarResourcesResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['SearchCalendarResourcesResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['SearchCalendarResourcesResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelGetRessources($pId) {
        //offset => depart
        $soap = '  <GetCalendarResourceRequest  xmlns="urn:zimbraAdmin" >';
        $soap .= '<calresource by="id">';
        $soap .= "" . $pId . "";
        $soap .= '</calresource>';
        $soap .= '</GetCalendarResourceRequest>';

        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['GetCalendarResourceResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['GetCalendarResourceResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['GetCalendarResourceResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelDeleteRessources($pId) {
        /*
         *

          <DeleteAccountRequest>
          <id>{value-of-zimbraId}</id>
          </DeleteAccountRequest>

         *
         * */
        $soap = '        <DeleteCalendarResourceRequest  xmlns="urn:zimbraAdmin" visible="1">';
        $soap .='                 <id>' . $pId . '</id>';
        $soap .='</DeleteCalendarResourceRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['DeleteCalendarResourceResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['DeleteCalendarResourceResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['DeleteCalendarResourceResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelDeleteAccount($pId) {
        /*
         *

          <DeleteAccountRequest>
          <id>{value-of-zimbraId}</id>
          </DeleteAccountRequest>

         *
         * */
        $soap = '        <DeleteAccountRequest  xmlns="urn:zimbraAdmin" visible="1">';
        $soap .='                 <id>' . $pId . '</id>';
        $soap .='</DeleteAccountRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['DeleteAccountResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['DeleteAccountResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['DeleteAccountResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelRenameAccount($pId, $pNewName) {
        /*
         *
          <RenameAccountRequest>
          <id>{value-of-zimbraId}</id>
          <newName>{new-account-name}</newName>
          </RenameAccountRequest>
         *
         * */
        $soap = '        <RenameAccountRequest  xmlns="urn:zimbraAdmin" visible="1">';
        $soap .='                 <id>' . $pId . '</id>';
        $soap .='                 <newName>' . $pNewName . '@' . $this->_domain . '</newName>';
        $soap .='</RenameAccountRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['RenameAccountResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['RenameAccountResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['RenameAccountResponse'][0];
            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelRenameRessources($pId, $pNewName) {
        /*
         *
          <RenameAccountRequest>
          <id>{value-of-zimbraId}</id>
          <newName>{new-account-name}</newName>
          </RenameAccountRequest>
         *
         * */
        $soap = '        <RenameCalendarResourceRequest  xmlns="urn:zimbraAdmin" visible="1">';
        $soap .='                 <id>' . $pId . '</id>';
        $soap .='                 <newName>' . $pNewName . '@' . $this->_domain . '</newName>';
        $soap .='</RenameCalendarResourceRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['RenameCalendarResourceResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['RenameCalendarResourceResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['RenameCalendarResourceResponse'][0];
            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelChangePass($pId, $pNewPass) {
        /*
         *
          <RenameAccountRequest>
          <id>{value-of-zimbraId}</id>
          <newName>{new-account-name}</newName>
          </RenameAccountRequest>
         *
         * */
        $soap = '        <SetPasswordRequest  xmlns="urn:zimbraAdmin" visible="1">';
        $soap .='                 <id>' . $pId . '</id>';
        $soap .='                 <newPassword>' . $pNewPass . '</newPassword>';
        $soap .='</SetPasswordRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['SetPasswordResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['SetPasswordResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['SetPasswordResponse'][0];
            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelGetAllAdminAccountsRequest() {
        /*
         *
          //<ModifyAccountRequest>
          //  <id>{value-of-zimbraId}</id>
          //  <a n="...">...</a>+
          //</ModifyAccountRequest>
         *
         * */
        $soap = '        <GetAllAdminAccountsRequest  xmlns="urn:zimbraAdmin">';
        $soap .='</GetAllAdminAccountsRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['GetAllAdminAccountsResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['GetAllAdminAccountsResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['GetAllAdminAccountsResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelUpdateAccount($pId, $detail = array()) {
        /*
         *
          //<ModifyAccountRequest>
          //  <id>{value-of-zimbraId}</id>
          //  <a n="...">...</a>+
          //</ModifyAccountRequest>
         *
         * */
        $soap = '        <ModifyAccountRequest  xmlns="urn:zimbraAdmin" visible="1">';
        $soap .='                 <id>' . $pId . '</id>';
        //foreach(array('cn','co','company','displayName','gn','initials','l','ou','physicalDeliveryOfficeName','street','postalCode','sn','st','telephoneNumber') as $key)
        foreach ($detail as $key => $val) {
            if ($val . "x" != "x") {
                $soap .= '     <a n="' . $key . '">' . htmlspecialchars($val) . '</a>';
            } else {
                $soap .= '     <a n="' . $key . '"/>';
            }
        }
        $soap .='</ModifyAccountRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['ModifyAccountResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['ModifyAccountResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['ModifyAccountResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public $documentSearchResponse = array();

    public function BabelSearchRequest($searchOpt = array(), $where = 'anywhere', $type = 'contact', $limit = 300, $sortBy = 'nameAsc', $offset = 0, $fetch = 'all') {
//offset => depart
        $soap = '  <SearchRequest  xmlns="urn:zimbraMail"
                                   limit="' . $limit . '"
                                   offset="' . $offset . '"
                                   sortBy="' . $sortBy . '"
                                   types="' . $type . '"
                                   fetch="' . $fetch . '"
               >';
        $searchStr = false;
        $iter = 0;
        if (count($searchOpt) > 0) {
            foreach ($searchOpt as $key => $val) {
//                if  ($iter > 1 && $val['condition'])
//                {
//                    $searchStr .= " " . $val['condition'] . " ";
//                }
                foreach ($val as $key1 => $val1) {
                    if ($key1 == "condition" && $iter > 0) {
                        $searchStr .= " " . $val1;
                    } else {
                        $searchStr .= " FIELD[" . $key1 . "]:" . $val1 . "";
                    }
                    $iter++;
                }
            }
            $searchStr = preg_replace('/ ?OR ?$/', "", $searchStr);
            $searchStr = preg_replace('/ ?AND ?$/', "", $searchStr);
        }
        $soap .= '<query>is:' . $where . ' ';
        if ($searchStr) {
            $soap .= $searchStr;
        }
        $soap .="</query>";



        $soap .= '</SearchRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['SearchResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['SearchResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['SearchResponse'][0];
            array_push($this->documentSearchResponse, $newFolder);
//            $folder['u'] = (!isset($folder['u']))?$folder['folder_attribute_u'][0]:$folder['u'];
//            $folder['n'] = (!isset($folder['n']))?$folder['folder_attribute_n'][0]:$folder['n'];

            return $newFolder;
        } else {
            return false;
        }

        /*
         *
          <query>{query-string}</query>
          </SearchRequest>
         */
    }

    public function BabelSearchGALRequest($searchOpt = array(), $type = 'account', $limit = 300, $sortBy = 'nameAsc', $offset = 0, $fetch = 'all') {
//offset => depart
        $soap = '  <SearchDirectoryRequest  limit="' . $limit . '" offset="' . $offset . '" xmlns="urn:zimbraAdmin" domain="' . $this->_domain . '"
                                   types="' . $type . '"
               >';
        $searchStr = false;
        $iter = 0;
        if (count($searchOpt) > 0) {
            foreach ($searchOpt as $key => $val) {
//                if  ($iter > 1 && $val['condition'])
//                {
//                    $searchStr .= " " . $val['condition'] . " ";
//                }
                foreach ($val as $key1 => $val1) {
                    if ($key1 == "condition" && $iter > 0) {
                        $searchStr .= " " . $val1;
                    } else {
                        $searchStr .= " FIELD[" . $key1 . "]:" . $val1 . "";
                    }
                    $iter++;
                }
            }
            $searchStr = preg_replace('/ ?OR ?$/', "", $searchStr);
            $searchStr = preg_replace('/ ?AND ?$/', "", $searchStr);
        }
        $soap .= '<query>';
        if ($searchStr) {
            $soap .= $searchStr;
        }
        $soap .= "</query>";
        $soap .= '</SearchDirectoryRequest>';
        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);
            $newFolder = $array['soap:Envelope'][0]['soap:Body'][0]['SearchDirectoryResponse'][0];
            array_push($this->documentSearchResponse, $newFolder);
            return $newFolder;
        } else {
            return false;
        }

        /*
         *
          <query>{query-string}</query>
          </SearchRequest>
         */
    }

    public $documentParsedResponse;

    public function parseDocumentSearchResponse($arr = array()) {
        if (count($this->documentSearchResponse) < 1) {
            $this->documentSearchResponse = $arr;
        }
        $fileArray = array();
        foreach ($this->documentSearchResponse as $key => $val) {//pour chaque round
            if (is_array($val['doc'])) {
                foreach ($val['doc'] as $docKey => $docVal) {//pour chaque doc
                    if (preg_match('/^\./', utf8_decode($val['doc_attribute_name'][$docKey]))) {
                        continue;
                    }
                    //Si la réponse de zimbra est NULL
                    if ($val["doc_attribute_id"][$docKey] . "x" == "x") {
                        continue;
                    }
                    if (is_array($docVal)) { // précision sur le fichier
                        $fileArray[$val["doc_attribute_id"][$docKey]][$docKey] = array("name" => utf8_decode($val['doc_attribute_name'][$docKey]),
                            "mime" => $val['doc_attribute_ct'][$docKey],
                            "url" => $val['doc_attribute_rest'][$docKey],
                            "author" => $val['doc_attribute_cr'][$docKey],
                            "rev" => $val['doc_attribute_rev'][$docKey],
                            "date_mod" => $val['doc_attribute_md'][$docKey],
                            "date" => $val['doc_attribute_d'][$docKey],
                            "version" => $val['doc_attribute_ver'][$docKey],
                            "parentFolder" => $val['doc_attribute_l'][$docKey],
                            "size" => $val['doc_attribute_s'][$docKey],
                            "fileSpec" => array("date" => $docVal['d'],
                                "date_mod" => $docVal['md'],
                                "version" => $docVal['ver'],
                                "parentFolder" => $docVal['l'],
                                "id" => $docVal['id'],
                                "rev" => $docVal['rev'],
                                "date_creat" => $docVal['cd'],
                                "url" => $docVal['rest'],
                                "mime" => $docVal['ct'],
                                "desc" => iconv('ISO-8859-1', 'UTF-8', $docVal['fr'][0]),
                                "size" => $docVal['s'],
                                "author" => $docVal['cr']));
                    } else {
                        $fileArray[$val["doc_attribute_id"][$docKey]][$docKey] = array("name" => utf8_decode($val['doc_attribute_name'][$docKey]),
                            "mime" => $val['doc_attribute_ct'][$docKey],
                            "url" => $val['doc_attribute_rest'][$docKey],
                            "author" => $val['doc_attribute_cr'][$docKey],
                            "rev" => $val['doc_attribute_rev'][$docKey],
                            "date_mod" => $val['doc_attribute_md'][$docKey],
                            "date" => $val['doc_attribute_d'][$docKey],
                            "version" => $val['doc_attribute_ver'][$docKey],
                            "parentFolder" => $val['doc_attribute_l'][$docKey],
                            "size" => $val['doc_attribute_s'][$docKey],
                            'fileSpec' => false);
                    }
                }
            }
        }

        $this->documentParsedResponse = $fileArray;
        return($fileArray);
    }

    public $appointmentFolderLevel = array();

    public function parseRecursiveAptFolder($zimDirArr, $level = 0) {

        $zimDirArrFolder = $zimDirArr['folder'];
        if (sizeof($zimDirArrFolder) > 0)
            foreach ($zimDirArrFolder as $key => $val) { //recurse avec folder_attribute_id et folder_attribute_name
                if ($zimDirArr['folder_attribute_view'][$key] == "appointment") { //Folder ex Calendar
                    array_push($this->appointmentFolderId, $zimDirArr['folder_attribute_id'][$key]);
                    array_push($this->appointmentFolderName, $zimDirArr['folder_attribute_name'][$key]);
                    array_push($this->appointmentFolderDesc, $val);
                    array_push($this->appointmentFolderLevel, array("level" => $level, "parent" => $zimDirArr['folder_attribute_l'][$key], "name" => $zimDirArr['folder_attribute_name'][$key], "id" => $zimDirArr['folder_attribute_id'][$key]));
                } else if ($val['view'] == "appointment") { //recurse KO //View
                    //2 choses à recuperer :> le folder simple et le folder de folder
                    array_push($this->appointmentFolderId, $val['id']);
                    array_push($this->appointmentFolderName, $val['name']);
                    array_push($this->appointmentFolderDesc, $val);
                    array_push($this->appointmentFolderLevel, array("level" => $level, "parent" => $val['l'], "name" => $val['name'], "id" => $val['id']));
                    $level+=1;
                    if (is_array($val['folder'])) {
                        $iter = 0;
                        foreach ($val['folder'] as $key1 => $val1) {
                            if (is_array($val1)) {
                                $tmpLevel = $level + 1;
                                array_push($this->appointmentFolderId, $val1['id']);
                                array_push($this->appointmentFolderName, $val1['name']);
                                array_push($this->appointmentFolderDesc, $val);
                                array_push($this->appointmentFolderLevel, array("level" => $tmpLevel, "parent" => $val1['l'], "name" => $val1['name'], "id" => $val1['id']));

                                $tmpLevel = $level + 1;
                                if (is_array($val1['folder'])) {
                                    $tmpLevel = $level + 1;
                                    $this->parseRecursiveAptFolder($val1, $tmpLevel);
                                }
                            } else {
                                $tmpLevel = $level + 1;
                                $tmpLevel = $level + 1;
                                array_push($this->appointmentFolderId, $val['folder_attribute_id'][$iter]);
                                array_push($this->appointmentFolderName, $val['folder_attribute_name'][$iter]);
                                array_push($this->appointmentFolderDesc, $val);
                                array_push($this->appointmentFolderLevel, array("level" => $tmpLevel, "parent" => $val['folder_attribute_l'][$iter], "name" => $val['folder_attribute_name'][$iter], "id" => $val['folder_attribute_id'][$iter]));
                                $iter++;
                            }
                        }
                    }
                }// if / else if
            }//foreach
        else
            echo "Pas de ressources";
    }

//function

    public function BabelDeleteContact($pId) {
//        <ContactActionRequest>
//  <!-- some actions can be preceeded by a "!" to negate them -->
//  <action id="{list}" op="move|delete|flag|trash|tag|update" tag="..." l="..."/>
//</ContactActionRequest>
        $removeId = $pId;
        if (is_array($pId)) {
            $removeId = join($pId, ","); //not tested
        }
        $soap = '<ContactActionRequest   xmlns="urn:zimbraMail"  >';
        $soap .= "  <action op='delete' id='" . $pId . "' />";
        $soap .= "  </ContactActionRequest>";

        $response = $this->soapRequest($soap);
        if ($response) {
            $responseArray = $this->makeXMLTree($response);
            $ret = $responseArray['soap:Envelope'][0]['soap:Body'][0]['FolderActionResponse'][0];


            return ($ret);
        } else {
            return false;
        }
    }

    public function BabelDeleteFolder($pId) {


        $soap = '<FolderActionRequest   xmlns="urn:zimbraMail" browseBy="' . $browseBy . '"  >';
        $soap .= "  <action op='delete' id='" . $pId . "' />";
        $soap .= "  </FolderActionRequest>";

        $response = $this->soapRequest($soap);
        if ($response) {
            $responseArray = $this->makeXMLTree($response);
            $ret = $responseArray['soap:Envelope'][0]['soap:Body'][0]['FolderActionResponse'][0];


            return ($ret);
        } else {
            return false;
        }

//    <FolderActionRequest>
//  <action id="{list}" op="read|delete|rename|move|trash|empty|color|grant|url|import|sync|fb|[!]check|update|[!]syncon" [l="{target-folder}"]
//                      [name="{new-name}"] [color="{new-color}"] [zid="{grantee-zimbra-id}"] [url="{target-url}"]
//                      [excludeFreeBusy="{exclude-free-busy-boolean}">
//    [<grant perm="..." gt="..." d="..." [args="..."] [key="..."]/>]
//  </action>
//</FolderActionRequest>
//
//<FolderActionResponse>
//  <action id="{list}" op="read|delete|empty|rename|move|trash|color|grant|url|import|sync|fb|[!]check|update" [zid="{grantee-zimbra-id}"]/>
//</FolderActionResponse>
//
//Actions:
//  <action op="read" id="{list}"/>
//    - mark all items in the folder as read
//
//  <action op="delete" id="{list}"/>
//    - hard-delete the folder, all items in the folder, and all the folder's subfolders
    }

    public function BabelRenameFolder($pId, $pnewName) {


        $soap = '<FolderActionRequest   xmlns="urn:zimbraMail"  >';
        $soap .= "  <action op='rename' id='" . $pId . "' name='" . $pnewName . "' />";
        $soap .= "  </FolderActionRequest>";

        $response = $this->soapRequest($soap);
        if ($response) {
            $responseArray = $this->makeXMLTree($response);
            $ret = $responseArray['soap:Envelope'][0]['soap:Body'][0]['FolderActionResponse'][0];


            return ($ret);
        } else {
            return false;
        }

//    <FolderActionRequest>
//  <action id="{list}" op="read|delete|rename|move|trash|empty|color|grant|url|import|sync|fb|[!]check|update|[!]syncon" [l="{target-folder}"]
//                      [name="{new-name}"] [color="{new-color}"] [zid="{grantee-zimbra-id}"] [url="{target-url}"]
//                      [excludeFreeBusy="{exclude-free-busy-boolean}">
//    [<grant perm="..." gt="..." d="..." [args="..."] [key="..."]/>]
//  </action>
//</FolderActionRequest>
//
//<FolderActionResponse>
//  <action id="{list}" op="read|delete|empty|rename|move|trash|color|grant|url|import|sync|fb|[!]check|update" [zid="{grantee-zimbra-id}"]/>
//</FolderActionResponse>
//
//Actions:
//  <action op="read" id="{list}"/>
//    - mark all items in the folder as read
//
//  <action op="delete" id="{list}"/>
//    - hard-delete the folder, all items in the folder, and all the folder's subfolders
    }

    public $contactFolderLevel = array();

    public function parseRecursiveContactFolder($zimDirArr, $level = 0) {

        $zimDirArrFolder = $zimDirArr['folder'];
        if (sizeof($zimDirArrFolder) > 0)
            foreach ($zimDirArrFolder as $key => $val) { //recurse avec folder_attribute_id et folder_attribute_name
                if ($zimDirArr['folder_attribute_view'][$key] == "contact") { //Folder ex Calendar
                    array_push($this->contactFolderId, $zimDirArr['folder_attribute_id'][$key]);
                    array_push($this->contactFolderName, $zimDirArr['folder_attribute_name'][$key]);
                    array_push($this->contactFolderDesc, $val);
                    array_push($this->contactFolderLevel, array("level" => $level, "parent" => $zimDirArr['folder_attribute_l'][$key], "name" => $zimDirArr['folder_attribute_name'][$key], "id" => $zimDirArr['folder_attribute_id'][$key]));
                } else if ($val['view'] == "contact") { //recurse KO //View
                    //2 choses à recuperer :> le folder simple et le folder de folder
                    array_push($this->contactFolderId, $val['id']);
                    array_push($this->contactFolderName, $val['name']);
                    array_push($this->contactFolderDesc, $val);
                    array_push($this->contactFolderLevel, array("level" => $level, "parent" => $val['l'], "name" => $val['name'], "id" => $val['id']));
                    $level+=1;
                    if (is_array($val['folder'])) {
                        $iter = 0;
                        foreach ($val['folder'] as $key1 => $val1) {
                            if (is_array($val1)) {
                                $tmpLevel = $level + 1;
                                array_push($this->contactFolderId, $val1['id']);
                                array_push($this->contactFolderName, $val1['name']);
                                array_push($this->contactFolderDesc, $val);
                                array_push($this->contactFolderLevel, array("level" => $tmpLevel, "parent" => $val1['l'], "name" => $val1['name'], "id" => $val1['id']));

                                $tmpLevel = $level + 1;
                                if (is_array($val1['folder'])) {
                                    $tmpLevel = $level + 1;
                                    $this->parseRecursiveContactFolder($val1, $tmpLevel);
                                }
                            } else {
                                $tmpLevel = $level + 1;
                                $tmpLevel = $level + 1;
                                array_push($this->contactFolderId, $val['folder_attribute_id'][$iter]);
                                array_push($this->contactFolderName, $val['folder_attribute_name'][$iter]);
                                array_push($this->contactFolderDesc, $val);
                                array_push($this->contactFolderLevel, array("level" => $tmpLevel, "parent" => $val['folder_attribute_l'][$iter], "name" => $val['folder_attribute_name'][$iter], "id" => $val['folder_attribute_id'][$iter]));
                                $iter++;
                            }
                        }
                    }
                }// if / else if
            }//foreach
    }

//function

    public $wikiFolderLevel = array();

    public function parseRecursiveWikiFolder($zimDirArr, $level = 0) {

        $zimDirArrFolder = $zimDirArr['folder'];
        foreach ($zimDirArrFolder as $key => $val) { //recurse avec folder_attribute_id et folder_attribute_name
            if ($zimDirArr['folder_attribute_view'][$key] == "wiki") { //Folder ex Calendar
                array_push($this->wikiFolderId, $zimDirArr['folder_attribute_id'][$key]);
                array_push($this->wikiFolderName, $zimDirArr['folder_attribute_name'][$key]);
                array_push($this->wikiFolderDesc, $val);
                array_push($this->wikiFolderLevel, array("level" => $level, "parent" => $zimDirArr['folder_attribute_l'][$key], "name" => $zimDirArr['folder_attribute_name'][$key], "id" => $zimDirArr['folder_attribute_id'][$key]));
            } else if ($val['view'] == "wiki") { //recurse KO //View
                //2 choses à recuperer :> le folder simple et le folder de folder
                array_push($this->wikiFolderId, $val['id']);
                array_push($this->wikiFolderName, $val['name']);
                array_push($this->wikiFolderDesc, $val);
                array_push($this->wikiFolderLevel, array("level" => $level, "parent" => $val['l'], "name" => $val['name'], "id" => $val['id']));
                $level+=1;
                if (is_array($val['folder'])) {
                    $iter = 0;
                    foreach ($val['folder'] as $key1 => $val1) {
                        if (is_array($val1)) {
                            $tmpLevel = $level + 1;
                            array_push($this->wikiFolderId, $val1['id']);
                            array_push($this->wikiFolderName, $val1['name']);
                            array_push($this->wikiFolderDesc, $val);
                            array_push($this->wikiFolderLevel, array("level" => $tmpLevel, "parent" => $val1['l'], "name" => $val1['name'], "id" => $val1['id']));

                            $tmpLevel = $level + 1;
                            if (is_array($val1['folder'])) {
                                $tmpLevel = $level + 1;
                                $this->parseRecursiveWikiFolder($val1, $tmpLevel);
                            }
                        } else {
                            $tmpLevel = $level + 1;
                            $tmpLevel = $level + 1;
                            array_push($this->wikiFolderId, $val['folder_attribute_id'][$iter]);
                            array_push($this->wikiFolderName, $val['folder_attribute_name'][$iter]);
                            array_push($this->wikiFolderDesc, $val);
                            array_push($this->wikiFolderLevel, array("level" => $tmpLevel, "parent" => $val['folder_attribute_l'][$iter], "name" => $val['folder_attribute_name'][$iter], "id" => $val['folder_attribute_id'][$iter]));
                            $iter++;
                        }
                    }
                }
            }// if / else if
        }//foreach
    }

//function

    public $documentFolderLevel = array();
    public $errArr = array();
    public $lastLevelZimbraDoc = 0;

    public function parseRecursiveDocumentFolder($zimDirArr, $level = 0) {
        $zimDirArrFolder = $zimDirArr['folder'];
        if ($this->lastLevelZimbraDoc < $level) {
            $this->lastLevelZimbraDoc = $level;
        }

//        if (is_array($zimDirArrFolder))
//        {
        foreach ($zimDirArrFolder as $key => $val) { //recurse avec folder_attribute_id et folder_attribute_name
            if ($zimDirArr['folder_attribute_view'][$key] == "document") { //Folder ex Calendar
                array_push($this->documentFolderId, $zimDirArr['folder_attribute_id'][$key]);
                array_push($this->documentFolderName, $zimDirArr['folder_attribute_name'][$key]);
                array_push($this->documentFolderDesc, $val);
                array_push($this->documentFolderLevel, array("level" => $level, "parent" => $zimDirArr['folder_attribute_l'][$key], "name" => $zimDirArr['folder_attribute_name'][$key], "id" => $zimDirArr['folder_attribute_id'][$key]));
            } else if ($val['view'] == "document") { //recurse KO //View
                //2 choses à recuperer :> le folder simple et le folder de folder
                array_push($this->documentFolderId, $val['id']);
                array_push($this->documentFolderName, $val['name']);
                array_push($this->documentFolderDesc, $val);
                array_push($this->documentFolderLevel, array("level" => $level, "parent" => $val['l'], "name" => $val['name'], "id" => $val['id']));
                $level+=1;
                if ($this->lastLevelZimbraDoc < $level) {
                    $this->lastLevelZimbraDoc = $level;
                }
                if (is_array($val['folder'])) {
                    $iter = 0;
                    foreach ($val['folder'] as $key1 => $val1) {
                        if (is_array($val1)) {
                            $tmpLevel = $level + 1;
                            if ($this->lastLevelZimbraDoc < $tmpLevel) {
                                $this->lastLevelZimbraDoc = $tmpLevel;
                            }
                            array_push($this->documentFolderId, $val1['id']);
                            array_push($this->documentFolderName, $val1['name']);
                            array_push($this->documentFolderDesc, $val);
                            array_push($this->documentFolderLevel, array("level" => $tmpLevel, "parent" => $val1['l'], "name" => $val1['name'], "id" => $val1['id']));
                            if (is_array($val1['folder'])) {
                                $tmpLevel = $level + 1;
                                $this->parseRecursiveDocumentFolder($val1, $tmpLevel);
                                if ($this->lastLevelZimbraDoc < $tmpLevel) {
                                    $this->lastLevelZimbraDoc = $tmpLevel;
                                }
                            }
                        } else {
                            $tmpLevel = $level + 1;
                            $tmpLevel = $level + 1;
                            if ($this->lastLevelZimbraDoc < $tmpLevel) {
                                $this->lastLevelZimbraDoc = $tmpLevel;
                            }
                            array_push($this->documentFolderId, $val['folder_attribute_id'][$iter]);
                            array_push($this->documentFolderName, $val['folder_attribute_name'][$iter]);
                            array_push($this->documentFolderDesc, $val);
                            array_push($this->documentFolderLevel, array("level" => $tmpLevel, "parent" => $val['folder_attribute_l'][$iter], "name" => $val['folder_attribute_name'][$iter], "id" => $val['folder_attribute_id'][$iter]));
                            $iter++;
                        }
                    }
                }
            }// if / else if
        }//foreach
    }

//function

    public $tagArray = array();

    public function getTags() {
        $soap = '        <GetTagRequest   xmlns="urn:zimbraMail"  />';
        $response = $this->soapRequest($soap);
        if ($response) {
            $responseArray = $this->makeXMLTree($response);
            $ret = $responseArray['soap:Envelope'][0]['soap:Body'][0]['GetTagResponse'][0];
            foreach ($ret['tag'] as $key => $val) {
                $tagInternalId = $key;
                $color = $ret['tag_attribute_color'][$tagInternalId];
                $name = $ret['tag_attribute_name'][$tagInternalId];
                $id = $ret['tag_attribute_id'][$tagInternalId];
                $this->tagArray[$tagInternalId] =
                        array("color" => $color,
                            "name" => $name,
                            "uid" => $id
                );
            }

            return ($ret);
        } else {
            return false;
        }
    }

//function

    public function displayTagsSelect($print = false, $htmlId, $pHtmlName = '', $optArr = array()) {
        $html = "";
        $htmlName = $pHtmlName;
        if ($pHtmlName . "x" == "x") {
            $htmlName = $htmlId;
        }
        if (count($optArr) < 1) {
            $optArr = $this->tagArray;
        }
        if (count($optArr) < 1) {
            $this->getTags();
            $optArr = $this->tagArray;
        }
        if (count($optArr) > 0) {
            $html = "<SELECT id='" . $htmlId . "' name='" . $htmlName . "'>";
            $html .= "<OPTION value='0'>Select-></OPTION>";
            foreach ($optArr as $key => $val) {
                $html .= "<OPTION value='" . $val["uid"] . "'>" . $val['name'] . "</OPTION>";
            }
            $html .= "</SELECT>";
        }
        if ($print)
            print $html;
        return($html);
    }

//function

    public function BabelBrowseRequest($by = false) {
        //by : domains|attachments|objects
        $browseBy = ($by ? $by : "attachments");
        $soap = '        <BrowseRequest   xmlns="urn:zimbraMail" browseBy="' . $browseBy . '"  >';
        $soap .= "  <query>is: anywhere</query>";
        $soap .= "<browseBy>attachments</browseBy>";
        $soap .= "  </BrowseRequest>";

        $response = $this->soapRequest($soap);
        if ($response) {
            $responseArray = $this->makeXMLTree($response);
            $ret = $responseArray['soap:Envelope'][0]['soap:Body'][0]['BrowseResponse'][0];


            return ($ret);
        } else {
            return false;
        }
    }

//function

    public $ApptArray = array();

    public function pushDateArr($date, $name, $desc, $doliId, $uid, $cat, $allday, $loc = "", $isOrg = 1, $l = 'null', $url = '', $socid) {
        if (!is_array($date) && preg_match("/([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})[\W]?([0-9]{2})?[\W]?([0-9]{2})?[\W]?([0-9]{2})?/", $date, $arrPreg)) {//2007-08-31 12:01:01
            array_push($this->ApptArray, array("start" => array("year" => $arrPreg[1], "month" => $arrPreg[2], "day" => $arrPreg[3], "hour" => $arrPreg[4], "min" => $arrPreg[5]),
                "end" => array("year" => $arrPreg[1], "month" => $arrPreg[2], "day" => $arrPreg[3], "hour" => $arrPreg[4], "min" => $arrPreg[5]),
                "transp" => "O",
                "fb" => "B",
                "status" => "TENT",
                "allDay" => $allday,
                "isOrg" => $isOrg,
                "noBlob" => "0",
                "l" => $l,
                "name" => $name,
                "loc" => $loc,
                "descHtml" => $desc,
                "desc" => strip_tags($desc),
                "doliId" => $doliId,
                "cat" => $cat,
                'uid' => $uid,
                'url' => $url,
                "socid" => $socid
                    )
            );

            return($this->ApptArray);
        } else if (is_array($date)) {//2007-08-31 12:01:01
            $dateStart = $date['debut'];
            $parseOk = false;
            $arrPreg = array();
            if (preg_match("/([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})[\W]?([0-9]{2})?[\W]?([0-9]{2})?[\W]?([0-9]{2})?/", $dateStart, $arrPreg)) {
                $parseOk = true;
            }
            $dateEnd = $date['fin'];
            $arrPreg1 = array();
            if ($parseOk && preg_match("/([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})[\W]?([0-9]{2})?[\W]?([0-9]{2})?[\W]?([0-9]{2})?/", $dateEnd, $arrPreg1)) {
                $parseOk = true;
            } else {
                $parseOk = false;
                print "Date End Malformated";
            }
            if ($parseOk) {
                array_push($this->ApptArray, array("start" => array("year" => $arrPreg[1], "month" => $arrPreg[2], "day" => $arrPreg[3], "hour" => $arrPreg[4], "min" => $arrPreg[5]),
                    "end" => array("year" => $arrPreg1[1], "month" => $arrPreg1[2], "day" => $arrPreg1[3], "hour" => $arrPreg1[4], "min" => $arrPreg1[5]),
                    "transp" => "O",
                    "fb" => "B",
                    "status" => "TENT",
                    "allDay" => $allday,
                    "isOrg" => $isOrg,
                    "noBlob" => "0",
                    "l" => $l,
                    "name" => $name,
                    "loc" => $loc,
                    "descHtml" => $desc,
                    "desc" => strip_tags($desc),
                    "doliId" => $doliId,
                    "cat" => $cat,
                    'uid' => $uid,
                    'url' => $url,
                    'socid' => $socid
                        )
                );
            }
            return($this->ApptArray);
        } else {
            return($this->ApptArray);
        }
    }

    public function Babel_pushDateArr($date, $name, $desc, $doliId, $uid, $cat, $allday, $loc = "", $isOrg = 1, $l = 'null', $url = '', $socid, $obj) {

        if (!is_array($date) && preg_match("/([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})[\W]?([0-9]{2})?[\W]?([0-9]{2})?[\W]?([0-9]{2})?/", $date, $arrPreg)) {//2007-08-31 12:01:01
            array_push($this->ApptArray, array("start" => array("year" => $arrPreg[1], "month" => $arrPreg[2], "day" => $arrPreg[3], "hour" => $arrPreg[4], "min" => $arrPreg[5]),
                "end" => array("year" => $arrPreg[1], "month" => $arrPreg[2], "day" => $arrPreg[3], "hour" => $arrPreg[4], "min" => $arrPreg[5]),
                "transp" => "O",
                "fb" => "B",
                "status" => "TENT",
                "allDay" => $allday,
                "isOrg" => $isOrg,
                "noBlob" => "0",
                "l" => $l,
                "name" => $name,
                "loc" => $loc,
                "descHtml" => $desc,
                "desc" => strip_tags($desc),
                "doliId" => $doliId,
                "cat" => $cat, //nom de la table sql
                'uid' => $uid,
                'url' => $url,
                "socid" => $socid,
                "obj" => $obj
                    )
            );

            return($this->ApptArray);
        } else if (is_array($date)) {//2007-08-31 12:01:01
            $dateStart = $date['debut'];
            $parseOk = false;
            $arrPreg = array();
            if (preg_match("/([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})[\W]?([0-9]{2})?[\W]?([0-9]{2})?[\W]?([0-9]{2})?/", $dateStart, $arrPreg)) {
                $parseOk = true;
            }
            $dateEnd = $date['fin'];
            $arrPreg1 = array();
            if ($parseOk && preg_match("/([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})[\W]?([0-9]{2})?[\W]?([0-9]{2})?[\W]?([0-9]{2})?/", $dateEnd, $arrPreg1)) {
                $parseOk = true;
            } else {
                $parseOk = false;
                print "Date End Malformated";
            }
            if ($parseOk) {
                array_push($this->ApptArray, array("start" => array("year" => $arrPreg[1], "month" => $arrPreg[2], "day" => $arrPreg[3], "hour" => $arrPreg[4], "min" => $arrPreg[5]),
                    "end" => array("year" => $arrPreg1[1], "month" => $arrPreg1[2], "day" => $arrPreg1[3], "hour" => $arrPreg1[4], "min" => $arrPreg1[5]),
                    "transp" => "O",
                    "fb" => "B",
                    "status" => "TENT",
                    "allDay" => $allday,
                    "isOrg" => $isOrg,
                    "noBlob" => "0",
                    "l" => $l,
                    "name" => $name,
                    "loc" => $loc,
                    "descHtml" => $desc,
                    "desc" => strip_tags($desc),
                    "doliId" => $doliId,
                    "cat" => $cat,
                    'uid' => $uid,
                    'url' => $url,
                    'socid' => $socid,
                    "obj" => $obj
                        )
                );
            }

            return($this->ApptArray);
        } else {
            return($this->ApptArray);
        }
    }

    public $dolibarr_main_url_root;

    public function Synopsis_Zimbra_GetPropalUser($userId, $parentFolder) {//parentFolder = rep du nom de l'utilisateur
        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "propal.rowid,
                           " . MAIN_DB_PREFIX . "propal.ref,
                           " . MAIN_DB_PREFIX . "propal.datec,
                           " . MAIN_DB_PREFIX . "propal.datep,
                           " . MAIN_DB_PREFIX . "propal.fin_validite,
                           " . MAIN_DB_PREFIX . "propal.date_valid,
                           " . MAIN_DB_PREFIX . "propal.date_cloture,
                           " . MAIN_DB_PREFIX . "propal.fk_user_author,
                           " . MAIN_DB_PREFIX . "propal.fk_user_valid,
                           " . MAIN_DB_PREFIX . "propal.fk_user_cloture,
                           " . MAIN_DB_PREFIX . "propal.fk_statut,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid,
                           " . MAIN_DB_PREFIX . "propal.note_private,
                           " . MAIN_DB_PREFIX . "propal.note_public,
                           " . MAIN_DB_PREFIX . "propal.date_livraison
                      FROM " . MAIN_DB_PREFIX . "propal,
                           " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "propal.fk_soc
                       AND (fk_user_author =" . $userId . "
                            OR fk_user_valid =" . $userId . "
                            OR fk_user_cloture=" . $userId . ')';

        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        while ($res = $db->fetch_object($resql)) {
            $url = $this->dolibarr_main_url_root . "/comm/propal.php?propalid=" . $res->rowid;
            if ($res->datec) {
                //get Loc Zimbra
                $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Propales'
                                         AND folder_parent = " . $parentFolder;

                if ($resqlLocZim = $db->query($requeteLocZim)) {
                    $zimRes = $db->fetch_object($resqlLocZim);
                    $zimLoc = $zimRes->fid;
                    $typeId = $zimRes->ftid;
                    $arrRes = $this->Babel_pushDateArr(
                            $res->datec, "Créat. de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "propal", 1, //all day
                            "", 1, //loc géo
                            $zimLoc, //loc zimbra
                            $url, $soc->id, $res);
                    $id++;
                }
                if ($res->datep) {
                    $arrRes = $this->Babel_pushDateArr(
                            $res->datep, "Date Prop " . "" . $res->ref . "" . " (" . $res->socname . ")", "Proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "propal", 1, //all day
                            "", //loc géo
                            1, //is org
                            $zimLoc, //loc zimbra
                            $url, $soc->id, $res);
                    $id++;
                }
                if ($res->fin_validite && $res->date_valid) {
                    $arrRes = $this->Babel_pushDateArr(
                            array('debut' => $res->date_valid, "fin" => $res->fin_validite), "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "propal", 1, //all day
                            "", //loc géo
                            1, //is org
                            $zimLoc, //loc zimbra
                            $url, $soc->id, $res);
                    $id++;
                }
                if ($res->date_cloture) {
                    $arrRes = $this->Babel_pushDateArr(
                            $res->date_cloture, "Clot de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cloture Proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "propal", 1, //all day
                            "", //loc géo
                            1, //is org
                            $zimLoc, //loc zimbra
                            $url, $soc->id, $res);
                    $id++;
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetPropalUserById($userId, $propalId) {//parentFolder = rep du nom de l'utilisateur
        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "propal.rowid,
                           " . MAIN_DB_PREFIX . "propal.ref,
                           " . MAIN_DB_PREFIX . "propal.datec,
                           " . MAIN_DB_PREFIX . "propal.datep,
                           " . MAIN_DB_PREFIX . "propal.fin_validite,
                           " . MAIN_DB_PREFIX . "propal.date_valid,
                           " . MAIN_DB_PREFIX . "propal.date_cloture,
                           " . MAIN_DB_PREFIX . "propal.fk_user_author,
                           " . MAIN_DB_PREFIX . "propal.fk_user_valid,
                           " . MAIN_DB_PREFIX . "propal.fk_user_cloture,
                           " . MAIN_DB_PREFIX . "propal.fk_statut,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid,
                           " . MAIN_DB_PREFIX . "propal.note_private,
                           " . MAIN_DB_PREFIX . "propal.note_public,
                           " . MAIN_DB_PREFIX . "propal.date_livraison
                      FROM " . MAIN_DB_PREFIX . "propal,
                           " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "propal.fk_soc
                       AND " . MAIN_DB_PREFIX . "propal.rowid = " . $propalId;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $tmpUser = new User($this->db);
                $tmpUser->fetch($userId);
                $url = $this->dolibarr_main_url_root . "/comm/propal.php?propalid=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Propales'
                                         AND folder_parent = ( SELECT max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $tmpUser->firstname . ' ' . $tmpUser->lastname . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Créat. de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "propal", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->datep) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datep, "Date Prop " . "" . $res->ref . "" . " (" . $res->socname . ")", "Proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "propal", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                        
                    }
                    if ($res->fin_validite && $res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                array('debut' => $res->date_valid, "fin" => $res->fin_validite), "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "propal", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_cloture) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_cloture, "Clot de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cloture Proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "propal", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetPropal() {
        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "propal.rowid,
                           " . MAIN_DB_PREFIX . "propal.ref,
                           " . MAIN_DB_PREFIX . "propal.datec,
                           " . MAIN_DB_PREFIX . "propal.datep,
                           " . MAIN_DB_PREFIX . "propal.fin_validite,
                           " . MAIN_DB_PREFIX . "propal.date_valid,
                           " . MAIN_DB_PREFIX . "propal.date_cloture,
                           " . MAIN_DB_PREFIX . "propal.fk_user_author,
                           " . MAIN_DB_PREFIX . "propal.fk_user_valid,
                           " . MAIN_DB_PREFIX . "propal.fk_user_cloture,
                           " . MAIN_DB_PREFIX . "propal.fk_statut,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid,
                           " . MAIN_DB_PREFIX . "propal.note_private,
                           " . MAIN_DB_PREFIX . "propal.note_public,
                           " . MAIN_DB_PREFIX . "propal.date_livraison
                      FROM " . MAIN_DB_PREFIX . "propal, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "propal.fk_soc ";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/comm/propal.php?propalid=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Propales'
                                         AND folder_parent =( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Créat. de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "propal", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->datep) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datep, "Date Prop " . "" . $res->ref . "" . " (" . $res->socname . ")", "Proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "propal", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->fin_validite && $res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                array('debut' => $res->date_valid, "fin" => $res->fin_validite), "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "propal", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_cloture) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_cloture, "Clot de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cloture Proposition commerciale " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "propal", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetFacture() {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "facture.rowid,
                           " . MAIN_DB_PREFIX . "facture.ref as ref,
                           " . MAIN_DB_PREFIX . "facture.datec,
                           " . MAIN_DB_PREFIX . "facture.paye,
                           " . MAIN_DB_PREFIX . "facture.datef,
                           " . MAIN_DB_PREFIX . "facture.date_lim_reglement,
                           " . MAIN_DB_PREFIX . "facture.date_valid,
                           " . MAIN_DB_PREFIX . "facture.fk_user_author,
                           " . MAIN_DB_PREFIX . "facture.fk_user_valid,
                           " . MAIN_DB_PREFIX . "facture.fk_statut,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid,
                           " . MAIN_DB_PREFIX . "facture.note_private as note,
                           " . MAIN_DB_PREFIX . "facture.note_public
                      FROM " . MAIN_DB_PREFIX . "facture, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "facture.fk_soc ";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/compta/facture.php?facid=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Factures'
                                         AND folder_parent =( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Créat. de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "facture", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_lim_reglement && $res->datef) {
                        $arrRes = $this->Babel_pushDateArr(
                                array('debut' => $res->datef, "fin" => $res->date_lim_reglement), "Facture " . "" . $res->ref . "" . " (" . $res->socname . ")", "Réglement de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "facture", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    } else if ($res->datef) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datef, "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "facture", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Facture " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validation de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "facture", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetFactureUser($userId, $parentFolderId) {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "facture.rowid,
                           " . MAIN_DB_PREFIX . "facture.ref as ref,
                           " . MAIN_DB_PREFIX . "facture.datec,
                           " . MAIN_DB_PREFIX . "facture.paye,
                           " . MAIN_DB_PREFIX . "facture.datef,
                           " . MAIN_DB_PREFIX . "facture.date_lim_reglement,
                           " . MAIN_DB_PREFIX . "facture.date_valid,
                           " . MAIN_DB_PREFIX . "facture.fk_user_author,
                           " . MAIN_DB_PREFIX . "facture.fk_user_valid,
                           " . MAIN_DB_PREFIX . "facture.fk_statut,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid,
                           " . MAIN_DB_PREFIX . "facture.note_private as note,
                           " . MAIN_DB_PREFIX . "facture.note_public
                      FROM " . MAIN_DB_PREFIX . "facture, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "facture.fk_soc
                       AND (" . MAIN_DB_PREFIX . "facture.fk_user_author = " . $userId . " OR " . MAIN_DB_PREFIX . "facture.fk_user_valid = " . $userId . ")";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/compta/facture.php?facid=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Factures'
                                         AND folder_parent = " . $parentFolderId . "";
                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Créat. de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "facture", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_lim_reglement && $res->datef) {
                        $arrRes = $this->Babel_pushDateArr(
                                array('debut' => $res->datef, "fin" => $res->date_lim_reglement), "Facture " . "" . $res->ref . "" . " (" . $res->socname . ")", "Réglement de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "facture", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    } else if ($res->datef) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datef, "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "facture", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Facture " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validation de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "facture", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetPaiement() {
        $db = $this->db;
        $requetePre = " SELECT " . MAIN_DB_PREFIX . "paiement_facture.fk_facture,
                               " . MAIN_DB_PREFIX . "paiement.datep
                          FROM " . MAIN_DB_PREFIX . "paiement,
                               " . MAIN_DB_PREFIX . "paiement_facture
                         WHERE " . MAIN_DB_PREFIX . "paiement_facture.fk_paiement = " . MAIN_DB_PREFIX . "paiement.rowid
                           ";
        if ($resqlPre = $this->db->query($requetePre)) {
            while ($objPai = $db->fetch_object($resqlPre)) {
                $requete = "SELECT " . MAIN_DB_PREFIX . "facture.rowid,
                                   " . MAIN_DB_PREFIX . "facture.ref as ref,
                                   " . MAIN_DB_PREFIX . "facture.datec,
                                   " . MAIN_DB_PREFIX . "facture.paye,
                                   " . MAIN_DB_PREFIX . "facture.datef,
                                   " . MAIN_DB_PREFIX . "facture.date_lim_reglement,
                                   " . MAIN_DB_PREFIX . "facture.date_valid,
                                   " . MAIN_DB_PREFIX . "facture.fk_user_author,
                                   " . MAIN_DB_PREFIX . "facture.fk_user_valid,
                                   " . MAIN_DB_PREFIX . "facture.fk_statut,
                                   " . MAIN_DB_PREFIX . "societe.nom as socname,
                                   " . MAIN_DB_PREFIX . "societe.rowid as socid,
                                   " . MAIN_DB_PREFIX . "facture.note_private as note,
                                   " . MAIN_DB_PREFIX . "facture.note_public
                              FROM " . MAIN_DB_PREFIX . "facture, " . MAIN_DB_PREFIX . "societe
                             WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "facture.fk_soc AND " . MAIN_DB_PREFIX . "facture.rowid = " . $objPai->fk_facture . "
                              ";
                $resql = $db->query($requete);
                $id = 0;

                $typeId = false;
                if ($resql) {
                    while ($res = $db->fetch_object($resql)) {
                        $url = $this->dolibarr_main_url_root . "/compta/paiement/card.php?id=" . $res->rowid;
                        //get Loc Zimbra
                        $requeteLocZim = "SELECT folder_type_refid as ftid,
                                                 folder_uid as fid
                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                           WHERE folder_name='Factures'
                                             AND folder_parent =( SELECT  max(folder_uid)
                                                                    FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                                   WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                     AND folder_type_refid = (SELECT id
                                                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                               WHERE val='appointment'))";
                        if ($resqlLocZim = $this->db->query($requeteLocZim)) {
                            $zimRes = $db->fetch_object($resqlLocZim);
                            $zimLoc = $zimRes->fid;
                            $typeId = $zimRes->ftid;
                            $arrRes = $this->Babel_pushDateArr(
                                    $objPai->datep, "Regl de " . "" . $res->ref . "" . " (" . $res->socname . ")", "R&egrave;glement de la facture " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "paiement", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                        while (count($this->ApptArray) > 0) {
                            $arr = array_pop($this->ApptArray);
                            $arr1 = $arr;
                            //extract socid
                            //Store to Db, Store to Zimbra
                            $ret = $this->createApptBabel($arr);
                            // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                            //                $parent = $arr['l'];
                            $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                            $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                            //faut aussi placer l'event dans le calendrier de la société
                            $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                            $arr1['l'] = $parentId;
                            $ret1 = $this->createApptBabel($arr1);
                            $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                            $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
                        }
                    }
                }
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetRessources() {
        $db = $this->db;
        $requetePre = " SELECT " . MAIN_DB_PREFIX . "Synopsis_global_ressources.id as rowid,
                               " . MAIN_DB_PREFIX . "Synopsis_global_ressources.date_achat
                          FROM " . MAIN_DB_PREFIX . "Synopsis_global_ressources
                        WHERE date_achat is not null AND isGroup = 0
                           ";
        if ($resqlPre = $this->db->query($requetePre)) {
            while ($objPai = $db->fetch_object($resqlPre)) {
                $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_global_ressources.id as rowid,
                                   " . MAIN_DB_PREFIX . "Synopsis_global_ressources.nom,
                                   " . MAIN_DB_PREFIX . "Synopsis_global_ressources.date_achat,
                                   " . MAIN_DB_PREFIX . "Synopsis_global_ressources.description,
                                   " . MAIN_DB_PREFIX . "Synopsis_global_ressources.fk_user_resp,
                                   " . MAIN_DB_PREFIX . "Synopsis_global_ressources.fk_parent_ressource,
                                   " . MAIN_DB_PREFIX . "Synopsis_global_ressources.photo,
                                   " . MAIN_DB_PREFIX . "Synopsis_global_ressources.isGroup,
                                   " . MAIN_DB_PREFIX . "Synopsis_global_ressources.valeur,
                                   " . MAIN_DB_PREFIX . "Synopsis_global_ressources.cout
                              FROM " . MAIN_DB_PREFIX . "Synopsis_global_ressources
                             WHERE isGroup=0
                               AND " . MAIN_DB_PREFIX . "Synopsis_global_ressources.id = " . $objPai->rowid;
                $resql = $db->query($requete);
                $id = 0;
                $typeId = false;
                if ($resql) {
                    while ($res = $db->fetch_object($resql)) {
                        $tmpUser = new User($this->db);
                        $tmpUser->id = $resql->fk_user_resp;
                        $phone = $tmpUser->prefPhone;
                        $respmail = $tmpUser->email;
                        $mail = preg_replace("/ /", "", iconv("ISO-8859-1", "UTF-8", $res->nom) . "-" . $res->rowid) . "@" . $this->_domain;
                        $newAccountDet = array();
                        $newAccountDet['zimbraCalResBuilding'] = 'Batiment';
                        $newAccountDet['zimbraCalResContactEmail'] = $respmail;
                        $newAccountDet['uid'] = preg_replace("/ /", "", iconv("ISO-8859-1", "UTF-8", $res->nom) . "-" . $res->rowid);
                        $newAccountDet['zimbraCalResFloor'] = 'Etage';
                        $newAccountDet['zimbraAccountStatus'] = 'active';
                        $newAccountDet['zimbraCalResRoom'] = 'Salle';
                        $newAccountDet['zimbraCalResSite'] = 'Lieu';
                        $newAccountDet['street'] = 'addresse';
                        $newAccountDet['zimbraCalResAutoDeclineIfBusy'] = false;
                        $newAccountDet['zimbraAllowAnyFromAddress'] = false;
                        $newAccountDet['zimbraCalResContactName'] = iconv("ISO-8859-1", "UTF-8", $tmpUser->firstname . ' ' . $tmpUser->lastname);
                        $newAccountDet['cn'] = preg_replace("/ /", "", iconv("ISO-8859-1", "UTF-8", $res->nom) . "-" . $res->rowid);
                        $newAccountDet['zimbraCalResContactPhone'] = $phone;
                        $newAccountDet['sn'] = preg_replace("/ /", "", iconv("ISO-8859-1", "UTF-8", $res->nom) . "-" . $res->rowid);
                        $newAccountDet['zimbraCalResCapacity'] = 22222;
                        $newAccountDet['displayName'] = preg_replace("/ /", "", iconv("ISO-8859-1", "UTF-8", $res->nom) . "-" . $res->rowid);
                        $newAccountDet['postalCode'] = '13100';
                        $newAccountDet['zimbraCalResType'] = 'Location'; // Equipment
                        $retCreateRes = $this->BabelCreateRessources(preg_replace("/ /", "", iconv("ISO-8859-1", "UTF-8", $res->nom) . "-" . $res->rowid), $newAccountDet);


                        //get Loc Zimbra
                        $requeteLocZim = "SELECT folder_type_refid as ftid,
                                                 folder_uid as fid
                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                           WHERE folder_name='Ressources'
                                             AND folder_type_refid = (SELECT id
                                                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                       WHERE val='appointment')";
                        if ($resqlLocZim = $this->db->query($requeteLocZim)) {
                            $zimRes = $db->fetch_object($resqlLocZim);
                            $zimLoc = $zimRes->fid;
                            $typeId = $zimRes->ftid;

                            $createArray = array('view' => 'appointment',
                                "name" => iconv("ISO-8859-1", "UTF-8", $res->nom) . "-" . $res->rowid,
                                "where" => $zimLoc);
                            $ret = $this->BabelCreateFolder($createArray);
                            $arr4ndFold["appointment"][] = $ret;
                            //fill SQL table
                            $this->BabelInsertTriggerFolder($ret['id'], $ret['name'], $ret['parent'], "appointment", 2);
                            $url = $this->dolibarr_main_url_root . "/Babel_Ressource/resa.php?ressource_id=" . $res->rowid;


                            $tmpUser = $this->dolibarr_main_url_root . "/user/card.php?id=" . $resql->fk_user_resp;
//print $objPai->date_achat ." - ". $res->rowid."<br>";
                            $arrRes = $this->Babel_pushDateArr(
                                    $objPai->date_achat, "Ress " . "" . $res->nom . "", "Ressource " . $res->nom . "<BR><P>" . $res->description . "<BR><P>" . $tmpUser, $res->nom, $id, "" . MAIN_DB_PREFIX . "Synopsis_global_ressource", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $ret['id'], //loc zimbra
                                    $url, $res->rowid, $res);
                            $id++;
                            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Zimbra_li_Ressources
                                            (ZimbraLogin, ZimbraId,calFolderZimId,Ressource_refid ) VALUES
                                            ('" . $res->nom . "-" . $res->rowid . "','" . $retCreateRes['calresource'][0]['id'] . "','" . $ret['id'] . "'," . $res->rowid . " )";
                            //print $requete;
                            $this->db->query($requete);
                        }
                        while (count($this->ApptArray) > 0) {
                            $arr = array_pop($this->ApptArray);
//                            $arr1 = $arr;
                            //extract socid
                            //Store to Db, Store to Zimbra
//var_dump($arr);
                            $ret = $this->createApptBabel($arr);
                            // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                            //                $parent = $arr['l'];
                            $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                            $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


//                            //faut aussi placer l'event dans le calendrier de la société
//                            $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
//                            $arr1['l']=$parentId;
//                            $ret1 = $this->createApptBabel($arr1);
//                            $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
//                            $this->Babel_AddEventFromTrigger($typeId,$arr1,$zimId1);
                        }
                    }
                }
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetPaiementFourn() {
        $db = $this->db;
        $requetePre = " SELECT " . MAIN_DB_PREFIX . "paiementfourn_facturefourn.fk_facturefourn,
                               " . MAIN_DB_PREFIX . "paiementfourn.datep
                          FROM " . MAIN_DB_PREFIX . "paiementfourn,
                               " . MAIN_DB_PREFIX . "paiementfourn_facturefourn
                         WHERE " . MAIN_DB_PREFIX . "paiementfourn_facturefourn.fk_paiementfourn = " . MAIN_DB_PREFIX . "paiementfourn.rowid
                           ";
        if ($resqlPre = $this->db->query($requetePre)) {
            while ($objPai = $db->fetch_object($resqlPre)) {
                $requete = "SELECT " . MAIN_DB_PREFIX . "facture_fourn.rowid,
                               " . MAIN_DB_PREFIX . "facture_fourn.ref as ref,
                               " . MAIN_DB_PREFIX . "facture_fourn.datec,
                               " . MAIN_DB_PREFIX . "facture_fourn.paye,
                               " . MAIN_DB_PREFIX . "facture_fourn.datef,
                               " . MAIN_DB_PREFIX . "facture_fourn.date_lim_reglement,
                               " . MAIN_DB_PREFIX . "facture_fourn.date_valid,
                               " . MAIN_DB_PREFIX . "facture_fourn.fk_user_author,
                               " . MAIN_DB_PREFIX . "facture_fourn.fk_user_valid,
                               " . MAIN_DB_PREFIX . "facture_fourn.fk_statut,
                               " . MAIN_DB_PREFIX . "societe.nom as socname,
                               " . MAIN_DB_PREFIX . "societe.rowid as socid,
                               " . MAIN_DB_PREFIX . "facture_fourn.note,
                               " . MAIN_DB_PREFIX . "facture_fourn.note_public
                          FROM " . MAIN_DB_PREFIX . "facture_fourn, " . MAIN_DB_PREFIX . "societe
                         WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "facture_fourn.fk_soc
                           AND " . MAIN_DB_PREFIX . "facture_fourn.rowid = " . $objPai->fk_facturefourn . "
                              ";
                $resql = $db->query($requete);
                $id = 0;

                $typeId = false;
                if ($resql) {
                    while ($res = $db->fetch_object($resql)) {
                        $url = $this->dolibarr_main_url_root . "/compta/paiement/card.php?id=" . $res->rowid;
                        //get Loc Zimbra
                        $requeteLocZim = "SELECT folder_type_refid as ftid,
                                                 folder_uid as fid
                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                           WHERE folder_name='Factures fournisseur'
                                             AND folder_parent =( SELECT  max(folder_uid)
                                                                    FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                                   WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                     AND folder_type_refid = (SELECT id
                                                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                               WHERE val='appointment'))";
                        if ($resqlLocZim = $this->db->query($requeteLocZim)) {
                            $zimRes = $db->fetch_object($resqlLocZim);
                            $zimLoc = $zimRes->fid;
                            $typeId = $zimRes->ftid;
                            $arrRes = $this->Babel_pushDateArr(
                                    $objPai->datep, "Regl de " . "" . $res->ref . "" . " (" . $res->socname . ")", "R&egrave;glement de la facture fournisseur " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "paiement", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                        while (count($this->ApptArray) > 0) {
                            $arr = array_pop($this->ApptArray);
                            $arr1 = $arr;
                            //extract socid
                            //Store to Db, Store to Zimbra
                            $ret = $this->createApptBabel($arr);
                            // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                            //                $parent = $arr['l'];
                            $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                            $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                            //faut aussi placer l'event dans le calendrier de la société
                            $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                            $arr1['l'] = $parentId;
                            $ret1 = $this->createApptBabel($arr1);
                            $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                            $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
                        }
                    }
                }
            }
        }
    }

//fcuntion

    public function Babel_DelZimCal($pId, $options = "") {
        //<CancelAppointmentRequest id="ID_OF_DEFAULT_INVITE" comp="COMPONENT_NUM_DEFAULT_INVITE">
        //   [<tz ...>]  // definition for TZID referenced by DATETIME in <inst>
        //   [<inst [range="THISANDFUTURE|THISANDPRIOR"] DATETTIME/>]?
        //   [ <m>
        //       [<e.../>*] // users to send update to
        //       [<su>{subject of cancellation mail}</su>]
        //       <mp>...</mp>
        //     </m> ]
        //</CancelAppointmentRequest>
        $soap = '<CancelAppointmentRequest   xmlns="urn:zimbraMail" id="' . $pId . '" comp="0" >';
        $soap .= "  </CancelAppointmentRequest>";

        $response = $this->soapRequest($soap);
        if ($response) {
            $responseArray = $this->makeXMLTree($response);
            $ret = $responseArray['soap:Envelope'][0]['soap:Body'][0]['CancelAppointmentResponse'][0];


            return ($ret);
        } else {
            return false;
        }
    }

    public function Synopsis_Zimbra_GetFactureFounisseur() {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "facture_fourn.rowid,
                           " . MAIN_DB_PREFIX . "facture_fourn.ref as ref,
                           " . MAIN_DB_PREFIX . "facture_fourn.datec,
                           " . MAIN_DB_PREFIX . "facture_fourn.paye,
                           " . MAIN_DB_PREFIX . "facture_fourn.datef,
                           " . MAIN_DB_PREFIX . "facture_fourn.date_lim_reglement,
                           " . MAIN_DB_PREFIX . "facture_fourn.fk_user_author,
                           " . MAIN_DB_PREFIX . "facture_fourn.fk_user_valid,
                           " . MAIN_DB_PREFIX . "facture_fourn.fk_statut,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid,
                           " . MAIN_DB_PREFIX . "facture_fourn.note,
                           " . MAIN_DB_PREFIX . "facture_fourn.note_public
                      FROM " . MAIN_DB_PREFIX . "facture_fourn, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "facture_fourn.fk_soc ";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/fourn/facture/card.php?facid=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Factures fournisseur'
                                         AND folder_parent =( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Créat. de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la facture fournisseur" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "facture_fourn", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_lim_reglement && $res->datef) {
                        $arrRes = $this->Babel_pushDateArr(
                                array('debut' => $res->datef, "fin" => $res->date_lim_reglement), "Facture " . "" . $res->ref . "" . " (" . $res->socname . ")", "Réglement de la facture fournisseur" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "facture_fourn", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    } else if ($res->datef) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datef, "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la facture fournisseur" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "facture_fourn", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetIntervention() {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "fichinter.rowid,
                           " . MAIN_DB_PREFIX . "fichinter.fk_soc,
                           " . MAIN_DB_PREFIX . "fichinter.fk_contrat,
                           " . MAIN_DB_PREFIX . "fichinter.datec,
                           " . MAIN_DB_PREFIX . "fichinter.date_valid,
                           " . MAIN_DB_PREFIX . "fichinter.datei,
                           " . MAIN_DB_PREFIX . "fichinter.fk_user_author,
                           " . MAIN_DB_PREFIX . "fichinter.fk_user_valid,
                           " . MAIN_DB_PREFIX . "fichinter.fk_statut,
                           " . MAIN_DB_PREFIX . "fichinter.description,
                           " . MAIN_DB_PREFIX . "fichinter.note_private,
                           " . MAIN_DB_PREFIX . "fichinter.note_public,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM " . MAIN_DB_PREFIX . "fichinter, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "fichinter.fk_soc ";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/fichinter/card.php?id=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Interventions'
                                         AND folder_parent =( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Créat. de la FI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la fiche d'intervention' " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "fichinter", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->datei) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datei, "Intervention " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "fichinter", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Valid. FI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de la fiche d'intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "fichinter", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetInterventionUser($userid, $parentFolderId) {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "fichinter.rowid,
                           " . MAIN_DB_PREFIX . "fichinter.fk_soc,
                           " . MAIN_DB_PREFIX . "fichinter.fk_contrat,
                           " . MAIN_DB_PREFIX . "fichinter.datec,
                           " . MAIN_DB_PREFIX . "fichinter.date_valid,
                           " . MAIN_DB_PREFIX . "fichinter.datei,
                           " . MAIN_DB_PREFIX . "fichinter.fk_user_author,
                           " . MAIN_DB_PREFIX . "fichinter.fk_user_valid,
                           " . MAIN_DB_PREFIX . "fichinter.fk_statut,
                           " . MAIN_DB_PREFIX . "fichinter.description,
                           " . MAIN_DB_PREFIX . "fichinter.note_private,
                           " . MAIN_DB_PREFIX . "fichinter.note_public,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM " . MAIN_DB_PREFIX . "fichinter, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "fichinter.fk_soc
                       AND ( " . MAIN_DB_PREFIX . "fichinter.fk_user_author = " . $userid . " OR " . MAIN_DB_PREFIX . "fichinter.fk_user_valid = " . $userid . " ) ";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/fichinter/card.php?id=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Interventions'
                                         AND folder_parent = " . $parentFolderId;

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Créat. de la FI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la fiche d'intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "fichinter", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->datei) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datei, "Intervention " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "fichinter", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Valid de la FI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de la fiche d'intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "fichinter", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetInterventionUserById($userId, $fiid) {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "fichinter.rowid,
                           " . MAIN_DB_PREFIX . "fichinter.fk_soc,
                           " . MAIN_DB_PREFIX . "fichinter.fk_contrat,
                           " . MAIN_DB_PREFIX . "fichinter.datec,
                           " . MAIN_DB_PREFIX . "fichinter.date_valid,
                           " . MAIN_DB_PREFIX . "fichinter.datei,
                           " . MAIN_DB_PREFIX . "fichinter.fk_user_author,
                           " . MAIN_DB_PREFIX . "fichinter.fk_user_valid,
                           " . MAIN_DB_PREFIX . "fichinter.fk_statut,
                           " . MAIN_DB_PREFIX . "fichinter.description,
                           " . MAIN_DB_PREFIX . "fichinter.note_private,
                           " . MAIN_DB_PREFIX . "fichinter.note_public,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM " . MAIN_DB_PREFIX . "fichinter, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "fichinter.fk_soc
                       AND " . MAIN_DB_PREFIX . "fichinter.rowid = " . $fiid;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/fichinter/card.php?id=" . $res->rowid;
                if ($res->datec) {
                    $tmpUser = new User($this->db);
                    $tmpUser->fetch($userId);
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Interventions'
                                         AND folder_parent = ( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $tmpUser->getFullName($langs) . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Créat. de la FI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la fiche d'intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "fichinter", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->datei) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datei, "Intervention " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "fichinter", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Valid de la FI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de la fiche d'intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "fichinter", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetsynopsisdemandeinterventionUser($userid, $parentFolderId) {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT ".MAIN_DB_PREFIX."synopsisdemandeinterv.rowid,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_soc,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_contrat,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.datec,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.date_valid,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.datei,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_user_author,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_user_valid,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_statut,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.description,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.note_private,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.note_public,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM ".MAIN_DB_PREFIX."synopsisdemandeinterv, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_soc
                       AND ( ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_user_author = " . $userid . " OR ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_user_valid = " . $userid . " ) ";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/synopsisdemandeinterv/card.php?id=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Interventions'
                                         AND folder_parent = " . $parentFolderId;

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Créat. la DI ' " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la demande d'intervention' " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, MAIN_DB_PREFIX."synopsisdemandeinterv", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->datei) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datei, "DI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Demande d'intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, MAIN_DB_PREFIX."synopsisdemandeinterv", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Valid. de la DI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de la demande intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, MAIN_DB_PREFIX."synopsisdemandeinterv", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetsynopsisdemandeinterventionUserById($userId, $diid) {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT ".MAIN_DB_PREFIX."synopsisdemandeinterv.rowid,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_soc,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_contrat,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.datec,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.date_valid,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.datei,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_user_author,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_user_valid,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_statut,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.description,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.note_private,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.note_public,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM ".MAIN_DB_PREFIX."synopsisdemandeinterv, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_soc
                       AND ".MAIN_DB_PREFIX."synopsisdemandeinterv.rowid =  " . $diid;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/synopsisdemandeinterv/card.php?id=" . $res->rowid;
                if ($res->datec) {
                    $tmpUser = new User($this->db);
                    $tmpUser->fetch($userId);
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Interventions'
                                         AND folder_parent = ( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $tmpUser->getFullName($langs) . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Créat. la DI ' " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la demande d'intervention' " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, MAIN_DB_PREFIX."synopsisdemandeinterv", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->datei) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datei, "DI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Demande d'intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, MAIN_DB_PREFIX."synopsisdemandeinterv", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Valid. de la DI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de la demande intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, MAIN_DB_PREFIX."synopsisdemandeinterv", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_Getsynopsisdemandeintervention() {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT ".MAIN_DB_PREFIX."synopsisdemandeinterv.rowid,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_soc,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_contrat,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.datec,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.date_valid,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.datei,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_user_author,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_user_valid,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_statut,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.description,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.note_private,
                           ".MAIN_DB_PREFIX."synopsisdemandeinterv.note_public,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM ".MAIN_DB_PREFIX."synopsisdemandeinterv, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = ".MAIN_DB_PREFIX."synopsisdemandeinterv.fk_soc";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/synopsisdemandeinterv/card.php?id=" . $res->rowid;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Interventions'
                                         AND folder_parent = ( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";
                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Créat. la DI ' " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la demande d'intervention' " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, MAIN_DB_PREFIX."synopsisdemandeinterv", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->datei) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datei, "DI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Demande d'intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, MAIN_DB_PREFIX."synopsisdemandeinterv", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Valid. de la DI " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de la demande intervention " . $res->rowid . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, MAIN_DB_PREFIX."synopsisdemandeinterv", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetExpedition() {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT  " . MAIN_DB_PREFIX . "expedition.rowid as id,
                            " . MAIN_DB_PREFIX . "expedition.ref,
                            " . MAIN_DB_PREFIX . "expedition.fk_soc,
                            " . MAIN_DB_PREFIX . "expedition.date_creation,
                            " . MAIN_DB_PREFIX . "expedition.date_valid,
                            " . MAIN_DB_PREFIX . "expedition.date_expedition,
                            " . MAIN_DB_PREFIX . "expedition.fk_user_author,
                            " . MAIN_DB_PREFIX . "expedition.fk_user_valid,
                            " . MAIN_DB_PREFIX . "expedition.fk_shipping_method,
                            " . MAIN_DB_PREFIX . "expedition.fk_statut,
                            " . MAIN_DB_PREFIX . "expedition.note_private as note,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.rowid,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.code,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.libelle,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.description,
                            " . MAIN_DB_PREFIX . "societe.nom as socname,
                            " . MAIN_DB_PREFIX . "societe.rowid as socid,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.active
                      FROM  " . MAIN_DB_PREFIX . "societe, " . MAIN_DB_PREFIX . "expedition
                 LEFT JOIN  " . MAIN_DB_PREFIX . "c_shipment_mode " . MAIN_DB_PREFIX . "c_shipment_mode
                        ON  " . MAIN_DB_PREFIX . "expedition.fk_shipping_method = " . MAIN_DB_PREFIX . "c_shipment_mode.rowid
                    WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "expedition.fk_soc";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/expedition/card.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Expeditions'
                                         AND folder_parent =( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_creation, "Prise en compte de l'expedition " . "" . $res->ref . "" . " (" . $res->socname . ")", "Prise en compte de l'expedition " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "expedition", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_expedition) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_expedition, "Expedition " . "" . $res->ref . "" . " (" . $res->socname . ")", "Expedition de " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "expedition", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Validation de l'expedition " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de l'expedition " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "expedition", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetExpeditionUser($userid, $parentFolderId) {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT  " . MAIN_DB_PREFIX . "expedition.rowid as id,
                            " . MAIN_DB_PREFIX . "expedition.ref,
                            " . MAIN_DB_PREFIX . "expedition.fk_soc,
                            " . MAIN_DB_PREFIX . "expedition.date_creation,
                            " . MAIN_DB_PREFIX . "expedition.date_valid,
                            " . MAIN_DB_PREFIX . "expedition.date_expedition,
                            " . MAIN_DB_PREFIX . "expedition.fk_user_author,
                            " . MAIN_DB_PREFIX . "expedition.fk_user_valid,
                            " . MAIN_DB_PREFIX . "expedition.fk_shipping_method,
                            " . MAIN_DB_PREFIX . "expedition.fk_statut,
                            " . MAIN_DB_PREFIX . "expedition.note_private as note,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.rowid,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.code,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.libelle,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.description,
                            " . MAIN_DB_PREFIX . "societe.nom as socname,
                            " . MAIN_DB_PREFIX . "societe.rowid as socid,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.active
                      FROM  " . MAIN_DB_PREFIX . "societe, " . MAIN_DB_PREFIX . "expedition
                 LEFT JOIN  " . MAIN_DB_PREFIX . "c_shipment_mode " . MAIN_DB_PREFIX . "c_shipment_mode
                        ON  " . MAIN_DB_PREFIX . "expedition.fk_shipping_method = " . MAIN_DB_PREFIX . "c_shipment_mode.rowid
                    WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "expedition.fk_soc AND (fk_user_author = " . $userid . " OR fk_user_valid = " . $userid . ") ";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/expedition/card.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Livraisons'
                                         AND folder_parent = " . $parentFolderId;

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_creation, "Prise en compte de l'expedition " . "" . $res->ref . "" . " (" . $res->socname . ")", "Prise en compte de l'expedition " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "expedition", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_expedition) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_expedition, "Expedition " . "" . $res->ref . "" . " (" . $res->socname . ")", "Expedition de " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "expedition", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Validation de l'expedition " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de l'expedition " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "expedition", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetExpeditionUserById($userId, $expedId) {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT  " . MAIN_DB_PREFIX . "expedition.rowid as id,
                            " . MAIN_DB_PREFIX . "expedition.ref,
                            " . MAIN_DB_PREFIX . "expedition.fk_soc,
                            " . MAIN_DB_PREFIX . "expedition.date_creation,
                            " . MAIN_DB_PREFIX . "expedition.date_valid,
                            " . MAIN_DB_PREFIX . "expedition.date_expedition,
                            " . MAIN_DB_PREFIX . "expedition.fk_user_author,
                            " . MAIN_DB_PREFIX . "expedition.fk_user_valid,
                            " . MAIN_DB_PREFIX . "expedition.fk_shipping_method,
                            " . MAIN_DB_PREFIX . "expedition.fk_statut,
                            " . MAIN_DB_PREFIX . "expedition.note_private as note,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.rowid,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.code,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.libelle,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.description,
                            " . MAIN_DB_PREFIX . "societe.nom as socname,
                            " . MAIN_DB_PREFIX . "societe.rowid as socid,
                            " . MAIN_DB_PREFIX . "c_shipment_mode.active
                      FROM  " . MAIN_DB_PREFIX . "societe, " . MAIN_DB_PREFIX . "expedition
                 LEFT JOIN  " . MAIN_DB_PREFIX . "c_shipment_mode " . MAIN_DB_PREFIX . "c_shipment_mode
                        ON  " . MAIN_DB_PREFIX . "expedition.fk_shipping_method = " . MAIN_DB_PREFIX . "c_shipment_mode.rowid
                    WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "expedition.fk_soc AND " . MAIN_DB_PREFIX . "expedition.rowid = $expedId";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                    $tmpUser = new User($this->db);
                    $tmpUser->fetch($userId);
                $url = $this->dolibarr_main_url_root . "/expedition/card.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Livraisons'
                                         AND folder_parent = ( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $tmpUser->getFullName($langs) . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_creation, "Prise en compte de l'expedition " . "" . $res->ref . "" . " (" . $res->socname . ")", "Prise en compte de l'expedition " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "expedition", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_expedition) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_expedition, "Expedition " . "" . $res->ref . "" . " (" . $res->socname . ")", "Expedition de " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "expedition", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Validation de l'expedition " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de l'expedition " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "expedition", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetLivraison() {
        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT  " . MAIN_DB_PREFIX . "livraison.rowid,
                            " . /*MAIN_DB_PREFIX . "livraison.fk_expedition,
                            " . */MAIN_DB_PREFIX . "livraison.ref,
                            " . MAIN_DB_PREFIX . "livraison.fk_soc,
                            " . MAIN_DB_PREFIX . "livraison.date_creation,
                            " . MAIN_DB_PREFIX . "livraison.date_valid,
                            " . MAIN_DB_PREFIX . "livraison.fk_user_author,
                            " . MAIN_DB_PREFIX . "livraison.fk_user_valid,
                            " . MAIN_DB_PREFIX . "livraison.fk_statut,
                            " . MAIN_DB_PREFIX . "livraison.note_private as note,
                            " . MAIN_DB_PREFIX . "livraison.note_public,
                            " . MAIN_DB_PREFIX . "livraison.date_delivery,
                            " . MAIN_DB_PREFIX . "societe.nom as socname,
                            " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM  " . MAIN_DB_PREFIX . "societe, " . MAIN_DB_PREFIX . "livraison
                    WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "livraison.fk_soc";

        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/livraison/card.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Expeditions'
                                         AND folder_parent =( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_creation, "Prise en compte de la livraison " . "" . $res->ref . "" . " (" . $res->socname . ")", "Prise en compte de la livraison " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "livraison", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_livraison) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_livraison, "Livraison " . "" . $res->ref . "" . " (" . $res->socname . ")", "Livraison de " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "livraison", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Validation de la livraison " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de la livraison " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "livraison", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetLivraisonUser($userid, $parentFolderId) {
        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT  " . MAIN_DB_PREFIX . "livraison.rowid,
                            " . /*MAIN_DB_PREFIX . "livraison.fk_expedition,
                            " . */MAIN_DB_PREFIX . "livraison.ref,
                            " . MAIN_DB_PREFIX . "livraison.fk_soc,
                            " . MAIN_DB_PREFIX . "livraison.date_creation,
                            " . MAIN_DB_PREFIX . "livraison.date_valid,
                            " . MAIN_DB_PREFIX . "livraison.fk_user_author,
                            " . MAIN_DB_PREFIX . "livraison.fk_user_valid,
                            " . MAIN_DB_PREFIX . "livraison.fk_statut,
                            " . MAIN_DB_PREFIX . "livraison.note_private as note,
                            " . MAIN_DB_PREFIX . "livraison.note_public,
                            " . MAIN_DB_PREFIX . "livraison.date_delivery,
                            " . MAIN_DB_PREFIX . "societe.nom as socname,
                            " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM  " . MAIN_DB_PREFIX . "societe, " . MAIN_DB_PREFIX . "livraison
                    WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "livraison.fk_soc AND (fk_user_author = " . $userid . " OR fk_user_valid = " . $userid . " )";

        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/livraison/card.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Livraisons'
                                         AND folder_parent =( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_uid = " . $parentFolderId . ")";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_creation, "Prise en compte de la livraison " . "" . $res->ref . "" . " (" . $res->socname . ")", "Prise en compte de la livraison " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "livraison", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_livraison) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_livraison, "Livraison " . "" . $res->ref . "" . " (" . $res->socname . ")", "Livraison de " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "livraison", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Validation de la livraison " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de la livraison " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "livraison", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetlivraisonUserById($userId, $expedId) {

        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT  " . MAIN_DB_PREFIX . "livraison.rowid as id,
                            " . MAIN_DB_PREFIX . "livraison.ref,
                            " . MAIN_DB_PREFIX . "livraison.fk_soc,
                            " . MAIN_DB_PREFIX . "livraison.date_creation,
                            " . MAIN_DB_PREFIX . "livraison.date_valid,
                            " . MAIN_DB_PREFIX . "livraison.date_delivery,
                            " . MAIN_DB_PREFIX . "livraison.fk_user_author,
                            " . MAIN_DB_PREFIX . "livraison.fk_user_valid,
                            " . MAIN_DB_PREFIX . "livraison.fk_statut,
                            " . MAIN_DB_PREFIX . "livraison.note_private as note,
                            " . MAIN_DB_PREFIX . "societe.nom as socname,
                            " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM  " . MAIN_DB_PREFIX . "societe, " . MAIN_DB_PREFIX . "livraison
                    WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "livraison.fk_soc AND " . MAIN_DB_PREFIX . "livraison.rowid = $expedId";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                    $tmpUser = new User($this->db);
                    $tmpUser->fetch($userId);
                $url = $this->dolibarr_main_url_root . "/livraison/card.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Livraisons'
                                         AND folder_parent = ( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $tmpUser->getFullName($langs) . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_creation, "Prise en compte de la livraison " . "" . $res->ref . "" . " (" . $res->socname . ")", "Prise en compte de la livraison " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "livraison", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_livraison) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_livraison, "livraison " . "" . $res->ref . "" . " (" . $res->socname . ")", "livraison de " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "livraison", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Validation de la livraison " . "" . $res->rowid . "" . " (" . $res->socname . ")", "Validation de la livraison " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "livraison", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetActionCom() {
        $db = $this->db;
        $requete = "SELECT  " . MAIN_DB_PREFIX . "actioncomm.datec,
                            " . MAIN_DB_PREFIX . "actioncomm.datep,
                            " . MAIN_DB_PREFIX . "actioncomm.datep2,
                            " . MAIN_DB_PREFIX . "actioncomm.label,
                            " . MAIN_DB_PREFIX . "actioncomm.id,
                            " . MAIN_DB_PREFIX . "c_actioncomm.libelle,
                            " . MAIN_DB_PREFIX . "projet.title,
                            " . MAIN_DB_PREFIX . "projet.ref,
                            " . MAIN_DB_PREFIX . "actioncomm.durationp,
                            " . MAIN_DB_PREFIX . "actioncomm.note,
                            " . MAIN_DB_PREFIX . "societe.nom as socname,
                            " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM  " . MAIN_DB_PREFIX . "societe, " . MAIN_DB_PREFIX . "actioncomm " . MAIN_DB_PREFIX . "actioncomm
                 LEFT JOIN " . MAIN_DB_PREFIX . "projet on " . MAIN_DB_PREFIX . "actioncomm.fk_project = " . MAIN_DB_PREFIX . "projet.rowid
                 LEFT JOIN " . MAIN_DB_PREFIX . "c_actioncomm on " . MAIN_DB_PREFIX . "c_actioncomm.id = " . MAIN_DB_PREFIX . "actioncomm.fk_action
                 LEFT JOIN " . MAIN_DB_PREFIX . "socpeople on " . MAIN_DB_PREFIX . "socpeople.rowid = " . MAIN_DB_PREFIX . "actioncomm.fk_contact
                    WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "actioncomm.fk_soc";

        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/comm/action/card.php?id=" . $res->id;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Actions'
                                         AND folder_parent =( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $allDay = 0;
                        if ($res->datep) {
                            $date = $res->datep;
                            if ($res->datep2 && $res->datep && $res->durationp != 0) {
                                $allDay = 1;
                                $date = array('debut' => $res->datep, 'fin' => $res->datep2);
                            }
                            $arrRes = $this->Babel_pushDateArr(
                                    $date, $res->libelle . " (" . $res->socname . ")", "Action commerciale " . $res->libelle . "<HR><P>" . $res->label . "<BR>Ref :" . $res->ref . "<HR><P>" . $res->note . "<BR><P>", $res->ref, $id, "" . MAIN_DB_PREFIX . "actioncomm", $allDay, //all day
                                    "", 1, //loc géo
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetActionComUser($userId, $parentFolderId) {
        $db = $this->db;
        $requete = "SELECT  " . MAIN_DB_PREFIX . "actioncomm.datec,
                            " . MAIN_DB_PREFIX . "actioncomm.datep,
                            " . MAIN_DB_PREFIX . "actioncomm.datep2,
                            " . MAIN_DB_PREFIX . "actioncomm.label,
                            " . MAIN_DB_PREFIX . "actioncomm.id,
                            " . MAIN_DB_PREFIX . "c_actioncomm.libelle,
                            " . MAIN_DB_PREFIX . "projet.title,
                            " . MAIN_DB_PREFIX . "projet.ref,
                            " . MAIN_DB_PREFIX . "actioncomm.durationp,
                            " . MAIN_DB_PREFIX . "actioncomm.note,
                            " . MAIN_DB_PREFIX . "societe.nom as socname,
                            " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM  " . MAIN_DB_PREFIX . "societe, " . MAIN_DB_PREFIX . "actioncomm " . MAIN_DB_PREFIX . "actioncomm
                 LEFT JOIN " . MAIN_DB_PREFIX . "projet on " . MAIN_DB_PREFIX . "actioncomm.fk_project = " . MAIN_DB_PREFIX . "projet.rowid
                 LEFT JOIN " . MAIN_DB_PREFIX . "c_actioncomm on " . MAIN_DB_PREFIX . "c_actioncomm.id = " . MAIN_DB_PREFIX . "actioncomm.fk_action
                 LEFT JOIN " . MAIN_DB_PREFIX . "socpeople on " . MAIN_DB_PREFIX . "socpeople.rowid = " . MAIN_DB_PREFIX . "actioncomm.fk_contact
                    WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "actioncomm.fk_soc
                      AND (" . MAIN_DB_PREFIX . "actioncomm.fk_user_action =  " . $userId . " OR " . MAIN_DB_PREFIX . "actioncomm.fk_user_done =  " . $userId . " OR " . MAIN_DB_PREFIX . "actioncomm.fk_user_author =  " . $userId . " OR " . MAIN_DB_PREFIX . "actioncomm.fk_user_mod =  " . $userId . " )";

        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/comm/action/card.php?id=" . $res->id;
                if ($res->datec) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Actions'
                                         AND folder_parent = " . $parentFolderId;

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $allDay = 0;
                        if ($res->datep) {
                            $date = $res->datep;
                            if ($res->datep2 && $res->datep && $res->durationp != 0) {
                                $allDay = 1;
                                $date = array('debut' => $res->datep, 'fin' => $res->datep2);
                            }
                            $arrRes = $this->Babel_pushDateArr(
                                    $date, htmlentities($res->libelle . " (" . $res->socname . ")"), "Action commerciale " . htmlentities($res->libelle) . "<HR><P>" . htmlentities($res->label) . "<BR>Ref :" . $res->ref . "<HR><P>" . htmlentities($res->note) . "<BR><P>", $res->ref, $id, "" . MAIN_DB_PREFIX . "actioncomm", $allDay, //all day
                                    "", 1, //loc géo
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetCommande() {
        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "commande.rowid,
                           " . MAIN_DB_PREFIX . "commande.ref,
                           " . MAIN_DB_PREFIX . "commande.date_creation,
                           " . MAIN_DB_PREFIX . "commande.date_commande,
                           " . MAIN_DB_PREFIX . "commande.date_valid,
                           " . MAIN_DB_PREFIX . "commande.date_cloture,
                           " . MAIN_DB_PREFIX . "commande.fk_user_author,
                           " . MAIN_DB_PREFIX . "commande.fk_user_valid,
                           " . MAIN_DB_PREFIX . "commande.fk_user_cloture,
                           " . MAIN_DB_PREFIX . "commande.fk_statut,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid,
                           " . MAIN_DB_PREFIX . "commande.note_private as note,
                           " . MAIN_DB_PREFIX . "commande.note_public,
                           " . MAIN_DB_PREFIX . "commande.date_livraison
                      FROM " . MAIN_DB_PREFIX . "commande, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "commande.fk_soc ";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/commande/card.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Commandes'
                                         AND folder_parent =( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;

                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_creation, "Créat. de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la commande " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_commande) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Date commande " . "" . $res->ref . "" . " (" . $res->socname . ")", "Commande  " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_valid && $res->date_cloture . "x" == "x") {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la commande " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    } else if ($res->date_cloture) {
                        $arrRes = $this->Babel_pushDateArr(
                                array('debut' => $res->date_valid, 'fin' => $res->date_cloture), "Clot de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cloture de la commande " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetCommandeUser($userid, $parentFolderId) {
        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "commande.rowid,
                           " . MAIN_DB_PREFIX . "commande.ref,
                           " . MAIN_DB_PREFIX . "commande.date_creation,
                           " . MAIN_DB_PREFIX . "commande.date_commande,
                           " . MAIN_DB_PREFIX . "commande.date_valid,
                           " . MAIN_DB_PREFIX . "commande.date_cloture,
                           " . MAIN_DB_PREFIX . "commande.fk_user_author,
                           " . MAIN_DB_PREFIX . "commande.fk_user_valid,
                           " . MAIN_DB_PREFIX . "commande.fk_user_cloture,
                           " . MAIN_DB_PREFIX . "commande.fk_statut,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid,
                           " . MAIN_DB_PREFIX . "commande.note_private as note,
                           " . MAIN_DB_PREFIX . "commande.note_public,
                           " . MAIN_DB_PREFIX . "commande.date_livraison
                      FROM " . MAIN_DB_PREFIX . "commande, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "commande.fk_soc
                       AND (" . MAIN_DB_PREFIX . "commande.fk_user_author = " . $userid . " OR " . MAIN_DB_PREFIX . "commande.fk_user_valid = " . $userid . " OR " . MAIN_DB_PREFIX . "commande.fk_user_cloture =" . $userid . " ) ";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/commande/card.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Commandes'
                                         AND folder_parent = " . $parentFolderId;

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;

                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_creation, "Créat. de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la commande " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_commande) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Date commande " . "" . $res->ref . "" . " (" . $res->socname . ")", "Commande  " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_valid && $res->date_cloture . "x" == "x") {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la commande " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    } else if ($res->date_cloture) {
                        $arrRes = $this->Babel_pushDateArr(
                                array('debut' => $res->date_valid, 'fin' => $res->date_cloture), "Clot de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cloture de la commande " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetCommandeUserById($userid, $commandeId) {
        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "commande.rowid,
                           " . MAIN_DB_PREFIX . "commande.ref,
                           " . MAIN_DB_PREFIX . "commande.date_creation,
                           " . MAIN_DB_PREFIX . "commande.date_commande,
                           " . MAIN_DB_PREFIX . "commande.date_valid,
                           " . MAIN_DB_PREFIX . "commande.date_cloture,
                           " . MAIN_DB_PREFIX . "commande.fk_user_author,
                           " . MAIN_DB_PREFIX . "commande.fk_user_valid,
                           " . MAIN_DB_PREFIX . "commande.fk_user_cloture,
                           " . MAIN_DB_PREFIX . "commande.fk_statut,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid,
                           " . MAIN_DB_PREFIX . "commande.note_private as note,
                           " . MAIN_DB_PREFIX . "commande.note_public,
                           " . MAIN_DB_PREFIX . "commande.date_livraison
                      FROM " . MAIN_DB_PREFIX . "commande, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "commande.fk_soc
                       AND " . MAIN_DB_PREFIX . "commande.rowid =  " . $commandeId;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/commande/card.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    $tmpUser = new User($this->db);
                    $tmpUser->fetch($userId);
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Commandes'
                                         AND folder_parent = ( SELECT max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $tmpUser->firstname . ' ' . $tmpUser->lastname . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_creation, "Créat. de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la commande " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_commande) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Date commande " . "" . $res->ref . "" . " (" . $res->socname . ")", "Commande  " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_valid && $res->date_cloture . "x" == "x") {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la commande " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    } else if ($res->date_cloture) {
                        $arrRes = $this->Babel_pushDateArr(
                                array('debut' => $res->date_valid, 'fin' => $res->date_cloture), "Clot de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cloture de la commande " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetCommandeFourn() {
        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "commande_fournisseur.rowid,
                           " . MAIN_DB_PREFIX . "commande_fournisseur.ref,
                           " . MAIN_DB_PREFIX . "commande_fournisseur.date_creation,
                           " . MAIN_DB_PREFIX . "commande_fournisseur.date_commande,
                           " . MAIN_DB_PREFIX . "commande_fournisseur.date_valid,
                           " . MAIN_DB_PREFIX . "commande_fournisseur.date_cloture,
                           " . MAIN_DB_PREFIX . "commande_fournisseur.fk_user_author,
                           " . MAIN_DB_PREFIX . "commande_fournisseur.fk_user_valid,
                           " . MAIN_DB_PREFIX . "commande_fournisseur.fk_user_cloture,
                           " . MAIN_DB_PREFIX . "commande_fournisseur.fk_statut,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid,
                           " . MAIN_DB_PREFIX . "commande_fournisseur.note,
                           " . MAIN_DB_PREFIX . "commande_fournisseur.note_public
                      FROM " . MAIN_DB_PREFIX . "commande_fournisseur, " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "commande_fournisseur.fk_soc ";
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;

        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/fourn/commande/card.php?id=" . $res->rowid;
                if ($res->date_creation) {
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Commandes fournisseur'
                                         AND folder_parent =( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $res->socname . '-' . $res->socid . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;

                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_creation, "Créat. de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation de la commande fournisseur " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande_fournisseur", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_commande) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Date commande fournisseur" . "" . $res->ref . "" . " (" . $res->socname . ")", "Commande  fournisseur" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande_fournisseur", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->date_valid && $res->date_cloture . "x" == "x") {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Valid de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validit&eacute; de la commande fournisseur" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande_fournisseur", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    } else if ($res->date_cloture) {
                        $arrRes = $this->Babel_pushDateArr(
                                array('debut' => $res->date_valid, 'fin' => $res->date_cloture), "Clot de " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cloture de la commande fournisseur" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->ref, $id, "" . MAIN_DB_PREFIX . "commande_fournisseur", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetSQLParentFolder($uuid) {
        $requete = "SELECT folder_parent
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                     WHERE folder_uid=$uuid";
        $res = $this->db->query($requete);
        return($this->db->fetch_object($res)->folder_parent);
    }

    public function Babel_AddEventFromTrigger($typeId = 1, $arr, $zimId) {
        $id = $arr['obj']->rowid;
        if ("x" . $id == "x") {
            $id = $arr['obj']->id;
        }
        if ("x" . $typeId == "x") {
            $typeId = 1;
        }
        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger
                                (`type_event_refid`,`event_uid` ,`event_folder` , `event_table_link`, `event_table_id`,      `datec`,`dateu`)
                         VALUES (" . $typeId . ",       '" . $zimId . "'," . $arr['l'] . ",   '" . $arr['cat'] . "',  " . $id . ",now(),   now())";
        $this->db->query($requete);
//        print $requete;
//        exit();
    }

    public function Synopsis_Zimbra_GetSoc() {
        $db = $this->db;
        //1 get All soc
        $count = 0;
        $count2 = 0;
        $arrAlpha = array(0 => 'abc', 1 => 'def', 2 => 'ghi', 3 => 'jkl', 4 => 'mno', 5 => 'pqrs', 6 => 'tuv', 7 => 'wxyz', 8 => 'autres');

        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "societe
                     WHERE client > 0";
        if ($resqlsoc = $db->query($requete)) {
            while ($soc = $db->fetch_object($resqlsoc)) {
                $count++;
                //2 create Dir in calendar
                $where = "";
                //1ere lettre de la societe
                $firstLetter = $soc->nom;
                $firstLetterA = $firstLetter[0];
                $firstLetterIn = $arrAlpha[8];
                for ($i = 0; $i < 8; $i++) {
                    if (preg_match("/" . $firstLetterA . "/i", $arrAlpha[$i])) {
                        $firstLetterIn = $arrAlpha[$i];
                    }
                }
                //Trouve le numéro de rep
                $requete = "SELECT folder_uid
                             FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                            WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='appointment'
                              AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                              AND skeleton_part =1
                              AND folder_name = '" . $firstLetterIn . "'
                              AND folder_parent = ( SELECT  max(folder_uid) FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                     WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='appointment'
                                                      AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                                                      AND folder_name  LIKE 'Soci%t%s')";
                $arr4ndFold = array();
                $resql = $db->query($requete);
                if ($where = $db->fetch_object($resql)->folder_uid) {
                    $createArray = array('view' => 'appointment',
                        "name" => iconv("ISO-8859-1", "UTF-8", $soc->nom) . "-" . $soc->rowid,
                        "where" => $where);
                    $ret = $this->BabelCreateFolder($createArray);
                    $arr4ndFold["appointment"][] = $ret;
                    //fill SQL table
                    $this->BabelInsertTriggerFolder($ret['id'], $ret['name'], $ret['parent'], "appointment", 2);
                    //create SubFolder =>
                    $this->subFolderBIMP-ERP = array();
                    $this->Babel_createBIMP-ERPSubFolder($ret['id'], "soc");
                    foreach ($this->subFolderBIMP-ERP as $key => $val) {
                        $this->BabelInsertTriggerFolder($val['id'], $val['name'], $val['parent'], "appointment", 2);
                    }
                }
                //Fiche contact:
                $socArr = array();
                //Trouve le numéro de rep
                $requete = "SELECT folder_uid, " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id as ttid
                             FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                            WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='contact'
                              AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                              AND skeleton_part =1
                              AND folder_name LIKE 'Soci%t%'
                              AND folder_parent = ( SELECT  max(folder_uid) FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                     WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='contact'
                                                      AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                                                      AND folder_name  = 'Contacts - BIMP-ERP')";
                $resql = $db->query($requete);
                if ($resTmp = $db->fetch_object($resql)) {
                    $where = $resTmp->folder_uid;
                    $typeId = $resTmp->ttid;
                    $socArr = array();
                    $socArr['l'] = $where;
                    $confPrefCat = "work";
                    $confJabberPrefix = "other://";
                    $socArr['contactDet']["fileAs"] = 3;
                    if ("x" . $soc->address != "x") {
                        $socArr['contactDet'][$confPrefCat . "Street"] = iconv("ISO-8859-1", "UTF-8", $soc->addresss);
                    }
                    if ("x" . $soc->zip != "x") {
                        $socArr['contactDet'][$confPrefCat . "PostalCode"] = iconv("ISO-8859-1", "UTF-8", $soc->zip);
                    }
                    if ("x" . $soc->town != "x") {
                        $socArr['contactDet'][$confPrefCat . "City"] = iconv("ISO-8859-1", "UTF-8", $soc->town);
                    }
                    if ("x" . $soc->pays != "x" && $soc->pays_id > 0) {
                        $socArr['contactDet'][$confPrefCat . "Country"] = iconv("ISO-8859-1", "UTF-8", $soc->pays);
                    }
                    if ("x" . $soc->email != "x") {
                        $socArr['contactDet']["email"] = iconv("ISO-8859-1", "UTF-8", $soc->email);
                    }
                    if ("x" . $soc->note != "x") {
                        $socArr['contactDet']["notes"] = iconv("ISO-8859-1", "UTF-8", $soc->note);
                    }
                    if ("x" . $soc->phone_pro != "x") {
                        $socArr['contactDet']["workPhone"] = iconv("ISO-8859-1", "UTF-8", $soc->phone);
                        $socArr['contactDet']['companyPhone'] = iconv("ISO-8859-1", "UTF-8", $soc->phone);
                    }
                    if ("x" . $soc->fax != "x") {
                        $socArr['contactDet'][$confPrefCat . "Fax"] = iconv("ISO-8859-1", "UTF-8", $soc->fax);
                    }
                    if ("x" . $soc->nom != "x") {
                        $socArr['contactDet']["company"] = iconv("ISO-8859-1", "UTF-8", $soc->nom);
                        $socArr['contactDet']["fullName"] = iconv("ISO-8859-1", "UTF-8", $soc->nom);
                        $socArr['contactDet']["lastName"] = iconv("ISO-8859-1", "UTF-8", $soc->nom);
                    }
                    $ret = $this->createContBabel($socArr);
                    $zimId = $ret["id"];
                    $arr = array();
                    $arr['l'] = $where;
                    $arr['cat'] = "" . MAIN_DB_PREFIX . "societe";
                    $arr['obj'] = $soc;
                    $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);
                    $count2++;
                }
            }
            print $count2 . " soci&eacute;t&eacute;s synchronis&eacute;es sur " . $count . "<BR>";
            //3 create File in contact
        }
        //2 créer le folder dans le bon folder id et les sous folders propal , commande, facture expedition, ...
    }

    public function Synopsis_Zimbra_GetPeople() {
        $db = $this->db;
        //1 get All soc
        $count = 0;
        require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
        $requete = "SELECT rowid
                      FROM " . MAIN_DB_PREFIX . "socpeople
                   ";
        if ($resqlsoc = $db->query($requete)) {
            while ($res = $db->fetch_object($resqlsoc)) {
                $count++;
                //2 create Dir in calendar
                $where = "";
                //Fiche contact:
                $socArr = array();
                //Trouve le numéro de rep
                $requete = "SELECT folder_uid, " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id as ttid
                             FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                            WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='contact'
                              AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                              AND skeleton_part =1
                              AND folder_name = 'Personnes'
                              AND folder_parent = ( SELECT  max(folder_uid) FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                     WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='contact'
                                                      AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                                                      AND folder_name  LIKE 'Contacts - BIMP-ERP')";
                if ($resql = $db->query($requete)) {
                    $cont = new Contact($db);
                    $cont->fetch($res->rowid);
                    $resTmp = $db->fetch_object($resql);
                    $where = $resTmp->folder_uid;
                    $typeId = $resTmp->ttid;
                    $ret = $this->connect();
                    //Get Cotact Folder
                    $contArr = array();
                    $contArr['l'] = $where;
                    $confPrefCat = "work";
                    $confJabberPrefix = "other://";
                    if ("x" . $cont->nom != "x") {
                        $contArr['contactDet']["lastName"] = iconv("ISO-8859-1", "UTF-8", $cont->nom);
                    }
                    if ("x" . $cont->prenom != "x") {
                        $contArr['contactDet']["firstName"] = iconv("ISO-8859-1", "UTF-8", $cont->prenom);
                    }
                    if ("x" . $cont->address != "x") {
                        $contArr['contactDet'][$confPrefCat . "Street"] = iconv("ISO-8859-1", "UTF-8", $cont->address);
                    }
                    if ("x" . $cont->cp != "x") {
                        $contArr['contactDet'][$confPrefCat . "PostalCode"] = iconv("ISO-8859-1", "UTF-8", $cont->cp);
                    }
                    if ("x" . $cont->ville != "x") {
                        $contArr['contactDet'][$confPrefCat . "City"] = iconv("ISO-8859-1", "UTF-8", $cont->ville);
                    }
                    if ("x" . $cont->pays != "x" && $cont->fk_pays > 0) {
                        $contArr['contactDet'][$confPrefCat . "Country"] = iconv("ISO-8859-1", "UTF-8", $cont->pays);
                    }
                    if ("x" . $cont->poste != "x") {
                        $contArr['contactDet']["jobTitle"] = iconv("ISO-8859-1", "UTF-8", $cont->poste);
                    }
                    if ("x" . $cont->email != "x") {
                        $contArr['contactDet']["email"] = iconv("ISO-8859-1", "UTF-8", $cont->email);
                    }
                    if ("x" . $cont->birthday_mysql != "x") {
                        $contArr['contactDet']["birthday"] = iconv("ISO-8859-1", "UTF-8", $cont->birthday_mysql);
                    }
                    if ("x" . $cont->jabberid != "x") {
                        $contArr['contactDet']["imAddress1"] = iconv("ISO-8859-1", "UTF-8", $confJabberPrefix . $cont->jabberid);
                    }
                    if ("x" . $cont->note != "x") {
                        $contArr['contactDet']["notes"] = iconv("ISO-8859-1", "UTF-8", $cont->note);
                    }
                    if ("x" . $cont->phone_pro != "x") {
                        $contArr['contactDet']["workPhone"] = iconv("ISO-8859-1", "UTF-8", $cont->phone_pro);
                    }
                    if ("x" . $cont->fax != "x") {
                        $contArr['contactDet'][$confPrefCat . "Fax"] = iconv("ISO-8859-1", "UTF-8", $cont->fax);
                    }
                    if ("x" . $cont->phone_perso != "x") {
                        $contArr['contactDet']["homePhone"] = iconv("ISO-8859-1", "UTF-8", $cont->phone_perso);
                    }
                    if ("x" . $cont->phone_mobile != "x") {
                        $contArr['contactDet']["mobilePhone"] = iconv("ISO-8859-1", "UTF-8", $cont->phone_mobile);
                    }
                    if ("x" . $cont->socname != "x") {
                        $contArr['contactDet']["company"] = iconv("ISO-8859-1", "UTF-8", $cont->socname);
                    }
                    //get Company phone if exist
                    $requeteTel = "SELECT " . MAIN_DB_PREFIX . "societe.tel
                                  FROM " . MAIN_DB_PREFIX . "societe
                                 WHERE rowid = " . $cont->fk_soc;
                    if ($resqlTel = $db->query($requeteTel)) {
                        $resTel = $db->fetch_object($resqlTel);
                        if ($resTel->tel . "x" != "x") {
                            $contArr['contactDet']['companyPhone'] = $resTel->tel;
                        }
                    }
                    $ret = $this->createContBabel($contArr);
                    $zimId = $ret["id"];
                    $arr = array();
                    $arr['l'] = $where;
                    $arr['cat'] = "" . MAIN_DB_PREFIX . "socpeople";
                    $arr['obj'] = $cont;
                    $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);
                }
            }
            print $count . "contacts synchronis&eacute;es<BR>";
            //3 create File in contact
        }
        //2 créer le folder dans le bon folder id et les sous folders propal , commande, facture expedition, ...
    }

    public function BabelInsertTriggerFolder($id, $name, $parent, $type, $skel = 1) {
        $db = $this->db;
        $requete = "INSERT IGNORE INTO " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
             (`folder_type_refid`,
              `folder_uid`,
              `folder_name`,
              `folder_parent`,
              `datec`,
              `dateu`,
              `skeleton_part`)
      VALUES (
              (SELECT id from " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type WHERE val = '" . $type . "'),
              " . $id . ",
              '" . $name . "',
              '" . $parent . "',
              now(),
              now(),
              $skel
            )";
        //print $requete ."<br>";
        $db->query($requete);
    }

    public function Synopsis_Zimbra_GetContratDetUserById($userId, $contratid) {
        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "contrat.rowid,
                           " . MAIN_DB_PREFIX . "contratdet.date_commande,
                           " . MAIN_DB_PREFIX . "contratdet.label as ref,
                           " . MAIN_DB_PREFIX . "contratdet.date_ouverture_prevue,
                           " . MAIN_DB_PREFIX . "contratdet.date_ouverture,
                           " . MAIN_DB_PREFIX . "contratdet.date_fin_validite as date_detfinvalid,
                           " . MAIN_DB_PREFIX . "contratdet.date_cloture as date_detcloture,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM " . MAIN_DB_PREFIX . "contrat,
                           " . MAIN_DB_PREFIX . "contratdet,
                           " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "contratdet.fk_contrat = " . MAIN_DB_PREFIX . "contrat.rowid
                       AND " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "contrat.fk_soc
                       AND " . MAIN_DB_PREFIX . "contrat.rowid = " . $contratid;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/contrat/card.php?id=" . $res->rowid;
                if ($res->date_ouverture) {
                    $tmpUser = new User($this->db);
                    $tmpUser->fetch($userId);
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Contrats'
                                         AND folder_parent =( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $tmpUser->getFullName($langs) . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";

                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        if ($res->date_ouverture) {
                            $arrRes = $this->Babel_pushDateArr(
                                    $res->date_ouverture, "Ouv du serv " . "" . $res->ref . "" . " (" . $res->socname . ")", "Ouverture du service " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "contratdet", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        } else {
                            $arrRes = $zim->Babel_pushDateArr(
                                    $res->date_ouverture_prevue, "Ouv du serv. prev" . "" . $res->ref . "" . " (" . $res->socname . ")", "Ouverture pr&eacute;visonnelle du service" . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "contratdet", 1, //all day
                                    "", 1, //loc géo
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                        if ($res->date_commande) {
                            $arrRes = $this->Babel_pushDateArr(
                                    $res->date_commande, "Fin de val. du serv. " . "" . $res->ref . "" . " (" . $res->socname . ")", "Fin de validat&eacute; du service " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "contratdet", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }

                        if ($res->date_detfinvalid) {
                            $arrRes = $this->Babel_pushDateArr(
                                    $res->date_detfinvalid, "Cl&ocirc;t. du  serv. " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cl&ocirc;ture du service " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "contratdet", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                        if ($res->date_detcloture) {
                            $arrRes = $this->Babel_pushDateArr(
                                    $res->date_detcloture, "Valid. de la DI " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validation de la demande intervention " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "contratdet", 1, //all day
                                    "", //loc géo
                                    1, //is org
                                    $zimLoc, //loc zimbra
                                    $url, $soc->id, $res);
                            $id++;
                        }
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
//                $parent = $arr['l'];
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetContratUserById($userId, $contratid) {
        //Attn soc KO
        $db = $this->db;
        $requete = "SELECT " . MAIN_DB_PREFIX . "contrat.rowid,
                           " . MAIN_DB_PREFIX . "contrat.fk_soc,
                           " . MAIN_DB_PREFIX . "contrat.ref,
                           " . MAIN_DB_PREFIX . "contrat.datec,
                           " . MAIN_DB_PREFIX . "contrat.date_contrat,
                           " . MAIN_DB_PREFIX . "contrat.mise_en_service,
                           " . MAIN_DB_PREFIX . "contrat.date_valid,
                           " . MAIN_DB_PREFIX . "contrat.fin_validite,
                           " . MAIN_DB_PREFIX . "contrat.date_cloture,
                           " . MAIN_DB_PREFIX . "societe.nom as socname,
                           " . MAIN_DB_PREFIX . "societe.rowid as socid
                      FROM " . MAIN_DB_PREFIX . "contrat,
                           " . MAIN_DB_PREFIX . "societe
                     WHERE " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "contrat.fk_soc
                       AND " . MAIN_DB_PREFIX . "contrat.rowid = " . $contratid;
        $resql = $db->query($requete);
        $id = 0;
        $typeId = false;
        if ($resql) {
            while ($res = $db->fetch_object($resql)) {
                $url = $this->dolibarr_main_url_root . "/contrat/card.php?id=" . $res->rowid;
                if ($res->datec) {
                    $tmpUser = new User($this->db);
                    $tmpUser->fetch($userId);
                    //get Loc Zimbra
                    $requeteLocZim = "SELECT folder_type_refid as ftid,
                                             folder_uid as fid
                                        FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                       WHERE folder_name='Contrats'
                                         AND folder_parent =( SELECT  max(folder_uid)
                                                                FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder
                                                               WHERE folder_name ='" . $tmpUser->getFullName($langs) . "'
                                                                 AND folder_type_refid = (SELECT id
                                                                                            FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                                                           WHERE val='appointment'))";


                    if ($resqlLocZim = $db->query($requeteLocZim)) {
                        $zimRes = $db->fetch_object($resqlLocZim);
                        $zimLoc = $zimRes->fid;
                        $typeId = $zimRes->ftid;
                        $arrRes = $this->Babel_pushDateArr(
                                $res->datec, "Créat. du contrat " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cr&eacute;ation du contrat " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "contrat", 1, //all day
                                "", 1, //loc géo
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_contrat) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_contrat, "Contrat " . "" . $res->ref . "" . " (" . $res->socname . ")", "Contrat " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "contrat", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->mise_en_service) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->mise_en_service, "Mise en serv. du contrat " . "" . $res->ref . "" . " (" . $res->socname . ")", "Mise en service du contrat " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "contrat", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                    if ($res->fin_validite) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->fin_validite, "Fin de val. du contrat " . "" . $res->ref . "" . " (" . $res->socname . ")", "Fin de validit&eacute; du contrat  " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "contrat", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_valid) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_valid, "Valid. du contrat " . "" . $res->ref . "" . " (" . $res->socname . ")", "Validation du contrat  " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "contrat", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }

                    if ($res->date_cloture) {
                        $arrRes = $this->Babel_pushDateArr(
                                $res->date_cloture, "Cl&ocirc;ture contrat " . "" . $res->ref . "" . " (" . $res->socname . ")", "Cl&ocirc;ture du contrat " . $res->ref . "<BR><P>" . $res->note . "<BR><P>" . $res->note_public, $res->rowid, $id, "" . MAIN_DB_PREFIX . "contrat", 1, //all day
                                "", //loc géo
                                1, //is org
                                $zimLoc, //loc zimbra
                                $url, $soc->id, $res);
                        $id++;
                    }
                }
            }
            while (count($this->ApptArray) > 0) {
                $arr = array_pop($this->ApptArray);
                $arr1 = $arr;
                //extract socid
                //Store to Db, Store to Zimbra
                $ret = $this->createApptBabel($arr);
                // Store to ".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger => fct => zimbraTrigger
                $zimId = $ret["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr, $zimId);


                //faut aussi placer l'event dans le calendrier de la société
                $parentId = $this->Synopsis_Zimbra_GetSQLParentFolder($arr['l']);
                $arr1['l'] = $parentId;
                $ret1 = $this->createApptBabel($arr1);
                $zimId1 = $ret1["CreateAppointmentResponse_attribute_invId"][0];
                $this->Babel_AddEventFromTrigger($typeId, $arr1, $zimId1);
            }
        }
    }

//fcuntion

    public function Synopsis_Zimbra_GetFourn() {
        $db = $this->db;
        //1 get All soc
        $count = 0;
        $count2 = 0;
        $arrAlpha = array(0 => 'abc', 1 => 'def', 2 => 'ghi', 3 => 'jkl', 4 => 'mno', 5 => 'pqrs', 6 => 'tuv', 7 => 'wxyz', 8 => 'autres');

        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "societe
                     WHERE fournisseur > 0 AND client = 0";
        if ($resqlsoc = $db->query($requete)) {
            while ($soc = $db->fetch_object($resqlsoc)) {
                $count++;
                //2 create Dir in calendar
                $where = "";
                //1ere lettre de la societe
                $firstLetter = $soc->nom;
                $firstLetterA = $firstLetter[0];
                $firstLetterIn = $arrAlpha[8];
                for ($i = 0; $i < 8; $i++) {
                    if (preg_match("/" . $firstLetterA . "/i", $arrAlpha[$i])) {
                        $firstLetterIn = $arrAlpha[$i];
                    }
                }
                //Trouve le numéro de rep
                $requete = "SELECT folder_uid
                             FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                            WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='appointment'
                              AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                              AND skeleton_part =1
                              AND folder_name = '" . $firstLetterIn . "'
                              AND folder_parent = ( SELECT  max(folder_uid) FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                                                     WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='appointment'
                                                      AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                                                      AND folder_name  LIKE 'Soci%t%')";
                $arr4ndFold = array();
                if ($resql = $db->query($requete)) {
                    $where = $db->fetch_object($resql)->folder_uid;
                    $createArray = array('view' => 'appointment',
                        "name" => iconv("ISO-8859-1", "UTF-8", $soc->nom) . "-" . $soc->rowid,
                        "where" => $where);
                    $ret = $this->BabelCreateFolder($createArray);
                    $arr4ndFold["appointment"][] = $ret;
                    //fill SQL table
                    $this->BabelInsertTriggerFolder($ret['id'], $ret['name'], $ret['parent'], "appointment", 2);
                    //create SubFolder =>
                    $this->subFolderBIMP-ERP = array();
                    $this->Babel_createBIMP-ERPSubFolder($ret['id'], "fourn");

                    foreach ($this->subFolderBIMP-ERP as $key => $val) {
                        //get new Id
                        $this->BabelInsertTriggerFolder($val['id'], $val['name'], $val['parent'], "appointment", 2);
                    }
                }
                //Fiche contact:
                $socArr = array();
                //Trouve le numéro de rep
                $requete = "SELECT folder_uid
                             FROM " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder," . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type
                            WHERE " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.val ='contact'
                              AND " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_type.id = " . MAIN_DB_PREFIX . "Synopsis_Zimbra_trigger_folder.folder_type_refid
                              AND skeleton_part =1
                              AND folder_name  LIKE 'Soci%t%'";
                $resql = $db->query($requete);
                if ($res = $db->fetch_object($resql)) {
                    $where = $res->folder_uid;
                    $socArr = array();
                    $socArr['l'] = $where;
                    $confPrefCat = "work";
                    $confJabberPrefix = "other://";
                    $socArr['contactDet']["fileAs"] = 3;
                    if ("x" . $soc->address != "x") {
                        $socArr['contactDet'][$confPrefCat . "Street"] = iconv("ISO-8859-1", "UTF-8", $soc->addresss);
                    }
                    if ("x" . $soc->zip != "x") {
                        $socArr['contactDet'][$confPrefCat . "PostalCode"] = iconv("ISO-8859-1", "UTF-8", $soc->zip);
                    }
                    if ("x" . $soc->town != "x") {
                        $socArr['contactDet'][$confPrefCat . "City"] = iconv("ISO-8859-1", "UTF-8", $soc->town);
                    }
                    if ("x" . $soc->pays != "x" && $soc->pays_id > 0) {
                        $socArr['contactDet'][$confPrefCat . "Country"] = iconv("ISO-8859-1", "UTF-8", $soc->pays);
                    }
                    if ("x" . $soc->email != "x") {
                        $socArr['contactDet']["email"] = iconv("ISO-8859-1", "UTF-8", $soc->email);
                    }
                    if ("x" . $soc->note != "x") {
                        $socArr['contactDet']["notes"] = iconv("ISO-8859-1", "UTF-8", $soc->note);
                    }
                    if ("x" . $soc->phone_pro != "x") {
                        $socArr['contactDet']["workPhone"] = iconv("ISO-8859-1", "UTF-8", $soc->phone);
                        $socArr['contactDet']['companyPhone'] = iconv("ISO-8859-1", "UTF-8", $soc->phone);
                    }
                    if ("x" . $soc->fax != "x") {
                        $socArr['contactDet'][$confPrefCat . "Fax"] = iconv("ISO-8859-1", "UTF-8", $soc->fax);
                    }
                    if ("x" . $soc->nom != "x") {
                        $socArr['contactDet']["company"] = iconv("ISO-8859-1", "UTF-8", $soc->nom);
                        $socArr['contactDet']["fullName"] = iconv("ISO-8859-1", "UTF-8", $soc->nom);
                        $socArr['contactDet']["lastName"] = iconv("ISO-8859-1", "UTF-8", $soc->nom);
                    }

                    $this->createContBabel($socArr);
                    $count2++;
                }
            }
            print $count2 . " fournisseurs synchronis&eacute;es sur " . $count . "<BR>";
            //3 create File in contact
        }
        //2 créer le folder dans le bon folder id et les sous folders propal , commande, facture expedition, ...
    }

    public $subFolderBIMP-ERP = array();

    function Babel_createBIMP-ERPSubFolder($where, $type = 'soc') {
        if ($type == 'soc') {
            foreach (array('Propales', 'Commandes', "Factures", "Expeditions", "Interventions", "Contrats", "Actions") as $key) {
                $createArray = array('view' => 'appointment',
                    "name" => $key,
                    "where" => $where);
                $ret = $this->BabelCreateFolder($createArray);
                array_push($this->subFolderBIMP-ERP, $ret);
            }
        }
        if ($type == 'fourn') {
            foreach (array('Propales', 'Commandes', "Factures ", "Expeditions", "Interventions", "Contrats", "Actions", 'Commandes fournisseur', "Factures fournisseur") as $key) {
                $createArray = array('view' => 'appointment',
                    "name" => $key,
                    "where" => $where);
                $ret = $this->BabelCreateFolder($createArray);
                array_push($this->subFolderBIMP-ERP, $ret);
            }
        }
        if ($type == 'user') {
            foreach (array('Propales', 'Commandes', "Factures ", "Livraisons", "Interventions", "Contrats", "Actions") as $key) {
                $createArray = array('view' => 'appointment',
                    "name" => $key,
                    "where" => $where);
                $ret = $this->BabelCreateFolder($createArray);
                array_push($this->subFolderBIMP-ERP, $ret);
            }
        }
    }

    public function BabelcheckDispo($myStartDate, $myEndDate, $ressource) {
        $zim1 = new Zimbra($ressource);
        $zim1->debug = false;
        $zim1->connect();
        $ret = $zim1->BabelSearchRequest(array(), "anywhere", "appointment");
        $statutForDate = "busy";
        foreach ($ret['appt'] as $key => $val) {

            $ret1 = $zim1->BabelGetAppointment($val['id']);
            $start = 0;
            $end = 0;
            if (preg_match("/([0-9]{4})([0-9]{2})([0-9]{2})[\w]{1}([0-9]{2})([0-9]{2})([0-9]{2})/", $ret1["appt"][0]['inv'][0]['comp'][0]["e_attribute_d"][0], $arr)) {
                $end = mktime($arr[4], $arr[5], $arr[6], $arr[2], $arr[3], $arr[1]);
            }
            if (preg_match("/([0-9]{4})([0-9]{2})([0-9]{2})[\w]{1}([0-9]{2})([0-9]{2})([0-9]{2})/", $ret1["appt"][0]['inv'][0]['comp'][0]["s_attribute_d"][0], $arr)) {
                $start = mktime($arr[4], $arr[5], $arr[6], $arr[2], $arr[3], $arr[1]);
            }
            if ($myStartDate > $end) {
                //free continue
                $statutForDate = "free";
                continue;
            } else if ($myEndDate < $start) {
                $statutForDate = "free";
                continue;
                //free continue
            } else if ($myStartDate > $start && $myStartDate < $end) {
                $statutForDate = "busy";
                break;
                //busy
            } else if ($myEndDate > $start && $myEndDate < $end) {
                $statutForDate = "busy";
                break;
                //busy
            } else if ($myEndDate > $start && $myEndDate < $end) {
                $statutForDate = "busy";
                break;
                //busy
            } else if ($start < $myStartDate && $myEndDate > $myStartDate) {
                $statutForDate = "busy";
                break;
                //busy
            }
        }
        return($statutForDate);
    }

    public function BabelCreateMountPoint($id, $rid, $name, $l = 1) {
        $soap = '  <CreateMountpointRequest  xmlns="urn:zimbraMail">';
        $soap .= '<link  xmlns="" l="' . $l . '" perm="r" n="1" s="0" name="' . $name . '" color="7" type="appointment" zid="' . $id . '"  view="appointment" f="#" rid="' . $rid . '"/>';
        $soap .= '</CreateMountpointRequest>';

        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['CreateMountpointResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['CreateMountpointResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['CreateMountpointResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelShareCal($rid, $parent) {
        $soap = '  <FolderActionRequest  xmlns="urn:zimbraMail">';
        $soap .= '  <action xmlns="" op="grant" id="' . $parent . '" >';
        $soap .= '      <grant gt="usr" inh="1" zid="' . $rid . '" perm="rwidaxpf" args=""/>';
        $soap .= "  </action>";
        $soap .= '</FolderActionRequest>';

        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['FolderActionResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['FolderActionResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['FolderActionResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function BabelCloseShareCal($rid, $parent) {
        $soap = '  <FolderActionRequest  xmlns="urn:zimbraMail">';
        $soap .= '  <action xmlns="" op="grant" id="' . $parent . '" >';
        $soap .= '      <grant gt="usr" inh="1" zid="' . $rid . '" perm="" args=""/>';
        $soap .= "  </action>";
        $soap .= '</FolderActionRequest>';

        $response = $this->soapRequest($soap);
        if ($response) {
            $array = $this->makeXMLTree($response);

            $newFolder = (is_array($array['soap:Envelope'][0]['soap:Body'][0]['FolderActionResponse'][0])) ? $array['soap:Envelope'][0]['soap:Body'][0]['FolderActionResponse'][0] : $array['soap:Envelope'][0]['soap:Body'][0]['FolderActionResponse'][0];

            return $newFolder;
        } else {
            return false;
        }
    }

    public function getMainCalId($arrConnect) {
        $tmpKey = "";
        foreach ($arrConnect['folder_attribute_name'] as $key => $val) {
            if ($val == "Calendar") {
                $tmpKey = $key;
                break;
            }
        }
        return($arrConnect['folder_attribute_id'][$tmpKey]);
    }

}

// end Zimbra class
// annoying sorting functions for getTasks...
// I don't know how to make usort calls to internal OO functions
// if someone knows how, please fix this :)

/**
 * zimbra_startSort
 *
 * sort of zimbra elements
 *
 * @since        version 1.0
 * @access    public
 * @param    array $task_a
 * @param    array $task_b
 * @return    int (($task_a['dueDate']-$task_a['dur']) < ($task_b['dueDate']-$task_b['dur'])) ? -1 : 1
 */
function zimbra_startSort($task_a, $task_b) {
    if (($task_a['dueDate'] - $task_a['dur']) == ($task_b['dueDate'] - $task_b['dur'])) {
        return ($task_a['name'] < $task_b['name']) ? -1 : 1;
    }
    return (($task_a['dueDate'] - $task_a['dur']) < ($task_b['dueDate'] - $task_b['dur'])) ? -1 : 1;
}

/**
 * zimbra_dueSort
 *
 * sort by dueDate
 *
 * @since        version 1.0
 * @access    public
 * @param    array $task_a
 * @param    array $task_b
 * @return    int ($task_a['dueDate'] < $task_b['dueDate']) ? -1 : 1
 */
function zimbra_dueSort($task_a, $task_b) {
    if ($task_a['dueDate'] == $task_b['dueDate']) {
        return ($task_a['name'] < $task_b['name']) ? -1 : 1;
    }
    return ($task_a['dueDate'] < $task_b['dueDate']) ? -1 : 1;
}

/**
 * zimbra_nameSort
 *
 * sort by name
 *
 * @since        version 1.0
 * @access    public
 * @param    array $task_a
 * @param    array $task_b
 * @return    int ($task_a['name'] < $task_b['name']) ? -1 : 1
 */
function zimbra_nameSort($task_a, $task_b) {
    if ($task_a['name'] == $task_b['name']) {
        return 0;
    }
    return ($task_a['name'] < $task_b['name']) ? -1 : 1;
}

//
//
//
//require('Var_Dump.php');
//Var_Dump::displayInit(array('display_mode' => 'HTML4_Text'), array('mode' => 'normal','offset' => 4));
//
//$zim = new Zimbra("someone");
//$zim->debug=true;
//$ret = $zim->connect();
//print count($ret['folder']);
////Get Appointment Folder
//$zim->parseRecursiveAptFolder($ret);
//    Var_Dump::display($appointmentFolderIdappointmentFolderId);
//    Var_Dump::display($ret);
//    Var_Dump::display($ret['folder_attribute_id']);
//    Var_Dump::display($ret['folder_attribute_name']);
//
//if ($ret)
//{
//    foreach($appointmentFolderIdappointmentFolderId as $key=>$val)
//    {
//        //print "toto".$val."<BR>";
////        $arr = $zim->getAptBabel("in:".$appointmentFolderName[$key]);
//
//        print "<HR> Debut de Folder #".$val ."(".$appointmentFolderName[$key].")" ;
//        print "<HR>";
////        Var_Dump::display( $zim->getMessages("in:".$appointmentFolderName[$key]));
////        Var_Dump::display($arr);
//        print "<HR> Fin de Folder #</HR>";
//    }
//} else {
//    print $zim->error;
//    print "not connected";
//}
////
////List Wiki
//
//print '<HR>wiki';
//print '<HR>';
//Var_Dump::display($wikiFolderId);
//foreach ($wikiFolderId as $key=>$val)
//{
//    Var_Dump::display($zim->getListWikiFolder($val));
//}
//Var_Dump::display($zim->appointmentFolderId);
//print '<HR>insert';
//$aptArr= array("0" => array( "start"    => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>12 , "min" => 30 ),
//                              "end"      => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>14 , "min" => 30 ),
//                              "fb"       => "B",
//                              "transp"   => "O",
//                              "status"   => "TENT",
//                              "allDay"   => "0",
//                              "name"     => "Test Zimbra 1",
//                              "loc"      => "Aix en Provence",
//                              "isOrg"    => "1",
//                              "url"      => "http://10.91.130.1/test.php",
//                              "noBlob"   => "0",
//                              "l"        => $zim->appointmentFolderId[2],
//                              "desc"     => "test",
//                              "descHtml" => "test"
//                 ),
//                "1" => array( "start"    => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>18 , "min" => 30 ),
//                              "end"      => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>20 , "min" => 30 ),
//                              "fb"       => "B",
//                              "transp"   => "O",
//                              "status"   => "TENT",
//                              "allDay"   => "0",
//                              "name"     => "Test Zimbra 2 HTML",
//                              "loc"      => "Aix en Provence",
//                              "isOrg"    => "1",
//                              "url"      => "http://10.91.130.1/test.php",
//                              "noBlob"   => "0",
//                              "l"        => $zim->appointmentFolderId[2],
//                              "desc"     => "test",
//                              "descHtml" => "<H2>test</H2>"
//                 ),
//                "2" => array( "start"    => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>10 , "min" => 30 ),
//                              "end"      => array( "year"=> 2009 , "month" => 2 , "day" => 26, "hour"=>12 , "min" => 45 ),
//                              "fb"       => "B",
//                              "transp"   => "O",
//                              "status"   => "TENT",
//                              "allDay"   => "0",
//                              "name"     => "Test Zimbra 3",
//                              "loc"      => "Aix en Provence",
//                              "isOrg"    => "1",
//                              "url"      => "http://10.91.130.1/test.php",
//                              "noBlob"   => "0",
//                              "l"        => $zim->appointmentFolderId[2],
//                              "desc"     => "test",
//                              "descHtml" => "test"
//                 )
//                 );
//Now insert test Element =>
//$where = $zim->appointmentFolderId[1];
//foreach ($aptArr as $key=>$val)
//{
//    if ($key == 2)
//    {
//        $zim->createApptBabel($aptArr[$key]);
//    }
//
//
//}
////Create Folder in Where
//print $where."toto";
//$createArray=array('view' => 'appointment', "name" => "testFolder2Import" , "color" => "1" , "flag" => "" , "where" => $where);
//$newFolder = $zim->BabelCreateFolder($createArray);
//
//
//var_dump($newFolder);
?>
