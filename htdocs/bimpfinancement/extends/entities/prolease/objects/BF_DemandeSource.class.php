<?php

// Entitié: prolease

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/objects/BF_DemandeSource.class.php';

BF_DemandeSource::$types['bimp'] = 'BIMP';
BF_DemandeSource::$types['actimac'] = 'ACTIMAC';

class BF_DemandeSource_ExtEntity extends BF_DemandeSource
{
    
}
