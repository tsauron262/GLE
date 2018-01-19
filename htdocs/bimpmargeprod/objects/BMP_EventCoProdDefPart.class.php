<?php

class BMP_EventCoProdDefPart extends BimpObject
{

    public function isEventEditable()
    {
        $event = $this->getParentInstance();
        if (!is_null($event) && $event->isLoaded()) {
            return $event->isEditable();
        }

        return 0;
    }

    public function getCreateForm()
    {
        if ($this->isEventEditable()) {
            return 'default';
        }

        return '';
    }

    public function getListRowStyle()
    {
        if ($this->isLoaded()) {
            $id_category = (int) $this->getData('id_category_montant');
            if ($id_category) {
                $color = $this->db->getValue('bmp_categorie_montant', 'color', '`id` = ' . $id_category);
                return 'color: #' . $color . '; font-weight: bold';
            }
        }
        return '';
    }

    // Overrides: 

    public function create()
    {
        $id_event = $this->getData('id_event');
        $id_cat = $this->getData('id_category_montant');
        $id_coprod = $this->getData('id_event_coprod');

        if (!is_null($id_event) && !is_null($id_cat) && !is_null($id_coprod)) {
            if (is_null($id_coprod) || !$id_coprod) {
                $event = BimpObject::getInstance($this->module, 'BMP_Event');
                if ($event->fetch($id_event)) {
                    $coprods = $event->getCoProds();

                    $errors = array();

                    if (!count($coprods)) {
                        $errors[] = 'Aucun co-producteur enregistré';
                    } else {
                        $cp_instance = BimpObject::getInstance($this->module, 'BMP_EventCoProd');
                        foreach ($coprods as $id_coprod => $coprod_name) {
                            $this->reset();
                            $part = (float) $cp_instance->getSavedData('default_part', $id_coprod);
                            $this->validateArray(array(
                                'id_event'            => (int) $id_event,
                                'id_category_montant' => (int) $id_cat,
                                'id_event_coprod'     => (int) $id_coprod,
                                'part'                => $part
                            ));
                            $errors = array_merge($errors, $this->create());
                        }

                        $eventMontant = BimpObject::getInstance($this->module, 'BMP_EventMontant');
                        $em_list = $eventMontant->getList(array(
                            'id_event'            => (int) $id_event,
                            'id_category_montant' => (int) $id_cat
                        ));
                        foreach ($em_list as $em) {
                            $eventMontant->reset();
                            if ($eventMontant->fetch((int) $em['id'])) {
                                $eventMontant->checkCoprodsParts();
                            }
                        }
                    }
                    return $errors;
                } else {
                    return array('ID de l\'événement absent ou invalide');
                }
            } else {
                $result = $this->getList(array(
                    'id_event'            => (int) $id_event,
                    'id_category_montant' => (int) $id_cat,
                    'id_event_coprod'     => (int) $id_coprod
                ));

                if (!is_null($result) && count($result)) {
                    return array('Les parts par défaut sont déjà définies pour cette catégorie');
                }

                return parent::create();
            }
        }
    }

    public function update()
    {
        $part = (float) $this->getData('part');
        $id_category_montant = (int) $this->getData('id_category_montant');
        $id_coprod = (int) $this->getData('id_event_coprod');

        $event = $this->getParentInstance();

        if ($part && $id_category_montant && $id_coprod) {
            if (!is_null($event) && $event->isLoaded()) {
                $coprods_parts = $this->getList(array(
                    'id_event'            => $event->id,
                    'id_category_montant' => $id_category_montant
                ));

                $total = $part;

                foreach ($coprods_parts as $cp_part) {
                    if ((int) $cp_part['id_event_coprod'] === $id_coprod) {
                        continue;
                    }
                    $total += (float) $cp_part['part'];
                }

                if ($total > 100) {
                    return array('Le total des parts des co-producteurs ne peut pas dépasser 100%');
                }
            } else {
                return array('Id de l\'événement absent ou invalide');
            }
        }

        $errors = parent::update();

        if (!is_null($event) && $event->isLoaded() && $id_category_montant) {
            $eventMontant = BimpObject::getInstance($this->module, 'BMP_EventMontant');
            $em_list = $eventMontant->getList(array(
                'id_event'            => (int) $event->id,
                'id_category_montant' => (int) $id_category_montant
            ));
            foreach ($em_list as $em) {
                $eventMontant->reset();
                if ($eventMontant->fetch((int) $em['id'])) {
                    $eventMontant->checkCoprodsParts();
                }
            }
        }

        return $errors;
    }
}
