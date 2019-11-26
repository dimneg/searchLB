<?php
include 'config.php';
$couchUserPwd = couchUser.':'.couchPass;
$time_pre = microtime(true);
$counter = 1;
$ch = curl_init();
$db = 'lb_market_cpa';
$connGemh =  new MySQLi(gemhDb_host, gemhDb_user, gemhDb_pass, gemhDb_name);
mysqli_set_charset($connGemh,"utf8");
$sql ="SET SESSION group_concat_max_len = 1000000;";
$sql = " SELECT marketId, (select (group_concat( distinct apiCpa SEPARATOR '~ ') ) ) as apicpa FROM gemhV2.marketCpas group by marketId limit 100000 offset 100000 ";
echo $sql;
$result = $connGemh->query($sql);
if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()){
         
         $arr = array(
                    
                    "apiCpa"   =>objectfromConcatString($row['apicpa']),                
                                         
                    
                   "issuedDate"   =>date("Y-m-d")
             
               );
         $file_contents=json_encode($arr,JSON_UNESCAPED_UNICODE);
                curl_setopt($ch, CURLOPT_URL, 'http://83.212.86.158:5984/'.$db.'/'.$row['marketId']);
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
$connGemh->close();

$time_post = microtime(true);
$exec_time = $time_post - $time_pre;

echo '(In '.number_format($exec_time/60,2).' mins)'.PHP_EOL ;

function objectfromConcatString($concatString){
    #$object = [[]];
    $array= explode('~ ', $concatString);
    foreach ($array as  $value) {
        #echo $key;
        $array_2 = explode('#', $value);
        #$object[$key]['apiCpa'] = $array_2[0] ; 
        $object[]=$array_2[0] ;
       # $object[$key]['cpa'] = $array_2[1] ;        
        
    }
    return  $object;
}



