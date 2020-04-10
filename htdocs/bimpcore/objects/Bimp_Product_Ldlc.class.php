<?php

class Bimp_Product_Ldlc extends BimpObject
{

    public static $idFournLdlc = 230880;

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');

            $marque = $this->getData('marque');

            if (in_array($marque, array('GENERIQUE'))) {
                $marque = '';
            }

            $values = array(
                'fields' => array(
                    'marque'           => $marque,
                    'ref_constructeur' => (string) $this->getData('refFabriquant'),
                    'label'            => $this->getData('libelle'),
                    'price'            => (float) $this->getData('pu_ht'),
                    'tva_tx'           => (float) $this->getData('tva_tx'),
                    'pa_prevu'         => (float) $this->getData('pa_ht'),
                    'infos_pa'         => 'Prix d\'achat LDLC'
                )
            );

            $buttons[] = array(
                'label'   => 'Créer un produit',
                'icon'    => 'fas_plus-circle',
                'onclick' => $product->getJsLoadModalForm('light', 'Nouveau produit', $values)
            );

            $buttons[] = array(
                'label'   => 'Rattacher à un produit',
                'icon'    => 'fas_link',
                'onclick' => $this->getJsActionOnclick('linkToProduct', array(), array(
//                    'form_name' => 'link_to_product'
                ))
            );
        }

        return $buttons;
    }

    public function actionLinkToProduct($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

//        $id_product = BimpTools::getArrayValueFromPath($data, 'id_product', 0);
//
//        if (!$id_product) {
//            $errors[] = 'Aucun produit sélectionné';
//        } else {
//            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id_product);
//
//            if (!BimpObject::objectLoaded($product)) {
//                $errors[] = 'Le produit d\'ID ' . $id_product . ' n\'existe pas';
//            } else {
//                $pfp = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', array(
//                            'fk_product' => $id_product,
//                            'fk_soc'     => self::$idFournLdlc,
//                            'ref_fourn'  => $this->getData('ref')
//                ));
//
//                if (!BimpObject::objectLoaded($pfp)) {
//                    $pfp = BimpObject::getInstance('bimpcore', 'Bimp_Product');
//                    $pfp->validateArray(array(
//                        'fk_product' => $id_product,
//                        'fk_soc'     => self::$idFournLdlc,
//                        'ref_fourn'  => $this->getData('ref')
//                    ));
//                }
//
//                $pfp->set('price', (float) $this->getData('pa_ht'));
                $warnings[] = 'En cours de développement';
//            }
//        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}
