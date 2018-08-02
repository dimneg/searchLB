<?php
include 'config.php';
include 'collectData.php';
$couchUserPwd = couchUser.':'.couchPass;

$time_pre = microtime(true);
$counter = 1;
$transform = new collectData();

$dateUpdate = '2018-07-28';
$connGemh =  new MySQLi(gemhDb_host, gemhDb_user, gemhDb_pass, gemhDb_name);
mysqli_set_charset($connGemh,"utf8");

$db = personscouchDB;
$dir_base= "d:/temp/persons/";
$ch = curl_init();

$sql = " SELECT  pd.id, pd.vatNumber,"
        . " pd.name,pd.address,pd.city, "
        . " pd.postcode, pd.adt, pd.issueddate,"
        . " pd.ownershipCnt, pd.managementCnt,"
        . " pd.isGsisCompany,  "
        . " m.vatId as s_ownCompanyVat,m.name as s_ownCompanyName, m.correctVat as s_ownCorrectVat, m.gemhnumber as s_ownGemhNumber, "
        . " m2.vatId as s_mgmtCompanyVat, m2.name as s_mgmtCompanyName, m2.correctVat as s_mgmtCorrectVat, m2.gemhnumber as s_mgmtGemhNumber,  " 
        . " m3.correctVat as personCompanyCorrectVat , m3.gemhnumber as personCompanyGemhNumber "
        . " FROM PersonalData pd  left join OwnershipData o  on o.personId = pd.id  left join Main m  "
        . " on o.gemhNumber = m.gemhNumber  left join MemberPosition mp  on mp.personId=pd.id  left join Main m2  on mp.gemhNumber = m2.gemhNumber "
        . " left join Main m3 on m3.vatId = pd.vatNumber "
        . " where pd.issueddate >= '$dateUpdate "
        #. " where pd.vatNumber = '133641529' "
        . " group by pd.id "
        . "' ";
  # and issueddate >= '$dateUpdate'  "
echo $sql;

$result = $connGemh->query($sql);
if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()){
         $id = $row['vatNumber'];
         
         //find and delete
                $chUpd = curl_init();
                $urlUpd = couchPath.$db.'/'.$id;
                echo $urlUpd.PHP_EOL;
                curl_setopt($chUpd, CURLOPT_URL, $urlUpd); 
                curl_setopt( $chUpd, CURLOPT_USERPWD, $couchUserPwd );	
                curl_setopt( $chUpd, CURLOPT_CUSTOMREQUEST, 'GET');		
                curl_setopt( $chUpd, CURLOPT_RETURNTRANSFER, true);		
                $resultUpd = curl_exec($chUpd);
                if(!curl_errno($chUpd)){             
                     $info = curl_getinfo($chUpd);
                     #echo 'Took ' . $info['total_time'] . ' seconds to send a request (get) to ' . $info['url'].'<br>';
                }
                curl_close( $chUpd );
                #print_r($info);
                if ($info['http_code']===200){
                    $destination =$dir_base."found/".str_replace('%3F', '?', $id).'.json';
                    $file = fopen($destination, "w+");
                    fputs($file, $resultUpd);
                    fclose($file);

                    $fdel=file_get_contents( $destination );
                    $jsonDel=json_decode( $fdel,true);
                    $jsonDel['_id']=str_replace('/', '%2F', $jsonDel['_id']);
                    $jsonDel['_id'] = str_replace('?','%3F', $jsonDel['_id']);
                    $jsonDel['_id'] = str_replace('?','%3F', $jsonDel['_id']);
                    $urlDel=couchPath.$db.'/'.$jsonDel['_id'];	

                    echo  $urlDel.' will be updated'.PHP_EOL;

                    $urlrev=$urlDel.'?rev='.$jsonDel['_rev'];
                    $chDel = curl_init();
                    curl_setopt($chDel , CURLOPT_URL, $urlrev); 
                    curl_setopt($chDel, CURLOPT_USERPWD, $couchUserPwd );          
                    curl_setopt($chDel, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    curl_setopt($chDel, CURLOPT_RETURNTRANSFER, true);
                    $resultDel = curl_exec( $chDel);
                    curl_close($chDel);
                }
         
          if ($row['personCompanyCorrectVat']==='false'){
              $link = $row['vatNumber'].'-'.$row['personCompanyGemhNumber'];
            
         }
         else {
             # $id = $row['gemhnumber'].'-'.$row['vatId'];
             $link = $row['vatNumber'];
         }
         if ($row['s_mgmtCorrectVat']==='true'){
            
            $s_mgmtCompanyLink = $row['s_mgmtCompanyVat'];
         }
         else {
             # $id = $row['gemhnumber'].'-'.$row['vatId'];
              $s_mgmtCompanyLink = $row['s_mgmtCompanyVat'].'-'.$row['s_mgmtGemhNumber'];
         }
         if ($row['s_ownCorrectVat']==='true'){
            
             $s_ownCompanyLink = $row['s_ownCompanyVat'];
         }
         else {
             # $id = $row['gemhnumber'].'-'.$row['vatId'];
              $s_ownCompanyLink = $row['s_ownCompanyVat'].'-'.$row['s_ownGemhNumber'];
         }
         
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
                    "link"   => $link,
                    'issueddate'=>isset($row['issueddate']) ? $row['issueddate'] : '',
                    'isCompany'=>isset($row['isGsisCompany']) ? $row['isGsisCompany'] : '',
                    's_ownCompanyVat'=>isset($row['s_ownCompanyVat']) ? $row['s_ownCompanyVat'] : '',
                    's_ownCompanyLink'=>$s_ownCompanyLink,
                    's_ownCompanyName'=>isset($row['s_ownCompanyName']) ? $row['s_ownCompanyName'] : '',
                    's_mgmtCompanyVat'=>isset($row['s_mgmtCompanyVat']) ? $row['s_mgmtCompanyVat'] : '',
                    's_mgmtCompanyLink'=>$s_mgmtCompanyLink,
                    's_mgmtCompanyName'=>isset($row['s_mgmtCompanyName']) ? $row['s_mgmtCompanyName'] : '',
                    'indexeddate'=>date("Y-m-d"),
               
         );
         
         #$id = $row['gemhnumber'];
         $file_contents=json_encode($arr,JSON_UNESCAPED_UNICODE);

        # curl_setopt($ch, CURLOPT_URL, 'localhost:5984/'.$db.'/'.$id);
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

# https://stackoverflow.com/questions/24174999/add-document-to-apache-solr-via-php-curl

################# create core
# cd /opt/solr
# sudo -u solr ./bin/solr create -c LbPersons

############### delete all json from core
# sudo curl "http://127.0.0.1:8983/solr/LbPersons/update?commit=true" -H "Content-Type: text/xml" --data-binary '<delete><query>*:*</query></delete>'


function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            self::deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}
function is_dir_empty($dir) {
  if (!is_readable($dir)) return NULL; 
  $handle = opendir($dir);
  while (false !== ($entry = readdir($handle))) {
    if ($entry != "." && $entry != "..") {
      return FALSE;
    }
  }
  return TRUE;
} 
