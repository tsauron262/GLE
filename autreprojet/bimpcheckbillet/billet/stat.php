<?php


require("init.php");




$sql2 = "SELECT SUM(`product_quantity`) as nb FROM `ps_order_detail` pso, ps_orders ps WHERE `product_id` IN (16 ) AND pso.`id_order` = ps.`id_order` AND valid = 1";
$result2 = $conn->query($sql2);
$dataSql2 = $result2->fetch_assoc();

$nb1 = $dataSql2['nb'];


$sql2 = "SELECT SUM(`product_quantity`) as nb FROM `ps_order_detail` pso, ps_orders ps WHERE `product_id` IN (15 ) AND pso.`id_order` = ps.`id_order` AND valid = 1";
$result2 = $conn->query($sql2);
$dataSql2 = $result2->fetch_assoc();

$nb2 = $dataSql2['nb'];


$sql2 = "SELECT SUM(`product_quantity`) as nb FROM `ps_order_detail` pso, ps_orders ps WHERE `product_id` IN (14 ) AND pso.`id_order` = ps.`id_order` AND valid = 1";
$result2 = $conn->query($sql2);
$dataSql2 = $result2->fetch_assoc();

$nb3= $dataSql2['nb'];


$sql2 = "SELECT SUM(`product_quantity`) as nb FROM `ps_order_detail` pso, ps_orders ps WHERE `product_id` IN (13 ) AND pso.`id_order` = ps.`id_order` AND valid = 1";
$result2 = $conn->query($sql2);
$dataSql2 = $result2->fetch_assoc();

$nb4 = $dataSql2['nb'];


$sql2 = "SELECT SUM(`product_quantity`) as nb FROM `ps_order_detail` pso, ps_orders ps WHERE `product_id` IN (13,15,16 ) AND pso.`id_order` = ps.`id_order` AND valid = 1";
$result2 = $conn->query($sql2);
$dataSql2 = $result2->fetch_assoc();

$nbVend = $dataSql2['nb'];





$sql2 = "SELECT SUM(`product_quantity`) as nb FROM `ps_order_detail` pso, ps_orders ps WHERE `product_id` IN (14,15,16 ) AND pso.`id_order` = ps.`id_order` AND valid = 1";
$result2 = $conn->query($sql2);
$dataSql2 = $result2->fetch_assoc();

$nbSam = $dataSql2['nb'];


$sql2 = "SELECT COUNT(code) as nb
FROM `ps_tickets` 

WHERE `id_order_details` IN (SELECT `id_order_detail` as nb FROM `ps_order_detail` pso, ps_orders ps WHERE `product_id` IN (13,14,15,16 ) AND pso.`id_order` = ps.`id_order` AND valid = 1)";
$result2 = $conn->query($sql2);
$dataSql2 = $result2->fetch_assoc();

$tele = $dataSql2['nb'];


$sql2 = "SELECT COUNT(c.reference) as nb
FROM  ps_order_detail cd, ps_orders c, ps_customer cli WHERE cli.id_customer = c.id_customer AND c.id_order = cd.id_order AND cd.id_order_detail NOT IN (SELECT id_order_details FROM ps_tickets)  AND valid = 1 AND product_id IN (13,14,15,16) ORDER BY cli.lastname, cli.firstname;";
$result2 = $conn->query($sql2);
$dataSql2 = $result2->fetch_assoc();

$tele2 = $dataSql2['nb'];









$sql2 = "SELECT SUM(`product_quantity`) as nb, COUNT(DISTINCT ps.id_order) as nb1 FROM `ps_order_detail` pso, ps_orders ps WHERE `product_id` IN (13,14,15,16 ) AND pso.`id_order` = ps.`id_order` AND current_state = 1";
$result2 = $conn->query($sql2);
$dataSql2 = $result2->fetch_assoc();

$nbPlCheque = $dataSql2['nb'];
$nbCheque = $dataSql2['nb1'];



$sql2 = "SELECT SUM(`product_quantity`) as nb, COUNT(DISTINCT ps.id_order) as nb1 FROM `ps_order_detail` pso, ps_orders ps WHERE `product_id` IN (13,14,15,16 ) AND pso.`id_order` = ps.`id_order` AND current_state = 10";
$result2 = $conn->query($sql2);
$dataSql2 = $result2->fetch_assoc();

$nbPlCheque2 = $dataSql2['nb'];
$nbCheque2 = $dataSql2['nb1'];



$sql2 = "SELECT SUM(`product_quantity`) as nb FROM `ps_order_detail` pso, ps_orders ps WHERE `product_id` IN (13,15,16 ) AND pso.`id_order` = ps.`id_order` AND valid = 1 AND id_customer = 379";
$result2 = $conn->query($sql2);
$dataSql2 = $result2->fetch_assoc();

$nbInvitV = $dataSql2['nb'];


$sql2 = "SELECT SUM(`product_quantity`) as nb FROM `ps_order_detail` pso, ps_orders ps WHERE `product_id` IN (14,15,16 ) AND pso.`id_order` = ps.`id_order` AND valid = 1 AND id_customer = 379";
$result2 = $conn->query($sql2);
$dataSql2 = $result2->fetch_assoc();

$nbInvitS = $dataSql2['nb'];







echo "Nombre de personne confirmées Vendredi : ".$nbVend;

echo "<br/><br/>Nombre de personne confirmées Samedi : ".$nbSam;

echo "<br/><br/>Total : ".($nbSam+$nbVend);

echo "<br/><br/>Nombre places téléchargées : ".$tele;

echo "<br/><br/>Nombre de commande non téléchargées : ".$tele2." (une commande peut contenir plusieurs places)";

echo "<br/><br/>Nombre places vendredi : ".$nb4;
echo "<br/><br/>Nombre places samedi : ".$nb3;
echo "<br/><br/>Nombre places weekend : ".$nb2;
echo "<br/><br/>Nombre places tarifs réduit : ".$nb1;

echo "<br/><br/>Nombre places attente chéque : ".$nbPlCheque. " sur ".$nbCheque." chéques";

echo "<br/><br/>Nombre places attente virement : ".$nbPlCheque2. " sur ".$nbCheque2." virements";

echo "<br/><br/>Nombre d'invitations vendredi : ".$nbInvitV;

echo "<br/><br/>Nombre d'invitations samedi : ".$nbInvitS;





if(isset($_REQUEST['envoieMail'])){
	echo "<h1>Billet NON Télécharger</h1>";


	$sql2 = 'SELECT cli.firstname, cli.lastname , cli.email, c.reference, cd.product_name, c.id_order, c.valid, c.current_state, cd.product_id, cd.product_quantity 
	, concat("http://sucsenscene.fr/billets/telechargement.php?cm=", c.reference) as lien
	FROM  ps_order_detail cd, ps_orders c, ps_customer cli WHERE cli.id_customer = c.id_customer AND c.id_order = cd.id_order AND cd.id_order_detail NOT IN (SELECT id_order_details FROM ps_tickets)  AND valid = 1 AND product_id IN (13,14,15,16) ORDER BY cli.lastname, cli.firstname;';
	$result2 = $conn->query($sql2);
	echo "<pre>";
	while($dataSql2 = $result2->fetch_assoc()){
		mail($dataSql2['email']/*"tommy@drsi.fr"*/, "ATTENTION Place(s) Sucs en Scène", utf8_decode("Bonjour ".utf8_encode($dataSql2['lastname']." ".$dataSql2['firstname'])." 

 Tout d'abord merci pour votre commande de place(s) pour le festival Sucs En Scène 4 les 25 et 26 Août 2017.

 Cependant sauf erreur de notre part vous n'avez a ce jour pas téléchargé vos billets.

 Pour info, après validation de votre commande, vous avez reçu un mail via sucsenscene.fr avec le lien de téléchargement des billets.

Dans le doute voici a nouveau votre lien de téléchargement :
".$dataSql2['lien']."


Merci beaucoup et a ce Week-end "), 'From: Site Sucs en Scene<gestion@sucsenscene.fr>');
		print_r($dataSql2);//die;
	}
}




if(isset($_REQUEST['envoieMail2'])){
	echo "<h1>Billet Télécharger</h1>";

	$sql2 = 'SELECT t.*, cli.firstname, cli.lastname , cli.email, c.reference, cd.product_name, c.id_order, c.valid, c.current_state, cd.product_id

FROM ps_tickets t, ps_order_detail cd, ps_orders c, ps_customer cli 

WHERE cli.id_customer = c.id_customer AND c.id_order = cd.id_order AND cd.id_order_detail = t.id_order_details

ORDER BY cli.lastname, cli.firstname;';
	$result2 = $conn->query($sql2);
	echo "<pre>";
	while($dataSql2 = $result2->fetch_assoc()){
		print_r($dataSql2);
	}
}
