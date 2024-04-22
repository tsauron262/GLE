<?php

class StockOrder extends BimpObject
{
    const STATUT_BROUILLON = 0;
    const STATUT_ORDERED = 1;

    public static $status_list = array(
        self::STATUT_BROUILLON => array('label' => 'Brouillon', 'icon' => 'far_file-alt', 'classes' => array('warning')),
        self::STATUT_ORDERED    => array('label' => 'Commande effectuée', 'icon' => 'fas_check', 'classes' => array('success')),
    );

    // Droits Users: 

    public function canSetAction($action)
    {
        switch ($action) {
            case 'addPart':
            case 'order':
                return 1;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'addPart':
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
                    'form_name' => 'order'
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
            $html .= '</div>';
        }
        return $html;
    }

    public function renderPartsList($content_only = false)
    {
        $html = '';
        $errors = array();

        $parts = $this->getData('parts');

        if ((int) $this->getData('status') === 0) {
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
                    $html .= BimpRender::renderAlerts('Non connecté à GSX. Veillez vous connecter puis cliquer sur "Actualiser"');
                    $html .= '<div class="buttonsContainer">';
                    $html .= '<span class="btn btn-default" onclick="gsx_open_login_modal($(this));">';
                    $html .= 'Se connecter' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
                    $html .= '</span>';
                    $html .= '</div>';
                } else {
                    $errors = $gsx->getErrors();

                    if (!count($errors)) {
                        $code_centre = $this->getData('code_centre');
                        $rows = array();

                        foreach ($result as $order) {
                            if ($order['orderId'] == $orderId) {
                                if (isset($order['parts'])) {
                                    $i = 1;
                                    foreach ($order['parts'] as $orderPart) {
                                        if ((int) $orderPart['partQuantity'] != (int) $orderPart['quantitySubmitted']) {
                                            $qtyPending = (int) ((int) $orderPart['partQuantity'] - (int) $orderPart['quantitySubmitted']);

                                            $row = array(
                                                'row_data'   => array(
                                                    'part_number' => $orderPart['number']
                                                ),
                                                'number'     => $orderPart['number'],
                                                'desc'       => $orderPart['description'],
                                                'qtyPending' => $qtyPending,
                                                'qtyStock'   => '',
                                                'qty'        => ''
                                            );

                                            $max = $qtyPending;
                                            $qty = 0;
                                            $selected_serials = array();
                                            $serials = array();

                                            $qtyStock = 0;
                                            $stock = BimpCache::findBimpObjectInstance('bimpapple', 'ConsignedStock', array(
                                                        'code_centre' => $code_centre,
                                                        'part_number' => $orderPart['number']
                                            ));

                                            if (BimpObject::objectLoaded($stock)) {
                                                if ((int) $orderPart['serialized']) {
                                                    $serials = $stock->getData('serials');
                                                    $qtyStock = count($serials);
                                                } else {
                                                    $qtyStock = (int) $stock->getData('qty');
                                                }
                                            }

                                            if ($max > $qtyStock) {
                                                $max = $qtyStock;

                                                if ($qty > $max) {
                                                    $qty = $max;
                                                }
                                            }

                                            $row['qtyStock'] = '<span class="badge badge-' . ($qtyStock >= $qtyPending ? 'success' : ($qtyStock > 0 ? 'warning' : 'danger')) . '">' . $qtyStock . '</span>';

                                            if (isset($parts[$orderPart['number']])) {
                                                $part = $parts[$orderPart['number']];

                                                if ((int) $orderPart['serialized']) {
                                                    $selected_serials = BimpTools::getArrayValueFromPath($part, 'serials', array());
                                                    $qty = count($selected_serials);
                                                } else {
                                                    $qty = (int) BimpTools::getArrayValueFromPath($part, 'qty', 0);
                                                }
                                            }

                                            if ($qtyStock > 0) {
                                                if ((int) $orderPart['serialized']) {
                                                    $items = array();
                                                    $values = array();

                                                    foreach ($selected_serials as $serial) {
                                                        $values[$serial] = $serial;
                                                    }

                                                    foreach ($serials as $serial) {
                                                        $items[$serial] = $serial;
                                                    }

                                                    $row['qty'] .= BimpInput::renderInput('check_list', 'part_' . $i . '_serials', $values, array(
                                                                'items'        => $items,
                                                                'search_input' => (count($items) > 10 ? 1 : 0),
                                                                'extra_class'  => 'part_serials_check_list',
                                                                'max'          => $max
                                                    ));
                                                } else {
                                                    $row['qty'] = BimpInput::renderInput('qty', 'part_' . $i . '_qty', $qty, array(
                                                                'extra_class' => 'part_qty_input',
                                                                'data'        => array(
                                                                    'data_type' => 'number',
                                                                    'min'       => 0,
                                                                    'max'       => $max
                                                                )
                                                    ));
                                                }
                                            }

                                            $rows[] = $row;
                                            $i++;
                                        }
                                    }
                                }
                            }
                        }

                        if (empty($rows)) {
                            $html .= BimpRender::renderAlerts('Aucun stock consigné à renvoyer trouver pour ce n° de commande', 'warning');
                        } else {
                            $headers = array(
                                'number'     => 'Ref.',
                                'desc'       => 'Libellé',
                                'qtyPending' => array('label' => 'Qté attendue', 'align' => 'center'),
                                'qtyStock'   => array('label' => 'Qté en stock', 'align' => 'center'),
                                'qty'        => 'Qté / N° de série à renvoyer'
                            );

                            $html .= '<div class="parts_form">';
                            $html .= BimpRender::renderBimpListTable($rows, $headers, array(
                                        'searchable'  => true,
                                        'search_mode' => 'lighten'
                            ));

                            $html .= '<div class="ajaxResultContainer" style="display: none"></div>';

                            $html .= '<div class="buttonsContainer align-right">';
                            $html .= '<span class="btn btn-primary" onclick="ConsignedStocksShipment.saveParts($(this), ' . $this->id . ');">';
                            $html .= BimpRender::renderIcon('fas_save', 'iconLeft') . 'Enregistrer les modifications';
                            $html .= '</span>';
                            $html .= '</div>';
                            $html .= '</div>';
                        }
                    }
                }
            }
        } elseif ((int) $this->getData('status') > 0) {
            $headers = array(
                'ref'     => 'Réf.',
                'qty'     => 'Qté renvoyés',
                'serials' => 'N° de serie'
            );

            $rows = array();

            foreach ($parts as $part_number => $part) {
                $row = array(
                    'ref' => $part_number
                );

                if (isset($part['serials'])) {
                    $row['qty'] = count($part['serials']);
                    $row['serials'] = '';

                    foreach ($part['serials'] as $serial) {
                        $row['serials'] .= ($row['serials'] ? '<br/>' : '') . $serial;
                    }
                } else {
                    $row['qty'] = BimpTools::getArrayValueFromPath($part, 'qty', 0);
                }

                $rows[] = $row;
            }

            $html .= '<div class="col-xs-12 col-md-6 col-lg-6">';
            $html .= BimpRender::renderBimpListTable($rows, $headers);
            $html .= '</div>';
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        if ($content_only) {
            return $html;
        }

        $title = BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Liste des composants';
        return BimpRender::renderPanel($title, $html, '', array(
                    'foldable'       => true,
                    'type'           => 'secondary',
                    'header_buttons' => array(
                        array(
                            'label'   => 'Actualiser',
                            'icon'    => 'fas_undo',
                            'onclick' => $this->getJsLoadCustomContent('renderPartsList', '$(this).findParentByClass(\'panel\').children(\'.panel-body\')', array(true))
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

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionOrder($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Commande effectuée avec succès';

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}
