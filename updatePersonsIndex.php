<?php
include 'config.php';

$time_pre = microtime(true);
$counter = 1;


$connGemh =  new MySQLi(gemhDb_host, gemhDb_user, gemhDb_pass, gemhDb_name);
mysqli_set_charset($connGemh,"utf8");

$ch = curl_init("http://83.212.86.164:8983/solr/".personsSolrCore."/update?wt=json");

$sql = "SELECT * FROM PersonalData where isGsisCompany = 0 limit 10000 offset 50000";

$result = $connGemh->query($sql);
if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()){
         $data = array(
            "add" => array( 
                "doc" => array(
                    "id"   => $row['id'],
                    "vat"   => $row['vatNumber'],
                    "adt" => $row['adt'],
                    "address" => isset($row['address']) ? $row['address'] : '',
                    'postcode'=>isset($row['postcode']) ? $row['postcode'] : '',
                    'city'=>isset($row['city']) ? $row['city'] : '',
                    'name'=>isset($row['name']) ? $row['name'] : '',
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
# sudo -u solr ./bin/solr create -c LbPersons

############### delete all json from core
# sudo curl "http://127.0.0.1:8983/solr/LbPersons/update?commit=true" -H "Content-Type: text/xml" --data-binary '<delete><query>*:*</query></delete>'


