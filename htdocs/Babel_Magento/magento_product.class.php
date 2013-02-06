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

require_once('magento_soap.class.php');
class magento_product extends magento_soap{

    function magento_product($conf) {
        $MagentoSoapUrl = $conf->global->MAGENTO_PROTO."://".$conf->global->MAGENTO_HOST;
        if ($conf->global->MAGENTO_PROTO == 'http' && $conf->global->MAGENTO_PORT != 80)
        {
            $MagentoSoapUrl .= ":".$conf->global->MAGENTO_PORT;
        }
        if ($conf->global->MAGENTO_PROTO == 'https' && $conf->global->MAGENTO_PORT != 443)
        {
            $MagentoSoapUrl .= ":".$conf->global->MAGENTO_PORT;
        }
        $MagentoSoapUrl .= $conf->global->MAGENTO_PATH;
        $this->MagentoSoapUrl= $MagentoSoapUrl;
        $this->username = $conf->global->MAGENTO_USER;
        $this->pass = $conf->global->MAGENTO_PASS;

    }
    public function cat_prod_list()
    {
        $result = $this->call_magento("catalog_product.list");
        return ($result);
    }
    public function cat_prod_info($catId)
    {
        $arr = array();
        array_push($arr,$catId);
        $result = $this->call_magento("catalog_category.info",$arr);
        return ($result);

    }
    public function prod_prod_list()
    {
        $result = $this->call_magento("product.list");
        return ($result);
    }
    public function prod_prod_info($prodId)
    {
        $arr = array();
        array_push($arr,$prodId);
        $result = $this->call_magento("product.info",$arr);
        return ($result);
    }

    public function prod_attribute_set()
    {
        $result = $this->call_magento("catalog_product_attribute_set.list");
        return ($result);

    }

    public function prod_cat_list()
    {
        $result = $this->call_magento("catalog_category.tree");
        return ($result);
    }
    public function prod_in_cat($catId)
    {
        $arr = array();
        array_push($arr,$catId);
        array_push($arr,1);
        $result = $this->call_magento("category.assignedProducts",$arr);
        return ($result);
    }

    public function prod_cat_get_stock($prodSkuOrId)
    {
        $arr = array();
        array_push($arr,$prodSkuOrId);
        $result = $this->call_magento("product_stock.list",$arr);
        return ($result);
    }

    public function prod_cat_updt_stock($prodSkuOrId,$qty,$is_inStock=1)
    {
//        'qty'=>50, 'is_in_stock'=>1
        $arr = array();
        array_push($arr,$prodSkuOrId);
        array_push($arr,array('qty' => $qty, 'is_in_stock' => $is_inStock));
        $result = $this->call_magento("product_stock.update",$arr);
        return ($result);
    }

    public $ProdByCatArr = false;
    public function parseProdList($rs)
    {
        $this->ProdByCatArr = array();
        foreach($rs as $key=>$val)
        {
            if (is_array($val['category_ids']) && count($val['category_ids']) >0)
            {
                foreach($val['category_ids'] as $tmpid => $catArr)
                {
                    $this->ProdByCatArr[$catArr][$val['product_id']]['name']=$val['name'];
                    $this->ProdByCatArr[$catArr][$val['product_id']]['sku']=$val['sku'];
                    $this->ProdByCatArr[$catArr][$val['product_id']]['id']=$val['product_id'];
                    $this->ProdByCatArr[$catArr][$val['product_id']]['type']=$val['type'];
                }
            } else {
                //Product has no category
                $this->ProdByCatArr[1][$val['product_id']]['name']=$val['name'];
                $this->ProdByCatArr[1][$val['product_id']]['sku']=$val['sku'];
                $this->ProdByCatArr[1][$val['product_id']]['id']=$val['product_id'];
                $this->ProdByCatArr[1][$val['product_id']]['type']=$val['type'];
            }
        }
    }

    public $jsonArr = array();
    public $catNameArr = array();
    public function parseCat($arr)
    {
//        var_dump::Display($arr);
        $this->jsonArr["id"]=$arr['category_id'];
        $this->jsonArr["name"]=$arr['name'];
        $this->jsonArr["data"]=array('id' => $arr['category_id']);
        $this->jsonArr['children']=array();
        $this->catNameArr[$arr['category_id']]=$arr['name'];
        $curcat = 1;
            if ($this->ProdByCatArr)
            {
                foreach($this->ProdByCatArr as $key1=>$val1)
                {
                    if ($key1 == $curcat)
                    {
                        //add a children
                        foreach($val1 as $key2 => $val2)
                        {
                            array_push($this->jsonArr['children'], array('id' => "p".$key2,
                                                                'data' => array('sku' =>$val2["sku"]),
                                                                'name' =>$val2['name'],
                                                                'children' => array()));
                        }
                    }
                }
            }

        $this->parseChildrenCat($arr['children'],$this->jsonArr["children"],$arr['children']['category_id']);
        //add prod datas


    }
    public function parseChildrenCat($arr,&$arr2,$curcat)
    {
        //pour chaque categorie, on a un children
        foreach( $arr as $key => $val)
        {
            array_push($arr2, array("id" => "c".$val['category_id'],
                                    "data" =>array() ,
                                    "name" => $val['name'],
                                    "children" => array()));
            $this->catNameArr[$val['category_id']]=$val['name'];
            $idx=count($arr2) - 1;
            //Si il y a un subcat√©gorie :
            if (is_array($val['children']) && count($val['children'] > 0))
            {
                $this->parseChildrenCat($val['children'],$arr2[$idx]["children"],$val['children']['category_id']);
            }
            //pour chaque categorie, on ajoute les produits
            if (is_array($this->ProdByCatArr[$val['category_id']]) && count($this->ProdByCatArr[$val['category_id']]) > 0)
            {
                foreach( $this->ProdByCatArr[$val['category_id']] as $key => $val1)
                {
                    array_push($arr2[$idx]['children'], array("id" => "p".$key."-c".$val["category_id"],
                                            "data" =>$val1["sku"],
                                            "name" => $val1['name'],
                                            "children" => array()));
                }
            }
        }
    }

    public $catArr = array();
    public function parseProdCat($arr)
    {
        $this->catArr[$arr['category_id']]['MagcatId']= $arr['category_id'];
        $this->catArr[$arr['category_id']]['name'] = $arr['name'];
        $this->catArr[$arr['category_id']]['is_active'] = $arr['is_active'];
        $this->catArr[$arr['category_id']]['position'] = $arr['position'];
        $this->catArr[$arr['category_id']]['level'] = $arr['level'];
        $this->catArr[$arr['category_id']]['parent_id'] = $arr['parent_id'];
        if (is_array($arr['children']))
        {
            foreach($arr['children'] as $key=>$val)
            {
                $this->parseProdCat($val);
            }
        }
    }

    public $gleCatArr = array();
    private $db;
    public function parseCatGLE($db)
    {
        $this->db=$db;
        $requete = "SELECT *
                      FROM babel_categorie
                      WHERE level = 0";
        $sql = $this->db->query($requete);
        while ($res = $db->fetch_object($sql))
        {
            $this->gleCatArr['name']=utf8_encode($res->label);
            $this->gleCatArr['category_id']=utf8_encode($res->rowid);
            $this->gleCatArr['parent_id']=0;
            $this->gleCatArr['is_active']=utf8_encode($res->visible);
            $this->gleCatArr['position']=utf8_encode($res->position);
            $this->gleCatArr['level']=utf8_encode($res->level);
            $this->gleCatArr['rowid']=utf8_encode($res->level);
            //cherche les enfants
            $requete = "SELECT *
                          FROM ".MAIN_DB_PREFIX."categorie_association
                         WHERE fk_categorie_mere =".$res->rowid;
            $sql1 = $db->query($requete);
//            $this->gleCatArr[]["children"]=array();
            if ($db->num_rows($sql1) > 0)
            {
                while ($res1 = $db->fetch_object($sql1))
                {
                    $this->parseCatGLEChild($res1->fk_categorie_fille,&$this->gleCatArr["children"],$res1->fk_categorie_mere);
                }
            }
        }
    }
    public function parseCatGLEChild($fille_id,&$catArr,$parentId)
    {

        $db=$this->db;
        $requete = "SELECT *
                  FROM babel_categorie
                  WHERE rowid = ".$fille_id;
        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql))
        {
            $catArr[]=array('name'=>utf8_encode($res->label),
                            'category_id'=>utf8_encode($res->rowid),
                            'parent_id'=>utf8_encode($parentId),
                            'is_active'=>utf8_encode($res->visible),
                            'rowid'=>utf8_encode($res->rowid),
                            'position'=>utf8_encode($res->position),
                            'level'=>utf8_encode($res->level)
                            );
            $idx=count($catArr) - 1;
            $requete = "SELECT *
                          FROM ".MAIN_DB_PREFIX."categorie_association
                         WHERE fk_categorie_mere =".$res->rowid;
            $sql1 = $db->query($requete);
            //$catArr['children']=array();
            if ($db->num_rows($sql1) > 0)
            {
                while ($res1 = $db->fetch_object($sql1))
                {
                    $this->parseCatGLEChild($res1->fk_categorie_fille,&$catArr[$idx]["children"],$res1->fk_categorie_mere);
                }
            }
        }

    }

    public function prod_list_incId_gt($incId=0)
    {
        $result = $this->call_magento("catalog_product.list",array(array('product_id' => array('gt'=>$incId))));
        return ($result);
    }
    public function prod_list_updated_gt($incId=0)
    {
        $result = $this->call_magento("catalog_product.list",array(array('updated_at' => array('gt'=>$incId))));
        return ($result);
    }


    public function createProdGle($prodInfo,$db)
    {
        require_once('../product.class.php');
        $user = new User($db);
        $user->fetch(1);
        require_once (DOL_DOCUMENT_ROOT."/product/class/product.class.php");
        $prod = new Product($db);
        $prod->magento_Sku = $prodInfo['sku'];
        $prod->magento_Id = $prodInfo['product_id'];
        $prod->magento_Type = $prodInfo['type'];
        $prod->magento_Cat = $prodInfo['categories'][0];

        $prod->ref = $prodInfo["sku"];
        $prod->libelle = $prodInfo["name"];
        //Prob TVA
        $tva = 19.6;
        if ($prodInfo["tax_class_id"]==1)
        {
            $tva = 19.6;
        }
        $prod->tva_tx = $tva;
        $prod->price = $prodInfo["price"];

        $prod->status = $prodInfo["status"];
        $prod->catid =  $prodInfo["categories"][0];
        $prod->magCat =  $prodInfo["categories"];
        $prod->description =  $prodInfo["description"];
        $prod->note =  $prodInfo["short_description"];
        //
        $prod->stock_loc =  "";
        $prod->type=1;
        //
        $prod->weight =  $prodInfo["weight"];
        $prod->weight_units =  "kg";
        $prod->volume =  "";
        $prod->volume_units = "";
        $id = $prod->create_magento($user);
        if ($id > 0)
        {
            $prod->id = $id;
            $prod->update_magento($id,$user);
        }
    }

    public function updateProdGle($prodInfo,$db)
    {
        $user = new User($db);
        $user->fetch(1);

        $magProdid = $prodInfo['product_id'];
        require_once (DOL_DOCUMENT_ROOT."/product/class/product.class.php");
        $prod = new Product($db);
        $requete = "SELECT  * FROM ".MAIN_DB_PREFIX."product WHERE magento_id =". $magProdid;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $gleId = $res->rowid;
        $prod->ref = $prodInfo["sku"];
        $prod->libelle = $prodInfo["name"];
        //Prob TVA
        $tva = 19.6;
        if ($prodInfo["tax_class_id"]==1)
        {
            $tva = 19.6;
        }
        $prod->tva_tx = $tva;
        $prod->price = $prodInfo["price"];
        $prod->status = $prodInfo["status"];
        $prod->catid =  $prodInfo["categories"][0];
        $prod->magCat =  $prodInfo["categories"];
        $prod->description =  $prodInfo["description"];
        $prod->note =  $prodInfo["short_description"];
        //
        $prod->stock_loc =  "";
        //
        $prod->weight =  $prodInfo["weight"];
        $prod->weight_units =  "kg";
        $prod->volume =  "";
        $prod->volume_units = "";
        $prod->id = $gleId;
        $test = $prod->update_magento($gleId,$user);
        if ($test == -2)
            print $prod->error;
        //TODO add to category
        //TODO Stock
    }
    public function deleteProdGle($prodInfo,$db)
    {
        $magProdid = $prodInfo['product_id'];
        $requete = "SELECT  *
                      FROM ".MAIN_DB_PREFIX."product
                     WHERE magento_id =". $magProdid;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $gleId = $res->rowid;
        $prod = new Product($db);
        $prod->id = $magProdid;
        //TODO delete photo
        $prod->delete_magento($magProdid);

    }


    public function createCategoryMagento($parent=1,$name,$store,$description="",$meta_description="",$meta_keywords=""){
//catalog_category.create => int $parentId - ID of parent category
                                //array $categoryData - category data ( array('attribute_code'=>'attribute_value' )
                                //mixed $storeView - store view ID or code (optional)
//        $arr = array();
//        array_push($arr,$prodSkuOrId);
//        array_push($arr,array('qty' => $qty, 'is_in_stock' => $is_inStock))
        $arr=array();
        array_push($arr,$parent);
        array_push($arr,array("name" => $name,
                              "is_active" => 1,
                              "is_anchor" =>1,
                              'description'=>$description,
                              'meta_description'=>$meta_description,
                              'meta_keywords'=>$meta_keywords,
                              'default_sort_by'=>'price',
                              'available_sort_by'=>'price',
                                ));
        //array_push($arr,$parent,$store);
        $result = $this->call_magento("catalog_category.create",$arr);
        return ($result);
    }
    public function createProductMagento($attrSet,$ref,$name,$shortDesc,$desc,$price,$weight,$status){

        $arr=array();

        array_push($arr,'simple');
        array_push($arr,$attrSet);
        array_push($arr,$ref);
        array_push($arr,array("name" => $name,
                              "websites" => array(1),
                              "short_description" => $shortDesc,
                              'description'=>$desc,
                              'weight' => $weight,
                              'status' => $status,
                              'price'=> $price));
        $result = $this->call_magento("catalog_product.create",$arr);
        return ($result);
    }
    public function deleteProductMagento($ref)
    {
        $arr=array();
        array_push($arr,$ref);
        $result = $this->call_magento("catalog_product.delete",$arr);
        return ($result);

    }
    public function upadteProductMagento($ref,$name,$shortDesc,$desc,$price,$weight,$status){

        $arr=array();

        array_push($arr,$ref);
        array_push($arr,array("name" => $name,
                              "websites" => array(1),
                              "short_description" => $shortDesc,
                              'description'=>$desc,
                              'weight' => $weight,
                              'status' => $status,
                              'price'=> $price));
        $result = $this->call_magento("catalog_product.create",$arr);
        return ($result);
    }

    public function createProductImage($sku,$label,$imgFile,$mime="image/jpeg",$type="image",$position=1)
    {
//        catalog_product_attribute_media.create
//Upload new product image
//
//Return: string - image file name
//
//Arguments:
//
//mixed product - product ID or code
//array data - image data. requires file content in base64, and image mime-type. Example: array('file' => array('content' => base64_encode($file), 'mime' => 'image/jpeg')
//mixed storeView - store view ID or code (optional)
//Aliases:
//
//product_attribute_media.create
//product_media.create
        $arr=array();
        array_push($arr,$sku);
        array_push($arr,array("file" => array("content" => base64_encode(file_get_contents($imgFile)), "mime" => $mime),
                              "label" => $label,
                              "position" => $position,
                              'types'=>array($type), // type small_image => thumb || image => main image
                              'exclude'=> 0));
        $result = $this->call_magento("catalog_product_attribute_media.create",$arr);
        return ($result);

    }
    public function assignCategory2ProductMagento($prodCat,$prodId,$position=false)
    {
        $arr=array();
        array_push($arr,$prodCat);
        array_push($arr,$prodId);
        if ($position) array_push($arr,$position);
        $result = $this->call_magento("catalog_category.assignProduct",$arr);
        return ($result);
    }


}
?>