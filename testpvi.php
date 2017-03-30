<?php 
define('JUMP',"\n\n");

function url_get_contents($adresse, $timeout = 5){
	$ch = curl_init($adresse);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_REFERER, "http://www.dfdsfs.fr");
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux i686; rv:18.0) Gecko/20130330 Thunderbird/17.0.5");
	//curl_setopt($ch, CURLOPT_HEADER, array('HTTP_X_FORWARDED_FOR: 1.2.3.4'));
	//curl_setopt($ch, CURLOPT_PROXY, "54.251.150.200:80");
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

	$page		= curl_exec($ch);
	$CurlErr	= curl_error($ch);
	curl_close($ch);
	if ($CurlErr) {
		error_log($CurlErr);
		return false;
	} else {
		return $page;
	}
}

function getSiteList($contenu,$contentFile,$contentUrl) {
	preg_match_all('/<ol class="x2">(.*)<dl class="JS_PJ js_hide">/s', $contenu, $results);
	if (isset($results[1][0])) {
		if (!is_file($contentFile)) {
			file_put_contents($contentFile, $results[0][0]);
		}
		if (!is_file($contentUrl)) {
			$urls = array();
			preg_match_all('/<a href="(.*)">/', $results[0][0], $items);
			$string = "";
			foreach ($items[1] as $value) {
				$string.=$value."\n";
			}
			file_put_contents($contentUrl, $string);
		}
	}
}


function getCard($content) {
	preg_match_all('/_DATA(.*?);/s',$content,$result);
	if (isset($result[0][0])) {
		$pos = strpos($result[0][0],"{");

		$parse =  (substr($result[0][0],$pos,strlen($result[0][0])-$pos-1));
echo '1'.JUMP.$parse.JUMP;
		//$parse = preg_replace('/^\s*([\[\{]*)\s*(\w+)\s*\:\s*(.*)$/s','\1"\2":\3',$parse);
		$parse = str_replace(':,', ':0,', $parse);

		$parse = preg_replace('/([0-9a-zA-Z_]+)\s*\:\s*("|\[|[\-0-9]|{)/', '"\1":\2' , $parse);

		$parse = preg_replace('/\[\s*(,)\s*{/','[{' ,$parse);

		 //str_replace('[,{', '[{', $parse);

		$parse = str_replace('\\\'', '\'', $parse);

echo '2'.JUMP.$parse.JUMP;

		$parse = preg_replace('/\s+/', ' ', $parse);

		$result  = json_decode($parse);

		if ($result == null) {
			$parse = preg_replace('/([0-9a-zA-Z_]+)\s*\:\s*\'([0-9a-zA-Z_]+)\'/', '"\1":"\2"', $parse);
//$parse = strtr($parse,array("\n"=>'',"\0A"=>''));
			$result = json_decode($parse);
		}
echo '3'.JUMP.$parse.JUMP;
echo 'last error '.json_last_error().JUMP;
		return $result;
	} else {
		return false;
	}
}

header("Content-Type: plain/text"); 


echo '--> '.json_encode($argv)."<--\n\n";

if (substr($argv[1], 0,5)=='http:') {
	$content = url_get_contents($argv[1]);
} else {
	$content = file_get_contents($argv[1]);
}

echo JUMP.'RESULT'.JUMP;
print_r(getCard($content));

echo "\n";
