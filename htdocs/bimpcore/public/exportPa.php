<?php


if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != "exportPA" || $_SERVER['PHP_AUTH_PW'] != "9DDrvuNcWRdKCcsdclhTe2scssqKVIV33I3" ) {
    header('WWW-Authenticate: Basic realm="Acces Export"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'FORBIDEN';
    exit;
} else {

header("Content-type: text/xml");
}



define("NOLOGIN", 1);  // This means this output page does not require to be logged.
require_once("../../main.inc.php");

$query = 'SELECT p.*, pf.price, `ref_fourn` FROM llx_product p LEFT JOIN `llx_product_fournisseur_price` pf ON `fk_product` = p.rowid GROUP BY p.`rowid`';
if(GETPOST('limit'))
    $query .= " LIMIT 0,".GETPOST ('limit');
$sql = $db->query($query);
$tabResult = array();
while ($ln = $db->fetch_object($sql)){
    $tabProd = array('price'=>$ln->price*1.03, 'ref'=>$ln->ref, 'code_bar'=>$ln->barcode, 'label'=>$ln->label, 'description'=>$ln->description, 'ref_fourn'=>$ln->ref_fourn);
    $tabResult[] = array('clef'=>'product', 'val'=>$tabProd);
}


    echo addElem("products", $tabResult);


function addElem($elem, $tabSousElemOrValue){
    $sortie = "<".$elem.">\n";
    if(is_array($tabSousElemOrValue)){
        foreach($tabSousElemOrValue as $elem2 => $tabSousElemOrValue2)
            if(!is_int($elem2))
                $sortie .= addElem ($elem2, $tabSousElemOrValue2);
            else
                $sortie .= addElem ($tabSousElemOrValue2['clef'], $tabSousElemOrValue2['val']);
    }
    else
        $sortie .= traiteStrForXml($tabSousElemOrValue)."\n";
    
    $sortie .= "</".$elem.">\n";
    return $sortie;
}

function traiteStrForXml($str){
    $str = str_replace("&", "et", $str);
    $str = str_replace("<", "moins que", $str);
    $str = str_replace(">", "plus que", $str);
    return strip_tags ($str);
}


