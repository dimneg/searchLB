<?php
include 'config.php';
include 'collectData.php';

$time_pre = microtime(true);
$counter = 1;
$transform = new collectData();
$dateUpdate = '1900-01-01';


$core = 'testcore1';
 $ch = curl_init("http://83.212.86.164:8983/solr/".$core."/update?wt=json");

#$sql = "SELECT * FROM Main where orgtype <> 'FR'  and issueddate >= '$dateUpdate'  limit 10000 offset 10000";
$inputPath='C:\temp/onlyGsis/';
$files=array_diff(scandir($inputPath), array('..', '.'));

foreach($files as $let=>$word){
    if (($handle = fopen($inputPath.$word, "r")) !== FALSE){
        while (($row = fgetcsv($handle, 100000, ",")) !== FALSE) {
            if(!mb_detect_encoding($row[0] , 'utf-8', true)){
                $row[0]  = utf8_encode($row[0]);
             }
             $counter++; 
              if ($row[1] ==='FR'){
                  $core = 'testcore1';
              }
              else {
                   $core = 'testcore2';
              }
             
              $id = $row[0];            
             
                 
              
               echo $id .' '.$row[1].' '.$id;
                 $data = array(
                    "add" => array( 
                      "doc" => array(
                          "id"   => $id,
                           "vat"   => $row[0],
                           "orgType"   =>isset($row[1]) ? $row[1] : '',
                           "address" => isset($row[2]) ? $row[2] : '',
                           'postcode'=>isset($row[3]) ? $row[3] : '', 
                           'city'=>isset($row[4]) ? $row[4] : '',
                           'name'=>isset($row[5]) ? $row[5] : '',
                           'name_eng'=> $transform->transliterate($transform->unaccent(mb_convert_case($row[5], MB_CASE_UPPER, "UTF-8"))),
                           'registrationDate'=>isset($row[6]) ? $row[6] : '',
                           'issueddate'=>isset($row[7]) ? $row[7] : ''
                     ),
                         "commitWithin" => 1000,
                           ),
         );
                  $counter++; 
         
         $data_string = json_encode($data);

         curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
         curl_setopt($ch, CURLOPT_POST, TRUE);
         curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
         curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

         $response = curl_exec($ch);
         print_r( $response); 
        }
    }
}

         
         #$id = $row['gemhnumber'];
        
        # curl_close($ch);
        
     


      
 curl_close($ch);

#$connGemh->close();
$time_post = microtime(true);
$exec_time = $time_post - $time_pre;

echo '(In '.number_format($exec_time/60,2).' mins)'.PHP_EOL ;

# https://stackoverflow.com/questions/24174999/add-document-to-apache-solr-via-php-curl

################# create core
# cd /opt/solr
# sudo -u solr ./bin/solr create -c LbCompanies

############### delete all json from core
# sudo curl "http://127.0.0.1:8983/solr/LbPersons/update?commit=true" -H "Content-Type: text/xml" --data-binary '<delete><query>*:*</query></delete>'



