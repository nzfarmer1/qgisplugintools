#!/usr/bin/php
<?php


# Upload file to QGIS Plugin

/***************************************************************************
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 ***************************************************************************/

# Author: Andrew McClure <andrew@southweb.co.nz>


class Scraper
{
    public $ch;
    public $header = array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/16.1",
                            "Accept-Language: en-us,en;q=0.5",
                            "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5",
                            "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
                           );
   
    public $file;
    public $root = "https://plugins.qgis.org";
    public $file_name = "";
    public $username = 'XXXXX';
    public $password = 'XXXXX';

    private $token;
    private $doc;
    private $xpath;

                            
    
    public function __construct($file_name)
    {
        libxml_use_internal_errors(TRUE);
        
        $this->ch = curl_init();
	$this->file_name = $file_name;
        if (!file_exists($this->file_name))
		 die("Usage upload.php <filename.zip>\n");

        curl_setopt($this->ch, CURLOPT_HEADER, 0);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 20);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, 'cok.txt');
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, 'cok.txt');
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->ch, CURLOPT_ENCODING, '');
        $this->doc = new DOMDocument();
        
    }
    
    public function __destruct()
    {
        curl_close($this->ch);
    }
    
    public function get($url)
    {
        curl_setopt ($this->ch, CURLOPT_HTTPGET, TRUE); 
        curl_setopt ($this->ch, CURLOPT_POST, FALSE); 
        curl_setopt($this->ch, CURLOPT_URL, $url);
        
        $this->file = curl_exec($this->ch);
        if($this->file === false)
        {
            throw new Exception('Curl error: ' . curl_error($this->ch) . '=====> ' . $url . "\r\n");
        }
        
    }
    
    public function post($url, $data = NULL)
    {
        curl_setopt ($this->ch, CURLOPT_HTTPGET, FALSE); 
        curl_setopt ($this->ch, CURLOPT_POST, TRUE); 
        curl_setopt($this->ch, CURLOPT_URL, $url);
        
        if(isset($data))
        {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
        }
        $this->file = curl_exec($this->ch);
        if($this->file === false)
        {
            throw new Exception('Curl error: ' . curl_error($this->ch) . '=====> ' . $url . "\r\n");
        }
        
    }
    
    public function upload()
    {
        
        $url = $this->root . "/accounts/login/";
        $data = $this->parseLogin($url);
        $postdata = http_build_query($data);
        $this->post($url . "/.", $postdata);

        $this->doc->loadHTML($this->file);
        $this->xpath = new DOMXPath($this->doc);
        
        $upload = array('csrfmiddlewaretoken' => $this->getToken(""),
                        'experimental' =>"on",
                        'package'=>'@'.$this->file_name);
        
        $this->post($this->root . $data['next'],$upload);

        $this->doc->loadHTML($this->file);
        $this->xpath = new DOMXPath($this->doc);
        $query  = "//ul[@class='errorlist']/li";
        $nodes = $this->xpath->query($query);
        if (count($nodes)>0 && strlen($nodes->item(0)->nodeValue) >0 ) {
            echo $nodes->item(0)->nodeValue;
            return;
        }
#        var_dump($this->file);
 
        $nodes = $this->xpath->query("//div[@class='alert']/p[@class='success']");
        echo $nodes->item(0)->nodeValue;

        $nodes = $this->xpath->query("//div[@class='alert']/p[@class='warning']");
        echo $nodes->item(0)->nodeValue;
    }
    
    public function getToken($action)
    {
        $query  = "//form[@action='" .$action ."']/input[@name='csrfmiddlewaretoken']";
        $nodes = $this->xpath->query($query);
        return $nodes->item(0)->getAttribute('value');
    }
    
    
    public function parseLogin($url)
    {
        $username = urlencode($this->username);
        $password = urlencode($this->password);
        
        $this->get($url);
        $this->doc->loadHTML($this->file);
        $this->xpath = new DOMXPath($this->doc);
         
        $token =  $this->getToken(".");
        
        $data =array('csrfmiddlewaretoken' => $token,
                     'username' => $username,
                     'password' => $password,
                     'next' =>'/plugins/add/');
        
         return $data;
    }
}

$sc = new Scraper($argv[1]);
$sc->upload();


