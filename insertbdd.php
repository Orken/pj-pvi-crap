<?php 
include('functions.php');

    /**
     * removedblspc : enlève tous les double espace blanc.
     * 
     * @param mixed $string Chaine de caractere a nettoyer.
     *
     * @access public
     *
     * @return string Chaine nettoyée.
     */
function removedblspc($string) {
	return preg_replace('/\s+/', ' ', $string);
}

    /**
     * insertData
     * 
     * @param mixed $fichier    fichier des données.
     * @param mixed $connection connexion à la base de données.
     *
     * @access public
     *
     * @return void
     */
function insertData($fichier,$connection) {
	$url = str_replace('www.pro.pagesjaunes', 'pro.pagesjaunes', $fichier);
	$url = str_replace('_', '/', $url);
	$data = unserialize(file_get_contents('/var/www/pj/cards/'.$fichier));
	/* il semblerait que certaines url étaient en IPV6,
		on recharge les données dans ce cas. */	
	if (!preg_match('/\./', $data->url)) {
		$content = url_get_contents('http://'.$url);
		if ($datatmp = getCard($content)) {
			$data = $datatmp;
		}
	}
	/*********************************************/

	$insert = $connection->prepare('INSERT INTO
		prospects (pvi_id,url,url2,nom,tel,email,cp,ville,adr1,adr2,adr3,tel2)
		VALUES(
			:pvi_id, :url,:url2,:nom, :tel, :email, :cp, :ville, :adr1, :adr2, :adr3, :tel2 )');
	if (isset($data->geoCoordonnees)) {
		$geo = $data->geoCoordonnees[0];
		if (isset($geo->lab)) {
			$adr1	= trim($geo->lab);
			$adr2	= trim($geo->adr1);
			$adr3	= trim($geo->adr2);
			$tel2	= trim($geo->tel);
		} else if (isset($geo->default_address)) {
			$adr	= explode("-", removedblspc($geo->default_address),2);
			$adr1	= (isset($adr[1]))?trim($adr[0]):null;
			$adr2	= (isset($adr[1]))?trim($adr[1]):trim($adr[0]);
			$adr3	= $geo->default_city;
			$tel2	= '';
		} else {
			$print_r($geo);
			die;
		}

	} else {
		$adr1 = $adr2 = $adr3 = $tel2 = null;
	}
	try {
		$success = $insert->execute(
			array(
				'pvi_id'	=> utf8_decode(trim($data->pvi_id_oda)),
				'url'		=> 'http://'.trim($url),
				'url2'		=> trim($data->url),
				'nom'		=> utf8_decode(trim($data->corporate_name)),
				'tel'		=> utf8_decode(trim($data->tel)),
				'email'		=> utf8_decode(trim($data->email)),
				'cp'		=> utf8_decode(trim($data->cp)),
				'ville'		=> utf8_decode(trim($data->loc)),
				'adr1'		=> utf8_decode($adr1),
				'adr2'		=> utf8_decode($adr2),
				'adr3'		=> utf8_decode($adr3),
				'tel2'		=> utf8_decode($tel2),
				)
			);
		if( !$success ) {
			echo "Erreur\n";
			print_r($data);
			echo "\n\n\n";
		}
	} catch( Exception $e ){
		echo 'Erreur de requète : ', $e->getMessage();
	}
}


try{
	$dns = 'mysql:host=localhost;dbname=';
	$utilisateur = '';
	$motDePasse = '';
	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND=> "SET NAMES utf8");
	$connection = new PDO( $dns, $utilisateur, $motDePasse );
} catch (Exception $e) {
	echo "Connexion impossibru ! ". $e->getMessage();
	die();
}

if($dossier = opendir('/var/www/pj/cards')) {
	while(false !== ($fichier = readdir($dossier))) {
		if ($fichier{0}!='.') {
			insertData($fichier,$connection);
			//die;
		}
	}	
	closedir($dossier);
}
