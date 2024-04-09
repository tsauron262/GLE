<?php

class BimpCommController extends BimpController
{

    // Overrides: 

    public function display()
    {
        $object = $this->config->getObject('', 'bimp_comm');
        if (BimpObject::objectLoaded($object) &&
                method_exists($object, 'checkLines')) {
            $errors = $object->checkLines();
            if (count($errors)) {
                foreach ($errors as $e) {
                    $this->addMsg($e, 'danger');
                }
            }
        }

        parent::display();
    }

    public function useMultiOnglet()
    {
        return BimpCore::getConf('use_multi_onglet', null, 'bimpcommercial');
    }

    public function notUseMultiOnglet()
    {
        return !$this->useMultiOnglet();
    }

    // Traitements ajax: 

    protected function ajaxProcessLoadMailForm()
    {
        $errors = array();
        $html = '';

        $object = $this->config->getObject('', 'bimp_comm');

        if (!$object->isLoaded()) {
            $errors[] = 'ID ' . $object->getLabel('of_the') . ' absent ou invalide';
        } elseif (!method_exists($object, 'renderMailForm')) {
            $errors[] = 'L\'envoi d\'email n\'est pas disponible pour ' . $object->getLabel('the_plur');
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0, 'int')
        )));
    }

    protected function ajaxProcessLoadFormMargins()
    {
        $errors = array();
        $sucess = '';
        $html = '';

        $module = BimpTools::getValue('module', '', 'aZ09comma');
        $object_name = BimpTools::getValue('object_name', '', 'aZ09comma');
        $id_object_line = BimpTools::getValue('id_object_line', null, 'int');
        $id_parent = (int) BimpTools::getValue('id_parent', 0, 'int');

        if (!$module && !$object_name) {
            $type = BimpTools::getValue('object_type', '');
            if ($type) {
                BimpObject::loadClass('bimpcommercial', 'ObjectLine');
                $module = (string) ObjectLine::getModuleByType($type);
                $object_name = (string) ObjectLine::getObjectNameByType($type);
            }
        }

        if ($module && $object_name) {
            $objectLine = BimpCache::getBimpObjectInstance($module, $object_name, $id_object_line);
            if (!is_null($objectLine)) {
                if (!$objectLine->getParentId() && $id_parent) {
                    $objectLine->setIdParent($id_parent);
                }
                $html = $objectLine->renderFormMargins();
            } else {
                $errors[] = 'Erreur technique: Type de ligne invalide';
            }
        } else {
            $errors[] = 'Erreur technique: Type de ligne absent';
        }

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $sucess,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0, 'int')
        )));
    }
}
