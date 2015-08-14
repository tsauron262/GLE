<?php

include 'gsxlib/gsxlib.php';
//  $_ENV['GSX_CERT'] = '/path/to/gsx/client/cert.pem';
//  $_ENV['GSX_KEYPASS'] = 'MySuperSecretPrivateKeyPassPhrase';


        $_ENV['GSX_CERT'] = '/etc/apache2/ssl/Applecare-APP157-0000897316.Test.apple.com.chain.pem';
        $_ENV['GSX_KEYPASS'] = 'freeparty';
  $gsx = GsxLib::getInstance('0000897316', 'contact@drsi.fr');
  $info = $gsx->warrantyStatus('C2QMDSAVFH00');
  echo $info->productDescription;



