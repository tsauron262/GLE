<?php 

	/**
	 * This example mimics the iOS device activation details in GSX
	 */
require '../../main.inc.php';


//        $this->cert_path = '/etc/apache2/ssl/certUt.pem';
//        $this->cert_pass = 'freeparty';
        
        
        $_ENV['GSX_CERT'] = '/etc/apache2/ssl/certUt.pem';
        $_ENV['GSX_KEYPASS'] = 'freeparty';


	require '../gsxlib.php';

	$sold_to = '';
	$username = '';
	$password = '';
	$serialnumber = '';
	$gsx = GsxLib::getInstance('0000897316', 'contact@drsi.fr', 'ut');
echo "lllll";
	$info = $gsx->fetchiOsActivation($serialnumber);

 ?>

 <table>
 	<tr>
 		<td><strong>Serial Number:</strong> <?= $info->serialNumber ?></td>
 		<td><strong>Initial Activation Policy ID:</strong> <?= $info->initialActivationPolicyDetails ?></td>
 	</tr>
 	<tr>
 		<td><strong>MEID:</strong> <?= $info->meid ?></td>
 		<td><strong>Activation Policy Description:</strong> <?= $info->appliedActivationDetails ?></td>
 	</tr>
 	<tr>
 		<td><strong>IMEI:</strong> <?= $info->imeiNumber ?></td>
 		<td><strong>Applied Activation Policy ID:</strong> <?= $info->appliedActivationPolicyID ?></td>
 	</tr>
 	<tr>
 		<td><strong>Part Description:</strong> <?= $info->partDescription ?></td>
 		<td><strong>Applied Activation Description:</strong> <?= $info->appliedActivationDetails ?></td>
 	</tr>
 	<tr>
 		<td><strong>Product Version:</strong> <?= $info->productVersion ?></td>
 		<td><strong>Next Tether Policy ID:</strong> <?= $info->nextTetherPolicyID ?></td>
 	</tr>
 	<tr>
 		<td><strong>Last Restore Date:</strong> </td>
 		<td><strong>Next Tether Activation Policy Description:</strong> <?= $info->nextTetherPolicyDetails ?></td>
 	</tr>
 	<tr>
 		<td><strong>Bluetooth MAC address:</strong> <?= $info->bluetoothMacAddress ?></td>
 		<td><strong>First Unbrick Date:</strong> <?= $info->firstUnbrickDate ?></td>
 	</tr>
 	<tr>
 		<td><strong>MAC address:</strong> <?= $info->macAddress ?></td>
 		<td><strong>ICCID:</strong> <?= $info->iccID ?></td>
 	</tr>
 	<tr>
 		<td><strong>Last Unbrick Date:</strong> <?= $info->lastUnbrickDate ?></td>
 		<td><strong>Unbricked:</strong> <?= $info->unbricked ?></td>
 	</tr>
 	<tr>
 		<td><strong>Unlocked:</strong> <?= $info->unlocked ?></td>
 		<td></td>
 	</tr>
 </table>
