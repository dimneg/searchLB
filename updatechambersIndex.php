<?php
include 'config.php';
$couchUserPwd = couchUser.':'.couchPass;
$time_pre = microtime(true);
$counter = 1;
$ch = curl_init();
$input_path= "C:/temp/chambers/";
$db = chamberscouchDB;
$files=array_diff(scandir($input_path), array('..', '.'));
foreach($files as $let=>$word){
    if (($handle = fopen($input_path.$word, "r")) !== FALSE){
        while (($row = fgetcsv($handle, 100000, ",")) !== FALSE) {
            if(!mb_detect_encoding($row[0] , 'utf-8', true)){
                $row[0]  = utf8_encode($row[0]);
             }
              $id = $row[0];    
               $arr = array(
                    "chamber_gr"   => $row[2],
                    "chamber_en"   =>$row[3],
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
        }
    }
}




        
       

      

curl_close($ch);

$time_post = microtime(true);
$exec_time = $time_post - $time_pre;

echo '(In '.number_format($exec_time/60,2).' mins)'.PHP_EOL ;



