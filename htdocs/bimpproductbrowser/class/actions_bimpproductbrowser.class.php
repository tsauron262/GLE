<?php

//require_once DOL_DOCUMENT_ROOT . '/bimpproductbrowser/class/productBrowser.class.php';

class ActionsBimpproductbrowser {

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
//    public function doActions($parameters, &$object, &$action, $hookmanager) {
//        global $conf;
//        $pb = new BimpProductBrowser($hookmanager->db);
//        if ($object->id > 0 && $conf->global->BIMP_FORCE_CATEGORIZATION == 1 && $pb->productIsCategorized($object->id) == 0) {
//            $url = DOL_URL_ROOT . '/bimpproductbrowser/categoriser.php?id=' . $object->id.'&redirect=1';
//            header('Location: ' . $url);
//        }
//        return 0;
//    }
}
