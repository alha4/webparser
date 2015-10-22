<?php
 
 function error_debug_trace() {
   ini_set("display_errors","on");
   ini_set('error_reporting', E_ERROR);
   error_reporting(E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR);
 }
 
 error_debug_trace();
 
 interface HttpLoader {
    function load($url);
    function check_loaded_extension();
 }

 interface DataRender {
    function render($HTML); 
 }
 
 interface DataCollection {
    function set($key,$value);
    function get();
 }
 
 interface DataBulder {
    function buld($data);
 }
 
 interface DataStorageFacade {
    
   public function save($dataCollection,$params);
    
 }
 
 class SimpleCurlLoader implements HttpLoader {
    
    function __construct() {
        try {
            $this->check_loaded_extension();
        } catch(Exception $err) {
            exit($err->getMessage());
        }
    }
    
    function load($url) {
        
      $ch = curl_init();
      
      $host = parse_url($url);
      $host = $host['host'];
      
      $headers = array(
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language: ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3",
            "Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7",
            "Host: $host"
      );  
      
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      
      curl_setopt($ch, CURLOPT_REFERER, $host);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
      curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);

      curl_setopt($ch, CURLOPT_USERAGENT, $this->get_random_user_agent());
      curl_setopt($ch, CURLOPT_TIMEOUT, 45);
      curl_setopt($ch, CURLOPT_STDERR, $_SERVER['DOCUMENT_ROOT']."/curl_error.txt");
      curl_setopt($ch, CURLOPT_VERBOSE,1);
      
      /*$proxy = $proxy[mt_rand(0,count($proxy) - 1)];
      curl_setopt($ch, CURLOPT_PROXY, $proxy);*/
      
      curl_setopt($ch, CURLOPT_FAILONERROR, 1);
      curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
      
      curl_setopt($ch, CURLOPT_COOKIEJAR,  $_SERVER['DOCUMENT_ROOT']."/cookie.txt");
      curl_setopt($ch, CURLOPT_COOKIEFILE, $_SERVER['DOCUMENT_ROOT']."/cookie.txt");
      
      $body = curl_exec($ch);
      
      curl_close($ch);
      
      if(curl_errno($ch)!==0 && $body ) {
        return $body;
      }
      return false; 
    }
    private function get_random_user_agent() {
     $uas = array(
       'Mozilla/4.0 (compatible; MSIE 6.0; Windows 98)',
       'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0; .NET CLR 1.0.3705)',
       'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Maxthon)',
       'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; bgft)',
       'Mozilla/4.5b1 [en] (X11; I; Linux 2.0.35 i586)',
       'Mozilla/5.0 (compatible; Konqueror/2.2.2; Linux 2.4.14-xfs; X11; i686)',
       'Mozilla/5.0 (Macintosh; U; PPC; en-US; rv:0.9.2) Gecko/20010726 Netscape6/6.1',
       'Mozilla/5.0 (Windows; U; Win98; en-US; rv:0.9.2) Gecko/20010726 Netscape6/6.1',
       'Mozilla/5.0 (X11; U; Linux 2.4.2-2 i586; en-US; m18) Gecko/20010131 Netscape6/6.01',
       'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:0.9.3) Gecko/20010801',
       'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.0.7) Gecko/20060909 Firefox/1.5.0.7',
       'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.6) Gecko/20040413 Epiphany/1.2.1',
       'Opera/9.0 (Windows NT 5.1; U; en)',
       'Opera/8.51 (Windows NT 5.1; U; en)',
       'Opera/7.21 (Windows NT 5.1; U)',
       'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT)',
       'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)',
       'Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US; rv:1.8.0.6) Gecko/20060928 Firefox/1.5.0.6',
       'Opera/9.02 (Windows NT 5.1; U; en)',
       'Opera/8.54 (Windows NT 5.1; U; en)'
     );
 
      return $uas[rand(0, count($uas)-1)];
    }
    
    final function check_loaded_extension() {

        if( !extension_loaded("curl") ) {
            throw new \Exception('Ошибка не подключен [curl] модуль');
        }
        return true;
    }
 }

 class XpathDataRender implements DataRender {
    
    private $bulder;
    
    private $XExp;
    
    function __construct(DataBulder $bulder,$expression) {
      try {
        $this->check_loaded_dom_extension();
        $this->bulder = $bulder;
        $this->XExp = $expression;
      } catch(Exception $err) {
        
         exit($err->getMessage());
      } 
    }
    
    function render($stringHTML) {
        
      $dom = new \DomDocument();
      $dom->preserveWhiteSpace = false;
      $dom->loadHTML($stringHTML);
     
      $xpath = new \DOMXpath($dom);
      $nodes = $xpath->query($this->XExp);

    
      if($nodes !== false && $nodes->length !== 0) {
        
         return $this->bulder->buld($nodes);
         
      } else {
        // print_r($nodes);
         exit('данные не найдены');
      }
     
  }
  
  private function check_loaded_dom_extension() {
     if( !extension_loaded("dom") ) {
            throw new \Exception('Ошибка не подключен [dom] модуль');
     }
  }
}
 
class BannerBulder implements DataBulder {

  public function buld($nodes) {
  
  $arResult = array();

   foreach($nodes as $item) {
    
      $childItems = $item->childNodes;

      $link = $childItems->item(1);
      $uri =  parse_url($link->getAttribute("href"));

      if( $uri['query'] != '' ) {

        $page = substr($uri['query'],2);

        if( strpos(rawurldecode($page),"?") !== false) {

            $page = explode("?",rawurldecode($page));

            $page = str_replace(array("+","-","_","/")," ",$page[0]);

        } else {

           $page = str_replace(" ","-",$page);
           $page.='-White';
        }

      } else {

        $uri_split = explode("/",$uri['path']);

        

        $page = str_replace(array("_","/","-","+")," ",$uri_split[count($uri_split) - 2]);
      } 
     
      $img =  $link->firstChild->nextSibling;
      $src =  $img->getAttribute("src");

      $arResult[] = array("page"=>$page,"img"=>$src);

   }

   return $arResult;

  }
}

class WebPageParser {
    
    private $webPagesURI;
    
    private $dataRender;
    
    function __construct($url) {
        $this->webPagesURI = $url;
    }
    public function load(HttpLoader $loader,DataRender $render) {

     $pages_url = $this->webPagesURI;
        
     $result = $loader->load($pages_url);

     //print_r($result);
    
     if(false !== $result) { 

        return $render->render($result);
       
            
    } else {
            
         echo 'failed curl request';
     }
        
    }
 }

?>