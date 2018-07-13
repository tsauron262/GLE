<?php

require("init.php");

$codeBill = $_REQUEST['num'];

if(stripos($codeBill,"-") === false)
	$codeBill = substr($codeBill, 0, 12);
$color = "green";

$champDate = "S";

$tabProdJ = array("V" => array(15,16, 13),
		"S" => array(15,16, 14));


if(isset($_REQUEST['modeReset'])){
		$sql = "UPDATE `".PRESTA_PREF."tickets` SET `dateEntreeV` = '0000-00-00', `dateEntreeS` = '0000-00-00';";

		$result = $conn->query($sql);
		die("RESET OK");
}



$sql2 = "SELECT t.*, cli.firstname, cli.lastname , cli.email, c.reference, cd.product_name, 
c.id_order, c.valid, c.current_state, cd.product_id, t.code, t.codeBar

FROM ".PRESTA_PREF."tickets t, ".PRESTA_PREF."order_detail cd, ".PRESTA_PREF."orders c, ".PRESTA_PREF."customer cli

WHERE cli.id_customer = c.id_customer AND c.id_order = cd.id_order AND cd.id_order_detail = t.id_order_details

AND  (t.code = '".$codeBill."' || t.codeBar LIKE '".$codeBill."')

ORDER BY cli.lastname, cli.firstname;";


$result2 = $conn->query($sql2);

if ($fermer && !$modeTest) {
	header("Location: /");
}

if ($result2->num_rows < 1){
		$color = "red";
		$html = "Numéro inexistant ".$codeBill;
}
else{
	$html = "";
	$dataSql2 = $result2->fetch_assoc();



echo '
<style>
body{
	text-align: center;
	font-size: 38px; 
}

input{
	height: 60px;
	margin: 40px;
	font-size: 40px;
}


</style>


';


		echo "<br/>";
		echo utf8_encode("<h2>".$dataSql2['firstname']." ".$dataSql2['lastname']."</h2>");
		echo utf8_encode("<h3>".$dataSql2['product_name']."</h3>");
		echo $dataSql2['code'] . "  |   ";
		echo $dataSql2['codeBar']  ."</br></br>";

	if(!in_array($dataSql2["product_id"], $tabProdJ[$champDate])){
		$color = "red";
		$html = "Pas de place pour ".$champDate;
	}
	elseif($dataSql2["dateEntree".$champDate] < "2017-01-01"){

		$sql = "UPDATE `".PRESTA_PREF."tickets` SET `dateEntree".$champDate."` = now() WHERE `code` = '".$codeBill."';";

		$result = $conn->query($sql);
	}
	else{
		$color = "red";
		$html = "Déja rentré le :".$dataSql2["dateEntree".$champDate];
	}

}

	echo "<body style='background-color:".$color."'>";
	echo $html;

	echo '</br></br><form><input type="text" name="num" value=""><input type="hidden" name="mode" value="'.$modeTest.'"><input type="submit"/></form>';

?>
