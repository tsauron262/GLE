<?php
/*
  * GLE by Babel-Services
  *
  * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 16 juil. 2010
  *
  * Infos on http://www.babel-services.com
  *
  */
 /**
  *
  * Name : stat_produit_json.php
  * GLE-1.1
  */

  require_once('../../../main.inc.php');
       // CA genere depuis la campagne sur les societe prospecte
        $requete = "SELECT societe_refid,
                           (SELECT ifnull(sum(total_ht) ,0)
                              FROM llx_propal
                             WHERE fk_soc = Babel_campagne_societe.societe_refid
                               AND llx_propal.datec >= Babel_campagne_societe.date_cloture) AS sommepropal,
                           (SELECT ifnull(sum(total_ht) ,0)
                              FROM llx_commande
                             WHERE fk_soc = Babel_campagne_societe.societe_refid
                               AND llx_commande.date_creation >= Babel_campagne_societe.date_cloture) AS sommecommande,
                           (SELECT ifnull(sum(total) ,0)
                              FROM llx_facture
                             WHERE fk_soc = Babel_campagne_societe.societe_refid
                               AND llx_facture.datec >= Babel_campagne_societe .date_cloture) AS sommefacture
                      FROM Babel_campagne_societe
                     WHERE fk_statut = 3
                       AND campagne_refid = 1
                  GROUP BY societe_refid";

        $requete = "SELECT  sum(amount) as total,
                            SUM(CASE 1 WHEN amount<0 THEN 0 ELSE amount END) as vente,
                            SUM(CASE 1 WHEN amount>0 THEN 0 ELSE amount END) as achat,
                            UNIX_TIMESTAMP(datev) as dv
                       FROM llx_bank
   	 			      WHERE  datev > date_sub(now(), interval ".(($_REQUEST['fullSize'].'x' !=  "x")? 18 : 6)." month)
				   GROUP BY month(datev), year(datev)
				   ORDER BY datev ASC";
	
	
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
        $arrDate=array();
        while ($res = $db->fetch_object($sql))
        {
            $arr[date('m',$res->dv).date('Y',$res->dv)]['total']=$res->total;
            $arr[date('m',$res->dv).date('Y',$res->dv)]['vente']=$res->vente;
            $arr[date('m',$res->dv).date('Y',$res->dv)]['achat']=$res->achat;
            $color[date('m',$res->dv).date('Y',$res->dv)]=$colorTpl[$i];
            $arrDate[date('m',$res->dv).date('Y',$res->dv)]=date('m',$res->dv)."/".date('Y',$res->dv);

            $i++;
        }
        //var_dump($arrDate);
        require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php5-ofc-library/lib/OFC/OFC_Chart.php');

        $title = new OFC_Elements_Title( "Solde bancaire" );
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
        $minCA=0;
        $arrRes=array();
        $arrRes1=array();
        $arrRes2=array();
        foreach($arr as $key=>$val)
        {

            array_push($arrXLabel,$arrDate[$key]);

            $tmpPropal = new OFC_Charts_Bar_Value($val['total'] * 1);

            $tmpPropal->set_colour( $color[$key]);
            $tmpPropal->set_tooltip($arrDate[$key].'<br> Total : #val#'."€");

            array_push($arrRes,$tmpPropal );
            if ($val['total'] > $maxCA) $maxCA = $val["total"] ;



            $tmpPropal = new OFC_Charts_Bar_Value($val['vente'] * 1);

            $tmpPropal->set_colour( $color[$key]);
            $tmpPropal->set_tooltip($arrDate[$key].utf8_encode('<br> Entree : #val#')."€");

            array_push($arrRes1,$tmpPropal );
            if ($val['vente'] > $maxCA) $maxCA = $val["vente"] ;



            $tmpPropal = new OFC_Charts_Bar_Value($val['achat'] * 1);

            $tmpPropal->set_colour( $color[$key]);
            $tmpPropal->set_tooltip($arrDate[$key].'<br> Sortie : #val#'."€");

            array_push($arrRes2,$tmpPropal );
            if ($val['achat'] > $maxCA) $maxCA = $val["achat"] ;
            if ($val['achat'] < $minCA) $minCA = $val["achat"] ;


         }
        $bar_stack->set_values($arrRes);
        $bar_stack1->set_values($arrRes1);
        $bar_stack2->set_values($arrRes2);

        if (! preg_match('/[05]$/',$maxCA / 1000))
        {
            $maxCA = ceil($maxCA/1000);
            $maxCA = $maxCA * 1000;
        }

        if (! preg_match('/[05]$/',$minCA / 1000))
        {
            $minCA = ceil(abs($minCA)/1000);
            $minCA = $minCA * 1000;
            $minCA = -$minCA;
        }

        $y = new OFC_Elements_Axis_Y();
        $y->set_range( $minCA, $maxCA, 5 );

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
?>
