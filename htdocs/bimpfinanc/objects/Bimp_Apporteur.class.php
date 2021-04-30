<?php

class Bimp_Apporteur extends BimpObject{
    function isDeletable($force_delete = false, &$errors = array()) {
        $comm = $this->getChildrenList('commissions');
        if(count($comm) > 0)
            return 0;
        return 1;
    }
}