<?php

require_once('../main.inc.php');

llxHeader();




$ok = (isset($_REQUEST['go']) && $_REQUEST['go']);
$branche = (isset($_REQUEST['branche'])?$_REQUEST['branche'] : BimpCore::getConf('git_branch', 'master')) ;

echo '<form><input type="hidden" name="go" value="1"/><input type="text" name="branche" value="'.$branche.'"/><br/><input type="submit" value="Go"/></form>';

if($ok && $branche != ''){
    $lien = '/synopsistools/git_pull.php?no_menu=1&nolog=ujgjhkhkfghgkvgkfdkshfiohf5453FF454FFDzelef&go=1&branche='.$branche;

    $array = array("erp1", /*"erp2",*/ /*"erp3",*/ "erp4", /*"erp5",*/ "erp6");


    foreach($array as $erp){
        echo '<h1>Serveur '.$erp.'</h1>';
        $lienF = 'https://'.$erp.'.bimp.fr/'.DOL_URL_ROOT.$lien;
        echo $lienF;
        echo '<iframe style="width: 100%; height: 400px;" src="'.$lienF.'"></iframe>';
    }


    echo '<iframe style="width: 100%; height: 400px;" src="'.DOL_URL_ROOT.'/synopsistools/git_maj_version.php'.'"></iframe>';
}
llxFooter();

