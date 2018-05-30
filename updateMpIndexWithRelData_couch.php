<?php
include 'config.php';
include 'collectData.php';

$time_pre = microtime(true);
$counter = 1;
$transform = new collectData();

$dateUpdate = '2018-05-23';
$connGemh =  new MySQLi(gemhDb_host, gemhDb_user, gemhDb_pass, gemhDb_name);
mysqli_set_charset($connGemh,"utf8");

$db = MPcouchDB;
$ch = curl_init();

$sql = " SELECT  mp.id,mp.name, m.vatId as s_mgmtCompanyVat, m.name as s_mgmtCompanyName from MemberPosition mp join Main m on m.gemhnumber=mp.gemhnumber where mp.personId=0 ";
  
echo $sql;

$result = $connGemh->query($sql);
if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()){
         $id = $row['id'];
          $arr = array(
            
                    "row"   => $row['id'],
                   
                    'name'=>isset($row['name']) ? $row['name'] : '',
                    'name_eng'=> $transform->transliterate($transform->unaccent(mb_convert_case($row['name'], MB_CASE_UPPER, "UTF-8"))),                 
                   #  'isCompany'=>isset($row['isCompany']) ? $row['is'] : '',   
                    'issueddate'=>isset($row['issueddate']) ? $row['issueddate'] : '',                   
                    's_mgmtCompanyVat'=>isset($row['s_mgmtCompanyVat']) ? $row['s_mgmtCompanyVat'] : '',
                    's_mgmtCompanyName'=>isset($row['s_mgmtCompanyName']) ? $row['s_mgmtCompanyName'] : '',
                    #'s_ownCompanyVat'=>isset($row['s_mgmtCompanyVat']) ? $row['s_mgmtCompanyVat'] : '',
                    #'s_ownCompanyName'=>isset($row['s_mgmtCompanyName']) ? $row['s_mgmtCompanyName'] : ''
               
         );
         
         #$id = $row['gemhnumber'];
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



