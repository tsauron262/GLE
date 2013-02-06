<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 7 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : json_data.php
  * magentoGLE
  */
require_once('Var_Dump.php');
Var_Dump::displayInit(array('display_mode' => 'HTML4_Table'), array('mode' => 'normal','offset' => 4));
require_once('pre.inc.php');
  require_once('magento_sales.class.php');
  require_once('magento_product.class.php');
$mag = new magento_sales($conf);
$mag->connect();
$mag1 = new magento_product($conf);


$action =$_REQUEST['action'];
$date = $_REQUEST['date'];

$action = $action.$date;

switch ($action)
{
//Ventes
    case  'ventesweek':
    default :
    {
        $array = array(0=>0,1=>0,2=>0,3=>0,4=>0,5=>0,6=>0);
        $res = $mag->sales_list();
        $now = time();
        $last = strtotime('-1 week',$now);
        $retArr=array();
        foreach($res as $key=>$val)
        {
            if (strtotime($val['created_at']) > $last)
            {
                $diff = $now - strtotime($val['created_at']);
                $jour = intval($diff / (24*3600));
                if (!is_numeric($retArr[$jour]['amount']))
                {
                    $retArr[$jour]["amount"]=0;
                }
                if (!is_numeric($retArr[$jour]['qty']))
                {
                    $retArr[$jour]["qty"]=0;
                }
                $retArr[$jour]["amount"] += $val['subtotal'];
                $retArr[$jour]["qty"] ++;
            }
        }
        // on distribue dans des arrays
        //on affiche
        $arrDays[0]="Dim.";
        $arrDays[1]="Lun.";
        $arrDays[2]="Mar.";
        $arrDays[3]="Mer.";
        $arrDays[4]="Jeu.";
        $arrDays[5]="Ven.";
        $arrDays[6]="Sam.";
        $arrDays[7]="Dim.";

        $arr2 = array();
        $arr1 = array();
        $arrJ = array(
                       1=> $arrDays[date('N',time()-6*24*3600)],
                       2=> $arrDays[date('N',time()-5*24*3600)],
                       3=> $arrDays[date('N',time()-4*24*3600)],
                       4=> $arrDays[date('N',time()-3*24*3600)],
                       5=> $arrDays[date('N',time()-2*24*3600)],
                       6=> $arrDays[date('N',time()-24*3600)],
                       7=> $arrDays[date('N')]);

        //var_dump($arrJ);
        $arrJfinal = array();
        foreach($arrJ as $key=>$val)
        {
            array_push($arrJfinal,$val);
        }
        for($i=7;$i>0;$i--)
        {
            if (!is_numeric($retArr[$i]["amount"]))
            {
                $retArr[$i]["amount"]=0;
            }
            array_push($arr1,$retArr[$i]["amount"]);
        }
        for($i=7;$i>0;$i--)
        {
            if (!is_numeric($retArr[$i]["qty"]))
            {
                $retArr[$i]["qty"]=0;
            }
            array_push($arr2,$retArr[$i]["qty"]);
        }


          $arr = array();
          $arr['title']=array('text' =>  "Evolution des ventes", "style" => array("font-size" => "20px", "color" => "#0000ff" , "font-family" => "Verdana", "text-align" => "center"));
          $arr['y_legend']=array('text' =>  "Open Flash Chart", "style" => array("color" => "#736AFF",  "font-size" => "12px"));
          $arr['elements']=array (array('type' => "bar",
                                 'alpha' => 0.5,
                                 'colour' => "#9933CC",
                                 'text' => "Page view",
                                 'font-size' => 10,
                                 'values' => $arr1,
                                 ),
                                 array('type' => "bar",
                                 'alpha' => 0.5,
                                 'colour' => "#CC9933",
                                 'text' => "Page view 2",
                                 'font-size' => 10,
                                 'values' => $arr2,
                                 "axis"=> "right"
                                 ),
                                 );
          $arr['x_axis']=array('stroke' =>  1,
                               "tick_height" => 10,
                               "colour" => "#736AFF",
                               "grid_colour" => "#00ff00",
                               "labels" => array("labels" => $arrJfinal));
          $arr['y_axis_right']=array('stroke' =>  4,
                               "tick_height" => 3,
                               "colour" => "#CC9933",
                               "grid_colour" => "#CC9933",
                               "offset" => 0,
                               "max" => 20);
          $arr['y_axis']=array('stroke' =>  4,
                               "tick_height" => 3,
                               "colour" => "#d000d0",
                               "grid_colour" => "#00ff00",
                               "offset" => 0,
                               "max" => 10000);

        echo json_encode($arr);

    }
    break;
//Customers

    case "customerweek":
    {
        $array = array(0=>0,1=>0,2=>0,3=>0,4=>0,5=>0,6=>0);
        $res = $mag1->customer_list();

        $now = time();
        $last = strtotime('-1 week',$now);
        $retArr=array();
        foreach($res as $key=>$val)
        {
            if (strtotime($val['created_at']) > $last)
            {
                $diff = $now - strtotime($val['created_at']);
                $jour = intval($diff / (24*3600));
                if (!is_numeric($retArr[$jour]['qty']))
                {
                    $retArr[$jour]["qty"]=0;
                }
                $retArr[$jour]["qty"] ++;
            }
        }
        // on distribue dans des arrays
        //on affiche
        $arrDays[-1]="Sam.";
        $arrDays[0]="Dim.";
        $arrDays[1]="Lun.";
        $arrDays[2]="Mar.";
        $arrDays[3]="Mer.";
        $arrDays[4]="Jeu.";
        $arrDays[5]="Ven.";
        $arrDays[6]="Sam.";
        $arrDays[7]="Dim.";
        $arrDays[7]="Lun.";

        $arr2 = array();
        $arr1 = array();
        $arrJ = array(
                       0=> $arrDays[date('N',time()-7*24*3600)],
                       1=> $arrDays[date('N',time()-6*24*3600)],
                       2=> $arrDays[date('N',time()-5*24*3600)],
                       3=> $arrDays[date('N',time()-4*24*3600)],
                       4=> $arrDays[date('N',time()-3*24*3600)],
                       5=> $arrDays[date('N',time()-2*24*3600)],
                       6=> $arrDays[date('N',time()-24*3600)],
                       7=> $arrDays[date('N')]);

        //var_dump($arrJ);
        $arrJfinal = array();
        foreach($arrJ as $key=>$val)
        {
            array_push($arrJfinal,$val);
        }
        for($i=7;$i>-1;$i--)
        {
            if (!is_numeric($retArr[$i]["qty"]))
            {
                $retArr[$i]["qty"]=0;
            }
            array_push($arr2,$retArr[$i]["qty"]);
        }


          $arr = array();
          $arr['title']=array('text' =>  "Insription", "style" => array("font-size" => "20px", "color" => "#0000ff" , "font-family" => "Verdana", "text-align" => "center"));
          $arr['y_legend']=array('text' =>  "Open Flash Chart", "style" => array("color" => "#736AFF",  "font-size" => "12px"));
          $arr['elements']=array (array('type' => "line",
                                 'alpha' => 0.5,
                                 'colour' => "#9933CC",
                                 'text' => "Nb client",
                                 'font-size' => 10,
                                 'values' => $arr2,
                                 ),
                                 );
          $arr['x_axis']=array('stroke' =>  1,
                               "tick_height" => 10,
                               "colour" => "#736AFF",
                               "grid_colour" => "#00ff00",
                               "labels" => array("labels" => $arrJfinal));
          $arr['y_axis']=array('stroke' =>  4,
                               "tick_height" => 3,
                               "colour" => "#d000d0",
                               "grid_colour" => "#00ff00",
                               "offset" => 0,
                               "max" => 10);

        echo json_encode($arr);


    }
    break;
    case "customermonth":
    {
        $res = $mag1->customer_list();

        $now = time();
        $last = strtotime('-1 month',$now);
        $retArr=array();
        foreach($res as $key=>$val)
        {
            if (strtotime($val['created_at']) > $last)
            {
                $diff = $now - strtotime($val['created_at']);
                $jour = intval($diff / (24*3600));
                if (!is_numeric($retArr[$jour]['qty']))
                {
                    $retArr[$jour]["qty"]=0;
                }
                $retArr[$jour]["qty"] ++;
            }
        }
        // on distribue dans des arrays
        //on affiche

        //var_dump($arrJ);
        $arrJfinal = array();
        $arr2 = array();

        for($i=31;$i>=0;$i--)
        {
            if ($i%2 == 1)
            {
                array_push($arrJfinal,date("d/m ",strtotime('-'.$i.' day',$now)));
            } else {
                array_push($arrJfinal," ");
            }
            if (!is_numeric($retArr[$i]["qty"]))
            {
                $retArr[$i]["qty"]=0;
            }
            array_push($arr2,$retArr[$i]["qty"]);
        }
          $arr = array();
          $arr['title']=array('text' =>  "Insription", "style" => array("font-size" => "20px", "color" => "#0000ff" , "font-family" => "Verdana", "text-align" => "center"));
          $arr['y_legend']=array('text' =>  "Nb client", "style" => array("color" => "#736AFF",  "font-size" => "12px"));
          $arr['elements']=array (array('type' => "line",
                                 'alpha' => 0.5,
                                 'colour' => "#9933CC",
                                 'text' => "Nb client",
                                 'font-size' => 10,
                                 'values' => $arr2,
                                 ),
                                 );
          $arr['x_axis']=array('stroke' =>  1,
                               "tick_height" => 10,
                               "colour" => "#736AFF",
                               'font-size' => 6,
                               "grid_colour" => "#00ff00",
                               "labels" => array("labels" => $arrJfinal));
          $arr['y_axis']=array('stroke' =>  4,
                               "tick_height" => 3,
                               "colour" => "#d000d0",
                               "grid_colour" => "#00ff00",
                               "offset" => 0,
                               "max" => 10);

        echo json_encode($arr);


    }
    break;
    case "customeryear":
    {
        $res = $mag1->customer_list();

        $now = time();
        $last = strtotime('-1 year',$now);
        $retArr=array();
        foreach($res as $key=>$val)
        {
            if (strtotime($val['created_at']) > $last)
            {
                $month = date('mY',strtotime($val['created_at']));
                if (!is_numeric($retArr[$month]['qty']))
                {
                    $retArr[$month]["qty"]=0;
                }
                $retArr[$month]["qty"] ++;
            }
        }
        // on distribue dans des arrays
        //on affiche

        $arrJfinal = array();
        $arr2 = array();

        for($i=12;$i>=0;$i--)
        {
            array_push($arrJfinal,date("m/Y",strtotime('-'.$i.' month',$now)));
            $dateIdx = date("mY",strtotime('-'.$i.' month',$now));
            if (!is_numeric($retArr[$dateIdx]["qty"]))
            {
                $retArr[$dateIdx]["qty"]=0;
            }
            array_push($arr2,$retArr[$dateIdx]["qty"]);
        }
          $arr = array();
          $arr['title']=array('text' =>  "Insription", "style" => array("font-size" => "20px", "color" => "#0000ff" , "font-family" => "Verdana", "text-align" => "center"));
          $arr['y_legend']=array('text' =>  "Nb client", "style" => array("color" => "#736AFF",  "font-size" => "12px"));
          $arr['elements']=array (array('type' => "line",
                                 'alpha' => 0.5,
                                 'colour' => "#9933CC",
                                 'text' => "Nb client",
                                 'font-size' => 10,
                                 'values' => $arr2,
                                 ),
                                 );
          $arr['x_axis']=array('stroke' =>  1,
                               "tick_height" => 10,
                               "colour" => "#736AFF",
                               'font-size' => 6,
                               "grid_colour" => "#00ff00",
                               "labels" => array("labels" => $arrJfinal));
          $arr['y_axis']=array('stroke' =>  4,
                               "tick_height" => 3,
                               "colour" => "#d000d0",
                               "grid_colour" => "#00ff00",
                               "offset" => 0,
                               "max" => 10);

        echo json_encode($arr);


    }
    break;
    case "customerall":
    {
        $res = $mag1->customer_list();

        $now = time();
        $retArr=array();
        foreach($res as $key=>$val)
        {
                $month = date('Y',strtotime($val['created_at']));
                if (!is_numeric($retArr[$month]['qty']))
                {
                    $retArr[$month]["qty"]=0;
                }
                $retArr[$month]["qty"] ++;
        }
        // on distribue dans des arrays
        //on affiche

        $arrJfinal = array();
        $arr2 = array();

        for($i=4;$i>=0;$i--)
        {
            array_push($arrJfinal,date("Y",strtotime('-'.$i.' year',$now)));
            $dateIdx = date("Y",strtotime('-'.$i.' year',$now));
            if (!is_numeric($retArr[$dateIdx]["qty"]))
            {
                $retArr[$dateIdx]["qty"]=0;
            }
            array_push($arr2,$retArr[$dateIdx]["qty"]);
        }
          $arr = array();
          $arr['title']=array('text' =>  "Insription", "style" => array("font-size" => "20px", "color" => "#0000ff" , "font-family" => "Verdana", "text-align" => "center"));
          $arr['y_legend']=array('text' =>  "Nb client", "style" => array("color" => "#736AFF",  "font-size" => "12px"));
          $arr['elements']=array (array('type' => "bar",
                                 'alpha' => 0.5,
                                 'colour' => "#9933CC",
                                 'text' => "Nb client",
                                 'font-size' => 10,
                                 'values' => $arr2,
                                 ),
                                 );
          $arr['x_axis']=array('stroke' =>  1,
                               "tick_height" => 10,
                               "colour" => "#736AFF",
                               'font-size' => 6,
                               "grid_colour" => "#00ff00",
                               "labels" => array("labels" => $arrJfinal));
          $arr['y_axis']=array('stroke' =>  4,
                               "tick_height" => 3,
                               "colour" => "#d000d0",
                               "grid_colour" => "#00ff00",
                               "offset" => 0,
                               "max" => 10);

        echo json_encode($arr);


    }
    break;

//Products
    case  'productweek':
    {
        $res = $mag->sales_list();

        $now = time();
        $last = strtotime('-1 week',$now);
        $retArr=array();
        foreach($res as $key=>$val)
        {
//sales infos
//foreach items

            if (strtotime($val['created_at']) > $last)
            {
                $diff = $now - strtotime($val['created_at']);
                $jour = intval($diff / (24*3600));
                if (!is_numeric($retArr[$jour]['qty']))
                {
                    $retArr[$jour]["qty"]=0;
                }
                $retArr[$jour]["qty"] ++;
            }
        }
        // on distribue dans des arrays
        //on affiche
        $arrDays[-1]="Sam.";
        $arrDays[0]="Dim.";
        $arrDays[1]="Lun.";
        $arrDays[2]="Mar.";
        $arrDays[3]="Mer.";
        $arrDays[4]="Jeu.";
        $arrDays[5]="Ven.";
        $arrDays[6]="Sam.";
        $arrDays[7]="Dim.";
        $arrDays[7]="Lun.";

        $arr2 = array();
        $arr1 = array();
        $arrJ = array(
                       0=> $arrDays[date('N',time()-7*24*3600)],
                       1=> $arrDays[date('N',time()-6*24*3600)],
                       2=> $arrDays[date('N',time()-5*24*3600)],
                       3=> $arrDays[date('N',time()-4*24*3600)],
                       4=> $arrDays[date('N',time()-3*24*3600)],
                       5=> $arrDays[date('N',time()-2*24*3600)],
                       6=> $arrDays[date('N',time()-24*3600)],
                       7=> $arrDays[date('N')]);

        //var_dump($arrJ);
        $arrJfinal = array();
        foreach($arrJ as $key=>$val)
        {
            array_push($arrJfinal,$val);
        }
        for($i=7;$i>-1;$i--)
        {
            if (!is_numeric($retArr[$i]["qty"]))
            {
                $retArr[$i]["qty"]=0;
            }
            array_push($arr2,$retArr[$i]["qty"]);
        }


          $arr = array();
          $arr['title']=array('text' =>  "Insription", "style" => array("font-size" => "20px", "color" => "#0000ff" , "font-family" => "Verdana", "text-align" => "center"));
          $arr['y_legend']=array('text' =>  "Open Flash Chart", "style" => array("color" => "#736AFF",  "font-size" => "12px"));
          $arr['elements']=array (array('type' => "line",
                                 'alpha' => 0.5,
                                 'colour' => "#9933CC",
                                 'text' => "Nb client",
                                 'font-size' => 10,
                                 'values' => $arr2,
                                 ),
                                 );
          $arr['x_axis']=array('stroke' =>  1,
                               "tick_height" => 10,
                               "colour" => "#736AFF",
                               "grid_colour" => "#00ff00",
                               "labels" => array("labels" => $arrJfinal));
          $arr['y_axis']=array('stroke' =>  4,
                               "tick_height" => 3,
                               "colour" => "#d000d0",
                               "grid_colour" => "#00ff00",
                               "offset" => 0,
                               "max" => 10);

        echo json_encode($arr);

    }
    break;
}


$mag->disconnect();

?>
