<?php 
define('JUMP',"\n\n");

include('functions.php');

header("Content-Type: plain/text"); 

/**
	On scanne les pages puis on les parse
**/
$count = 0;
for ($i=1; $i<672 ; $i++) { 
	set_time_limit(0);
	if ($count>15) {
		break;
	}
	$contentFile = '/var/www/mespj/content/' . $i . '.txt';
	echo $contentFile;
	if (!is_file($contentFile)) {
		$adresse = ($i==1)?'https://www.pagesjaunes.fr/annonceurs/pack-visibilite-internet':'https://www.pagesjaunes.fr/annonceurs/pack-visibilite-internet/' . $i;
		$content = url_get_contents($adresse);
		$count++;
	} else {
		$content = file_get_contents($contentFile);
	}
	$contentUrl = '/var/www/mespj/url/' . $i . '.txt';
	if (!is_file($contentUrl)) {
		$sites = getSiteList($content,$contentFile,$contentUrl);
		extractData($contentUrl);
	} 
}

/**
	On repasse tous les fichiers précedents
	ça permet de recuperer certains site 
	qui aurait fait un timeout
**/
if($dossier = opendir('/var/www/mespj/url')) {
	while(false !== ($fichier = readdir($dossier))) {
		if ($fichier{0}!='.') {
			extractData('/var/www/mespj/url/'.$fichier);
		}
	}	
	closedir($dossier);
}

