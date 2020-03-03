<?php

class public_userController extends Bimp_user_client_controller {

    public function renderHtml() {
        global $userClient;
        $html = '';

        $html .= '<div class="page_content container-fluid">';
        $instance = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserClient');
        $list = new BC_ListTable($instance, 'default', 1, null, '', 'far_user');
        $list->addFieldFilterValue('attached_societe', $userClient->getData('attached_societe'));
        $html .= $list->renderHtml();
        $html .= '</div>';

        return $html;
    }

}
