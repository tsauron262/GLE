<?php

require_once('../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/nusoap.php';
require_once DOL_DOCUMENT_ROOT . '/synopsisapple/gsxDatas.class.php';
llxHeader();

$gsxDats = new gsxDatas('C02HW2ACDKQ5');

$datas = array(
    'repairConfirmationNumber' => 'G259489904',
    'customerEmailAddress' => '',
    'customerFirstName' => '',
    'customerLastName' => '',
    'fromDate' => '',
    'toDate' => '',
    'incompleteRepair' => '',
    'pendingShipment' => '',
    'purchaseOrderNumber' => '',
    'repairNumber' => '',
    'repairStatus' => '',
    'repairType' => '',
    'serialNumber' => '',
    'shipToCode' => '',
    'soldToReferenceNumber' => '',
    'technicianFirstName' => '',
    'technicianLastName' => '',
    'unreceivedModules' => '',
);
$client = 'RepairLookup';
$requestName = 'RepairLookupRequest';
$request = $gsxDats->gsx->_requestBuilder($requestName, 'lookupRequestData', $datas);
$response = $gsxDats->gsx->request($request, $client);

echo '<pre>';
print_r($response);
