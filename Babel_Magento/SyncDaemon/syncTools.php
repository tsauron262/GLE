<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once("../magento_sales.class.php");
require_once("../magento_product.class.php");
require_once("../magento_customer.class.php");
require_once("../../main.inc.php");

//status : 0=> a synchro, 1 en cours desynchro, 2 synchro OK, 3 error
//mode   : 0=> insert 1=> update 2=> delete

//TODO :> categorie

$magSal=new magento_sales($conf);
$magProd=new magento_product($conf);
$magCust=new magento_customer($conf);

$magSal->connect();




function get_last_op($db)
{
    $requete = "SELECT ifnull(last_cust_IncId,0) as last_cust_IncId ,
                       ifnull(last_prod_IncId,0) as last_prod_IncId ,
                       ifnull(last_sale_IncId,0) as last_sale_IncId,
                       ifnull(previous_check,0) as previous_check
                  FROM last_op";
    $sql=$db->query($requete);
    if($db->num_rows($sql) > 0)
    {
        $res=$db->fetch_object($sql);
        return (array("cust"=> $res->last_cust_IncId, "prod" => $res->last_prod_IncId, "sale" => $res->last_sale_IncId, "previous_check" => $res->previous_check));
    } else {
        $res=$db->fetch_object($sql);
        return (array("cust"=> 0, "prod" =>0, "sale" => 0));
    }
}

 $dbGle = $db;
 $db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,"gleMagentoSpool",$conf->db->port);

//Update last check timestamp
$requete = "UPDATE last_op SET  previous_check=last_check, last_check = date_sub(now(), interval ".intval(date("O"))/100 ." hour)";
$db->query($requete);

$lastArr= get_last_op($db);
//get what's new
//=> sales
$createArr=array();
$createArr['sales']=array();
//print "<br>". $lastArr["sale"] ."<br>";
    //=> liste des ventes dont l'increment_id > $lastArr
    $list=$magSal->sales_list_incId_gt($lastArr["sale"]);
    //=> stock dans le spool DB => mode 0, status 0,
    foreach($list as $key=>$val)
    {
        //parse la liste, prend les Id, le timestamp de modif/creation
        $mode= 0; //insert
        $status=0; //a synchroniser

        $magento_id = $val['increment_id'];
        $increment_id = $val['increment_id'];
        $last_update = $val['updated_at'];
        array_push($createArr['sales'],$magento_id);
        //insert into spool

        $db->begin();
        $requeteSpool = "INSERT INTO spool(magento_id,magento_type,status,tms,mode) VALUES ('$magento_id','sales',$status,'$last_update',$mode)";
        $resSpool=$db->query($requeteSpool);
        if($resSpool)
        {
            //=> insert dans last_op_sales
            $requete = "INSERT INTO last_op_sales(magento_id,timelastmodif) VALUES ('$magento_id','$last_update')";
            $res = $db->query($requete);
            if ($res)
            {
                $requete = "UPDATE last_op SET last_sale_IncId = ".$magento_id;
                $res1 = $db->query($requete);
                if($res1)
                {
                    $db->commit();
                }else{
                    $db->rollback();
                }
                //OK
            } else {
                $db->rollback();
                print "Err on sale ref ".$magento_id."<br>";
                print $db->lasterrno."<br>";
                print $db->lastquery."<br>";
                //log error for post treatment
            }
        } else {
            $db->rollback();
            print $db->lasterrno."<br>";
            print $db->lastquery."<br>";
            var_dump($db);
            //log error for post treatment
        }
    }

$createArr['cust']=array();

$magCust->client = $magSal->client;
$magCust->session = $magSal->session;
//=> customers
    //=> liste des clients dont l'increment_id > $lastArr
    $list=$magCust->cust_list_incId_gt($lastArr["cust"]);
    //=> stock dans le spool DB => mode 0, status 0,
    foreach($list as $key=>$val)
    {
        //parse la liste, prend les Id, le timestamp de modif/creation
        $mode= 0; //insert
        $status=0; //a synchroniser

        $magento_id = $val['customer_id'];
        $increment_id = $val['increment_id'];
        $last_update = $val['updated_at'];
        array_push($createArr['cust'],$magento_id);

        //insert into spool

        $db->begin();
        $requeteSpool = "INSERT INTO spool(magento_id,magento_type,status,tms,mode)
                              VALUES ('$magento_id','cust',$status,'$last_update',$mode)";
        $resSpool=$db->query($requeteSpool);
        if($resSpool)
        {
            //=> insert dans last_op_sales
            $requete = "INSERT INTO last_op_cust(magento_id,timelastmodif)
                             VALUES ('$magento_id','$last_update')";
            $res = $db->query($requete);
            if ($res)
            {
                $requete = "UPDATE last_op
                               SET last_cust_IncId = ".$magento_id;
                $res1 = $db->query($requete);
                if($res1)
                {
                    $db->commit();
                }else{
                    $db->rollback();
                }
                //OK
            } else {
                $db->rollback();
                print "Err on sale ref ".$magento_id."<br>";
                print $db->lasterrno."<br>";
                print $db->lastquery."<br>";
                //log error for post treatment
            }
        } else {
            $db->rollback();
            print $db->lasterrno."<br>";
            print $db->lastquery."<br>";
            //log error for post treatment
        }
    }
    //=> stock dans le spool DB =>mode0,status 0
    //=> insert dans last_op_cust
//=> products
    //=> liste des produits dont l'increment_id > $lastArr
    $magSal->sales_list_incId_gt($lastArr["prod"]);

$magProd->client = $magSal->client;
$magProd->session = $magSal->session;
//=> customers
    //=> liste des clients dont l'increment_id > $lastArr
    $list=$magProd->prod_list_incId_gt($lastArr["prod"]);
    //=> stock dans le spool DB => mode 0, status 0,
$createArr['prod']=array();

    foreach($list as $key=>$val)
    {
        //parse la liste, prend les Id, le timestamp de modif/creation
        $mode= 0; //insert
        $status=0; //à synchroniser

        $magento_id = $val['product_id'];
        array_push($createArr['prod'],$magento_id);
        $increment_id = $val['product_id'];
        //insert into spool
        $infoProd=$magProd->prod_prod_info($magento_id);
        $last_update=$infoProd['updated_at'];

        $db->begin();
        $requeteSpool = "INSERT INTO spool(magento_id,magento_type,status,tms,mode) VALUES ('$magento_id','prod',$status,'$last_update',$mode)";
        $resSpool=$db->query($requeteSpool);
        if($resSpool)
        {
            //=> insert dans last_op_sales
            $requete = "INSERT INTO last_op_product(magento_id,timelastmodif) VALUES ('$magento_id','$last_update')";
            $res = $db->query($requete);
            if ($res)
            {
                $requete = "UPDATE last_op SET last_prod_IncId = ".$magento_id;
                $res1 = $db->query($requete);
                if($res1)
                {
                    $db->commit();
                }else{
                    $db->rollback();
                }
                //OK
            } else {
                $db->rollback();
                print "Err on sale ref ".$magento_id."<br>";
                print $db->lasterrno."<br>";
                print $db->lastquery."<br>";
                //log error for post treatment
            }
        } else {
            $db->rollback();
            print $db->lasterrno."<br>";
            print $db->lastquery."<br>";
            //log error for post treatment
        }
    }
    //=> stock dans le spool DB => mode0,status 0
    //=> insert dans last_op_prod


//get what's updated
//=> sales
    //=> liste des ventes depuis dernier check

    //=> compare avec last_op_sales
    $list1 = $magSal->sales_list_updated_gt($lastArr['previous_check']);

    foreach($list1 as $key=>$val)
    {
        //exclude tout ceux qui sont en mode create
        if (! in_array($val['increment_id'],$createArr['sales']) )
        {
            $mode=1;
            $status=0;
            $magento_id = $val['increment_id'];
            $increment_id = $val['increment_id'];
            $last_update = $val['updated_at'];

            //Set to be updated
                    $db->begin();
                    $requeteSpool = "INSERT INTO spool(magento_id,magento_type,status,tms,mode) VALUES ('$magento_id','sales',$status,'$last_update',$mode)";
                    $resSpool=$db->query($requeteSpool);
                    if($resSpool)
                    {
                        //=> insert dans last_op_sales
                        $requete = "UPDATE last_op_sales set timelastmodif='$last_update' WHERE magento_id = '$magento_id' ";
                        $res = $db->query($requete);
                        if ($res)
                        {
                            $db->commit();
                        } else {
                            print "<br>Err<br>";
                            $db->rollback();
                            print $db->lasterrno."<br>";
                            print $db->lastquery."<br>";
                        }

                    } else {
                        print "<br>Err<br>";
                        $db->rollback();
                        print $db->lasterrno."<br>";
                        print $db->lastquery."<br>";
                    }
        }
    }
    //=> si difference  stock dans le spool DB => mode 1, status 0
//=> customers
    //=> liste des clients
    $list1 = $magCust->cust_list_updated_gt($lastArr['previous_check']);


    foreach($list1 as $key=>$val)
    {
        //exclude tout ceux qui sont en mode create
        if (! in_array($val['increment_id'],$createArr['cust']) )
        {
            $mode=1;
            $status=0;
            $magento_id = $val['increment_id'];
            $increment_id = $val['increment_id'];
            $last_update = $val['updated_at'];

            //Set to be updated
                    $db->begin();
                    $requeteSpool = "INSERT INTO spool(magento_id,magento_type,status,tms,mode)
                                          VALUES ('$magento_id','cust',$status,'$last_update',$mode)";
                    $resSpool=$db->query($requeteSpool);
                    if($resSpool)
                    {
                        //=> insert dans last_op_sales
                        $requete = "UPDATE last_op_cust
                                       SET timelastmodif='$last_update'
                                     WHERE magento_id = '$magento_id' ";
                        $res = $db->query($requete);
                        if ($res)
                        {
                            $db->commit();
                        } else {
                            print "<br>Err<br>";
                            $db->rollback();
                            print $db->lasterrno."<br>";
                            print $db->lastquery."<br>";
                        }

                    } else {
                        print "<br>Err<br>";
                        $db->rollback();
                        print $db->lasterrno."<br>";
                        print $db->lastquery."<br>";
                    }
        }
    }
//=> product
$list1 = $magProd->prod_list_updated_gt($lastArr['previous_check']);
    foreach($list1 as $key=>$val)
    {
        //exclude tout ceux qui sont en mode create
        if (! in_array($val['product_id'],$createArr['cust']) )
        {
            $mode=1;
            $status=0;
            $magento_id = $val['product_id'];
            $increment_id = $val['product_id'];


        $infoProd=$magProd->prod_prod_info($magento_id);
        $last_update=$infoProd['updated_at'];


            //Set to be updated
                    $db->begin();
                    $requeteSpool = "INSERT INTO spool(magento_id,magento_type,status,tms,mode)
                                          VALUES ('$magento_id','prod',$status,'$last_update',$mode)";
                    $resSpool=$db->query($requeteSpool);
                    if($resSpool)
                    {
                        //=> insert dans last_op_sales
                        $requete = "UPDATE last_op_cust
                                       SET timelastmodif='$last_update'
                                     WHERE magento_id = '$magento_id' ";
                        $res = $db->query($requete);
                        if ($res)
                        {
                            $db->commit();
                        } else {
                            print "<br>Err<br>";
                            $db->rollback();
                            print $db->lasterrno."<br>";
                            print $db->lastquery."<br>";
                        }

                    } else {
                        print "<br>Err<br>";
                        $db->rollback();
                        print $db->lasterrno."<br>";
                        print $db->lastquery."<br>";
                    }
        }
    }
//insert into spool db

require_once('../magento_customer.class.php');
//$mag = new magento_customer();
//$mag->connect();
//$list = $mag->customer_list();
//var_dump($list);

//call web services thought send_to_WS if something new


$POST['callSync']="<magentoCall>
    <access>
     <login>
          md5Hash
     </login>
     <pass>
          md5Hash
     </pass>
    </access>
    <message>
          syncspool
    </message>
  </magentoCall>";
send_to_WS("http://127.0.0.1/GLE-1.2/main/htdocs/Babel_Magento/SyncDaemon/magento_import.php",$POST);

function send_to_WS($url,$POST)
{
  $parsed_url=parse_url($url); //GET
  $POST_Arr = array(); //POST
    foreach ($POST as $key => &$val) {
          if (is_array($val)) $val = implode(',', $val);
            $POST_Arr[] = $key.'='.urlencode($val);
        }
        $post_string = implode('&', $POST_Arr);

  $sock = fsockopen($parsed_url['host'],
          isset($parsed_url['port'])?$parsed_url['port']:80,
          $errno, $errstr, 30);

  if (!$sock) {
      return false;
  } else {
      $out = "POST ".$parsed_url['path']." HTTP/1.1\r\n";
      $out.= "Host: ".$parsed_url['host']."\r\n";
      $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
      $out.= "Content-Length: ".strlen($parsed_url['query'].$post_string)."\r\n";
      $out.= "Connection: Close\r\n\r\n";
      if (isset($parts['query'])) $out.= $parsed_url['query'];
      if (strlen($post_string)>0) $out.= $post_string;
      fwrite($sock, $out);
      fclose($sock);
      return $sock;
  }
}

?>