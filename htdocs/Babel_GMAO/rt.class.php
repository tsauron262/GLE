<?php

class rt {
    public $username = "root";
    public $password = "password";
    public $url = "http://rtdemo.etatcritik.dyndns.org/rt/REST/1.0";
    public $baseurl = "http://rtdemo.etatcritik.dyndns.org/rt/REST/1.0";
    public $request;
    public $post_data = array();
    public $id;

    function rt($db) {
        $this->db = $db;
        global $conf;
        $this->username = $conf->global->GLE_RT_USER;
        $this->password = $conf->global->GLE_RT_PASS;
        $this->url = DOL_URL_ROOT."/rt/REST/1.0";
        $this->baseurl = DOL_URL_ROOT."/rt/REST/1.0";
    }


    function fetch($id)
    {
        $this->id=$id;
        $this->url = $this->baseurl . "/ticket/".$id."/show";
        $this->buidRequest();
        $this->sendRequest();
    }
    function buidRequest(){
        if (preg_match('/\?/',$this->url))
        {
            $this->url .= "&user=".$this->username."&pass=".$this->password;
        } else {
            $this->url .= "?user=".$this->username."&pass=".$this->password;
        }
        $this->request = new HttpRequest($this->url, HTTP_METH_GET);
        $this->request->addPostFields( $this->post_data );
    }
    function parseResponse($resp){
        $arr = preg_split("/\n/",$resp);
        $iter=0;
        $arrRet=array();
        foreach ($arr as $key=>$val)
        {
//            print $key." ".$val."<br>";
            if (preg_match("/^RT\/[0-9].\/[0-9].\/[0-9]/",$val))
            {
                $debug = "RT OK";
            } else if (preg_match('/--/',$val)){
                $iter++;
            }else if (preg_match('/:/',$val)){
                $arr1=array();
                $arr1 = preg_split('/:/',$val);
                $tmpVal = $arr1[1];
                $i=2;
                while ($arr1[$i]."x" != "x")
                {
                    $tmpVal .= ":".$arr1[$i];
                    $i++;
                }
                $arrRet[$iter][$arr1[0]]=$this->cleanString($tmpVal);
                if ($arr1[0]=='id')
                {
                    $arr2=preg_split('/\//',$arr1[1]);
                    $arrRet[$iter]['type']=$this->cleanString($arr2[0]);
                    $arrRet[$iter][$this->cleanString($arr2[0]).'Id']=$arr2[1];
                }
            }
        }
        return ($arrRet);
    }
    private function cleanString($str){
        $tmp = preg_replace('/^ */','',$str);
        $tmp = preg_replace('/ *$/','',$tmp);
        return $tmp;
    }
    function sendRequest(){
        $response = $this->request->send();

        $body = $response->getBody();
        $return = $this->parseResponse($body);
        //print $this->url;
//        require_once('Var_Dump.php');
//        Var_Dump::displayInit(array('display_mode' => 'HTML4_Text'), array('mode' => 'normal','offset' => 4));
//        Var_Dump::display($return);
        return ($return);
    }
    function showLinks(){
        $this->url = $this->baseurl . "/ticket/".$this->id."/links/show";
        $this->buidRequest();
        $return = $this->sendRequest();
        return ($return);
    }
    function getAttachments(){
        $this->url = $this->baseurl ."/ticket/".$this->id."/attachments";
        $this->buidRequest();
        $return = $this->sendRequest();
        return ($return);
    }
    function getAttachment($attachmentId){
        $this->url = $this->baseurl ."/ticket/".$this->id."/attachments/".$attachmentId;
        $this->buidRequest();
        $return = $this->sendRequest();
        return ($return);
    }
    function getAttachmentContent($attachmentId){
        $this->url = $this->baseurl ."/ticket/".$this->id."/attachments/".$attachmentId."/content";
        $this->buidRequest();
        $return = $this->sendRequest();
        return ($return);
    }
    function getHistory($verbose=false){
        if($verbose)
        {
            $this->url = $this->baseurl ."/ticket/".$this->id."/history?format=1";
        } else{
            $this->url = $this->baseurl ."/ticket/".$this->id."/history/";
        }
        $this->buidRequest();
        $return = $this->sendRequest();
        return ($return);
    }
    function getHistoryById($historyId){
        $this->url = $this->baseurl ."/ticket/".$this->id."/history/id/".$historyId;
        $this->buidRequest();
        $return = $this->sendRequest();
        return ($return);
    }
    function searchTicket($query,$order='+Queue',$format='l'){
        $this->url = $this->baseurl ."/search/ticket?query=".$query."&orderby=".$order."&format=".$format;
        $this->buidRequest();
        $return = $this->sendRequest();
        return ($return);
    }

    function getUserDetail($userid){
        $this->url = $this->baseurl ."user/".$userid;
        $this->buidRequest();
        $return = $this->sendRequest();
        return ($return);
    }
/*Edits
Tickets
To update an existing ticket: post on /REST/1.0/ticket/<ticket-id>/edit with a variable named "content", containing "key: value" line by line (like the one displayed when issuing ticket/<ticket-id>/show). Example:

Priority: 5
TimeWorked: 15

Comment
Tickets
To add a comment to an existing ticket: post on /REST/1.0/ticket/<ticket-id>/ with a variable name content", containing "key: value" line by line:

Text: the text comment
Attachment: an attachment filename/path

if you used "Attachment", you must add to your POST a variable attachment_1 that contains the raw attachment.

Create
Tickets
To create a new ticket: post on /REST/1.0/ticket/new with a variable named "content", containing "key: value" line by line, example:

id: ticket/new
Queue: <queue name>
Requestor: <requestor email address>
Subject: <subject>
Cc: <...>
AdminCc: <...>
Owner: <...>
Status: <...>
Priority: <...>
InitialPriority: <...>
FinalPriority: <...>
TimeEstimated: <...>
Starts: <...>
Due: <...>
Text: <The ticket content>
cf-CustomFieldName: <Custom field value>*/

}
?>