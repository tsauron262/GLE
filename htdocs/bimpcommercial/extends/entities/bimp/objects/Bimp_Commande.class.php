<?php

//Entity: bimp

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Commande.class.php';

class Bimp_Commande_ExtEntity extends Bimp_Commande
{

    // Getters: 

    public function getIdEntrepotSpare()
    {
        $cli = $this->getChildObject('client');
        return $cli->getData('entrepot_spare');
    }

    public function getActionsButtons()
    {
        $buttons = parent::getActionsButtons();

        if ($this->getIdEntrepotSpare()) {
            $button = array(
                'label'   => 'Maj date fin spare',
                'icon'    => 'fas_link',
                'onclick' => $this->getJsActionOnclick('majDateFinSpare', array(), array('form_name' => 'majDateFinSpare'))
            );

            if (isset($buttons['buttons_groups'][0]['buttons'])) {
                $buttons['buttons_groups'][0]['buttons'][] = $button;
            } else {
                $buttons[] = $button;
            }
        }

        return $buttons;
    }

    // Actions: 

    public function actionMajDateFinSpare($data, &$success)
    {
//        $lines = $this->getChildrenObjects('lines', array(
//            'type' => ObjectLine::LINE_PRODUCT
//        ));
//        foreach ($lines as $line) {
//            $product = $line->getProduct();
//            $full_qty = (float) $line->getFullQty();
//            if (BimpObject::objectLoaded($product)) {
//                if ($product->isSerialisable()) {

        $success = 'Nouvelle date fin SPARE ' . $data['dateF'];
        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
        $list = $reservation->getList(array(
            'id_commande_client' => (int) $this->id,
//            'status'                  => 200,
            'id_equipment'       => array(
                'operator' => '>',
                'value'    => 0
            )
                ), null, null, 'id', 'asc', 'array', array('id', 'id_equipment'));

        if (!is_null($list)) {
            foreach ($list as $item) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $item['id_equipment']);
                $equipment->updateField('date_fin_spare', $data['dateF']);
                $success .= '<br/>' . $equipment->getData('serial');
            }
        }
        return array('errors' => array(), 'warnings' => array());
    }
}
