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

 /* debut de action */
//print $_GET['action'];
if ($_REQUEST['action']=="downloadVCS")
{

    $id = 20090228;
//var_dump($arrRes);

    $Filename = "dolibarr-" . $id . ".vcs";
    header("Content-Type: text/x-vCalendar");
    header("Content-Disposition: inline; filename=$Filename");
            $tz = "+01";

        print "BEGIN:VCALENDAR\n";
        print "VERSION:1.0\n";
        print "PRODID:Synopsis et DRSI Calendar Exporter\n";
        print "TZ: ".$tz."\n";

    foreach($arrRes as $key=>$val)
    {

            print "BEGIN:VEVENT\n";
        if ($val['allDay']==1 && $val['name'])
        {
            $sday = $val["start"]['day'];
            if (strlen($val["start"]['day']) == 1)
            {
                $sday = "0".  $val["start"]['day'];
            }

            $smonth = $val['start']['month'];
            if (strlen($val["start"]['month'] == 1))
            {
                $smonth = "0".    $val["start"]['month'];
            }

            $eday = $val["end"]['day'];
            if (strlen($val["end"]['day']) == 1)
            {
                $eday = "0".    $val["end"]['day'];
            }
            $emonth = $val['end']['month'] - 1;
            $emonth = preg_replace('[\WA-Za-z]',"",$emonth);
            if (strlen($emonth) == 1 )
            {
                $emonth = "0". $val["end"]['month'];
            }

            //print "<BR>".$emonth ."<BR>";
            $vCalStart = $val["start"]['year'].$smonth.$sday;
            $vCalEnd = $val["end"]['year'].$emonth.$eday;
            $DescDum = utf8_decode( $val['desc']);
            $Summary = utf8_decode( $val['name']);
            if ("x".$val['desc'] == "x")
            {
                $DescDum = $Summary;
            }
            $tz = "+01";
            print "SUMMARY;CHARSET=ISO-8859-15:".$Summary . "\n";
            print "DESCRIPTION;CHARSET=ISO-8859-15: ".html_entity_decode($DescDum) . "\n";
            print "DTSTART:". $vCalStart . "\n";
        } else if ($val['name']){

            $sday = $val["start"]['day'];
            if (strlen($val["start"]['day']) == 1)
            {
                $sday = "0".  $val["start"]['day'];
            }

            $smonth = $val['start']['month'];
            if (strlen($val["start"]['month']) == 1)
            {
                $smonth = "0".    $val["start"]['month'];
            }

            $eday = $val["end"]['day'];
            if (strlen($val["end"]['day']) == 1)
            {
                $eday = "0".    $val["end"]['day'];
            }

            $emonth = $val['end']['month'];
            if (strlen($val["end"]['month']) <= 1)
            {
                $emonth = "0". $val["end"]['month'];
            }
            $shour = $val['start']['hour'];
            if (strlen($val['start']['hour']) == 1)
            {
                $shour = "0".$shour;
            }
            $ehour = $val['end']['hour'];
            if (strlen($val['end']['hour']) == 1)
            {
                $ehour = "0".$ehour;
            }
            $smin = $val['start']['min'];
            if (strlen($val['start']['min']) == 1)
            {
                $smin = "0".$smin;
            }
            $emin = $val['end']['min'];
            if (strlen($val['end']['min']) == 1)
            {
                $emin = "0".$emin;
            }


            $vCalStart = $val["start"]['year'].$smonth.$sday."T".$shour.$smin."00Z";
            $vCalEnd = $val["end"]['year'].$emonth.$eday."T".$ehour.$emin."00Z";
            $DescDum = utf8_decode( $val['desc']);
            $Summary = utf8_decode( $val['name'] );
            print "SUMMARY:".$Summary . "\n";
            print "DESCRIPTION;ENCODING=QUOTED-PRINTABLE: ".$DescDum . "\n";
            print "DTSTART:". $vCalStart . "\n";
            print "DTEND:". $vCalEnd . "\n";

        }
            print "END:VEVENT\n";

    }
        print "END:VCALENDAR\n";

exit(0);

} else if ($_REQUEST['action']=="downloadICS")
{
    $id = 20090228;
//var_dump($arrRes);

$Filename = "dolibarr-" . $id . ".ics";
    header("Content-Type: text/calendar");
    header("Content-Disposition: inline; filename=$Filename");
            $tz = "+01";

        print "BEGIN:VCALENDAR\n";
        print "VERSION:2.0\n";
        print "PRODID:Synopsis et DRSI Calendar Exporter\n";
        print "TZ: ".$tz."\n";

    foreach($arrRes as $key=>$val)
    {
            print "BEGIN:VEVENT\n";
        if ($val['allDay']==1 && $val['name'])
        {
            $sday = $val["start"]['day'];
            if (strlen($val["start"]['day']) == 1)
            {
                $sday = "0".  $val["start"]['day'];
            }

            $smonth = $val['start']['month'];
            if (strlen($val["start"]['month'] == 1))
            {
                $smonth = "0".    $val["start"]['month'];
            }

            $eday = $val["end"]['day'];
            if (strlen($val["end"]['day']) == 1)
            {
                $eday = "0".    $val["end"]['day'];
            }
            $emonth = $val['end']['month'] ;
            $emonth = preg_replace('[\WA-Za-z]',"",$emonth);
            if (strlen($emonth) == 1 )
            {
                $emonth = "0". $val["end"]['month'];
            }

            //print "<BR>".$emonth ."<BR>";
            $vCalStart = $val["start"]['year'].$smonth.$sday;
            $vCalEnd = $val["end"]['year'].$emonth.$eday;
            $DescDum = utf8_decode( $val['desc']);
            $Summary = utf8_decode( $val['name']);
            if ("x".$val['desc'] == "x")
            {
                $DescDum = $Summary;
            }
            $tz = "+01";
            print "SUMMARY;CHARSET=ISO-8859-15:".$Summary . "\n";
            print "DESCRIPTION;CHARSET=ISO-8859-15: ".html_entity_decode($DescDum) . "\n";
            print "LOCATION:" . $val["loc"]."\n";
            if (strlen($val['url']) > 0)
            {
                print "URL;VALUE=URI:".$url."\n";
            }
            print "X-FUNAMBOL-ALLDAY: 1\n";
            print "DTSTART:". $vCalStart . "\n";
            print "DTEND:". $vCalEnd . "\n";
        } else if ($val['name']){

            $sday = $val["start"]['day'];
            if (strlen($val["start"]['day']) == 1)
            {
                $sday = "0".  $val["start"]['day'];
            }

            $smonth = $val['start']['month'];
            if (strlen($val["start"]['month']) == 1)
            {
                $smonth = "0".    $val["start"]['month'];
            }

            $eday = $val["end"]['day'];
            if (strlen($val["end"]['day']) == 1)
            {
                $eday = "0".    $val["end"]['day'];
            }

            $emonth = $val['end']['month'];
            if (strlen($val["end"]['month']) <= 1)
            {
                $emonth = "0". $val["end"]['month'];
            }
            $shour = $val['start']['hour'];
            if (strlen($val['start']['hour']) == 1)
            {
                $shour = "0".$shour;
            }
            $ehour = $val['end']['hour'];
            if (strlen($val['end']['hour']) == 1)
            {
                $ehour = "0".$ehour;
            }
            $smin = $val['start']['min'];
            if (strlen($val['start']['min']) == 1)
            {
                $smin = "0".$smin;
            }
            $emin = $val['end']['min'];
            if (strlen($val['end']['min']) == 1)
            {
                $emin = "0".$emin;
            }


            $vCalStart = $val["start"]['year'].$smonth.$sday."T".$shour.$smin."00Z";
            $vCalEnd = $val["end"]['year'].$emonth.$eday."T".$ehour.$emin."00Z";
            $DescDum = utf8_decode( $val['desc']);
            $Summary = utf8_decode( $val['name'] );
            if (strlen($val['url']) > 0)
            {
                print "URL;VALUE=URI:".$url."\n";
            }
            print "SUMMARY:".$Summary . "\n";
            print "DESCRIPTION;ENCODING=QUOTED-PRINTABLE: ".$DescDum . "\n";
            print "DTSTART:". $vCalStart . "\n";
            print "LOCATION:" . $val["loc"]."\n";
            print "DTEND:". $vCalEnd . "\n";

        }
            print "END:VEVENT\n";

    }
        print "END:VCALENDAR\n";

exit(0);

}

if ($_REQUEST['action'] == "sendToZimbra")
{
    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Zimbra/ZimbraSoap.class.php');
    $zim = new Zimbra("eos");
    $zim->langs=$langs;
    $ret = $zim->connect();
//    $zim->debug=true;
    $folder = "";
    if ($_REQUEST['repZimbraId'] . "x" != "x")
    {
        $where = $_REQUEST['repZimbraId'];
    } else {
        print "err : no folder id";
        exit(0);
    }
    $color =1;
    if ($_REQUEST['zimbraColorFold'].'x' != "x")
    {
        $color = $_REQUEST['zimbraColorFold'];
    }
    $tagZim = false;
    if ($_REQUEST['zimbraTag']."x" != "x" && $_REQUEST['zimbraTag'] > 0)
    {
        $tagZim=$_REQUEST['zimbraTag'];

    }
    if ($_REQUEST['zimbraCreateFold'] . "x" != "x")
    {

        $createArray=array('view' => 'appointment',
                           "name" => $_REQUEST['zimbraCreateFold'] ,
                           "color" => $color ,
                           "flag" => "" ,
                           "where" => $where);
        $folder = $zim->BabelCreateFolder($createArray);
        //Faut changer le where
        $where = $folder["id"];
        if ("x".$where == "x")
        {
            print " createFolder / setFolder failed";
            exit(0);
        }
    } else {
        $folder = $zim->getFolderAppt($where);
    }
    //On a la liste des events
    //On la recompose = on ajoute le repertoire destinataire
    foreach($arrRes as $key=>$val)
    {
        $arrRes[$key]["l"]=$where;
        $arrRes[$key]['t']=$tagZim;
    }
    //On envoie dans zimbra
    foreach($arrRes as $key=>$val)
    {
        $zim->createApptBabel($val);
    }
}

/* Fin de Action */

?>
