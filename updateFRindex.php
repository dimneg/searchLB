<?php
include 'config.php';

$time_pre = microtime(true);
$counter = 1;

$dateUpdate = '1900-01-01';
$connGemh =  new MySQLi(gemhDb_host, gemhDb_user, gemhDb_pass, gemhDb_name);
mysqli_set_charset($connGemh,"utf8");

$ch = curl_init("http://83.212.86.164:8983/solr/".FRSolrCore."/update?wt=json");

$sql = "SELECT * FROM Main where orgtype = 'FR'  and issueddate > '$dateUpdate'  limit 50000 offset 600000";

$result = $connGemh->query($sql);
if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()){
         $data = array(
            "add" => array( 
                "doc" => array(
                    "id"   => $row['id'],
                    "vat"   => $row['vatId'],     
                    "gemhNumber"   => $row['gemhnumber'],     
                    "orgType"   =>isset($row['orgType']) ? $row['orgType'] : '',
                    "address" => isset($row['street']) ? $row['street'] : '',
                    'postcode'=>isset($row['postalCode']) ? $row['postalCode'] : '',
                    'city'=>isset($row['locality']) ? $row['locality'] : '',
                    'name'=>isset($row['name']) ? $row['name'] : '',
                    'brandname'=>isset($row['brandname']) ? $row['brandname'] : '',
                    'status'=>isset($row['status']) ? $row['status'] : '',
                    'chamber'=>isset($row['chamber']) ? $row['chamber'] : '',
                    'gemhdate'=>isset($row['gemhdate']) ? $row['gemhdate'] : '',
                    'registrationDate'=>isset($row['registrationDate']) ? $row['registrationDate'] : '',
                    'issueddate'=>isset($row['issueddate']) ? $row['issueddate'] : '',
                ),
                "commitWithin" => 1000,
            ),
         );
         
         #$id = $row['gemhnumber'];
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

      

curl_close($ch);
$connGemh->close();
$time_post = microtime(true);
$exec_time = $time_post - $time_pre;

echo '(In '.number_format($exec_time/60,2).' mins)'.PHP_EOL ;

# https://stackoverflow.com/questions/24174999/add-document-to-apache-solr-via-php-curl

################# create core
# cd /opt/solr
# sudo -u solr ./bin/solr create -c LbFr

############### delete all json from core
# sudo curl "http://127.0.0.1:8983/solr/LbPersons/update?commit=true" -H "Content-Type: text/xml" --data-binary '<delete><query>*:*</query></delete>'



