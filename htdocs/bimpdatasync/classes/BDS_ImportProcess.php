<?php

include_once __DIR__ . '/BDS_ImportData.php';

abstract class BDS_ImportProcess extends BDS_Process
{

    // Traitement des objets Dolibarr:

    protected function saveObject(&$object, $label = null, $display_success = true, &$errors = null)
    {
        $isCurrentObject = $this->isCurrent($object);
        $object_name = $this->getObjectClass($object);
        if (is_null($label)) {
            $label = 'de l\'objet de type "' . $object_name . '"';
        }

        if (!is_null($object) && is_object($object)) {
            if (isset($object->id) && $object->id) {
                if (method_exists($object, 'update')) {
                    if (in_array($object_name, array('Product', 'Societe', 'Contact'))) {
                        $result = $object->update($object->id, $this->user);
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
}
