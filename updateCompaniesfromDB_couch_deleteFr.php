<?php
include 'config.php';
include 'collectData.php';
include 'Rdf.php';
include 'showResults.php';
$couchUserPwd = couchUser.':'.couchPass;
$time_pre = microtime(true);
$counter = 1;
$transform = new collectData();
$dateUpdate = '2018-08-03';
$connGemh =  new MySQLi(gemhDb_host, gemhDb_user, gemhDb_pass, gemhDb_name);
mysqli_set_charset($connGemh,"utf8");

$db = companiescouchDB;
$ch = curl_init();

$manualDate = isset($argv[1]) ? $argv[1]: '';

 if (PHP_OS==='WINNT') {
    $dir_base= "C:/temp/fr_delete/";
}
else {
    $dir_base= "/home/user/searchLB/temp/fr/";
}

 
 
 if (file_exists($dir_base."found/")) {
     deleteDir($dir_base."found/"); //delete temp folder
     if (!is_dir_empty($dir_base."found/")){
         mkdir($dir_base."found/", 0777); //create temp folder for files to be updated
     }
         
 }
 
 
 if ($manualDate !=''){
     
     $date = $manualDate;
     
 }
 else {
     
     $date =  '2019-11-01';
     
 }
 
$sql ="SET SESSION group_concat_max_len = 1000000;";
$result = $connGemh->query($sql);
#$sql = "SELECT * FROM Main where orgtype <> 'FR'  and issueddate >= '$dateUpdate'  limit 10000 offset 10000";
$sql = "  SELECT m.vatId, m.gemhnumber, m.orgType, m.street, m.postalCode, m.locality, m.name, m.brandname, m.status, m.chamber, m.gemhdate, m.registrationDate, m.issueddate, m.correctVat,"
        . " cl2.title ,  (select (group_concat( distinct cl.apiCpa,'#',cl.level1Code,'#',cl.countCompanies,'#',IFNULL(cl.marketId,' '),'#',cl.code,'#',cc.main,'#',cl.parent SEPARATOR '~ ') ) ) as cpaArray "
        . "  FROM Main m  left join companyCpa cc on cc.gemhnumber = m.gemhNumber left join CpaList cl on cl.apiCpa=cc.apiCpa "
        ." right join  companyCpa cc2 on cc2.gemhnumber = m.gemhNumber right join CpaList cl2 on (cl2.apiCpa=cc2.apiCpa and  cc2.main = 1) "
        . "where (m.orgtype = 'FR' ) "
        . "and m.issueddate >= '$date' and m.issueddate <= '2019-11-18' "
       # . "and m.issueddate = '2019-04-09' "
       #. " and m.gemhnumber='001037501000' " //148595001000 //003467701000 //001352601000
        . "group by m.gemhnumber  ";
        
 #$sql = "SELECT * FROM Main where (orgtype <> 'FR' or orgtype is null) and vatId= '997834472'  ";
echo $sql;
$result = $connGemh->query($sql);
if ($result->num_rows > 0) {
     while($row = $result->fetch_assoc()){
         if ($row['cpaArray'] !=='') {
             $cpaAll = objectfromConcatString($row['cpaArray']);
         }
         else {
              $cpaAll = [];
         }
         if ($row['correctVat']==='true'){
            $bidVat= checkBidToVat($connGemh,$row['vatId']);
            if ($bidVat == NULL) {
                $link = $row['vatId'];
            }
            else {
                 $link = $bidVat;
            }
            
         }
         else {
             # $id = $row['gemhnumber'].'-'.$row['vatId'];
              $link = $row['vatId'].'-'.$row['gemhnumber'];
         }
         $id = $row['gemhnumber'];
         echo $row['orgType'].' '.$id;
       # $ch = curl_init("http://83.212.86.164:8983/solr/".$core."/update?wt=json");
       # 
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
             
             echo  $urlDel.' will be deleted'.PHP_EOL;
             
             $urlrev=$urlDel.'?rev='.$jsonDel['_rev'];
             $chDel = curl_init();
             curl_setopt($chDel , CURLOPT_URL, $urlrev); 
	     curl_setopt($chDel, CURLOPT_USERPWD, $couchUserPwd);          
             curl_setopt($chDel, CURLOPT_CUSTOMREQUEST, 'DELETE');
             curl_setopt($chDel, CURLOPT_RETURNTRANSFER, true);
             $resultDel = curl_exec( $chDel);
             curl_close($chDel);
         }
         
         
         #$Rdf = new Rdf();
         
        
         
         #$id = $row['gemhnumber'];
        
         
         
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

function objectfromConcatString($concatString){
    $cpaObject = [[]];
    $arrayL1= explode('~ ', $concatString);
    foreach ($arrayL1 as $key => $value) {
        echo $key;
        $arrayL2 = explode('#', $value);
        $cpaObject[$key]['apiCpa'] = $arrayL2[0] ; 
        $cpaObject[$key]['level1Code'] = $arrayL2[1] ; 
        $cpaObject[$key]['countCompanies'] = $arrayL2[2] ; 
        $cpaObject[$key]['marketId'] = $arrayL2[3] ; 
        $cpaObject[$key]['code'] = $arrayL2[4] ; 
        $cpaObject[$key]['main'] = $arrayL2[5] ; 
        $cpaObject[$key]['parent'] = $arrayL2[6] ; 
        
    }
    return  $cpaObject;
}

function checkBidToVat($conn,$vat){
    $sql = "SELECT * FROM BidToVat where vatId='$vat' order by date desc limit 1 ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
         while($row = $result->fetch_assoc()){
             return $row['bid'].'-'.$row['gemhnumber'];
         }
    }
    else {
        return NULL;
    }
    
    
    
}