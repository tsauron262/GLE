<?php 

require_once('simpletest/autorun.php');
require_once('gsxlib.php');

error_reporting(E_ALL);


class GsxlibTest extends UnitTestCase
{
    function setUp() {
        $this->sn = $_ENV['GSX_SN'];
        $this->gsx = GsxLib::getInstance($_ENV['GSX_SOLDTO'], $_ENV['GSX_USER'], 'ut');
        $symptom = $this->gsx->fetchSymptomIssue($this->sn)->symptoms[0];
        $this->symptom_code = $symptom->reportedSymptomCode;
        $issue = $this->gsx->fetchSymptomIssue(array(
            'reportedSymptomCode' => $this->symptom_code
        ));
        $this->issue_code = $issue->issues[0]->reportedIssueCode;
        $this->received_date = date('m/d/y');
        $this->received_time = date('h:i A');
        $this->shipto = $_ENV['GSX_SHIPTO'];
        $this->techid = $_ENV['GSX_TECHID'];
    }

    function testPartsLookup() {
        $this->parts = $this->gsx->partsLookup($this->sn);
        $this->part = $this->parts[12];
        $this->assertEqual($this->part->partNumber, '661-5787');
    }

    function testWarranty() {
        $wty = $this->gsx->warrantyStatus($this->sn);
        $this->assertEqual($wty->warrantyStatus, 'Out Of Warranty (No Coverage)');
    }

    function testSymptomIssue() {
        $r = $this->gsx->fetchSymptomIssue($this->sn);
        $this->assertEqual($r->symptoms[0]->reportedSymptomCode, 6115);
        $this->assertEqual($r->symptoms[1]->reportedSymptomDesc, "Accidental damage");
    }

    function testCreateCarryInRepair() {
        $repairData = array(
            'serialNumber' => $this->sn,
            'shipTo' => $this->shipto,
            'diagnosedByTechId' => $this->techid,
            'symptom' => 'Sample symptom',
            'diagnosis' => 'Sample diagnosis',
            'unitReceivedDate' => $this->received_date,
            'unitReceivedTime' => $this->received_time,
            'notes' => 'A sample notes',
            'poNumber' => '11223344',
            'popFaxed' => FALSE,
            'orderLines' => array(
                'partNumber' => '661-6049',
                'comptiaCode' => '660',
                'comptiaModifier' => 'A',
                'abused' => FALSE
            ),
            'customerAddress' => array(
                'addressLine1' => 'Address line 1',
                'country' => 'US',
                'city' => 'Cupertino',
                'state' => 'CA',
                'street' => 'Valley Green Dr',
                'zipCode' => '95014',
                'regionCode' => '005',
                'companyName' => 'Apple Inc',
                'emailAddress' => 'test@example.com',
                'firstName' => 'Customer Firstname',
                'lastName' => 'Customer lastname',
                'primaryPhone' => '4088887766'
            ),
            'reportedSymptomCode' => $this->symptom_code,
            'reportedIssueCode' => $this->issue_code,
        );

        $this->gsx->createCarryinRepair($repairData);

    }

    function testCreateMailInRepair() {
        $repairData = array(
            'shipTo' => $this->shipto,
            'accidentalDamage' => FALSE,
            'addressCosmeticDamage' => FALSE,
            'comptia' => array(
                'comptiaCode' => 'X01',
                'comptiaModifier' => 'D',
                'comptiaGroup' => 1,
                'technicianNote' => 'sample technician notes'
            ),
            'requestReviewByApple' => FALSE,
            'serialNumber' => 'RM6501PXU9C',
            'diagnosedByTechId' => $this->techid,
            'symptom' => 'Sample symptom',
            'diagnosis' => 'Sample diagnosis',
            'reportedSymptomCode' => $this->symptom_code,
            'reportedIssueCode' => $this->issue_code,
            'unitReceivedDate' => $this->received_date,
            'unitReceivedTime' => $this->received_time,
            'notes' => 'A sample notes',
            'purchaseOrderNumber' => 'AB12345',
            'trackingNumber' => '12345',
            'shipper' => 'XDHL',
            'soldToContact' => 'Cupertino',
            'popFaxed' => FALSE,
            'orderLines' => array(
                'partNumber' => '076-1080',
                'comptiaCode' => '660',
                'comptiaModifier' => 'A',
                'abused' => FALSE
            ),
            'customerAddress' => array(
                'addressLine1' => 'Address line 1',
                'country' => 'US',
                'city' => 'Cupertino',
                'state' => 'CA',
                'street' => 'Valley Green Dr',
                'zipCode' => '95014',
                'regionCode' => '005',
                'companyName' => 'Apple Inc',
                'emailAddress' => 'test@example.com',
                'firstName' => 'Customer Firstname',
                'lastName' => 'Customer lastname',
                'primaryPhone' => '4088887766'
            ),
        );

        $this->gsx->createMailinRepair($repairData);

    }
}
