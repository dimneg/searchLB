<?php
include 'config.php';
include 'collectData.php';
$couchUserPwd = couchUser.':'.couchPass;
$time_pre = microtime(true);
$counter = 1;
$transform = new collectData();

#$dir_base= "/home/user/searchLB/temp/mp/temp/";
$dir_base= "c:/temp/mp/temp/";

if (file_exists($dir_base."found/")) {
     deleteDir($dir_base."found/"); //delete temp folder
     if (!is_dir_empty($dir_base."found/")){
         mkdir($dir_base."found/", 0777); //create temp folder for files to be updated
     }
         
 }

$dateUpdate = '2019-05-01';
$connGemh =  new MySQLi(gemhDb_host, gemhDb_user, gemhDb_pass, gemhDb_name);
mysqli_set_charset($connGemh,"utf8");

$db = MPcouchDB;
$ch = curl_init();

$sql = " SELECT  mp.id, mp.name, m.vatId as s_mgmtCompanyVat,  m.correctVat as s_mgmtCorrectVat,  m.gemhnumber as s_mgmtGemhNumber, m.name as s_mgmtCompanyName"
       # . " from MemberPosition mp join Main m on m.gemhnumber=mp.gemhnumber where mp.personId=0 and  mp.issuedDate >= subdate(current_date,0 ) limit 700000 offset 0 ";
         . " from MemberPosition mp join Main m on m.gemhnumber=mp.gemhnumber where mp.personId=0 and mp.forDelete <> 1  and mp.issuedDate >= '$dateUpdate'  limit 700000 offset 0 ";
  
echo $sql;

$result = $connGemh->query($sql);
if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()){
         
         $id = $row['id'];
          if ($row['s_mgmtCorrectVat']==='true'){
            
            $s_mgmtCompanyLink = $row['s_mgmtCompanyVat'];
         }
         else {
             # $id = $row['gemhnumber'].'-'.$row['vatId'];
              $s_mgmtCompanyLink = $row['s_mgmtCompanyVat'].'-'.$row['s_mgmtGemhNumber'];
         }
         
         //find and delete
         $chUpd = curl_init();
         $urlUpd = couchPath.$db.'/'.$id;
         echo $urlUpd.PHP_EOL;
         curl_setopt($chUpd, CURLOPT_URL, $urlUpd); 
	 curl_setopt( $chUpd, CURLOPT_USERPWD, $couchUserPwd);	
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
	     curl_setopt($chDel, CURLOPT_USERPWD, $couchUserPwd);          
             curl_setopt($chDel, CURLOPT_CUSTOMREQUEST, 'DELETE');
             curl_setopt($chDel, CURLOPT_RETURNTRANSFER, true);
             $resultDel = curl_exec( $chDel);
             curl_close($chDel);
         }
       
          $arr = array(
            
                    "row"   => $row['id'],
                   
                    'name'=>isset($row['name']) ? $row['name'] : '',
                    'name_eng'=> $transform->transliterate($transform->unaccent(mb_convert_case($row['name'], MB_CASE_UPPER, "UTF-8"))),                 
                   #  'isCompany'=>isset($row['isCompany']) ? $row['is'] : '',   
                    'issueddate'=>isset($row['issueddate']) ? $row['issueddate'] : '',                   
                    's_mgmtCompanyVat'=>isset($row['s_mgmtCompanyVat']) ? $row['s_mgmtCompanyVat'] : '',
                    's_mgmtCompanyName'=>isset($row['s_mgmtCompanyName']) ? $row['s_mgmtCompanyName'] : '',
                    's_mgmtCompanyLink'=>$s_mgmtCompanyLink,
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
            deleteDir($file);
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
