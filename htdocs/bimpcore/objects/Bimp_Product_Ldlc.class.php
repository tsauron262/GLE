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
                    'fp_id_fourn'      => self::$idFournLdlc,
                    'fp_ref_fourn'     => $this->getData('refLdlc'),
                    'fp_pa_ht'         => $this->getData('pa_ht')
                )
            );

            $buttons[] = array(
                'label'   => 'Créer un produit',
                'icon'    => 'fas_plus-circle',
                'onclick' => $product->getJsLoadModalForm('light_fourn_price', 'Nouveau produit', $values)
            );

            $buttons[] = array(
                'label'   => 'Rattacher à un produit',
                'icon'    => 'fas_link',
                'onclick' => $this->getJsActionOnclick('linkToProduct', array(), array(
                    'form_name' => 'link_to_product'
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

        $id_product = (int) BimpTools::getArrayValueFromPath($data, 'id_product', 0);
        $fourn_price = (int) BimpTools::getArrayValueFromPath($data, 'fourn_price', 0);
        $sell_price = (int) BimpTools::getArrayValueFromPath($data, 'sell_price', 0);

        if (!$id_product) {
            $errors[] = 'Aucun produit sélectionné';
        } else {
            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id_product);

            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Le produit d\'ID ' . $id_product . ' n\'existe pas';
            } else {

                if ($fourn_price) {
                    $pfp = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', array(
                                'fk_product' => $id_product,
                                'fk_soc'     => self::$idFournLdlc,
                                'ref_fourn'  => $this->getData('refLdlc')
                    ));

                    if (!BimpObject::objectLoaded($pfp)) {
                        $pfp = BimpObject::getInstance('bimpcore', 'Bimp_ProductFournisseurPrice');
                        $pfp->validateArray(array(
                            'fk_product' => $id_product,
                            'fk_soc'     => self::$idFournLdlc,
                            'ref_fourn'  => $this->getData('refLdlc')
                        ));
                    }

                    $pfp->set('price', (float) $this->getData('pa_ht'));
                    $pfp->set('tva_tx', (float) $this->getData('tva_tx'));

                    $pfp_errors = array();
                    $pfp_warnings = array();

                    if ($pfp->isLoaded()) {
                        $pfp_errors = $pfp->update($pfp_warnings, true);

                        if (count($pfp_warnings)) {
                            $warnings[] = BimpTools::getMsgFromArray($pfp_warnings, 'Erreurs lors de la mise à jour du prix d\'achat fournisseur');
                        }

                        if (count($pfp_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($pfp_errors, 'Echec de la mise à jour du prix d\'achat fournisseur');
                        } else {
                            $success .= 'Prix d\'achat fournisseur #' . $pfp->id . ' mis à jour avec succès';
                        }
                    } else {
                        $pfp_errors = $pfp->create($pfp_warnings, true);

                        if (count($pfp_warnings)) {
                            $warnings[] = BimpTools::getMsgFromArray($pfp_warnings, 'Erreurs lors de la création du prix d\'achat fournisseur');
                        }

                        if (count($pfp_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($pfp_errors, 'Echec de la création du prix d\'achat fournisseur');
                        } else {
                            $success .= 'Prix d\'achat fournisseur #' . $pfp->id . ' créé avec succès';
                        }
                    }
                }

                if ($sell_price) {
                    global $user;
                    BimpTools::resetDolObjectErrors($this->dol_object);
                    if ($product->dol_object->updatePrice((float) $this->getData('pu_ht'), 'HT', $user, (float) $this->getData('tva_tx')) < 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object), 'Echec de la mise à jour du prix de vente du produit');
                    } else {
                        $success .= ($success ? '<br/>' : '') . 'Prix de vente du produit mis à jour avec succès';
                    }
                }

                if ($fourn_price && !count($errors)) {
                    $del_errors = array();
                    $this->delete($del_errors, true);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}
