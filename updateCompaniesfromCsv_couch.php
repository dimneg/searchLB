<?php
include 'config.php';
include 'collectData.php';
include 'Rdf.php';
include 'showResults.php';
$couchUserPwd = couchUser.':'.couchPass;
$dateUpdate = '2018-07-28';
$time_pre = microtime(true);
$counter = 1;
$transform = new collectData();


$ch = curl_init();
$db = nonGemhcouchDB;
$dir_base= "d:/temp/non_gemh/";
 
 if (file_exists($dir_base."found/")) {
     deleteDir($dir_base."found/"); //delete temp folder
     if (!is_dir_empty($dir_base."found/")){
         mkdir($dir_base."found/", 0777); //create temp folder for files to be updated
     }
         
 }
#$sql = "SELECT * FROM Main where orgtype <> 'FR'  and issueddate >= '$dateUpdate'  limit 10000 offset 10000";
$inputPath='C:\temp/onlyGsis/';
$files=array_diff(scandir($inputPath), array('..', '.'));

foreach($files as $let=>$word){
    if (($handle = fopen($inputPath.$word, "r")) !== FALSE){
        while (($row = fgetcsv($handle, 100000, ",")) !== FALSE) {
            if(!mb_detect_encoding($row[0] , 'utf-8', true)){
                $row[0]  = utf8_encode($row[0]);
             }
             #$counter++; 
              
             
               $id = $row[0];    
               $link = $row[0];
             
                 
              
               echo $id .' '.$row[1].' '.$id;
               
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
                
                 $arr = array(
                    "id"   => $id,
                    "vat"   => $row[0],     
                    #"gemhNumber"   => $row['gemhnumber'],     
                    "orgType"   =>isset($row[1]) ? $row[1] : '',
                    "address" => isset($row[2]) ? $row[2] : '',
                    'postcode'=>isset($row[3]) ? $row[3] : '', 
                    'city'=>isset($row[4]) ? $row[4] : '',
                    'name'=>isset($row[5]) ? $row[5] : '',
                    'name_eng'=> $transform->transliterate($transform->unaccent(mb_convert_case($row[5], MB_CASE_UPPER, "UTF-8"))),
                    #'brandname'=>isset($row['brandname']) ? $row['brandname'] : '',
                    #'status'=>isset($row['status']) ? $row['status'] : '',
                    #'chamber'=>isset($row['chamber']) ? $row['chamber'] : '',
                    #'gemhdate'=>isset($row['gemhdate']) ? $row['gemhdate'] : '',
                    'registrationDate'=>isset($row[6]) ? $row[6] : '',
                    'issueddate'=>isset($row[7]) ? $row[7] : '',
                    'indexeddate'=>date("Y-m-d"),
                    #'correctVat'=>isset($row['correctVat']) ? $row['correctVat'] : '',
                     'link' => $link,
               
         );
                  $counter++; 
         
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
        echo $counter.'-'.$id.PHP_EOL;
        echo $response.PHP_EOL;
        $counter++;
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