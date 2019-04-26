<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpticketrestaurant/class/BimpTicketRestaurant.class.php';

$btr = new BimpTicketRestaurant($db);

$btr->getTicket();

$db->close();
