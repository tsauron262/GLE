<?php header('text/xml');
print <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<wsdl:definitions name="connect" targetNamespace="http://www.example.org/connect/" xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" xmlns:tns="http://www.example.org/connect/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/">
    <wsdl:message name="WSconnectRequest">
        <wsdl:part
         name="login" type="xsd:string">
        </wsdl:part>
        <wsdl:part
         name="pass"
         type="xsd:string"
        >
        </wsdl:part>
    </wsdl:message>
    <wsdl:message name="WSconnectResponse">
        <wsdl:part
         name="return" type="xsd:string">
        </wsdl:part>
    </wsdl:message>
    <wsdl:portType name="connectPortType">
        <wsdl:operation name="WSconnect">
            <wsdl:input message="tns:WSconnectRequest">
            </wsdl:input>
            <wsdl:output message="tns:WSconnectResponse">
            </wsdl:output>
        </wsdl:operation>
    </wsdl:portType>
    <wsdl:binding
     name="connectBinding"
     type="tns:connectPortType"
    >
        <soap:binding
         style="rpc"
         transport="http://schemas.xmlsoap.org/soap/http"
        />
        <wsdl:operation name="WSconnect">
            <soap:operation
             soapAction="http://www.example.org/connect/WSconnect"
            />
            <wsdl:input>
                <soap:body
                 use="encoded"
                 namespace="http://www.example.org/connect/"
                 encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
                />
            </wsdl:input>
            <wsdl:output>
                <soap:body
                 use="encoded"
                 namespace="http://www.example.org/connect/"
                 encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
                />
            </wsdl:output>
        </wsdl:operation>
    </wsdl:binding>
    <wsdl:service name="connectInventory">
        <wsdl:port name="babelConnect" binding="tns:connectBinding">
            <soap:address location="http://127.0.0.1/GLE-1.2/main/htdocs/Babel_WS/loginWS.php"/>
        </wsdl:port>
    </wsdl:service>
</wsdl:definitions>
EOF;
?>
