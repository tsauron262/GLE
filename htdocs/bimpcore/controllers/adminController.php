<?php

class adminController extends BimpController
{

    public function renderScriptsTabContent()
    {
        global $user;

        if (!$user->admin) {
            return BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à cet onglet');
        }

        $files = scandir(DOL_DOCUMENT_ROOT . '/bimpcore/scripts/');

        $html = '';

        foreach ($files as $f) {
            if (in_array($f, array('.', '..'))) {
                continue;
            }

            if (preg_match('/^(.*)\.php$/', $f, $matches)) {
                $html .= '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $f . '" target="_blank">';
                $html .= $matches[1];
                $html .= '</a><br/>';
            }
        }

        return $html;
    }

    public function renderTestTab()
    {
        $html .= "<div id='retourTestSpeed'></div>";
        $html .= "<br/><br/>Google : <span id='retourGoogle'></span> s";
        $html .= "<br/><br/>Php : <span id='retourPhp'></span> s";
        $html .= "<br/><br/>Mysql : <span id='retourMysql'></span> s";
        $html .= "<br/><br/>Transfert : <span id='retourTransfert'></span> s";
        $html .= "<br/><br/>Total : <span id='retourTotal'></span> s";
        $html .= "<button onClick='displayResult();'>Re-Tester</button>";
        $html .= "<script>";
        $html .= "timeDeb = new Date().getTime(); ";


        $html .= "function displayResult(google, php, mysql, total){"
                . "$('#retourGoogle').html(google);"
                . "$('#retourPhp').html(php);"
                . "$('#retourMysql').html(mysql);"
                . "$('#retourTransfert').html(total - mysql - php - google);"
                . "$('#retourTotal').html(total);"
                . "}";


        $html .= "setObjectAction($(this), {module: 'bimpcore', object_name: 'BimpTest'}, 'testSpeed', {}, null, null, function(){}, null, null, false);";
        $html .= "</script>";

        return $html;
    }

    public function renderRightsTab()
    {
        $html = "";
        $obj = BimpObject::getInstance('bimpcore', 'Bimp_UserGroup_Rights');

        $titre = "Droit";
        $nomList = 'default';


        $filtreDroit = 0;
        if (GETPOST('id_right')) {
            $right = BimpObject::getInstance('bimpcore', 'Bimp_Rights');
            $nomR = $right->getRightName(GETPOST('id_right'));
            if ($nomR != "") {
                $filtreDroit = GETPOST('id_right');
                $nomList = 'right';
                $titre .= ' "' . $nomR . '"';
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


        $obj = BimpObject::getInstance('bimpcore', 'Bimp_User_Rights');

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
