<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 2 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : rt-stats-json.php
  * GLE-1.1
  */



  require_once('../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");


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
    //1 calculdu total de cout par semaine = frais de projet+ ressourcematos + ressource humaine
    //Prévisionnel et reel
    case "actComPie";
    {

        $resArray=array();
        $requete = " SELECT sum(montantHT) as sommeHT,
                            year(dateAchat) as Y,
                            week(dateAchat,3) as W
                       FROM ".MAIN_DB_PREFIX."Synopsis_projet_frais
                      WHERE fk_projet = ".$project_id."
                   GROUP BY year(dateAchat),week(dateAchat,3)";
        $sql = $db->query($requete);
        while ($res=$db->fetch_object($sql))
        {
            $resArray[$res->Y][$res->W]=$res->sommeHT;
        }

    echo json_encode($resArray);

    }
    break;
    case 'ressource':
    {
        $resArray=array();
        for($i=0;$i<10;$i++)
        {
            $resArray[rand(2007,2010)][rand(0,52)]=rand(1000,100000)/100;
        }
        echo json_encode($resArray);

    }
    break;

    case 'closeByDay':
    {
        require_once( DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php-ofc-library/open-flash-chart.php' );
        require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");


        $debutTs = mktime(0,0,0,6,1,2010);
        $finTs = mktime(0,0,0,7,31,2010);

        require_once(DOL_DOCUMENT_ROOT.'/Babel_GMAO/rt.class.php');
        $rt = new rt($db);

        $res = $rt->searchTicket(urlencode("(( Status = 'resolved' ) AND (Created > '2010-06-06 00:00'))"));
        //prepass
        $prepassArr = array();
        foreach($res as $key=>$val)
        {
            $date = strtotime($val['Resolved']);
            $dateSimple = mktime(0,0,0,date('m',$date),date('d',$date),date('Y',$date));
            $prepassArr[$dateSimple]++;;
        }

        $data = array();
        $iter=0;
        $xLegend = array();

        $dataV  = array();
        for ($i=$debutTs;$i <= $finTs ; $i += (3600 * 24))
        {
            $dateAchatTS = $i;
                if ( $dateAchatTS >= mktime(0,0,0,date('m',$i),date('d',$i),date('Y',$i)) && $dateAchatTS <= mktime(23,59,59,date('m',$i),date('d',$i),date('Y',$i)) )
                {
                    $montantHT['cout']=$prepassArr[$dateAchatTS];
                    $data[$iter] = new bar_value(round($montantHT['cout']));
                    $data[$iter]->set_colour( '#179F3C' );
                    $data[$iter]->set_tooltip( 'Le '.date('d/m/Y',$i).' <hr> Total : #val#' );
                    if ($max < $montantHT['cout']) $max = $montantHT['cout'];
                } else if ($data[$iter] < 1){
                    $data[$iter] = new bar_value(0);
                    $data[$iter]->set_tooltip( "Le ".date('d/m/Y',$i));
                }
            $xLegend[$iter]=date('d/m/Y',$i);
            $iter++;
        }

        $title = new title( "Résolu par jour" );
        $title->set_style("font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");

        $bar = new bar_glass();

        $bar->set_values( $data );
        while ($max % 10 != 0)
        {
            $max++;
        }
        $anime = new bar_on_show("grow-up",4,1);
        $bar->set_on_show( $anime );

        $chart = new open_flash_chart();
        $chart->set_title( $title );
        $chart->add_element( $bar );
        $x_labels = new x_axis_labels();
        $x_labels->set_steps( 7 );
        $x_labels->rotate(-30);
        $x_labels->set_colour( '#A2ACBA' );
        $x_labels->set_labels( $xLegend );

        $x = new x_axis();
        $x->set_colour( '#A2ACBA' );
        $x->set_grid_colour( '#D7E4A3' );
        $x->set_offset( true );
        $x->set_steps(7);
        // Add the X Axis Labels to the X Axis
        $x->set_labels( $x_labels );

        $chart->set_x_axis( $x );

        //
        // LOOK:
        //
//        $x_legend = new x_legend( '1983 to 2008' );
//        $x_legend->set_style( '{font-size: 20px; color: #778877}' );
//        $chart->set_x_legend( $x_legend );

        $y = new y_axis();
        $interval = $max / 2;
        $y->set_range( 0, $max, $interval );
        $chart->set_y_axis( $y );

        echo $chart->toPrettyString();



    }
    break;

    case 'pendingByDay':
    {
        require_once( DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php-ofc-library/open-flash-chart.php' );
        require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");


        $debutTs = mktime(0,0,0,6,1,2010);
        $finTs = mktime(0,0,0,7,31,2010);

        require_once(DOL_DOCUMENT_ROOT.'/Babel_GMAO/rt.class.php');
        $rt = new rt($db);

        $res = $rt->searchTicket(urlencode("(( Status != 'resolved' AND Status != 'rejected' ) AND (Created > '2010-06-06 00:00'))"));
//prepass
        $prepassArr = array();
        foreach($res as $key=>$val)
        {
            $date = strtotime($val['Created']);
//            print $date."<br/>";
//            print date('d/m/Y',$date)."<br>";
            $dateSimple = mktime(0,0,0,date('m',$date),date('d',$date),date('Y',$date));
//            print $dateSimple."<br/>";
//            print date('d/m/Y',$dateSimple)."<br>";
            if ('x'.$prepassArr[$dateSimple] == 'x'){
                $prepassArr[$dateSimple]=0;
            }
//            print "<br>";
            $prepassArr[$dateSimple]=$prepassArr[$dateSimple]+1;
        }
        $data = array();
        $iter=0;
        $xLegend = array();

        $dataV  = array();
        for ($i=$debutTs;$i <= $finTs ; $i += (3600 * 24))
        {
            $dateAchatTS = $i;
                if ( $dateAchatTS >= mktime(0,0,0,date('m',$i),date('d',$i),date('Y',$i)) && $dateAchatTS <= mktime(23,59,59,date('m',$i),date('d',$i),date('Y',$i)) )
                {
                    $montantHT['cout']=$prepassArr[$dateAchatTS];
                    $data[$iter] = new bar_value(round($montantHT['cout']));
                    $data[$iter]->set_colour( '#179F3C' );
                    $data[$iter]->set_tooltip( 'Créer le '.date('d/m/Y',$i).' <hr> Total : #val#' );
                    if ($max < $montantHT['cout']) $max = $montantHT['cout'];
                } else if ($data[$iter] < 1){
                    $data[$iter] = new bar_value(0);
                    $data[$iter]->set_tooltip( "Le ".date('d/m/Y',$i));
                }
            $xLegend[$iter]=date('d/m/Y',$i);
            $iter++;
        }

        $title = new title( "Tickets non résolus" );
        $title->set_style("font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");

        $bar = new bar_glass();

        $bar->set_values( $data );
        while ($max % 10 != 0)
        {
            $max++;
        }
        $anime = new bar_on_show("grow-up",4,1);
        $bar->set_on_show( $anime );

        $chart = new open_flash_chart();
        $chart->set_title( $title );
        $chart->add_element( $bar );
        $x_labels = new x_axis_labels();
        $x_labels->set_steps( 7 );
        $x_labels->rotate(-30);
        $x_labels->set_colour( '#A2ACBA' );
        $x_labels->set_labels( $xLegend );

        $x = new x_axis();
        $x->set_colour( '#A2ACBA' );
        $x->set_grid_colour( '#D7E4A3' );
        $x->set_offset( true );
        $x->set_steps(7);
        // Add the X Axis Labels to the X Axis
        $x->set_labels( $x_labels );

        $chart->set_x_axis( $x );

        //
        // LOOK:
        //
//        $x_legend = new x_legend( '1983 to 2008' );
//        $x_legend->set_style( '{font-size: 20px; color: #778877}' );
//        $chart->set_x_legend( $x_legend );

        $y = new y_axis();
        $interval = $max / 2;
        $y->set_range( 0, $max, $interval );
        $chart->set_y_axis( $y );

        echo $chart->toPrettyString();



    }
    break;
    case "globalByDay":
    {
        require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php-ofc-library/open-flash-chart.php');


        $animation_1= isset($_GET['animation_1'])?$_GET['animation_1']:'fade-in';
        $delay_1    = isset($_GET['delay_1'])?$_GET['delay_1']:0.5;
        $cascade_1    = isset($_GET['cascade_1'])?$_GET['cascade_1']:1;

        $colorTpl =  array( '#6D71FF', '#4D50B3', '#FFA028' ,'#179F3C',"#9E9E9E","#FFFFF0");
        $debutTs = mktime(0,0,0,6,1,2010);
        $finTs = mktime(0,0,0,7,31,2010);

        require_once(DOL_DOCUMENT_ROOT.'/Babel_GMAO/rt.class.php');
        $rt = new rt($db);

        $res = $rt->searchTicket(urlencode("(( Status != 'resolved' AND Status != 'rejected' ) AND (Created > '2010-06-06 00:00'))"));
//prepass
        $prepassArr = array();

        $bar_stack = new bar_stack();

        // set a cycle of 3 colours:
        $bar_stack->set_colours( $colorTpl );
        $bar_stack->set_on_show(array('type:' => $animation_1, 'cascade' => $cascade_1, 'delay' => $delay_1));


        foreach($res as $key=>$val)
        {
            $date = strtotime($val['Created']);
            $dateSimple = mktime(0,0,0,date('m',$date),date('d',$date),date('Y',$date));
            if ('x'.$prepassArr[$dateSimple][$val["Status"]] == 'x'){
                $prepassArr[$dateSimple][$val["Status"]]=0;
            }
            $prepassArr[$dateSimple][$val["Status"]]+=1;
        }
        //Dimension de l'array bar stack
        $xLegend = array();
        $iter=0;
        $barStackArr=array(array('val' => 0,'tip'=>'Le #x_label# : <br> #total#'),
                           array('val' => 0,'tip'=>''),
                           array('val' => 0,'tip'=>''),
                           array('val' => 0,'tip'=>''));
        $barStackOrderArr= array('new'=>0,'open'=>1,'stalled'=>2,"resolved"=>3,"rejected"=>4);
        for($i=$debutTs;$i<=$finTs;$i+=86400)
        {
            $barStackArrCop=array();
            $barStackArrCop = $barStackArr;
            foreach($prepassArr as $key=>$val){
                if ($key >= $i && $key < $i+86400)
                {
                    foreach($val as $key1 =>$val1)
                    {
                        $barStackArrCop[$barStackOrderArr[$key1]]=array('val' => $val1,'tip'=>'Le #x_label# : <br> #val# '.$key1.' sur #total#');
                    }
                }
            }
            $bar_stack->append_stack($barStackArrCop);
            $xLegend[$iter]=date('d/m/Y',$i);
            $iter++;
        }


        $title = new title( 'Vue d\'ensemble' );
        $title->set_style("font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");


        $bar_stack->set_keys(
            array(
                new bar_stack_key( $colorTpl[0], 'Nouveau', 13 ),
                new bar_stack_key( $colorTpl[1], 'Ouvert', 13 ),
                new bar_stack_key( $colorTpl[2], 'Stagnant', 13 ),
                new bar_stack_key( $colorTpl[3], 'Résolu', 13 ),
                )
            );
        //$bar_stack->set_tooltip( 'X label [#x_label#], Value [#val#]<br>Total [#total#]' );



        $y = new y_axis();
        $y->set_range( 0, 14, 2 );

//        $x = new x_axis();
//        $x->set_labels_from_array( array( 'Winter', 'Spring', 'Summer', 'Autmn' ) );

        $x_labels = new x_axis_labels();
        $x_labels->set_steps( 7 );
        $x_labels->rotate(-30);
        $x_labels->set_colour( '#A2ACBA' );
        $x_labels->set_labels( $xLegend );

        $x = new x_axis();
        $x->set_colour( '#A2ACBA' );
        $x->set_grid_colour( '#D7E4A3' );
        $x->set_offset( true );
        $x->set_steps(7);
        // Add the X Axis Labels to the X Axis
        $x->set_labels( $x_labels );
        $chart = new open_flash_chart();

        $chart->set_x_axis( $x );



        $tooltip = new tooltip();
        $tooltip->set_hover();
        $bar_stack->set_on_show(array('type:' => $animation_1, 'cascade' => $cascade_1, 'delay' => $delay_1));

        $chart = new open_flash_chart();
        $chart->set_title( $title );
        $chart->add_element( $bar_stack );
        $chart->set_x_axis( $x );
        $chart->add_y_axis( $y );
//        $chart->set_tooltip( $tooltip );

        echo $chart->toPrettyString();
    }
    break;
    case "vueStatutTicket":
    {


        $animation_1= isset($_GET['animation_1'])?$_GET['animation_1']:'fade-in';
        $delay_1    = isset($_GET['delay_1'])?$_GET['delay_1']:0.5;
        $cascade_1    = isset($_GET['cascade_1'])?$_GET['cascade_1']:1;


        $arrXLabel=array('Nouveau','Ouvert','Stagnant',"Résolu","Rejeté");

        $sql = $db->query($requete);
        $color = array("new"=> '#6D71FF', "open"=>'#4D50B3',
                       "stalled" => '#FFA028' , "resolved" => '#179F3C',
                       "rejected" => "#9E9E9E");
        $tipsArr = array("new"=> 'Nouveau', "open"=>'Ouvert',
                       "stalled" => 'Stagnant' , "resolved" => 'Résolu',
                       "rejected" => "Rejeté");

        require_once(DOL_DOCUMENT_ROOT.'/Babel_GMAO/rt.class.php');
        $rt = new rt($db);

        $res = $rt->searchTicket(urlencode("(Created > '2010-06-06 00:00')"));
//prepass
        $prepassArr = array();

        $arr= array('new'=>0,'open'=>0,'stalled'=>0,"resolved"=>0,"rejected"=>0);

        foreach($res as $key=>$val)
        {
            $arr[$val["Status"]]+=1;
        }
        require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php5-ofc-library/lib/OFC/OFC_Chart.php');

        $title = new OFC_Elements_Title( "Vue par statut" );

        $bar_stack = new OFC_Charts_Bar_3d();
        $bar_stack->set_alpha(0.9);

        $maxSoc=0;
        for ($i=-1; $i<count($arr) -1;$i++)
        {
            $atLeasetOneFound = false;
            $arrRes = array();
            foreach ($arr as $key=>$val)
            {
                $j=$i;
                $totDay = 0;
                $atLeasetOneFound = true;
                if ($val . "x" != "x" && $i == $key)
                {
                    $totDay += $val;
                    $objval = new OFC_Charts_Bar_Value( $val) ;
                    $objval->set_colour($color[$key]);
                    $objval->set_tooltip( '#val# ticket  '.$tipsArr[$key] );
                    $bar_stack->append_value($objval);
                    if ($totDay > $maxSoc) $maxSoc = floor($totDay);
                    $j++;
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
    default:
    {
        require_once( DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php-ofc-library/open-flash-chart.php' );
        require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");


        $debutTs = mktime(0,0,0,9,1,2009);
        $finTs = mktime(0,0,0,12,31,2009);

// costProjetRessource
        $data = array();
        $iter=0;
        $xLegend = array();
//costTaskRessource($taskId)
//$this->costByDay["task"][$taskId]['Ressource']=$arrayByDay
        $dataV  = array();
        for ($i=$debutTs;$i <= $finTs ; $i += (3600 * 24))
        {
            $dateAchatTS = $i;
                if ( $dateAchatTS >= mktime(0,0,0,date('m',$i),date('d',$i),date('Y',$i)) && $dateAchatTS <= mktime(23,59,59,date('m',$i),date('d',$i),date('Y',$i)) )
                {
                    $montantHT['cout']=rand(0,10000);
                    $data[$iter] = new bar_value(round($montantHT['cout']));
                    $data[$iter]->set_colour( '#ff0000' );
                    $data[$iter]->set_tooltip( 'Le '.date('d/m/Y',$i).' <hr> Total : #val#' );
                    if ($max < $montantHT['cout']) $max = $montantHT['cout'];
                } else if ($data[$iter] < 1){
                    $data[$iter] = new bar_value(0);
                    $data[$iter]->set_tooltip( "Le ".date('d/m/Y',$i));
                }
            $xLegend[$iter]=date('d/m/Y',$i);
            $iter++;
        }

        $title = new title( "Ressources matériels" );
        $title->set_style("font-size: 14px; color:#0000ff; font-family: Verdana; text-align: center;");

        $bar = new bar_glass();

        $bar->set_values( $data );
        while ($max % 50 != 0)
        {
            $max++;
        }
        $anime = new bar_on_show("grow-up",4,1);
        $bar->set_on_show( $anime );

        $chart = new open_flash_chart();
        $chart->set_title( $title );
        $chart->add_element( $bar );
        $x_labels = new x_axis_labels();
        $x_labels->set_steps( 7 );
        $x_labels->rotate(-30);
        $x_labels->set_colour( '#A2ACBA' );
        $x_labels->set_labels( $xLegend );

        $x = new x_axis();
        $x->set_colour( '#A2ACBA' );
        $x->set_grid_colour( '#D7E4A3' );
        $x->set_offset( true );
        $x->set_steps(7);
        // Add the X Axis Labels to the X Axis
        $x->set_labels( $x_labels );

        $chart->set_x_axis( $x );

        //
        // LOOK:
        //
//        $x_legend = new x_legend( '1983 to 2008' );
//        $x_legend->set_style( '{font-size: 20px; color: #778877}' );
//        $chart->set_x_legend( $x_legend );

        $y = new y_axis();
        $interval = $max / 10;
        $y->set_range( 0, $max, $interval );
        $chart->set_y_axis( $y );

        echo $chart->toPrettyString();



    }
    break;

}
?>
