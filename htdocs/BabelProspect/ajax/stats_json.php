<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 8-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : recapCamp.php
  * GLE-1.1
  */


  //Grid avec la liste et les resultats
  //Subgrid avec les notes d'avancement



  require_once('../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");

$campagneId = $_REQUEST['campagneId'];

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@Synopsis_Tools");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);
// Initialisation de l'objet Societe
$soc = new Societe($db);
$soc->fetch($socid);


$action = $_REQUEST['action'];

switch ($action)
{
    case "actComPie";
    {
        // activite des commerciaux
            //Pie chart :> nbr de client pris en charge par chaque commerciaux % a la totalite
        $requete = "SELECT count(societe_refid) as nbSoc,
                           ifnull(user_id,0) as user_id
                      FROM Babel_campagne_societe
                     WHERE campagne_refid = $campagneId
                  GROUP BY user_id";
        $sql = $db->query($requete);
        $arrVal = array();
        while ($res = $db->fetch_object($sql))
        {
            $tmpUser= new User($db);
            $tmpUser->id = $res->user_id;
            $tmpUser->fetch();
            $fullname = $tmpUser->fullname;
            if ($tmpUser->fullname . "x" == "x")
            {
                $fullname = " --- ";
            }
            $arrVal[]=array("value" => round($res->nbSoc), "label" => $fullname);
        }
        $arr=array();
        $arr['elements']=array(array("tip" => "#val# de #total# sociétés<br>#percent#",
                               "colours" => array(  "0x24A12B",  "0x243D8A",
                                                     "0xF43210",
                                                    "0xEB7916", "0x3F0E63", "0xEDEC0A",
                                                    "0x21A56F",  "0x221B6F",
                                                     "0xEB3914",
                                                    "0xEEA60D" , "0x91135E", "0x6AC720"
                                                     ),
                               "alpha" => 0.8,
                               "start_angle" => 135,
                               "no-labels" => false,
                               "animate" => array(0=> array("type"=>"bounce","distance"=>35), 1=> array("type"=>fade)),
                               "values" => $arrVal,
                               "type" => "pie",
                               "border" => "2" ));
        $arr['bg_colour'] ="#FAFCBC";
        $arr['ani--mate']=true;
        $arr['title']=array('text' => "Activité commerciale", "style" => "font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");


    echo json_encode($arr);

    }
    break;
    case "statutSocPie":
    {
        //Pie chart: qte de societe % statut
        $requete = "SELECT count(*) as count,
                       fk_statut
                  FROM Babel_campagne_societe
                 WHERE campagne_refid = $campagneId
              GROUP BY fk_statut ";
        $sql = $db->query($requete);
        $arrVal = array();
        while ($res = $db->fetch_object($sql))
        {
            $statutArr[0]="Impossible";
            $statutArr[1]="En attente";
            $statutArr[2]="En cours";
            $statutArr[3]="Clôturer";
            $statutArr[4]="Repousser";
            $statutArr[5]="Repousser";
            $statutArr[6]="Repousser";
            $arrVal[]=array("value" => round($res->count), "label" => $statutArr[$res->fk_statut]);
        }
        $arr=array();

        $arr['elements']=array(array("tip" => "#val# de #total# sociétés<br>#percent#",
                               "colours" => array(  "0x24A12B",  "0x243D8A",
                                                     "0xF43210",
                                                    "0xEB7916", "0x3F0E63", "0xEDEC0A",
                                                    "0x21A56F",  "0x221B6F",
                                                     "0xEB3914",
                                                    "0xEEA60D" , "0x91135E", "0x6AC720"
                                                     ),
                               "alpha" => 0.8,
                               "start_angle" => 135,
                               "no-labels" => false,
                               "animate" => array(0=> array("type"=>"bounce","distance"=>35), 1=> array("type"=>fade)),
                               "values" => $arrVal,
                               "type" => "pie",
                               "border" => "2" ));
        $arr['bg_colour'] ="#FAFCBC";
        $arr['ani--mate']=true;
        $arr['title']=array('text' => "Etat de la campagne", "style" => "font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");
        echo json_encode($arr);
    }
    break;
    case 'successPie':
    {
       //Pie chart des reussite et des echec % a leur totalites respective
        $requete = "SELECT count(*) as count,
                           closeStatut
                      FROM Babel_campagne_societe
                     WHERE fk_statut = 3
                       AND campagne_refid = $campagneId
                  GROUP BY closeStatut ";
        $sql = $db->query($requete);
        $arrVal = array();
        while ($res = $db->fetch_object($sql))
        {
            $closeStatutArr[0]="Impossible";
            $closeStatutArr[1]="Positif";
            $closeStatutArr[2]="Négatif";
            $closeStatutArr[3]="Impossible";
            $arrVal[]=array("value" => round($res->count), "label" => $closeStatutArr[$res->closeStatut]);
        }
        $arr=array();
        $arr['elements']=array(array("tip" => "#val# de #total# sociétés<br>#percent#",
                               "colours" => array(  "0x24A12B", "0xCB230A" ),
                               "alpha" => 0.8,
                               "start_angle" => 135,
                               "no-labels" => false,
                               "animate" => array(0=> array("type"=>"bounce","distance"=>35), 1=> array("type"=>fade)),
                               "values" => $arrVal,
                               "type" => "pie",
                               "border" => "2" ));
        $arr['bg_colour'] ="#FAFCBC";
        $arr['ani--mate']=true;
        $arr['title']=array('text' => "Succès de la campagne", "style" => "font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");
        echo json_encode($arr);

    }
    break;
    case 'clotureOkKoLine':
    {

        $requete = "SELECT UNIX_TIMESTAMP(date_format(dateDebut,'%Y-%m-%d')) as dateDebut,
                           UNIX_TIMESTAMP(date_format(dateFin,'%Y-%m-%d')) as dateFin
                      FROM Babel_Campagne
                     WHERE id = ".$campagneId;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $startDate = $res->dateDebut;//1246399200
        $endDate = $res->dateFin;//1254261600
        $maxSoc=0;

        $arr = array();
        $arr1 = array();

        //Line chart : nb de cloture positif par jour  +  nb de cloture negatif par jour
        $requete = "SELECT day(date_cloture) as jour,
                           month(date_cloture) as mois,
                           year(date_cloture) as annee,
                           unix_timestamp(date_cloture) as epoch_date,
                           count(*) as count,
                           closeStatut
                      FROM Babel_campagne_societe
                     WHERE fk_statut = 3
                       AND campagne_refid = $campagneId
                  GROUP BY closeStatut,
                           day(date_cloture),
                           month(date_cloture),
                           year(date_cloture) ";

        require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php-ofc-library/open-flash-chart.php');

            function dot($col)
            {
                $default_dot = new dot();
                $default_dot
                    ->size(3)
                    ->halo_size(1)
                    ->colour($col)
                    ->tooltip('X: #x_label#<br>Y: #val#<br>#date:Y-m-d at H:i#');
                return $default_dot;
            }

            function green_dot()
            {
                return dot('#3D5C56');
            }

            $data_1 = array();
            $data_2 = array();

            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql))
            {
                if ($res->closeStatut == 2)
                {
                    $arr1[$res->epoch_date]=$res->count;
                } else {
                    $arr[$res->epoch_date]=$res->count;
                }
            }
            for( $i=$startDate; $i<=$endDate; $i+=86400 )
            {
                $tmpCnt = 0;
                $tmpCnt1 = 0;
                foreach($arr as $key=>$val)
                {
                    if ($key >= $i && $key < $i + 86400 )
                    {
                        $tmpCnt +=$val;

                    }
                }
                foreach($arr1 as $key=>$val)
                {
                    if ($key >= $i && $key < $i + 86400 )
                    {
                        $tmpCnt1 +=$val;
                    }
                }
                $data_1[] = new scatter_value($i, $tmpCnt);
                $data_2[] = new scatter_value($i, $tmpCnt1);
                if ($tmpCnt > $maxSoc) $maxSoc = $tmpCnt;
                if ($tmpCnt1 > $maxSoc) $maxSoc = $tmpCnt1;
            }
            $def = new hollow_dot();
            $def->size(1)->halo_size(1)->tooltip('Négatif: #date:d M y#<br>Value: #val#');

            $def1 = new hollow_dot();
            $def1->size(1)->halo_size(1)->tooltip('Positif: #date:d M y#<br>Value: #val#');


            $line = new line( );
            $line->set_colour('#24A12B');
            $line->set_width(1);
            $line->set_values($data_2);
            $line->set_default_dot_style( $def );
            $line->set_key("Négatif",10);
            $line1 = new line(  );
            $line1->set_colour('#CB230A');
            $line1->set_width(1);
            $line1->set_values($data_1);
            $line1->set_default_dot_style( $def1 );
            $line1->set_key("Positif",10);

            $x = new x_axis();
            $x->set_range(
                $startDate,
                $endDate
                );
            $x->set_steps(86400);

            $labels = new x_axis_labels();
            $labels->text('#date:d/m/Y#');
            $labels->set_steps(86400);
            if ($endDate - $startDate > 86400 * 7)
            {
                $labels->visible_steps(7);
            } else {
                $labels->visible_steps(1);
            }
            $labels->rotate(-30);
            $x->set_labels($labels);
            $y = new y_axis();
            $step = 1;
            if ($maxSoc > 10)
            {
                $step = floor($maxSoc / 5);
            }
            $y->set_range( 0, $maxSoc, $step );

            $chart = new open_flash_chart();
            $title = new title( "Résultat par jour" );
            $title->set_style("font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");
            $chart->set_title( $title );
            $chart->add_element( $line );
            $chart->add_element( $line1 );
            $chart->set_x_axis( $x );
            $chart->set_y_axis( $y );

            echo $chart->toPrettyString();

    }
    break;
    case "stCommBarChart":
    {


        $animation_1= isset($_GET['animation_1'])?$_GET['animation_1']:'fade-in';
        $delay_1    = isset($_GET['delay_1'])?$_GET['delay_1']:0.5;
        $cascade_1    = isset($_GET['cascade_1'])?$_GET['cascade_1']:1;


        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."c_stcomm WHERE active = 1";
        $sql = $db->query($requete);
        $arr = array();
        $arrLang = array();
        $arrXLabel=array();
        while ($res = $db->fetch_object($sql))
        {
            $arr[$res->id]=0;
            array_push($arrXLabel,utf8_encode($res->libelle));
        }
//var_dump($arrXLabel);
        //Bar chart : y: taux de chque stcomm en cloture x : le stcomm
        $requete = "SELECT ifnull(count(*),0) as count,
                           ifnull(closeStComm,-1) as closeStComm
                      FROM Babel_campagne_societe
                     WHERE fk_statut = 3
                       AND campagne_refid = $campagneId
                  GROUP BY closeStComm ";

        $sql = $db->query($requete);
        $color = array( '#AA0000', '#AA5500', '#AAAA00' ,'#55CC00',"#00AA00","#00DD00");

        while ($res = $db->fetch_object($sql))
        {
            $arr[$res->closeStComm] = $res->count ;
        }

        require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php5-ofc-library/lib/OFC/OFC_Chart.php');

        $title = new OFC_Elements_Title( "Résultat commercial" );

        $bar_stack = new OFC_Charts_Bar_3d();
        $bar_stack->set_alpha(0.9);

        $maxSoc=0;
        for ($i=-1; $i<count($arr) -1;$i++)
        {
            $atLeasetOneFound = false;
            $arrRes = array();
            foreach ($arr as $key=>$val)
            {
                $totDay = 0;
                $atLeasetOneFound = true;
                if ($val . "x" != "x" && $i == $key)
                {
                    $totDay += $val;
                    $objval = new OFC_Charts_Bar_Value( $val) ;
                    $objval->set_colour($color[$i+1]);
                    $objval->set_tooltip( '#val# société(s)  '.$arrXLabel[$i+1] );
                    $bar_stack->append_value($objval);
                    if ($totDay > $maxSoc) $maxSoc = floor($totDay);
                }
            }
        }
        $keyArr = array();

        $maxSoc++;
        while (! preg_match('/[05]$/',$maxSoc))
        {
            $maxSoc++;
        }

        $y = new OFC_Elements_Axis_Y();
        $y->set_range( 0, $maxSoc ,  5 );

        $x = new OFC_Elements_Axis_X();
        $x->set_steps( 1 );
        $x->set_3d( 1 );

        $labels = new OFC_Elements_Axis_X_Label_Set("","","","");

        $labels->set_labels($arrXLabel);

        $labels->visible_steps(1);

        $labels->set_rotate(-30);
        $labels->set_size(10);

        $x->set_labels($labels);

        $chart = new OFC_Chart();
        $title->set_style("font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");
        $chart->set_title( $title );
        $bar_stack->set_on_show(new OFC_Charts_Bar_On_Show($animation_1, $cascade_1, $delay_1));
        $chart->add_element( $bar_stack );
        $chart->set_x_axis( $x );
        $chart->add_y_axis( $y );

        echo $chart->toPrettyString();

    }
    break;
    case "clotureOkKoPerCom":
    {

        //Bar chart : nb de cloture positif par jour par commercial en cote a cote  +  nb de cloture negatif par jour par commercial en cote a cote
        require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php-ofc-library/open-flash-chart.php');


        $colorTpl =  array( '#0000FF', '#00FF00', '#FF0000' ,'#00FFFF',"#FFFF00","#FFFFF0");
        $colorTpl1 = array( '#000099', '#009900', '#990000' ,'#009999',"#999900","#999990");
//$colorTpl = array();
        //100 colors :
//        for ($i=0;$i<100;$i++)
//        {
//            $red = dechex(rand(0,255));
//            $green = dechex(rand(0,255));
//            $blue = dechex(rand(0,255));
//            array_push($colorTpl,'#'.$red.$green,$blue);
//        }

        $requete = "SELECT UNIX_TIMESTAMP(date_format(dateDebut,'%Y-%m-%d')) as dateDebut,
                           UNIX_TIMESTAMP(date_format(dateFin,'%Y-%m-%d')) as dateFin
                      FROM Babel_Campagne
                     WHERE id = ".$campagneId;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $startDate = $res->dateDebut ;
        $endDate = $res->dateFin ;
        $requete = "SELECT day(date_cloture) as jour,
                           month(date_cloture) as mois,
                           year(date_cloture) as annee,
                           unix_timestamp(date_cloture) as epoch_date,
                           ifnull(count(*),0) as count,
                           user_id,
                           closeStatut,
                           fk_statut
                      FROM Babel_campagne_societe
                     WHERE fk_statut = 3
                       AND campagne_refid = $campagneId
                  GROUP BY closeStatut,
                           user_id,
                           day(date_cloture),
                           month(date_cloture),
                           year(date_cloture) ";
        $sql = $db->query($requete);

        $i=0;
        $arr=array();
        $arrRes=array();
        $remColor = array();
        $bar_stack=array();
        $arrRes=array();
        $remColor1=array();
        $remColor2=array();
        $arrFullname = array();
        $maxPerDay=0;

        $animation_1= isset($_GET['animation_1'])?$_GET['animation_1']:'fade-in';
        $delay_1    = isset($_GET['delay_1'])?$_GET['delay_1']:0.5;
        $cascade_1    = isset($_GET['cascade_1'])?$_GET['cascade_1']:1;


        while ($res = $db->fetch_object($sql))
        {
            $date = strtotime($res->mois."/".$res->jour."/".$res->annee);
            $count = 0;
            if ('x'.$res->count != 'x') $count = $res->count;
            $arr[$res->user_id][$date][$res->closeStatut] = $count ;

            $fuser = new User($db);
            $fuser->id = $res->user_id;
            $fuser->fetch();
            $arrFullname[$res->user_id]=$fuser->fullname;
            if (!in_array($res->user_id,$remColor))
            {
                $color = array();
                array_push($color, new bar_stack_key( $colorTpl[$i], $fuser->fullname, 9 ));
                array_push($remColor,$res->user_id);
                $remColor1[$res->user_id]=$colorTpl[$i];
                $remColor2[$res->user_id]=$colorTpl1[$i];
                $bar_stack[$res->user_id] = new bar_stack();
                $bar_stack[$res->user_id]->set_colours( $colorTpl[$i] );
                $bar_stack[$res->user_id]->set_on_show(array('type:' => $animation_1, 'cascade' => $cascade_1, 'delay' => $delay_1));
                $bar_stack[$res->user_id]->set_keys($color);


                //$bar_stack[$res->user_id]->set_tooltip( '');
                $arrRes[$userid]=array();
                $i++;
            }
        }
        for ($i=$startDate;$i<$endDate; $i+= 86400)
        {
            foreach($bar_stack as $userid => $val)
            {
                $countOK=0;
                $countKO=0;
                foreach($arr[$userid] as $key => $val1)
                {
                    if ($key >= $i && $key < $i+86400)
                    {
                        $atLeastOne = true;
                        $countOK = ($val1[1]."x"=="x"?0:array('val' => $val1[1] * 1,'colour'=> $remColor1[$userid],'tip'=>$arrFullname[$userid].'<br> Le #x_label# : <br> #val# positif(s) sur #total#'));
                        $countKO = ($val1[2]."x"=="x"?0:array('val' => $val1[2] * 1,'colour'=> $remColor2[$userid],'tip'=>$arrFullname[$userid].'<br> Le #x_label# : <br> #val# négatif(s) sur #total#'));
                        if ($maxPerDay < $val1[1] + $val1[2]) $maxPerDay = $val1[1] + $val1[2];
                        break;
                    }
                }
                //print '<hr>'.var_dump($countOK)."<br>".var_dump($countKO)."<HR>";
                $bar_stack[$userid]->append_stack(array($countOK,$countKO));

            }
        }

        while (! preg_match('/[05]$/',$maxPerDay))
        {
            $maxPerDay++;
        }
        $step=1;
        if ($maxPerDay > 100)
        {
            $step = $maxPerDay / 10;
        } else if ($maxPerDay > 50){
            $step = $maxPerDay / 5;
        }

        $y = new y_axis();
        $y->set_range( 0, $maxPerDay, 2 );

        $x = new x_axis();

        $maxSoc=0;
        $arrXLabel=array();
        for ($i=$startDate;$i<$endDate; $i+= 86400)
        {
            $atLeasetOneFound = false;
            $arrRes = array();
            foreach($color as $key=>$val)
            {
                $arrRes[$key]= array();
            }
            array_push($arrXLabel,date("d/m/Y",$i));
        }

        $x->set_labels_from_array( $arrXLabel ,-30,7);

        //$tooltip = new tooltip();
        //$tooltip->set_hover();

        $chart = new open_flash_chart();
        foreach($bar_stack as $key => $val)
        {
            $chart->add_element( $val );
        }
        $title = new title( 'Statut de clôture par commercial' );
        $title->set_style( "{font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;}" );

        $chart->set_title( $title );

        $chart->set_x_axis( $x );
        $chart->add_y_axis( $y );
        //$chart->set_tooltip( $tooltip );

        echo $chart->toPrettyString();

    }
    break;
    case "actComBar":
    {
        //Bar chart :> du nbr de client pris en charge par rapport au temps en cumulant avec des couleurs differentes chaque comemrcial
        //start stop
        $requete = "SELECT UNIX_TIMESTAMP(date_format(dateDebut,'%Y-%m-%d')) as dateDebut,
                           UNIX_TIMESTAMP(date_format(dateFin,'%Y-%m-%d')) as dateFin
                      FROM Babel_Campagne
                     WHERE id = ".$campagneId;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $startDate = $res->dateDebut ;
        $endDate = $res->dateFin ;

        $maxSoc = 0;
        $requete = "SELECT count(societe_refid) AS nbSoc,
                                   user_id AS user_id,
                                   day(date_prisecharge) AS jour,
                                   month(date_prisecharge) AS mois,
                                   year(date_prisecharge) AS annee,
                                   UNIX_TIMESTAMP(date_format(date_prisecharge,'%Y-%m-%d')) as epoch_date
                              FROM Babel_campagne_societe
                             WHERE campagne_refid = $campagneId
                               AND date_prisecharge IS NOT NULL
                               AND user_id IS NOT NULL
                          GROUP BY user_id,
                                   day(date_prisecharge),
                                   month(date_prisecharge),
                                   year(date_prisecharge)";
        $sql = $db->query($requete);
        $arr = array();
        $color=array();
        $colorTpl = array( '#F4D318', '#C0C8CA', '#7DFB6A' ,'#BA7BFA',"#D673F5","#6D48F1");
//$colorTpl = array();
        //100 colors :
        for ($i=0;$i<100;$i++)
        {
            $red = dechex(rand(0,255));
            $green = dechex(rand(0,255));
            $blue = dechex(rand(0,255));
            array_push($colorTpl,'#'.$red.$green,$blue);
        }

        $i=0;
        while ($res = $db->fetch_object($sql))
        {

            $arr[$res->epoch_date][$res->user_id] = $res->nbSoc ;
            $color[$res->user_id] = $colorTpl[$i];
            $i++;
        }

        require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php5-ofc-library/lib/OFC/OFC_Chart.php');

        $title = new OFC_Elements_Title( "Statut de la prise en charge" );
        $title->set_style("font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");


        $bar_stack = new OFC_Charts_Bar_Stack();

        $animation_1= isset($_GET['animation_1'])?$_GET['animation_1']:'fade-in';
        $delay_1    = isset($_GET['delay_1'])?$_GET['delay_1']:0.5;
        $cascade_1    = isset($_GET['cascade_1'])?$_GET['cascade_1']:1;

        $bar_stack->set_on_show(new OFC_Charts_Bar_On_Show($animation_1, $cascade_1, $delay_1));

        $arrXLabel=array();
        $maxSoc=0;
        for ($i=$startDate;$i<$endDate; $i+= 86400)
        {
            $atLeasetOneFound = false;
            $arrRes = array();
            $totDay = 0;
            $userid = "";
            foreach ($arr as $key=>$val)
            {
                if ($key> $i && $key <= $i + 86400 )
                {
                    foreach ($val as $key1=>$val1)
                    {
                        $atLeasetOneFound = true;
                        if ($val1 . "x" != "x")
                        {
                            $userid = $key1;
                            $fuser = new User($db);
                            $fuser->id = $userid;
                            $fuser->fetch();

                            array_push($arrRes,new OFC_Charts_Bar_Stack_Value($val1 * 1, $color[$key1],$fuser->fullname.'<br>  Nb action le #x_label# :<br>  #val#<br>  Total journalier: #total#' ) );
                            $totDay += $val1;
                        }
                    }
                }
            }
            array_push($arrXLabel,date("d/m/Y",$i));
            if ($atLeasetOneFound)
            {
                $bar_stack->append_stack($arrRes);
                //$bar_stack->set_tooltip(  );
                if ($totDay > $maxSoc) $maxSoc = floor($totDay);
            } else {
                $bar_stack->append_stack(array(0));
            }
        }
        $keyArr = array();
        foreach ($color as $key=>$val)
        {
            $tmpUsr = new User($db);
            $tmpUsr->id = $key;
            $tmpUsr->fetch();
            array_push($keyArr, new OFC_Charts_Bar_Stack_Key($val,$tmpUsr->fullname,12));
        }

        $bar_stack->OFC_Charts_Bar_Set_Keys($keyArr);

        $maxSoc++;
        while (! preg_match('/[05]$/',$maxSoc))
        {
            $maxSoc++;
        }


        $y = new OFC_Elements_Axis_Y();
        $y->set_range( 0, $maxSoc, 5 );

        $x = new OFC_Elements_Axis_X();

        $x->set_steps( 86400 );

        $labels = new OFC_Elements_Axis_X_Label_Set("","","","");

        $labels->set_labels($arrXLabel);

        if (count($arrXLabel) > 7)
        {
            $labels->visible_steps(7);
        } else {
            $labels->visible_steps(1);
        }

        $labels->set_rotate(-30);
        $labels->set_size(10);

        $x->set_labels($labels);

        $chart = new OFC_Chart();
        $chart->set_title( $title );
        $chart->add_element( $bar_stack );
        $chart->set_x_axis( $x );
        $chart->add_y_axis( $y );

        echo $chart->toPrettyString();
    }
    break;
    case "SocNotePerCom":
    {
        //Pie chart : Quantite de societe par note
        $requete = "SELECT count(societe_refid) as cnt, floor(avis) as note
                      FROM Babel_campagne_avancement
                     WHERE campagne_refid = $campagneId
                       AND dateModif = (SELECT MAX(dateModif)
                                          FROM Babel_campagne_avancement as a
                                         WHERE a. societe_refid = Babel_campagne_avancement. societe_refid)
                  GROUP BY floor(avis)";

        $sql = $db->query($requete);
        $arrVal = array();
        while ($res = $db->fetch_object($sql))
        {
            $noteArr[0]=">= 0";
            $noteArr[1]=">= 1";
            $noteArr[2]=">= 2";
            $noteArr[3]=">= 3";
            $noteArr[4]=">= 4";
            $arrVal[]=array("value" => $res->cnt * 1, "label" => $noteArr[$res->note]);
        }
        $arr=array();
        $arr['elements']=array(array("tip" => "#val# de #total# sociétés<br>#percent#",
                               "colours" => array(  "0x24A12B",  "0x243D8A",
                                                     "0xF43210",
                                                    "0xEB7916", "0x3F0E63", "0xEDEC0A",
                                                    "0x21A56F",  "0x221B6F",
                                                     "0xEB3914",
                                                    "0xEEA60D" , "0x91135E", "0x6AC720"
                                                     ),
                               "alpha" => 0.8,
                               "start_angle" => 135,
                               "no-labels" => false,
                               "animate" => array(0=> array("type"=>"bounce","distance"=>35), 1=> array("type"=>fade)),
                               "values" => $arrVal,
                               "type" => "pie",
                               "border" => "2" ));
        $arr['bg_colour'] ="#FAFCBC";
        $arr['ani--mate']=true;
        $arr['title']=array('text' => "Notation des prospects", "style" => "font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");
        echo json_encode($arr);



    }
    break;
    case "avancSocPerCom":
    {
        //pIE chart : Quantite de societe par avancement
        $requete = "SELECT count(societe_refid) as cnt,
                           floor(avancement / 2) as avancement
                      FROM Babel_campagne_avancement
                     WHERE campagne_refid = $campagneId
                       AND dateModif = (SELECT MAX(dateModif)
                                          FROM Babel_campagne_avancement as a
                                         WHERE a. societe_refid = Babel_campagne_avancement. societe_refid)
                  GROUP BY floor(avancement / 2) ";

        $sql = $db->query($requete);
        $arrVal = array();
        $noteArr=array();
        for ($i=0;$i<5;$i++)
        {
            $noteArr[$i]=">= à " .$i * 20 . "%";
        }

        while ($res = $db->fetch_object($sql))
        {
            $arrVal[]=array("value" => $res->cnt * 1, "label" => $noteArr[$res->avancement]);
        }
        $arr=array();
        $arr['elements']=array(array("tip" => "#val# de #total# sociétés<br>#percent#\n",
                               "colours" => array(  "0x24A12B",  "0x243D8A",
                                                     "0xF43210",
                                                    "0xEB7916", "0x3F0E63", "0xEDEC0A",
                                                    "0x21A56F",  "0x221B6F",
                                                     "0xEB3914",
                                                    "0xEEA60D" , "0x91135E", "0x6AC720"
                                                     ),
                               "alpha" => 0.8,
                               "start_angle" => 135,
                               "no-labels" => false,
                               "animate" => array(0=> array("type"=>"bounce","distance"=>35), 1=> array("type"=>fade)),
                               "values" => $arrVal,
                               "type" => "pie",
                               "border" => "2" ));
        $arr['bg_colour'] ="#FAFCBC";
        $arr['ani--mate']=true;
        $arr['title']=array('text' => "Avancement des prospects", "style" => "font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");
        echo json_encode($arr);

    }
    break;
    case "CAsinceProspect":
    {

        // CA genere depuis la campagne sur les societe prospecte
        $requete = "SELECT societe_refid,
                           (SELECT ifnull(sum(total_ht) ,0)
                              FROM ".MAIN_DB_PREFIX."propal
                             WHERE fk_soc = Babel_campagne_societe.societe_refid
                               AND ".MAIN_DB_PREFIX."propal.datec >= Babel_campagne_societe.date_cloture) AS sommepropal,
                           (SELECT ifnull(sum(total_ht) ,0)
                              FROM ".MAIN_DB_PREFIX."commande
                             WHERE fk_soc = Babel_campagne_societe.societe_refid
                               AND ".MAIN_DB_PREFIX."commande.date_creation >= Babel_campagne_societe.date_cloture) AS sommecommande,
                           (SELECT ifnull(sum(total) ,0)
                              FROM ".MAIN_DB_PREFIX."facture
                             WHERE fk_soc = Babel_campagne_societe.societe_refid
                               AND ".MAIN_DB_PREFIX."facture.datec >= Babel_campagne_societe .date_cloture) AS sommefacture
                      FROM Babel_campagne_societe
                     WHERE fk_statut = 3
                       AND campagne_refid = $campagneId
                  GROUP BY societe_refid";

        $sql = $db->query($requete);
        $arr = array();
        $color=array();
        $color1=array();
        $color2=array();
        $colorTpl = array( '#F4D318', '#C0C8CA', '#7DFB6A' ,'#BA7BFA',"#D673F5","#6D48F1");
        $colorTpl1 = array( '#D4B300', '#A088AA', '#5DDB4A' ,'#9A5BDA',"#B653D5","#4D28D1");
        $colorTpl2 = array( '#B49300', '#80688A', '#3DAB2A' ,'#9A3BBA',"#9633B5","#2D08B1");
//$colorTpl = array();
        //100 colors :
        for ($i=0;$i<100;$i++)
        {
            $red = dechex(rand(0,255));
            $green = dechex(rand(0,255));
            $blue = dechex(rand(0,255));
            array_push($colorTpl,'#'.$red.$green,$blue);
            $red = dechex(rand(0,255) - 32);
            $green = dechex(rand(0,255)  - 32);
            $blue = dechex(rand(0,255) - 32);
            array_push($colorTpl1,'#'.$red.$green,$blue);
            $red = dechex(rand(0,255) - 64);
            $green = dechex(rand(0,255)  - 64);
            $blue = dechex(rand(0,255) - 64);
            array_push($colorTpl2,'#'.$red.$green,$blue);
        }

        $i=0;
        $arrXLabel=array();
        while ($res = $db->fetch_object($sql))
        {
            $arr[$res->societe_refid]['propal']=$res->sommepropal ;
            $arr[$res->societe_refid]['command']=$res->sommecommande;
            $arr[$res->societe_refid]['facture']=$res->sommefacture;
            $color[$res->societe_refid]=$colorTpl[$i];
            $color1[$res->societe_refid]=$colorTpl1[$i];
            $color2[$res->societe_refid]=$colorTpl2[$i];

            $i++;
        }
        require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php5-ofc-library/lib/OFC/OFC_Chart.php');

        $title = new OFC_Elements_Title( "CA depuis la prospection" );
        $title->set_style("font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");


        $bar_stack = new OFC_Charts_Bar_Glass();
        $bar_stack1 = new OFC_Charts_Bar_Glass();
        $bar_stack2 = new OFC_Charts_Bar_Glass();

        $animation_1= isset($_GET['animation_1'])?$_GET['animation_1']:'fade-in';
        $delay_1    = isset($_GET['delay_1'])?$_GET['delay_1']:0.5;
        $cascade_1    = isset($_GET['cascade_1'])?$_GET['cascade_1']:1;

        $bar_stack->set_on_show(new OFC_Charts_Bar_On_Show($animation_1, $cascade_1, $delay_1));
        $bar_stack1->set_on_show(new OFC_Charts_Bar_On_Show($animation_1, $cascade_1, $delay_1));
        $bar_stack2->set_on_show(new OFC_Charts_Bar_On_Show($animation_1, $cascade_1, $delay_1));

        $arrXLabel=array();
        $maxCA=0;
        $arrRes=array();
        $arrRes1=array();
        $arrRes2=array();
        foreach($arr as $key=>$val)
        {
            $soc = new Societe($db,$key);
            $soc->fetch($key);

            array_push($arrXLabel,$soc->nom);

            $tmpPropal = new OFC_Charts_Bar_Value($val['propal'] * 1);

            $tmpPropal->set_colour( $color[$key]);
            $tmpPropal->set_tooltip($soc->nom.'<br> Propal : #val#'."€");

            array_push($arrRes,$tmpPropal );
            if ($val['propal'] > $maxCA) $maxCA = $val["propal"] ;

            $tmpCommand = new OFC_Charts_Bar_Value($val['command'] * 1);
            $tmpCommand->val=$val['command'] * 1;
            $tmpCommand->set_colour( $color1[$key]);
            $tmpCommand->set_tooltip($soc->nom.'<br> Commande : #val#'."€" );

            array_push($arrRes1,$tmpCommand);
            if ($val['command'] > $maxCA) $maxCA = $val["command"] ;

            $tmpFacture = new OFC_Charts_Bar_Value($val['facture'] * 1);
            $tmpFacture->val=$val['facture'] * 1;
            $tmpFacture->set_colour( $color2[$key]);
            $tmpFacture->set_tooltip($soc->nom.'<br> Facture : #val#'."€" );

            array_push($arrRes2,$tmpFacture );
            if ($val['facture'] > $maxCA) $maxCA = $val["facture"] ;
        }
        $bar_stack->set_values($arrRes);
        $bar_stack1->set_values($arrRes1);
        $bar_stack2->set_values($arrRes2);

        if (! preg_match('/[05]$/',$maxCA / 1000))
        {
            $maxCA = ceil($maxCA/1000);
            $maxCA = $maxCA * 1000;
        }
        $y = new OFC_Elements_Axis_Y();
        $y->set_range( 0, $maxCA, 5 );

        $x = new OFC_Elements_Axis_X();

        $labels = new OFC_Elements_Axis_X_Label_Set("","","","");

        $labels->set_labels($arrXLabel);

        $labels->visible_steps(1);

        $labels->set_rotate(-30);
        $labels->set_size(10);

        $x->set_labels($labels);

        $chart = new OFC_Chart();
        $chart->set_title( $title );
        $chart->add_element( $bar_stack );
        $chart->add_element( $bar_stack1 );
        $chart->add_element( $bar_stack2 );
        $chart->set_x_axis( $x );
        $chart->add_y_axis( $y );

        echo $chart->toPrettyString();


    }
    break;
    case "CApositifReturnSoc":
    {
        // CA genere depuis la campagne par toutes les societes
        //Seulement les retour positif
        $requete = "SELECT societe_refid,
                           (SELECT ifnull(sum(total_ht) ,0)
                              FROM ".MAIN_DB_PREFIX."propal
                             WHERE fk_soc = Babel_campagne_societe.societe_refid
                               AND ".MAIN_DB_PREFIX."propal.datec >= Babel_campagne_societe.date_cloture) AS sommepropal,
                           (SELECT ifnull(sum(total_ht) ,0)
                              FROM ".MAIN_DB_PREFIX."commande
                             WHERE fk_soc = Babel_campagne_societe.societe_refid
                               AND ".MAIN_DB_PREFIX."commande.date_creation >= Babel_campagne_societe.date_cloture) AS sommecommande,
                           (SELECT ifnull(sum(total) ,0)
                              FROM ".MAIN_DB_PREFIX."facture
                             WHERE fk_soc = Babel_campagne_societe.societe_refid
                               AND ".MAIN_DB_PREFIX."facture.datec >= Babel_campagne_societe .date_cloture) AS sommefacture
                      FROM Babel_campagne_societe
                     WHERE fk_statut = 3
                       AND closeStatut = 1
                       AND campagne_refid = $campagneId";

        $sql = $db->query($requete);
        $arr = array();
        $color=array();
        $color1=array();
        $color2=array();
        $colorTpl = array( '#F4D318', '#C0C8CA', '#7DFB6A' ,'#BA7BFA',"#D673F5","#6D48F1");
        $colorTpl1 = array( '#D4B300', '#A088AA', '#5DDB4A' ,'#9A5BDA',"#B653D5","#4D28D1");
        $colorTpl2 = array( '#B49300', '#80688A', '#3DAB2A' ,'#9A3BBA',"#9633B5","#2D08B1");
//$colorTpl = array();
        //100 colors :
        for ($i=0;$i<100;$i++)
        {
            $red = dechex(rand(0,255));
            $green = dechex(rand(0,255));
            $blue = dechex(rand(0,255));
            array_push($colorTpl,'#'.$red.$green,$blue);
            $red = dechex(rand(0,255) - 32);
            $green = dechex(rand(0,255)  - 32);
            $blue = dechex(rand(0,255) - 32);
            array_push($colorTpl1,'#'.$red.$green,$blue);
            $red = dechex(rand(0,255) - 64);
            $green = dechex(rand(0,255)  - 64);
            $blue = dechex(rand(0,255) - 64);
            array_push($colorTpl2,'#'.$red.$green,$blue);
        }

        $i=0;
        $arrXLabel=array();
        while ($res = $db->fetch_object($sql))
        {
            $arr[$res->societe_refid]['propal']=$res->sommepropal;
            $arr[$res->societe_refid]['command']=$res->sommecommande;
            $arr[$res->societe_refid]['facture']=$res->sommefacture;
            $color[$res->societe_refid]=$colorTpl[$i];
            $color1[$res->societe_refid]=$colorTpl1[$i];
            $color2[$res->societe_refid]=$colorTpl2[$i];

            $i++;
        }
        require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php5-ofc-library/lib/OFC/OFC_Chart.php');

        $title = new OFC_Elements_Title( "CA depuis la prospection (retour positif)" );
        $title->set_style("font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");


        $bar_stack = new OFC_Charts_Bar_Glass();
        $bar_stack1 = new OFC_Charts_Bar_Glass();
        $bar_stack2 = new OFC_Charts_Bar_Glass();

        $animation_1= isset($_GET['animation_1'])?$_GET['animation_1']:'fade-in';
        $delay_1    = isset($_GET['delay_1'])?$_GET['delay_1']:0.5;
        $cascade_1    = isset($_GET['cascade_1'])?$_GET['cascade_1']:1;

        $bar_stack->set_on_show(new OFC_Charts_Bar_On_Show($animation_1, $cascade_1, $delay_1));
        $bar_stack1->set_on_show(new OFC_Charts_Bar_On_Show($animation_1, $cascade_1, $delay_1));
        $bar_stack2->set_on_show(new OFC_Charts_Bar_On_Show($animation_1, $cascade_1, $delay_1));

        $arrXLabel=array();
        $maxCA=0;
        $arrRes=array();
        $arrRes1=array();
        $arrRes2=array();
        foreach($arr as $key=>$val)
        {
            $soc = new Societe($db,$key);
            $soc->fetch($key);

            array_push($arrXLabel,$soc->nom);

            $tmpPropal = new OFC_Charts_Bar_Value($val['propal'] * 1);

            $tmpPropal->set_colour( $color[$key]);
            $tmpPropal->set_tooltip($soc->nom.'<br> Propal : #val#'."€");

            array_push($arrRes,$tmpPropal );
            if ($val['propal'] > $maxCA) $maxCA = $val["propal"] ;

            $tmpCommand = new OFC_Charts_Bar_Value($val['command'] * 1);
            $tmpCommand->val=$val['command'] * 1;
            $tmpCommand->set_colour( $color1[$key]);
            $tmpCommand->set_tooltip($soc->nom.'<br> Commande : #val#'."€" );

            array_push($arrRes1,$tmpCommand);
            if ($val['command'] > $maxCA) $maxCA = $val["command"] ;

            $tmpFacture = new OFC_Charts_Bar_Value($val['facture'] * 1);
            $tmpFacture->val=$val['facture'] * 1;
            $tmpFacture->set_colour( $color2[$key]);
            $tmpFacture->set_tooltip($soc->nom.'<br> Facture : #val#'."€" );

            array_push($arrRes2,$tmpFacture );
            if ($val['facture'] > $maxCA) $maxCA = $val["facture"] ;
        }
        $bar_stack->set_values($arrRes);
        $bar_stack1->set_values($arrRes1);
        $bar_stack2->set_values($arrRes2);

        if (! preg_match('/[05]$/',$maxCA / 1000))
        {
            $maxCA = ceil($maxCA/1000);
            $maxCA = $maxCA * 1000;
        }
        $y = new OFC_Elements_Axis_Y();
        $y->set_range( 0, $maxCA, 5 );

        $x = new OFC_Elements_Axis_X();

        $labels = new OFC_Elements_Axis_X_Label_Set("","","","");

        $labels->set_labels($arrXLabel);

        $labels->visible_steps(1);

        $labels->set_rotate(-30);
        $labels->set_size(10);

        $x->set_labels($labels);

        $chart = new OFC_Chart();
        $chart->set_title( $title );
        $chart->add_element( $bar_stack );
        $chart->add_element( $bar_stack1 );
        $chart->add_element( $bar_stack2 );
        $chart->set_x_axis( $x );
        $chart->add_y_axis( $y );

        echo $chart->toPrettyString();

    }

}


?>
