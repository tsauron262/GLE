<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */

class SynopsisJasper {

    private $namespace = "http://www.jaspersoft.com/namespaces/php";

    function SynopsisJasper($db) {
        $this->db = $db;
//        require_once('SOAP/Client.php');
        require_once(DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/class.nusoap_base.php');
        require_once(DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/class.soap_transport_http.php');
//        require_once(DOL_DOCUMENT_ROOT.'/includes/nusoap/lib/class.soap_transport_https.php');
        require_once(DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/class.soapclient.php');
        require_once(DOL_DOCUMENT_ROOT . '/includes/nusoap/lib/class.soap_parser.php');

        define("TYPE_FOLDER", "folder");
        define("TYPE_REPORTUNIT", "reportUnit");
        define("TYPE_DATASOURCE", "datasource");
        define("TYPE_DATASOURCE_JDBC", "jdbc");
        define("TYPE_DATASOURCE_JNDI", "jndi");
        define("TYPE_DATASOURCE_BEAN", "bean");
        define("TYPE_IMAGE", "img");
        define("TYPE_FONT", "font");
        define("TYPE_JRXML", "jrxml");
        define("TYPE_CLASS_JAR", "jar");
        define("TYPE_RESOURCE_BUNDLE", "prop");
        define("TYPE_REFERENCE", "reference");
        define("TYPE_INPUT_CONTROL", "inputControl");
        define("TYPE_DATA_TYPE", "dataType");
        define("TYPE_OLAP_MONDRIAN_CONNECTION", "olapMondrianCon");
        define("TYPE_OLAP_XMLA_CONNECTION", "olapXmlaCon");
        define("TYPE_MONDRIAN_SCHEMA", "olapMondrianSchema");
        define("TYPE_XMLA_CONNTCTION", "xmlaConntction");
        define("TYPE_UNKNOW", "unknow");
        define("TYPE_LOV", "lov"); // List of values...
        define("TYPE_QUERY", "query"); // List of values...

        /**
         * These constants are copied here from DataType for facility
         */
        define("DT_TYPE_TEXT", 1);
        define("DT_TYPE_NUMBER", 2);
        define("DT_TYPE_DATE", 3);
        define("DT_TYPE_DATE_TIME", 4);

        /**
         * These constants are copied here from InputControl for facility
         */
        define("IC_TYPE_BOOLEAN", 1);
        define("IC_TYPE_SINGLE_VALUE", 2);
        define("IC_TYPE_SINGLE_SELECT_LIST_OF_VALUES", 3);
        define("IC_TYPE_SINGLE_SELECT_QUERY", 4);
        define("IC_TYPE_MULTI_VALUE", 5);
        define("IC_TYPE_MULTI_SELECT_LIST_OF_VALUES", 6);
        define("IC_TYPE_MULTI_SELECT_QUERY", 7);


        define("PROP_VERSION", "PROP_VERSION");
        define("PROP_PARENT_FOLDER", "PROP_PARENT_FOLDER");
        define("PROP_RESOURCE_TYPE", "PROP_RESOURCE_TYPE");
        define("PROP_CREATION_DATE", "PROP_CREATION_DATE");

        // File resource properties
        define("PROP_FILERESOURCE_HAS_DATA", "PROP_HAS_DATA");
        define("PROP_FILERESOURCE_IS_REFERENCE", "PROP_IS_REFERENCE");
        define("PROP_FILERESOURCE_REFERENCE_URI", "PROP_REFERENCE_URI");
        define("PROP_FILERESOURCE_WSTYPE", "PROP_WSTYPE");

        // Datasource properties
        define("PROP_DATASOURCE_DRIVER_CLASS", "PROP_DATASOURCE_DRIVER_CLASS");
        define("PROP_DATASOURCE_CONNECTION_URL", "PROP_DATASOURCE_CONNECTION_URL");
        define("PROP_DATASOURCE_USERNAME", "PROP_DATASOURCE_USERNAME");
        define("PROP_DATASOURCE_PASSWORD", "PROP_DATASOURCE_PASSWORD");
        define("PROP_DATASOURCE_JNDI_NAME", "PROP_DATASOURCE_JNDI_NAME");
        define("PROP_DATASOURCE_BEAN_NAME", "PROP_DATASOURCE_BEAN_NAME");
        define("PROP_DATASOURCE_BEAN_METHOD", "PROP_DATASOURCE_BEAN_METHOD");


        // ReportUnit resource properties
        define("PROP_RU_DATASOURCE_TYPE", "PROP_RU_DATASOURCE_TYPE");
        define("PROP_RU_IS_MAIN_REPORT", "PROP_RU_IS_MAIN_REPORT");

        // DataType resource properties
        define("PROP_DATATYPE_STRICT_MAX", "PROP_DATATYPE_STRICT_MAX");
        define("PROP_DATATYPE_STRICT_MIN", "PROP_DATATYPE_STRICT_MIN");
        define("PROP_DATATYPE_MIN_VALUE", "PROP_DATATYPE_MIN_VALUE");
        define("PROP_DATATYPE_MAX_VALUE", "PROP_DATATYPE_MAX_VALUE");
        define("PROP_DATATYPE_PATTERN", "PROP_DATATYPE_PATTERN");
        define("PROP_DATATYPE_TYPE", "PROP_DATATYPE_TYPE");

        // ListOfValues resource properties
        define("PROP_LOV", "PROP_LOV");
        define("PROP_LOV_LABEL", "PROP_LOV_LABEL");
        define("PROP_LOV_VALUE", "PROP_LOV_VALUE");


        // InputControl resource properties
        define("PROP_INPUTCONTROL_TYPE", "PROP_INPUTCONTROL_TYPE");
        define("PROP_INPUTCONTROL_IS_MANDATORY", "PROP_INPUTCONTROL_IS_MANDATORY");
        define("PROP_INPUTCONTROL_IS_READONLY", "PROP_INPUTCONTROL_IS_READONLY");

        // SQL resource properties
        define("PROP_QUERY", "PROP_QUERY");
        define("PROP_QUERY_VISIBLE_COLUMNS", "PROP_QUERY_VISIBLE_COLUMNS");
        define("PROP_QUERY_VISIBLE_COLUMN_NAME", "PROP_QUERY_VISIBLE_COLUMN_NAME");
        define("PROP_QUERY_VALUE_COLUMN", "PROP_QUERY_VALUE_COLUMN");
        define("PROP_QUERY_LANGUAGE", "PROP_QUERY_LANGUAGE");


        // SQL resource properties
        define("PROP_QUERY_DATA", "PROP_QUERY_DATA");
        define("PROP_QUERY_DATA_ROW", "PROP_QUERY_DATA_ROW");
        define("PROP_QUERY_DATA_ROW_COLUMN", "PROP_QUERY_DATA_ROW_COLUMN");


        define("MODIFY_REPORTUNIT", "MODIFY_REPORTUNIT_URI");
        define("CREATE_REPORTUNIT", "CREATE_REPORTUNIT_BOOLEAN");
        define("LIST_DATASOURCES", "LIST_DATASOURCES");
        define("IC_GET_QUERY_DATA", "IC_GET_QUERY_DATA");

        define("VALUE_TRUE", "true");
        define("VALUE_FALSE", "false");

        define("RUN_OUTPUT_FORMAT", "RUN_OUTPUT_FORMAT");
        define("RUN_OUTPUT_FORMAT_PDF", "PDF");
        define("RUN_OUTPUT_FORMAT_JRPRINT", "JRPRINT");
        define("RUN_OUTPUT_FORMAT_HTML", "HTML");
        define("RUN_OUTPUT_FORMAT_XLS", "XLS");
        define("RUN_OUTPUT_FORMAT_XML", "XML");
        define("RUN_OUTPUT_FORMAT_CSV", "CSV");
        define("RUN_OUTPUT_FORMAT_RTF", "RTF");
        define("RUN_OUTPUT_IMAGES_URI", "IMAGES_URI");
        define("RUN_OUTPUT_PAGE", "PAGE");
    }

    function parseFolder($someInputControlUri, $preg_filter = false, $remArray = array()) {
        $someInputControlUri = preg_replace('/\/$/', '', $someInputControlUri);
        $result = $this->ws_list_eos_shell($someInputControlUri, $GLOBALS['loginJasper'], $GLOBALS['passJasper']);
        $folders = array();

        if (1 || is_object($result)) {
            if (0 && get_class($result) == 'SOAP_Fault') {
                $errorMessage = $result->getFault()->faultstring;
            } else {
                $folders = $this->getResourceDescriptors($result);
            }
            foreach ($folders as $key => $val) {
                if ($val["type"] == "folder") {
                    $remArray = $this->parseFolder($val['uri'], $preg_filter, $remArray);
                } else {
                    if ($GLOBALS['verbose'])
                        echo $val['name'] . "\t" . $val["uri"] . "\t" . $val["type"] . "\t" . $val["label"] . "\n";
                    if ($preg_filter) {
                        if (preg_match($preg_filter, $val['uri'])) {
                            array_push($remArray, $val["uri"]);
                        }
                    } else {
                        array_push($remArray, $val["uri"]);
                    }
                }
            }
        } else {
            echo "[Connexion au serveur Jasper impossible. " . print_r($result, true)."]";
        }
        return $remArray;
    }

    function parseFolder2($someInputControlUri, $preg_filter = false, &$remArray) {
        $someInputControlUri = preg_replace('/\/$/', '', $someInputControlUri);
        $result = $this->ws_list_eos_shell($someInputControlUri, $GLOBALS['loginJasper'], $GLOBALS['passJasper']);
        $folders = array();
        if (get_class($result) == 'SOAP_Fault') {
            $errorMessage = $result->getFault()->faultstring;
        } else {
            $folders = $this->getResourceDescriptors($result);
        }
        foreach ($folders as $key => $val) {

            if ($val["type"] == "folder") {
                $remArray = $this->parseFolder($val['uri'], $preg_filter, $remArray);
            } else {
                if ($GLOBALS['verbose'])
                    echo $val['name'] . "\t" . $val["uri"] . "\t" . $val["type"] . "\t" . $val["label"] . "\n";
                if ($preg_filter) {
                    if (preg_match($preg_filter, $val['uri'])) {
                        array_push($remArray, $val["uri"]);
                    }
                } else {
                    array_push($remArray, $val["uri"]);
                }
            }
        }
    }

    //
    //Get Single report
    function saveReport($remArray, $keySeeked) {
        foreach ($remArray as $key => $val) {
            if ($key != $keySeeked)
                continue;
            $someInputControlUri = $val;
            $attachment = array();
            $result = $this->ws_eos_get_shell($someInputControlUri, $GLOBALS['loginJasper'], $GLOBALS['passJasper'], $attachment); //get filename
            $domDocument = new DomDocument();
            $domDocument->loadXML($result);
            $filename = $domDocument->getElementsByTagname("label")->item(0)->nodeValue;
            //Prob 1 IMG Src
            $domAttach = new DomDocument();
            $domAttach->loadHTML($attachment["cid:attachment"]);

            $img = $domAttach->getElementsByTagname("img");
            #print $img->length;
            $arrSrc = array();
            for ($i = 0; $i < $img->length; $i++) {
                $arrSrc[$img->item($i)->getAttribute("src")] = true;
            }

            //Name element src
            foreach ($arrSrc as $key1 => $val1) {
                //Get Image From Jasper
                $someInputControlUri123 = $someInputControlUri;
                $someInputControlUri123 = preg_replace("/\/[\w\._-]*$/", "", $someInputControlUri123);
                $someInputControlUri123 .= "/" . $key1;
                //print $someInputControlUri123."<HR>";
                $attachment1 = array();
                $result = $this->ws_eos_get_shell($someInputControlUri123, $GLOBALS['loginJasper'], $GLOBALS['passJasper'], $attachment1); //get filename
                $dolroot = DOL_DATA_ROOT;
                $fullPath = $dolroot . "/JasperImg"; #($attachment1["cid:attachment"]);
                if (is_writable($dolroot) && !file_exists($fullPath)) {
                    mkdir($fullPath, 0700);
                } else if (!is_writable($dolroot)) {
                    print "Err : " . $dolroot . " n'est pas accessible en ecriture<BR>";
                }
                if (file_exists($fullPath) && (is_writable($fullPath))) {
                    //print basename($key1);
                    if (file_exists($fullPath . "/" . basename($key1))) {
                        unlink($fullPath . "/" . basename($key1));
                    }
                    $fh = fopen($fullPath . "/" . basename($key1), "w");
                    fwrite($fh, $attachment1["cid:attachment"]);
                    fclose($fh);
                } else {
                    print "Err:" . $fullPath . " n'est pas accessible en ecriture<BR>";
                }

                //print "<HR>";
                //Replace image SRC
                //$img = $domAttach->getElementsByTagname("img");
                #print $img->length;
                $arrSrcReplace = array();
                for ($i = 0; $i < $img->length; $i++) {
                    $src = basename($img->item($i)->getAttribute("src"));
                    $doliURL = DOL_URL_ROOT;
                    $src = $doliURL . "/../documents/JasperImg/" . $src;
                    #echo "<H3 style='color: #FF0000;'>".$img->item($i)->getAttribute("src")."<BR>";
                    $img->item($i)->setAttribute("src", $src);
                }
            }
            //Affiche HTML
            echo "<DIV style='border: 1px solid #5AA3CC; background-color:#DAD3FC; opacity: 0.9'>";
            $domSave = $domAttach->getElementsByTagname("body")->item(0);
            $newHTML = new DomDocument();
            $node = $newHTML->importNode($domSave, true);
            $newHTML->appendChild($node);
            echo utf8_decode($newHTML->saveHTML());
            echo "</DIV>";
        }
    }

    function genReport($remArray, $keySeeked, $return = false) {
        foreach ($remArray as $key => $val) {
            if ($key != $keySeeked)
                continue;
            $attachment = array();
            $result = $this->ws_eos_runReport($val, $GLOBALS['loginJasper'], $GLOBALS['passJasper'], $attachment); //get filename
            $domDocument = new DomDocument();
            $domDocument->loadXML($result);
            $filename = $val;
            //Par défault pdf
            $filename .= ".pdf";
            //var_dump($attachment);
            $pdf_content = $attachment["cid:report"];
            if ($return) {
                return($pdf_content);
            } else {
                // We'll be outputting a PDF
                header('Content-type: application/pdf');
                // It will be called downloaded.pdf
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo $pdf_content;
            }
        }
    }

    function genXLSReport($remArray, $keySeeked, $return = false) {
        foreach ($remArray as $key => $val) {
            if ($key != $keySeeked)
                continue;
            $attachment = array();
            $output_params = array();
            $output_params["RUN_OUTPUT_FORMAT"] = 'XLS';

            $result = $this->ws_eos_runReport($val, $GLOBALS['loginJasper'], $GLOBALS['passJasper'], $attachment, array(), $output_params); //get filename
            $domDocument = new DomDocument();
            $domDocument->loadXML($result);
            $filename = $val;
            //Par défault pdf
            $filename .= ".xls";
            //var_dump($attachment);
            $pdf_content = $attachment["cid:report"];
            if ($return) {
                return($pdf_content);
            } else {
                // We'll be outputting a PDF
                header('Content-type: application/vnd.ms-excel');
//                // It will be called downloaded.pdf
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo $pdf_content;
            }
        }
    }

    function genHTMLReport($remArray, $keySeeked) {
        foreach ($remArray as $key => $val) {
            if ($key != $keySeeked)
                continue;
            $attachment = array();
            $output_params = array();
            $output_params["RUN_OUTPUT_FORMAT"] = 'HTML';

            $result = $this->ws_eos_runReport($val, $GLOBALS['loginJasper'], $GLOBALS['passJasper'], $attachment, array(), $output_params); //get filename

            $html = $attachment['cid:report'];
            $domAttach = new DomDocument();
            $domAttach->loadHTML($html);

            //Get Image List
            $img = $domAttach->getElementsByTagname("img");
            #print $img->length;
            $arrSrc = array();
            for ($i = 0; $i < $img->length; $i++) {
                $arrSrc[$img->item($i)->getAttribute("src")] = true;
                // echo $img->item($i)->getAttribute("src") . "<BR>";
            }
            //var_dump($attachment);
            //Name element src
            foreach ($arrSrc as $key1 => $val1) {
                $imgName = basename($key1);
                $content_img = $attachment['cid:' . $imgName];
                //print $content_img;
                $fullPath = DOL_DOCUMENT_ROOT;
                $fullPath .= "/Synopsis_Jasper/images/";
                if (file_exists($fullPath) && (is_writable($fullPath))) {
                    //print basename($key1);
                    if (basename($key1) != "px" && file_exists($fullPath . "/" . basename($key1))) {
                        unlink($fullPath . "/" . basename($key1));
                    }
                    if (basename($key1) != "px") {
                        $fh = fopen($fullPath . "/" . basename($key1), "w");
                        fwrite($fh, $content_img);
                        fclose($fh);
                    }
                } else {
                    print "Err:" . $fullPath . " n'est pas accessible en ecriture<BR>";
                }

                //print "<HR>";
                //Replace image SRC
                //$img = $domAttach->getElementsByTagname("img");
                #print $img->length;
                $arrSrcReplace = array();
                for ($i = 0; $i < $img->length; $i++) {
                    $src = basename($img->item($i)->getAttribute("src"));
                    $doliURL = DOL_URL_ROOT;
                    $src = $doliURL . "/Synopsis_Jasper/images/" . $src;
                    #echo "<H3 style='color: #FF0000;'>".$img->item($i)->getAttribute("src")."<BR>";
                    $img->item($i)->setAttribute("src", $src);
                }
            }


            //Affiche HTML
            echo "<DIV style='border: 1px solid #5AA3CC; background-color:#DAD3FC; opacity: 0.9;'>";
            $domSave = $domAttach->getElementsByTagname("body")->item(0);
            $newHTML = new DomDocument();
            $node = $newHTML->importNode($domSave, true);
            $newHTML->appendChild($node);

            echo html_entity_decode(iconv("ISO-8859-1", "UTF-8", $newHTML->saveHTML()));


            echo "</DIV>";
        }
    }

    //Get All Reports
    function saveAllReport($remArray) {
        foreach ($remArray as $key => $val) {
            $someInputControlUri = $val;
            $attachment = array();
            $result = $this->ws_eos_get_shell($someInputControlUri, $GLOBALS['loginJasper'], $GLOBALS['passJasper'], $attachment); //get filename
            //var_dump($result);
            $domDocument = new DomDocument();
            $domDocument->loadXML($result);
            $filename = $domDocument->getElementsByTagname("label")->item(0)->nodeValue;
            echo "<BR>" . $filename . "<BR>";
            //Prob 1 IMG Src
            $domAttach = new DomDocument();
            $domAttach->loadHTML($attachment["cid:attachment"]);

            $img = $domAttach->getElementsByTagname("img");
            #print $img->length;
            $arrSrc = array();
            for ($i = 0; $i < $img->length; $i++) {
                $arrSrc[$img->item($i)->getAttribute("src")] = true;
            }

            //Name element src
            foreach ($arrSrc as $key1 => $val1) {
                if ($val1)
                    print $key1 . "<BR>";

                #var_dump($result);
                #print "<pre><code>".preg_replace("/</","&lt;",$attachment["cid:attachment"])."</code></pre><BR>";
                //target
#                writeFile($filename,$attachment["cid:attachment"]);
                //Get Image From Jasper
                $someInputControlUri123 = $someInputControlUri;
                $someInputControlUri123 = preg_replace("/\/[\w\._-]*$/", "", $someInputControlUri123);
                $someInputControlUri123 .= "/" . $key1;
                print $someInputControlUri123 . "<HR>";
                $attachment1 = array();
                $result = $this->ws_eos_get_shell($someInputControlUri123, $GLOBALS['loginJasper'], $GLOBALS['passJasper'], $attachment1); //get filename
                $dolroot = DOL_DATA_ROOT;
                $fullPath = $dolroot . "/JasperImg"; #($attachment1["cid:attachment"]);
                if (is_writable($dolroot) && !file_exists($fullPath)) {
                    mkdir($fullPath, 0700);
                } else if (!is_writable($dolroot)) {
                    print "Err : " . $dolroot . " n'est pas accessible en ecriture<BR>";
                }
                if (file_exists($fullPath) && (is_writable($fullPath))) {

                    print basename($key1);
                    if (file_exists($fullPath . "/" . basename($key1))) {
                        unlink($fullPath . "/" . basename($key1));
                    }
                    $fh = fopen($fullPath . "/" . basename($key1), "w");
                    fwrite($fh, $attachment1["cid:attachment"]);
                    fclose($fh);
                } else {
                    print "Err:" . $fullPath . " n'est pas accessible en ecriture<BR>";
                }

                print "<HR>";
                //Replace image SRC
                //$img = $domAttach->getElementsByTagname("img");
                #print $img->length;
                $arrSrcReplace = array();
                for ($i = 0; $i < $img->length; $i++) {
                    $src = basename($img->item($i)->getAttribute("src"));
                    $doliURL = DOL_URL_ROOT;
                    $src = $doliURL . "/../documents/JasperImg/" . $src;
                    #echo "<H3 style='color: #FF0000;'>".$img->item($i)->getAttribute("src")."<BR>";
                    $img->item($i)->setAttribute("src", $src);
                }
            }
            //Affiche HTML
            echo utf8_decode($domAttach->saveHTML());
        }
    }

    function dlReport($remArray, $keySeeked, $return = false) {
        foreach ($remArray as $key => $val) {
            if ($key != $keySeeked)
                continue;
            $someInputControlUri = $val;
            $attachment = array();
            $result = $this->ws_eos_get_shell($someInputControlUri, $GLOBALS['loginJasper'], $GLOBALS['passJasper'], $attachment); //get filename
            //var_dump($result);
            $domDocument = new DomDocument();
            $domDocument->loadXML($result);
            $filename = $domDocument->getElementsByTagname("label")->item(0)->nodeValue;
            //echo "<BR>".$filename."<BR>";
            //Prob 1 IMG Src
            $pdf_content = $attachment["cid:attachment"];
            if ($return) {
                return($pdf_content);
            } else {
                // We'll be outputting a PDF
                header('Content-type: application/pdf');
                // It will be called downloaded.pdf
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo $pdf_content;
            }
        }
    }

    function writeFile($filename, $attachement) {
        $fh = fopen($GLOBALS["tmpFilePath"] . '/' . $filename, "w");
        #        if ($GLOBALS['verbose']) echo $filename ." \t\tOK\n";
        #        fwrite($fh,$attachement);
        #        fclose($fh);
        #system ("cp -i  ".$GLOBALS["tmpFilePath"].'/'.$filename." ".$GLOBALS["filePath"].'/');
        #        if ($GLOBALS['verbose']) print ("curl -s -u".$GLOBALS["loginZimbra"].":".$GLOBALS["passZimbra"]." -T ".$GLOBALS['tmpFilePath'].'/'.$filename."  ".$GLOBALS['filePath']."\n");
        #        if ($GLOBALS['verbose']) system ("curl -u".$GLOBALS["loginZimbra"].":".$GLOBALS["passZimbra"]." -T ".$GLOBALS['tmpFilePath'].'/'.$filename."  ".$GLOBALS['filePath']);
        #        else  system ("curl -s -u".$GLOBALS["loginZimbra"].":".$GLOBALS["passZimbra"]." -T ".$GLOBALS['tmpFilePath'].'/'.$filename."  ".$GLOBALS['filePath']);
        #        unlink ($GLOBALS["tmpFilePath"].'/'.$filename);
    }

    // ws_checkUsername try to list a void URL. If no WS error occurs, the credentials are fine
    function ws_checkUsername($username, $password) {
        $connection_params = array("user" => $username, "pass" => $password);
        $info = new nusoap_client($GLOBALS["webservices_uri"], false, false, $connection_params);

        $op_xml = "<request operationName=\"list\"><resourceDescriptor name=\"\" wsType=\"folder\" uriString=\"\" isNew=\"false\">" .
                "<label></label></resourceDescriptor></request>";

        $params = array("request" => $op_xml);
        $response = $info->call("list", $params, array('namespace' => $GLOBALS["namespace"]));
        unset($info);
        return $response;
    }

    function ws_list($uri, $args = array()) {
        ;

        $connection_params = array("user" => $_SESSION["username"], "pass" => $_SESSION["password"]);
        $info = new nusoap_client($GLOBALS["webservices_uri"], false, false, $connection_params);

        $op_xml = "<request operationName=\"list\">";

        if (is_array($args)) {
            $keys = array_keys($args);
            foreach ($keys AS $key) {
                $op_xml .="<argument name=\"$key\">" . $args[$key] . "</argument>";
            }
        }

        $op_xml .="<resourceDescriptor name=\"$uri\" wsType=\"folder\" uriString=\"$uri\" isNew=\"false\">" .
                "<label></label></resourceDescriptor></request>";

        $params = array("request" => $op_xml);
        $response = $info->call("list", $params, array('namespace' => $GLOBALS["namespace"]));
        unset($info);

        return $response;
    }

    function ws_list_eos_shell($uri, $username, $password, $args = array()) {
        $info = new nusoap_client($GLOBALS["webservices_uri"]);
        $info->username = $username;
        $info->password = $password;
        $info->authtype = 'basic';

        $op_xml = "<request operationName=\"list\">";

        if (is_array($args)) {
            $keys = array_keys($args);
            foreach ($keys AS $key) {
                $op_xml .="<argument name=\"$key\">" . $args[$key] . "</argument>";
            }
        }

        $op_xml .="<resourceDescriptor name=\"$uri\" wsType=\"folder\" uriString=\"$uri\" isNew=\"false\">" .
                "<label></label></resourceDescriptor></request>";

        $params = array("request" => $op_xml);
        $response = $info->call("list", $params, array('namespace' => $GLOBALS["namespace"]));
//require_once('Var_Dump.php');
//var_dump::display($response);

        unset($info);

        return $response;
    }

    function ws_get($uri, $args = array()) {
        ;

        $connection_params = array("user" => $_SESSION["username"], "pass" => $_SESSION["password"]);
        $info = new nusoap_client($GLOBALS["webservices_uri"], false, false, $connection_params);

        $op_xml = "<request operationName=\"get\">";

        if (is_array($args)) {
            $keys = array_keys($args);
            foreach ($keys AS $key) {
                $op_xml .="<argument name=\"$key\">" . $args[$key] . "</argument>";
            }
        }

        $op_xml .= "<resourceDescriptor name=\"$uri\" wsType=\"reportUnit\" uriString=\"$uri\" isNew=\"false\">" .
                "<label></label></resourceDescriptor></request>";

        $params = array("request" => $op_xml);
        $response = $info->call("get", $params, array('namespace' => $GLOBALS["namespace"]));

        unset($info);

        return $response;
    }

    function ws_eos_get($uri, $username, $password, &$attachment, $args = array()) {
        ;

        $connection_params = array("user" => $username, "pass" => $password);
        $info = new nusoap_client($GLOBALS["webservices_uri"], false, false, $connection_params);

        $op_xml = "<request operationName=\"get\">";

        if (is_array($args)) {
            $keys = array_keys($args);
            foreach ($keys AS $key) {
                $op_xml .="<argument name=\"$key\">" . $args[$key] . "</argument>";
            }
        }
        $op_xml .="<argument name=\"USE_DIME_ATTACHMENTS\"><![CDATA[1]]></argument>";
        $op_xml .= "<resourceDescriptor name=\"$uri\" wsType=\"reportUnit\" uriString=\"$uri\" isNew=\"false\">" .
                "<label></label></resourceDescriptor></request>";
        $params = array("request" => $op_xml);
        $response = $info->call("get", $params, array('namespace' => $GLOBALS["namespace"]));
        $attachment = $info->_soap_transport->attachments;
        unset($info);
        return $response;
    }

    function ws_eos_get_shell($uri, $username, $password, &$attachment, $args = array()) {
        //print  $username . " " . $password;
        //print $GLOBALS["webservices_uri"];
        $info = new nusoap_client($GLOBALS["webservices_uri"]);
        $info->username = $username;
        $info->password = $password;
        $info->authtype = 'basic';

        $op_xml = "<request operationName=\"get\">";

        if (is_array($args)) {
            $keys = array_keys($args);
            foreach ($keys AS $key) {
                $op_xml .="<argument name=\"$key\">" . $args[$key] . "</argument>";
            }
        }
        $op_xml .="<argument name=\"USE_DIME_ATTACHMENTS\"><![CDATA[1]]></argument>";
        $op_xml .= "<resourceDescriptor name=\"$uri\" wsType=\"reportUnit\" uriString=\"$uri\" isNew=\"false\">" .
                "<label></label></resourceDescriptor></request>";
        $params = array("request" => $op_xml);
        $response = $info->call("get", $params, array('namespace' => $GLOBALS["namespace"]));
//        echo "<pre>";var_dump($info);echo "</pre>";die($response);
        $attachment = $info->_soap_transport->attachments;
//                var_dump($attachment);
        //unset ($info);
        return $response;
    }

    function ws_runReport($uri, $report_params, $output_params, &$attachments) {
        $max_execution_time = 120; // 2 mins.

        $connection_params = array("user" => $_SESSION["username"], "pass" => $_SESSION["password"], "timeout" => $max_execution_time);
        $info = new nusoap_client($GLOBALS["webservices_uri"], false, false, $connection_params);

        /*
          //$v =  new SOAP_Attachment('test','application/octet',"c:\client_file.png");
          //$methodValue = new SOAP_Value('request', 'this is my request', array($v));
          //$av=array($v);
         */

        $op_xml = "<request operationName=\"runReport\">";

        if (is_array($output_params)) {
            $keys = array_keys($output_params);
            foreach ($keys AS $key) {
                $op_xml .="<argument name=\"$key\">" . $output_params[$key] . "</argument>\n";
            }
        }

        $op_xml .="<argument name=\"USE_DIME_ATTACHMENTS\"><![CDATA[1]]></argument>";

        $op_xml .="<resourceDescriptor name=\"\" wsType=\"reportUnit\" uriString=\"$uri\" isNew=\"false\">" .
                "<label></label>";


        // Add parameters...
        if (is_array($report_params)) {
            $keys = array_keys($report_params);
            foreach ($keys AS $key) {
                $op_xml .="<parameter name=\"$key\"><![CDATA[" . $report_params[$key] . "]]></parameter>";
            }
        }

        $op_xml .="</resourceDescriptor></request>";
        $params = array("request" => $op_xml);
        $response = $info->call("runReport", $params, array('namespace' => "http://www.jaspersoft.com/client"));
        $attachments = $info->_soap_transport->attachments;
        unset($info);
        return $response;
    }

    function ws_eos_runReport($uri, $username, $password, &$attachments, $report_params = array(), $output_params = array()) {
        $max_execution_time = 120; // 2 mins.

        $info = new nusoap_client($GLOBALS["webservices_uri"]);
        $info->username = $username;
        $info->password = $password;
        $info->authtype = 'basic';
        $info->timeout = $max_execution_time;

        /*
          //$v =  new SOAP_Attachment('test','application/octet',"c:\client_file.png");
          //$methodValue = new SOAP_Value('request', 'this is my request', array($v));
          //$av=array($v);
         */

        $op_xml = "<request operationName=\"runReport\">";

        if (is_array($output_params)) {
            $keys = array_keys($output_params);
            foreach ($keys AS $key) {
                $op_xml .="<argument name=\"$key\">" . $output_params[$key] . "</argument>\n";
            }
        }

        $op_xml .="<argument name=\"USE_DIME_ATTACHMENTS\"><![CDATA[1]]></argument>";

        $op_xml .="<resourceDescriptor name=\"\" wsType=\"reportUnit\" uriString=\"$uri\" isNew=\"false\">" .
                "<label></label>";


        // Add parameters...
        if (is_array($report_params)) {
            $keys = array_keys($report_params);
            foreach ($keys AS $key) {
                $op_xml .="<parameter name=\"$key\"><![CDATA[" . $report_params[$key] . "]]></parameter>";
            }
        }

        $op_xml .="</resourceDescriptor></request>";
        $params = array("request" => $op_xml);
        $response = $info->call("runReport", $params, array('namespace' => "http://www.jaspersoft.com/client"));
        $attachments = $info->_soap_transport->attachments;
        unset($info);

        return $response;
    }

    // ********** XML related functions *******************
    function getOperationResult($operationResult) {
        $domDocument = new DOMDocument();
        $domDocument->loadXML($operationResult);
        $operationResultValues = array();
        foreach ($domDocument->childNodes AS $ChildNode) {
            if ($ChildNode->nodeName != '#text') {
                if ($ChildNode->nodeName == "operationResult") {
                    foreach ($ChildNode->childNodes AS $ChildChildNode) {
                        if ($ChildChildNode->nodeName == 'returnCode') {
                            $operationResultValues['returnCode'] = $ChildChildNode->nodeValue;
                        } else if ($ChildChildNode->nodeName == 'returnMessage') {
                            $operationResultValues['returnMessage'] = $ChildChildNode->nodeValue;
                        }
                    }
                }
            }
        }
        return $operationResultValues;
    }

    function getResourceDescriptors($operationResult) {
        $domDocument = new DOMDocument();
        $domDocument->loadXML($operationResult);

        $folders = array();
        $count = 0;

        foreach ($domDocument->childNodes AS $ChildNode) {
            if ($ChildNode->nodeName != '#text') {
                if ($ChildNode->nodeName == "operationResult") {
                    foreach ($ChildNode->childNodes AS $ChildChildNode) {
                        if ($ChildChildNode->nodeName == 'resourceDescriptor') {
                            $resourceDescriptor = $this->readResourceDescriptor($ChildChildNode);
                            $folders[$count] = $resourceDescriptor;
                            $count++;
                        }
                    }
                }
            }
        }
        return $folders;
    }

    function readResourceDescriptor($node) {
        $resourceDescriptor = array();

        $resourceDescriptor['name'] = $node->getAttributeNode("name")->value;
        $resourceDescriptor['uri'] = $node->getAttributeNode("uriString")->value;
        $resourceDescriptor['type'] = $node->getAttributeNode("wsType")->value;

        $resourceProperties = array();
        $subResources = array();
        $parameters = array();

        // Read subelements...
        foreach ($node->childNodes AS $ChildNode) {
            if ($ChildNode->nodeName == 'label') {
                $resourceDescriptor['label'] = $ChildNode->nodeValue;
            } else if ($ChildNode->nodeName == 'description') {
                $resourceDescriptor['description'] = $ChildNode->nodeValue;
            } else if ($ChildNode->nodeName == 'resourceProperty') {
                //$resourceDescriptor['resourceProperty'] = $ChildChildNode->nodeValue;
                // read properties...
                $resourceProperty = $this->addReadResourceProperty($ChildNode);
                $resourceProperties[$resourceProperty["name"]] = $resourceProperty;
            } else if ($ChildNode->nodeName == 'resourceDescriptor') {
                array_push($subResources, $this->readResourceDescriptor($ChildNode));
            } else if ($ChildNode->nodeName == 'parameter') {
                $parameters[$ChildNode->getAttributeNode("name")->value] = $ChildNode->nodeValue;
            }
        }

        $resourceDescriptor['properties'] = $resourceProperties;
        $resourceDescriptor['resources'] = $subResources;
        $resourceDescriptor['parameters'] = $parameters;


        return $resourceDescriptor;
    }

    function addReadResourceProperty($node) {
        $resourceProperty = array();

        $resourceProperty['name'] = $node->getAttributeNode("name")->value;

        $resourceProperties = array();

        // Read subelements...
        foreach ($node->childNodes AS $ChildNode) {
            if ($ChildNode->nodeName == 'value') {
                $resourceProperty['value'] = $ChildNode->nodeValue;
            } else if ($ChildNode->nodeName == 'resourceProperty') {
                //$resourceDescriptor['resourceProperty'] = $ChildChildNode->nodeValue;
                // read properties...
                array_push($resourceProperties, $this->addReadResourceProperty($ChildNode));
            }
        }

        $resourceProperty['properties'] = $resourceProperties;

        return $resourceProperty;
    }

    // Sample to put something on the server
    function ws_put() {
        ;

        $connection_params = array("user" => $_SESSION["username"], "pass" => $_SESSION["password"]);

        $fh = fopen("c:\\myimage.gif", "rb");
        $data = fread($fh, filesize("c:\\myimage.gif"));
        fclose($fh);

        $attachment = array("body" => $data, "content_type" => "application/octet-stream", "cid" => "123456");

        $info = new nusoap_client($GLOBALS["webservices_uri"], false, false, $connection_params);
        $info->_options['attachments'] = 'Dime';
        $info->_attachments = array($attachment);

        $op_xml = "<request operationName=\"put\">";
        $op_xml .="<resourceDescriptor name=\"JRLogo3\" wsType=\"img\" uriString=\"/images/JRLogo3\" isNew=\"true\"><label>JR logo PHP</label>";
        $op_xml .="<description>JR logo</description><resourceProperty name=\"PROP_HAS_DATA\"> <value><![CDATA[true]]></value></resourceProperty>";
        $op_xml .="<resourceProperty name=\"PROP_PARENT_FOLDER\"><value><![CDATA[/images]]></value></resourceProperty></resourceDescriptor></request>";

        $params = array("request" => $op_xml);
        $response = $info->call("put", $params, array('namespace' => $GLOBALS["namespace"]));
        unset($info);

        return $response;
    }

}

?>
