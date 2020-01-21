<?php

if (!defined('BIMP_LIB')) {
    require_once __DIR__ . '/../Bimp_Lib.php';
}

class BimpDolObject extends BimpObject
{
    public static $dol_module = '';
    
    public function actionGeneratePdf($data, &$success, $errors = array(), $warnings = array())
    {
        $success = 'PDF généré avec succès';

        if ($this->isLoaded()) {
            if (!$this->isDolObject() || !method_exists($this->dol_object, 'generateDocument')) {
                $errors[] = 'Cette fonction n\'est pas disponible pour ' . $this->getLabel('the_plur');
            } else {
                if (!isset($data['model']) || !$data['model']) {
                    $data['model'] = $this->getModelPdf();
                }
                global $langs;
                $this->dol_object->error = '';
                $this->dol_object->errors = array();
                if ($this->dol_object->generateDocument($data['model'], $langs) <= 0) {
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Des erreurs sont survenues lors de la génération du PDF');
                } else {
                    $ref = dol_sanitizeFileName($this->getRef());
                    
                    if (isset(static::$files_module_part)) {
                        $module_part = static::$files_module_part;
                    } else {
                        $module_part = static::$dol_module;
                    }
                    $file = DOL_URL_ROOT . '/document.php?modulepart=' . $module_part . '&file='.$ref . '/' . $ref.".pdf";
                    if(method_exists($this, 'getFileUrl'))
                            $file = $this->getFileUrl($ref.'.pdf');
                    
                    $url = $file ;
                    $success_callback = 'window.open(\'' . $url . '\');';
                }
            }
        } else {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }
    
    
    public function renderLinkedObjectsTable($htmlP = '')
    {
        $html = '';
        if ($this->isLoaded()) {
            $objects = array();

            if ($this->isDolObject()) {
                $propal_instance = null;
                $facture_instance = null;
                $commande_instance = null;
                $commande_fourn_instance = null;
                foreach (BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db) as $item) {
                    switch ($item['type']) {
                        case 'propal':
                            $propal_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $item['id_object']);
                            if ($propal_instance->isLoaded()) {
                                $icon = $propal_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($propal_instance->getLabel()),
                                    'ref'      => $propal_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $propal_instance->displayData('datep'),
                                    'total_ht' => $propal_instance->displayData('total_ht'),
                                    'status'   => $propal_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'facture':
                            $facture_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $item['id_object']);
                            if ($facture_instance->isLoaded()) {
                                $icon = $facture_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($facture_instance->getLabel()),
                                    'ref'      => $facture_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $facture_instance->displayData('datef'),
                                    'total_ht' => $facture_instance->displayData('total'),
                                    'status'   => $facture_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'commande':
                            $commande_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $item['id_object']);
                            if ($commande_instance->isLoaded()) {
                                $icon = $commande_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($commande_instance->getLabel()),
                                    'ref'      => $commande_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $commande_instance->displayData('date_commande'),
                                    'total_ht' => $commande_instance->displayData('total_ht'),
                                    'status'   => $commande_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'order_supplier':
                            $commande_fourn_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $item['id_object']);
                            if ($commande_fourn_instance->isLoaded()) {
                                $icon = $commande_fourn_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($commande_fourn_instance->getLabel()),
                                    'ref'      => $commande_fourn_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $commande_fourn_instance->displayData('date_commande'),
                                    'total_ht' => $commande_fourn_instance->displayData('total_ht'),
                                    'status'   => $commande_fourn_instance->displayData('fk_statut')
                                );
                            }
                            break;

                        case 'invoice_supplier':
                            $facture_fourn_instance = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $item['id_object']);
                            if (BimpObject::objectLoaded($facture_fourn_instance)) {
                                $icon = $facture_fourn_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($facture_fourn_instance->getLabel()),
                                    'ref'      => $facture_fourn_instance->getNomUrl(0, true, true, 'full'),
                                    'date'     => $facture_fourn_instance->displayData('datef'),
                                    'total_ht' => $facture_fourn_instance->displayData('total_ht'),
                                    'status'   => $facture_fourn_instance->displayData('fk_statut')
                                );
                            }
                            break;
                        case 'contrat':
                            $contrat_instance = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat', (int) $item['id_object']);
                            if (BimpObject::objectLoaded($contrat_instance)) {
                                $icon = $contrat_instance->params['icon'];
                                $objects[] = array(
                                    'type'     => BimpRender::renderIcon($icon, 'iconLeft') . BimpTools::ucfirst($contrat_instance->getLabel()),
                                    'ref'      => $contrat_instance->getNomUrl(0, true, true, 'fiche_contrat'),
                                    'date'     => $contrat_instance->displayData('date_start'),
                                    'total_ht' => $contrat_instance->getTotalContrat() . "€",
                                    'status'   => $contrat_instance->displayData('statut')
                                );
                            }
                            break;
                    }
                }
            }

            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Type</th>';
            $html .= '<th>Réf.</th>';
            $html .= '<th>Date</th>';
            $html .= '<th>Montant HT</th>';
            $html .= '<th>Statut</th>';
//            $html .= '<th></th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            if (count($objects)) {
                foreach ($objects as $data) {
                    $htmlP .= '<tr>';
                    $htmlP .= '<td><strong>' . $data['type'] . '</strong></td>';
                    $htmlP .= '<td>' . $data['ref'] . '</td>';
                    $htmlP .= '<td>' . $data['date'] . '</td>';
                    $htmlP .= '<td>' . $data['total_ht'] . '</td>';
                    $htmlP .= '<td>' . $data['status'] . '</td>';
//                    $html .= '<td style="text-align: right">';
//                    
//                    $html .= BimpRender::renderRowButton('Supprimer le lien', 'trash', '');
//
//                    $html .= '</td>';
                    $htmlP .= '</tr>';
                }
            }
            if ($htmlP == '') {
                $htmlP .= '<tr>';
                $htmlP .= '<td colspan="5">' . BimpRender::renderAlerts('Aucun objet lié', 'info') . '</td>';
                $htmlP .= '</tr>';
            }

            $html .= $htmlP;
            $html .= '</tbody>';
            $html .= '</table>';

            $html = BimpRender::renderPanel('Objets liés', $html, '', array(
                        'foldable' => true,
                        'type'     => 'secondary-forced',
                        'icon'     => 'fas_link',
//                        'header_buttons' => array(
//                            array(
//                                'label'       => 'Lier à...',
//                                'icon_before' => 'plus-circle',
//                                'classes'     => array('btn', 'btn-default'),
//                                'attr'        => array(
//                                    'onclick' => ''
//                                )
//                            )
//                        )
            ));
        }

        return $html;
    }

    
    
    public function getModelPdf()
    {
        if ($this->field_exists('model_pdf')) {
            return $this->getData('model_pdf');
        }

        return '';
    }
}
