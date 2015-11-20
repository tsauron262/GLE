 no warnings; # turn off warnings
 
 use XML::Compile::WSDL11;
 use XML::Compile::SOAP11;
 use XML::Compile::Transport::SOAPHTTP;
 use HTTP::Request;
 use HTTP::Response;
 use Data::Dumper;
 
 #Configuration
 $access = " Add License Key Here";
 $userid = " Add User Id Here";
 $passwd = " Add Password Here";
 $operation = "ProcessPickupCreation";
 $endpointurl = " Add URL Here";
 $wsdlfile = " Add Wsdl File Here ";
 $schemadir = " Add Schema Location Here";
 $outputFileName = "XOLTResult.xml";
 
 sub processPickupCreation
 {
 	my $request =
 	{
 		Header =>
 		{
	 		UPSSecurity =>  
		  	{
			   UsernameToken =>
			   {
				   Username => "$userid",
				   Password => "$passwd"
			   },
			   ServiceAccessToken =>
			   {
				   AccessLicenseNumber => "$access"
			   }
		  	},
		  	
 		},
 		
 		Request =>
 		{
 			RequestOption => ''
 		},
 		
 		RatePickupIndicator => 'N',
 		Shipper =>
 		{
 			Account =>
 			{
 				AccountNumber => '0E463V',
 				AccountCountryCode => 'US'
 			},
 		},
 		PickupDateInfo =>
 		{
 			CloseTime => '1400',
 			ReadyTime => '0500',
 			PickupDate => '20100104'
 		},
 		PickupAddress =>
 		{
 			CompanyName => 'Pickup Proxy',
 			ContactName => 'Pickup Manager',
 			AddressLine => '315 Saddle Bridge Drive',
 			Room => 'RO1',
 			Floor => '2',
 			City => 'Allendale',
 			StateProvince => 'NJ',
 			Urbanization => '',
 			PostalCode => '07401',
 			CountryCode => 'US',
 			ResidentialIndicator => 'Y',
 			PickupPoint => 'Lobby',
 			Phone =>
 			{
 				Number => '6785851399',
 				Extension => '911'
 			}
 		},
 		AlternateAddressIndicator => 'Y',
 		PickupPiece =>
 		{
 			ServiceCode => '001',
 			Quantity => '27',
 			DestinationCountryCode => 'US',
 			ContainerCode => '01'
 		},
 		PickupPiece =>
 		{
 			ServiceCode => '012',
 			Quantity => '4',
 			DestinationCountryCode => 'US',
 			ContainerCode => '01'
 		},
 		TotalWeight =>
 		{
 			Weight => '5.5',
 			UnitOfMeasurement => 'LBS'
 		},
 		OverweightIndicator => 'N',
 		PaymentMethod => '01',
 		SpecialInstruction => 'Test',
 		ReferenceNumber => '',
 		Notification =>
 		{
 			ConfirmationEmailAddress => ['jdoe@ups.com' , 'jdoe@ups.com'],
 			UndeliverableEmailAddress => ''
 		}
 	};
 	
 	return $request;
 }
 
 sub processPickupRate
 {
 	# Add ProcessPickupRate request
 }
 
 sub processPickupCancel
 {
 	# Add ProcessPickupCancel request
 }
 
 sub processPickupPendingStatus
 {
 	# Add ProcessPickupPendingStatus request
 }
 
 my $wsdl = XML::Compile::WSDL11->new( $wsdlfile );
 my @schemas = glob "$schemadir/*.xsd";
 $wsdl->importDefinitions(\@schemas);
 my $operation = $wsdl->operation($operation);
 my $call = $operation->compileClient( endpoint => $endpointurl );
 #print $wsdl->explain('ProcessPickupCreation' , PERL => 'INPUT' , recurse => 1); # describes soap service
 
 if($operation->name() eq "ProcessPickupCreation")
 {
 	($answer , $trace) = $call->(processPickupCreation() , 'UTF-8');	
 }
 elsif($operation->name() eq "ProcessPickupRate")
 {
 	($answer , $trace) = $call->(processPickupRate() , 'UTF-8');
 }
 elsif($operation->name() eq "ProcessPickupCancel")
 {
 	($answer , $trace) = $call->(processPickupCancel() , 'UTF-8');
 }
 else
 {
 	($answer , $trace) = $call->(processPickupPendingStatus() , 'UTF-8');
 }
 
 if($answer->{Fault})
 {
	print $answer->{Fault}->{faultstring} ."\n";
	print Dumper($answer);
	print "See XOLTResult.xml for details.\n";
		
	# Save Soap Request and Response Details
	open(fw,">$outputFileName");
	$trace->printRequest(\*fw);
	$trace->printResponse(\*fw);
	close(fw);
 }
 else
 {
	# Get Response Status Description
    print "Description: " . $answer->{Body}->{Response}->{ResponseStatus}->{Description} . "\n"; 
        
    # Print Request and Response
    my $req = $trace->request();
	print "Request: \n" . $req->content() . "\n";
	my $resp = $trace->response();
	print "Response: \n" . $resp->content();
		
	# Save Soap Request and Response Details
	open(fw,">$outputFileName");
	$trace->printRequest(\*fw);
	$trace->printResponse(\*fw);
	close(fw);
}
 