 no warnings; # turn off warnings
 
 use XML::Compile::WSDL11;
 use XML::Compile::SOAP11;
 use XML::Compile::Transport::SOAPHTTP;
 use HTTP::Request;
 use HTTP::Response;
 use Data::Dumper;
 
 #Configuration
 $access = "EC858403EAECDE9B";
 $userid = "59us";
 $passwd = "Password1";
 $operation = "ProcessShipLabelRecovery";
 $endpointurl = "http://153.2.133.60:48011/xoltws_ship/LBRecovery";
 $wsdlfile = "./LabelRecoveryWS.wsdl";
 $schemadir = "./";
 $outputFileName = "XOLTResult.xml";
 
 sub processLabelRecovery
 {
 	my $request =
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
 		
 		TrackingNumber => '1ZAA75229092095917'
 	};

        return $request;
 }
 
 my $wsdl = XML::Compile::WSDL11->new( $wsdlfile );
 my @schemas = glob "$schemadir/*.xsd";
 $wsdl->importDefinitions(\@schemas);
 my $operation = $wsdl->operation($operation);
 my $call = $operation->compileClient(endpoint => $endpointurl);

 ($answer , $trace) = $call->(processLabelRecovery() , 'UTF-8');	
     
 
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
 