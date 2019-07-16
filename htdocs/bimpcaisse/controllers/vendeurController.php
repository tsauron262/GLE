<?php



class vendeurController extends BimpController {

    public function renderSession() {
        global $user;
        $obj = BimpObject::getInstance('bimpcaisse', 'BC_CaisseSession');
        $list = new BC_ListTable($obj, 'default', 1, null, "Session de caisse ouverte par " .$user->getNomUrl(1));
        $list->addFieldFilterValue('id_user_open', $user->id);
        $html .= $list->renderHtml();
        
        $obj = BimpObject::getInstance('bimpcaisse', 'BC_CaisseSession');
        $list = new BC_ListTable($obj, 'default', 1, null, "Session de caisse fermée par " .$user->getNomUrl(1));
        $list->addFieldFilterValue('id_user_closed', $user->id);
        $html .= $list->renderHtml();
        return $html;    
            
    }
}
