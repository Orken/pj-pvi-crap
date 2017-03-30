<?php 
header("Content-Type: plain/text"); 
define('JMP',"\n");
define('DBLJMP',"\n\n");

function ecrit($label,$string){
	echo $label."\t: ".$string.JMP;
}

echo JMP.'--> '.$argv[1]."<--".DBLJMP;

$data = unserialize(file_get_contents($argv[1]));
print_r($data);

ecrit('Nom',$data->corporate_name);
ecrit('Tel',$data->tel);
ecrit('email',$data->email);
ecrit('Où?',$data->cp.' '.$data->loc);
if (isset($data->geoCoordonnees)) {
	$geo = $data->geoCoordonnees[0];
	echo JMP;
	ecrit('Adresse',$geo->lab);
	ecrit("",$geo->adr1);
	ecrit("",$geo->adr2);
}