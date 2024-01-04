<?php

class BimpLink extends BimpObject
{

    // Getters booléens:

    public function isUserDest()
    {
        return 1;
    }

    // Getters:

    public function getSourceObject()
    {
        $module = $this->getData('src_module');
        $name = $this->getData('src_name');
        $id = (int) $this->getData('src_id');

        if ($module && $name && $id) {
            return BimpCache::getBimpObjectInstance($module, $name, $id);
        }

        return null;
    }

    public function getLinkedObject()
    {
        $type = $this->getData('linked_type');

        switch ($type) {
            case 'BO':
                $module = $this->getData('linked_module');
                $name = $this->getData('linked_name');
                $id = (int) $this->getData('linked_id');

                if ($module && $name && $id) {
                    return BimpCache::getBimpObjectInstance($module, $name, $id);
                }
                break;

            case 'DO':
                $module = $this->getData('linked_module');
                $file = $this->getData('linked_file');
                $name = $this->getData('linked_name');
                $id = (int) $this->getData('linked_id');

                if ($module) {
                    if (!$file) {
                        $file = $module;
                    }
                    if (!$name) {
                        $name = ucfirst($file);
                    }

                    return BimpCache::getDolObjectInstance($id, $module, $file, $name);
                }
                break;
        }

        return null;
    }

    public function getSourceFieldLabel()
    {
        $field = $this->getData('src_field');
        if ($field) {
            $object = $this->getSourceObject();

            if (is_a($object, 'BimpObject')) {
                if (in_array($object->object_name, array('BimpNote', 'BS_Note'))) {
                    if (BimpObject::objectLoaded($object)) {
                        return 'Contenu de la note #' . $object->id;
                    } else {
                        return 'Contenu d\'une note';
                    }
                } else {
                    return $object->getConf('fields/' . $field . '/label', $field);
                }
            }
        }

        return '';
    }

    public function getSourceFieldContent()
    {
        $field = $this->getData('src_field');
        if ($field) {
            $object = $this->getSourceObject();

            if (is_a($object, 'BimpObject')) {
                if ($object->canViewField($field)) {
                    return $object->getData($field);
                } else {
                    return '<span class="danger">Vous n\'avez pas la permission de voir le contenu de ce champ</span>';
                }
            }
        }

        return '';
    }

    public function getMyLink($id_user, $id_max, &$errors = array())
    {
        $demandes = array();

        $filters = array(
            'id'             => array(
                'operator' => '>',
                'value'    => (int) $id_max
            ),
            'linked_module'  => 'bimpcore',
            'user_ou_groupe' => array('or' => array(
                    'user'  => array('and_fields' => array(
                            'linked_name' => 'Bimp_User',
                            'linked_id'   => $id_user,
                        )),
                    'group' => array('and_fields' => array(
                            'linked_name' => 'Bimp_UserGroup',
                            'linked_id'   => self::getUserUserGroupsList($id_user),
                        )),
                ))
        );

        $links = BimpCache::getBimpObjectObjects($this->module, $this->object_name, $filters, 'a.viewed', 'DESC', array(), 15);

        $nb_demandes = 0;

        foreach ($links as $d) {
            $bimp_object = $d->getSourceObject();

            if ($bimp_object->isLoaded()) {
                $nb_demandes++;
                $new_demande = array(
                    'id'             => $d->id,
                    'is_viewed'      => (int) $d->getData('viewed'),
                    'can_set_viewed' => (int) $this->isUserDest(),
                    'obj_link'       => $bimp_object->getLink(),
                    'src'            => $d->getSourceFieldLabel(),
                    'content'        => $d->displaySourceFieldContent()
                );

                $demandes['content'][] = $new_demande;
            }
        }

        if (!isset($demandes['content'])) {
            $demandes['content'] = array();
        }

        $demandes['nb_demande'] = $nb_demandes;

        return $demandes;
    }

    // Getters statiques: 

    public static function getUsersLinked($src_object)
    {
        $users = array();
        $hashs = self::getLinksForSource($src_object, '', 'bimpcore', 'Bimp_User');
        foreach ($hashs as $hash) {
            $users[$hash->getData('linked_id')] = $hash->getLinkedObject();
        }
        return $users;
    }

    public static function getLinksForSource($src_object, $field_name = '', $linked_module = '', $linked_name = '')
    {
        if (is_a($src_object, 'BimpObject') && BimpObject::objectLoaded($src_object)) {
            $filters = array(
                'src_module' => $src_object->module,
                'src_name'   => $src_object->object_name,
                'src_id'     => $src_object->id
            );

            if ($field_name) {
                $filters['src_field'] = $field_name;
            }

            if ($linked_module) {
                $filters['linked_module'] = $linked_module;
            }

            if ($linked_name) {
                $filters['linked_name'] = $linked_name;
            }

            return BimpCache::getBimpObjectObjects('bimpcore', 'BimpLink', $filters);
        }

        return array();
    }

    public static function getLinksForLinkedObject($linked_object)
    {
        if (is_a($linked_object, 'BimpObject')) {
            $filters = array(
                'linked_type'   => 'BO',
                'linked_module' => $linked_object->module,
                'linked_name'   => $linked_object->object_name,
                'linked_id'     => $linked_object->id
            );

            return BimpCache::getBimpObjectObjects('bimpcore', 'BimpLink', $filters);
        }

        return array();
    }

    // Affichage: 

    public function displayObj($object, $with_object_label = false)
    {
        $html = '';

        if (is_object($object)) {
            if (BimpObject::objectLoaded($object)) {
                if (is_a($object, 'BimpObject')) {
                    if ($with_object_label) {
                        $html .= BimpTools::ucfirst($object->getLabel()) . '&nbsp;&nbsp;';
                    }
                    $html .= $object->getLink();
                } elseif (method_exists($object, 'getNomUrl')) {
                    if ($with_object_label) {
                        $html .= get_class($object) . '&nbsp;&nbsp;';
                    }
                    $html .= $object->getNomUrl(1);
                } else {
                    $html .= 'Objet "' . get_class($object) . '"' . (isset($object->id) ? ' #' . $object->id : '');
                }
            } elseif ((int) $this->getData('linked_id')) {
                $html .= '<span class="danger">';
                if (is_a($object, 'BimpObject')) {
                    $html .= ucfirst($object->getLabel('the'));
                } else {
                    $html .= 'L\'objet "' . get_class($object) . '"';
                }
                $html .= ' d\'ID ' . $this->getData('src_id') . ' n\'existe plus';
                $html .= '</span>';
            }
        }

        return $html;
    }

    public function displaySourceObject($with_object_label = false)
    {
        $object = $this->getSourceObject();

        if (BimpObject::objectLoaded($object) && is_a($object, 'BimpObject') && in_array($object->object_name, array('BimpNote', 'BS_Note'))) {
            $parent = $object->getParentInstance();

            if (BimpObject::objectLoaded($parent)) {
                $object = $parent;
            }
        }

        return $this->displayObj($object, $with_object_label);
    }

    public function displayLinkedObject($with_object_label = false)
    {
        $object = $this->getLinkedObject();
        return $this->displayObj($object, $with_object_label);
    }

    public function displaySourceFieldContent($no_html = false)
    {
        $content = $this->getSourceFieldContent();
        return $this->replaceHastags($content, $no_html);
    }

    // Rendus HTML: 

    public static function renderObjectLinkedObjectsLists($object, $type = 'both')
    {
        $html = '';

        if (!BimpObject::objectLoaded($object) || !is_a($object, 'BimpObject')) {
            if (is_a($object, 'BimpObject')) {
                $html .= BimpRender::renderAlerts('ID ' . $object->getLabel('of_the') . ' absent');
            } elseif (is_object($object)) {
                $html .= BimpRender::renderAlerts('ID de l\'objet "' . get_class($object) . '" absent');
            } else {
                $html .= BimpRender::renderAlerts('Objet invalide');
            }
            return $html;
        }

        if (!$object->can('view')) {
            return BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ce contenu');
        }

        $headers = array(
            'obj'     => 'Objet',
            'field'   => 'Champ',
            'content' => 'Contenu du champ'
        );

        // Objets cités: 
        $html .= '<div style="padding: 10px;">';

        if (in_array($type, array('linked', 'both'))) {
            $html .= '<h3>' . BimpRender::renderIcon('fas_arrow-right', 'iconLeft') . 'Objets cités par ' . $object->getLabel('this') . '</h3>';

            $rows = array();

            $links = self::getLinksForSource($object);

            if (!empty($links)) {
                foreach ($links as $link) {
                    $rows[] = array(
                        'obj'     => $link->displayLinkedObject(true),
                        'field'   => $link->getSourceFieldLabel(),
                        'content' => $link->displaySourceFieldContent()
                    );
                }
            }

            // Ajouts des notes: 
            $notes = array();
            if (in_array($object->object_name, array('BS_Ticket', 'BS_Inter'))) {
                $notes = $object->getChildrenObjects('notes');
            } else {
                $notes = $object->getNotes();
            }

            if (is_array($notes) && !empty($notes)) {
                foreach ($notes as $note) {
                    if ($note->can('view')) {
                        $note_links = self::getLinksForSource($note);

                        if (!empty($note_links)) {
                            foreach ($note_links as $link) {
                                $content = '';
                                if ($note->can('view')) {
                                    $content = $link->displaySourceFieldContent();
                                } else {
                                    $content = '<span class="danger">Vous n\'avez pas la permission de voir le contenu de cette note</span>';
                                }
                                $rows[] = array(
                                    'obj'     => $link->displayLinkedObject(true),
                                    'field'   => 'Contenu note #' . $note->id,
                                    'content' => $content
                                );
                            }
                        }
                    }
                }
            }

            if (!empty($rows)) {
                $html .= BimpRender::renderBimpListTable($rows, $headers, array(
                            'searchable'  => true,
                            'sortable'    => true,
                            'search_mode' => 'show'
                ));
            } else {
                $html .= BimpRender::renderAlerts('Aucun objet cité par ' . $object->getLabel('this'), 'warning');
            }
        }

        // Objets citant: 
        if (in_array($type, array('sources', 'both'))) {
            $html .= '<h3>' . BimpRender::renderIcon('fas_arrow-left', 'iconLeft') . 'Objets citant ' . $object->getLabel('this') . '</h3>';

            $rows = array();

            $links = self::getLinksForLinkedObject($object);

            if (!empty($links)) {
                foreach ($links as $link) {
                    $rows[] = array(
                        'obj'     => $link->displaySourceObject(true),
                        'field'   => $link->getSourceFieldLabel(),
                        'content' => $link->displaySourceFieldContent()
                    );
                }
            }

            if (!empty($rows)) {
                $html .= BimpRender::renderBimpListTable($rows, $headers, array(
                            'searchable'  => true,
                            'sortable'    => true,
                            'search_mode' => 'show'
                ));
            } else {
                $html .= BimpRender::renderAlerts('Aucun objet cité par ' . $object->getLabel('this'), 'warning');
            }
        }

        $html .= '</div>';

        return $html;
    }

    // Actions: 

    public function actionSetAsViewed($data, &$success = '')
    {
        $errors = array();
        $warnings = array();
        $success = 'Marquée comme vue';

        if (!$this->getData("viewed") && $this->isUserDest()) {
            $this->set('viewed', 1);
            $errors = $this->update($warnings, true);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'success_callback' => 'if (typeof notifHashtag !== "undefined" && notifHashtag !== null)
        notifHashtag.reloadNotif();'
        );
    }

    // Traitements statiques: 

    public static function deleteAllForSource($src_object, $field_name, &$warnings = array(), $no_transactions_db = false)
    {
        $errors = array();

        $links = self::getLinksForSource($src_object, $field_name);

        if (!empty($links)) {
            foreach ($links as $link) {
                $id_link = (int) $link->id;
                $link_warnings = array();
                
                if ($no_transactions_db) {
                    $link->useNoTransactionsDb();
                }
                
                $link_errors = $link->delete($link_warnings, true);

                if (count($link_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($link_warnings, 'Lien #' . $id_link);
                }

                if (count($errors)) {
                    $errors[] = BimpTools::getMsgFromArray($link_errors, 'Lien #' . $id_link);
                }
            }
        }

        return $errors;
    }

    public static function setLinksForSource($src_object, $field_name, $links, &$warnings = array())
    {
        $errors = array();

        if (is_a($src_object, 'BimpObject') && BimpObject::objectLoaded($src_object) && is_array($links)) {
            $no_transactions = $src_object->db->db->noTransaction;
            
            if (empty($links)) {
                // Suppr de tous les liens éventuels pour cet objet source:
                $del_warnings = array();

                $del_errors = self::deleteAllForSource($src_object, $field_name, $del_warnings, $no_transactions);

                if (count($del_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($del_errors, 'Erreurs lors de la suppression des hashtags du champ "' . $src_object->getConf('fields/' . $field_name . '/label', $field_name) . '"');
                }
            } else {
                // Suppression des liens courants non présents dans les nouveaux liens:    
                $cur_links = self::getLinksForSource($src_object, $field_name);
                ObjectsDef::insertObjectsDefData($links);

                foreach ($cur_links as $idx => $cur_link) {
                    $cl_type = (string) $cur_link->getData('linked_type');
                    $cl_module = (string) $cur_link->getData('linked_module');
                    $cl_name = (string) $cur_link->getData('linked_name');
                    $cl_file = (string) $cur_link->getData('linked_file');
                    $cl_id = (int) $cur_link->getData('linked_id');

                    foreach ($links as $link) {
                        if ($cl_type == (string) BimpTools::getArrayValueFromPath($link, 'obj_type', '') &&
                                $cl_module == (string) BimpTools::getArrayValueFromPath($link, 'obj_module', '') &&
                                $cl_name == (string) BimpTools::getArrayValueFromPath($link, 'obj_name', '') &&
                                $cl_file == (string) BimpTools::getArrayValueFromPath($link, 'obj_file', '') &&
                                $cl_id == (int) BimpTools::getArrayValueFromPath($link, 'id', 0)) {
                            continue 2;
                        }
                    }

                    $del_warnings = array();
                    $id_cur_link = $cur_link->id;
                    
                    if ($no_transactions) {
                        $cur_link->useNoTransactionsDb();
                    }
                    
                    $del_errors = $cur_link->delete($del_warnings, true);

                    if (count($del_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($del_warnings, 'Echec de la suppression du lien #' . $id_cur_link);
                    }

                    unset($cur_links[$idx]);
                }

                // Création des nouveaux liens:
                $data = array(
                    'src_module' => $src_object->module,
                    'src_name'   => $src_object->object_name,
                    'src_id'     => $src_object->id,
                    'src_field'  => $field_name
                );

                foreach ($links as $link) {
                    foreach ($cur_links as $cur_link) {
                        $cl_type = (string) $cur_link->getData('linked_type');
                        $cl_module = (string) $cur_link->getData('linked_module');
                        $cl_name = (string) $cur_link->getData('linked_name');
                        $cl_file = (string) $cur_link->getData('linked_file');
                        $cl_id = (int) $cur_link->getData('linked_id');

                        if ($cl_type == (string) BimpTools::getArrayValueFromPath($link, 'obj_type', '') &&
                                $cl_module == (string) BimpTools::getArrayValueFromPath($link, 'obj_module', '') &&
                                $cl_name == (string) BimpTools::getArrayValueFromPath($link, 'obj_name', '') &&
                                $cl_file == (string) BimpTools::getArrayValueFromPath($link, 'obj_file', '') &&
                                $cl_id == (int) BimpTools::getArrayValueFromPath($link, 'id', 0)) {
                            continue 2;
                        }
                    }

                    $data['linked_type'] = (string) BimpTools::getArrayValueFromPath($link, 'obj_type', '');
                    $data['linked_module'] = (string) BimpTools::getArrayValueFromPath($link, 'obj_module', '');
                    $data['linked_name'] = (string) BimpTools::getArrayValueFromPath($link, 'obj_name', '');
                    $data['linked_file'] = (string) BimpTools::getArrayValueFromPath($link, 'obj_file', '');
                    $data['linked_id'] = (int) BimpTools::getArrayValueFromPath($link, 'id', 0);

                    $newLink = BimpObject::createBimpObject('bimpcore', 'BimpLink', $data, true, $warnings, $no_transactions);

                    if (BimpObject::objectLoaded($newLink)) {
                        $cur_links[] = $newLink;
                    }
                }
            }
        }

        return $errors;
    }
}
