<?php

class chatController extends BimpController {

    public function renderHtml() {
        $notes = BimpObject::getInstance("bimpcore", "BimpNote");
        $tabFils = $notes->getMyConversations();
        
        $i = 0;
        foreach($tabFils as $fil){
            $i++;
            if(method_exists($fil['obj'], "getNomUrl"))
                echo $fil['obj']->getNomUrl();
            else
                echo "<br/>".$fil['obj']->getName().($fil==0?" NON LU" : "");
            if(!$fil['lu'])
                echo " (non lu)";
            
            echo $fil['obj']->renderNotesList(true, "chat", $i);
        }
        echo "<br/><br/><br/>";
        return $html;
    }


}

