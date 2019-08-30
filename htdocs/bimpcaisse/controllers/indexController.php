<?php

class indexController extends BimpController
{

    protected $caisse = null;

    public function isCaisseValide(BC_Caisse $caisse, &$errors = array())
    {
        return $caisse->isValid($errors);
    }

    public function getUserCaisse()
    {
        if (is_null($this->caisse)) {
            global $user;

            if (isset($user->id) && $user->id) {
                BimpObject::loadClass('bimpcaisse', 'BC_Caisse');
                $id_caisse = (int) BC_Caisse::getUserCaisse((int) $user->id);
                if ($id_caisse) {
                    $this->caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $id_caisse);
                    if (!BimpObject::objectLoaded($this->caisse)) {
                        unset($this->caisse);
                        $this->caisse = null;
                    }
                }
            }
        }

        return $this->caisse;
    }

    // Rendus HTML:

    public function renderHtml()
    {
        global $conf, $mysoc;

        $html .= '<div id="bc_main_container">';
        $html .= '<div class="container-fluid">';

        $html .= '<div class="row bc_header">';

        $html .= '<div class="header_logo">';
        $html .= '<img src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&file=' . $mysoc->logo . '" alt="' . $mysoc->name . '"/>';
        $html .= '</div>';

        $html .= $this->renderHeaderContent();

        $html .= '<span class="fullScreenButton bs-popover" ';
        $html .= BimpRender::renderPopoverData('Plein écran', 'bottom');
        $html .= '>';
        $html .= '<i class="fas fa5-expand-arrows-alt"></i>';
        $html .= '</span>';

        $html .= '<span class="windowMaximiseButton bs-popover" ';
        $html .= BimpRender::renderPopoverData('Agrandir', 'bottom');
        $html .= '>';
        $html .= '<i class="fa fa-window-maximize"></i>';
        $html .= '</span>';

        $html .= '</div>';

        $html .= '<div class="row bc_body">';
        $html .= $this->renderContentHtml();
        $html .= '</div>';

        $html .= '<div class="row bc_footer">';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderHeaderContent()
    {
        global $user, $langs;
        $caisse = $this->getUserCaisse();

        $html = '';

        if (isset($user->id) && $user->id) {
            $html .= '<div class="headerBlock">';
            $html .= '<i class="fa fa-user-circle"></i>';
            $html .= '<div class="headerBlockTitle">Caissier:</div>';
            $html .= '<div class="headerBlockContent">';
            $html .= $user->getFullName($langs);
            $html .= '</div>';
            $html .= '</div>';
        }

        if (!is_null($caisse)) {
            $entrepot = $caisse->getChildObject('entrepot');
            if (isset($entrepot->id) && $entrepot->id) {
                $html .= '<div class="headerBlock">';
                $html .= '<i class="fa fa-building"></i>';
                $html .= '<div class="headerBlockTitle">Centre:</div>';
                $html .= '<div class="headerBlockContent">';
                $html .= $entrepot->libelle;
                $html .= '</div>';
                $html .= '</div>';
            }

            $html .= '<div class="headerBlock">';
            $html .= '<i class="fa fa-calculator"></i>';
            $html .= '<div class="headerBlockTitle">Caisse:</div>';
            $html .= '<div class="headerBlockContent">';
            $html .= $caisse->getData('name');
            $html .= '</div>';
            $html .= '</div>';
        }

        return $html;
    }

    public function renderContentHtml()
    {
        $html = '';

        $caisse = $this->getUserCaisse();

        if (!BimpObject::objectLoaded($caisse)) {
            $html .= $this->renderOpenCaisseHtml();
        } else {
            $tabs = array(
                array(
                    'id'      => 'ventes',
                    'title'   => 'Ventes',
                    'content' => $this->renderVentesTabHtml()
                ),
                array(
                    'id'      => 'clients',
                    'title'   => 'Clients',
                    'content' => $this->renderClientsTabHtml()
                ),
                array(
                    'id'      => 'mvt_fonds',
                    'title'   => 'Mouvements de fonds',
                    'content' => $this->renderMvtFondsTabHtml($caisse)
                ),
                array(
                    'id'      => 'paiements',
                    'title'   => 'Historique des paiements',
                    'content' => $this->renderSessionPaymentsTab($caisse)
                )
            );

            $html .= BimpRender::renderNavTabs($tabs);
        }

        return $html;
    }

    public function renderOpenCaisseHtml()
    {
        $html = '';

        global $user;

        if (!isset($user->id) || !$user->id) {
            $html .= BimpRender::renderAlerts('Aucun utilisateur connecté. Veuillez vous authentifier');
        } else {
            $id_entrepot = BimpTools::getValue('id_entrepot', 0);

            $caisses = array();

            if ($id_entrepot) {
                $caisse_instance = BimpObject::getInstance($this->module, 'BC_Caisse');

                foreach ($caisse_instance->getList(array(
                    'id_entrepot' => (int) $id_entrepot,
                    'status'      => 0
                        ), null, null, 'id', 'asc', 'array', array(
                    'id', 'name'
                )) as $caisse) {
                    $caisses[$caisse['id']] = $caisse['name'];
                }
            }

            $html .= '<div class="buttonsContainer align-right">';
            $caisse = BimpObject::getInstance('bimpcaisse', 'BC_Caisse');
            $onclick = $caisse->getJsActionOnclick('connectUser', array(
                'id_entrepot' => $id_entrepot
                    ), array(
                'form_name' => 'user_connect'
            ));
            $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
            $html .= '<i class="fas fa5-power-off iconLeft"></i>Se connecter à une caisse déjà ouverte';
            $html .= '</button>';

            BimpObject::loadClass('bimpcaisse', 'BC_CaisseSession');

            $id_session = (int) BC_CaisseSession::getUserLastClosedSession($user - id);

            if ($id_session) {
                $recap_url = DOL_URL_ROOT . '/bimpcore/view.php?module=bimpcaisse&object_name=BC_CaisseSession&id_object=' . $id_session . '&view=recap';
                $html .= '<button class="btn btn-default" onclick="window.open(\'' . $recap_url . '\', \'Récapitulatif session de caisse\', \'menubar=no, status=no, width=827, height=900\');">';
                $html .= BimpRender::renderIcon('fas_print', 'iconLeft') . ' Dernière session de caisse';
                $html .= '</button>';
            }

            $html .= '</div>';

            $html .= '<div id="openCaisseForm">';

            $html .= '<div class="freeForm">';
            $html .= '<div class="freeFormTitle">Ouverture de caisse</div>';
            $html .= '<div class="freeFormContent">';

            $html .= '<div class="freeFormRow">';
            $html .= '<div class="freeFormLabel">Centre: </div>';
            $html .= '<div class="freeFormInput">';
            $html .= BimpInput::renderInput('search_entrepot', 'id_entrepot', $id_entrepot);
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div id="caisseSelectContainer">';
            $html .= $this->renderCaisseSelect($caisses);
            $html .= '</div>';

            $html .= '<div class="freeFormAjaxResult">';
            $html .= '</div>';

            $html .= '<div class="freeFormSubmit rightAlign">';
            $html .= '<button id="openCaisseButton" type="button" class="btn btn-primary btn-large' . (!count($caisses) ? ' disabled' : '') . '"';
            $html .= ' onclick="openCaisse($(this), 0);">';
            $html .= '<i class="fa fa-check iconLeft"></i>Valider';
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>';
        }

        return $html;
    }

    public function renderCaisseSelect($caisses = null)
    {
        $html = '';

        if (is_null($caisses)) {
            $id_entrepot = BimpTools::getValue('id_entrepot', 0);

            if ($id_entrepot) {
                $instance = BimpObject::getInstance($this->module, 'BC_Caisse');

                $caisses = array();

                foreach ($instance->getList(array(
                    'id_entrepot' => $id_entrepot,
                    'status'      => 0
                        ), null, null, 'id', 'asc', 'array', array(
                    'id', 'name'
                )) as $caisse) {
                    $caisses[(int) $caisse['id']] = $caisse['name'];
                }
            }
        }

        if (count($caisses)) {
            $html .= '<div class="freeFormRow">';
            $html .= '<div class="freeFormLabel">Caisse: </div>';
            $html .= '<div class="freeFormInput">';
            $html .= '<select name="id_caisse" id="id_caisse">';
            foreach ($caisses as $id_caisse => $name) {
                $html .= '<option value="' . $id_caisse . '">' . $name . '</option>';
            }
            $html .= '</select>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="freeFormRow">';
            $html .= '<div class="freeFormLabel">Montant du fonds de caisse: </div>';
            $html .= '<div class="freeFormInput">';

            $html .= BimpInput::renderInput('text', 'fonds', '', array(
                        'data'        => array(
                            'data_type' => 'number',
                            'decimals'  => 2,
                            'min'       => 0,
                            'unsigned'  => 1
                        ),
                        'addon_right' => '<i class="fa fa-euro"></i>'
            ));

            $html .= '</div>';
            $html .= '</div>';
        } else {
            $html .= BimpRender::renderAlerts('Aucune caisse disponible pour ce centre');
        }

        return $html;
    }

    public function renderCloseCaisseHtml()
    {
        $html = '';

        global $user;

        if (!isset($user->id) || !$user->id) {
            $html .= BimpRender::renderAlerts('Aucun utilisateur connecté. Veuillez vous authentifier');
        } else {
            $caisse = $this->getUserCaisse();
            $errors = array();
            if (!BimpObject::objectLoaded($caisse)) {
                $errors[] = 'Aucune caisse ouverte pour l\'utilisateur connecté';
            } else {
                $this->isCaisseValide($caisse, $errors);
            }

            if (count($errors)) {
                $html .= BimpRender::renderAlerts($errors);
            } else {
                $rows = array();
                $buttons = array();

                $rows[] = array(
                    'label' => 'Caisse',
                    'input' => '<strong>' . $caisse->getData('name') . '</strong>'
                );

                $rows[] = array(
                    'label' => 'Montant du fonds de caisse',
//                    'input' => BimpInput::renderInput('text', 'fonds', '', array(
//                        'data'        => array(
//                            'data_type' => 'number',
//                            'decimals'  => 2,
//                            'min'       => 0,
//                            'unsigned'  => 1
//                        ),
//                        'addon_right' => '<i class="fa fa-euro"></i>'
//                    ))
                    'input' => BimpRender::renderCompteurCaisse('fonds')
                );

                $button = '<button id="cancelCloseCaisseButton" type="button" class="btn btn-danger btn-large buttonLeft">';
                $button .= '<i class="fa fa-times iconLeft"></i>Annuler';
                $button .= '</button>';
                $buttons[] = $button;

                $button = '<button id="submitCloseCaisseButton" type="button" class="btn btn-primary btn-large"';
                $button .= ' onclick="closeCaisse($(this), 0);">';
                $button .= '<i class="fa fa-check iconLeft"></i>Valider';
                $button .= '</button>';
                $buttons[] = $button;

                $html .= '<div id="closeCaisseForm">';
                $html .= BimpRender::renderFreeForm($rows, $buttons, 'Fermeture de caisse');
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderVentesTabHtml()
    {
        global $user;

        $html = '';

        $caisse = $this->getUserCaisse();

        $html .= '<div id="current_params">';
        if (BimpObject::objectLoaded($caisse)) {
            $html .= '<input type="hidden" name="id_user" value="' . (int) $user->id . '"/>';
            $html .= '<input type="hidden" name="id_entrepot" value="' . (int) $caisse->getData('id_entrepot') . '"/>';
            $html .= '<input type="hidden" name="id_caisse" value="' . (int) $caisse->id . '"/>';
            $html .= '<input type="hidden" name="caisse_name" value="' . $caisse->getData('name') . '"/>';
        } else {
            $html .= '<input type="hidden" name="id_user" value="0"/>';
            $html .= '<input type="hidden" name="id_entrepot" value="0"/>';
            $html .= '<input type="hidden" name="id_caisse" value="0"/>';
            $html .= '<input type="hidden" name="caisse_name" value=""/>';
        }
        $html .= '</div>';

        $html .= '<div id="currentVenteContainer" class="col-lg-12">';

        $html .= '<div id="currenVenteContent">';
        $html .= '</div>';

        $html .= '<div class="footer_buttons">';

        // Bouton abandon de la vente:
        $html .= '<button id="cancelCurrentVenteButton" type="button" class="btn btn-danger btn-large"';
        $html .= ' onclick="saveCurrentVente($(this), 0);">';
        $html .= '<i class="fa fa-times iconLeft"></i>Abandonner</button>';

        // Bouton Valider la vente:
        $html .= '<button id="validateCurrentVenteButton" type="button" class="btn btn-success btn-large disabled"';
        $html .= ' onclick="saveCurrentVente($(this), 2);">';
        $html .= '<i class="fa fa-check iconLeft"></i>Valider la vente</button>';

        // Bouton Enregistrer en tant que brouillon:
        $html .= '<button id="saveCurrentVenteButton" type="button" class="btn btn-default btn-large"';
        $html .= ' onclick="saveCurrentVente($(this), 1);">';
        $html .= '<i class="fa fa-save iconLeft"></i>Enregistrer en tant que brouillon</button>';
        $html .= '</div>';

        $html .= '</div>';

        $html .= '<div id="listVentesContainer" class="col-lg-12">';

        $html .= '<div id="venteToolbar" class="buttonsContainer">';
        // Bouton nouvelle vente: 
        $html .= '<button id="newVenteButton" class="btn btn-primary btn-large" type="button">';
        $html .= '<i class="fa fa-plus iconLeft"></i>Nouvelle vente';
        $html .= '</button>';

        // Bouton paiement factures:
        $html .= '<button id="newPaymentButton" class="btn btn-default btn-large" type="button">';
        $html .= '<i class="fa fa-euro iconLeft"></i>Paiement factures';
        $html .= '</button>';

        // Bouton Fermer caisse:
        $html .= '<button id="closeCaisseButton" class="btn btn-danger btn-large" type="button">';
        $html .= '<i class="fa fa-times iconLeft"></i>Fermer la caisse';
        $html .= '</button>';

        // Bouton déconnexion utilisateur:
        $onclick = $caisse->getJsActionOnclick('disconnectUser');
        $html .= '<button id="disconnectUserButton" class="btn btn-default btn-large" type="button" onclick="' . $onclick . '">';
        $html .= '<i class="fas fa5-power-off iconLeft"></i>Se déconnecter de la caisse';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div id="venteErrors"></div>';

        if (!is_null($caisse)) {
            $html .= $this->renderCloseCaisseHtml();

            $list = new BC_ListTable(BimpObject::getInstance($this->module, 'BC_Vente'), 'default', 1, 0, 'Dernières ventes');
            $list->addFieldFilterValue('status', array(
                'operator' => '>',
                'value'    => 0
            ));
            $list->addFieldFilterValue('id_caisse_session', (int) $caisse->getData('id_current_session'));

            $html .= $list->renderHtml();
        }

        $html .= '</div>';

        return $html;
    }

    public function renderClientsTabHtml()
    {
        $client = BimpObject::getInstance('bimpcore', 'Bimp_Client');
        $list = new BC_ListTable($client, 'clients_caisse');
        return $list->renderHtml();
    }

    public function renderMvtFondsTabHtml(BC_Caisse $caisse)
    {
        $errors = array();

        if (!$this->isCaisseValide($caisse, $errors)) {
            return BimpRender::renderAlerts($errors);
        }

        $caisseMvt = BimpObject::getInstance('bimpcaisse', 'BC_CaisseMvt');
        $list = new BC_ListTable($caisseMvt, 'caisse', 1, null, 'Mouvements de fonds pour la caisse "' . $caisse->getData('name') . '"', 'fas_exchange-alt');
        $list->addFieldFilterValue('id_entrepot', (int) $caisse->getData('id_entrepot'));
        $list->addFieldFilterValue('id_caisse', (int) $caisse->id);
        $html .= $list->renderHtml();

        return $html;
    }

    public function renderSessionPaymentsTab(BC_Caisse $caisse)
    {
        $html = '';
        $errors = array();

        if (!$this->isCaisseValide($caisse, $errors)) {
            return BimpRender::renderAlerts($errors);
        }

        $sql = 'SELECT MAX(id) as id FROM ' . MAIN_DB_PREFIX . 'bc_caisse_session WHERE id_caisse = ' . (int) $caisse->id . ' AND id_user_closed > 0';
        $result = BimpCache::getBdb()->executeS($sql, 'array');

        if (isset($result[0]['id']) && (int) $result[0]['id']) {
            $html .= '<div class="buttonsContainer align-right">';
            $recap_url = DOL_URL_ROOT . '/bimpcore/view.php?module=bimpcaisse&object_name=BC_CaisseSession&id_object=' . (int) $result[0]['id'] . '&view=recap';
            $html .= '<button class="btn btn-default" onclick="window.open(\'' . $recap_url . '\', \'Récapitulatif session de caisse\', \'menubar=no, status=no, width=827, height=900\');">';
            $html .= BimpRender::renderIcon('fas_print', 'iconLeft') . ' Session de caisse précédente';
            $html .= '</button>';
            $html .= '</div>';
        }

        $session = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_CaisseSession', (int) $caisse->getData('id_current_session'));
        if (!$session->isLoaded()) {
            $html .= BimpRender::renderAlerts('Aucune session valide en cours pour cette caisse');
        } else {
            $html .= $session->renderView('default', true, 1);
        }

        $bc_paiement = BimpObject::getInstance('bimpcaisse', 'BC_Paiement');
        $list = new BC_ListTable($bc_paiement, 'session', 1, null, 'Liste des paiements - caisse: "' . $caisse->getData('name') . '"', 'fas_hand-holding-usd');
        $list->addFieldFilterValue('id_caisse', (int) $caisse->id);
        $list->addFieldFilterValue('id_caisse_session', (int) $caisse->getData('id_current_session'));
        $html .= $list->renderHtml();

        return $html;
    }

    // Traitements Ajax:

    protected function ajaxProcessOpenCaisse()
    {
        $errors = array();
        $html = '';

        global $user;

        if (!isset($user->id) || !$user->id) {
            $errors[] = 'Aucun utilisateur connecté. Authentification nécessaire';
        }

        $id_caisse = (int) BimpTools::getValue('id_caisse', 0);
        $fonds = (float) BimpTools::getValue('fonds', 0);
        $confirm_fonds = (int) BimpTools::getValue('confirm_fonds', 0);
        $need_confirm_fonds = 0;

        if (!$id_caisse) {
            $errors[] = 'Aucune caisse sélectionnée';
        }
        if (!$fonds) {
            $errors[] = 'Montant du fonds de caisse non renseigné';
        }

        BimpObject::loadClass('bimpcaisse', 'BC_Caisse');
        $id_user_caisse = (int) BC_Caisse::getUserCaisse((int) $user->id);
        if ($id_user_caisse) {
            if ($id_user_caisse === $id_caisse) {
                $errors[] = 'Vous êtes déjà connecté à cette caisse';
            } else {
                $user_caisse = BimpCache::getBimpObjectInstance($this->module, 'BC_Caisse', $id_user_caisse);
                if (BimpObject::objectLoaded($user_caisse)) {
                    $caisse_label = '"' . $user_caisse->getData('name') . '"';
                } else {
                    $caisse_label = ' d\'ID ' . $id_user_caisse;
                }
                $errors[] = 'Vous êtes déjà connecté à la caisse ' . $caisse_label;
            }
        }

        if (!count($errors)) {
            $caisse = BimpCache::getBimpObjectInstance($this->module, 'BC_Caisse', $id_caisse);
            if (!$caisse->isLoaded()) {
                $errors[] = 'Cette caisse n\'est pas enregistrée';
            } else {
                if ((int) $caisse->getData('status')) {
                    $errors[] = 'Cette caisse est déjà ouverte sur un autre poste';
                } else {
                    $current_fonds = (float) $caisse->getData('fonds');

                    if ($fonds !== $current_fonds) {
                        if (!$confirm_fonds) {
                            $msg = 'Un écart avec le fonds de caisse enregistré a été constaté (Fonds actuel: ' . BimpTools::displayMoneyValue($current_fonds, 'EUR') . ').<br/>';
                            $msg .= 'Confirmez-vous le montant du fonds de caisse indiqué?';
                            $html = BimpRender::renderAlerts($msg, 'warning');
                            $need_confirm_fonds = 1;
                        } else {
                            global $user;
                            $msg = 'Correction du fonds de caisse suite à un différentiel constaté à l\'ouverture par ' . $user->getNomUrl();
                            $correction_errors = $caisse->correctFonds($fonds, $msg);
                            if (count($correction_errors)) {
                                $errors[] = 'Echec de la correction du fonds de caisse';
                                $errors = array_merge($errors, $correction_errors);
                            } else {
                                $fonds = $caisse->getSavedData('fonds');
                                $caisse->set('fonds', $fonds);
                            }
                        }
                    }

                    // Création de la session: 
                    if (!$need_confirm_fonds && !count($errors)) {
                        $session = BimpObject::getInstance($this->module, 'BC_CaisseSession');
                        $session_errors = $session->validateArray(array(
                            'id_caisse'    => (int) $id_caisse,
                            'fonds_begin'  => $fonds,
                            'date_open'    => date('Y-m-d H:i:s'),
                            'id_user_open' => (int) $user->id
                        ));

                        if (!count($session_errors)) {
                            $session_errors = $session->create();
                            if (!count($session_errors)) {
                                // Mise à jour de la caisse:
                                $caisse->set('id_current_session', (int) $session->id);
                                $caisse->set('status', 1);
                                $session_errors = $caisse->update();

                                // Enregistrement de l'utilisateur: 
                                $errors = $caisse->connectUser($user->id);
                            }
                        }

                        if (count($session_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($session_errors, 'Echec de l\'ouverture de la caisse');
                        } else {
                            $html = BimpRender::renderAlerts('Ouverture de la caisse "' . $caisse->getData('name') . '" effectuée avec succès', 'success');
                        }
                    }
                }
            }
        }

        die(json_encode(array(
            'errors'             => $errors,
            'html'               => $html,
            'need_confirm_fonds' => $need_confirm_fonds,
            'request_id'         => BimpTools::getValue('request_id', 0),
        )));
    }

    protected function ajaxProcessCloseCaisse()
    {
        $errors = array();
        $html = '';

        global $user;

        if (!isset($user->id) || !$user->id) {
            $errors[] = 'Aucun utilisateur connecté. Authentification nécessaire';
        }

        $id_caisse = (int) BimpTools::getValue('id_caisse', 0);
        $fonds = (float) BimpTools::getValue('fonds', 0);
        $confirm_fonds = (int) BimpTools::getValue('confirm_fonds', 0);
        $need_confirm_fonds = 0;
        $id_entrepot = 0;
        $recap_url = '';
        $recap_width = 827;

        if (!$id_caisse) {
            $errors[] = 'Aucune caisse sélectionnée';
        }
        if (!$fonds) {
            $errors[] = 'Montant du fonds de caisse non renseigné';
        }

        if (!count($errors)) {
            $caisse = BimpCache::getBimpObjectInstance($this->module, 'BC_Caisse', $id_caisse);

            if ($this->isCaisseValide($caisse, $errors)) {
                $id_entrepot = (int) $caisse->getData('id_entrepot');
                $session = $caisse->getChildObject('current_session');
                if (is_null($session) || !$session->isLoaded()) {
                    $errors[] = 'Session de caisse invalide';
                } else {
                    $current_fonds = (float) $caisse->getData('fonds');

                    if ($fonds !== $current_fonds) {
                        if (!$confirm_fonds) {
                            $msg = 'Un écart avec le fonds de caisse théorique a été constaté (Fonds actuel: ' . BimpTools::displayMoneyValue($current_fonds, 'EUR') . ').<br/>';
                            $msg .= 'Confirmez-vous le montant du fonds de caisse indiqué?';
                            $html = BimpRender::renderAlerts($msg, 'warning');
                            $need_confirm_fonds = 1;
                        } else {
                            global $user;
                            $msg = 'Correction du fonds de caisse suite à un différentiel constaté à la fermeture par ' . $user->getNomUrl();
                            $correction_errors = $caisse->correctFonds($fonds, $msg);
                            if (count($correction_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($correction_errors, 'Echec de la correction du fonds de caisse');
                            } else {
                                $fonds = $caisse->getSavedData('fonds');
                            }
                            $caisse->set('fonds', $fonds);
                        }
                    }

                    // Fermeture de la session: 
                    if (!$need_confirm_fonds) {
                        $session->set('date_closed', date('Y-m-d H:i:s'));
                        $session->set('id_user_closed', (int) $user->id);
                        $session->set('fonds_end', $fonds);
                        $session_errors = $session->update();
                        if (!count($session_errors)) {
                            $caisse->set('id_current_session', 0);
                            $caisse->set('status', 0);
                            $session_errors = $caisse->update();
                        }
                        if (count($session_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($session_errors, 'Echec de la fermeture de la caisse');
                        } else {
                            $errors = array_merge($errors, $caisse->disconnectAllUsers());

                            $html = BimpRender::renderAlerts('Fermeture de la caisse "' . $caisse->getData('name') . '" effectuée avec succès', 'success');

                            $recap_url = DOL_URL_ROOT . '/bimpcore/view.php?module=bimpcaisse&object_name=BC_CaisseSession&id_object=' . $session->id . '&view=recap';
                            $recap_width = BC_Caisse::$windowWidthByDpi[(int) $caisse->getData('printer_dpi')];
//                            $recap_url = 'window.open(\'' . $url . '\', \'Récapitulatif session de caisse\', "menubar=no, status=no, width=' . BC_Caisse::$windowWidthByDpi[(int) $caisse->getData('printer_dpi')] . ', height=900");';
                        }
                    }
                }
            }
        }

        die(json_encode(array(
            'errors'             => $errors,
            'html'               => $html,
            'need_confirm_fonds' => $need_confirm_fonds,
            'id_entrepot'        => $id_entrepot,
            'request_id'         => BimpTools::getValue('request_id', 0),
            'recap_url'          => $recap_url,
            'recap_width'        => $recap_width
        )));
    }

    public function ajaxProcessLoadNewVente()
    {
        $errors = array();

        $id_caisse = (int) BimpTools::getValue('id_caisse', 0);
        $id_client = (int) BimpTools::getValue('id_client', 0);

        if (!$id_caisse) {
            $errors[] = 'ID de la caisse absent';
        } else {
            $caisse = BimpCache::getBimpObjectInstance($this->module, 'BC_Caisse', $id_caisse);

            if ($this->isCaisseValide($caisse, $errors)) {
                $caisse = BimpCache::getBimpObjectInstance($this->module, 'BC_Caisse', $id_caisse);
                $vente = BimpObject::getInstance($this->module, 'BC_Vente');
                $errors = $vente->validateArray(array(
                    'status'            => 1,
                    'id_caisse'         => $id_caisse,
                    'id_caisse_session' => (int) $caisse->getData('id_current_session'),
                    'id_entrepot'       => (int) $caisse->getData('id_entrepot'),
                    'id_client'         => $id_client
                ));

                if (!count($errors)) {
                    $errors = $vente->create();
                }

                $data = array();
                $html = '';

                if (!count($errors)) {
                    $view = new BC_View($vente, 'creation');
                    $html = $view->renderHtml();
                    $data = $vente->getAjaxData();
                }
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0),
            'vente_data' => $data
        )));
    }

    public function ajaxProcessLoadVenteData()
    {
        $errors = array();
        $data = array();

        $id_vente = (int) BimpTools::getValue('id_vente');

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        } else {
            $vente = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Vente', $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe pas';
            } else {
                $data = $vente->getAjaxData();
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'vente_data' => $data,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    public function ajaxProcessLoadVente()
    {
        $errors = array();

        $id_caisse = (int) BimpTools::getValue('id_caisse', 0);
        $id_vente = (int) BimpTools::getValue('id_vente', 0);

        if (!$id_caisse) {
            $errors[] = 'ID de la caisse absent';
        }

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (!count($errors)) {
            $caisse = BimpCache::getBimpObjectInstance($this->module, 'BC_Caisse', $id_caisse);

            if ($this->isCaisseValide($caisse, $errors)) {
                $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', $id_vente);

                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $vente->set('status', 1);
                    $vente->set('id_caisse', $id_caisse);
                    $vente->set('id_caisse_session', (int) $caisse->getData('id_current_session'));
                    $vente->set('id_entrepot', (int) $caisse->getData('id_entrepot'));

                    $errors = $vente->update();

                    $data = array();
                    $html = '';

                    if (!count($errors)) {
                        $view = new BC_View($vente, 'creation');
                        $html = $view->renderHtml();
                        $data = $vente->getAjaxData();
                    }
                }
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0),
            'vente_data' => $data
        )));
    }

    protected function ajaxProcessSaveVenteStatus()
    {
        $errors = array();
        $validate_errors = array();
        $validate = 0;
        $ticket_html = '';
        $ticket_errors = array();

        $id_vente = (int) BimpTools::getValue('id_vente');
        $status = BimpTools::getValue('status');

        $vente = null;

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        } else {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe pas';
            }
        }

        if (is_null($status)) {
            $errors[] = 'Nouveau statut de la vente absent';
        }

        $success = '';
        if (!count($errors)) {
            if ($vente->getData('status') === 2) {
                $errors[] = 'Cette vente ne peut pas être modifiée car elle a été validée';
            } else {
                if ((int) $status === 2) {
//                    if ($vente->getData('status') === 0) {
//                        $errors[] = 'Cette vente ne peut pas être validée car elle a été annulée';
//                    } else {
                    $success = 'Vente validée avec succès';
                    $validate = (int) $vente->validateVente($validate_errors);
                    if (!$validate) {
                        $errors[] = 'Cette vente ne peut pas être validée';
                    } else {
                        $ticket_html = $vente->renderTicketHtml($ticket_errors);
                    }
                    if (count($validate_errors)) {
                        $msg = 'Erreur validation vente ' . $vente->id . "\n";
                        foreach ($validate_errors as $e) {
                            $msg .= ' - ' . $e . "\n";
                        }
                        dol_syslog($msg, LOG_DEBUG);
                    }
//                    }
                } else {
                    if ((int) $status === 1) {
                        $success = 'Vente enregistrée avec succès';
                    } else {
                        $success = 'Vente abandonnée';
                    }
                    $vente->set('status', (int) $status);
                    $errors = array_merge($errors, $vente->update());
                }
            }
        }

        die(json_encode(array(
            'errors'          => $errors,
            'validate_errors' => $validate_errors,
            'validate'        => $validate,
            'success'         => $success,
            'ticket_html'     => $ticket_html,
            'ticket_errors'   => $ticket_errors,
            'request_id'      => BimpTools::getValue('request_id', 0),
        )));
    }

    protected function ajaxProcessSaveCommercial()
    {
        $errors = array();

        $id_vente = BimpTools::getValue('id_vente', 0);
        $id_user_resp = BimpTools::getValue('id_user_resp', 0);

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (!$id_user_resp) {
            $errors[] = 'Aucun utilisateur spécifié pour le commercial';
        }

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', (int) $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $vente->set('id_user_resp', $id_user_resp);
                    $errors = $vente->update();
                }
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => 'Commercial de la vente enregistré avec succès',
            'request_id' => BimpTools::getValue('request_id', 0),
        )));
    }

    protected function ajaxProcessSaveNotePlus()
    {
        $errors = array();
        $id_vente = BimpTools::getValue('id_vente', 0);
        $note_plus = BimpTools::getValue('note_plus', '');

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }


        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', (int) $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $vente->set('note_plus', $note_plus);
                    $errors = $vente->update();
                }
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => 'Note de la vente enregistrée avec succès',
            'request_id' => BimpTools::getValue('request_id', 0),
        )));
    }

    protected function ajaxProcessSaveCondReglement()
    {
        $errors = array();

        $id_vente = BimpTools::getValue('id_vente', 0);
        $id_cond = BimpTools::getValue('id_cond', 0);

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (!$id_cond) {
            $errors[] = 'Aucune condition de réglement spécifiée';
        }

        $vente_data = array();

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', (int) $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $vente->set('id_cond_reglement', $id_cond);
                    $errors = $vente->update();
                }
                $vente_data = $vente->getAjaxData();
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => 'Conditions de réglement mises à jour avec succès',
            'vente_data' => $vente_data,
            'request_id' => BimpTools::getValue('request_id', 0),
        )));
    }

    protected function ajaxProcessSaveVenteHt()
    {
        $errors = array();

        $vente_ht = BimpTools::getValue('vente_ht', null);
        $id_vente = BimpTools::getValue('id_vente', 0);

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (is_null($vente_ht)) {
            $errors[] = 'Champ "Vente au prix hors-taxes" non spécifié';
        } else {
            $vente_ht = (int) $vente_ht;
        }

        $vente_data = array();

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', (int) $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $vente->set('vente_ht', (int) $vente_ht);
                    $errors = $vente->update();
                }
                $vente_data = $vente->getAjaxData();
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => 'Mise à jour des conditions de vente effectuée avec succès',
            'vente_data' => $vente_data,
            'request_id' => BimpTools::getValue('request_id', 0),
        )));
    }

    public function ajaxProcessSaveClient()
    {
        $errors = array();

        $id_vente = (int) BimpTools::getValue('id_vente', 0);
        $id_client = (int) BimpTools::getValue('id_client', 0);

        $html = '';
        $discounts_html = '';

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if ($id_client === '') {
            $id_client = 0;
        }

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', (int) $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $current_client = (int) $vente->getData('id_client');
                    if ($current_client !== $id_client) {
                        $vente->set('id_client', (int) $id_client);
                        $vente->set('id_client_contact', 0);
                        $errors = $vente->update();
                        if (!count($errors)) {
                            $html = $vente->renderClientView();
                            $discounts_html = $vente->renderDiscountsAssociation();
                        }
                    }
                }
            }
        }

        die(json_encode(array(
            'errors'         => $errors,
            'html'           => $html,
            'discounts_html' => $discounts_html,
            'request_id'     => BimpTools::getValue('request_id', 0),
        )));
    }

    public function ajaxProcessSaveContact()
    {
        $errors = array();

        $id_vente = BimpTools::getValue('id_vente', 0);
        $id_contact = BimpTools::getValue('id_contact', 0);

        $html = '';

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', (int) $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $vente->set('id_client_contact', (int) $id_contact);
                    $errors = $vente->update();
                    if (!count($errors)) {
                        $html = $vente->renderContactView();
                    }
                }
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0),
        )));
    }

    public function ajaxProcessFindProduct()
    {
        $errors = array();

        $search = BimpTools::getValue('search', '');
        $id_vente = (int) BimpTools::getValue('id_vente', 0);

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (!$search) {
            $errors[] = 'Aucun code-barre ou numéro de série spécifié';
        }

        $result = array(
            'cart_html'   => '',
            'result_html' => ''
        );

        $vente_data = array();

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                $result = $vente->findArticleToAdd($search, $errors);
                $vente_data = $vente->getAjaxData();
            }
        }

        die(json_encode(array(
            'errors'      => $errors,
            'request_id'  => BimpTools::getValue('request_id', 0),
            'cart_html'   => $result['cart_html'],
            'result_html' => $result['result_html'],
            'vente_data'  => $vente_data
        )));
    }

    public function ajaxProcessSelectArticle()
    {
        $errors = array();

        $id_vente = (int) BimpTools::getValue('id_vente', 0);
        $id_object = (int) BimpTools::getValue('id_object', 0);
        $object_name = BimpTools::getValue('object_name', '');

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (!$id_object) {
            $errors[] = 'ID du produit ou de l\'équipement absent';
        }

        if (!$object_name) {
            $errors[] = 'Type d\'article absent';
        }

        $html = '';
        $vente_data = array();

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $html = $vente->selectArticle($id_object, $object_name, $errors);
                    $vente_data = $vente->getAjaxData();
                }
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0),
            'html'       => $html,
            'vente_data' => $vente_data
        )));
    }

    public function ajaxProcessSaveArticleQty()
    {
        $errors = array();

        $id_vente = BimpTools::getValue('id_vente', 0);
        $id_article = BimpTools::getValue('id_article', 0);
        $qty = BimpTools::getValue('qty', null);

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (!$id_article) {
            $errors[] = 'Aucun article spécifié';
        }

        if (is_null($qty)) {
            $errors[] = 'Quantité à enregistrée absente';
        }

        $vente_data = array();
        $total_ttc = 0;
        $stock = 0;

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', (int) $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $article = BimpCache::getBimpObjectInstance($this->module, 'BC_VenteArticle', (int) $id_article);
                    if (!$article->isLoaded()) {
                        $errors[] = 'Article non trouvé';
                    } else {
                        $article->set('qty', (int) $qty);
                        $errors = $article->update();

                        $total_ttc = (float) ((int) $article->getData('qty') * (float) $article->getData('unit_price_tax_in'));
                        $stock = (int) $article->getProductStock((int) $vente->getData('id_entrepot'));
                    }
                }
                $vente_data = $vente->getAjaxData();
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'vente_data' => $vente_data,
            'total_ttc'  => $total_ttc,
            'stock'      => $stock,
            'request_id' => BimpTools::getValue('request_id', 0),
        )));
    }

    public function ajaxProcessRemoveArticle()
    {
        $errors = array();

        $id_vente = BimpTools::getValue('id_vente', 0);
        $id_article = BimpTools::getValue('id_article', 0);

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (!$id_article) {
            $errors[] = 'Aucun article spécifié';
        }

        $vente_data = array();

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', (int) $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $article = BimpCache::getBimpObjectInstance($this->module, 'BC_VenteArticle', (int) $id_article);
                    if (!$article->isLoaded()) {
                        $errors[] = 'Article non trouvé';
                    } else {
                        $errors = $article->delete();
                    }
                }
                $vente_data = $vente->getAjaxData();
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'vente_data' => $vente_data,
            'request_id' => BimpTools::getValue('request_id', 0),
        )));
    }

    public function ajaxProcessDeleteRemise()
    {
        $errors = array();

        $id_vente = (int) BimpTools::getValue('id_vente', 0);
        $id_remise = BimpTools::getValue('id_remise', 0);

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (!$id_remise) {
            $errors[] = 'ID de la remise absent';
        }

        $html = '';
        $vente_data = array();

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $remise = BimpCache::getBimpObjectInstance($this->module, 'BC_VenteRemise', $id_remise);
                    $errors = $remise->delete();
                    $vente_data = $vente->getAjaxData();
                }
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0),
            'vente_data' => $vente_data
        )));
    }

    public function ajaxProcessAddPaiement()
    {
        $errors = array();

        $id_vente = (int) BimpTools::getValue('id_vente', 0);
        $code = BimpTools::getValue('code', '');
        $montant = BimpTools::getValue('montant', 0);

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (!$code) {
            $errors[] = 'Code du type de paiement absent';
        }

        if (!$montant) {
            $errors[] = 'Montant absent ou invalide';
        }

        $html = '';
        $vente_data = array();

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $html = $vente->addPaiement($code, $montant, $errors);
                }
                $vente_data = $vente->getAjaxData();
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0),
            'html'       => $html,
            'vente_data' => $vente_data
        )));
    }

    public function ajaxProcessDeletePaiement()
    {
        $errors = array();

        $id_vente = (int) BimpTools::getValue('id_vente', 0);
        $id_paiement = BimpTools::getValue('id_paiement', 0);

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (!$id_paiement) {
            $errors[] = 'ID du paiement absent';
        }

        $html = '';
        $vente_data = array();

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $paiement = BimpCache::getBimpObjectInstance($this->module, 'BC_VentePaiement', $id_paiement);
                    $errors = $paiement->delete();
                    $html = $vente->renderPaiementsLines();
                }
                $vente_data = $vente->getAjaxData();
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0),
            'html'       => $html,
            'vente_data' => $vente_data
        )));
    }

    protected function ajaxProcessLoadCaisseSelect()
    {
        die(json_encode(array(
            'html'       => $this->renderCaisseSelect(),
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessSearchEquipmentToReturn()
    {
        $errors = array();
        $warnings = array();
        $equipments = array();

        $serial = BimpTools::getValue('serial', '');
        $id_vente = (int) BimpTools::getValue('id_vente', 0);

        if (!$serial) {
            $errors[] = 'Aucun numéro de série spécifié';
        }

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        $vente = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Vente', $id_vente);
        if (!BimpObject::objectLoaded($vente)) {
            $errors[] = 'ID de la vente invalide';
        }

        $id_client = (int) $vente->getData('id_client');

        if (!count($errors)) {
            BimpObject::loadClass('bimpequipment', 'Equipment');
            BimpObject::loadClass('bimpequipment', 'BE_Place');

            $currentReturnedEquipments = $vente->getCurrentReturnedEquipments();
            $list = Equipment::findEquipments($serial);

            if (count($list)) {
                foreach ($list as $id_equipment) {
                    if (in_array($id_equipment, $currentReturnedEquipments)) {
                        $warnings[] = 'Un équipement correspondant à ce numéro de série a déjà été ajouté aux retours';
                        continue;
                    }
                    $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                    if (!BimpObject::objectLoaded($equipment)) {
                        continue;
                    }
                    $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $equipment->getData('id_product'));

                    if (!BimpObject::objectLoaded($product)) {
                        continue;
                    }

                    $eq_errors = array();
                    if (!$equipment->isAvailable(0, $eq_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($eq_errors);
                        continue;
                    }

                    $eq_warnings = $equipment->checkPlaceForReturn($id_client);
                    $place = $equipment->getCurrentPlace();

                    $label = $product->getRef() . ' - NS: ' . $equipment->getData('serial');

                    if (BimpObject::objectLoaded($place)) {
                        $label .= ' (Emplacement: ' . $place->getPlaceName() . ')';
                    } else {
                        $label .= ' (Aucun emplacement)';
                    }

                    $equipments[] = array(
                        'id'        => (int) $id_equipment,
                        'label'     => $label,
                        'id_client' => (BimpObject::objectLoaded($place) && (int) $place->getData('type') === BE_Place::BE_PLACE_CLIENT ? (int) $place->getData('id_client') : 0),
                        'warnings'  => $eq_warnings
                    );
                }
            }

            if (!count($equipments)) {
                $msg = 'Aucun équipement elligible au retour trouvé pour ce numéro de série';
                $errors[] = $msg;
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'equipments' => $equipments,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessRemoveReturn()
    {
        $errors = array();

        $id_vente = BimpTools::getValue('id_vente', 0);
        $id_return = BimpTools::getValue('id_return', 0);

        if (!$id_vente) {
            $errors[] = 'ID de la vente absent';
        }

        if (!$id_return) {
            $errors[] = 'Aucun article retourné spécifié';
        }

        $vente_data = array();

        if (!count($errors)) {
            $vente = BimpCache::getBimpObjectInstance($this->module, 'BC_Vente', (int) $id_vente);
            if (!$vente->isLoaded()) {
                $errors[] = 'Cette vente n\'existe plus';
            } else {
                if ($vente->getData('status') === 2) {
                    $errors[] = 'Cette vente ne peut pas être modifée car elle a été validée';
                } else {
                    $return = BimpCache::getBimpObjectInstance($this->module, 'BC_VenteReturn', (int) $id_return);
                    if (!$return->isLoaded()) {
                        $errors[] = 'Article non trouvé';
                    } else {
                        $errors = $return->delete();
                    }
                }
                $vente_data = $vente->getAjaxData();
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'vente_data' => $vente_data,
            'request_id' => BimpTools::getValue('request_id', 0),
        )));
    }
}
