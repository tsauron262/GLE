<?php

class BMP_EventCoProd extends BimpObject
{

    public function isEventEditable()
    {
        $event = $this->getParentInstance();
        if (!is_null($event) && $event->isLoaded()) {
            return $event->isEditable();
        }

        return 0;
    }

    public function create()
    {
        $errors = $this->validate();

        $event = $this->getParentInstance();

        if (!count($errors)) {
            if (!is_null($event) && $event->isLoaded()) {
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

        $errors = parent::create();

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
            $cpdp_instance = BimpObject::getInstance($this->module, 'BMP_EventCoProdDefPart');

            foreach ($categories as $id_cat => $cat_total) {
                if (($cat_total + $part) > 100) {
                    $part = 100 - $cat_total;
                }

                $cpdp_instance->reset();
                $cpdp_instance->validateArray(array(
                    'id_event'            => $event->id,
                    'id_category_montant' => (int) $id_cat,
                    'id_event_coprod'     => (int) $this->id,
                    'part'                => (float) $part
                ));

                $errors = array_merge($errors, $cpdp_instance->create());
            }
        }

        return $errors;
    }

    public function update()
    {
        $errors = $this->validate();

        $event = $this->getParentInstance();

        if (!count($errors)) {
            if (!is_null($event) && $event->isLoaded()) {
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

        $errors = parent::update();

        if (!is_null($event) && $event->isLoaded()) {
            $errors = array_merge($errors, $event->checkCoprodsParts());
        }

        return $errors;
    }

    public function delete()
    {
        $id = $this->id;
        $id_event = (int) $this->getData('id_event');

        $errors = parent::delete();
        $errors = array();

        if (!count($errors) && !is_null($id) && $id) {
            $cpdp = BimpObject::getInstance($this->module, 'BMP_EventCoProdDefPart');
            $list = $cpdp->getList(array(
                'id_event'        => $id_event,
                'id_event_coprod' => (int) $id
            ));

            foreach ($list as $item) {
                $cpdp->reset();
                if ($cpdp->fetch((int) $item['id'])) {
                    $errors = array_merge($errors, $cpdp->delete());
                }
            }

            $em = BimpObject::getInstance($this->module, 'BMP_EventMontant');
            $list = $em->getList(array(
                'id_event'  => $id_event,
                'id_coprod' => (int) $id
            ));

            foreach ($list as $item) {
                $em->reset();
                if ($em->fetch((int) $item['id'])) {
                    $errors = array_merge($errors, $em->delete());
                }
            }
        }

        return $errors;
    }
}
