<?php
/*
 * Класс загрузки страниц
 * 
 * @return array of 
 */


class GetPage_Main {

  const COOKIE_JAR = '/tmp/rest-client-cookie';
  const AGENT = 'rest-client newpower(600541@mail.ru)/1.0.2';
  const CONNECTTIMEOUT='20';
  
  public $response_info;
  public $response_object;
  public $response_raw;

  public $http_options = array();
  public $array_data_page = array();

  function __construct($http_options = array()) {
		$this->set_http_options($http_options);		
  }
  
  //Устанавливаем опции http
	public function set_http_options($http_options = array())
	{
		$this->http_options = array_merge(array(
			'cookiestore' => self::COOKIE_JAR,
			'useragent' => self::AGENT,
			'CONNECTTIMEOUT' => self::CONNECTTIMEOUT,
			'AGENT' => self::AGENT,
			'redirect' => 5
    	), $http_options);
	}
  
  
function get_page_run($target_url)
{
	//Обрезаем лишнее из ссылки
	$target_url=trim($target_url);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $target_url);
	if (strtolower((substr($target_url,0,5))=='https'))
	{
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	}
	  

	// curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	//curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);

	curl_setopt($ch, CURLOPT_TIMEOUT, $this->http_options["CONNECTTIMEOUT"]);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->http_options["CONNECTTIMEOUT"]); 
	curl_setopt($ch, CURLOPT_USERAGENT, $this->http_options["AGENT"]);
	
	//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
	//curl_setopt($ch, CURLOPT_MAXREDIRS, 2); 
	if ($this->http_options["PROXY"]) {
		curl_setopt($ch, CURLOPT_PROXY, $this->http_options["PROXY"]);
		if ($this->http_options["CURLOPT_PROXYUSERPWD"]) {
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->http_options["CURLOPT_PROXYUSERPWD"]);
		}
	}
	$html=curl_exec($ch); 

	$errmsg = curl_error($ch);
	$header = curl_getinfo($ch); 

	 
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	echo "http_code".$http_code;
	
	$return_array=array();
	if ($http_code == 301 || $http_code == 302 || $http_code == 303)
	{
		$this->array_data_page = $this->curl_redir_exec($ch);
		echo "JJJJJJJJJJJJJJJJJJJ";
	}
	else
	{
		$this->array_data_page=array("html"=>$html,"url"=>$target_url,"http_code"=>$http_code,"err"=>$errmsg,"header"=>$header);
	}
	//echo "page_echo".$return_array["url"]."<br><br>";
	return $this->array_data_page;
}

function curl_redir_exec($ch)
  {
	static $curl_loops = 0;
	static $curl_max_loops = 5;
	if ($curl_loops >= $curl_max_loops)
    {
    	$curl_loops = 0;
    	return false;
    }
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($ch);
	list($header, $data) = explode("\n\n", $data, 2);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
	if ($http_code == 301 || $http_code == 302 || $http_code == 303)
    {
	    $matches = array();
	    preg_match('/Location:(.*?)\n/', $header, $matches);
	    $url = @parse_url(trim(array_pop($matches)));
	    if (!$url)
	    {
	      $curl_loops = 0;
	      return $data;
	    }
	    $last_url = parse_url(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
	   
	    if (!$url['scheme']) $url['scheme'] = $last_url['scheme'];
	    if (!$url['host']) $url['host'] = $last_url['host'];
	    if (!$url['path'])  $url['path'] = $last_url['path'];
	    $new_url = $url['scheme'] . '://' . $url['host'] . $url['path'] . ($url['query']?'?'.$url['query']:'');
	   // echo $new_url.' --+- '.$http_code.'<br>';
		return $this->get_page_run($new_url);
	  //  curl_setopt($ch, CURLOPT_URL, $new_url);
	   // return curl_redir_exec($ch);
    }
  else
    {
    $curl_loops = 0;
    return $data;
    }
  }
  

}