<?php

class adminController extends BimpController
{

    public function renderTestTab()
    {
        $html = "<div id='retourTestSpeed'></div>";
        $html .= "<br/><br/>Google : <span id='retourGoogle'></span> s";
        $html .= "<br/><br/>Php : <span id='retourPhp'></span> s";
        $html .= "<br/><br/>Mysql : <span id='retourMysql'></span> s";
        $html .= "<br/><br/>Fichier : <span id='retourFile'></span> s";
        $html .= "<br/><br/>Transfert : <span id='retourTransfert'></span> s";
        $html .= "<br/><br/>Total : <span id='retourTotal'></span> s";
        $html .= "<br/><br/>Info : <span id='retourInfo'></span>";
        $html .= "<br/><br/><button onClick='goTest();'>Re-Tester</button>";
        $html .= "<script>";
        $html .= "";

        $html .= "function displayResult(google, php, mysql, file, total, info){"
                . "$('#retourGoogle').html(google);"
                . "$('#retourPhp').html(php);"
                . "$('#retourMysql').html(mysql);"
                . "$('#retourFile').html(file);"
                . "$('#retourTransfert').html(total - mysql - php - google - file);"
                . "$('#retourTotal').html(total);"
                . "$('#retourInfo').html(info);"
                . "}"
                . "function goTest(){"
                . "timeDeb = new Date().getTime();"
                . "$('#retourGoogle').html('*');"
                . "$('#retourPhp').html('*');"
                . "$('#retourMysql').html('*');"
                . "$('#retourFile').html('*');"
                . "$('#retourTransfert').html('*');"
                . "$('#retourTotal').html('*');"
                . "$('#retourInfo').html('*');"
                . "setObjectAction($(this), {module: 'bimpcore', object_name: 'BimpTest'}, 'testSpeed');"
                . "}";

        $html .= "setTimeout(function(){goTest();}, 300);";
        $html .= "</script>";

        return $html;
    }
    
    
    
     public function renderRightsTab()
    {
        $html = "";
        $obj = BimpObject::getInstance('bimpcore', 'Bimp_UserGroupRight');

        $titre = "Droit";
        $nomList = 'default';


        $filtreDroit = 0;
        if (GETPOST('id_right')) {
            $right = BimpObject::getInstance('bimpcore', 'Bimp_Right');
            $nomR = 'mm';//$right->getRightName(GETPOST('id_right'));
            if ($nomR != "") {
                $filtreDroit = GETPOST('id_right');
//                $nomList = 'right';
//                $titre .= ' "' . $nomR . '"';
            } else
                $html .= BimpTools::getMsgFromArray(array("Droit d'id " . GETPOST('id_right') . " inconnue"));
        }
        $filtreUser = 0;
        if (GETPOST('id_user')) {
            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', GETPOST('id_user'));
            if ($user->isLoaded()) {
                $filtreUser = GETPOST('id_user');
//                $nomList = 'user';
                $titre .= ' de "' . $user->dol_object->getNomUrl(1) . '"';
            } else
                $html .= BimpTools::getMsgFromArray(array("User d'id " . GETPOST('id_right') . " inconnue"));
        }


        $list = new BC_ListTable($obj, $nomList, 1, null, $titre . ' par groupe');
        if ($filtreDroit) {
            $list->addFieldFilterValue('fk_id', (int) $filtreDroit);
        }
        if ($filtreUser) {
            $list->addJoin("usergroup_user", "usergroup_user.fk_usergroup = a.fk_usergroup", "usergroup_user");
            $list->addFieldFilterValue('usergroup_user.fk_user', (int) $filtreUser);
        }
        $html .= $list->renderHtml();


        $obj = BimpObject::getInstance('bimpcore', 'Bimp_UserRight');

        $list = new BC_ListTable($obj, $nomList, 1, null, $titre . ' par utilisateur');
        if ($filtreDroit) {
            $list->addFieldFilterValue('fk_id', (int) $filtreDroit);
        }
        if ($filtreUser) {
            $list->addFieldFilterValue('fk_user', (int) $filtreUser);
        }
        $html .= $list->renderHtml();
        return $html;
    }
}
