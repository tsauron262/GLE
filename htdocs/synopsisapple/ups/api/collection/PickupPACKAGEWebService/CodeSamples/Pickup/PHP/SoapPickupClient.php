<?php

  function processPickupCreation()
  {
      //create soap request
    $requestoption['RequestOption'] = '1';
    $request['Request'] = $requestoption;
    $request['RatePickupIndicator'] = 'N';
    $account['AccountNumber']= '0E463V';
    $account['AccountCountryCode'] = 'US';
    $shipper['Account'] = $account;
    $request['Shipper'] = $shipper;
    $pickupdateinfo['CloseTime'] = '1400';
    $pickupdateinfo['ReadyTime'] ='0500';
    $pickupdateinfo['PickupDate'] = '20100104';
    $request['PickupDateInfo'] = $pickupdateinfo;
    $pickupaddress['CompanyName'] = 'Pickup Proxy';
    $pickupaddress['ContactName'] = 'Pickup Manager';
    $pickupaddress['AddressLine'] = '315 Saddle Bridge Drive';
    $pickupaddress['Room'] = 'RO1';
    $pickupaddress['Floor'] = '2';
    $pickupaddress['City'] = 'Allendale';
    $pickupaddress['StateProvince'] = 'NJ';
    $pickupaddress['Urbanization'] = '';
    $pickupaddress['PostalCode'] = '07401';
    $pickupaddress['CountryCode'] = 'US';
    $pickupaddress['ResidentialIndicator'] = 'Y';
    $pickupaddress['PickupPoint'] = 'Lobby';
    $phone['Number'] = '6785851399';
    $phone['Extension'] = '911';
    $pickupaddress['Phone'] = $phone;
    $request['PickupAddress'] = $pickupaddress;
    $request['AlternateAddressIndicator'] = 'Y';
    $pickuppiece['ServiceCode'] = '001';
    $pickuppiece['Quantity'] = '27';
    $pickuppiece['DestinationCountryCode'] = 'US';
    $pickuppiece['ContainerCode'] = '01';
    $request['PickupPiece'] = $pickuppiece;
    $totalweight['Weight'] = '5.5';
    $totalweight['UnitOfMeasurement'] = 'LBS';
    $request['TotalWeight'] = $totalweight;
    $request['OverweightIndicator'] =  'N';
 	$request['PaymentMethod'] = '01';
 	$request['SpecialInstruction'] =  'Test';
 	$request['ReferenceNumber'] = '';
    $cnfrmemailaddr =  array
    (
        'jdoe@ups.com',
        'edoe@ups.com'
    );
    $notification['ConfirmationEmailAddress'] = $cnfrmemailaddr;
    $notification['UndeliverableEmailAddress'] = '';
    $request['Notification'] = $notification;
   

	echo "Request.......\n";
	print_r($request);
    echo "\n\n";
    return $request;

  }

  function processPickupRate()
  {
    //create soap request
  }

  function processPickupCancel()
  {
    //create soap request
  }

  function processPickupPendingStatus()
  {
    //create soap request
  }

  try
  {

    $mode = array
    (
         'soap_version' => 'SOAP_1_1',  // use soap 1.1 client
         'trace' => 1
    );

    // initialize soap client
  	$client = new SoapClient($wsdl , $mode);

  	//set endpoint url
  	$client->__setLocation($endpointurl);


    //create soap header
    $usernameToken['Username'] = $userid;
    $usernameToken['Password'] = $passwd;
    $serviceAccessLicense['AccessLicenseNumber'] = $access;
    $upss['UsernameToken'] = $usernameToken;
    $upss['ServiceAccessToken'] = $serviceAccessLicense;

    $header = new SoapHeader('http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0','UPSSecurity',$upss);
    $client->__setSoapHeaders($header);

    if(strcmp($operation,"ProcessPickupCreation") == 0 )
    {
        //get response
  	    $resp = $client->__soapCall($operation,array(processPickupCreation()));

         //get status
        echo "Response Status: " . $resp->Response->ResponseStatus->Description ."\n";

        //save soap request and response to file
        $fw = fopen($outputFileName , 'w');
        fwrite($fw , "Request: \n" . $client->__getLastRequest() . "\n");
        fwrite($fw , "Response: \n" . $client->__getLastResponse() . "\n");
        fclose($fw);

    }
    else if(strcmp($operation,"ProcessPickupRate") == 0 )
    {
        $resp = $client->__soapCall($operation,array(processPickupRate()));

        //get status
        echo "Response Status: " . $resp->Response->ResponseStatus->Description ."\n";

  	    //save soap request and response to file
  	    $fw = fopen($outputFileName ,'w');
  	    fwrite($fw , "Request: \n" . $client->__getLastRequest() . "\n");
        fwrite($fw , "Response: \n" . $client->__getLastResponse() . "\n");
        fclose($fw);
    }
    else if(strcmp($operation,"ProcessPickupCancel") == 0 )
    {
        $resp = $client->__soapCall($operation,array(processPickupCancel()));

        //get status
        echo "Response Status: " . $resp->Response->ResponseStatus->Description ."\n";

  	    //save soap request and response to file
  	    $fw = fopen($outputFileName ,'w');
  	    fwrite($fw , "Request: \n" . $client->__getLastRequest() . "\n");
        fwrite($fw , "Response: \n" . $client->__getLastResponse() . "\n");
        fclose($fw);
    }
    else
    {
        $resp = $client->__soapCall($operation,array(processPickupPendingStatus()));

        //get status
        echo "Response Status: " . $resp->Response->ResponseStatus->Description ."\n";

  	    //save soap request and response to file
  	    $fw = fopen($outputFileName ,'w');
  	    fwrite($fw , "Request: \n" . $client->__getLastRequest() . "\n");
        fwrite($fw , "Response: \n" . $client->__getLastResponse() . "\n");
        fclose($fw);
    }

  }
  catch(Exception $ex)
  {
  	print_r ($ex);
  }

?>
