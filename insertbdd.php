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
	$data = unserialize(file_get_contents('/var/www/mespj/cards/'.$fichier));
	/* il semblerait que certaines url étaient en IPV6,
		on recharge les données dans ce cas. */	
	if (!preg_match('/\./', $data->url)) {
		$content = url_get_contents('http://'.$url);
		if ($datatmp = getCard($content)) {
			$data = $datatmp;
		}
	}
	/*********************************************/
			print_r($data);
			die('37');

	$insert = $connection->prepare('INSERT INTO
		prospects (pvi_id,url,url2,nom,tel,email,cp,ville,adr1,adr2,adr3,tel2)
		VALUES(
			:pvi_id, :url,:url2,:nom, :tel, :email, :cp, :ville, :adr1, :adr2, :adr3, :tel2 )');

	try {
		$success = $insert->execute(
			array(
				'pvi_id'	=> $data->oda,
				'url'		=> 'http://'.trim($url),
				'url2'		=> $data->url,
				'nom'		=> $data->name,
				'tel'		=> $data->tel1,
				'email'		=> $data->email,
				'cp'		=> $data->cp,
				'ville'		=> $data->ville,
				'adr1'		=> $data->adr1,
				'adr2'		=> $data->adr2,
				'adr3'		=> $data->adr3,
				'tel2'		=> $data->tel2,
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
			die('fin');
		}
	}	
	closedir($dossier);
}
