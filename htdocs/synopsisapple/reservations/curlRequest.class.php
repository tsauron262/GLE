<?php

class curlRequest
{

    public static $numbersNumChars = 10;
    public $soldTo;
    public $shipTo;
    public $pword;
    public $QRSCertfileDir;
    public $QRSCertfileName;
    public $requestName;
    protected $ch;

    public function __construct($soldTo, $shipTo, $pword, $QRSCertfileName)
    {
        $soldTo = '' . $soldTo;
        $shipTo = '' . $shipTo;
        
        if (strlen($soldTo) < self::$numbersNumChars) {
            $n = (self::$numbersNumChars - strlen($soldTo));
            while ($n > 0) {
                $soldTo = '0' . $soldTo;
                $n--;
            }
        }
        if (strlen($shipTo) < self::$numbersNumChars) {
            $n = (self::$numbersNumChars - strlen($shipTo));
            while ($n > 0) {
                $shipTo = '0' . $shipTo;
                $n--;
            }
        }
        $this->soldTo = $soldTo;
        $this->shipTo = $shipTo;
        $this->pword = $pword;
        $this->QRSCertfileDir = DOL_DOCUMENT_ROOT . '/synopsisapple/certif/';
        $this->QRSCertfileName = $QRSCertfileName;
    }

    public function __destruct()
    {
        if ($this->ch) {
            curl_close($this->ch);
        }
    }

    public function init($url)
    {
        if ($this->ch) {
            curl_close($this->ch);
        }

        $this->ch = curl_init($url);

        if (!$this->ch) {
            return false;
        }

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-PARTNER-SOLDTO : ' . $this->soldTo
        );

        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->ch, CURLOPT_SSLCERT, $this->QRSCertfileDir . $this->QRSCertfileName);
        curl_setopt($this->ch, CURLOPT_SSLCERTPASSWD, $this->pword);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT ,10); 
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
        return true;
    }

    public function exec($params)
    {
        if (!$this->ch)
            return false;

        if (count($params))
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($params));

        $data = curl_exec($this->ch);
        if ($data === false) {
            return false;
        }

        return json_decode($data);
    }

    public function getLastError()
    {
        if (!$this->ch) {
            return 'Echec de la connexion au service';
        }
        return curl_error($this->ch);
    }
}

class CurlReservationSummary extends curlRequest
{

    public function __construct($soldTo, $shipTo, $pword, $QRSCertfileName)
    {
        parent::__construct($soldTo, $shipTo, $pword, $QRSCertfileName);
        $this->init('https://asp-partner.apple.com/api/v1/partner/reservation/search');
    }

    public function fetch($from, $to, $productCode)
    {
        $params = array(
            "shipToCode" => $this->shipTo,
            "fromDate" => $from,
            "toDate" => $to,
            "productCode" => $productCode,
            "currentStatus" => "RESERVED"
        );

        return parent::exec($params);
    }
}

class CurlReservation extends curlRequest
{

    public function __construct($soldTo, $shipTo, $pword, $QRSCertfileName)
    {
        parent::__construct($soldTo, $shipTo, $pword, $QRSCertfileName);
    }

    public function fetch($reservationId)
    {
        $url = 'https://asp-partner.apple.com/api/v1/partner/reservation/' . $this->shipTo . '/' . $reservationId;

        if ($this->init($url))
            return parent::exec(array());

        return false;
    }
}
