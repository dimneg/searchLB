<?php


class collectData {
   function getAll($solrPath,$varKeyword,$solrCore){
       global $Limit;
       #$this->prepareResults($DbPath,"elod_diaugeia_hybrids","buyerVatIdOrName","by_buyerDtls_VatIdOrName",$LuceneOperand,25,"score",$varKeyword,$couchUser,$couchPass);
       
							
      
       $this->prepareResultsSolr($solrPath,$varKeyword,$solrCore );       		  
      
       
	
       #
       #
 }
   
    
   function prepareResultsSolr($solrPath,$solrCore,$varKeyword){
       $vat = $varKeyword;
       $ch = curl_init();
       
       $url = $solrPath.$solrCore."/select?indent=on&q=vat:".$vat."&wt=json";
       $url = str_replace(' ','%20',$url);
       echo $url.PHP_EOL;
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_USERPWD, 'dimneg:dim1978');			
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-type: application/json; charset=utf-8',
	'Accept: */*'
         ));
       
       $response = curl_exec($ch); 
       $json = json_decode($response,true);
       curl_close($ch);	         
       
        if(isset ($json['rows'])) {
             foreach($json['rows'] as $r){  
                  if (isset ($json['rows'])) {
                       $newdata =  array (
                            'name' => (isset($r['fields']['term'][1])) ? $r['fields']['term'][1] : null ,     
                            'vat' => $r['fields']['term'][0]
                           );
                  }
                  $arrayElements = count($Results);
                  if  ($arrayElements <= 1000 && isset($newdata)){
                      $key = $this->searchForId($newdata['vat'], $Results,'vat');
                      if ($key === NULL){
                          $Results[] = $newdata;      //insert whole record
                      }
                      
                  }
                 
             }
            
        }
       
   } 
    
   
   function prepareResults($DbPath,$Db,$DesignDoc,$Index,$Wc,$Limit,$Sort,$varKeyword,$couchUser,$couchPass, $solrPath,$solrCore,$sparqlServer,$corpSolrCore) {
        global $AlreadyFound;
        $couchUserPwd = $couchUser.':'.$couchPass;
        #echo 'pass = '.$couchUserPwd.PHP_EOL;
        //$GLOBALS['newdata'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $DbPath.$Db."/_design/".$DesignDoc."/".$Index."?q=term:".$varKeyword.$Wc."&limit:".$Limit."&sort:".$Sort);
        #echo $DbPath.$Db."/_design/".$DesignDoc."/".$Index."?q=term:".$varKeyword.$Wc."&limit:".$Limit."&sort:".$Sort."<br>";
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $couchUserPwd );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                       'Content-type: application/json; charset=utf-8',
                       'Accept: */*'
                    ));

        $response = curl_exec($ch); 

        curl_close($ch);
        global $prefix ; 
        global $Results;
        global $Lang;   
        
        $json = json_decode($response,true);
        global $Actual_link; 
        global $Lang; 
        $Actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        if (strpos($Actual_link,'/en/') !== false) {
            $Lang= 'en/';
        }
        else
        if (strpos($Actual_link,'/el/') !== false) {
            $Lang= 'el/';
        }
        else $Lang='';

        

        if(isset ($json['rows'])) {
             foreach($json['rows'] as $r){     
                 
            
                
                global $Boost;
                $Boost = 1.2;
                switch ($Wc) { //boost step 1
                case "";{	            
                   $r['score'] *=$Boost;
                   break; 
                }
                case "*"; {
                   $r['score'] *=1;
                    break; 

                }
                case "~0.75"; {
                   $r['score'] *=1;
                   break; 
                }
                }
            //echo $Boost;
                if ($Db == 'yds_big_sellers'){ //got country info so, ignore
                    $country = isset($r['fields']['countryName'])  ? $r['fields']['countryName'] : 'EU';
                }
                else {
                     $country = $this->defineCountry($Db);
                }

           #if(isset ($json['rows']) && (strpos($r['id'], '_') == false || strpos($r['id'], 'TEDS_') !== false ) && ($this->checkAFM($r['fields']['term'][0]) || (strpos($Db, 'australia')== true || strpos($Db, 'yds_big')== true)) ) { //_links and wrong vats exluded for now
             if (isset ($json['rows']) && (strpos($r['id'], '_') == false || strpos($r['id'], 'TEDS_') !== false  ) && ($this->checkAFM($r['fields']['term'][0]) || (strpos($Db, 'australia')!== false || strpos($Db, 'yds_big')!== false)) ){
                $newdata =  array (
                 // 'name' => $r['fields']['term'][1],
                      'name' => (isset($r['fields']['term'][1])) ? $r['fields']['term'][1] : null ,            
                      'vat' => $r['fields']['term'][0],
                     # 'link' => $prefix.$r['id'],
                      'link' => $Actual_link.'/'.$r['fields']['term'][0],
                      'address'=>(isset($r['fields']['address']) ) ? $r['fields']['address'] : null ,
                      'pc'=>(isset($r['fields']['pc']) ) ? $r['fields']['pc'] : null ,   
                      'city'=>(isset($r['fields']['city']) ) ? $r['fields']['city'] : null ,
                      'locality'=>(isset($r['fields']['locality']) ) ? $r['fields']['locality'] : null ,
                      'countryName'=> $country,
                      'score' =>  $r['score'],
                      'id' => $r['id'],
                      #'lastUpdate'=> $r['fields']['lastUpdate'],
                     //ΔΙΑΥΓΕΙΑ buyers
                      'db_award0'=>(isset($r['fields']['db_awardAmount0']) ) ? $r['fields']['db_awardAmount0'] : null ,
                      'db_award1'=> (isset($r['fields']['db_awardAmount1']) ) ? $r['fields']['db_awardAmount1'] : null ,
                      'db_award2'=> (isset($r['fields']['db_awardAmount2']) ) ? $r['fields']['db_awardAmount2'] : null ,
                      'db_awardCnt0'=> (isset($r['fields']['db_awardCounter0']) ) ? $r['fields']['db_awardCounter0'] : null ,
                      'db_awardCnt1'=> (isset($r['fields']['db_awardCounter1']) ) ? $r['fields']['db_awardCounter1'] : null ,
                      'db_awardCnt2'=> (isset($r['fields']['db_awardCounter2']) ) ? $r['fields']['db_awardCounter2'] : null ,
                  
                      'db_spend0'=> (isset($r['fields']['db_spendAmount0']) ) ? $r['fields']['db_spendAmount0'] : null ,
                      'db_spend1'=> (isset($r['fields']['db_spendAmount1']) ) ? $r['fields']['db_spendAmount1'] : null ,
                      'db_spend2'=> (isset($r['fields']['db_spendAmount2']) ) ? $r['fields']['db_spendAmount2'] : null ,
                      'db_spendCnt0'=>  (isset($r['fields']['db_spendCounter0']) ) ? $r['fields']['db_spendCounter0'] : null ,
                      'db_spendCnt1'=>  (isset($r['fields']['db_spendCounter1']) ) ? $r['fields']['db_spendCounter1'] : null ,
                      'db_spendCnt2'=> (isset($r['fields']['db_spendCounter2']) ) ? $r['fields']['db_spendCounter2'] : null ,
                      'db_lastUpdate'=> (isset($r['fields']['db_lastUpdate']) ) ? $r['fields']['db_lastUpdate'] : null ,
                      
                     
                   //ΔΙΑΥΓΕΙΑ sellers
                      'award0'=>(isset($r['fields']['awardAmount0']) ) ? $r['fields']['awardAmount0'] : null ,
                      'award1'=> (isset($r['fields']['awardAmount1']) ) ? $r['fields']['awardAmount1'] : null ,
                      'award2'=> (isset($r['fields']['awardAmount2']) ) ? $r['fields']['awardAmount2'] : null ,
                      'awardCnt0'=> (isset($r['fields']['awardCounter0']) ) ? $r['fields']['awardCounter0'] : null ,
                      'awardCnt1'=> (isset($r['fields']['awardCounter1']) ) ? $r['fields']['awardCounter1'] : null ,
                      'awardCnt2'=> (isset($r['fields']['awardCounter2']) ) ? $r['fields']['awardCounter2'] : null ,                    
                      'spend0'=> (isset($r['fields']['spendAmount0']) ) ? $r['fields']['spendAmount0'] : null ,
                      'spend1'=> (isset($r['fields']['spendAmount1']) ) ? $r['fields']['spendAmount1'] : null ,
                      'spend2'=> (isset($r['fields']['spendAmount2']) ) ? $r['fields']['spendAmount2'] : null ,
                      'spendCnt0'=>  (isset($r['fields']['spendCounter0']) ) ? $r['fields']['spendCounter0'] : null ,
                      'spendCnt1'=>  (isset($r['fields']['spendCounter1']) ) ? $r['fields']['spendCounter1'] : null ,
                      'spendCnt2'=> (isset($r['fields']['spendCounter2']) ) ? $r['fields']['spendCounter2'] : null ,
                      'lastUpdate'=> (isset($r['fields']['lastUpdate']) ) ? $r['fields']['lastUpdate'] : null ,
                 	   
                     
                      //ΚΗΜΔΗΣ buyers
                      'kb_contractAmountPrev'=> (isset($r['fields']['kb_contractsAmount0']) ) ? $r['fields']['kb_contractsAmount0'] : null ,
                      'kb_contractAmountCur'=> (isset($r['fields']['kb_contractsAmount1']) ) ? $r['fields']['kb_contractsAmount1'] : null ,                     
                      'kb_paymentAmountPrev'=> (isset($r['fields']['kb_paymentsAmount0']) ) ? $r['fields']['kb_paymentsAmount0'] : null ,
                      'kb_paymentAmountCur'=> (isset($r['fields']['kb_paymentsAmount1']) ) ? $r['fields']['kb_paymentsAmount1'] : null ,                      	  
                      'kb_contractItemsNo'=> (isset($r['fields']['kb_contractItemsNo']) ) ? $r['fields']['kb_contractItemsNo'] : null ,
                      'kb_paymentItemsNo'=>  (isset($r['fields']['kb_paymentItemsNo']) ) ? $r['fields']['kb_paymentItemsNo'] : null ,
                      'kb_lastUpdate'=> (isset($r['fields']['kb_lastUpdate']) ) ? $r['fields']['kb_lastUpdate'] : null ,
                      
                      //ΚΗΜΔΗΣ sellers
                      'contractAmountPrev'=> (isset($r['fields']['contractsAmount0']) ) ? $r['fields']['contractsAmount0'] : null ,
                      'contractAmountCur'=> (isset($r['fields']['contractsAmount1']) ) ? $r['fields']['contractsAmount1'] : null ,                     
                      'paymentAmountPrev'=> (isset($r['fields']['paymentsAmount0']) ) ? $r['fields']['paymentsAmount0'] : null ,
                      'paymentAmountCur'=> (isset($r['fields']['paymentsAmount1']) ) ? $r['fields']['paymentsAmount1'] : null ,                      	  
                      'contractItemsNo'=> (isset($r['fields']['contractItemsNo']) ) ? $r['fields']['contractItemsNo'] : null ,
                      'paymentItemsNo'=>  (isset($r['fields']['paymentItemsNo']) ) ? $r['fields']['paymentItemsNo'] : null ,
                      'ks_lastUpdate'=> (isset($r['fields']['ks_lastUpdate']) ) ? $r['fields']['ks_lastUpdate'] : null ,

                      //australia & London & europa & newyork
                     // australia sellers
                      'contractAmount0'=> (isset($r['fields']['contractAmount0']) ) ? $r['fields']['contractAmount0'] : null ,
                      'contractAmount1'=> (isset($r['fields']['contractAmount1']) ) ? $r['fields']['contractAmount1'] : null ,
                      'contractAmount2'=> (isset($r['fields']['contractAmount2']) ) ? $r['fields']['contractAmount2'] : null ,
                      #'contractAmount3'=> (isset($r['fields']['contractAmount3']) ) ? $r['fields']['contractAmount3'] : null , //london only
                      'contractCounter0'=>(isset($r['fields']['contractCounter0']) ) ? $r['fields']['contractCounter0'] : null ,
                      'contractCounter1'=> (isset($r['fields']['contractCounter1']) ) ? $r['fields']['contractCounter1'] : null ,
                      'contractCounter2'=> (isset($r['fields']['contractCounter2']) ) ? $r['fields']['contractCounter2'] : null ,
                      #'contractCounter3'=> (isset($r['fields']['contractCounter3']) ) ? $r['fields']['contractCounter3'] : null , //london only
                     // australia buyers
                      'ab_contractAmount0'=> (isset($r['fields']['ab_contractAmount0']) ) ? $r['fields']['ab_contractAmount0'] : null ,
                      'ab_contractAmount1'=> (isset($r['fields']['ab_contractAmount1']) ) ? $r['fields']['ab_contractAmount1'] : null ,
                      'ab_contractAmount2'=> (isset($r['fields']['ab_contractAmount2']) ) ? $r['fields']['ab_contractAmount2'] : null ,
                      'ab_contractCounter0'=>(isset($r['fields']['ab_contractCounter0']) ) ? $r['fields']['ab_contractCounter0'] : null ,
                      'ab_contractCounter1'=> (isset($r['fields']['ab_contractCounter1']) ) ? $r['fields']['ab_contractCounter1'] : null ,
                      'ab_contractCounter2'=> (isset($r['fields']['ab_contractCounter2']) ) ? $r['fields']['ab_contractCounter2'] : null ,
                      'ab_lastUpdate'=> (isset($r['fields']['ab_lastUpdate']) ) ? $r['fields']['ab_lastUpdate'] : null ,
                     

                     

                   //espa  	
                      'SubsContractsAmount'=> (isset($r['fields']['SubsContractsAmount']) ) ? $r['fields']['SubsContractsAmount'] : null ,
                      'SubsPaymentsAmount'=>(isset($r['fields']['SubsPaymentsAmount']) ) ? $r['fields']['SubsPaymentsAmount'] : null ,	
                      'SubsContractsCounter'=>(isset($r['fields']['SubsContractsCounter']) ) ? $r['fields']['SubsContractsCounter'] : null ,
                      'SubsPaymentsCounter'=> (isset($r['fields']['SubsPaymentsCounter']) ) ? $r['fields']['SubsPaymentsCounter'] : null,  
                      #'pageKind'=> (isset($r['fields']['pageKind']) ) ? $r['fields']['pageKind'] : null,  
                      'espa_lastUpdate'=> (isset($r['fields']['espa_lastUpdate']) ) ? $r['fields']['espa_lastUpdate'] : null ,
                      
                     //ted
                     'tedSumofAmounts' => (isset($r['fields']['total_amount']) ) ? $r['fields']['total_amount'] : 0 ,
                     'tedContracts' => (isset($r['fields']['contracts']) ) ? $r['fields']['contracts'] : 0,
                     
                   #  'altNames'=>(isset($r['fields']['altNames']) ) ? $r['fields']['altNames'] : null, 
                     'altNames'=>  $this->getAltNamesSolr($solrPath, $solrCore, $r['fields']['term'][0]),
                     'gemhDate'=>(isset($r['fields']['Gemhdate']) ) ? $r['fields']['Gemhdate'] : null,
                     'chamber'=>(isset($r['fields']['Chamber']) ) ? $r['fields']['Chamber'] : null,
                     'gemhNumber'=>(isset($r['fields']['GemhNumber']) ) ? $r['fields']['GemhNumber'] : null,
                     
                     #'dataDiaugeiaBuyers'=>  $this->defineSource($Db, 'dataDiaugeiaBuyers'),
                     #'dataDiaugeiaSellers'=>  $this->defineSource($Db, 'dataDiaugeiaSellers'),
                     'dataDiaugeia'=>  $this->defineSource($Db, 'dataDiaugeia',0),
                     'dataKhmdhs'=>  $this->defineSource($Db, 'dataKhmdhs',0),
                     'dataEspa'=> $this->defineSource($Db, 'dataEspa',0),
                     'dataTed'=>  $this->defineSource($Db, 'dataTed',0),
                     'dataGemh'=>  $this->defineSource($Db, 'dataGemh',0),
                     'dataAustralia'=>$this->defineSource($Db, 'dataAustralia',0),
                    # 'dataMatched'=>  $this->defineSource($Db, 'dataMatched',0),     
                     
                     'dataDiaugeiaBuyer' => $this->defineProperty($Db,'buyer',0),
                     'dataDiaugeiaSeller' => $this->defineProperty($Db,'seller',0),
                     'dataKhmdhsBuyer' => $this->defineProperty($Db,'buyer',0),
                     'dataKhmdhsSeller' => $this->defineProperty($Db,'seller',0),
                     'dataAustraliaBuyer' => $this->defineProperty($Db,'buyer',0),
                     'dataAustraliaSeller' => $this->defineProperty($Db,'seller',0),
                     'dataTedSeller'=>  $this->defineProperty($Db, 'seller',0),
                     
                     #'tedSumofAmounts' => $this->getTedDataRDF($r['fields']['term'][0], $sparqlServer)
                     'corporate_id' =>  $this->getCorporationSolr($solrPath, $corpSolrCore, $r['fields']['term'][0])
                
                     
                );	
            }
            #echo $Db.' '. $this->defineSource($Db, 'dataDiaugeia').PHP_EOL;

            $arrayElements = count($Results);
                    //echo $arrayElements;
                if  ($arrayElements <= 1000 && isset($newdata)){
                    
                    $key = $this->searchForId($newdata['vat'], $Results,'vat');
                    if ($key === NULL){
                      $Results[] = $newdata;      //insert whole record
                    }
                    else {
                         #echo 'same vat found '.$newdata['vat'].' '.$Db.PHP_EOL;
                        //check for new data
                         $Results[$key]['dataDiaugeia'] = $this->defineSource($Db, 'dataDiaugeia',$Results[$key]['dataDiaugeia']);
                         $Results[$key]['dataDiaugeiaBuyer'] = $this->defineProperty($Db, 'buyer',$Results[$key]['dataDiaugeiaBuyer']);                        
                         $Results[$key]['dataDiaugeiaSeller'] = $this->defineProperty($Db, 'seller',$Results[$key]['dataDiaugeiaSeller']);
                         
                         
                         $Results[$key]['dataKhmdhs'] = $this->defineSource($Db, 'dataKhmdhs',$Results[$key]['dataKhmdhs']);
                         $Results[$key]['dataKhmdhsBuyer'] = $this->defineProperty($Db, 'buyer',$Results[$key]['dataKhmdhsBuyer']);
                         $Results[$key]['dataKhmdhsSeller'] = $this->defineProperty($Db, 'seller',$Results[$key]['dataKhmdhsSeller']);
                         
                         $Results[$key]['dataEspa'] = $this->defineSource($Db, 'dataEspa',$Results[$key]['dataEspa']);
                         
                         $Results[$key]['dataGemh'] =  $this->defineSource($Db, 'dataGemh',$Results[$key]['dataGemh']);
                         
                         $Results[$key]['dataTed'] =  $this->defineSource($Db, 'dataTed',$Results[$key]['dataTed']);
                         $Results[$key]['dataTedSeller'] =  $this->defineProperty($Db, 'seller',$Results[$key]['dataTedSeller']);
                         $Results[$key]['dataTedBuyer'] =  $this->defineProperty($Db, 'buyer',$Results[$key]['dataTedBuyer']);
                         switch ($Db){
                             case "elod_diaugeia_sellers":
                                 $Results[$key]['award0'] =(isset($r['fields']['awardAmount0']) ) ? $r['fields']['awardAmount0'] : null;
                                 $Results[$key]['award1'] = (isset($r['fields']['awardAmount1']) ) ? $r['fields']['awardAmount1'] : null ;
                                 $Results[$key]['award2'] = (isset($r['fields']['awardAmount2']) ) ? $r['fields']['awardAmount2'] : null ;
                                 $Results[$key]['awardCnt0'] = (isset($r['fields']['awardCounter0']) ) ? $r['fields']['awardCounter0'] : null ;
                                 $Results[$key]['awardCnt1'] = (isset($r['fields']['awardCounter1']) ) ? $r['fields']['awardCounter1'] : null ;
                                 $Results[$key]['awardCnt2'] = (isset($r['fields']['awardCounter2']) ) ? $r['fields']['awardCounter2'] : null ;                   
                                 $Results[$key]['spend0'] = (isset($r['fields']['spendAmount0']) ) ? $r['fields']['spendAmount0'] : null ;
                                 $Results[$key]['spend1'] = (isset($r['fields']['spendAmount1']) ) ? $r['fields']['spendAmount1'] : null ;
                                 $Results[$key]['spend2'] = (isset($r['fields']['spendAmount2']) ) ? $r['fields']['spendAmount2'] : null ;
                                 $Results[$key]['spendCnt0'] =  (isset($r['fields']['spendCounter0']) ) ? $r['fields']['spendCounter0'] : null ;
                                 $Results[$key]['spendCnt1'] =  (isset($r['fields']['spendCounter1']) ) ? $r['fields']['spendCounter1'] : null ;
                                 $Results[$key]['spendCnt2'] = (isset($r['fields']['spendCounter2']) ) ? $r['fields']['spendCounter2'] : null ;
                                 $Results[$key]['lastUpdate'] = (isset($r['fields']['lastUpdate']) ) ? $r['fields']['lastUpdate'] : null ;
                                 $Results[$key]['score'] *= 1.2;
                                  break;
                              case "elod_diaugeia_buyers":
                                 $Results[$key]['db_award0'] =(isset($r['fields']['db_awardAmount0']) ) ? $r['fields']['db_awardAmount0'] : null;
                                 $Results[$key]['db_award1'] = (isset($r['fields']['db_awardAmount1']) ) ? $r['fields']['db_awardAmount1'] : null ;
                                 $Results[$key]['db_award2'] = (isset($r['fields']['db_awardAmount2']) ) ? $r['fields']['db_awardAmount2'] : null ;
                                 $Results[$key]['db_awardCnt0'] = (isset($r['fields']['db_awardCounter0']) ) ? $r['fields']['db_awardCounter0'] : null ;
                                 $Results[$key]['db_awardCnt1'] = (isset($r['fields']['db_awardCounter1']) ) ? $r['fields']['db_awardCounter1'] : null ;
                                 $Results[$key]['db_awardCnt2'] = (isset($r['fields']['db_awardCounter2']) ) ? $r['fields']['db_awardCounter2'] : null ;                   
                                 $Results[$key]['db_spend0'] = (isset($r['fields']['db_spendAmount0']) ) ? $r['fields']['db_spendAmount0'] : null ;
                                 $Results[$key]['db_spend1'] = (isset($r['fields']['db_spendAmount1']) ) ? $r['fields']['db_spendAmount1'] : null ;
                                 $Results[$key]['db_spend2'] = (isset($r['fields']['db_spendAmount2']) ) ? $r['fields']['db_spendAmount2'] : null ;
                                 $Results[$key]['db_spendCnt0'] =  (isset($r['fields']['db_spendCounter0']) ) ? $r['fields']['db_spendCounter0'] : null ;
                                 $Results[$key]['db_spendCnt1'] =  (isset($r['fields']['db_spendCounter1']) ) ? $r['fields']['db_spendCounter1'] : null ;
                                 $Results[$key]['db_spendCnt2'] = (isset($r['fields']['db_spendCounter2']) ) ? $r['fields']['db_spendCounter2'] : null ;
                                 $Results[$key]['db_lastUpdate'] = (isset($r['fields']['db_lastUpdate']) ) ? $r['fields']['db_lastUpdate'] : null ;
                                 $Results[$key]['score']*= 1.2;
                                 break;
                             case "elod_sellers":
                                $Results[$key]['contractAmountPrev'] = (isset($r['fields']['contractsAmount0']) ) ? $r['fields']['contractsAmount0'] : null ;
                                $Results[$key]['contractAmountCur'] = (isset($r['fields']['contractsAmount1']) ) ? $r['fields']['contractsAmount1'] : null ;                    
                                $Results[$key]['paymentAmountPrev'] = (isset($r['fields']['paymentsAmount0']) ) ? $r['fields']['paymentsAmount0'] : null ;
                                $Results[$key]['paymentAmountCur' ]= (isset($r['fields']['paymentsAmount1']) ) ? $r['fields']['paymentsAmount1'] : null ;                      	  
                                $Results[$key]['contractItemsNo'] = (isset($r['fields']['contractItemsNo']) ) ? $r['fields']['contractItemsNo'] : null ;
                                $Results[$key]['paymentItemsNo'] =  (isset($r['fields']['paymentItemsNo']) ) ? $r['fields']['paymentItemsNo'] : null ;  
                                $Results[$key]['ks_lastUpdate'] = (isset($r['fields']['ks_lastUpdate']) ) ? $r['fields']['ks_lastUpdate'] : null ;
                                $Results[$key]['score'] *= 1.2;
                                break;
                             case "elod_buyers":
                                $Results[$key]['kb_contractAmountPrev'] = (isset($r['fields']['kb_contractsAmount0']) ) ? $r['fields']['kb_contractsAmount0'] : null ;
                                $Results[$key]['kb_contractAmountCur'] = (isset($r['fields']['kb_contractsAmount1']) ) ? $r['fields']['kb_contractsAmount1'] : null ;                    
                                $Results[$key]['kb_paymentAmountPrev'] = (isset($r['fields']['kb_paymentsAmount0']) ) ? $r['fields']['kb_paymentsAmount0'] : null ;
                                $Results[$key]['kb_paymentAmountCur' ]= (isset($r['fields']['kb_paymentsAmount1']) ) ? $r['fields']['kb_paymentsAmount1'] : null ;                      	  
                                $Results[$key]['kb_contractItemsNo'] = (isset($r['fields']['kb_contractItemsNo']) ) ? $r['fields']['kb_contractItemsNo'] : null ;
                                $Results[$key]['kb_paymentItemsNo'] =  (isset($r['fields']['paymentItemsNo']) ) ? $r['fields']['kb_paymentItemsNo'] : null ; 
                                $Results[$key]['kb_lastUpdate'] = (isset($r['fields']['kb_lastUpdate']) ) ? $r['fields']['kb_lastUpdate'] : null ;
                                $Results[$key]['score'] *= 1.2;
                                break;
                             case "elod_espa_beneficiaries":
                                $Results[$key]['SubsContractsAmount'] = (isset($r['fields']['SubsContractsAmount']) ) ? $r['fields']['SubsContractsAmount'] : null ;
                                $Results[$key]['SubsPaymentsAmount'] = (isset($r['fields']['SubsPaymentsAmount']) ) ? $r['fields']['SubsPaymentsAmount'] : null ;	
                                $Results[$key]['SubsContractsCounter'] =(isset($r['fields']['SubsContractsCounter']) ) ? $r['fields']['SubsContractsCounter'] : null ;
                                $Results[$key]['SubsPaymentsCounter'] = (isset($r['fields']['SubsPaymentsCounter']) ) ? $r['fields']['SubsPaymentsCounter'] : null;
                                $Results[$key]['espa_lastUpdate'] = (isset($r['fields']['espa_lastUpdate']) ) ? $r['fields']['espa_lastUpdate'] : null ;
                                $Results[$key]['score'] *= 1.2;
                                break;
                             case "elod_main_orgv4_all":
                                $Results[$key]['gemhDate'] = (isset($r['fields']['Gemhdate']) ) ? $r['fields']['Gemhdate'] : null;
                                $Results[$key]['chamber'] =(isset($r['fields']['Chamber']) ) ? $r['fields']['Chamber'] : null;
                                $Results[$key]['gemhNumber'] = (isset($r['fields']['GemhNumber']) ) ? $r['fields']['GemhNumber'] : null;
                                $Results[$key]['score'] *= 1.1;
                                break;
                             case "elod_main_orgv4_fr":
                                $Results[$key]['gemhDate'] = (isset($r['fields']['Gemhdate']) ) ? $r['fields']['Gemhdate'] : null;
                                $Results[$key]['chamber'] =(isset($r['fields']['Chamber']) ) ? $r['fields']['Chamber'] : null;
                                $Results[$key]['gemhNumber'] = (isset($r['fields']['GemhNumber']) ) ? $r['fields']['GemhNumber'] : null;
                                 $Results[$key]['score'] *= 1.1;
                                break;
                            case "yds_big_sellers":
                                $Results[$key]['tedSumofAmounts'] = (isset($r['fields']['total_amount']) ) ? $r['fields']['total_amount'] : 0;
                                $Results[$key]['tedContracts'] = (isset($r['fields']['contracts']) ) ? $r['fields']['contracts'] : 0;
                                $Results[$key]['score'] *= 1.2;
                                break;
                          
                          
                          }
                         
                        
                         
                    } 
                      
                }
                   
            }

        }
       

        if (!empty($newdata)) {
             $AlreadyFound = 1 ; 
         }
                 
        else {
             $AlreadyFound = 0;
         }
                

    }  
    
   
    
   function defineSource($db, $field, $status){
       #echo 'defining source: '.$db.' '.$field.' '.$status.PHP_EOL;
      # 0-> clean 1-> ok 2-> do nothing
       $matchDb = $status;
       
       if (($db == 'elod_buyers' || $db == 'elod_sellers' ) && $field == 'dataKhmdhs'  &&  $matchDb == 0){
          $matchDb = 1;   # echo 'khmdhs matched';
          return  $matchDb;
       }
       if (($db == 'elod_diaugeia_buyers' || $db == 'elod_diaugeia_sellers' ) && $field == 'dataDiaugeia' &&  $matchDb == 0 ){
           $matchDb =  1;
           return  $matchDb;
       }
       
       if (($db == 'elod_australia_buyers' || $db == 'elod_australia_sellers' ) && $field == 'dataAustralia'  &&  $matchDb == 0){
            $matchDb =  1;
            return  $matchDb;
       }       
       if ($db == 'elod_espa_beneficiaries'  && $field == 'dataEspa' &&  $matchDb == 0 ){
            $matchDb =  1;
            return  $matchDb;
       }
       if ($db == 'yds_big_sellers'  && $field == 'dataTed' &&  $matchDb == 0){
           $matchDb =  1;
           return  $matchDb;
       }
       
       if (($db == 'elod_main_orgv4_all' || $db == 'elod_main_orgv4_fr' ) && $field == 'dataGemh' &&  $matchDb == 0){
            $matchDb =  1;
            return  $matchDb;
       } 
      
       return  $matchDb;
      
   } 
   
   function defineProperty($db,$field, $status){
       # $propertiesArray = ['ΦΟΡΕΑΣ','ΑΝΑΔΟΧΟΣ','ΔΙΚΑΙΟΥΧΟΣ'];
       $matchProperty = $status;
       if ($db == 'elod_diaugeia_buyers' && $matchProperty == 0){
          if ($field == 'buyer'){
               $matchProperty = 1;
               return $matchProperty;
          }
       }
       if ($db == 'elod_buyers' && $matchProperty == 0){
          if ($field == 'buyer'){
               $matchProperty = 1;
               return $matchProperty;
          }
       }
       if ($db == 'elod_australia_buyers' && $matchProperty == 0){
          if ($field == 'buyer'){
               $matchProperty = 1;
               return $matchProperty;
          }
       }
       if ($db == 'elod_diaugeia_sellers' ){
           if ($field == 'seller' && $matchProperty == 0){
               $matchProperty = 1;
               return $matchProperty;
           }
       }
       if ($db == 'elod_sellers' ){
           if ($field == 'seller' && $matchProperty == 0){
               $matchProperty = 1;
               return $matchProperty;
           }
       }
       if ($db == 'elod_australia_sellers' ){
           if ($field == 'seller' && $matchProperty == 0){
               $matchProperty = 1;
               return $matchProperty;
           }
       }
       if ($db == 'yds_big_sellers' ){
           if ($field == 'seller' && $matchProperty == 0){
               $matchProperty = 1;
               return $matchProperty;
           }
       }
      return $matchProperty;
   }
   function searchForId($id, $array, $index) { 
    foreach ($array as $key => $val) {       
            if ( $val[$index] === $id ) {
                return $key;
                        
            }
                //else echo 'not equal';
     }
        return NULL;
  } 
   function checkAFM($afm) {
   
        if (!preg_match('/^(EL){0,1}[0-9]{9}$/i', $afm))
            return false;
        if (strlen($afm) > 9)
            $afm = substr($afm, 2);

        $remainder = 0;
        $sum = 0;

        for ($nn = 2, $k = 7, $sum = 0; $k >= 0; $k--, $nn += $nn)
            $sum += $nn * ($afm[$k]);
        $remainder = $sum % 11;

        return ($remainder == 10) ? $afm[8] == '0'
                                  : $afm[8] == $remainder;
    }
    
   function getAltNamesSolr($solrPath,$solrCore,$vat){
       $ch = curl_init();
       $url = $solrPath.$solrCore."/select?indent=on&q=id:".$vat."&wt=json";
       $url = str_replace(' ','%20',$url);	
      # echo $url.PHP_EOL;
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_USERPWD, 'dimneg:dim1978');			
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-type: application/json; charset=utf-8',
	'Accept: */*'
         ));
       
       $response = curl_exec($ch); 
                
       $json = json_decode($response,true);
       curl_close($ch);	
       if (isset ($json['response']['docs'][0]['alt_names']) ){
           # return implode(', ', $json['response']['alt_names']);
           #echo implode(', ', $json['response']['alt_names']);
          # print_r($json['response']['docs'][0]['alt_names']);
            return implode(', ', $json['response']['docs'][0]['alt_names']);
       }
       #return $json;
       
   } 
   function getCorporationSolr($solrPath,$solrCore,$vat){
       $return = 0;
       $ch = curl_init();
       $url = $solrPath.$solrCore."/select?indent=on&q=db_id:".$vat."&wt=json";
       $url = str_replace(' ','%20',$url);
       #echo $url.PHP_EOL;
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_USERPWD, 'dimneg:dim1978');			
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-type: application/json; charset=utf-8',
	'Accept: */*'
         ));
       $response = curl_exec($ch);                 
       $json = json_decode($response,true);
       #echo $json['response']['docs'][0]['core'][0].PHP_EOL;
       if (isset ($json['response']['docs'][0]['core']) ){
           /*if ($json['response']['docs'][0]['core'][0] == 1){
               return 'Oracle Corporation';
           }
           else {
               return '';
           }*/
           $return = $json['response']['docs'][0]['core'][0];
           
               
       } 
       curl_close($ch);	
       return $return;
   }
   function getCorporationDetailsSolr($solrPath,$solrCore,$corpId){
       $cnt = 0;
       $ch = curl_init();
       $url = $solrPath.$solrCore."/select?indent=on&q=core:".$corpId."&rows=20&wt=json";
       $url = str_replace(' ','%20',$url);
       #echo $url.PHP_EOL;
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_USERPWD, 'dimneg:dim1978');			
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-type: application/json; charset=utf-8',
	'Accept: */*'
       ));
       $response = curl_exec($ch);                 
       $json = json_decode($response,true);
       $response = [];
       $responseArray = [
           [
               
           ]];
       curl_close($ch);
       if (isset ($json['response']['docs'][0])){
           foreach ($json['response']['docs'] as $key => $value) {
               $responseArray[$cnt]['db_id'] = $value['db_id'][0];
               $responseArray[$cnt]['name'] = $value['name'][0];
               $responseArray[$cnt]['country'] = $value['country'][0];
               $responseArray[$cnt]['uniqueShow'] = mb_convert_case($value['name'][0].$value['country'][0], MB_CASE_UPPER, "UTF-8");
               #$response[] = '['.$value['db_id'][0];
               #$response[] = $value['name'][0];
               #$response[] = $value['country'][0].']';
            $cnt++;
               
               
           }
           return  [$json['response']['docs'][0]['coreName'][0],$responseArray];
           
           #return [$json['response']['docs'][0]['coreName'][0],implode(', ', $response)];
          
       }
               
   }
   
 
   function getTedDataRDF($vat,$sparqlServer){
      
       $ch = curl_init();
       $url = $sparqlServer."/sparql/?default-graph-uri=http%3A%2F%2Fyourdatastories.eu%2Fvirtual&query=SELECT+%28sum%28xsd%3Adecimal%28%3FcurrencyValue%29%29+as+%3FsumOfAmounts%29%0D%0AFROM+<http%3A%2F%2Fyourdatastories.eu%2FTEDGreece>%0D%0AWHERE+%7B%0D%0A%3Fcontract+elod%3Abuyer+<http%3A%2F%2Flinkedeconomy.org%2Fresource%2FOrganization%2F090025586>+%3B%0D%0Apc%3AagreedPrice+%3FagreedPrice+.%0D%0A%3FagreedPrice+gr%3AhasCurrencyValue+%3FcurrencyValue+.%0D%0A%7D&format=application%2Fsparql-results%2Bjson&timeout=0&debug=on";
       #echo $url.PHP_EOL;
       curl_setopt_array($ch, array(
           CURLOPT_PORT => "8890",
           CURLOPT_URL => $url ,
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => "",
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 600,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST => "GET",
           CURLOPT_HTTPHEADER => array(
	    "cache-control: no-cache",
	    "postman-token: b79e291a-0eef-efe9-3d63-e067a3535154",
            'Content-type: application/json; charset=utf-8',
	    'Accept: */*'
	  ),
          ));
       $response = curl_exec($ch);
       $err = curl_error($ch);
       $json = json_decode($response,true);
       curl_close($ch);
       if (isset ($json['results']['bindings'][0]['sumOfAmounts']) ){
            return $json['results']['bindings'][0] ['sumOfAmounts'];
       }
   }
   
   function transliterate($word){
       $word = mb_convert_case($word, MB_CASE_UPPER, "UTF-8");
       $word = $this->cleanSpecialChar($word);
       
       $word = str_replace("Α", "A",$word);
       $word = str_replace("Β", "B",$word);
       $word = str_replace("Γ", "G",$word);
       $word = str_replace("Δ", "D",$word);
       $word = str_replace("Ε", "E",$word);
       $word = str_replace("Ζ", "Z",$word);
       $word = str_replace("Η", "I",$word);
       
       $word = str_replace("Θ", "TH",$word);
       $word = str_replace("Ι", "I",$word);
       $word = str_replace("Κ", "K",$word);
       $word = str_replace("Λ", "L",$word);
       $word = str_replace("Μ", "M",$word);
       
       $word = str_replace("Ν", "N",$word);
       $word = str_replace("Ξ", "X",$word);
       $word = str_replace("Ο", "O",$word);
       $word = str_replace("Π", "P",$word);
       $word = str_replace("Ρ", "R",$word);
       
       $word = str_replace("Σ", "S",$word);
       $word = str_replace("Τ", "T",$word);
       $word = str_replace("Υ", "Y",$word);
       $word = str_replace("Φ", "F",$word);
       
       $word = str_replace("Χ", "X",$word);
       $word = str_replace("Ψ", "PS",$word);
       $word = str_replace("Ω", "O",$word);
       
       return $word;
   }
   
   function cleanSpecialChar($word){
       $word = str_replace("Ά", "Α",$word);
       $word = str_replace("Έ", "Ε",$word);
       $word = str_replace("Ή", "Η",$word);
       $word = str_replace("Ί", "Ι",$word);
       
       $word = str_replace("Ύ", "Υ",$word);
       $word = str_replace("Ό", "Ο",$word);
       $word = str_replace("Ώ", "Ω",$word);
       $word = str_replace("Ϋ", "Υ",$word);
       $word = str_replace("Ϊ", "Ι",$word);
       return $word;
   }
   
   function defineCountry($db){
       switch ($db) {
           case 'elod_diaugeia_buyers':
               $country = 'GR';
               break;
           case 'elod_diaugeia_sellers':
               $country = 'GR';
               break;
           case 'elod_buyers':
               $country = 'GR';
               break;
           case 'elod_sellers':
               $country = 'GR';
               break;
           case 'elod_espa_beneficiaries':
               $country = 'GR';
               break;
           case 'elod_main_orgv4_all':
               $country = 'GR';
               break;
           case 'elod_main_orgv4_fr':
               $country = 'GR';
               break;
           case 'elod_australia_buyers':
               $country = 'AU';
               break;
           case 'elod_australia_sellers':
               $country = 'AU';
               break;
           case 'yds_big_sellers':
               $country = 'TED';
               break;

           default:
               break;
       }
       return $country;
   }
}
