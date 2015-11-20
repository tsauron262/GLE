<?php

      //Configuration
      $access = " Add License Key Here";
      $userid = " Add User Id Here";
      $passwd = " Add Password Here";

      $accessSchemaFile = " Add AccessRequest Schema File";
      $requestSchemaFile = " Add ShipConfirmRequest Schema File";
      $responseSchemaFile = " Add ShipConfirmResponse Schema File";
      $ifSchemaFile = " Add IF Schema File";

      $endpointurl = ' Add URL Here';
      $outputFileName = "XOLTResult.xml";


      try
      {
         //create AccessRequest data object
         $das = SDO_DAS_XML::create("$accessSchemaFile");
    	 $doc = $das->createDocument();
         $root = $doc->getRootDataObject();
         $root->AccessLicenseNumber=$access;
         $root->UserId=$userid;
         $root->Password=$passwd;
         $security = $das->saveString($doc);

         //create ShipConfirmRequest data oject
         $das = SDO_DAS_XML::create("$requestSchemaFile");
         $das->addTypes("$ifSchemaFile");
         $requestDO = $das->createDataObject('','RequestType');
         $requestDO->RequestAction='ShipConfirm';
         $requestDO->RequestOption='nonvalidate';

         $doc = $das->createDocument('ShipmentConfirmRequest');
         $root = $doc->getRootDataObject();
         $root->Request = $requestDO;


         $labelSpecificationDO = $das->createDataObject('' , 'LabelSpecificationType');
         $labelPrintMethodDO = $das->createDataObject('' , 'LabelPrintMethodCodeDescriptionType');
         $labelPrintMethodDO->Code = 'GIF';
         $labelPrintMethodDO->Description = 'gif';
         $labelSpecificationDO->LabelPrintMethod = $labelPrintMethodDO;
         $labelSpecificationDO->HTTPUserAgent = 'Mozilla/4.5';
         $labelImageFormatDO = $das->createDataObject('' , 'LabelImageFormatCodeDescriptionType');
         $labelImageFormatDO->Code = 'GIF';
         $labelImageFormatDO->Description = 'gif';
         $labelSpecificationDO->LabelImageFormat = $labelImageFormatDO;
         $root->LabelSpecification = $labelSpecificationDO;

         $shipmentDO = $das->createDataObject('','ShipmentType');
         $rateInfoDO = $das->createDataObject('','RateInformationType');
         $rateInfoDO->NegotiatedRatesIndicator = '';
         $shipmentDO->RateInformation = $rateInfoDO;
         $shipmentDO->Description = '';

         $shipperDO = $das->createDataObject('', 'ShipperType');
         $shipperDO->Name = 'Shipper Name';
         $shipperDO->PhoneNumber = '1234567890';
         $shipperDO->TaxIdentificationNumber = '1234567877';
         $shipperDO->ShipperNumber = 'ISUS01';
         $addressDO = $das->createDataObject('' , 'ShipperAddressType');
         $addressDO->AddressLine1 = '2311 York Rd';
         $addressDO->City = 'Timonium';
         $addressDO->StateProvinceCode = 'MD';
         $addressDO->PostalCode = '21093';
         $addressDO->CountryCode = 'US';
         $shipperDO->Address = $addressDO;
         $shipmentDO->Shipper = $shipperDO;

         $shipToDO = $das->createDataObject('','ShipToType');
         $shipToDO->CompanyName = 'Happy Dog Pet Supply';
         $shipToDO->AttentionName = 'Marley Brinson';
         $shipToDO->PhoneNumber = '97225377171';
         $addressToDO = $das->createDataObject('','ShipToAddressType');
         $addressToDO->AddressLine1 = '78 federal road';
         $addressToDO->City = 'Danbury';
         $addressToDO->StateProvinceCode = 'CT';
         $addressToDO->PostalCode = '06810';
         $addressToDO->CountryCode = 'US';
         $shipToDO->Address = $addressToDO;
         $shipmentDO->ShipTo = $shipToDO;

         $shipFromDO = $das->createDataObject('', 'ShipFromType');
         $shipFromDO->CompanyName = 'Bullwinkle J. Moose';
         $shipFromDO->AttentionName = 'Bull';
         $shipFromDO->PhoneNumber = '1234567890';
         $shipFromDO->TaxIdentificationNumber = '1234567877';
         $addressFromDO = $das->createDataObject('','ShipFromAddressType');
         $addressFromDO->AddressLine1 = '2311 York Rd';
         $addressFromDO->City = 'City';
         $addressFromDO->StateProvinceCode = 'MD';
         $addressFromDO->PostalCode = '21093';
         $addressFromDO->CountryCode = 'US';
         $shipFromDO->Address = $addressFromDO;
         $shipmentDO->ShipFrom = $shipFromDO;

         $paymentInfoDO = $das->createDataObject('','PaymentInformationType');
         $prepaidDO = $das->createDataObject('' , 'PrepaidType');
         $billshipperDO = $das->createDataObject('', 'BillShipperType');
         $billshipperDO->AccountNumber = 'ISUS01';
         $prepaidDO->BillShipper = $billshipperDO;
         $paymentInfoDO->Prepaid = $prepaidDO;
         $shipmentDO->PaymentInformation = $paymentInfoDO;

         $serviceDO = $das->createDataObject('','ServiceType');
         $serviceDO->Code = '02';
         $serviceDO->Description = '2nd Day Air';
         $shipmentDO->Service = $serviceDO;

         $packageDO = $das->createDataObject('' , 'PackageType');
         $packagingTypeDO = $das->createDataObject('' , 'PackagingTypeType');
         $packagingTypeDO->Code = '02';
         $packagingTypeDO->Description = 'Customer Supplied';
         $packageDO->PackagingType = $packagingTypeDO;
         $packageDO->Description = 'Package Description';

         $referenceNumberDO = $das->createDataObject('', 'ReferenceNumberType');
         $referenceNumberDO->Code = '00';
         $referenceNumberDO->Value = 'Package';
         $packageDO->ReferenceNumber = $referenceNumberDO;

         $packageWeightDO = $das->createDataObject('' , 'PackageWeightType');
         $unitDO = $das->createDataObject('' , 'UnitOfMeasurementType');
         $packageWeightDO->UnitOfMeasurement = $unitDO;
         $packageWeightDO->Weight = '60.0';
         $packageDO->PackageWeight = $packageWeightDO;
         $packageDO->LargePackageIndicator = '';
         $packageDO->AdditionalHandling = '0';
         $shipmentDO->Package = $packageDO;
         $root->Shipment = $shipmentDO;
         $request = $das->saveString($doc);

         //create Post request
         $form = array
         (
             'http' => array
             (
                 'method' => 'POST',
                 'header' => 'Content-type: application/x-www-form-urlencoded',
                 'content' => "$security$request"
             )
         );

         //print form request
         print_r($form);


         $request = stream_context_create($form);
         $browser = fopen($endpointurl , 'rb' , false , $request);
         if(!$browser)
         {
             throw new Exception("Connection failed.");
         }

         //get response
         $response = stream_get_contents($browser);
         fclose($browser);

         if($response == false)
         {
            throw new Exception("Bad data.");
         }
         else
         {
            //save request and response to file
  	    $fw = fopen($outputFileName,'w');
            fwrite($fw , "Response: \n" . $response . "\n");
            fclose($fw);

            //get response status
            $resp = new SimpleXMLElement($response);
            echo $resp->Response->ResponseStatusDescription . "\n";
         }
      }
      catch(SDOException $sdo)
      {
      	 echo $sdo;
      }
      catch(Exception $ex)
      {
      	 echo $ex;
      }

?>

