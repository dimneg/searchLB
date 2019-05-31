<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rdf
 *
 * @author dimitris negkas
 */
class Rdf {
    public static function requestDiaugeiaPaymentItem($connection_url, $vatid){ 
		 
        $type = 'Organization';
	$url = 	 "                  
                    PREFIX gr: <http://purl.org/goodrelations/v1#>
                                PREFIX dcterms: <http://purl.org/dc/terms/>

                                SELECT (count(distinct ?payment as ?payment0))  (sum(xsd:decimal(?amount)) as ?totalAmount)
                                FROM <http://linkedeconomy.org/Diavgeia>
                                WHERE {
                                ?payment elod:hasExpenditureLine ?expenditureLine ;
                                         elod:buyer ?buyer .
                                ?expenditureLine elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;
                                                 elod:amount ?ups .
                                ?ups gr:hasCurrencyValue ?amount .
                                FILTER NOT EXISTS {?payment elod:hasCorrectedDecision ?correctedDecision} .
                                FILTER NOT EXISTS {?ups elod:riskError \"1\"^^xsd:boolean} .
                                }
                ";
		// is curl installed?
		if (!function_exists('curl_init')){ 
			die('CURL is not installed!');
		}
	       #print_r($url);
		$post = [
			'query' => $url,
			'format' => 'application/sparql-results+json',
			'timeout' => '0',
			'debug' => 'on'
		];
	 
		// get curl handle
		$curl= curl_init();
				
		curl_setopt_array($curl, array(
		  CURLOPT_PORT => "8890",
		  CURLOPT_URL => $connection_url,
		  //CURLOPT_USERPWD => $username . ":" . $password,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 600,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,		  		  
		  CURLOPT_POST => 1,
		  CURLOPT_POSTFIELDS => $post
		));

		$response = curl_exec($curl);
                echo 'requestDiaugeiaPaymentItem:'. print_r($response);
                $json = json_decode($response,true);
                print_r($json);
                if(isset ($json['results'])) {
                    $amount = 0;
                    $records = 0;
                    $cnt =0;
                    foreach ($json['results']['bindings'] as $key => $record) {
                        if (isset($record['totalAmount'])){
                            $amount += $record['totalAmount']['value'];
                            #$amount = $record['totalAmount']['value'];
                            $cnt += $record['callret-0']['value'];
                            $records++;
                        }
                        
                        
                    }
                 }
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "Error #:" . $err;		 
		} 
		
		#return [$amount,$records];
                return [$amount, $cnt];
                
	}
        
        
   public static function requestDiaugeiaExpenseApprovalItem($connection_url, $vatid){
		 
        $type = 'Organization';
	$url = 	 "                  
                    PREFIX elod: <http://linkedeconomy.org/ontology#>
                    PREFIX gr: <http://purl.org/goodrelations/v1#>
                    PREFIX dcterms: <http://purl.org/dc/terms/>

                    SELECT distinct (str(?date) AS ?date) (xsd:decimal(?amount) AS ?amount)
                    FROM <http://linkedeconomy.org/Diavgeia>
                    WHERE {
                    ?expenseApproval elod:hasExpenditureLine ?expenditureLine ;
                                    dcterms:issued ?date ;
                                    rdf:type elod:ExpenseApprovalItem .
                    ?expenditureLine elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;
                                         elod:amount ?ups .
                    ?ups gr:hasCurrencyValue ?amount .
                    FILTER NOT EXISTS {?expenseApproval elod:hasCorrectedDecision ?correctedDecision} .
                    FILTER NOT EXISTS {?ups elod:riskError \"1\"^^xsd:boolean} .
                    }
                ";
		// is curl installed?
		if (!function_exists('curl_init')){ 
			die('CURL is not installed!');
		}
	 
		$post = [
			'query' => $url,
			'format' => 'application/sparql-results+json',
			'timeout' => '0',
			'debug' => 'on'
		];
	 
		// get curl handle
		$curl= curl_init();
				
		curl_setopt_array($curl, array(
		  CURLOPT_PORT => "8890",
		  CURLOPT_URL => $connection_url,
		  //CURLOPT_USERPWD => $username . ":" . $password,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 600,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,		  		  
		  CURLOPT_POST => 1,
		  CURLOPT_POSTFIELDS => $post
		));

		$response = curl_exec($curl);
                $json = json_decode($response,true);
                if(isset ($json['results'])) {
                    $amount = 0;
                    $records = 0;
                    foreach ($json['results']['bindings'] as $key => $record) {
                        $amount += $record['amount']['value'];
                        $records++;
                        
                    }
                 }
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "Error #:" . $err;		 
		} 
		
		return [$amount,$records];
                
	}     
        
   public static function requestEspaContracts($connection_url, $vatid){
      $type = 'Organization';
                $qry = "
                    SELECT distinct (str(?startDate) as ?date) (xsd:decimal(?amount) as ?amount)
                    FROM <http://linkedeconomy.org/NSRF>
                    WHERE {
                    ?project a elod:PublicWork ;
                             elod:hasRelatedContract ?contract .
                    ?contract elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;   
                              elod:price ?ups ;
                              pc:startDate ?startDate .
                    ?ups gr:hasCurrencyValue ?amount.
                    }
                ";
                 if (!function_exists('curl_init')){ 
			die('CURL is not installed!');
            }
	 
		$post = [
			'query' => $qry,
			'format' => 'application/sparql-results+json',
			'timeout' => '0',
			'debug' => 'on'
		];
                
                $curl= curl_init();
				
		curl_setopt_array($curl, array(
		  CURLOPT_PORT => "8890",
		  CURLOPT_URL => $connection_url,
		  //CURLOPT_USERPWD => $username . ":" . $password,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 600,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,		  		  
		  CURLOPT_POST => 1,
		  CURLOPT_POSTFIELDS => $post
		));

		$response = curl_exec($curl);
                #print_r ($response);
                $json = json_decode($response,true);
                #print_r($json);
                if(isset ($json['results'])) {
                    $amount = 0;
                    $records = 0;
                    foreach ($json['results']['bindings'] as $key => $record) {
                        $amount += $record['amount']['value'];
                        $records++;
                        
                    }
                 }
                 $err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "Error #:" . $err;		 
		} 
		
		return [$amount,$records];
    }
}
