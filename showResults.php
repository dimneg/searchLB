<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of showResults
 *
 * @author dimitris negkas
 */
class showResults {
    function presentResults($solrPath, $corpSolrCore,$advChoiceArea,$advChoiceAmount){ //test 090166291
        require_once 'collectData.php';
        global $Results;
        
        #$this->saveCsvCloud($Results, '/var/log/results.csv');
       
        #$source = ' ';
        $i = 1;
        $uniqueResults = array_filter($Results); //let 's see if there is need to group
        #print_r($uniqueResults);
        $sumResults = count($uniqueResults);
        #echo "results number:".$sumResults.PHP_EOL; 
        $sumSpend = 0;
        $sumAward = 0;
        $sumContracts = 0;
        $sumPayments = 0;
        $counterContracts = 0;
        $sumAwardSel = 0;
        $sumSpendSel = 0;
        
       
        
        echo "<table id='searchResults' class='display'  ><thead><tr><th></th></th></th> </tr></thead>";  

        echo "<tbody>";
        
        //corporation
        $corporateResultGroupping = $this->corpOccur($uniqueResults);
        $corporatesCount =  $corporateResultGroupping [0];
        if ($corporatesCount > 0){
             $solrDetails = new collectData();
             foreach ( $corporateResultGroupping[1] as $key => $value) {
                echo "<tr>";
                echo "<td style=\" text-align:left; border-left: 0px solid #ccc; font-size:15px; padding-right:0px;  width:400px;\">"; 
                $corpData = $solrDetails -> getCorporationDetailsSolr($solrPath, $corpSolrCore, $value['id']);
                $name = $corpData[0];
                echo "<a class='nameLink' href='#' target='_blank' >$name</a> ";
                echo ' <font class="dataset" color="#FF0000" style="font-size: 0.77em">[Corporate Group]</font>';
                echo ' <font class="dataset" color="#006621" style="font-size: 0.77em">High confidence</font>';
                echo '</br>'; 
               # echo "<a class='nameLink' href='#' target='_blank' >$name</a> </br>";	
                #echo ' <font class="dataset" color="#FF0000" style="font-size: 0.77em">CORPORATE GROUP</font></br> ';
                echo 'Περιέχει:</br>';
                #$grouppedCorpData = $this->_group_by(corpData[1], $name);
                $uniqueCompanies = $this->unique_multidim_array($corpData[1] , 'uniqueShow');
                foreach (  $uniqueCompanies   as $key => $value) {                   
                    echo $this->unaccent(mb_convert_case($value['name'], MB_CASE_UPPER, "UTF-8")).' ['.$value['country'].']'; 
                    echo '<BR>';                    
                }
               echo ' <font color="#FFA500" size="2">Δημόσιες Προμήθειες</font> <br> '; 
                echo '<img src="languages/images/gr.png" alt="Ελλάδα"  width="15" height="12" >';
                echo '&nbsp';
                echo 'Ελλάδα <br>' ;
                echo ' <font class="dataset" color="#006621" style="font-size: 0.77em">ΔΙΑΥΓΕΙΑ</font> '; 
                echo '&nbsp';
               # echo ' (<B>'.round(($uniqueResults[$i]['db_awardCnt0']+$uniqueResults[$i]['db_awardCnt1']+$uniqueResults[$i]['db_awardCnt2']),0).'</B>) '; 
               switch ($name) {
                   case 'ORACLE CORPORATION':
                       echo  'Οριστικοποίηση Πληρωμών: <B> €0.4M (5) </B> Κατακυρώσεις: <B> €0.0K (0) </B>  [από 6/2014 έως 7/2016]  </br>';
                       break;
                   case 'PRICEWATERHOUSECOOPERS CORPORATION':
                       echo  'Οριστικοποίηση Πληρωμών: <B> €2.3M (2) </B> Κατακυρώσεις: <B> €8.8M (4) </B> [από 6/2014 έως 7/2016]  </br>';
                       break;
                   case 'NOVARTIS CORPORATION':
                       echo  'Οριστικοποίηση Πληρωμών: <B>  €3.8M (226)</B>  Κατακυρώσεις: <B> €0.0K (0) </B>   [από 6/2014 έως 7/2016]  </br>';
                       break;
                   case 'ATOS CORPORATION':
                       echo  'Οριστικοποίηση Πληρωμών:<B> €0.0K (0) </B>  Κατακυρώσεις: <B> €0.0K (0) </B>   [από 6/2014 έως 7/2016]   </br>';
                       break;

                   default:
                       echo  'Συμβάσεις: <B> €0.0K (0) </B> Κατακυρώσεις: <B> €0.0K (0) </B> </br>';
                       break;
               }
               echo ' <font class="dataset" color="#006621" style="font-size: 0.77em">ΚΗΜΔΗΣ</font> '; 
               echo '&nbsp';
               switch ($name) {
                   case 'ORACLE CORPORATION':
                       echo  'Συμβάσεις: <B> €61.7K (4) </B> Πληρωμές: <B> €72.5K (11)  </B>  [από 2/2013 έως 2/2015]</br>';
                       break;
                   case 'PRICEWATERHOUSECOOPERS CORPORATION':
                       echo  'Συμβάσεις: <B> €56.6K (2) </B> Πληρωμές: <B> €0.2M (4) </B>  [από 2/2013 έως 2/2015] </br>';
                       break;
                   case 'NOVARTIS CORPORATION':
                       echo  'Συμβάσεις: <B>  €0.0K (0)  </B> Πληρωμές: <B> €0.0K (0)   </B>  [από 2/2013 έως 2/2015] </br>';
                       break;
                   case 'ATOS CORPORATION':
                       echo  'Συμβάσεις: <B> €0.0K (0) </B>Πληρωμές: <B> €0.0K (0)  </B>  [από 2/2013 έως 2/2015] </br>';
                       break;

                   default:
                       echo  'Συμβάσεις: <B> €0.0K (0) </B>Πληρωμές: <B> €0.0K (0)  </B> [από έως] </br>';
                       break;
               }
               
                #echo  'Συμβάσεις: <B> €0.4M (5) </B> </br>';
               # echo ' <font class="dataset" color="#006621" style="font-size: 0.77em">ΚΗΜΔΗΣ</font>'; 
                #echo '&nbsp';
                #echo  'Συμβάσεις: </br> ';
                #echo ' <font class="dataset" color="#006621" style="font-size: 0.77em">Επιδοτήσεις ΕΣΠΑ</font> ';     
                 #echo '&nbsp';
                #echo  'Συμβάσεις: </br>';
                echo '<img src="languages/images/eu.png" alt="Ευρώπη"  width="15" height="12" >';
                echo '&nbsp';
                echo 'Ευρώπη </br>' ;
              
                switch ($name) {
                   case 'ORACLE CORPORATION':
                       echo  'Συμβάσεις: <B> €0.5Β (21) </B>  [από 2006 έως 2015] </br>';
                       break;
                   case 'PRICEWATERHOUSECOOPERS CORPORATION':
                       echo  'Συμβάσεις: <B> €2.0B (87) </B> [από 2006 έως 2015] </br>';
                       break;
                   case 'NOVARTIS CORPORATION':
                       echo  'Συμβάσεις: <B> €2.6B (99)  </B>  [από 2006 έως 2015]</br>';
                       break;
                   case 'ATOS CORPORATION':
                       echo  'Συμβάσεις: <B> €2.5B (48) </B> [από 2006 έως 2015] </br>';
                       break;

                   default:
                       echo  'Συμβάσεις: <B> €0.0B (0) </B>  [από έως] </br>';
                       break;
               }
                
                echo '<img src="languages/images/au.png" alt="Αυστραλία"  width="15" height="12" >';
                echo '&nbsp';
                echo 'Αυστραλία </br> ' ;
                
                switch ($name) {
                   case 'ORACLE CORPORATION':
                       echo  'Συμβάσεις: <B> $1.1Β (209) </B>  [από 1990 έως 9/2016] </br>';
                       break;
                   case 'PRICEWATERHOUSECOOPERS CORPORATION':
                       echo  'Συμβάσεις: <B> $3.8B (41) </B>  [από 1990 έως 9/2016]</br>';
                       break;
                   case 'NOVARTIS CORPORATION':
                       echo  'Συμβάσεις: <B> $4.1M (2) </B>   [από 1990 έως 9/2016]</br>';
                       break;
                   case 'ATOS CORPORATION':
                       echo  'Συμβάσεις: <B> $0.2M (2) </B> [από 1990 έως 9/2016] </br>';
                       break;

                   default:
                       echo  'Συμβάσεις: <B> $0.0B (0) </B> [από 1990 έως 9/2016] </br>';
                       break;
               }
               
                #echo 'Εταιρίες: '.$corpData[1]. ' και άλλες';
                #echo $solrDetails -> getCorporationDetailsSolr($solrPath, $corpSolrCore, $value['id']);
                #echo $value['id']; 
                echo "</td>";
                echo "<td style=\" text-align:left; border-left: 0px solid #ccc; font-size:15px; padding-right:0px;  width:400px;\">"; 
                echo 100000;
                echo "</td>";
                echo "</tr>";

            }
        }
       
        
        
        while ($i <= $sumResults) { 
            
            $name = $this->unaccent(mb_convert_case($uniqueResults[$i]['name'],MB_CASE_UPPER, "UTF-8"));
            # $corporation = $uniqueResults[$i]['corporate_id'];
            $uniqueResults[$i]['amountClass'] = '';
            if ($advChoiceAmount !== ''){
                #echo $advChoiceAmount;
                 if ($uniqueResults[$i]['dataDiaugeiaSeller'] == 1 &&  $uniqueResults[$i]['amountClass'] ==''){
                      $uniqueResults[$i]['amountClass'] = $this->defineAmountClass( $this->fromTextToNumber($uniqueResults[$i]['spend0']) + $this->fromTextToNumber($uniqueResults[$i]['spend1']) + $this->fromTextToNumber($uniqueResults[$i]['spend2']));
                 }
                 if ($uniqueResults[$i]['dataKhmdhsSeller'] == 1 &&  $uniqueResults[$i]['amountClass'] ==''){
                         $uniqueResults[$i]['amountClass'] = $this->defineAmountClass($this->fromTextToNumber($uniqueResults[$i]['contractAmountPrev']) + $this->fromTextToNumber($uniqueResults[$i]['contractAmountCur'])) ;
                 }
                 if ($uniqueResults[$i]['dataEspa'] == 1 &&  $uniqueResults[$i]['amountClass'] ==''){
                      $uniqueResults[$i]['amountClass'] =  $this->defineAmountClass($this->fromTextToNumber($uniqueResults[$i]['SubsContractsAmount'])); 
                 }
                 if ($uniqueResults[$i]['dataAustraliaSeller'] == 1 &&  $uniqueResults[$i]['amountClass'] ==''){
                    $uniqueResults[$i]['amountClass'] = $this->defineAmountClass($this->fromTextToNumber($uniqueResults[$i]['contractAmount0']) + $this->fromTextToNumber($uniqueResults[$i]['contractAmount1'])  + $this->fromTextToNumber($uniqueResults[$i]['contractAmount2'])    ) ; 
                 }
                 if ($uniqueResults[$i]['dataTedSeller'] == 1  &&  $uniqueResults[$i]['amountClass'] ==''){                     
                     $tedSumAmount = $uniqueResults[$i]['tedSumofAmounts'];
                     $uniqueResults[$i]['amountClass'] = $this->defineAmountClass(preg_replace('/\D/', '',$tedSumAmount));
                 }
                 
                /* if ($uniqueResults[$i]['dataDiaugeiaSeller'] == 1){                     
                     $uniqueResults[$i]['amountClass'] = $this->defineAmountClass( $this->fromTextToNumber($uniqueResults[$i]['spend0']) + $this->fromTextToNumber($uniqueResults[$i]['spend1']) + $this->fromTextToNumber($uniqueResults[$i]['spend2']));
                   
                 }
                 else {
                     if ($uniqueResults[$i]['dataKhmdhsSeller'] == 1){
                         $uniqueResults[$i]['amountClass'] = $this->defineAmountClass($this->fromTextToNumber($uniqueResults[$i]['contractAmountPrev']) + $this->fromTextToNumber($uniqueResults[$i]['contractAmountCur'])) ;
                     }
                     else {
                          if ($uniqueResults[$i]['dataEspa'] == 1){
                             $uniqueResults[$i]['amountClass'] =  $this->defineAmountClass($this->fromTextToNumber($uniqueResults[$i]['SubsContractsAmount']));
                          }
                          else {
                               if ($uniqueResults[$i]['dataAustraliaSeller'] == 1){
                                  
                                     $uniqueResults[$i]['amountClass'] = $this->defineAmountClass($this->fromTextToNumber($uniqueResults[$i]['contractAmount0']) + $this->fromTextToNumber($uniqueResults[$i]['contractAmount1'])  + $this->fromTextToNumber($uniqueResults[$i]['contractAmount2'])    ) ;
                               }
                               else {
                                    if ($uniqueResults[$i]['dataTedSeller'] == 1){
                                       
                                         $tedSumAmount = $uniqueResults[$i]['tedSumofAmounts'];
                                         $uniqueResults[$i]['amountClass'] = $this->defineAmountClass(preg_replace('/\D/', '',$tedSumAmount));
                                        
                                    }
                                    else {
                                         $uniqueResults[$i]['amountClass'] = '';
                                    }
                               }
                             
                          }
                     }
                 } */
                 
            }
            
          
           # echo 'amount:'.preg_replace('/\D/', '',$uniqueResults[$i]['tedSumofAmounts']).'class: '.$uniqueResults[$i]['amountClass'].'</br>';
            
            if  (isset($uniqueResults[$i]['vat']) && ($advChoiceArea =='' || $advChoiceArea == $uniqueResults[$i]['countryName']) && ($advChoiceAmount == '' || $advChoiceAmount == $uniqueResults[$i]['amountClass']  ) ) {    
                
                if  (!is_numeric($uniqueResults[$i]['vat'])) { //boost step 2
                    $uniqueResults[$i]['score'] = bcmul(0.75,$uniqueResults[$i]['score'] ,4) ;
                }
                
            
                echo "<tr>";

                //....basic view 1...\\
                echo "<td style=\" text-align:left; border-left: 0px solid #ccc; font-size:15px; padding-right:0px;  width:400px;\">"; 
                      
                echo "<a class='nameLink' href='#' target='_blank' >$name</a> ";	
                echo ' ';
                $scorePresentation = $this->presentConfidence($sumResults,$uniqueResults[$i]['score']);             
                echo " <font class='dataset' color=$scorePresentation[1] style='font-size: 0.77em'>$scorePresentation[0]</font></br>";
                //....alt names...\\
                #if ($uniqueResults[$i]['corporate_id'] !==''){
                  #  echo " <font class='dataset' color='#006621' style='font-size: 0.77em'>$corporation</font></br> ";
                #}
                if (!empty($uniqueResults[$i]['altNames'])) {
                     echo 'Eμφανίζεται και ως: '.$uniqueResults[$i]['altNames']."</br>";
                     
                }
                 //....basic view 2...\\
                echo '<I>';
                echo $this->hide_not_avail($uniqueResults[$i]['address']);
                echo ' ';
                echo $this->hide_not_avail($uniqueResults[$i]['pc']);
                echo ' ';
                echo $this->hide_not_avail_space($uniqueResults[$i]['city']); 
                echo ' ';
                echo $this->hide_not_avail($uniqueResults[$i]['locality']); 
                echo ' ';
                echo $this->hide_not_avail_space($uniqueResults[$i]['countryName']); 
                echo ' ';
                echo $this->getVatLabel($uniqueResults[$i]['vat']). $this->hide_not_avail($uniqueResults[$i]['vat']."</br>");
                echo '</I>';
                //....show diaugeia...\\
                if ($uniqueResults[$i]['dataDiaugeia'] == 1){
                                         
                    echo ' <font class="dataset" color="#006621" style="font-size: 0.77em">ΔΙΑΥΓΕΙΑ</font></br> ';
                    if ($uniqueResults[$i]['dataDiaugeiaBuyer'] == 1){
                         echo ' <font color="#FFA500" size="1">ΦΟΡΕΑΣ</font> '; 
                         echo  'Οριστικοποίηση Πληρωμών: ';
                         $sumSpend = $this->fromTextToNumber($uniqueResults[$i]['db_spend0']) + $this->fromTextToNumber($uniqueResults[$i]['db_spend1']) + $this->fromTextToNumber($uniqueResults[$i]['db_spend2'])  ;
                         echo '<B> '.$this->fromNumberToText($sumSpend,'€').'</B>';
                         echo ' (<B>'.round(($uniqueResults[$i]['db_spendCnt0']+$uniqueResults[$i]['db_spendCnt1']+$uniqueResults[$i]['db_spendCnt2']),0).'</B>) '; 
                         echo  'Κατακυρώσεις: ';
                         $sumAward = $this->fromTextToNumber($uniqueResults[$i]['db_award0']) + $this->fromTextToNumber($uniqueResults[$i]['db_award1']) + $this->fromTextToNumber($uniqueResults[$i]['db_award2'])  ;
                         echo  '<B> '.$this->fromNumberToText($sumAward,'€').'</B>';
                         echo ' (<B>'.round(($uniqueResults[$i]['db_awardCnt0']+$uniqueResults[$i]['db_awardCnt1']+$uniqueResults[$i]['db_awardCnt2']),0).'</B>) '; 
                         echo  ' &nbsp [έως '.$uniqueResults[$i]['db_lastUpdate'].']</br>';	
                         
                         
                    }
                   # echo ' <font color="#FFA500" size="1">property</font> '; 
                    
                    if ($uniqueResults[$i]['dataDiaugeiaSeller'] == 1){
                         echo ' <font color="#FFA500" size="1">ΑΝΑΔΟΧΟΣ</font> '; 
                         echo  'Οριστικοποίηση Πληρωμών: ';
                         $sumSpend = $this->fromTextToNumber($uniqueResults[$i]['spend0']) + $this->fromTextToNumber($uniqueResults[$i]['spend1']) + $this->fromTextToNumber($uniqueResults[$i]['spend2'])  ;
                         echo '<B> '.$this->fromNumberToText($sumSpend,'€').'</B>';
                         echo ' (<B>'.round(($uniqueResults[$i]['spendCnt0']+$uniqueResults[$i]['db_spendCnt1']+$uniqueResults[$i]['spendCnt2']),0).'</B>) '; 
                         echo  'Κατακυρώσεις: ';
                         $sumAward = $this->fromTextToNumber($uniqueResults[$i]['award0']) + $this->fromTextToNumber($uniqueResults[$i]['award1']) + $this->fromTextToNumber($uniqueResults[$i]['award2'])  ;
                         echo  '<B> '.$this->fromNumberToText($sumAward,'€').'</B>';
                         echo ' (<B>'.round(($uniqueResults[$i]['awardCnt0']+$uniqueResults[$i]['awardCnt1']+$uniqueResults[$i]['awardCnt2']),0).'</B>) '; 
                         echo  ' &nbsp [έως '.$uniqueResults[$i]['lastUpdate'].']</br>';	
                    }
                    
                   
                }
                   
                   
                if ($uniqueResults[$i]['dataKhmdhs'] == 1){
                    echo ' <font class="dataset" color="#006621" style="font-size: 0.77em">ΚΗΜΔΗΣ</font></br> '; 
                    if ($uniqueResults[$i]['dataKhmdhsBuyer'] == 1){
                         echo ' <font color="#FFA500" size="1">ΦΟΡΕΑΣ</font> '; 
                         echo  'Συμβάσεις: ';
                         $sumContracts = $this->fromTextToNumber($uniqueResults[$i]['kb_contractAmountPrev']) + $this->fromTextToNumber($uniqueResults[$i]['kb_contractAmountCur'])  ;
                         echo '<B>'.$this->fromNumberToText($sumContracts,'€').'</B>';
                         echo  ' (<B>'.round(($uniqueResults[$i]['kb_contractItemsNo']),0).'</B>) ';
                         echo  'Πληρωμές: ';
                         $sumPayments = $this->fromTextToNumber($uniqueResults[$i]['kb_paymentAmountPrev']) + $this->fromTextToNumber($uniqueResults[$i]['kb_paymentAmountCur'])  ;
                         echo '<B>'.$this->fromNumberToText($sumPayments,'€').'</B>';
                         echo  ' (<B>'.round(($uniqueResults[$i]['kb_paymentItemsNo']),0).'</B>) ';
                         echo  ' &nbsp [έως '.$uniqueResults[$i]['kb_lastUpdate'].']</br>';	
                        
                        
                    }
                    if ($uniqueResults[$i]['dataKhmdhsSeller'] == 1){
                        echo ' <font color="#FFA500" size="1">ΑΝΑΔΟΧΟΣ</font> '; 
                        echo  'Συμβάσεις: ';
                        $sumContracts = $this->fromTextToNumber($uniqueResults[$i]['contractAmountPrev']) + $this->fromTextToNumber($uniqueResults[$i]['contractAmountCur'])  ;
                        echo '<B>'.$this->fromNumberToText($sumContracts,'€').'</B>';
                        echo  ' (<B>'.round(($uniqueResults[$i]['contractItemsNo']),0).'</B>) ';
                        echo  'Πληρωμές: ';
                        $sumPayments = $this->fromTextToNumber($uniqueResults[$i]['paymentAmountPrev']) + $this->fromTextToNumber($uniqueResults[$i]['paymentAmountCur'])  ;
                        echo '<B>'.$this->fromNumberToText($sumPayments,'€').'</B>';
                        echo  ' (<B>'.round(($uniqueResults[$i]['paymentItemsNo']),0).'</B>) ';
                        echo  ' &nbsp [έως '.$uniqueResults[$i]['ks_lastUpdate'].']</br>';	
                    }
                }
                if ($uniqueResults[$i]['dataEspa'] == 1){
                     echo ' <font class="dataset" color="#006621" style="font-size: 0.77em">Επιδοτήσεις ΕΣΠΑ</font></br> ';
                     echo ' <font color="#FFA500" size="1">ΔΙΚΑΙΟΥΧΟΣ</font> '; 
                     echo  'Συμβάσεις: ';
                     echo '<B>'.$uniqueResults[$i]['SubsContractsAmount'].'</B>';
                     echo  ' (<B>'.round(($uniqueResults[$i]['SubsContractsCounter']),0).'</B>) ';
                     echo  'Πληρωμές: ';
                     echo '<B>'.$uniqueResults[$i]['SubsPaymentsAmount'].'</B>';
                     echo  ' (<B>'.round(($uniqueResults[$i]['SubsPaymentsCounter']),0).'</B>) ';
                     echo  ' &nbsp [έως '.$this->convertDate($uniqueResults[$i]['espa_lastUpdate']).']</br>';
                }
                if ($uniqueResults[$i]['dataGemh'] == 1){
                     echo ' <font class="dataset" color="#800080" style="font-size: 0.77em">Γ.Ε.Μ.Η.</font></br> '; 
                     echo 'Αρ. Γ.Ε.Μ.Η.: '.$this->hide_not_avail($uniqueResults[$i]['gemhNumber']);	
                     echo ' &nbsp Επιμελητήριο: '.$this->hide_not_avail($uniqueResults[$i]['chamber']);
                     echo  ' &nbsp [Ημ/νία: '.  $this->convertDate($uniqueResults[$i]['gemhDate']).']</br>';	
                }
                
                if ($uniqueResults[$i]['dataAustralia'] == 1){
                    echo ' <font class="dataset" color="#006621" style="font-size: 0.77em">ΑΥΣΤΡΑΛΙΑ</font></br> '; 
                     if ($uniqueResults[$i]['dataAustraliaBuyer'] == 1){
                         echo ' <font color="#FFA500" size="1">ΦΟΡΕΑΣ</font> '; 
                         echo  'Συμβάσεις: ';
			 $sumContracts=$this->fromTextToNumber($uniqueResults[$i]['ab_contractAmount0']) + $this->fromTextToNumber($uniqueResults[$i]['ab_contractAmount1'])  + $this->fromTextToNumber($uniqueResults[$i]['ab_contractAmount2'])    ;
                         echo '<B>'.$this->fromNumberToText($sumContracts,'$').'</B>';
                         $counterContracts=$uniqueResults[$i]['ab_contractCounter0'] + $uniqueResults[$i]['ab_contractCounter1'] + $uniqueResults[$i]['ab_contractCounter2'] ;
                         echo  ' (<B>'.round($counterContracts,0).'</B>) ';		
                         echo  ' &nbsp [Έως '.$uniqueResults[$i]['ab_lastUpdate'].']</br>';
                         
                     }
                     if ($uniqueResults[$i]['dataAustraliaSeller'] == 1){
                         echo ' <font color="#FFA500" size="1">ΑΝΑΔΟΧΟΣ</font> '; 
                         echo  'Συμβάσεις: ';
			 $sumContracts = $this->fromTextToNumber($uniqueResults[$i]['contractAmount0']) + $this->fromTextToNumber($uniqueResults[$i]['contractAmount1'])  + $this->fromTextToNumber($uniqueResults[$i]['contractAmount2'])    ;
                         echo '<B>'.$this->fromNumberToText($sumContracts,'$').'</B>';
                         $counterContracts = $uniqueResults[$i]['contractCounter0'] + $uniqueResults[$i]['contractCounter1'] + $uniqueResults[$i]['contractCounter2'] ;
                         echo  ' (<B>'.round($counterContracts,0).'</B>) ';		
                         echo  ' &nbsp [Έως '.$uniqueResults[$i]['lastUpdate'].']</br>';
                         
                     }
                    
                }
                if ($uniqueResults[$i]['dataTed'] == 1){
                    echo ' <font class="dataset" color="#006621" style="font-size: 0.77em">T.E.D.</font></br> '; 
                    if (isset($uniqueResults[$i]['dataTedBuyer']) && $uniqueResults[$i]['dataTedBuyer'] == 1){
                         echo ' <font color="#FFA500" size="1">ΦΟΡΕΑΣ</font> '; 
                         echo  'Συμβάσεις: ';
                    }
                    if ($uniqueResults[$i]['dataTedSeller'] == 1){
                         echo ' <font color="#FFA500" size="1">ΑΝΑΔΟΧΟΣ</font> '; 
                         echo  'Συμβάσεις: ';
                         $sumAmount = $uniqueResults[$i]['tedSumofAmounts'];
                         #echo '<B>'.$sumAmount .'</B>';
                         #echo '<B>'.$this->fromNumberToText($sumAmount,'€').'</B>'; 
                         echo '<B>'.$this->fromNumberToText(preg_replace('/\D/', '', $sumAmount),'€').'</B>'; 
                         #echo '<B>'.preg_replace('/\D/', '', $this->fromNumberToText($sumAmount,'€')).'</B>';
                         $counterContracts = $uniqueResults[$i]['tedContracts'];
                         echo  ' (<B>'.round($counterContracts,0).'</B>) ';	
                         #echo '<B>'.$counterContracts .'</B>';
                         #echo '<B>'.$this->fromNumberToText($uniqueResults[$i]['tedSumofAmounts'],'€').'</B>';
                         #echo  ' (<B>'.round($uniqueResults[$i]['tedContracts'],0).'</B>) ';	
                         echo  ' &nbsp [Έως 2015]</br>';
                        
                         
                     }
                }
                #echo 'score :'.$uniqueResults[$i]['score']; 
                #echo 'class: '.$uniqueResults[$i]['amountClass']; 
                
                
                 
                echo "</td>";
                   
                echo "<td style=\" text-align:left; border-left: 0px solid #ccc; font-size:15px; padding-right:0px;  width:80px;\">";
                echo $uniqueResults[$i]['score']; //hidden
                echo "</td>";

                echo "</tr>"; 
                }
            $i++;
         }
         
        echo "</tbody>";
        echo "</table>";
            
        
    }
    
    function fromTextToNumber($text) {
        $numbered=0;
        if ((strpos($text,'M') !== false) || (strpos($text,'Μ') !== false)) {
        $numbered=str_replace("€", "",$text);
        $numbered=str_replace("$", "",$numbered);
        $numbered=str_replace("£", "",$numbered);
        $numbered=str_replace("M", "",$numbered);
        $numbered*=1000000;
        }
        else 
        if ((strpos($text,'K') !== false) || (strpos($text,'Κ') !== false)) {
        $numbered=str_replace("€", "",$text);
        $numbered=str_replace("$", "",$numbered);
        $numbered=str_replace("£", "",$numbered);
        $numbered=str_replace("K", "",$numbered);
        $numbered*=1000;
        }
        else 
        if ((strpos($text,'B') !== false) || (strpos($text,'Β') !== false)) {
        $numbered=str_replace("€", "",$text);
        $numbered=str_replace("$", "",$text);
        $numbered=str_replace("£", "",$numbered);
        $numbered=str_replace("B", "",$numbered);
        $numbered*=1000000000;
        }
        else {
        $numbered*=1;
        }

        return $numbered;
    }

    function fromNumberToText($number,$currency) {
        $texted = $currency.'0.0K'; //€0.0K
        $digits = strlen($number);
	if (($digits == 1) || (($digits) == 2)){
            $texted = $currency.'0.0K';
	}
	else
	if ($digits == 3) { //e.g. 860=0.8K
            $dividor = 1000;
            $texted = $currency.number_format(round($number/($dividor),1), 1, '.', '').'K';
	}
	else
	if ($digits == 4) { //e.g 8600->8.6K
	$dividor = 1000;
	//$texted='€'.round($number/($dividor),1).'K';
	$texted = $currency.number_format(round($number/($dividor),1), 1, '.', '').'K';
	}
	else
	if ($digits == 5) { //e.g 86000->86K
	$dividor=1000;
	$texted=$currency.number_format(round($number/($dividor),1), 1, '.', '').'K';
	}
	else
	if ($digits == 6) { //e.g 860000->0.8M (kanonika 0.9)
	$dividor=1000000;
	$texted=$currency.number_format(round($number/($dividor),1), 1, '.', '').'M';
	}
	else
	if ($digits == 7) { //e.g 8.600.000->8.6K  
	$dividor=1000000;
	$texted=$currency.number_format(round($number/($dividor),1), 1, '.', '').'M';
	}
	else
	if ($digits == 8) { //e.g 80.600.000->80.6M  
	$dividor=1000000;
	$texted=$currency.number_format(round($number/($dividor),1), 1, '.', '').'M';
	}
	else
	if ($digits == 9) { //e.g 800.600.000->0.8B  //ok
	$dividor=1000000000;
	$texted=$currency.number_format(round($number/($dividor),1), 1, '.', '').'B';
	}
	else
	if ($digits == 10) { //e.g 8.000.600.000->8B  //ok
	$dividor=1000000000;
	$texted=$currency.number_format(round($number/($dividor),1), 1, '.', '').'B';
	}
	else
	if ($digits == 11) { //e.g 80.000.600.000->80B  //ok
	$dividor=1000000000;
	$texted=$currency.number_format(round($number/($dividor),1), 1, '.', '').'B';
	}
	else {
	$texted=$currency.'0.0K'; //test only
	}

	return $texted;
}

    function hide_not_avail($field){
	if (($field=='Μη Διαθέσιμο') || ($field=='Ο ΑΦΜ δεν έχει καταχωρηθεί') ||($field=='-')){
	$field='';
	}
	return $field;
}
    function hide_not_avail_space($field){
	if (($field=='Μη Διαθέσιμο') || ($field=='') || ($field=='Ο ΑΦΜ δεν έχει καταχωρηθεί') ||($field=='-')){
	$field=' ';
	}
	else {
	$field=($field.' &nbsp');
	}
	
	return $field;
        
        
    } 
    
    function saveCsvCloud($tableName,$fileName){
	$fp = fopen($fileName, 'w');

	foreach ($tableName as $fields) {
            fputcsv($fp, $fields,"~");		
	}

	fclose($fp);	
    }
    function convertDate($date){
        if (substr($date,0,3)== 201){
             $revDate= date("d-m-Y", strtotime($date));
        }
        //$revDate='wrong'.$date;
       
        else {
            $revDate=$date;
        }
            
        return $revDate;

    }
    
    function corpOccur($uniqueResults){
        $totalCorporatesOccs = 0;
        $corpCnt = 0;
        $totalCorporatesArray = [[]];
        foreach ($uniqueResults as $key => $value) {
            if  ($value['corporate_id']!= 0){
                $key = $this->searchForId($value['corporate_id'] , $totalCorporatesArray,'id');
                if ($key === NULL){
                    $totalCorporatesArray[$corpCnt]['id'] = $value['corporate_id'];
                    $totalCorporatesArray[$corpCnt]['cnt'] =  1;
                    $corpCnt++;
                }
                else {
                    $totalCorporatesArray[$key]['cnt']++;
                }
                $totalCorporatesOccs++;
                
                
            }
        }
        #return $totalCorporatesOccs;
        return [$totalCorporatesOccs,$totalCorporatesArray];
        
    }
    function searchForId($id, $array,$index) { 
        foreach ($array as $key => $val) {       
                if ( $val[$index] === $id ) {
                    return $key;

                }
                    //else echo 'not equal';
         }
            return NULL;
    } 
    function getVatLabel($vat){
         $vatLabel ='Α.Φ.Μ. ';
         if (strlen($vat)== 7){
             $vatLabel ='ID';
         }
         if (strlen($vat)>= 10){          
            $vatLabel = 'A.B.N. ';          
                
                        
         }
         return $vatLabel;
               
                       
                                
        
    }
    function unaccent($string) {    
        $string =  str_replace('Ά','Α',$string);
        $string =  str_replace('Έ','Ε',$string);
        $string =  str_replace('Ί','Ι',$string);
        $string =  str_replace('Ή','Η',$string);
        $string =  str_replace('Ύ','Υ',$string);
        $string =  str_replace('Ό','Ο',$string);
        $string =  str_replace('Ώ','Ω',$string);
        return $string;
   }
   
    function unique_multidim_array($array, $key){
        $temp_array = [[]]; //empty array
        $i = 0;
        $key_array = [[]]; //empty array           

        foreach($array as $val){	

            #if 	(!in_array($val[$key],$key_array))   { 
            if 	(isset($val[$key]) && !in_array($val[$key],$key_array))   { 
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
                $i++;
            }
                    
            else { 
                
            }
                    

        }
        return $temp_array;

    }
    
    function presentConfidence ($countResults, $score){
        if ($countResults ==1){
            return  ['High confidence','#006621'];
        }
        if ($score > 5){
            return ['High confidence','#006621'];
        }
        else {
            if ($score > 1){
               return ['Medium confidence','#7CFC00']; 
            }
            else {
                if ($countResults == 1 && $score == 1 ){
                    return  ['High confidence','#006621'];
                }
                else {
                     if ($score == 1){
                         return ['Low confidence','#FFA500'];
                     }
                     else {
                         return ['No confidence','#FF0000'];
                     }
                }
            }
        }
    }
    
    function defineAmountClass($amount){
        $class = '';
        if ($amount > 2000000000){
            $class = 4;
           # echo $class; 
            return $class;
        }
        else {
            if ($amount > 2000000 && $amount <= 2000000000){
                 $class = 3;
                  #echo $class; 
                 return $class;
            }
            else {
                 if ($amount > 2000 && $amount <= 2000000){
                      $class = 2;
                       #echo $class; 
                      return $class;
                 }
                 else {
                     if ($amount > 0){
                         $class = 1;
                         # echo $class; 
                         return $class;
                     }
                     
                 }
            }
        }
        # echo 'empty class'; 
        return $class;
    }
    
    
}
