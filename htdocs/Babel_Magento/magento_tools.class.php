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
class magento_tools {
    public $db;

    public function magento_tools($db) {
        $this->db = $db;
    }

    public $catGLEArr = array();
    public function listCatGLE($force=false)
    {
        if (!$force || count($this->catGLEArr) == 0)
        {
            $requete = "SELECT * FROM babel_categorie";
            $sql = $this->db->query($requete);
            while ($res = $this->db->fetch_object($sql))
            {
                $this->catGLEArr["GLEID"][$res->rowid]=$res->label;
                if ("x".$res->magento_id != "x")
                   $this->catGLEArr["MAGID"][$res->magento_id]=$res->label;
            }
        }
    }
    public function testSyncCatMagToGLE($arr)
    {
        $atLeastOneFound = false;
        //TODO ajouter active, position, level, parentid
        foreach($this->catGLEArr['MAGID'] as $key=>$val)
        {
//            print $key . " ".$arr['MagcatId']."<br>".$arr['name'] ." ". $val."<br>";
            if ($arr['MagcatId'] == $key && $arr['name'] == $val )
            {
                //Sync OK
                $atLeastOneFound = true;
                return('OK');
            } else if ($arr['MagcatId'] == $key && $arr['name'] != $val )
            {
                //remonte une alerte :> on update
                return('UPDATE');
            }
        }
        if (!$atLeastOneFound)
        {
            //remonte une alerte :> on insert
            return ('INSERT');
        }
    }
    //idem a dessus, sauf que l'Arr est filtre
    public function syncCatMagToGLE($arr)
    {
        $atLeastOneFound = false;
        foreach($this->catGLEArr['MAGID'] as $key=>$val)
        {
            if ($arr['MagcatId'] == $key && $arr['name'] == $val )
            {
                //Sync OK :> ne devrait pas arriver car arr filtre
                $atLeastOneFound = true;
                break;
            } else if ($arr['MagcatId'] == $key && $arr['name'] != $val )
            {
                $requete = "UPDATE  babel_categorie set name='".$val."' WHERE magento_id=".$arr['MagcatId'];
                //on update
            }
        }
        if (!$atLeastOneFound)
        {
            //On insert
            $requete = "INSERT INTO babel_categorie (label, description, visible, type, magento_id, position, level, magento_product)
                                VALUES              ('".$arr['name']."','Magento: ".$arr['name']."',1,0,".$arr['MagcatId'].",".$arr['position'].",".$arr['level'].",1 )";
        }
    }
    public $prodGLEArr = array();
    public function listProdGLE($force=false)
    {
        if (!$force || count($this->prodGLEArr) == 0)
        {
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."product";
            $sql = $this->db->query($requete);
            while ($res = $this->db->fetch_object($sql))
            {
                $this->prodGLEArr["GLEID"][$res->rowid]=$res->label;
                if ("x".$res->magento_id != "x")
                   $this->prodGLEArr["MAGID"][$res->magento_id]=$res->label;
            }
        }
    }
    public $modeProd=array();
    public function testSyncProdToGLE($arr,$key)
    {
        $db=$this->db;
        foreach ($arr as $prodID => $prodArr)
        {
            //$prodInfo = $mag->prod_prod_info($prodID);
            //var_dump::display($prodInfo);
            //Verifie si le produit existe
            $sku = $prodArr['sku'];
            $name = $prodArr['name'];
            $id = $prodArr['id'];
            $type = $prodArr['type'];
            $requete = "SELECT count(*) as cnt
                          FROM ".MAIN_DB_PREFIX."product
                         WHERE label = '".preg_replace("/'/","\\'",$name). "'
                           AND magento_sku = '".$sku. "'
                           AND magento_type = '".$type. "'
                           AND magento_cat = '".$key. "'
                           AND magento_id = '".$prodID. "'"    ;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);

            if ($res->cnt > 0)
            {
                $mode = 'OK';
                $this->modeProd[$id]['mode']=$mode;
            $this->modeProd[$id]['sku']=$sku;
                $this->modeProd[$id]['name']=$name;
                $this->modeProd[$id]['id']=$id;
            } else {
                $requete = "SELECT count(*) as cnt
                              FROM ".MAIN_DB_PREFIX."product
                             WHERE label = '".preg_replace("/'/","\\'",$name). "'
                                OR magento_sku = '".$sku. "'
                                OR (magento_id = '".$id. "' AND magento_type = '".$type. "')
                                OR (magento_sku = '".$sku. "' AND magento_type = '".$type. "')
                                OR magento_id = '".$id. "'
                                OR (magento_cat = '".$key. "' AND magento_id= '".$id. "')
                                OR (magento_cat = '".$key. "' AND magento_sku= '".$sku. "')"    ;
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                if ($res->cnt > 0)
                {
                    $mode = 'UPDATE';
                    $this->modeProd[$id]['mode']=$mode;
                    $this->modeProd[$id]['name']=$name;
                $this->modeProd[$id]['sku']=$sku;
                    $this->modeProd[$id]['id']=$id;
                } else {
                    $mode = 'INSERT';
                    $this->modeProd[$id]['mode']=$mode;
                $this->modeProd[$id]['sku']=$sku;
                    $this->modeProd[$id]['name']=$name;
                    $this->modeProd[$id]['id']=$id;
                }
            }
        }
    }

//TODO :> ajouter la ref produit
//1) Faire une meth qui donne/tock la liste des produits de GLE
//2) Faire une sub pour la synchro :> soit le produit existe dans GLE => demande utilisateur, soir pas present, et synchro
//3) Faire une sub pour la vrai synchro :> on ajoute le produit dans GLE


}
?>