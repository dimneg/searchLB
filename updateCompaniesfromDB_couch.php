<?php
include 'config.php';
include 'collectData.php';

$time_pre = microtime(true);
$counter = 1;
$transform = new collectData();
$dateUpdate = '1900-01-01';
$connGemh =  new MySQLi(gemhDb_host, gemhDb_user, gemhDb_pass, gemhDb_name);
mysqli_set_charset($connGemh,"utf8");

$db = FRcouchDB;
$ch = curl_init();

#$sql = "SELECT * FROM Main where orgtype <> 'FR'  and issueddate >= '$dateUpdate'  limit 10000 offset 10000";
$sql = "SELECT * FROM Main where orgtype = 'FR'  limit 50000 offset 50000";

$result = $connGemh->query($sql);
if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()){
         if ($row['orgType'] ==='FR'){
            
             $core = FRSolrCore;
         }
         else {
             $core = companiesSolrCore;
              #echo $row['orgType'];
         }
         if ($row['correctVat']==='true'){
             $id = $row['vatId'];
         }
         else {
              $id = $row['vatId'].'-'.$row['gemhnumber'];
         }
          echo $row['orgType'].' '.$id;
       # $ch = curl_init("http://83.212.86.164:8983/solr/".$core."/update?wt=json");
         
      
                 $arr = array(
                    "id"   => $id,
                    "vat"   => $row['vatId'],     
                    "gemhNumber"   => $row['gemhnumber'],     
                    "orgType"   =>isset($row['orgType']) ? $row['orgType'] : '',
                    "address" => isset($row['street']) ? $row['street'] : '',
                    'postcode'=>isset($row['postalCode']) ? $row['postalCode'] : '', 
                    'city'=>isset($row['locality']) ? $row['locality'] : '',
                    'name'=>isset($row['name']) ? $row['name'] : '',
                    'name_eng'=> $transform->transliterate($transform->unaccent(mb_convert_case($row['name'], MB_CASE_UPPER, "UTF-8"))),
                    'brandname'=>isset($row['brandname']) ? $row['brandname'] : '',
                    'status'=>isset($row['status']) ? $row['status'] : '',
                    'chamber'=>isset($row['chamber']) ? $row['chamber'] : '',
                    'gemhdate'=>isset($row['gemhdate']) ? $row['gemhdate'] : '',
                    'registrationDate'=>isset($row['registrationDate']) ? $row['registrationDate'] : '',
                    'issueddate'=>isset($row['issueddate']) ? $row['issueddate'] : '',
                    'indexeddate'=>date("Y-m-d"),
                    'correctVat'=>isset($row['correctVat']) ? $row['correctVat'] : ''
               
         );
         
         #$id = $row['gemhnumber'];
         $counter++; 
         
         $file_contents=json_encode($arr,JSON_UNESCAPED_UNICODE);

        # curl_setopt($ch, CURLOPT_URL, 'localhost:5984/'.$db.'/'.$id);
          curl_setopt($ch, CURLOPT_URL, 'http://83.212.86.158:5984/'.$db.'/'.$id);
         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); /* or PUT */
         curl_setopt($ch, CURLOPT_POSTFIELDS, $file_contents);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_USERPWD, 'dimneg:dim1978');
         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-type: application/json',
                        'Accept: */*'
         ));
        $response = curl_exec($ch); 
        echo $counter.'-'.$id.PHP_EOL;
        echo $response.PHP_EOL;
        $counter++;

         
        #print_r( $response); 
        # curl_close($ch);
        
     }
}

      
curl_close($ch);

$connGemh->close();
$time_post = microtime(true);
$exec_time = $time_post - $time_pre;

echo '(In '.number_format($exec_time/60,2).' mins)'.PHP_EOL ;

# https://stackoverflow.com/questions/24174999/add-document-to-apache-solr-via-php-curl

################# create core
# cd /opt/solr
# sudo -u solr ./bin/solr create -c LbCompanies

############### delete all json from core
# sudo curl "http://127.0.0.1:8983/solr/LbPersons/update?commit=true" -H "Content-Type: text/xml" --data-binary '<delete><query>*:*</query></delete>'



