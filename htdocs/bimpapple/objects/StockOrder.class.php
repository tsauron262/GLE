<?php

class StockOrder extends BimpObject
{

    const STATUT_BROUILLON = 0;
    const STATUT_ORDERED = 1;

    public static $status_list = array(
        self::STATUT_BROUILLON => array('label' => 'Brouillon', 'icon' => 'far_file-alt', 'classes' => array('warning')),
        self::STATUT_ORDERED   => array('label' => 'Commande effectuée', 'icon' => 'fas_check', 'classes' => array('success')),
    );

    // Droits Users: 

    public function canSetAction($action)
    {
        switch ($action) {
            case 'setPartQty':
            case 'addPart':
            case 'removePart':
                return 1;

            case 'order':
                return 1;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        if (!BimpCore::getConf('gsx_allow_stock_order', 0, 'bimpapple')) {
            $errors[] = 'Commandes de stock GSX non autorisées';
            return 0;
        }

        switch ($action) {
            case 'addPart':
            case 'removePart':
            case 'setPartQty':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if ((int) $this->getData('status')) {
                    $errors[] = 'Ce renvoi n\'est plus au statut brouillon';
                    return 0;
                }
                return 1;

            case 'order':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if ((int) $this->getData('status')) {
                    $errors[] = 'Ce renvoi n\'est plus au statut brouillon';
                    return 0;
                }

                if (empty($this->getData('parts'))) {
                    $errors[] = 'Aucun composant ajouté à cette commande';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isCreatable($force_create = false, &$errors = [])
    {
        if (!BimpCore::getConf('gsx_allow_stock_order', 0, 'bimpapple')) {
            $errors[] = 'Commandes de stock GSX non autorisées';
            return 0;
        }

        return parent::isCreatable($force_create, $errors);
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        if ($force_edit) {
            return 1;
        }

        if ((int) $this->getData('status') === self::STATUT_ORDERED) {
            return 0;
        }

        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ($force_delete) {
            return 1;
        }

        if ((int) $this->getData('status') <= 0) {
            return 1;
        }

        return 0;
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->canSetAction('order') && $this->isActionAllowed('order')) {
            $buttons[] = array(
                'label'   => 'Commander',
                'icon'    => 'fas_cart-arrow-down',
                'onclick' => $this->getJsActionOnclick('order', array(), array(
                    'confirm_msg' => 'Veuillez confirmer'
                ))
            );
        }


        return $buttons;
    }

    // Getters données: 

    public function getShipTo(&$errors = array())
    {
        $shipTo = '';
        $centre = $this->getData('code_centre');

        if (!$centre) {
            $errors[] = 'Centre absent';
        } else {
            $centres = BimpCache::getCentres();

            if (!isset($centres[$centre])) {
                $errors[] = 'Aucun centre pour le code "' . $centre . '"';
            } else {
                $shipTo = BimpTools::getArrayValueFromPath($centres[$centre], 'shipTo', '');

                if (!$shipTo) {
                    $errors[] = 'Aucun n° shipTo pour le centre ' . $centres[$centre]['label'];
                }
            }
        }

        return $shipTo;
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        $status = (int) $this->getData('status');

        if ($status > 0) {
            $html .= '<div class="object_header_infos">';
            $html .= 'Commande effectuée le ' . $this->displayData('date_order', 'default', false);

            $user = $this->getChildObject('user_order');
            if (BimpObject::objectLoaded($user)) {
                $html .= ' par ' . $user->getLink();
            }
            $html .= '</div>';
        }
        return $html;
    }

    public function renderSearchPartsForm()
    {
        $html = '';

        if ($this->isLoaded() && !(int) $this->getData('status')) {
            $html .= '<div class="stockOrderSearchPartsForm singleLineForm" style="margin-top: 10px"';
            $html .= ' data-id_stock_order="' . $this->id . '"';
            $html .= '>';

            $html .= '<div class="singleLineFormCaption">';
            $html .= '<h4>' . BimpRender::renderIcon('fas_search', 'iconLeft') . 'Recherche de composant à ajouter</h4>';
            $html .= '</div>';

            $html .= '<div class="singleLineFormContent stockOrderSearchPartForm">';

            $content = '<label>Type de recherche: </label>';
            $content .= BimpInput::renderInput('select', 'search_type', 'part_number', array(
                        'options' => array(
                            'partNumber'  => 'Ref. composant',
                            'description' => 'Description',
                            'eeeCode'     => 'Code EEE',
                            'productName' => 'Nom du produit correspondant'
                        )
            ));
            $html .= BimpInput::renderInputContainer('search_type', 'partNumber', $content);

            $content = '<label>Recherche: </label>';
            $content .= BimpInput::renderInput('text', 'search_terms', '');

            $html .= BimpInput::renderInputContainer('search_terms', '', $content);

            $onclick = 'StockOrder.searchParts($(this), ' . $this->id . ')';

            $html .= '<button type="button" class="btn btn-primary" onclick="' . $onclick . '">';
            $html .= 'Rechercher' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
            $html .= '</button>';
            $html .= '<div class="quickAddForm_ajax_result"></div>';

            $html .= '</div>';
            $html .= '</div>';
        }

        return $html;
    }

    public function renderPartsList($content_only = false)
    {
        $html = '';
        $errors = array();

        $parts = $this->getData('parts');

        if (empty($parts)) {
            $html .= BimpRender::renderAlerts('Aucun composant ajouté dans cette commande de stock', 'warning');
        } else {
            $headers = array(
                'ref'     => 'Réf.',
                'desc'    => 'Desc',
                'qty'     => 'Qté',
                'buttons' => ''
            );

            $rows = array();

            $parts_editable = (!(int) $this->getData('status') && $this->canSetAction('setPartQty'));

            $i = 0;
            foreach ($parts as $part_number => $part) {
                $i++;
                $qty_html = '';

                if ($parts_editable) {
                    $qty_html .= BimpInput::renderInput('qty', 'part_' . $i . '_qty', $part['qty'], array(
                                'data'       => array(
                                    'min'      => 1,
                                    'decimals' => 0
                                ),
                                'extra_attr' => array(
                                    'onchange' => 'StockOrder.setPartQty($(this), \'' . $part_number . '\', ' . $this->id . ')'
                                )
                    ));
                } else {
                    $qty_html .= $part['qty'];
                }
                $rows[] = array(
                    'ref'     => $part_number,
                    'qty'     => $qty_html,
                    'desc'    => $part['desc'],
                    'buttons' => ($parts_editable ? BimpRender::renderRowButton('Retirer', 'fas_trash-alt', $this->getJsActionOnclick('removePart', array(
                                'part_number' => $part_number
                                    ), array(
                                'no_triggers'      => 1,
                                'success_callback' => 'function () {StockOrder.reloadPartsList()}'
                    ))) : '')
                );
            }

            $html .= '<div class="col-xs-12 col-md-6 col-lg-6">';
            $html .= BimpRender::renderBimpListTable($rows, $headers);
            $html .= '</div>';

            if (count($errors)) {
                $html .= BimpRender::renderAlerts($errors);
            }
        }

        if ($content_only) {
            return $html;
        }

        $title = BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Liste des composants ' . ($parts_editable ? 'à commander' : 'commandés');

        return BimpRender::renderPanel($title, $html, '', array(
                    'foldable'       => true,
                    'type'           => 'secondary',
                    'header_buttons' => array(
                        array(
                            'label'   => 'Actualiser',
                            'icon'    => 'fas_undo',
                            'onclick' => $this->getJsLoadCustomContent('renderPartsList', '$(this).findParentByClass(\'panel\').children(\'.panel-body\')', array(true)),
                            'classes' => array('reloadStockorderPartsListButton')
                        )
                    )
        ));
    }

    // Traitements: 

    public function checkParts($new_parts, &$errors = array(), &$warnings = array())
    {
        if ($this->isLoaded($errors)) {
            if ((int) $this->getData('status') === 0) {
                if (!empty($new_parts)) {
                    $shipTo = $this->getShipTo($errors);
                    $orderId = $this->getData('order_id');

                    if (!$orderId) {
                        $errors[] = 'N° de commande absent';
                    }

                    if (!count($errors)) {
                        require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
                        $gsx = new GSX_v2($shipTo);

                        $result = $gsx->consignmentOrderLookup($orderId);

                        if (!$gsx->logged) {
                            $errors[] = $gsx->displayNoLogged();
                        } else {
                            $errors = $gsx->getErrors();

                            if (!count($errors)) {
                                $parts = array();
                                $code_centre = $this->getData('code_centre');

                                foreach ($result as $order) {
                                    if ($order['orderId'] == $orderId) {
                                        if (isset($order['parts'])) {
                                            foreach ($order['parts'] as $orderPart) {
                                                $partNumber = $orderPart['number'];

                                                if (isset($new_parts[$partNumber])) {
                                                    $qtyPending = (int) ((int) $orderPart['partQuantity'] - (int) $orderPart['quantitySubmitted']);
                                                    $stock = BimpCache::findBimpObjectInstance('bimpapple', 'ConsignedStock', array(
                                                                'code_centre' => $code_centre,
                                                                'part_number' => $partNumber
                                                                    ), true);

                                                    if ($qtyPending > 0) {
                                                        if (BimpObject::objectLoaded($stock)) {
                                                            if ((int) $orderPart['serialized']) {
                                                                $serials = array();
                                                                $stocks_serials = $stock->getData('serials');

                                                                if (isset($new_parts[$partNumber]['serials']) && !empty($new_parts[$partNumber]['serials'])) {
                                                                    foreach ($new_parts[$partNumber]['serials'] as $serial) {
                                                                        if (!in_array($serial, $stocks_serials)) {
                                                                            $warnings[] = $partNumber . ': le n° de série "' . $serial . '" n\'est plus présent en stock';
                                                                        } else {
                                                                            $serials[] = $serial;
                                                                        }
                                                                    }

                                                                    if (count($serials) > $qtyPending) {
                                                                        $errors[] = $partNumber . ': nombre de n° de série sélectionné supérieur au nombre attendu. Veuillez retirer ' . (count($serials) - $qtyPending) . ' éléments';
                                                                    } elseif (!empty($serials)) {
                                                                        $parts[$partNumber] = array('serials' => $serials);
                                                                    }
                                                                }
                                                            } else {
                                                                $new_qty = (int) BimpTools::getArrayValueFromPath($new_parts, $partNumber . '/qty', 0);

                                                                if ($new_qty > 0) {
                                                                    $stock_qty = (int) $stock->getData('qty');

                                                                    if (!$stock_qty) {
                                                                        $warnings[] = $partNumber . ': aucune unité disponible en stock';
                                                                        $new_qty = 0;
                                                                    } elseif ($new_qty > $stock_qty) {
                                                                        if ($stock_qty > 1) {
                                                                            $warnings[] = $partNumber . ': seules ' . $stock_qty . ' unités sont disponibles en stock';
                                                                        } else {
                                                                            $warnings[] = $partNumber . ': seule ' . $stock_qty . ' unité est disponible en stock';
                                                                        }

                                                                        $new_qty = $stock_qty;
                                                                    }

                                                                    if ($new_qty > 0) {
                                                                        if ($new_qty > $qtyPending) {
                                                                            if ($qtyPending > 1) {
                                                                                $warnings[] = $partNumber . ': seules ' . $qtyPending . ' unités sont attendues';
                                                                            } else {
                                                                                $warnings[] = $partNumber . ': seule ' . $qtyPending . ' unité est attendue';
                                                                            }

                                                                            $new_qty = $qtyPending;
                                                                        }

                                                                        if ($new_qty > 0) {
                                                                            $parts[$partNumber] = array('qty' => $new_qty);
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        } else {
                                                            $warnings[] = $partNumber . ': aucune unité en stock';
                                                        }
                                                    } else {
                                                        $warnings[] = $partNumber . ': aucune unité attendue';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                return $parts;
                            }
                        }
                    }
                }
            } else {
                $errors[] = 'Ce renvoi n\'est plus au statut brouillon';
            }
        }

        return $new_parts;
    }

    // Actions: 

    public function actionAddPart($data, &$success = '')
    {
        $errors = array();
        $warnings = array();
        $success = 'Ajout d\'un composant à commander effectué avec succès';

        $ref = BimpTools::getArrayValueFromPath($data, 'ref', '');
        if (!$ref) {
            $errors[] = 'Réf. absente';
        }

        $desc = BimpTools::getArrayValueFromPath($data, 'desc', '');
        if (!$desc) {
            $errors[] = 'Description absente';
        }

        $qty = (int) BimpTools::getArrayValueFromPath($data, 'qty', 0);
        if (!$qty) {
            $errors[] = 'Aucune quantité à commander';
        }

        if (!count($errors)) {
            $parts = $this->getData('parts');

            if (isset($parts[$ref])) {
                $errors[] = 'Ce composant a déjà été ajouté dans cette commande. Veuillez en modifier les qtés si nécessaire';
            } else {
                $parts[$ref] = array(
                    'desc' => $desc,
                    'qty'  => $qty
                );

                $errors = $this->updateField('parts', $parts);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemovePart($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Retrait du composant effectué';

        $part_number = BimpTools::getArrayValueFromPath($data, 'part_number', '', $errors, true, 'Ref. composant absente');

        if (!count($errors)) {
            $parts = $this->getData('parts');

            if (!isset($parts[$part_number])) {
                $errors[] = 'Le composant "' . $part_number . '" n\'a pas été ajouté à cette commande de stock';
            } else {
                unset($parts[$part_number]);
                $errors = $this->updateField('parts', $parts);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSetPartQty($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Mise à jour qté effectuée';

        $part_number = BimpTools::getArrayValueFromPath($data, 'part_number', '', $errors, true, 'Ref. composant absente');
        $qty = (int) BimpTools::getArrayValueFromPath($data, 'qty', '', $errors, true, 'Qté absente');

        if (!count($errors)) {
            $parts = $this->getData('parts');

            if (!isset($parts[$part_number])) {
                $errors[] = 'Le composant "' . $part_number . '" n\'a pas été ajouté à cette commande de stock';
            } else {
                $parts[$part_number]['qty'] = $qty;

                $errors = $this->updateField('parts', $parts);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionOrder($data, &$success)
    {
        global $user;
        $errors = array();
        $warnings = array();
        $success = '';

        $parts = $this->getData('parts');
        $shipTo = $this->getShipTo($errors);

        if (empty($parts)) {
            $errors[] = 'Aucun composant ajouté à cette commande';
        }

        if (!count($errors)) {
            $order_parts = array();

            foreach ($parts as $part_number => $part_data) {
                $order_parts[] = array(
                    'number'   => $part_number,
                    'quantity' => $part_data['qty']
                );
            }

            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

            $gsx = new GSX_v2($shipTo);

            if (BimpCore::isModeDev()) {
                $result = array(
                    'orderId' => '123456'
                );
            } else {
                $result = $gsx->stockingOrderCreate($order_parts);
                if (!$gsx->logged) {
                    $errors[] = BimpRender::renderAlerts($gsx->displayNoLogged());
                } else {
                    $errors = $gsx->getErrors();
                }
            }


            if (!count($errors)) {
                $success .= 'Commande de stock effectuée sur GSX avec succès';

                $order_id = BimpTools::getArrayValueFromPath($result, 'orderId', '');
                if ($order_id) {
                    $this->set('order_id', $order_id);
                }
                $this->set('date_order', date('Y-m-d H:i:s'));
                $this->set('id_user_order', $user->id);
                $this->set('status', self::STATUT_ORDERED);

                $up_errors = $this->update($warnings, true);
                if (count($up_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du statut de la commande de stock');
                }

                $code_centre = $this->getData('code_centre');
                BimpObject::loadClass('bimpapple', 'InternalStock');

                foreach ($parts as $part_number => $part_data) {
                    $stock = InternalStock::getStockInstance($code_centre, $part_number);
                    $stock_errors = array();

                    if (BimpObject::objectLoaded($stock)) {
                        $stock_errors = $stock->modifQtyToReceive((int) $part_data['qty']);
                    } else {
                        $eeeCode = '';
//
//                            $result2 = $gsx->stockingOrderPartsSummary(array(
//                                'partNumber' => $part_number
//                            ));
//
//                            if (is_array($result2) && !empty($result2)) {
//                                foreach ($result2 as $res_part) {
//                                    if ($res_part['partNumber'] === $part_number) {
//                                        if (isset($res_part['eeeCodes']) && !empty($res_part['eeeCodes'])) {
//                                            if (is_string($res_part['eeeCodes'])) {
//                                                $eeeCode = $res_part['eeeCodes'];
//                                            } elseif (is_array($res_part['eeeCodes'])) {
//                                                $eeeCode = implode(' ', $res_part['eeeCodes']);
//                                            }
//                                        }
//                                        break;
//                                    }
//                                }
//                            }

                        BimpObject::createBimpObject('bimpapple', 'InternalStock', array(
                            'code_centre'    => $code_centre,
                            'part_number'    => $part_number,
                            'qty'            => 0,
                            'qty_to_receive' => (int) $part_data['qty'],
                            'description'    => $part_data['desc'],
                            'code_eee'       => $eeeCode
                                ), true, $stock_errors);
                    }

                    if (count($stock_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Echec de l\'enregistrement de la quantité à recevoir pour le composant "' . $part_number . '"');
                    }
                }
            }

            return array(
                'errors'   => $errors,
                'warnings' => $warnings
            );
        }
    }
}
