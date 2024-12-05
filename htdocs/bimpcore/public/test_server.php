<?php


















try{
    
    
    if(!isset($_REQUEST['error']) || $_REQUEST['error'] == 0)
        fonction_nexistepas();
    else{
        require_once("../../main.inc.php");
        llxHeader();
        $cacheServeur = (isset($_REQUEST['cache_serveur'])? $_REQUEST['cache_serveur'] : 0);
        $result = BimpCache::getCommercialBimpComm('propal', $cacheServeur);
        $result = BimpCache::getCommercialBimpComm('propal', $cacheServeur);
        $result = BimpCache::getCommercialBimpComm('propal', $cacheServeur);
        $result = BimpCache::getCommercialBimpComm('propal', $cacheServeur);
        echo 'ok '.count($result).'<br/>';
        echo 'cache_serveur = '.$cacheServeur.'<br/>';
        llxFooter();
    }
} catch (\Error  $ex) {
    
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    echo '<pre>';
    print_r($ex);
    die('fin de page avec erreur');
}

die('fin normal');