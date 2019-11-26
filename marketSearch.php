<?php
$DbPath= 'http://83.212.86.158:5984/';
$Db = 'lb_market_cpa';
$gemhDb ='lb_companies';
$couchUser= 'dimneg';
$couchPass = 'fujintua0)';
$lucenePath ='_fti/local/';
$Limit = 25;
$Sort = 'score';
$Wc = '';
$Index ='';
$apiCpa=[];

$markets_Array = [100,661];

$ch = curl_init();

foreach ($markets_Array as $key => $market_id) {
    
   #$apiCpa[]= searchMarketsForCpas($DbPath, $Db, $couchUser, $couchPass, $market_id,$ch);
   $apiCpa= array_merge($apiCpa,searchMarketsForCpas($DbPath, $Db, $couchUser, $couchPass, $market_id,$ch));
    
    
}
print_r($apiCpa);

foreach ($apiCpa as $key => $cpa) {
    
    $varKeyword = $cpa;
    $searchvar1 = getCompaniesByCpa($DbPath,$lucenePath, $gemhDb, 'buyerVatIdOrName', 'by_buyerDtls_VatIdOrName', $Wc, $Limit, $Sort, $varKeyword, $couchUser,$couchPass,$ch,'apiCpa');
    echo $cpa. ' '.$searchvar1. 'companies'.PHP_EOL;
    
}

 curl_close($ch);

function searchMarketsForCpas($DbPath,$Db,$couchUser, $couchPass,$market_id,$ch){
    
    $couchUserPwd = $couchUser.':'.$couchPass;
    
   
    $url = $DbPath.$Db.'/'.$market_id;  
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $couchUserPwd );
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-type: application/json; charset=utf-8',
    'Accept: */*'
    ));

    $response = curl_exec($ch);
    
    $json = json_decode($response,true);
    return $json['apiCpa'];
    
    
    
    
}

function getCompaniesByCpa($DbPath,$lucenePath,$Db,$DesignDoc,$Index,$Wc,$Limit,$Sort,$varKeyword,$couchUser,$couchPass,$ch,$term){
    
    $couchUserPwd = $couchUser.':'.$couchPass;
    
    $url = $DbPath.$lucenePath.$Db."/_design/".$DesignDoc."/".$Index."?q=".$term.":".$varKeyword.$Wc."&limit:".$Limit."&sort:".$Sort;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $couchUserPwd );
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                       'Content-type: application/json; charset=utf-8',
                       'Accept: */*'
                    ));

    $response = curl_exec($ch);
    
   $json = json_decode($response,true);
   return $json['total_rows'];
    
    
    
}