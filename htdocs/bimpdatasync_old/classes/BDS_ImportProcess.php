<?php

include_once __DIR__ . '/BDS_ImportData.php';

abstract class BDS_ImportProcess extends BDS_Process
{

    const BDS_STATUS_IMPORTED = 0;
    const BDS_STATUS_IMPORTING = 1;
    const BDS_STATUS_IMPORT_FAIL = -1;

    public static $status_labels = array(
        0  => 'importé',
        1  => 'en cours d\'import',
        -1 => 'échec import'
    );

    public static function getClassName()
    {
        return 'BDS_ImportProcess';
    }

    // Traitement des objets Dolibarr:

    protected function saveObject(&$object, $label = null, $display_success = true, &$errors = null, $notrigger = false)
    {
        $isCurrentObject = $this->isCurrent($object);
        $object_name = $this->getObjectClass($object);
        if (is_null($label)) {
            $label = 'de l\'objet de type "' . $object_name . '"';
        }

        if (!is_null($object) && is_object($object)) {
            if (isset($object->id) && $object->id) {
                if (method_exists($object, 'update')) {
                    if (in_array($object_name, array('Product'))) {
                        $result = $object->update($object->id, $this->user, $notrigger);
                    } elseif (in_array($object_name, array('Societe', 'Contact'))) {
                        $result = $object->update($object->id, $this->user, true);
                    } else {
                        $result = $object->update($this->user);
                    }
                    if ($result <= 0) {
                        $msg = 'Echec de la mise à jour ' . $label;
                        if (!$isCurrentObject) {
                            $msg .= ' d\'ID: ' . $object->id;
                        }
                        if (!is_null($errors)) {
                            $errors[] = $msg;
                        }
                        if (isset($object->error) && $object->error) {
                            $msg .= ' (Erreur: ' . html_entity_decode(htmlspecialchars_decode($object->error, ENT_QUOTES)) . ')';
                        }
                        if (isset($object->errors) && is_array($object->errors) && count($object->errors)) {
                            $msg .= '<br/>Erreurs:';
                            foreach ($object->errors as $err) {
                                $msg .= ' - ' . $err . '<br/>';
                            }
                        }
                        $this->SqlError($msg, $this->curName(), $this->curId(), $this->curRef());

                        return false;
                    } else {
                        if ($isCurrentObject) {
                            $this->incUpdated();
                        }
                        if ($display_success || $isCurrentObject) {
                            $msg = 'Mise à jour ' . $label . ' effectuée avec succès';
                            $this->Success($msg, $this->curName(), $this->curId(), $this->curRef());
                        }
                        return true;
                    }
                } else {
                    $msg = 'Erreur technique: impossible d\'effectuer la mise à jour ' . $label . ' - Méthode "update()" inexistante';
                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                    if (!is_null($errors)) {
                        $errors[] = $msg;
                    }
                }
            } else {
                if (method_exists($object, 'create')) {
                    $result = $object->create($this->user);
                    if ($result <= 0) {
                        $msg = 'Echec de la création ' . $label;
                        if (isset($object->error) && $object->error) {
                            $msg .= ' (Erreur: ' . html_entity_decode(htmlspecialchars_decode($object->error, ENT_QUOTES)) . ')';
                        }
                        if (isset($object->errors) && is_array($object->errors) && count($object->errors)) {
                            $msg .= '<br/>Erreurs:';
                            foreach ($object->errors as $err) {
                                $msg .= ' - ' . $err . '<br/>';
                            }
                        }
                        $this->SqlError($msg, $this->curName(), $this->curId(), $this->curRef());
                        if (!is_null($errors)) {
                            $errors[] = $msg;
                        }
                        return false;
                    } else {
                        if ($isCurrentObject) {
                            $this->current_object['id'] = $object->id;
                            $this->incCreated();
                        }
                        if ($display_success) {
                            $msg = 'Création ' . $label . ' effectuée avec succès';
                            if (!$isCurrentObject) {
                                $msg .= ' (ID: ' . $object->id . ')';
                            }
                            $this->Success($msg, $this->curName(), $this->curId(), $this->curRef());
                        }
                        return true;
                    }
                } else {
                    $msg = 'Erreur technique: impossible d\'effectuer la création ' . $label . ' - Méthode "create()" inexistante';
                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                    if (!is_null($errors)) {
                        $errors[] = $msg;
                    }
                }
            }
        } else {
            $msg = 'Impossible d\'effectuer la création ' . $label . ' (Objet null)';
            $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
            if (!is_null($errors)) {
                $errors[] = $msg;
            }
        }
        return false;
    }

    protected function deleteObject($object, $label = null, &$errors = null, $display_info = true)
    {
        if (!isset($object->id) || !$object->id) {
            if (!is_null($errors)) {
                $errors[] = 'Impossible de supprimer l\'objet (ID Absent)';
            }
            return false;
        }

        $object_name = $this->getObjectClass($object);
        if (is_null($label)) {
            $label = 'de l\'objet de type "' . $object_name . '"';
        }

        $id_object = $object->id;
        $is_current_object = $this->isCurrent($object);

        if (method_exists($object, 'delete')) {
            $object->do_not_export = 1;
            if (in_array($object_name, array('Categorie'))) {
                $result = $object->delete($this->user);
            } elseif (in_array($object_name, array('Societe'))) {
                $result = $object->delete($object->id);
            } else {
                $result = $object->delete();
            }
            if ($result > 0) {
                if ($is_current_object || $display_info) {
                    $this->Info('Suppression ' . $label . ' d\'ID ' . $id_object . ' effectuée', $this->curName(), $is_current_object ? null : $this->curId(), $this->curRef());
                }
                if ($is_current_object) {
                    $this->incDeleted();
                }
                BDS_SyncData::deleteByLocObject($this->processDefinition->id, $object_name, $id_object, $errors);
                return true;
            } else {
                $msg = 'Echec de la suppression ' . $label;
                if (!$is_current_object) {
                    $msg .= ' d\'ID ' . $id_object;
                }
                if (isset($object->error) && $object->error) {
                    $msg .= ' - Erreur: ' . $object->error;
                }
                if (isset($object->errors) && is_array($object->errors) && count($object->errors)) {
                    $msg .= '<br/>Erreurs:';
                    foreach ($object->errors as $err) {
                        $msg .= ' - ' . $err . '<br/>';
                    }
                }
                $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                if (!is_null($errors)) {
                    $errors[] = $msg;
                }
                return false;
            }
        } else {
            $msg = 'Erreur technique: impossible d\'effectuer la suppression ' . $label;
            $msg .= ' - méthode "delete()" inexistante';
            $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
            if (!is_null($errors)) {
                $errors[] = $msg;
            }
        }

        return false;
    }

    protected function executeObjectImport($object_name, $id_object)
    {
        $this->Error('Opération "executeObjectImport" non disponible pour ce processus');
    }

    // Gestion statique des données d'importation des objets: 

    public static function getObjectProcessData($id_process, $id_object, $object_name)
    {
        $import_data = new BDS_ImportData();
        if ($import_data->fetchByObjectId($id_process, $object_name, $id_object)) {
            $reference = 'Référence d\'import: ';
            $reference .= $import_data->import_reference ? '"' . $import_data->import_reference . '"' : '(inconnu)';
            return array(
                'references'   => $reference,
                'status_label' => static::$status_labels[(int) $import_data->status],
                'status_value' => $import_data->status,
                'date_add'     => (isset($import_data->date_add) && $import_data->date_add ? $import_data->date_add : 0),
                'date_update'  => (isset($import_data->date_update) && $import_data->date_update ? $import_data->date_update : 0),
                'actions'      => array(
                    'import' => 'Importer'
                )
            );
        }
    }
    
    public static function getObjectsStatusInfos($id_process, $object_name = null)
    {
        $data = array();

        global $db;
        $bdb = new BDSDb($db);

        $where = '`id_process` = ' . (int) $id_process;

        if (!is_null($object_name)) {
            $where .= ' AND `object_name` = \'' . $object_name . '\'';
        }
        $rows = $bdb->getRows(BDS_ImportData::$table, $where, null, 'object', array(
            'status', 'object_name'
        ));
        if (!is_null($rows)) {
            foreach ($rows as $r) {
                if (!isset($r->status) || !isset($r->object_name) || !$r->object_name) {
                    continue;
                }
                if (!isset($data[$r->object_name])) {
                    $data[$r->object_name] = array();
                }
                if (!isset($data[$r->object_name][(int) $r->status])) {
                    $data[$r->object_name][(int) $r->status] = array(
                        'label' => self::$status_labels[(int) $r->status],
                        'count' => 1
                    );
                } else {
                    $data[$r->object_name][(int) $r->status]['count'] ++;
                }
            }
        }
        return $data;
    }

    public static function renderProcessObjectsList($process)
    {
        global $db;
        $bdb = new BDSDb($db);

        $sort_by = BDS_Tools::getValue('sort_by', 'date_update');
        $sort_way = BDS_Tools::getValue('sort_way', 'desc');

        $objects = BDS_ImportData::getAllObjectsList($bdb, $process->id, $sort_by, $sort_way);

        foreach ($objects as $object_name => &$object) {
            $object['nbFails'] = 0;
            $object['nbProcessing'] = 0;
            $rows = array();
            $object_label = ucfirst(BDS_Report::getObjectLabel($object_name));
            foreach ($object['list'] as $row) {
                $date_add = new DateTime($row['date_add']);
                $date_update = new DateTime($row['date_update']);

                $object_link = BDS_Tools::makeObjectUrl($object_name, $row['id_object']);
                $name = BDS_Tools::makeObjectName($bdb, $object_name, $row['id_object'], false);

                $label = '';
                if ($object_link) {
                    $label .= '<a href="' . $object_link . '" target="_blank">';
                    $label .= $name ? $name : $object_label;
                    $label .= '</a>';
                } else {
                    $label .= $name ? $name : $object_label;
                }

                $status = '<span class="';
                if ((int) $row['status'] < 0) {
                    $status .= 'danger';
                    $object['nbFails']++;
                } elseif ((int) $row['status'] > 0) {
                    $object['nbProcessing']++;
                    $status .= 'warning';
                } else {
                    $status .= 'success';
                }
                $status .= '">' . self::$status_labels[(int) $row['status']] . '</span>';

                $rows[] = array(
                    'id_data'            => $row['id_Import_data'],
                    'id_object'          => $row['id_object'],
                    'object_label_html'  => $label,
                    'object_label_value' => $name ? $name : null,
                    'import_reference'   => $row['import_reference'],
                    'date_add_html'      => $date_add->format('d / m / Y - H:i:s'),
                    'date_add_value'     => $row['date_add'],
                    'date_update_html'   => $date_update->format('d / m / Y - H:i:s'),
                    'date_update_value'  => $row['date_update'],
                    'status_html'        => $status,
                    'status_value'       => (int) $row['status']
                );
                unset($date_add);
                unset($date_update);
            }
            $object['list'] = $rows;
            $object['buttons'] = array(
                array(
                    'label'   => 'Importer',
                    'class'   => 'butAction',
                    'onclick' => 'executeObjectProcess($(this), \'import\', ' . $process->id . ', \'{object_name}\', {id_object})'
                )
            );

            $object['bulkActions'] = array(
                array(
                    'function' => 'executeSelectedObjectProcess(\'import\', ' . $process->id . ', \'{object_name}\')',
                    'label'    => 'Importer les éléments sélectionnés'
                )
            );
        }
        $fields = array(
            'id_object'        => array(
                'label'  => 'ID',
                'sort'   => true,
                'search' => 'text',
                'width'  => '5%'
            ),
            'object_label'     => array(
                'label_eval' => 'return ucfirst($object[\'label\']);',
                'sort'       => false,
                'search'     => 'text',
                'width'      => '25%'
            ),
            'import_reference' => array(
                'label'  => 'Référence d\'import',
                'sort'   => true,
                'search' => 'text',
                'width'  => '10%'
            ),
            'date_add'         => array(
                'label'  => '1ère synchronisation',
                'sort'   => true,
                'search' => 'date',
                'width'  => '20%'
            ),
            'date_update'      => array(
                'label'  => 'Dernière mise à jour',
                'sort'   => true,
                'search' => 'date',
                'width'  => '20%'
            ),
            'status'           => array(
                'label'        => 'Statut',
                'sort'         => true,
                'search'       => 'select',
                'search_query' => self::$status_labels,
                'width'        => '15%'
            ),
        );
        return renderProcessObjectsList($objects, $fields, $buttons, $bulkActions);
    }

    // Gestion des produits: 

    protected function updateProductPrice(Product $product, $prix_ht)
    {
        if (isset($product->price) && $product->price > 0) {
            if (isset($this->parameters['select_price']) &&
                    in_array($this->parameters['select_price'], array('highest', 'lowest'))) {
                $elements = getElementElement('product', 'fournisseur_for_price', $product->id);
                if (count($elements)) {
                    $fk_fourn = $elements[0]['d'];
                    if ((int) $fk_fourn !== (int) $this->parameters['id_soc_fournisseur']) {
                        switch ($this->parameters['select_price']) {
                            case 'highest':
                                if ((float) $prix_ht <= $product->price) {
                                    return;
                                }

                            case 'lowest':
                                if ((float) $prix_ht >= $product->price) {
                                    return;
                                }
                        }
                    }
                }
            }
        }

        if (!isset($product->tva_tx) || !$product->tva_tx) {
            if (!$this->checkParameter('tva_tx_default', 'float')) {
                return;
            }
            $product->tva_tx = $this->parameters['tva_tx_default'];
        }

        if ((float) $prix_ht !== (float) $product->price) {
            if (!$product->updatePrice($prix_ht, 'HT', $this->user, $product->tva_tx)) {
                $this->Error('Echec de la mise à jour du prix', $this->curName(), $this->curId(), $this->curRef());
            } else {
                setElementElement('product', 'fournisseur_for_price', $product->id, $this->parameters['id_soc_fournisseur']);
            }
        }
    }

    protected function updateProductBuyPrice($id_product, $prix_achat_ht, $ref_fournisseur, $id_soc_fournisseur = null, $tax_rate = null)
    {
        if (is_null($id_soc_fournisseur)) {
            if (!$this->checkParameter('id_soc_fournisseur', 'int')) {
                return;
            }
            $id_soc_fournisseur = $this->parameters['id_soc_fournisseur'];
        }
        if (is_null($tax_rate)) {
            if (!$this->checkParameter('default_tax_rate', 'float')) {
                return;
            }
            $tax_rate = $this->parameters['default_tax_rate'];
        }

        if (!class_exists('ProductFournisseur')) {
            require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
        }
        $pfp = new ProductFournisseur($this->db->db);

        $where = '`fk_soc` = ' . (int) $id_soc_fournisseur;
        $where .= ' AND `fk_product` = ' . (int) $id_product;

        $row = $this->db->getRow('product_fournisseur_price', $where, array('rowid', 'price'));
        if (!is_null($row) && $row->rowid) {
            $pfp->fetch_product_fournisseur_price($row->rowid);
        }
        $pfp->id = $id_product;

        if (!isset($pfp->price) || !$pfp->price ||  
                        ((float) $pfp->price !== (float) $prix_achat_ht)) {
            $fourn = new Societe($this->db->db);
            $fourn->fetch($id_soc_fournisseur);

            if ($pfp->update_buyprice(1, (float) $prix_achat_ht, $this->user, 'HT', $fourn, 0, $ref_fournisseur, $tax_rate) < 0) {
                $msg = 'Echec de la mise à jour du prix d\'achat';
                if ($pfp->error) {
                    $msg .= ' - Erreur: ' . $pfp->error;
                }
                $this->Alert($msg, $this->curName(), $this->curId(), $this->curRef());
            }
        }
    }

    protected function updateProductStock($product, $qty, $id_wharehouse = null)
    {
        if (is_null($id_wharehouse)) {
            if (!$this->checkParameter('id_wharehouse', 'int')) {
                return;
            }
            $id_wharehouse = $this->parameters['id_wharehouse'];
        }
        $nPces = (int) $qty - (isset($product->stock_reel) ? (int) $product->stock_reel : 0);
        if ($nPces !== 0) {
            if ($nPces < 0) {
                $mvt = 1;
                $nPces *= -1;
            } else {
                $mvt = 0;
            }
            $product->error = '';
            if (!$product->correct_stock($this->user, $id_wharehouse, (int) $nPces, $mvt, 'Mise à jour automatique')) {
                $msg = 'Echec de la mise à jour des stocks';
                if ($product->error) {
                    $msg .= ' Erreur: ' . $product->error;
                }
                $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
            }
        }
    }

    protected function createProductReference($product, $default_reference)
    {
        global $conf;

        $code_module = (!empty($conf->global->PRODUCT_CODEPRODUCT_ADDON) ? $conf->global->PRODUCT_CODEPRODUCT_ADDON : 'mod_codeproduct_leopard');
        if ($code_module != 'mod_codeproduct_leopard') {
            if (substr($code_module, 0, 16) == 'mod_codeproduct_' && substr($code_module, -3) == 'php') {
                $code_module = substr($code_module, 0, dol_strlen($code_module) - 4);
            }
            dol_include_once('/core/modules/product/' . $code_module . '.php');
            $modCodeProduct = new $code_module;
            if (!empty($modCodeProduct->code_auto)) {
                $product->ref = $modCodeProduct->getNextValue($product, $product->type);
            }
            unset($modCodeProduct);
        }
        if (empty($product->reference)) {
            $product->reference = $default_reference;
        }
    }

    protected function addProductToCategory($id_product, $id_categorie = null)
    {
        if (is_null($id_categorie)) {
            if (isset($this->options['new_references_category']) && $this->options['new_references_category']) {
                $id_categorie = (int) $this->options['new_references_category'];
            } else {
                if (!$this->checkParameter('id_categorie_default', 'int')) {
                    return;
                }
                $id_categorie = $this->parameters['id_categorie_default'];
            }
        }
        if (!$this->db->insert('categorie_product', array(
                    'fk_categorie' => (int) $id_categorie,
                    'fk_product'   => (int) $id_product
                ))) {
            $msg = 'Echec de l\'association du produit avec la catégorie d\'ID "' . $id_categorie . '"';
            $this->SqlError($msg, $this->curName(), $this->curId(), $this->curRef());
        }
    }
}
