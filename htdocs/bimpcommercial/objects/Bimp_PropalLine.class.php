<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/ObjectLine.class.php';

class Bimp_PropalLine extends ObjectLine
{

    public static $parent_comm_type = 'propal';
    public static $dol_line_table = 'propaldet';
    
    // Getters - ovrrides ObjectLine
    public function showMarginsInForms()
    {
        return 1;
    }
}
