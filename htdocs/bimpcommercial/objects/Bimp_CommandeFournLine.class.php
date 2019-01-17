<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/FournObjectLine.class.php';

class Bimp_CommandeFournLine extends FournObjectLine
{

    public static $parent_comm_type = 'commande_fournisseur';
    public static $dol_line_table = 'commande_fournisseurdet';    
}
