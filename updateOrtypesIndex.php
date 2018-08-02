<?php
include 'config.php';
$couchUserPwd = couchUser.':'.couchPass;
$time_pre = microtime(true);
$counter = 1;

$dateUpdate = '2018-01-01';
$connGemh =  new MySQLi(gemhDb_host, gemhDb_user, gemhDb_pass, gemhDb_name);
mysqli_set_charset($connGemh,"utf8");
$db = orgtypecouchDB;

$sql = "SELECT orgtype FROM Main group by orgtype ";
echo $sql.PHP_EOL;

$result = $connGemh->query($sql);
if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()){
         $id = str_replace('/', '_', $row['orgtype']);
         $ch = curl_init();
         $url = couchPath.$db.'/'.$id;
         echo $url.PHP_EOL;
         switch ($row['orgtype']) {
             case 'ASSOCIATION':
             $orgtypeFront='Ένωση';
                 break;
             case 'COOP':
             $orgtypeFront='ΣΥΝ.';
                 break;
             case 'COOP/AGRI':
             $orgtypeFront='Α.ΣΥΝ.';
                 break;
             case 'FOREIGN':
             $orgtypeFront='FOREIGN';
                 break;
             case 'FOUNDATION':
             $orgtypeFront='Ιδρυμα';
                 break;
             case 'FR':
             $orgtypeFront='Ελ.Επαγγελμ.';
                 break;
             case 'GP':
             $orgtypeFront='ΟΕ';
                 break;
             case 'JV':
             $orgtypeFront='Κ/Ξ';
                 break;
             case 'LAWYERS':
             $orgtypeFront='LAWYERS';
                 break;
             case 'LP':
             $orgtypeFront='ΕΕ';
                 break;
             case 'LTD':
             $orgtypeFront='ΕΠΕ';
                 break;
             case 'LTD/SM':
             $orgtypeFront='Μ-ΕΠΕ';
                 break;
             case 'LTD_SPECIAL':
             $orgtypeFront='ΕΕ ΜΗ ΦΠ ΕΠΕ';
                 break;
              case 'NPO':
             $orgtypeFront='ΑΜΚΕ';
                 break;
             case 'OTHER':
             $orgtypeFront='ΚΑΔΚ';
                 break;

             default:
                 $orgtypeFront='';
                 break;
         }
         $arr = array(
              "orgtype_main"   => $row['orgtype'],
              "orgtype_front"   => $orgtypeFront
         );
        $file_contents=json_encode($arr,JSON_UNESCAPED_UNICODE);
         curl_setopt($ch, CURLOPT_URL, 'http://83.212.86.158:5984/'.$db.'/'.$id);
         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); /* or PUT */
         curl_setopt($ch, CURLOPT_POSTFIELDS, $file_contents);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_USERPWD, $couchUserPwd );
         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-type: application/json',
                        'Accept: */*'
         ));
        $response = curl_exec($ch); 
        echo $counter.'-'.$id.PHP_EOL;
        echo $response.PHP_EOL;
        $counter++;
         
        
     }
}

      

curl_close($ch);
$connGemh->close();
$time_post = microtime(true);
$exec_time = $time_post - $time_pre;

echo '(In '.number_format($exec_time/60,2).' mins)'.PHP_EOL ;



