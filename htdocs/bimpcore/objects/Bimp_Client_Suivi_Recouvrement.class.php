<?php

class Bimp_Client_Suivi_Recouvrement extends BimpObject{
    public $mode_list = array("Courrier", "Email", "Sms", "Téléphone");
    public $sens_list = array("Entrant", "Sortant");
}