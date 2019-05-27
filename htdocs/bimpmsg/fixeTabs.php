<?php

require_once DOL_DOCUMENT_ROOT . "/bimpcore/classes/FixeTabs_module.class.php";

class FixeTabs_bimpmsg extends FixeTabs_module {

    function init() {
        $content = "";
        $alert = false;
        $maxTaskView = 30;
        
        
        $notes = BimpObject::getInstance("bimpcore", "BimpNote");
        $tabFils = $notes->getMyConversations(true, $maxTaskView);
        
        $i = 0;
        $content = "";
        $nonLu = 0;
        foreach($tabFils as $fil){
            if($fil['obj']->id > 0){
                $i++;
                $content .= "<br/>";
                $content .= "<div class='lnMsg'><span class='titreFilMsg'>";
                if(method_exists($fil['obj'], "getNomUrl"))
                    $content .= $fil['obj']->getNomUrl();
                else
                    $content .=  "<br/>".$fil['obj']->getName();

    //                    $m = new BimpObject();
    //            $m->getChildObject("societe")

                if(!$fil['lu']){
                    $content .=  " (non lu)";
                    $nonLu++;
                    $notes->fetch($fil['idNoteRef']);
                }
                else {
                    global $db;
                    $sql = $db->query('SELECT MAX(id) max FROM `llx_bimpcore_note` WHERE `obj_type` = "bimp_object" AND `obj_module` = "'.$fil['obj']->module.'" AND `obj_name` = "'.$fil['obj']->object_name.'" AND `id_obj` = '.$fil['obj']->id);
                    $ln = $db->fetch_object($sql);
                    $notes->fetch($ln->max);
                }
                $content .= "</span>";
                if(method_exists($fil['obj'], "getChildObject")){
                        $soc = $fil['obj']->getChildObject("societe");
                        if(!$soc || !$soc->isLoaded()){
                            $soc = $fil['obj']->getChildObject("client");
                        }
                        if($soc && $soc->isLoaded())
                            $content .= " - ".$soc->getNomUrl(1);
                }
                $content .= "<div onclick='loadModalObjectNotes($(this), \"".$fil['obj']->module."\", \"".$fil['obj']->object_name."\", \"".$fil['obj']->id."\", \"chat\", true);'>";
                $content .= $notes->displayChatmsg("petit", false);
                $content .= "</div></div>";


                //echo $fil['obj']->renderNotesList(true, "chat", $i);
            }
        }

        

            $this->bimp_fixe_tabs->addTab("Messages", "<span class='' >Messages" . ($nonLu > 0 ? " <span class='red'>" . $nonLu . " message" . ($nonLu > 1 ? 's' : '') . " non lu" . ($nonLu > 1 ? 's' : '') . ".</span>" : "") . "</span>", $content);

    }

    function can($right){
        return 1;
//        global $conf;
//        $retour = false;
//        if($right == "view"){
//            if (isset($conf->global->MAIN_MODULE_BIMPTASK)) {
//                $task = BimpObject::getInstance("bimptask", "BIMP_Task");
//                if ($task->can("view"))
//                    $retour = true;
//            }
//        }
//        return $retour;
    }
    
    function displayHead() {
        $html = '';
        $html .= '<link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/bimpmsg/views/css/notesMsg.css"/>';
        $html .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/bimpmsg/views/js/notesMsg.js"></script>';
        return $html;
    }
}
