<?php
/*
 * GLE by Synopsis et DRSI
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
class Zimbra {
    public $zimbraLogin = "";
    public $zimbraPass = "";
    public $db;
    function Zimbra($db) {
        $this->db = $db;
    }
    function fetch_user($user_id)
    {
        $requete = "SELECT ZimbraLogin, ZimbraPass " .
            "     FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_li_User " .
            "    WHERE User_refid = ".$user_id."";
        $resql = $this->db->query($requete);
        if ($resql)
        {
            $res = $this->db->fetch_object($resql);
            $this->zimbraLogin= $res->ZimbraLogin;
            $this->zimbraPass = $res->ZimbraPass;
        }
    }

    function ParseBriefcase($user_id)
    {
        $requete = "SELECT * " .
                "     FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_Briefcase_User " .
                "    WHERE user_refid = ".$user_id ;
        $resql = $this->db->query($requete);
        $retArr = array();
        if ($resql)
        {
            $i=0;
            while ($res = $this->db->fetch_object($resql))
            {
                $retArr[$i]=$res->BriefcaseName;
                $i++;
            }
        }
        return ($retArr);

    }

    function RefreshBriefcase($user_id)
    {
        $dav = new HTTP_WebDAV_Client_Stream;
        $url = $GLOBALS['zimbraDavProto'] . "://".$GLOBALS['zimbraHost'];
        #$dav->url = "webdavs://zimbra.synopsis-erp.com";
        $dav->url = $url;
        $dav->user=$this->zimbraLogin;
        $dav->pass=$this->zimbraPass;
        $opened_path='';
        $dav->stream_open($dav->url."/dav/".$this->zimbraLogin."/Briefcase/","r",array(),&$opened_path);
    #            $dav->url = "webdavs://zimbra.synopsis-erp.com";
        $dav->url = $url;
        $arrDir = $dav->dir_opendir($dav->url."/dav/".$this->zimbraLogin."/Briefcase/",array());
        $dirFileArr=array();
        $dirFileArr = $dav->dirfiles;
        $arrRes = array();
        foreach ($dirFileArr as $key=>$val)
        {
            if (preg_match("/^\./",$val)) { continue; } // fichiers cachés
            $dav->url = $url;
        #            $dav->url = "webdavs://zimbra.synopsis-erp.com";
            $arrDir = $dav->dir_opendir($dav->url."/dav/".$this->zimbraLogin."/Briefcase/".$val,array());
            $cnt = count($dav->dirfiles);
            $arrRes[$key]['name']=$val;
            if ($cnt > 0) { $arrRes[$key]['isDir']=true; } else { $arrRes[$key]['isDir']=false;}
        }
        $dav->stream_close();
        if (count($arrRes) > 0)
        {
            $requeteDel = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_Briefcase_User " .
                    "            WHERE user_refid =".$user_id;
            $this->db->query($requeteDel);
            foreach ($arrRes as $key => $val)
            {
                if ($val['isDir'])
                {
                    $requeteAdd = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_Zimbra_Briefcase_User " .
                            "                  (user_refid,BriefcaseName) " .
                            "           VALUES (".$user_id.",'".$val['name']."')";
                    $this->db->query($requeteAdd);
                }
            }
        }
    }
   private $remArr = array();
   public $dav=false;
   function RecursiveRefreshBriefcase($user_id)
    {
        //$this->$
        $dav = new HTTP_WebDAV_Client_Stream;
        $this->dav=$dav;
        $url = $GLOBALS['zimbraDavProto'] . "://".$GLOBALS['zimbraHost'];
        #$dav->url = "webdavs://zimbra.synopsis-erp.com";
//        $this->dav->url = $url;
//        $this->dav->user=$this->zimbraLogin;
//        $this->dav->pass=$this->zimbraPass;
        $opened_path='';
//        $this->dav->stream_open($this->dav->url."/dav/".$this->zimbraLogin."/Briefcase/","r",array(),&$opened_path);
    #            $dav->url = "webdavs://zimbra.synopsis-erp.com";
//        $this->dav->url = $url;

//        $arrRes = array();
        $this->parseBriefCaseFolder($url);
        $this->dav->stream_close();
        $this->sqlStoreBriefCase($user_id);
    }
    function parseBriefCaseFolder($url)
    {
        $dav=$this->dav;
        $dav->url = $url;
        $dav->user=$this->zimbraLogin;
        $dav->pass=$this->zimbraPass;
        $dav->stream_open($dav->url."/dav/".$this->zimbraLogin."/Briefcase/","r",array(),&$opened_path);
//        var_dump($dav);
//        print('<br>');
        $dav->url = $url."/dav/".$this->zimbraLogin."/Briefcase/";
        //print ("dav URL l119 ".$dav->url.'<br>');
        $arrDir = $dav->dir_opendir($dav->url,array());
        $dirFileArr=array();
        $dirFileArr = $dav->dirfiles;
        $this->parseContent($dirFileArr,$url."/dav/".$this->zimbraLogin."/Briefcase/","/");

    }
    public $arrContent=array();
    public $depth = 0;
    public $maxdepth = 10;
    function parseContent($dirFileArr,$url,$lastOpen)
    {
        $dav=$this->dav;
        $this->depth++;
        foreach ($dirFileArr as $key=>$val)
        {
            if (preg_match("/^\./",$val)) { continue; } // fichiers cachés
            $dav->url = $url.$val;
        #            $dav->url = "webdavs://zimbra.synopsis-erp.com";
            $arrDir = $dav->dir_opendir($url.$val,array());
            $cnt = count($this->dav->dirfiles);
            $isDir = false;
            if ($cnt > 0) { $isDir=true;}
            array_push($this->arrContent,array("name" => $val, "isDir" => $isDir,"Depth" => $this->Depth, "lastOpen" => $lastOpen));
            if ($isDir && $this->depth < $this->maxdepth)
            {
                $dav->url = $url;
                $dav->user=$this->zimbraLogin;
                $dav->pass=$this->zimbraPass;

                $arrDir1 = $dav->dir_opendir($url."".$val."/",array());
                $dirFileArr1=array();
                $dirFileArr1 = $dav->dirfiles;
                $this->parseContent($dirFileArr1,$url."".$val."/",$lastOpen."/".$val."");

            }
        }

    }
    function sqlStoreBriefCase($user_id)
    {
        if (count($this->arrContent) > 0)
        {
            $requeteDel = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_Zimbra_Briefcase_User " .
                    "            WHERE user_refid =".$user_id;
            $this->db->query($requeteDel);
            foreach ($this->arrContent as $key => $val)
            {
                if ($val['isDir'])
                {
                    $fullName = $val['lastOpen']."/".$val['name'];
                    $fullName = urldecode($fullName); // traduit en texte formaté
                    $fullName = preg_replace("/\/\//","/",$fullName); // vire les //
                    $requeteAdd = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_Zimbra_Briefcase_User " .
                            "                  (user_refid,BriefcaseName) " .
                            "           VALUES (".$user_id.",'".$fullName."')";
                    $this->db->query($requeteAdd);
                }
            }
        }
    }
    function pushToZimbraBriefcase($url,$file_content,$user_id="")
    {
//        $url = $GLOBALS['zimbraDavProto']."://".$GLOBALS['zimbraHost']."/dav/".$GLOBALS['zimbraUser']."/Briefcase/";
        //$url = "webdav://10.91.130.61/dav/eos/Briefcase/testdav/".basename($rapport_name);
        //$url .= "testdav/".basename($file_name);
        //echo $url;
        //echo "<BR>";
        require_once (DOL_DOCUMENT_ROOT . '/Synopsis_Zimbra/WebDAV/Client.php');
        $dav = new HTTP_WebDAV_Client_Stream;
        $dav->url = $url;
        if ("x".$user_id !="x" || $this->zimbraLogin ."x" == "x")
        {
            if ("x".$user_id =="x") { print "no user id provided !! "; return false; }
            else {
                $this->fetch_user($user_id);
            }
        }
        $dav->user=$this->zimbraLogin;
        $dav->pass=$this->zimbraPass;
        $opened_path='';
        $ret = $dav->stream_open($url,"w",array(),&$opened_path);
        $dav->stream_upload_file($file_content);
        $dav->url = $url;
        $dav->stream_eof();
        $dav->url = $url;
        $dav->stream_close();
    }
}

?>