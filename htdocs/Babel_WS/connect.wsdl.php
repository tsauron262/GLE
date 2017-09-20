<?php
header('text/xml');
print <<<EOF
<?xml version='1.0' encoding='UTF-8'?>
<definitions name='Inventory'
 targetNamespace='urn:BIMP-ERPConnect'
 xmlns:tns='urn:BIMP-ERPConnect'
 xmlns:soap='http://schemas.xmlsoap.org/wsdl/soap/'
 xmlns:xsd='http://www.w3.org/2001/XMLSchema'
 xmlns:soapenc='http://schemas.xmlsoap.org/soap/encoding/'
 xmlns:wsdl='http://schemas.xmlsoap.org/wsdl/'
 xmlns='http://schemas.xmlsoap.org/wsdl/'>

    <message name='getItemCountRequest'>
        <part name='upc' type='xsd:string'/>
    </message>
    <message name='getItemCountResponse'>
        <part name='Result' type='xsd:integer'/>
    </message>

    <wsdl:message name="WSconnectRequest">
        <wsdl:part
         name="login"
         type="xsd:string"
        >
        </wsdl:part>
        <wsdl:part
            name="pass"
            type="xsd:string"
        >
        </wsdl:part>
    </wsdl:message>
    <wsdl:message name="WSconnectResponse">
        <wsdl:part
         name="return"
         type="xsd:string"
        >
        </wsdl:part>
    </wsdl:message>


    <wsdl:portType name="BabelPortType">
        <wsdl:operation name="WSconnect">
            <wsdl:input message="tns:WSconnectRequest">
            </wsdl:input>
            <wsdl:output message="tns:WSconnectResponse">
            </wsdl:output>
        </wsdl:operation>
    </wsdl:portType>
    <wsdl:binding
        name="BabelBinding"
        type="tns:BabelPortType"
    >
        <soap:binding
            style="rpc"
            transport="http://schemas.xmlsoap.org/soap/http"
        />
        <wsdl:operation name="WSconnect">
            <soap:operation
                soapAction="urn:BIMP-ERPConnect/WSconnect"
            />
            <wsdl:input>
                <soap:body
                    use="encoded"
                    namespace="urn:BIMP-ERPConnect"
                    encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
                />
                <soap:body
                    use="encoded"
                    namespace="urn:BIMP-ERPConnect"
                    encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
                />
            </wsdl:input>
            <wsdl:output>
                <soap:body
                    use="encoded"
                    namespace="urn:BIMP-ERPConnect"
                    encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
                />
                <soap:body
                    use="encoded"
                    namespace="urn:BIMP-ERPConnect"
                    encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
                />
            </wsdl:output>
        </wsdl:operation>
    </wsdl:binding>
    <service name='InventoryService'>
        <wsdl:port
         name="BabelPort"
         binding="tns:BabelBinding"
        >
            <soap:address
EOF;
             require_once('../conf/conf.php');
             print ' location="'.DOL_URL_ROOT.'/Babel_WS/loginWS.php"';
print <<<EOF
            />
        </wsdl:port>
    </service>
</definitions>
EOF;
?>