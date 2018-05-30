<?php
include 'config.php';
include 'collectData.php';

$time_pre = microtime(true);
$counter = 1;
$transform = new collectData();

$dateUpdate = '2018-05-23';
$connGemh =  new MySQLi(gemhDb_host, gemhDb_user, gemhDb_pass, gemhDb_name);
mysqli_set_charset($connGemh,"utf8");

$db = personscouchDB;
$ch = curl_init();

$sql = " SELECT  pd.id, pd.vatNumber,pd.name,pd.address,pd.city,  pd.postcode, pd.adt,pd.issueddate,  pd.ownershipCnt, pd.managementCnt, pd.isGsisCompany,    m.vatId as s_ownCompanyVat,m.name as s_ownCompanyName,m2.vatId as s_mgmtCompanyVat,m2.name as s_mgmtCompanyName  FROM PersonalData pd  left join OwnershipData o  on o.personId = pd.id  left join Main m   on o.gemhNumber = m.gemhNumber  left join MemberPosition mp  on mp.personId=pd.id  left join Main m2  on mp.gemhNumber = m2.gemhNumber  group by pd.id  limit 20000 offset 10000";
  # and issueddate >= '$dateUpdate'  "
echo $sql;

$result = $connGemh->query($sql);
if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()){
         $id = $row['vatNumber'];
          $arr = array(
            
                    "row"   => $row['id'],
                    "vat"   => $row['vatNumber'],
                    "adt" => $row['adt'],
                    "address" => isset($row['address']) ? $row['address'] : '',
                    'postcode'=>isset($row['postcode']) ? $row['postcode'] : '',
                    'city'=>isset($row['city']) ? $row['city'] : '',
                    'name'=>isset($row['name']) ? $row['name'] : '',
                    'name_eng'=> $transform->transliterate($transform->unaccent(mb_convert_case($row['name'], MB_CASE_UPPER, "UTF-8"))),
                    "managementCnt"   => $row['managementCnt'],      
                    "ownershipCnt"   => $row['ownershipCnt'],
                    "link"   => $row['vatNumber'],
                    'issueddate'=>isset($row['issueddate']) ? $row['issueddate'] : '',
                    'isCompany'=>isset($row['isGsisCompany']) ? $row['isGsisCompany'] : '',
                    's_ownCompanyVat'=>isset($row['s_ownCompanyVat']) ? $row['s_ownCompanyVat'] : '',
                    's_ownCompanyName'=>isset($row['s_ownCompanyName']) ? $row['s_ownCompanyName'] : '',
                    's_mgmtCompanyVat'=>isset($row['s_mgmtCompanyVat']) ? $row['s_mgmtCompanyVat'] : '',
                    's_mgmtCompanyName'=>isset($row['s_mgmtCompanyName']) ? $row['s_mgmtCompanyName'] : ''
               
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



