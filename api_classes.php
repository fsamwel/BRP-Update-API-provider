<?php

/* 
 * Created by Frank Samwel
 */

class Selflink
{
    var $href;

    public function __construct()
    {
        $this->href = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
}


class HalLink
{
    var $href;
    var $templated;
    
    public function __construct($path, $templated = false)
    {
        $this->href = $path;
        $this->templated = $templated;
    }
}


class GewijzigdePersonenHalCollectie
{
    var $_links;
    var $burgerservicenummers; //array of burgerservicenummers
    
    public function __construct($burgerservicenummers)
    {
        $this->_links = new GewijzigdePersonenHalCollectionLinks();
        $this->burgerservicenummers = $burgerservicenummers;
        
    }
}


class GewijzigdePersonenHalCollectionLinks
{
    var $self;
    var $ingeschrevenPersoon;
    
    public function __construct()
    {
        $this->self = new Selflink();
        $this->ingeschrevenPersoon = new HalLink(BRPBASEURL . '/ingeschrevenpersonen/{burgerservicenummer}', true);
        
    }
}

class Volgindicatie
{
    var $burgerservicenummer;
    var $einddatum;
    
    public function __construct($burgerservicenummer = "", $einddatum = "")
    {
        if ($burgerservicenummer != "") $this->burgerservicenummer = $burgerservicenummer; else unset($this->burgerservicenummer);
        if ($einddatum != "") $this->einddatum = $einddatum; else unset($this->einddatum);
    }
}


class VolgindicatieCollectie
{
    var $volgindicaties;
    
    public function __construct($volgindicaties)
    {
        $this->volgindicaties = $volgindicaties;
    }
}


class Bericht
{
    var $burgerservicenummer;
    var $datum;
    
    public function __construct($burgerservicenummer = "", $datum = "")
    {
        $this->burgerservicenummer = $burgerservicenummer;
        $this->datum = $datum;
    }
}


class InvalidParams
{
    var $code;
    var $name;
    var $reason;

    /**
     * Constructor for details on an invalid request parameter, used in the invalid-params property of the error
     * response message
     *
     * @param string $code                  code or category of error
     * @param string $name                  name of the property
     * @param string $reason                description of the error (why the value is considered invalid)
     */
    public function __construct($code, $name, $reason)
    {
        $this->code = $code;
        $this->name = $name;
        $this->reason = $reason;

        //error_log("Invalid parameter $name: $reason");
    }
}


class Foutbericht
{
    var $type;
    var $code;
    var $title;
    var $status;
    var $detail;
    var $instance;
    //var ${'invalid-params'};

    /**
     * Constructor for the body of an error response message
     *
     * @param string $category             category of error
     * @param string $errorCode            code for the error
     * @param string $title                short description of the error
     * @param integer $status              http status code of the error
     * @param string $detail               detailed description of the error
     * @param string $techdetail           technical details of the error for internal (maintenance and debugging) use only
     * @param array $invalidParams   if the error applies to one or more invalid parameters: array of paramFoutDetails
     */
    public function __construct($category, $errorCode, $title, $status, $detail, $invalidParams=null, $techdetail="")
    {
        $this->type = "URI:" . ERRORBASEURL . "$errorCode";
        $this->code = $errorCode;
        $this->title = $title;
        $this->status = $status;
        $this->detail = $detail;
        $this->instance = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if (!is_null($invalidParams))
            $this->{'invalid-params'} = $invalidParams;

        http_response_code($status);
        header("Content-Type: application/problem+json");
        header("api-version: " . API_VERSION);

        echo json_encode($this);

        if ($status>=500 or $techdetail) error_log("Error $status: $detail");
        if ($techdetail) error_log($techdetail);
    }
}