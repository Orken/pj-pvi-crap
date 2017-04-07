<?php 
require_once 'tesseract_ocr.php';

	/**
	 * url_get_contents : recupere le contenu d'une page web
	 * 
	 * @param mixed $adresse url à récuperer.
	 * @param int   $timeout timeout de la requete, 5s par défaut.
	 *
	 * @access public
	 *
	 * @return mixed le contenu de la page ou false si erreur.
	 */
function url_get_contents($adresse, $timeout = 10){
	$ch = curl_init($adresse);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_REFERER, "http://pro.pagesjaunes.fr");
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

    /**
     * get_contents
     * 
     * @param string $cachefilename Chemin du fichier de cache.
     * @param string $url           url à charger.
     *
     * @access public
     *
     * @return string Contenu de la page téléchargée.
     */
function get_contents($cachefilename,$url) {
	$content = "";
	if (is_file($cachefilename)) {
		$content = file_get_contents($cachefilename);
	} else {
		$content = url_get_contents($url);
		file_put_contents($cachefilename, $content);
	}
	return $content;
}

/**
* 
*/
class Card 
{

	public $oda   = "";
	public $name  = "";
	public $email = "";
	public $adr1  = "";
	public $adr2  = "";
	public $adr3  = "";
	public $cp    = "";
	public $ville = "";
	public $tel1  = "";
	public $tel2  = "";

}

function removedblspc($string) {
	return preg_replace('/\s+/', ' ', $string);
}

	/**
	 * getCard
	 * 
	 * @param mixed $content Contenu à décrypter.
	 *
	 * @access public
	 *
	 * @return mixed résultat sous la forme d'un objet JSONDécodé ou false si erreur.
	 */
function getCard($content) {
	preg_match_all("/_DATA = (.*?);/s", $content, $result);
	if (isset($result[1][0])) {
		$pvi = json_decode($result[1][0]);
		$card = new Card();
		if (isset($pvi->geoCoordonnees)) {
			$geo = $pvi->geoCoordonnees[0];
			if (isset($geo->lab)) {
				$card->adr1	= trim($geo->lab);
				$card->adr2	= trim($geo->adr1);
				$card->adr3	= trim($geo->adr2);
				$card->tel2	= isset($geo->tel)?trim($geo->tel):null;
			} else if (isset($geo->default_address)) {
				$adr	= explode("-", removedblspc($geo->default_address),2);
				$card->adr1	= (isset($adr[1]))?trim($adr[0]):null;
				$card->adr2	= (isset($adr[1]))?trim($adr[1]):trim($adr[0]);
				$card->adr3	= $geo->default_city;
				$card->tel2	= '';
			} else {
				$print_r($geo);
				die('ERROR');
			}

		} else {
			$card->adr1 = $card->adr2 = $card->adr3 = $card->tel2 = null;
		}

		$card->url   = trim($pvi->url);
		$card->oda   = trim($pvi->pvi_id_oda);
		$card->name  = utf8_decode(trim($pvi->corporate_name));
		$card->email = utf8_decode(trim($pvi->email));
		$card->tel1  = utf8_decode(trim($pvi->tel));
		$card->cp    = utf8_decode(trim($pvi->cp));
		$card->ville = utf8_decode(trim($pvi->loc));
		$card->adr1  = utf8_decode($card->adr1);
		$card->adr2  = utf8_decode($card->adr2);
		$card->adr3  = utf8_decode($card->adr3);
		$card->tel2  = utf8_decode($card->tel2);
		echo '.';
		return $card;
	}
	preg_match_all("/_COMPONENT_DATAS = (.*?);/s", $content, $result);
	if (isset($result[1][0])) {
		$card = json_decode($result[1][0]);
		foreach ($card as $key => $value) {
		//	echo $key."\n";
		}
//		die('2');
	}
	return false;
}

function getCardOld($content) {
	preg_match_all('/_DATA(.*?);/s',$content,$result);
	if (isset($result[0][0])) {
		$pos = strpos($result[0][0],"{");

		$parse =  (substr($result[0][0],$pos,strlen($result[0][0])-$pos-1));

		$parse = str_replace(':,', ':0,', $parse);

		$parse = preg_replace('/([0-9a-zA-Z_]+)\s*\:\s*("|\[|[\-0-9]|{)/', '"\1":\2' , $parse);

		// il arrive qu'il y ait une virgule parasite entre un [ et un {
		$parse = preg_replace('/\[\s*(,)\s*{/','[{' ,$parse);

		$parse = str_replace('\\\'', '\'', $parse);
		$parse = preg_replace('/\s+/', ' ', $parse);


		//	$result  = json_decode(stripslashes($parse));
		//	corrigé par 
		$result  = json_decode($parse);
		//	à cause des \"

		//	Cas des hotels qui on un truc chelou pour les reservations
		if ($result == null) {
			$parse = preg_replace('/([0-9a-zA-Z_]+)\s*\:\s*\'([0-9a-zA-Z_]+)\'/', '"\1":"\2"', $parse);
			$result = json_decode($parse);
		}
		//	Fin hotels

		return $result;
	} else {
		return false;
	}
}

	/**
	 * getSiteList
	 * 
	 * @param mixed $contenu     contenu à lire.
	 * @param mixed $contentFile fichier cache de la liste des url.
	 * @param mixed $contentUrl  fichier cache de l'url.
	 *
	 * @access public
	 *
	 * @return void.
	 */
function getSiteList($contenu,$contentFile,$contentUrl) {
	preg_match_all('/<ul class="liste4colonnes">(.*)<\/ul>/s', $contenu, $results);
	if (isset($results[1][0])) {
		if (!is_file($contentFile)) {
			file_put_contents($contentFile, $results[1][0]);
		}
		if (!is_file($contentUrl)) {
			$urls = array();
			preg_match_all('/href="(.*)"\s/', $results[1][0], $items);
			$string = "";
			foreach ($items[1] as $value) {
				$string.=$value."\n";
			}
			file_put_contents($contentUrl, $string);
		}
	}
}

	/**
	 * extractData : extrait les données dans un fichier
	 * 
	 * @param mixed $contentUrl fichier de l'url à charger.
	 *
	 * @access public
	 *
	 * @return void.
	 */
function extractData($contentUrl){
	$handle = fopen($contentUrl, 'r');
	if ($handle)
	{
		while (!feof($handle))
		{
			$line			= fgets($handle);
			$line = trim($line);
			if (!empty($line)) {
				//echo 'lit : '.$line."\n";
				$contentSite	= '/var/www/mespj/cards/'.trim(substr(str_replace('/', '_', $line), 7));
				$failSite		= '/var/www/mespj/__failcards/'.trim(substr(str_replace('/', '_', $line), 7));
				if (!is_file($contentSite)) {
					// certains sites sont réferencés avec www.pro.pagesjaunes.fr
					if (preg_match('/www.pro.pagesjaunes/', $line)) {
						echo 'match'."\n";
					}
					$correctUrl = str_replace("www.pro.pagesjaunes", "pro.pagesjaunes", $line);
					$content	= url_get_contents(trim($correctUrl),10);
					$result		= getCard($content);
					if (isset($result->url)) {
						echo "\tOK   : ".$line."\n";
						file_put_contents($contentSite, serialize($result));
						if (is_file($failSite)) {
							unlink($failSite);
						}
					} else {
						echo "\tfail : ".$line."\n";
						file_put_contents($failSite, $content);
					}

				}
			}
		}
		fclose($handle);
	}
}

function leadzero($string,$size) {
	return str_pad($string,$size,'0', STR_PAD_LEFT);
}

function getPj($content) {
	preg_match_all('/<area shape="poly" href="([a-z\.\?=0-9]*)"\s/', $content, $pj);
	return isset($pj[1])?$pj[1]:false;
}

function getAnnuaire($content) {
	preg_match('/pages_dir=(.*)">/', $content, $pj);
	return isset($pj[1])?$pj[1]:false;
}

function getPages($annuaire) {
	$url = 'http://mesannuaires.pagesjaunes.fr/fsi/server?type=list&source='.$annuaire.'&tpl=catalog%5Flist';
	$xmlContent = url_get_contents($url);
	$nodes = simplexml_load_string($xmlContent);
	$pages = array();
	foreach ($nodes->images as $key => $value) {
		foreach ($value as $k => $v) {
			$pages[]= (string)$v['file'];
		}
	}
	return $pages;
}

function checkPath($path) {
	if (!is_dir($path)) {
		mkdir($path,0777,true);
	}
}
	/*
	pour l'image :
	http://mesannuaires.pagesjaunes.fr/fsi/server?type=image&width=1505&height=2435&rect=0,0,1,1&profile=fsi&source={noeud image}

	pour l'XML : 
	http://mesannuaires.pagesjaunes.fr/fsi/server?source={noeud image}&type=info&tpl=catalog_page
	*/
function getFileFromPJ($page,$dept) {
	checkPath('jpg/'.$dept);
	checkPath('xml/'.$dept);
	checkPath('encart/'.$dept);
	$lastpos	= strrpos( $page,"%2F");
	$filename	= substr($page, $lastpos+3);
	$urljpg		= 'http://mesannuaires.pagesjaunes.fr/fsi/server?type=image&width=1505&height=2435&rect=0,0,1,1&profile=fsi&source='.$page;
	$urlxml		= 'http://mesannuaires.pagesjaunes.fr/fsi/server?source='.$page.'&type=info&tpl=catalog_page';
	$filejpg	= 'jpg/'.$dept.'/'.str_replace('.tif', '.jpg', $filename);
	$filexml	= 'xml/'.$dept.'/'.str_replace('.tif', '.xml', $filename);
	if (!is_file($filejpg)) {
		file_put_contents($filejpg, url_get_contents($urljpg));
	}
	if (!is_file($filexml)) {
		file_put_contents($filexml, url_get_contents($urlxml));
	}
	return $filexml;
}

function cutImageOld($dept) {
	if($dossier = opendir('xml/'.$dept.'/')) {
		while(false !== ($fichier = readdir($dossier))) {
			if ($fichier{0}!='.') {
				$images = _cutImage('xml/'.$dept.'/'.$fichier,$dept);
				insereDB($images);
			}
		}	
		closedir($dossier);
	}

}

function cutImage($xmlfile,$dept) {
	$images = array();
	echo "\n".$xmlfile."\n";
	$pos		= strrpos($xmlfile, '/');
	$filename	= substr($xmlfile,$pos+1);
	$xmlcontent	= simplexml_load_file($xmlfile);
	$jpgfile	= str_replace('xml', 'jpg', $xmlfile);
	if (!isset($xmlcontent->Pages->page->links->area)) {
		return;
	}
	//$width = $xmlcontent->image->Width['value'];
	//$height = $xmlcontent->image->Height['value'];
	$size = getimagesize($jpgfile);
    $width = $size[0];
    $height = $size[1];
	$i = 0;
	foreach ($xmlcontent->Pages->page->links->area as $key => $value) {
		$adnumber = $value['url'];
		$adnumber = str_replace('#adpopup:', '', $adnumber);
		$adnumber = str_replace(',', '-', $adnumber);
		$destfilename = str_replace('.xml', '.'.$adnumber.'.jpg', $filename);

		$coords = _getCoords($value->shape['coords'],$width,$height);

		if (!in_array($coords['format'],array('Vignettes')) ) {
			echo "\t".$key."\t".$filename."\t".$value->shape['coords']."\n";
			echo "\t\t".$coords['size']." : ".$coords['format']."\n";

			$src = imagecreatefromjpeg($jpgfile);
			$dest = imagecreatetruecolor($coords['maxX']-$coords['minX'], $coords['maxY']-$coords['minY']);

			// Copy
			imagecopy($dest, $src, 0, 0, $coords['minX'], $coords['minY'], $coords['maxX']-$coords['minX'], $coords['maxY']-$coords['minY']);

			$destpathname = 'encart/'.$dept.'/'.$coords['format'].'/';
			checkPath($destpathname);
			$destfile = $destpathname.$destfilename;

			imagejpeg($dest,$destfile);
			$codePostal = _OCR($destpathname,$destfilename);
			$codePostal	= (empty($codePostal)?"inconnu":$codePostal);
			echo "\t\tcp : ".$codePostal."\n";
			$page		= _extractPage($filename);
			$images[]	= array(
				'dept'			=> $dept,
				'filename'		=> $destfilename,
				'page'			=> $page,
				'path'			=> $destpathname.$codePostal.'/',
				'codepostal'	=> $codePostal,
				'format'		=> $coords['format'],
				'size'			=> $coords['size']
			);
			$i++;
		}
	}
//	$xmlcontent->image->Path['value'];
	return $images;
}

function _extractPage($filename) {
	if ($filename{0}==='p') {
		return substr($filename,4,4);
	} else {
		$pos = strpos($filename, '_',8);
		return substr($filename, 7,$pos-7);
	}
}
function _getCoords($values,$maxwidth,$maxheight) {
	$v = explode(',', $values);
	$coords = array(
		'minX'	=>$maxwidth,
		'maxX'	=>0,
		'minY'	=>$maxheight,
		'maxY'	=>0
		);
	foreach ($v as $k=>$percent) {
		if (($k%2)==0) {
			$coords['minX']	= min(array($coords['minX'],(int)($percent*$maxwidth)));
			$coords['maxX']	= max(array($coords['maxX'],(int)($percent*$maxwidth)));
		} else {
			$coords['minY']	= min(array($coords['minY'],(int)($percent*$maxheight)));
			$coords['maxY']	= max(array($coords['maxY'],(int)($percent*$maxheight)));
		}
	}
	$coords['format']	= _detectFormat($coords);
	$coords['size']		= _getSize($coords);
	return $coords;

}

/*
J6CQ	= 76x133    : 673 x 1050
J4VCQ	= 76x104    : 673 x 821
J3CQ	= 76x76     : 673 x 600
J2CQ	= 76x50     : 673 x 395
J12CQ	= 155x133   : 1373 x 1050
J1CQ	= 155x277.5 : 1370 x 2192

D'apres les test, 76mm => 325px -> 1mm = 4.276 px env.

Si le plus grand format est le pleine page, alors
155mm   => 1373px  -> 1mm = 8.858px
277.5mm => 2192px  -> 1mm = 8.775px
*/
function _getSize($coords) {
	$width	= (int)(_getWidth($coords) / 8.858);
	$height	= (int)(_getHeight($coords) / 7.900);
//	return $width."("._getWidth($coords).")x".$height."("._getHeight($coords).")";
	return $width."x".$height;
}
function _getWidth($coords) {
	return $coords['maxX'] - $coords['minX'];
}
function _getHeight($coords) {
	return $coords['maxY'] - $coords['minY'];
}

function _detectFormat($coords) {
	$width	= _getWidth($coords);
	$height	= _getHeight($coords);
	$format = 'HORSGABARIT';
	if ($width<600) {
		if ($height>1100) {
			$format = 'SIDE';
		} else {
			$format = 'Vignettes';
		}
	}
	if (($width>599)&&($width<1200)) {
		if ($height<1600) {
			$format = 'BPA';
		}
		if ($height<1100) {
			$format = 'J6CQ';
		} 
		if ($height<825) {
			$format = 'J4VCQ';
		} 
		if ($height<605) {
			$format = 'J3CQ';
		} 
		if ($height<390) {
			$format = 'J2CQ';
		}
	}
	if ($width>1199) {
		if ($height>1100) {
			$format = 'J1CQ';
		} 
		if ($height<1100) {
			$format = 'J12CQ';
		} 
	}
	return $format;
}

function _checkCP($text) {
	preg_match('/([^0-9])([0-9OI]{5}\s)([a-zA-Z])/', $text,$results);
	if (!isset($results[2])) {
		$text2 = preg_replace('/\s+/',' ', $text);
		preg_match('/([^0-9])([0-9OI]{5} )([a-zA-Z])/', $text2,$results);
		if (isset($results[2])) {
			echo $results[2];
		}
	}
	if (!isset($results[2])) {
		preg_match('/([^0-9])([0-9OI]{5})([^0-9])/', $text,$results);
	}
	return $results;
}
function _getCP($file,$txtfile) {
	$traitement = array(
		'',
		'-depth 1',
		'-resize 200% -depth 3',
		'-colorspace gray -resize 200% -depth 3',
		'-negate -depth 1', // good
		'-depth 4',

		'-channel B -separate -depth 3',

		'-channel R -separate -depth 3',

		'-negate -resize 200% -depth 3', //good

		'-channel G -separate -depth 3',
		'-negate -channel R -separate -depth 3',
		'-negate -channel B -separate -depth 3',
		'-negate -channel G -separate -depth 3',
	);
	foreach ($traitement as $channel) {
		if (empty($channel)) {
			exec('gocr -i '.$file.' -o '.$txtfile);
		} else {
			exec('convert '.$file.' '.$channel.'  channel.png');
			exec('gocr -i channel.png -o '.$txtfile);
		}
		$return = file_get_contents($txtfile);
		$results = _checkCP($return);
		if ( (isset($results[2])) && (trim($results[2])!='00000') ) {
			echo (empty($channel))?'':"\t\t\t\t".$channel."\n";//\t".trim($results[2])."\n";
			//echo json_encode($results);
			return $results;
		}
	}
	unlink('channel.png');
	return array();
}

function _OCR($pathname,$filename) {
	$txtfile = $pathname.$filename.'.txt';
	$results = _getCP($pathname.$filename,$txtfile);

	/*
		S'il n'y a aucun résultats, on essaie avec une autre couche
	*/
	if (!isset($results[2])) {
		$destpath = $pathname.'inconnu/';
		checkPath($destpath);
		rename($pathname.$filename, $pathname.'inconnu/'.$filename);
		rename($txtfile, $pathname.'inconnu/'.$filename.'.txt');
		return false;

	} else {
		$resultat = str_replace(array('O','I',' '), array('0','1',''), trim($results[2]));
		$destpath = $pathname.$resultat.'/';
		checkPath($destpath);
		rename($pathname.$filename, $pathname.$resultat.'/'.$filename);
		rename($txtfile, $pathname.$resultat.'/'.$filename.'.txt');
		return $resultat;
	}
}

function insereDB($images,$connection) {
	if (!empty($images)) {
		$insert = $connection->prepare('INSERT INTO encarts (dept,filename,page,path,codepostal,format,size)
			VALUES(
				:dept, :filename, :page,:path,:codepostal, :format, :size
				)');
		foreach ($images as $image) {
			$success = $insert->execute($image);
		}
	}
}
