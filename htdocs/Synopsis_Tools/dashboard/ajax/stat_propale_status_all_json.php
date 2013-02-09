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
        $animation_1= isset($_GET['animation_1'])?$_GET['animation_1']:'fade-in';
        $delay_1    = isset($_GET['delay_1'])?$_GET['delay_1']:0.5;
        $cascade_1    = isset($_GET['cascade_1'])?$_GET['cascade_1']:1;


        $requete = "SELECT * FROM llx_c_propalst WHERE active = 1";
        $sql = $db->query($requete);
        $arr = array();
        $arrLang = array();
        $arrXLabel=array();
        while ($res = $db->fetch_object($sql))
        {
            $arr[$res->id]=0;
            array_push($arrXLabel,$res->label);
        }
        $requete = "SELECT ifnull(sum(total_ht),0) as count,
                           ifnull(fk_statut,-1) as fk_statut
                      FROM llx_propal
                     WHERE datep > date_sub(now() , interval 6 month)
                  GROUP BY fk_statut ";

        $sql = $db->query($requete);
        $color = array( '#AA0000', '#AA5500', '#AAAA00' ,'#55CC00',"#00AA00","#00DD00","#5555CC");

        while ($res = $db->fetch_object($sql))
        {
            $arr[$res->fk_statut] = $res->count ;
        }

        require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Common/open-flash-chart/php5-ofc-library/lib/OFC/OFC_Chart.php');

        $title = new OFC_Elements_Title( "Toutes les proposition com. par statut 6 mois)" );

        $bar_stack = new OFC_Charts_Bar_3d();
        $bar_stack->set_alpha(0.9);

        $maxSoc=0;
        for ($i=-1; $i<count($arr) ;$i++)
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
                    $objval->set_tooltip( '#val# euro(s)  '.$arrXLabel[$i+1] );
                    $bar_stack->append_value($objval);
                    if ($totDay > $maxSoc) $maxSoc = floor($totDay);
                }
            }
        }
        $keyArr = array();

        $maxSoc++;
        while (! preg_match('/000$/',intval($maxSoc)))
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
?>
