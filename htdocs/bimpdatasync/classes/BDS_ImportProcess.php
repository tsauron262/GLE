<?php

include_once __DIR__ . '/BDS_ImportData.php';

abstract class BDS_ImportProcess extends BDS_Process
{

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
