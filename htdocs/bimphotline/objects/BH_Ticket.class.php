<?php

class BH_Ticket extends BimpObject {
    public function create() {
        $this->data['ticket_number'] = 'BH'.date('ymdhis');
        
        return parent::create();
    }
}