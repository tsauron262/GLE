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
class BabelCalendar {

    public $langs;
    public $ApptArray = array();

    public function BabelCalendar($langs) {
          $this->langs = $langs;
          $this->langs->load('synopsisGene@Synopsis_Tools');
          $this->langs->load("BabelCal");
    }



    public function pushDateArr($date,$name,$desc,$doliId,$uid,$cat,$allday,$loc="",$isOrg=1,$l='null',$url='')
    {
        if (!is_array($date) &&  preg_match("/([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})[\W]?([0-9]{2})?[\W]?([0-9]{2})?[\W]?([0-9]{2})?/",$date,$arrPreg))//2007-08-31 12:01:01
        {
            array_push($this->ApptArray,
                               array( "start" => array("year"=> $arrPreg[1] , "month" => $arrPreg[2] , "day" => $arrPreg[3], "hour"=>$arrPreg[4] , "min" => $arrPreg[5]),
                                      "end"  => array("year"=> $arrPreg[1] , "month" => $arrPreg[2]  , "day" => $arrPreg[3], "hour"=>$arrPreg[4] , "min" => $arrPreg[5]),
                                      "transp"   => "O",
                                      "fb"       => "B",
                                      "status"   => "TENT",
                                      "allDay"   => $allday,
                                      "isOrg"    => $isOrg,
                                      "noBlob"   => "0",
                                      "l"        => $l,
                                      "name"     => $name,
                                      "loc"      => $loc,
                                      "descHtml" => $desc,
                                      "desc" =>  strip_tags($desc),
                                      "doliId"   => $doliId,
                                      "cat"      => $cat,
                                      'uid'      => $uid,
                                      'url'      => $url,
                                     )
                                 );

            return($this->ApptArray);
        } else if (is_array($date))//2007-08-31 12:01:01
        {
            $dateStart=$date['debut'];
            $parseOk =false;
            $arrPreg=array();
            if (  preg_match("/([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})[\W]?([0-9]{2})?[\W]?([0-9]{2})?[\W]?([0-9]{2})?/",$dateStart,$arrPreg))
            {
                $parseOk = true;
            }
            $dateEnd=$date['fin'];
            $arrPreg1=array();
            if ( $parseOk && preg_match("/([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})[\W]?([0-9]{2})?[\W]?([0-9]{2})?[\W]?([0-9]{2})?/",$dateEnd,$arrPreg1))
            {
                $parseOk = true;
            } else {
                $parseOk = false;
                print "Date End Malformated";
            }
            if ($parseOk)
            {
                array_push($this->ApptArray,
                                   array( "start" => array("year"=> $arrPreg[1] , "month" => $arrPreg[2] , "day" => $arrPreg[3], "hour"=>$arrPreg[4] , "min" => $arrPreg[5]),
                                          "end"  => array("year"=> $arrPreg1[1] , "month" => $arrPreg1[2]  , "day" => $arrPreg1[3], "hour"=>$arrPreg1[4] , "min" => $arrPreg1[5]),
                                          "transp"   => "O",
                                          "fb"       => "B",
                                          "status"   => "TENT",
                                          "allDay"   => $allday,
                                          "isOrg"    => $isOrg,
                                          "noBlob"   => "0",
                                          "l"        => $l,
                                          "name"     => $name,
                                          "loc"      => $loc,
                                          "descHtml" => $desc,
                                          "desc" =>  strip_tags($desc),
                                          "doliId"   => $doliId,
                                          "cat"      => $cat,
                                          'uid'      => $uid,
                                          'url'      => $url,
                                         )
                                     );
//                var_dump($this->ApptArray);


            }

            return($this->ApptArray);
        } else {
            return($this->ApptArray);
        }
    }

    public function displayPopulateJs($arrIn='')
    {
        $arrRes=array();
        if (!is_array($arrIn))
        {
            $arrRes = $this->ApptArray;
        } else {
            $arrRes = $arrIn;
        }
        $i=1;
        print "<script type='text/javascript'>\n";
        print "function populate()\n";
        print "{\n";

        $first = "";
        $last = "";
        $last .= '    var entries = {'."\n";

        foreach($arrRes as $key=>$val)
        {
            $id = $val['cat'].'' .$val['doliId'].'' .$val['uid'] ;
            $id = preg_replace('/[\W]/','__',$id);
            $first .= 'var d'.$id.'s = new Date();';

            $startMin = $val['start']['min'];
            if ("x".$startMin == 'x')
            {
                $startMin = 0;
            }
            $startHour = $val['start']['hour'];
            if ("x".$startHour == "x")
            {
                $startHour = 0;
            }
            $first .= 'd'.$id.'s.setHours('.intval($startHour).','.intval($startMin).',0);'."\n";
            $first .= 'd'.$id.'s.setFullYear('.$val['start']['year'].');'."\n";
            $first .= 'd'.$id.'s.setMonth('.intval($val['start']['month'] - 1) .');'."\n";//Start at 0
            $first .= 'd'.$id.'s.setDate('.intval($val['start']['day']).');'."\n";

            $first .= 'var d'.$id.'e = new Date();'."\n";
            $endMin = $val['end']['min'];
            if ("x".$endMin == 'x')
            {
                $endMin = 0;
            }
            $endHour = $val['end']['hour'];
            if ("x".$endHour == "x")
            {
                $endHour = 0;
            }
            $first .= 'd'.$id.'e.setHours('.intval($endHour).','.intval($endMin).',0);'."\n";
            $first .= 'd'.$id.'e.setFullYear('.$val['end']['year'].');'."\n";
            $first .= 'd'.$id.'e.setMonth('.intval($val['end']['month'] - 1) .');'."\n";//Start at 0
            $first .= 'd'.$id.'e.setDate('.intval($val['end']['day']).');'."\n";
            $allDay='';
            if ($val['allDay'] == 1) {
                $allDay="true";
            } else {
                $allDay="false";
            }

            $last .= '        "id'.$i.'": {'."\n";
            $last .= '            starttime: dojo.date.stamp.toISOString(d'.$id.'s),'."\n";
            $last .= '            endtime: dojo.date.stamp.toISOString(d'.$id.'e),'."\n";
            $last .= '            allday: '.$allDay.','."\n";
            $last .= '            repeated: false,'."\n";
            $last .= '            title: "'.$val['name'].'",'."\n";
            $last .= '            url: "'.$val['url'].'",'."\n";
            $last .= '            body: "'.preg_replace('/\n|\r/',"", $val['descHtml']).' ",'."\n";
            $last .= '            attributes: {'."\n";
            $last .= '                Location: "'.$val['loc'].'",'."\n";
            $last .= '                Chair: "Jean-Marc LE FEVRE"'."\n";
            $last .= '            },'."\n";
            $last .= '            type: ["meeting","appointment"]'."\n";
            $last .= '        },';//close ID
            $i++;

        }
        if (count($arrRes) > 0)
        {
            print $first . $last;
            print "\n".'    };';

            print '    oCalendar.setCalendarEntries(entries); '."\n";
            print "\n";

        } else {
            print "return(true);";
        }
    print '}'."\n";
    print "</script>";
    }


    public function displayFilterPart($arr)
    {
        foreach($arr as $key=>$val)
        {
            $name=$val["name"];
            print "             <thead>
                                  <tr class='impair' ><td rowspan=1  colspan=2 style='padding-left: 1px; padding-right: 10px; ' >
                                    <span onClick='hideTab(\"tr".$name."\")'>
                                      <img ALIGN='ABSMIDDLE' id='tr".$name."Img' name='bas' src='img/bas.gif'/>
                                        &nbsp;".$this->langs->trans($name)."&nbsp;
                                    </span>
                                  </td>\n";
            $catChecked = false;
            foreach($val['data'] as $key1=>$val1)
            {
                $checked = false; if ($val1['checked']) { $checked="checked='checked'";}
                if ($val1['idx'])
                {
                    $checked = false;if ($val1["checked"] && $catChecked) { $checked="checked='checked'";}
                    print "             <tr class='pair' >
                                          <td colspan='2' style='width: 130px;'>".$this->langs->Trans($val1["trans"])."</td>
                                          <td  style='width: 16px;'  align=right><input onChange='checkboxFirst();' type='checkbox' $checked name='".$val1["idx"]."'/>\n";


                } else {
                    $catChecked = true;
                    print "
                                          <td colspan=2 style='width: 16px;' align=right><input $checked onChange='checkboxAnimeMain(this);' type='checkbox' $checked name='show".$name."'/>
                                        </tr>\n";

                    print "           </thead><tbody id='tr".$name."'>";
                    print "             <tr class='pair' >
                                          <td style='padding-left:12pt;' rowspan='".count($val["data"])."'>&nbsp;
                                          </td>
                                        </tr>\n";
                }
                print " \n";

            }

        }
    }


}
?>