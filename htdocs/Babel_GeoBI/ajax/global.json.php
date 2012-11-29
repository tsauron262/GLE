<?php

require_once('../../main.inc.php');


$requete = "SELECT count(*) as cnt, lat,lng, ".MAIN_DB_PREFIX."societe.nom,".MAIN_DB_PREFIX."societe.rowid FROM  ".MAIN_DB_PREFIX."societe, Babel_GeoBI
WHERE ".MAIN_DB_PREFIX."societe.rowid = Babel_GeoBI.`socid` AND lat is not null
GROUP BY ".MAIN_DB_PREFIX."societe.rowid";
$sql = $db->query($requete);
$iter = 0;
$arr=array();


while ($res = $db->fetch_object($sql))
{

    $requeteProp = "SELECT sum(total_ht) as t,
                           quarter(datep) as d,
                           count(total_ht) as c
                      FROM ".MAIN_DB_PREFIX."propal
                      WHERE fk_soc =".$res->rowid . "
                        AND year(datep) = ".date('Y').'
                   GROUP BY d';
    $sql1 = $db->query($requeteProp);
    $arrPropal = array();
    $totalPropal = 0;
    while ($res1 = $db->fetch_object($sql1))
    {
        $arrPropal[$res1->d]=round($res1->t);
        $arrPropal['count']=$res1->c;
        $arrPropal['somme']=$res1->t;
        $totalPropal += $res1->t;
    }

    $requeteProp = "SELECT sum(total_ht) as t,
                           quarter(date_commande) as d,
                           count(total_ht) as c
                      FROM ".MAIN_DB_PREFIX."commande
                      WHERE fk_soc =".$res->rowid . "
                        AND year(date_commande) = ".date('Y').'
                   GROUP BY d';
    $sql1 = $db->query($requeteProp);
    $arrCommande = array();
    $totalCommande = 0;
    while ($res1 = $db->fetch_object($sql1))
    {
        $arrCommande[$res1->d]=round($res1->t);
        $arrCommande['count']=$res1->c;
        $arrCommande['somme']=$res1->t;
        $totalCommande += $res1->t;
    }

    $requeteProp = "SELECT sum(total) as t,
                           quarter(datef) as d,
                           count(total_ht) as c
                      FROM ".MAIN_DB_PREFIX."facture
                      WHERE fk_soc =".$res->rowid . "
                        AND year(datef) = ".date('Y').'
                   GROUP BY d';
    $sql1 = $db->query($requeteProp);
    $arrFacture = array();
    $totalFacture = 0;
    while ($res1 = $db->fetch_object($sql1))
    {
        $arrFacture[$res1->d]=round($res1->t);
        $arrFacture['count']=$res1->c;
        $arrFacture['somme']=$res1->t;
        $totalFacture += $res1->t;
    }

$table = '<table class"canvasStats" style="display: none;" id="globalTable'.$res->rowid.'">
    <caption>2009 Employee Sales by Department</caption>
    <thead>
        <tr>
            <td></td>
            <th scope="col">1er Trim</th>
            <th scope="col">2eme Trim</th>
            <th scope="col">3eme Trim</th>
            <th scope="col">4eme Trim</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th scope="row">Propal.</th>
            <td>'.($arrPropal[1]>0?$arrPropal[1]:0).'</td>
            <td>'.($arrPropal[2]>0?$arrPropal[2]:0).'</td>
            <td>'.($arrPropal[3]>0?$arrPropal[3]:0).'</td>
            <td>'.($arrPropal[4]>0?$arrPropal[4]:0).'</td>
        </tr>
        <tr>
            <th scope="row">Comm.</th>
            <td>'.($arrCommande[1]>0?$arrCommande[1]:0).'</td>
            <td>'.($arrCommande[2]>0?$arrCommande[2]:0).'</td>
            <td>'.($arrCommande[3]>0?$arrCommande[3]:0).'</td>
            <td>'.($arrCommande[4]>0?$arrCommande[4]:0).'</td>
        </tr>
        <tr>
            <th scope="row">Fact.</th>
            <td>'.($arrFacture[1]>0?$arrFacture[1]:0).'</td>
            <td>'.($arrFacture[2]>0?$arrFacture[2]:0).'</td>
            <td>'.($arrFacture[3]>0?$arrFacture[3]:0).'</td>
            <td>'.($arrFacture[4]>0?$arrFacture[4]:0).'</td>
        </tr>
    </tbody>
</table>
';

    $dateStart = date('Y');



    $arr[]=array('socid'=>$res->rowid,
                 'cnt' => $res->cnt,
                 'lat' => $res->lat,
                 'lng' => $res->lng,
                 'nom' => utf8_encode($res->nom),
                 'stat' => $table,
                 "y0" => $dateStart,
                 'pc' => ($arrPropal['count']> 0?$arrPropal['count']:0),
                 'cc' => ($arrCommande['count']> 0?$arrCommande['count']:0),
                 'fc' => ($arrFacture['count']> 0?$arrFacture['count']:0),
                 'ps' => ($totalPropal> 0?price($totalPropal):0),
                 'cs' => ($totalCommande> 0?price($totalCommande):0),
                 'fs' => ($totalFacture> 0?price($totalFacture):0),
                 'total' => ($total> 0?price($total):0));
    $iter ++;
}

$jsonArr = array('count' => $iter, 'results' => $arr);
//
//$json = '{ "count": '.$iter.',
// "results": [
//{"photo_id": 27932, "photo_title": "Atardecer en Embalse", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/27932.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/27932.jpg", "longitude": -64.404945, "latitude": -32.202924, "width": 500, "height": 375, "upload_date": "25 June 2006", "owner_id": 4483, "owner_name": "Miguel Coranti", "owner_url": "http://www.panoramio.com/user/4483"},
//{"photo_id": 522084, "photo_title": "In Memoriam Antoine de Saint ExupÃ©ry", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/522084.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/522084.jpg", "longitude": 17.470493, "latitude": 47.867077, "width": 500, "height": 350, "upload_date": "21 January 2007", "owner_id": 109117, "owner_name": "Busa PÃ©ter", "owner_url": "http://www.panoramio.com/user/109117"},
//{"photo_id": 1578881, "photo_title": "Rosina Lamberti,Sunset,Templestowe , Victoria, Australia", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/1578881.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/1578881.jpg", "longitude": 145.141754, "latitude": -37.766372, "width": 500, "height": 474, "upload_date": "01 April 2007", "owner_id": 140796, "owner_name": "rosina lamberti", "owner_url": "http://www.panoramio.com/user/140796"},
//{"photo_id": 97671, "photo_title": "kin-dza-dza", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/97671.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/97671.jpg", "longitude": 30.785408, "latitude": 46.639301, "width": 500, "height": 375, "upload_date": "09 December 2006", "owner_id": 13058, "owner_name": "Kyryl", "owner_url": "http://www.panoramio.com/user/13058"},
//{"photo_id": 25514, "photo_title": "Arenal", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/25514.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/25514.jpg", "longitude": -84.693432, "latitude": 10.479372, "width": 500, "height": 375, "upload_date": "17 June 2006", "owner_id": 4112, "owner_name": "Roberto Garcia", "owner_url": "http://www.panoramio.com/user/4112"},
//{"photo_id": 57823, "photo_title": "Maria Alm", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/57823.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/57823.jpg", "longitude": 12.900009, "latitude": 47.409968, "width": 500, "height": 333, "upload_date": "05 October 2006", "owner_id": 8060, "owner_name": "Norbert MAIER", "owner_url": "http://www.panoramio.com/user/8060"},
//{"photo_id": 532693, "photo_title": "Wheatfield in afternoon light", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/532693.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/532693.jpg", "longitude": 11.272659, "latitude": 59.637472, "width": 500, "height": 333, "upload_date": "22 January 2007", "owner_id": 39160, "owner_name": "Snemann", "owner_url": "http://www.panoramio.com/user/39160"},
//{"photo_id": 57819, "photo_title": "Burg Hohenwerfen", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/57819.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/57819.jpg", "longitude": 13.189259, "latitude": 47.483221, "width": 500, "height": 333, "upload_date": "05 October 2006", "owner_id": 8060, "owner_name": "Norbert MAIER", "owner_url": "http://www.panoramio.com/user/8060"},
//{"photo_id": 1282387, "photo_title": "Thunderstorm in Martinique", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/1282387.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/1282387.jpg", "longitude": -61.013432, "latitude": 14.493688, "width": 500, "height": 400, "upload_date": "12 March 2007", "owner_id": 49870, "owner_name": "Jean-Michel Raggioli", "owner_url": "http://www.panoramio.com/user/49870"},
//{"photo_id": 945976, "photo_title": "Al tard", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/945976.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/945976.jpg", "longitude": 0.490866, "latitude": 40.903783, "width": 335, "height": 500, "upload_date": "21 February 2007", "owner_id": 3022, "owner_name": "Arcadi", "owner_url": "http://www.panoramio.com/user/3022"},
//{"photo_id": 73514, "photo_title": "Hintersee bei Ramsau", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/73514.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/73514.jpg", "longitude": 12.852459, "latitude": 47.609519, "width": 500, "height": 333, "upload_date": "30 October 2006", "owner_id": 8060, "owner_name": "Norbert MAIER", "owner_url": "http://www.panoramio.com/user/8060"},
//{"photo_id": 298967, "photo_title": "Antelope Canyon, Ray of Light", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/298967.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/298967.jpg", "longitude": -111.407890, "latitude": 36.894037, "width": 500, "height": 375, "upload_date": "04 January 2007", "owner_id": 64388, "owner_name": "Artusi", "owner_url": "http://www.panoramio.com/user/64388"},
//{"photo_id": 88151, "photo_title": "Val Verzasca - Switzerland", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/88151.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/88151.jpg", "longitude": 8.838158, "latitude": 46.257746, "width": 500, "height": 375, "upload_date": "28 November 2006", "owner_id": 11098, "owner_name": "Michele Masnata", "owner_url": "http://www.panoramio.com/user/11098"},
//{"photo_id": 6463, "photo_title": "Guggenheim and spider", "photo_url": "http://commondatastorage.googleapis.com/static.panoramio.com/photos/original/6463.jpg", "photo_file_url": "http://mw2.google.com/mw-panoramio/photos/medium/6463.jpg", "longitude": -2.933736, "latitude": 43.269159, "width": 500, "height": 375, "upload_date": "09 January 2006", "owner_id": 414, "owner_name": "Sonia Villegas", "owner_url": "http://www.panoramio.com/user/414"}
//]}';
//
//$jsondec = json_decode($json);
echo json_encode($jsonArr);

?>