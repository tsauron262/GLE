<?php

class shipment {

    public $rowid = null;
    public $db = null;
    public $shipTo = 0;
    public $infos = array(
        'length' => 0,
        'width' => 0,
        'height' => 0,
        'weight' => 0
    );
    public $parts = array();
    public $upsInfos = array(
        'charges' => array(
            'transportation' => 0,
            'options' => 0,
            'total' => 0
        ),
        'billingWeight' => 0,
        'trackingNumber' => null,
        'identificationNumber' => null,
    );
    public $gsxInfos = array(
        'confirmation' => '',
        'pdfFileName' => '',
        'bulkReturnId' => null,
        'trackingURL' => ''
    );
    public $errors = array();

    public function __construct($db, $rowid = null) {
        $this->db = $db;
        if (!is_null($rowid)) {
            $this->rowid = $rowid;
            $this->load();
        }
    }

    public function setInfos($length, $width, $height, $weight) {
        $this->infos['length'] = $length;
        $this->infos['width'] = $width;
        $this->infos['height'] = $height;
        $this->infos['weight'] = $weight;
    }

    public function setUpsInfos($transp, $options, $total, $billingWeight, $trackingNbr, $identificationNbr) {
        $this->upsInfos['charges']['transportation'] = $transp;
        $this->upsInfos['charges']['options'] = $options;
        $this->upsInfos['charges']['total'] = $total;
        $this->upsInfos['billingWeight'] = $billingWeight;
        $this->upsInfos['trackingNumber'] = $trackingNbr;
        $this->upsInfos['identificationNumber'] = $identificationNbr;
    }

    public function setGsxInfos($confirmation, $bulkReturnId, $pdfFileName, $trackingURL) {
        $this->gsxInfos['confirmation'] = $confirmation;
        $this->gsxInfos['pdfFileName'] = $pdfFileName;
        $this->gsxInfos['bulkReturnId'] = $bulkReturnId;
        $this->gsxInfos['trackingURL'] = $trackingURL;
    }

    public function addPart($name, $partNumber, $newNumber, $poNumber, $repairNumber, $serial, $returnNbr) {
        $part = array(
            'name' => $name,
            'number' => $partNumber,
            'new_number' => $newNumber,
            'poNumber' => $poNumber,
            'repairNumber' => $repairNumber,
            'serial' => $serial,
            'returnOrderNumber' => $returnNbr
        );

        if (!is_null($this->rowid)) {
            $partId = $this->insertPart($part);
            if ($partId !== false) {
                $this->parts[$partId] = $part;
                return;
            }
        }
        $this->parts[] = $part;
    }

    public function create() {
        if (isset($this->rowid)) {
            return $this->update();
        }
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'synopsisapple_shipment (';
        $sql .= '`ship_to`, ';
        $sql .= '`length`, ';
        $sql .= '`width`, ';
        $sql .= '`height`, ';
        $sql .= '`weight`, ';
        $sql .= '`transportation_charges`, ';
        $sql .= '`options_charges`, ';
        $sql .= '`total_charges`, ';
        $sql .= '`billing_weight`, ';
        $sql .= '`tracking_number`, ';
        $sql .= '`identification_number`, ';
        $sql .= '`gsx_confirmation`, ';
        $sql .= '`gsx_return_id`, ';
        $sql .= '`gsx_pdf_name`, ';
        $sql .= '`gsx_tracking_url`';
        $sql .= ') ';

        $sql .= 'VALUES (';
        $sql .= (isset($this->shipTo) ? "'" . $this->shipTo . "'" : 'NULL') . ', ';
        $sql .= (int) $this->infos['length'] . ', ';
        $sql .= (int) $this->infos['width'] . ', ';
        $sql .= (int) $this->infos['height'] . ', ';
        $sql .= (int) $this->infos['weight'] . ', ';
        $sql .= (float) $this->upsInfos['charges']['transportation'] . ', ';
        $sql .= (float) $this->upsInfos['charges']['options'] . ', ';
        $sql .= (float) $this->upsInfos['charges']['total'] . ', ';
        $sql .= (float) $this->upsInfos['billingWeight'] . ', ';
        $sql .= (isset($this->upsInfos['trackingNumber']) ? "'" . $this->upsInfos['trackingNumber'] . "'" : 'NULL') . ', ';
        $sql .= (isset($this->upsInfos['identificationNumber']) ? "'" . $this->upsInfos['identificationNumber'] . "'" : 'NULL') . ', ';
        $sql .= (isset($this->gsxInfos['confirmation']) ? "'" . $this->gsxInfos['confirmation'] . "'" : 'NULL') . ', ';
        $sql .= (isset($this->gsxInfos['bulkReturnId']) ? "'" . $this->gsxInfos['bulkReturnId'] . "'" : 'NULL') . ', ';
        $sql .= (isset($this->gsxInfos['pdfFileName']) ? "'" . $this->gsxInfos['pdfFileName'] . "'" : 'NULL') . ', ';
        $sql .= (isset($this->gsxInfos['trackingURL']) ? "'" . $this->gsxInfos['trackingURL'] . "'" : 'NULL');
        $sql .= ');';

        if (!$this->db->query($sql)) {
            $error = 'Echec de l\'enregistrement en base de données<br/>';
            $error .= 'Requête SQL: ' . $sql . '<br/>';
            $error .= 'Message SQL: ' . $this->db->lasterror();
            $this->errors[] = $error;
            return false;
        }
        $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX . 'synopsisapple_shipment');

        $check = true;
        if (count($this->parts)) {
            $parts = $this->parts;
            $this->parts = array();
            foreach ($parts as $part) {
                $partId = $this->insertPart($part);
                if ($partId) {
                    $this->parts[$partId] = $part;
                } else
                    $check = false;
            }
        }
        return $check;
    }

    protected function insertPart($datas) {
        if (is_null($this->rowid))
            return 0;

        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'synopsisapple_shipment_parts (';
        $sql .= '`shipment_id`, ';
        $sql .= '`name`, ';
        $sql .= '`part_number`, ';
        $sql .= '`part_new_number`, ';
        $sql .= '`part_po_number`, ';
        $sql .= '`repair_number`, ';
        $sql .= '`serial`, ';
        $sql .= '`return_order_number`';
        $sql .= ') ';
        $sql .= 'VALUES (';
        $sql .= $this->rowid . ', ';
        $sql .= "'" . (isset($datas['name']) ? $datas['name'] : '') . '\', ';
        $sql .= "'" . (isset($datas['number']) ? $datas['number'] : '') . '\', ';
        $sql .= "'" . (isset($datas['new_number']) ? $datas['new_number'] : '') . '\', ';
        $sql .= "'" . (isset($datas['poNumber']) ? $datas['poNumber'] : '') . '\', ';
        $sql .= "'" . (isset($datas['repairNumber']) ? $datas['repairNumber'] : '') . '\', ';
        $sql .= "'" . (isset($datas['serial']) ? $datas['serial'] : '') . '\', ';
        $sql .= "'" . (isset($datas['returnOrderNumber']) ? $datas['returnOrderNumber'] : '') . "'";
        $sql .= ');';

        if (!$this->db->query($sql)) {
            $this->errors[] = 'Echec de l\'enregistrement en base des données du compasant "' .
                    (isset($datas['part_number']) ? $datas['part_number'] : 'référence inconnue') .
                    '"<br/>Erreur SQL: ' . $this->db->lasterror();
            return 0;
        }
        return $this->db->last_insert_id(MAIN_DB_PREFIX . 'synopsisapple_shipment_parts');
    }

    public function update() {
        if (is_null($this->rowid))
            return $this->create();

        $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'synopsisapple_shipment SET ';
        $sql .= '`ship_to` = ' . (isset($this->shipTo) ? "'" . $this->shipTo . "'" : 'NULL') . ', ';
        $sql .= '`length` = ' . (int) $this->infos['length'] . ', ';
        $sql .= '`width` = ' . (int) $this->infos['width'] . ', ';
        $sql .= '`height` = ' . (int) $this->infos['height'] . ', ';
        $sql .= '`weight` = ' . (int) $this->infos['weight'] . ', ';
        $sql .= '`transportation_charges` = ' . (float) $this->upsInfos['charges']['transportation'] . ', ';
        $sql .= '`options_charges` = ' . (float) $this->upsInfos['charges']['options'] . ', ';
        $sql .= '`total_charges` = ' . (float) $this->upsInfos['charges']['total'] . ', ';
        $sql .= '`billing_weight` = ' . (float) $this->upsInfos['billingWeight'] . ', ';
        $sql .= '`tracking_number` = ' . (isset($this->upsInfos['trackingNumber']) ? "'" . $this->upsInfos['trackingNumber'] . "'" : 'NULL') . ', ';
        $sql .= '`identification_number` = ' . (isset($this->upsInfos['identificationNumber']) ? "'" . $this->upsInfos['identificationNumber'] . "'" : 'NULL') . ', ';
        $sql .= '`gsx_confirmation` = ' . (isset($this->gsxInfos['confirmation']) ? "'" . $this->gsxInfos['confirmation'] . "'" : 'NULL') . ', ';
        $sql .= '`gsx_return_id` = ' . (isset($this->gsxInfos['bulkReturnId']) ? "'" . $this->gsxInfos['bulkReturnId'] . "'" : 'NULL') . ', ';
        $sql .= '`gsx_pdf_name` = ' . (isset($this->gsxInfos['pdfFileName']) ? "'" . $this->gsxInfos['pdfFileName'] . "'" : 'NULL') . ', ';
        $sql .= '`gsx_tracking_url` = ' . (isset($this->gsxInfos['trackingURL']) ? "'" . $this->gsxInfos['trackingURL'] . "'" : 'NULL');
        $sql .= ' WHERE `rowid` = ' . $this->rowid . ';';

        if (!$this->db->query($sql)) {
            $error = 'Echec de la mise à jour des données de l\'expédition d\'id ' . $this->rowid . '<br/>Erreur SQL: ' . $this->db->lasterror();
            $error .= '<br/><br/>Requête: ' . $sql;
            $this->errors[] = $error;
            return false;
        }
        return true;
    }

    public function load() {
        if (!isset($this->rowid)) {
            $this->errors[] = 'Pas d\ID';
            return false;
        }

        $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'synopsisapple_shipment WHERE `rowid` = ' . $this->rowid;

        $result = $this->db->query($sql);
        if ($this->db->num_rows($result) > 0) {
            $datas = $this->db->fetch_object($result);

            $this->shipTo = $datas->ship_to;
            $this->infos['length'] = $datas->length;
            $this->infos['width'] = $datas->width;
            $this->infos['height'] = $datas->height;
            $this->infos['weight'] = $datas->weight;

            $this->upsInfos['charge']['transportation'] = $datas->transportation_charges;
            $this->upsInfos['charge']['options'] = $datas->options_charges;
            $this->upsInfos['charge']['total'] = $datas->total_charges;

            $this->upsInfos['billingWeight'] = $datas->billing_weight;
            $this->upsInfos['trackingNumber'] = $datas->tracking_number;
            $this->ref = $datas->tracking_number;
            $this->upsInfos['identificationNumber'] = $datas->identification_number;

            $this->gsxInfos['confirmation'] = $datas->gsx_confirmation;
            $this->gsxInfos['bulkReturnId'] = $datas->gsx_return_id;
            $this->gsxInfos['pdfFileName'] = $datas->gsx_pdf_name;
            $this->gsxInfos['trackingURL'] = $datas->gsx_tracking_url;
        } else {
            $this->errors[] = 'Aucune entrée trouvée pour l\'ID ' . $this->rowid . '<br/>Requête: ' . $sql;
            return false;
        }

        $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'synopsisapple_shipment_parts WHERE `shipment_id` = ' . $this->rowid;
        $parts = $this->db->query($sql);
        if ($this->db->num_rows($result) > 0) {
            $this->parts = array();
            while ($p = $this->db->fetch_object($parts)) {
                $part = array(
                    'name' => $p->name,
                    'number' => $p->part_number,
                    'new_number' => $p->part_new_number,
                    'poNumber' => $p->part_po_number,
                    'repairNumber' => $p->repair_number,
                    'serial' => $p->serial,
                    'returnOrderNumber' => $p->return_order_number
                );
                $this->parts[$p->rowid] = $part;
            }
        } else {
            $this->errors[] = 'Aucun composant trouvé pour l\'ID ' . $this->rowid . '<br/>Requête: ' . $sql;
            return false;
        }
        return true;
    }

    public function delete() {
        $check = true;
        $sql = 'DELETE FROM ' . _DB_MAIN_PREFIX . 'synopsisapple_shipment_parts WHERE `rowid` = ' . $this->rowid;
        if (!$this->db->query($sql)) {
            $this->errors[] = 'Erreur SQL: ' . $this->db->lasterror();
            $check = false;
        }

        $sql = 'DELETE FROM ' . _DB_MAIN_PREFIX . 'synopsisapple_shipment WHERE `shipment_id` = ' . $this->rowid;
        if (!$this->db->query($sql)) {
            $this->errors[] = 'Erreur SQL: ' . $this->db->lasterror();
            $check = false;
        }
        return $check;
    }

    public function getInfosHtml() {
        $html = '<div class="container tabBar">';
        $html .= '<input type="hidden" id="shipmentId" name="shipmentId" value="' . $this->rowid . '"/>';
        $html .= '<table class="border"><thead></thead><tbody>';
        $html .= '<tr class="liste_titre"><td colspan="2">Informations sur le colis</td></tr>';
        $html .= '<tr><td>Composants à expédier</td><td id="partsListRecapContainer">';

        if (count($this->parts)) {
            $html .= '<table>';
            $html .= '<thead><tr>';
            $html .= '<th>Nom</th>';
            $html .= '<th>Réf.</th>';
            $html .= '<th>Nouv. Réf.</th>';
            $html .= '<th>N° de commande</th>';
            $html .= '<th>N° de série</th>';
            $html .= '<th>Réparation</th>';
            $html .= '<th>N° de retour</th>';
            $html .= '</tr></thead><tbody>';

            foreach ($this->parts as $part) {
                $html .= '<tr>';
                $html .= '<td>' . $part['name'] . '</td>';
                $html .= '<td>' . $part['number'] . '</td>';
                $html .= '<td>' . $part['new_number'] . '</td>';
                $html .= '<td>' . $part['poNumber'] . '</td>';
                $html .= '<td>' . $part['serial'] . '</td>';
                $html .= '<td>' . $part['repairNumber'] . '</td>';
                $html .= '<td>' . $part['returnOrderNumber'] . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        } else {
            $html .= 'Aucun composant spécifié';
        }
        $html .= '</td></tr>';

        $html .= '<tr><td>Longueur</td>';
        $html .= '<td>' . $this->infos['length'] . '&nbsp;cm.</td></tr>';

        $html .= '<tr><td>Largeur</td>';
        $html .= '<td>' . $this->infos['width'] . '&nbsp;cm.</td></tr>';

        $html .= '<tr><td>Hauteur</td>';
        $html .= '<td>' . $this->infos['height'] . '&nbsp;cm.</td></tr>';

        $html .= '<tr><td>Poids</td>';
        $html .= '<td>' . $this->infos['weight'] . '&nbsp;kg.</td></tr>';

        $html .= '<tr class="liste_titre">';
        $html .= '<td colspan="2">Informations sur l\'expédition</td>';
        $html .= '</tr>';

        $html .= '<tr><td>ID</td>';
        $html .= '<td>';
        if (isset($this->rowid) &&
                !empty($this->rowid))
            $html .= $this->rowid;
        else
            $html .= '<span class="error">inconnu</p>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr><td>N° Ship-to</td>';
        $html .= '<td>';
        if (isset($this->shipTo) &&
                !empty($this->shipTo))
            $html .= $this->shipTo;
        else
            $html .= '<span class="error">inconnu</p>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr><td>N° d\'expédition</td>';
        $html .= '<td>';
        if (isset($this->upsInfos['identificationNumber']) &&
                !empty($this->upsInfos['identificationNumber']))
            $html .= $this->upsInfos['identificationNumber'];
        else
            $html .= 'inconnu';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr><td>N° de suivi</td>';
        $html .= '<td>';
        if (isset($this->upsInfos['trackingNumber']) &&
                !empty($this->upsInfos['trackingNumber']))
            $html .= $this->upsInfos['trackingNumber'];
        else
            $html .= 'inconnu';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr><td>Coût du transport</td>';
        $html .= '<td>';
        if (isset($this->upsInfos['charges']['transportation']) &&
                !empty($this->upsInfos['charges']['transportation'])) {
            $html .= $this->upsInfos['charges']['transportation'];
            $html .= '&nbsp;&euro;';
        } else
            $html .= 'inconnu';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr><td>Coût des options</td>';
        $html .= '<td>';
        if (isset($this->upsInfos['charges']['options']) &&
                !empty($this->upsInfos['charges']['options'])) {
            $html .= $this->upsInfos['charges']['options'];
            $html .= '&nbsp;&euro;';
        } else
            $html .= 'inconnu';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr><td>Coût total</td>';
        $html .= '<td>';
        if (isset($this->upsInfos['charges']['total']) &&
                !empty($this->upsInfos['charges']['total'])) {
            $html .= $this->upsInfos['charges']['total'];
            $html .= '&nbsp;&euro;';
        } else
            $html .= 'inconnu';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr><td>Poids facturé</td>';
        $html .= '<td>';
        if (isset($this->upsInfos['billingWeight']) &&
                !empty($this->upsInfos['billingWeight']))
            $html .= $this->upsInfos['billingWeight'];
        else
            $html .= 'inconnu';
        $html .= '</td>';
        $html .= '</tr>';

        if (isset($this->gsxInfos['bulkReturnId']) && !empty($this->gsxInfos['bulkReturnId'])) {
            $html .= '<tr class="liste_titre">';
            $html .= '<td colspan="2">Informations GSX</td>';
            $html .= '</tr>';

            $html .= '<tr><td>ID de retour</td>';
            $html .= '<td>';
            if (isset($this->gsxInfos['bulkReturnId']) &&
                    !empty($this->gsxInfos['bulkReturnId'])) {
                $html .= $this->gsxInfos['bulkReturnId'];
            } else
                $html .= '<p class="error">inconnu</p>';
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr><td>Statut</td>';
            $html .= '<td>';
            if (isset($this->gsxInfos['confirmation']) &&
                    !empty($this->gsxInfos['confirmation'])) {
                $html .= $this->gsxInfos['confirmation'];
            } else
                $html .= 'inconnu';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $html .= '<p id="shipmentButtons" style="text-align: right">';

        // à modif:
        $filesDir = dirname(__FILE__) . '/labels/';

        if (isset($this->gsxInfos['trackingURL']) && !empty($this->gsxInfos['trackingURL'])) {
            $html .= '<a class="button" href="'.$this->gsxInfos['trackingURL'].'" target="_blank">';
            $html .= 'Page de suivi</a>';
        }
        if (isset($this->upsInfos['trackingNumber']) && !empty($this->upsInfos['trackingNumber'])) {
            // à modif: 
            if (file_exists($filesDir . '/ups/label' . $this->upsInfos['trackingNumber'] . '.gif') &&
                    file_exists($filesDir . '/ups/label' . $this->upsInfos['trackingNumber'] . '.html')) {
                $html .= '<a class="button" href="./labels/ups/label' . $this->upsInfos['trackingNumber'] . '.html' . '" target="_blank">';
                $html .= 'Etiquette d\'expédition</a>';
            } else {
                $html .= '<span class="error">Etiquette d\'expédition absente</span><br/>';
            }
        }
        if (isset($this->gsxInfos['pdfFileName']) && !empty($this->gsxInfos['pdfFileName'])) {
            if (file_exists($filesDir . '/gsx/' . $this->gsxInfos['pdfFileName'])) {
                // à modif: 
                $html .= '<a class="button" href="./labels/gsx/' . $this->gsxInfos['pdfFileName'] . '" target="_blank">';
                $html .= 'PDF de la liste des composants</a>';
            } else {
                // à modif:
                if (isset($this->gsxInfos['bulkReturnId']) && !empty($this->gsxInfos['bulkReturnId'])) {
                    $html .= '<span class="error">PDF de la liste des composants absent</span><br/>';
                }
            }
        }
        $html .= '</p>';
        $html .= '<p style="text-align: center"><button class="button" onclick="reinitPage()">Retour</button></p>';
        $html .= '</div>';

        $html .= $this->getGsxRegistrationForm();
        return $html;
    }

    public function getGsxRegistrationForm() {
        if (isset($this->gsxInfos['bulkReturnId']) && !empty($this->gsxInfos['bulkReturnId']))
            return '';

        $html = '<div id="gsxRequestContainer" class="container">';
        $html .= '<label for="optionalNote">Note pour l\'enregistrement sur GSX (optionnel, max. 800 caractères)</label><br/>';
        $html .= '<textarea id="optionalNote" name="optionalNote" maxlength="800" cols="120" rows="8"></textarea>';
        $html .= '<p style="text-align: center">';
        $html .= '<span class="button" onclick="registerGsxShipment()">Enregistrer l\'envoi sur GSX</span></p>';
        $html .= '</div>';
        $html .= '<div id="gsxRequestResults"></div>';
        return $html;
    }

    public function displayErrors() {
        $html = '';
        if (count($this->errors)) {
            $html .= '<p class="error">Erreur(s) SQL: <br/>';
            $i = 1;
            foreach ($this->errors as $error) {
                $html .= $i . '. ' . $error . '<br/>';
                $i++;
            }
            $html .= '</p>';
        } else {
            $html .= '<p class="info">Pas d\'erreur SQL</p>';
        }
        return $html;
    }

}
