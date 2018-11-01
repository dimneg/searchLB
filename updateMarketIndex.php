<?php
include 'config.php';
$couchUserPwd = couchUser.':'.couchPass;
$time_pre = microtime(true);
$counter = 1;
$ch = curl_init();
$db = 'lb_markets';
$connGemh =  new MySQLi(gemhDb_host, gemhDb_user, gemhDb_pass, gemhDb_name);
mysqli_set_charset($connGemh,"utf8");
$sql = "SELECT * FROM gemhV2.Markets where titleGr<>'' ";
echo $sql;
$result = $connGemh->query($sql);
if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()){
         $id = $row['code'];
         $arr = array(
                    "id"   => $row['id'],
                    "code"   =>$row['code'],
                 
                    "marketTitle"   =>$row['marketTitle'],
                    "type"   =>$row['type'],
                    "level"   =>$row['type'],
                    "parentCode"   =>$row['parentCode'],
                    "level1Code"   =>$row['level1Code'],
                    "titleGr"   =>$row['titleGr'],
                    "googleWorkSheet"   =>$row['googleWorkSheet'],
                    "tableName"   =>$row['tableName'],
                   "issuedDate"   =>$row['issuedDate']
             
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
                print_r($response);
     }
}





        
       

      

curl_close($ch);

$time_post = microtime(true);
$exec_time = $time_post - $time_pre;

echo '(In '.number_format($exec_time/60,2).' mins)'.PHP_EOL ;



