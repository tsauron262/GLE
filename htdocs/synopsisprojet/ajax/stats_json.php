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
  * Name : stats_json.php
  * GLE-1.1
  */


  //Grid avec la liste et les resultats
  //Subgrid avec les notes d'avancement

$project_id=$_REQUEST['projId'];

  require_once('../../main.inc.php');
  require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");


$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@synopsistools");

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
    case 'fraisProjet':
    {
        require_once( DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php-ofc-library/open-flash-chart.php' );


        $debutTs = mktime(0,0,0,9,1,2009);
        $finTs = mktime(0,0,0,9,31,2009);

        $requete = "SELECT sum(montantHT) as HT, year(dateAchat) as YA, month(dateAchat) as MA, day(dateAchat) as DA
                      FROM ".MAIN_DB_PREFIX."Synopsis_projet_frais
                     WHERE fk_projet = ".$project_id."
                  GROUP BY year(dateAchat), month(dateAchat), day(dateAchat)";
        $sql = $db->query($requete);
        $sumArr = array();
        $max = 0;
        while ($res = $db->fetch_object($sql))
        {
            $dateU = mktime(0,0,0,$res->MA,$res->DA,$res->YA);
            $sumArr[$dateU]=round($res->HT);
        }
        $data = array();
        $iter=0;
        $xLegend = array();
        for ($i=$debutTs;$i <= $finTs ; $i += (3600 * 24))
        {
            foreach($sumArr as $dateAchatTS => $montantHT)
            {
                if ( $dateAchatTS >= mktime(0,0,0,date('m',$i),date('d',$i),date('Y',$i)) && $dateAchatTS <= mktime(23,59,59,date('m',$i),date('d',$i),date('Y',$i)) )
                {
                    $data[$iter] = new bar_value(round($montantHT));
                    $data[$iter]->set_colour( '#ff0000' );
                    $data[$iter]->set_tooltip( 'Le '.date('d/m/Y',$i).' <hr> Total : #val#' );
                    if ($max < $montantHT) $max = $montantHT;
                } else if ($data[$iter] < 1){
                    $data[$iter] = new bar_value(0);
                    $data[$iter]->set_tooltip( "Le ".date('d/m/Y',$i));
                }
            }
            $xLegend[$iter]=date('d/m/Y',$i);
            $iter++;
        }
        $title = new title( "Frais de projets" );
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

case 'ressource':
    {
        require_once( DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php-ofc-library/open-flash-chart.php' );
        require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");


        $debutTs = mktime(0,0,0,9,1,2009);
        $finTs = mktime(0,0,0,12,31,2009);

// costProjetRessource
        $project = new SynopsisProject($db);
        $project->id = $project_id;
        $project->costProjetRessource($conf);

        $requete = "SELECT fk_projet_task
                      FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa,
                           ".MAIN_DB_PREFIX."Synopsis_global_ressources
                     WHERE ".MAIN_DB_PREFIX."Synopsis_global_ressources_resa.fk_ressource = ".MAIN_DB_PREFIX."Synopsis_global_ressources.id
                       AND fk_projet_task is not null
                       AND fk_projet = ".$project_id;
        $sql = $db->query($requete);
        while ($res=$db->fetch_object($sql))
        {
            $project->costTaskRessource($res->fk_projet_task);
        }
//var_dump($project->arrCostAllRessourcePerDay);
        $data = array();
        $iter=0;
        $xLegend = array();
//costTaskRessource($taskId)
//$this->costByDay["task"][$taskId]['Ressource']=$arrayByDay
        $dataV  = array();
        for ($i=$debutTs;$i <= $finTs ; $i += (3600 * 24))
        {
            foreach($project->arrCostAllRessourcePerDay as $dateAchatTS => $montantHT)
            {
                if ( $dateAchatTS >= mktime(0,0,0,date('m',$i),date('d',$i),date('Y',$i)) && $dateAchatTS <= mktime(23,59,59,date('m',$i),date('d',$i),date('Y',$i)) )
                {
                    $data[$iter] = new bar_value(round($montantHT['cout']));
                    $data[$iter]->set_colour( '#ff0000' );
                    $data[$iter]->set_tooltip( 'Le '.date('d/m/Y',$i).' <hr> Total : #val#' );
                    if ($max < $montantHT['cout']) $max = $montantHT['cout'];
                } else if ($data[$iter] < 1){
                    $data[$iter] = new bar_value(0);
                    $data[$iter]->set_tooltip( "Le ".date('d/m/Y',$i));
                }
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
    case "paiementIO":
        //affiche combien rentre et sort selon les paiement des factures et factures fournisseurs
        //0 init
        require_once( DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php-ofc-library/open-flash-chart.php' );
        require_once( DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php-ofc-library/ofc_line_dot.php' );

        $dateo = mktime(0, 0, 0, 4, 1, date('Y')); //TODO fin last monday
        $dateFin = mktime(0, 0, 0, 12, 31, date('Y'));

        $data3 = array(); // total
        $data2 = array();// Sortie
        $data1 = array(); // Entree
        $remArr = array();
        $CompArr = array();

        $dot = new scatter_value($dateo,0);
        $data1[]=$dot;
        $data2[]=$dot;
        $data3[]=$dot;
        //1 liste les factures
        $requete = "SELECT day(datep) as dDF,
                           month(datep) as mDF,
                           year(datep) as yDF,
                           sum(".MAIN_DB_PREFIX."paiement_facture.amount) as tot
                      FROM ".MAIN_DB_PREFIX."facture,
                           ".MAIN_DB_PREFIX."paiement_facture,
                           ".MAIN_DB_PREFIX."paiement
                     WHERE ".MAIN_DB_PREFIX."paiement.rowid = ".MAIN_DB_PREFIX."paiement_facture.fk_paiement
                       AND ".MAIN_DB_PREFIX."paiement_facture.fk_facture = ".MAIN_DB_PREFIX."facture.rowid
                       AND fk_projet= ".$project_id."
            GROUP BY year(datep), month(datep), day(datep)";
        $sql = $db->query($requete);
        $max=0;
        while ($res = $db->fetch_object($sql))
        {
            $dot = new scatter_value(mktime(0,0,0,$res->mDF,$res->dDF,$res->yDF),round($res->tot));
            $data1[]=$dot;
            $CompArr[mktime(0,0,0,$res->mDF,$res->dDF,$res->yDF)]+=round($res->tot);
            if($max < $res->tot) $max = $res->tot;
        }

        $requete = "SELECT day(datep) as dDF,
                           month(datep) as mDF,
                           year(datep) as yDF,
                           sum(".MAIN_DB_PREFIX."paiementfourn_facturefourn.amount) as tot
                      FROM ".MAIN_DB_PREFIX."paiementfourn,
                           ".MAIN_DB_PREFIX."facture_fourn,
                           ".MAIN_DB_PREFIX."paiementfourn_facturefourn
                     WHERE ".MAIN_DB_PREFIX."paiementfourn.rowid = ".MAIN_DB_PREFIX."paiementfourn_facturefourn.fk_paiementfourn
                       AND ".MAIN_DB_PREFIX."paiementfourn_facturefourn.fk_facturefourn = ".MAIN_DB_PREFIX."facture_fourn.rowid
                       AND fk_projet= ".$project_id."
            GROUP BY year(".MAIN_DB_PREFIX."paiementfourn.datep), month(".MAIN_DB_PREFIX."paiementfourn.datep), day(".MAIN_DB_PREFIX."paiementfourn.datep)";
        $sql = $db->query($requete);
        $min=0;
        while ($res = $db->fetch_object($sql))
        {
            $dot = new scatter_value(mktime(0,0,0,$res->mDF,$res->dDF,$res->yDF),-round($res->tot));
            //$dot->set_tooltip("Le ".date('d/m/Y',mktime(0,0,0,$res->mDF,$res->dDF,$res->yDF)). " <hr/> Total : ".round($res->tot)." €");
            $data2[]=$dot;
            $CompArr[mktime(0,0,0,$res->mDF,$res->dDF,$res->yDF)]-=round($res->tot);
            //$data2[$iter]=+round($res->tot);
            if($min < $res->tot) $min = $res->tot;
        }
        $sum=0;
        $rem = false;
        for ($i=$dateo;$i<$dateFin;$i+=86400)
        {
            $sum += $CompArr[$i];
            if ($sum > $max) $max = $sum;
            if ($rem && $rem != $sum)
            {
                $dot = new scatter_value($i,$sum);
                $data3[]=$dot;
            }
            $rem = $sum;
        }

        $min = - round($min);
        $min = floor($min);
        $max = ceil($max);
        while ($min%50 != 0)
        {
            $min --;
        }
        while ($max%50 != 0)
        {
            $max ++;
        }


        $title = new title( "Paiement - E/S"  );

        // ------- LINE 2 -----
        $d = new hollow_dot();
        $d->size(3)->halo_size(1)->colour('#FF0000');

        $d2 = new hollow_dot();
        $d2->size(3)->halo_size(1)->colour('#00FF00');

        $d3 = new hollow_dot();
        $d3->size(3)->halo_size(1)->colour('#0000FF');


        $line = new scatter_line( '#FF0000', 3);
        $line->set_default_dot_style($d);
        $line->set_values( $data1 );
        $line->set_width( 2 );
        //$line->set_colour( '#3D5C56' );

        $line2 = new scatter_line( '#00FF00', 3);
        $line2->set_default_dot_style($d2);
        $line2->set_values( $data2 );
        $line2->set_width( 2 );
        //$line2->set_colour( '#5C563D' );

        $line3 = new scatter_line( '#0000FF', 3);
        $line3->set_default_dot_style($d3);
        $line3->set_values( $data3 );
        $line3->set_width( 2 );


        $x = new x_axis();
        // grid line and tick every 10
        $x->set_range(
            $dateo ,    // <-- min == 1st Jan, this year
            $dateFin,    // <-- max == 31st dec, this year
           86400 );
         //show ticks and grid lines for every day:
        $x->set_steps(86400*7);

        $labels = new x_axis_labels();
        // tell the labels to render the number as a date:
        $labels->text('#date:d/m/Y#');
        // generate labels for every day
        $labels->set_steps(86400);
        // only display every other label (every other day)
        $labels->visible_steps(7);
        $labels->rotate(-30);

        // finally attach the label definition to the x axis
        $x->set_labels($labels);


        $y = new y_axis();
        $interval=($max +$min) / 10;
        $y->set_range( $min, $max, $interval );


        $chart = new open_flash_chart();

//        $anime = new bar_on_show("mid-slide",4,1);
//        $line->set_on_show( $anime );
//        $line2->set_on_show( $anime );

        $chart->set_title( $title );
        $chart->add_element( $line );
        $chart->add_element( $line2 );
        $chart->add_element( $line3 );
$chart->set_x_axis( $x );

        $chart->set_y_axis( $y );
        echo $chart->toPrettyString();

    break;


}


?>
