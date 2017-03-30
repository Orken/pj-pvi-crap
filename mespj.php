<?php

/**
	Page du lien vers un annuaire pour un département
	http://mesannuaires.pagesjaunes.fr/zonegeo.php?type=pja&dpt={dept sur 3 digit ex :031}

	donne un lien vers 'pj.php' le récuperer entre '<area shape="poly" href="' et '"'

	http://mesannuaires.pagesjaunes.fr/{lien recupré ci dessus}

	dans cette page recuperer ce qu'il y a entre 'pages_dir=' et '">'

	c'est ce qu'il faut mettre entre parenthese sur le lien ci dessous

	Liste des pages de l'annuaire
	http://mesannuaires.pagesjaunes.fr/fsi/server?type=list&source={lien ci dessus}&tpl=catalog%5Flist

	on récupere un fichier xml qui liste toutes les pages de l'annuaire

	C'est l'attribut file du noeud image qu'il faut mettre dans les lien ci dessous

    pour l'image:
    http://mesannuaires.pagesjaunes.fr/fsi/server?type=image&width=1505&height=2435&rect=0,0,1,1&profile    = fsi&source={noeud image}

    pour l'XML:
    http://mesannuaires.pagesjaunes.fr/fsi/server?source={noeud image}&type=info&tpl                        = catalog_page

	Un fois qu'on a les images et les XML, il ne reste plus qu'a les découper et les OCRizer pour récuperer les données.

**/

include 'functions.php';
$dept		= $argv[1];
$dept3digit	= leadzero($dept,3);
$cachepath	= 'cache/'.$dept.'/';
checkPath($cachepath);
$url = 'http://mesannuaires.pagesjaunes.fr/ouvrage.php?dpt='.$dept3digit;


$cachefilename	= $cachepath.'ouvrage'.$dept3digit.'.htm';
$content		= get_contents($cachefilename,$url);
$pjs = array();

if (preg_match('/pj\.php\?code=([0-9]{3})/', $content,$result)) {
	$pjs[] = $result[0];
} else {
	$url = 	'http://mesannuaires.pagesjaunes.fr/zonegeo.php?type=pja&dpt='.$dept3digit;
	$cachefilename = $cachepath.'pj'.$dept3digit.'.htm';

	$content = get_contents($cachefilename,$url);
	if (!$pjs = getPj($content)) {
		echo "Etape 1 : Récupération du lien local impossible.\n";
		echo $url."\n";
		exit;
	}
}
foreach ($pjs as $pj) {
	echo '-- '.$pj.' --'."\n";
	$url = 'http://mesannuaires.pagesjaunes.fr/'.$pj;
	$cachefilename = $cachepath.preg_replace('/[^a-zA-Z0-9\.]/', '.', $pj);
	$content = get_contents($cachefilename,$url);
print_r($content);print_r($url);die;
	
	if (!$annuaire=getAnnuaire($content)) {
		echo "Etape 2 : Récupération de l'annuaire impossible.";
		exit;
	}
	echo '-- '.$annuaire.' --'."\n";
	
	$pages = getPages($annuaire);
	if (sizeof($pages)<1) {
		echo "Etape 3 : Récupération de la liste des pages impossibru.";
		exit;
	}
	echo "Récupération en cours des encarts.\n";
	try{
		$dns = 'mysql:host=localhost;dbname=';
		$utilisateur	= '';
		$motDePasse		= '';
		$options		= array(PDO::MYSQL_ATTR_INIT_COMMAND=> "SET NAMES utf8");
		$connection		= new PDO( $dns, $utilisateur, $motDePasse );
	} catch (Exception $e) {
		echo "Connexion impossibru ! ". $e->getMessage();
		die();
	}

	foreach ($pages as $value) {
		echo '.';
		$xmlfile	= getFileFromPJ($value,$dept);
		$images		= cutImage($xmlfile,$dept);
		insereDb($images,$connection);
	}
}

echo "\nFin\n";
