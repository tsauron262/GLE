<?php

class BMP_EventCoProd extends BimpObject
{

    public function isEventEditable()
    {
        $event = $this->getParentInstance();
        if (BimpObject::objectLoaded($event)) {
            return $event->isInEditableStatus();
        }

        return 0;
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = $this->validate();

        $event = $this->getParentInstance();

        if (!count($errors)) {
            if (BimpObject::objectLoaded($event)) {
                $coprods = $this->getList(array(
                    'id_event' => (int) $event->id
                ));

                $total = (float) $this->getData('default_part');
                foreach ($coprods as $cp) {
                    $total += $cp['default_part'];
                }

                if ($total > 100) {
                    return array('Le total des parts par défaut des co-producteurs ne peut pas dépasser 100%');
                }
            } else {
                return array('ID de l\'événement absent ou invalide');
            }
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $instance = BimpObject::getInstance($this->module, 'BMP_EventCoProdDefPart');
            $cp_defParts = $instance->getList(array(
                'id_event' => $event->id
            ));

            $categories = array();

            foreach ($cp_defParts as $cpdp) {
                if (!isset($categories[(int) $cpdp['id_category_montant']])) {
                    $categories[(int) $cpdp['id_category_montant']] = 0;
                }
                $categories[(int) $cpdp['id_category_montant']] += (float) $cpdp['part'];
            }

            $part = (float) $this->getData('default_part');

            $cat_instance = BimpObject::getInstance($this->module, 'BMP_CategorieMontant');
            foreach ($categories as $id_cat => $cat_total) {
                if (($cat_total + $part) > 100) {
                    $part = 100 - $cat_total;
                }

                $cpdp_instance = BimpObject::getInstance($this->module, 'BMP_EventCoProdDefPart');
                $cpdp_errors = $cpdp_instance->validateArray(array(
                    'id_event'            => $event->id,
                    'id_category_montant' => (int) $id_cat,
                    'id_event_coprod'     => (int) $this->id,
                    'part'                => (float) $part
                ));

                $cpdp_warnings = array();

                if (!count($cpdp_errors)) {
                    $cpdp_errors = $cpdp_instance->create($cpdp_warnings, true);
                }

                if (count($cpdp_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($cpdp_errors, 'Echec de la création de la part par défaut pour la catégorie "' . $cat_instance->getSavedData('name', (int) $id_cat) . '"');
                }

                if (count($cpdp_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($cpdp_errors, 'Des erreurs sont survenues suite à la création de la part par défaut pour la catégorie "' . $cat_instance->getSavedData('name', (int) $id_cat) . '"');
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = $this->validate();

        $event = $this->getParentInstance();

        if (!count($errors)) {
            if (BimpObject::objectLoaded($event)) {
                $coprods = $this->getList(array(
                    'id_event' => (int) $event->id
                ));

                $total = (float) $this->getData('default_part');
                foreach ($coprods as $cp) {
                    if ((int) $cp['id'] === (int) $this->id) {
                        continue;
                    }
                    $total += $cp['default_part'];
                }

                if ($total > 100) {
                    return array('Le total des parts par défaut des co-producteurs ne peut pas dépasser 100%');
                }
            } else {
                return array('ID de l\'événement absent ou invalide');
            }
        }

        $errors = parent::update($warnings, $force_update);

        if (BimpObject::objectLoaded($event)) {
            $parts_errors = $event->checkCoprodsParts();

            if (count($parts_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($parts_errors);
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id = $this->id;
        $id_event = (int) $this->getData('id_event');

        $errors = parent::delete($warnings, $force_delete);
        $errors = array();

        if (!count($errors) && !is_null($id) && $id) {
            $cpdp = BimpObject::getInstance($this->module, 'BMP_EventCoProdDefPart');
            $list = $cpdp->getList(array(
                'id_event'        => $id_event,
                'id_event_coprod' => (int) $id
            ));

            foreach ($list as $item) {
                $cpdp = BimpCache::getBimpObjectInstance($this->module, 'BMP_EventCoProdDefPart', (int) $item['id']);
                if ($cpdp->isLoaded()) {
                    $cpdp_warnings = array();
                    $cpdp_errors = $cpdp->delete($cpdp_warnings, $force_delete);
                    if (count($cpdp_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($cpdp_errors, 'Echec de la suppression ' . $cpdp->getLabel('of_the') . ' d\'ID ' . $item['id']);
                    }
                    if (count($cpdp_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($cpdp_warnings, 'Des erreurs sont survenues suite à la suppression ' . $cpdp->getLabel('of_the') . ' d\'ID ' . $item['id']);
                    }
                }
            }

            $em = BimpObject::getInstance($this->module, 'BMP_EventMontant');
            $list = $em->getList(array(
                'id_event'  => $id_event,
                'id_coprod' => (int) $id
            ));

            foreach ($list as $item) {
                $em = BimpCache::getBimpObjectInstance($this->module, 'BMP_EventMontant', (int) $item['id']);
                if ($em->isLoaded()) {
                    $em_warnings = array();
                    $em_errors = $em->delete($em_warnings, $force_delete);
                    if (count($em_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($em_errors, 'Echec de la suppression ' . $em->getLabel('of_the') . ' d\'ID ' . $item['id']);
                    }
                    if (count($em_warnings)) {
                        $warnings[] = BimpTools::getMsgFromArray($em_warnings, 'Des erreurs sont survenues suite à la suppression ' . $em->getLabel('of_the') . ' d\'ID ' . $item['id']);
                    }
                }
            }
        }

        return $errors;
    }
}
