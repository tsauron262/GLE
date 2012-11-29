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
require_once('../../main.inc.php');

        header("Content-Type: text/xml");
        $xml = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
        $xml .= '<ajax-response><response>';
        $str = $_REQUEST['str'];
        $optionArr = array();
        for($i=0;$i<strlen($str);$i++)
        {
            array_push($optionArr,array('lastname' => $str[$i]."*", 'condition' => "OR") );
        }
        require_once('../ZimbraSoap.class.php');
        $zimuser="";
        if ($conf->global->ZIMBRA_ZIMBRA_USE_LDAP=="true")
        {
            $zimuser=$user->login;
        } else {
            $user->getZimbraCred($user->id);
            $zimuser=$user->ZimbraLogin;
        }
        $zim = new Zimbra($zimuser);
        $zim->debug=false;
        $ret = $zim->connect();

//        $folder = $zim->BabelSearchRequest($optionArr);
        $offset = 0;$numOfElSoap=100;
         $more = 1;
         $arrTest = array();
         $iter = 0;
         while ($more == 1)
         {
            $folder = $zim->BabelSearchRequest($optionArr, "anywhere",'contact',$numOfElSoap,'nameAsc',$offset);
            array_push($arrTest,$folder);
            $offset += $numOfElSoap;
            $more = $return['more'];
            $iter++;
            $contactZim = array();
            foreach($folder['cn'] as $key => $contactArr)
            {
                $contactZim[$contactArr["id"]] = array();
                foreach($contactArr["a_attribute_n"] as $key1 => $idField)
                {
                    $contactZim[$contactArr["id"]][$idField] = $contactArr["a"][$key1] ;
                }
                $f1 = $zim->getFolderCont($contactArr["l"]);
                $contactZim[$contactArr["id"]]['parentFolderName'] = $f1['folder_attribute_name'][0];
            }
            foreach($contactZim as $key => $val)
            {
                $idPeople = false;
                $namePeople = "";
                $emailPeople = "";

                $idPeople = $key;
                if ($val["fullName"])
                {
                    $namePeople = $val["fullName"];
                } else {
                    if ($val['lastName'])
                    {
                        $namePeople = $val['lastName']. " ";
                    }
                    if ($val['firstName'])
                    {
                        $namePeople .= $val['firstName']. " ";
                    }
                }
                if ($val['email'] . "x" == "x")
                {
                    $emailPeople = "none";
                } else {
                    $emailPeople = $val['email'];
                }
                $extraStr = "\t<contactDet  id='".$idPeople."'  >\n ";
                foreach($val as $key1=>$val1)
                {
                    if ($key1=='image'){
                        continue;
                    }

                    $extraStr .= "\t\t<".$key1."><![CDATA[".$val1."]]></".$key1.">\n";
                }
                $extraStr .= "</contactDet>\n";
                //$xml .= "\t<contact parentFolderName='".$val['parentFolderName']."' id='".$idPeople."' name='".$namePeople."' email='".$emailPeople."' >\n";
                $xml .= "\t<contact  id='".$idPeople."' >\n";
                $xml .= "\t\t<parentFolderName><![CDATA[".$val['parentFolderName']."]]></parentFolderName>\n";
                $xml .= "\t\t<name><![CDATA[".$namePeople."]]></name>\n";
                $xml .= "\t\t<email><![CDATA[".$emailPeople."]]></email>\n";
                $xml .= $extraStr;
                $xml .= "\t</contact>\n";            }
        }

        $xml .='</response></ajax-response>';
        echo $xml;



?>