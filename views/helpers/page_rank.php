<?php
/**
 * PageRank Helper
 *
 * This helper will build Dynamic Menu
 *
 * @author M. M. Rifat-Un-Nabi <to.rifat@gmail.com>
 * @package PageRank
 * @subpackage PageRank.views.helpers
 */

App::import('Helper');

class PageRankHelper extends AppHelper {

/**
 * Yahoo API Key
 *
 * @var String
 * @access private
 */
    private $yahoo_api = 'xAjzxtvV34FFclQMjEUOar6aJRK5ABsk4K04guLpUv1oJPHoGK8y1SLSm8FT3w--';    

    /**
 * Constructor.
 *
 * @access private
 */
    function __construct($config=array()) {
        Cache::config('PageRank', array(  
            'duration'=> '+1 hours',  
            'engine'=> 'File',
            //'path' => CACHE . 'page_rank' . DS,   
            'path' => CACHE,   
            'prefix' => 'page_rank_',
        ));
    }

 /**
 * Returns Formated Number.
 *
 * @param integer Number
 * @return string Formated Number
 * @access protected
 */
    protected function _format($number) {
        return number_format(doubleval($number));
    }
    
    
/**
 * Returns Alexa PageRank of the given $url.
 *
 * @param string URL.
 * @return string Alexa PageRank
 * @access public
 */
    public function alexa($url) {
        $alexa = Cache::read('alexa_'.md5($url), 'PageRank');
        if($alexa !== false)
            return $alexa;
        
        //&dat=snbamz
        $alexa = '0';
        $url = 'http://data.alexa.com/data?cli=10&url=' . trim($url);
        $xmldata = simplexml_load_file($url);
        if(isset($xmldata->SD->POPULARITY['TEXT'][0]))
            $alexa = $this->_format($xmldata->SD->POPULARITY['TEXT'][0]);
        
        Cache::write('alexa_'.md5($url), $alexa, 'PageRank');
        return $alexa;    
    }
    
/**
 * Returns Facebook Likes count of the given page $url.
 *
 * @param string Facebook page URL.
 * @return string Facebook Likes
 * @access public
 */
    public function facebook($url) {
        $fb = Cache::read('fb_'.md5($url), 'PageRank');
        if($fb !== false)
            return $fb;
        
        if($pos = strrpos($url, '/')):
            $url = substr($url, $pos+1);
        endif;
        $fb = '0';
        $json = json_decode(file_get_contents('http://graph.facebook.com/?id='.urldecode($url)));
        //debug($json);
        if(is_object($json) && isset($json->shares)) $fb = $this->_format($json->shares);
        if(is_object($json) && isset($json->likes)) $fb = $this->_format($json->likes);
        
        Cache::write('fb_'.md5($url), $fb, 'PageRank');
        return $fb;
    }
    
/**
 * Returns Twitter followers count of the given $url.
 *
 * @param string Twitter page URL.
 * @return string Twitter followers count
 * @access public
 */
    public function twitter($url) {
        $twitter = Cache::read('twitter_'.md5($url), 'PageRank');
        if($twitter !== false)
            return $twitter;
        
        $twitter = '0';
        $url = rtrim($url, ' /');
        $name = substr($url, strrpos($url, '/')+1);
        $api_url = 'https://api.twitter.com/1/users/show.json?screen_name='.$name;
        $json = json_decode(file_get_contents($api_url));
        if(is_object($json)) $twitter = $this->_format($json->followers_count);
        
        Cache::write('twitter_'.md5($url), $twitter, 'PageRank');
        return $twitter;
    }
    
/**
 * Returns Yahoo Inbound of the given page $url.
 *
 * @param string URL.
 * @return string Yahoo inbound counts
 * @access public
 */
    public function yahoo($url) {
        $yahoo = Cache::read('yahoo_'.md5($url), 'PageRank');
        if($yahoo !== false)
            return $yahoo;
        
        $yahoo = '0';
        $api_url = 'http://search.yahooapis.com/SiteExplorerService/V1/inlinkData?appid='.$this->yahoo_api.'&output=php&entire_site=1&query='.$url;
        $data = unserialize(file_get_contents($api_url));
        if(is_array($data)) $yahoo = $this->_format($data['ResultSet']['totalResultsAvailable']);
        
        Cache::write('yahoo_'.md5($url), $yahoo, 'PageRank');
        return $yahoo;
    }
 
    private function genhash ($url) {
        $hash = 'Mining PageRank is AGAINST GOOGLE\'S TERMS OF SERVICE. Yes, I\'m talking to you, scammer.';
        $c = 16909125;
        $length = strlen($url);
        $hashpieces = str_split($hash);
        $urlpieces = str_split($url);
        for($d = 0; $d < $length; $d++) {
            $c = $c ^ (ord($hashpieces[$d]) ^ ord($urlpieces[$d]));
            $c = $this->zerofill($c, 23) | $c << 9;
        }
        return '8' . $this->hexencode($c);
    }

    private function zerofill($a, $b) {
        $z = hexdec(80000000);
        if ($z & $a) {
            $a = ($a>>1);
            $a &= (~$z);
            $a |= 0x40000000;
            $a = ($a>>($b-1));
        } else {
            $a = ($a>>$b);
        }
        return $a;
    }

    private function hexencode($str) {
        $out  = $this->hex8($this->zerofill($str, 24));
        $out .= $this->hex8($this->zerofill($str, 16) & 255);
        $out .= $this->hex8($this->zerofill($str, 8 ) & 255);
        $out .= $this->hex8($str & 255);
        return $out;
    }

    private function hex8 ($str) {
        $str = dechex($str);
        (strlen($str) == 1 ? $str = '0' . $str: null);
        return $str;
    }

/**
 * Returns Google PageRank of the given page $url.
 *
 * @param string URL.
 * @return string Google PageRank
 * @access public
 */
     public function google($url) {
        $google = Cache::read('google_'.md5($url), 'PageRank');
        if($google !== false)
            return $google;
        
        $google = '0';
        $googleurl = 'http://toolbarqueries.google.com/search?features=Rank&sourceid=navclient-ff&client=navclient-auto-ff&googleip=O;66.249.81.104;104&ch=' . $this->genhash($url) . '&q=info:' . urlencode($url);
        $out = file_get_contents($googleurl);
        $google = trim(substr($out, 9));
        
        Cache::write('google_'.md5($url), $google, 'PageRank');
        return $google;
    }
    
}
