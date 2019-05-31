<?php
include 'config.php';
$DbPath ="http://83.212.86.158:5984/";
$couchUserPwd = couchUser.':'.couchPass;
$time_pre = microtime(true);
$counter = 1;
$ch = curl_init();
$dbSource = 'lb_electoral';

$connectionInfo = array( "UID"=>uid ,                              
                         "PWD"=>pwd,                              
                         "Database"=>databaseName); 

#$conn = sqlsrv_connect(sqlserver, $connectionInfo);   

//https://support.winhost.com/kb/a687/sample-code-to-connect-to-an-ms-sql-database-using-php.aspx
//http://robsphp.blogspot.com/2012/09/how-to-install-microsofts-sql-server.html




$chMain = curl_init();
$urlMain = $DbPath.$dbSource.'/'.'_all_docs?include_docs=true';                                                                                                                                                
curl_setopt($chMain, CURLOPT_URL, $urlMain);
curl_setopt($chMain, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($chMain, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chMain, CURLOPT_USERPWD, $couchUserPwd );
curl_setopt($chMain, CURLOPT_HTTPHEADER, array(
 'Content-type: application/json; charset=utf-8',
 'Accept: */*'
  ));
echo $urlMain;
 $responseMain = curl_exec($chMain); 
 curl_close($chMain);
 $jsonMain = json_decode($responseMain,true);
 return $jsonMain;

foreach ($jsonMain ['rows'] as $row ) {
    echo $row['id'].PHP_EOL;
}




$time_post = microtime(true);
$exec_time = $time_post - $time_pre;

echo '(In '.number_format($exec_time/60,2).' mins)'.PHP_EOL ;
curl_close($ch);


