<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use DateTime;
use App\User;
use Illuminate\Routing\UrlGenerator;
use View;
use App;
use DB;
use Lang;
use Response;
use Illuminate\Support\Facades\Redirect;

class CompanyProfileController extends Controller
{
    protected $user;
    
    /**
     * Create a new controller instance1.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
        
        $this->middleware(function ($request, $next) {            
            $this->user = Auth::user();            
            return $next($request);
        });
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $lang = null, $vatid, $section = null) //Request $request,
    {  	        
       
        // $c2 = (new NotificationsController)->getAndUpdateNotifications();
        // return $c2;

        $search =  $request->input("s"); // s=1 is from pro search
        $product = $this->user->product; // product = 2 is pro package

        $urlparam = "";

        $lang = $this->setLanguage($lang);

        $dayName = $this->getDayName($lang);
        $monthName = $this->getMonthName($lang);
        $dayMonthNum = date("d");
        $monthNum = date('n', strtotime("first day of -1 month"));
        $monthNamePrev = $this->getMonthNamePrev($lang);
        $stst=$this->getliststatsfromuser($this->user->id); 
    	
		if ($lang == "en") {
            $qry_lang = "en";
		} else {
            $qry_lang = "el";
		}

        // Transform vatid to apply in queries
        $vatid = str_replace("-","/",$vatid);
        //echo $vatid;

        $allowedCompany = DB::select(DB::raw("SELECT cc_vatid,cc_cpa_code FROM cpa_companies 
                                                INNER JOIN cpa_to_order ON cto_cpacode = cc_cpa_code                                                
                                                WHERE cc_vatId = '".$vatid."' AND cto_user_id = ".$this->user->id));


            $company = $this->getCompanyData($vatid, $qry_lang);
            //if($company["name"]=="-") {
                //return Redirect::to($lang.'/notfound');
           // }

//////////////// XSOZ - Check if compny is really allowed when filters of maincpa and orgtype are applied           
/////////////// Filter orgtype 
            $allowedtmp=false;
            for($allo=0;$allo<count($allowedCompany);$allo++) {                
                    if(isset($allowedCompany[$allo]->cc_vatid)) {                       
                        $orgcpaopt_old_tmp = DB::select(DB::raw("SELECT * FROM users_onlyorgtype WHERE user_id = ".$this->user->id));
                        $orgcpafs= DB::select(DB::raw("SELECT * FROM users_orgtype_filter WHERE user_id = ".$this->user->id." AND cpa ='".str_replace('/', '.', $allowedCompany[$allo]->cc_cpa_code)."'"));  
                        //return var_dump($orgcpafs);

                        if(count($orgcpaopt_old_tmp)>0 && count($orgcpafs)>0) { 
                            foreach ($orgcpafs as $value) {  
                                if($value->orgtype==$company["orgTypeCode_nolang"]) {                                
                                     $allowedtmp=true;
                                     break;
                                } 
                            }  
                        } else {                            
                            $allowedtmp=true;
                            break;
                        }
                }
            }
           
            if($allowedtmp==false) { $allowedCompany=array(); }
//////////// Filter maincpa
            $allowedtmp=false;
            for($allo=0;$allo<count($allowedCompany);$allo++) {    
            //return 0;            
                    if(isset($allowedCompany[$allo]->cc_vatid)) {                       
                        $cpaopt_old_tmp = DB::select(DB::raw("SELECT * FROM users_onlymaincpa WHERE user_id = ".$this->user->id));
                        $cpafs= DB::select(DB::raw("SELECT * FROM users_cpas_filter WHERE user_id = ".$this->user->id." AND cpa_to_filter ='".str_replace('/', '.', $allowedCompany[$allo]->cc_cpa_code)."'"));
                        if(count($cpaopt_old_tmp)>0 && count($cpafs)>0) { 
                            foreach ($cpafs as $value) {                      
                                if($value->cpa_to_filter==str_replace('/', '.', substr($company["cpa"],-11))) {                                                                
                                     $allowedtmp=true;
                                     break;
                                }  
                            }                          
                        } else {
                            $allowedtmp=true;
                            break;
                        }
                }
            }
           
            if($allowedtmp==false) { $allowedCompany=array(); }


/////////////// XSOZ - End
//return $allowedCompany;

            
            // if($company["validVat"]!="1") {
            //     $vatid=$vatid."-".$company["gemhNumber"];
            // }
			//get all lists	
            //return 	$vatid;
			$listdata = DB::select(DB::raw("SELECT list_id, list_name
											FROM lists
											WHERE list_active=1 AND list_user_id = ".$this->user->id));
			//get lists that company exists	
            if($company["validVat"]==1) {							
			$listexists = DB::select(DB::raw("SELECT ctl_list_id
											FROM company_to_list
											WHERE ctl_company_id='".$vatid."'"));
            } else {
                $listexists = DB::select(DB::raw("SELECT ctl_list_id
                                                FROM company_to_list
                                                WHERE ctl_company_id='".str_replace("/","-",$vatid)."'"));
            }
// return $company["validVat"];
//          return $listexists;
			if(count($listexists)<1) {
                $listexists = DB::select(DB::raw("SELECT ctl_list_id
                                                FROM company_to_list
                                                WHERE ctl_company_id='".str_replace("/","-",$vatid)."'"));
            }	
            			
			$exarr = array();
							
			for ($i=0; $i<= count($listexists)-1; $i++) {
				$exarr[$i] = $listexists[$i]->ctl_list_id;
			}
			
			for ($i=0; $i<= count($listdata)-1; $i++) {
				if (in_array($listdata[$i]->list_id, $exarr)) {
					$listdata[$i]->exists = 1;
				} else {
					$listdata[$i]->exists = 0;
				}
			}
			
			
            //return $company;      
            //insert to last-searches
            $referer = app('Illuminate\Routing\UrlGenerator')->previous();
            
            //if (substr($referer, strrpos($referer, "/")+1) == "pro") {
            //if ($product == 2) {

                    $urlparam = "?s=1";

                    if ($search == 1) {      

                        // get mumber of profiles for specific user for this month
                        // $searchCount = DB::select(DB::raw("SELECT count(*) as count
                        //            FROM searches_by_user
                        //            WHERE sbu_user=".$this->user->id."
                        //            AND (MONTH(sbu_datetime) = MONTH(NOW()) AND YEAR(sbu_datetime) = YEAR(NOW()))"));
                        // $searchCount = DB::select(DB::raw("SELECT count(*) as count
                        //            FROM searches_by_user
                        //            WHERE sbu_user=".$this->user->id));

                        // get the limit for specific user
                        // $userCalls = DB::select(DB::raw("SELECT company_profile_calls
                        //            FROM users
                        //            WHERE id=".$this->user->id." LIMIT 1"));  

                        //$stst=$this->getliststatsfromuser($this->user->id);         
                       //  if ($stst[1] <= $stst[0]) {
                        //if ($searchCount[0]->count <= $userCalls[0]->company_profile_calls) {

                            $insertSearch = DB::insert("INSERT INTO searches_by_user (
                                        sbu_vatid, 
                                        sbu_user, 
                                        sbu_datetime, 
                                        sbu_title                                       
                                    ) VALUES (
                                        'company/".str_replace("/", "-",addslashes($vatid))."',
                                        ".$this->user->id.",
                                        NOW(), 
                                        '".addslashes($company["name"])."' 
                                    ) ON DUPLICATE KEY UPDATE                                
                                        sbu_datetime = NOW(),
                                        sbu_title = '".addslashes($company["name"])."'
                                    ");

                            
                        
                     //   } else {
                            //return $stst[0] . " - " . $stst[1];
                      //      return redirect()->route('lock', ['lang' => $lang]);
                     //   } 

                    } //else {                       
                        //return redirect()->route('home', ['lang' => $lang]);
                   // }
          //  } else { 
            //      if ($search == 1) {
            //         $resCalls = $this->checkProfileCalls($vatid);
            //         if (!$resCalls) {                   
            //             return redirect()->route('lock', ['lang' => $lang]);
            //         }
            //      }
            // }
            $resCalls = $this->XSOZcheckProfileCalls($vatid,$stst[0] - $stst[1],isset($allowedCompany[0]->cc_vatid));
            
            if ($resCalls[0]==false && isset($allowedCompany[0]->cc_vatid)==false) {  
            //return   var_dump($resCalls[0]==false) . var_dump(!isset($allowedCompany["cc_vatid"]));                           
                return redirect()->route('lock', ['lang' => $lang]);
            } 

            if (!$resCalls[1]) {                               
                $stst[1]+=1;
            } 
            
             
			// COMPANY PROFILE DATA END //
			
			if ($section == "debtors") {
                
                try {
    				$debtors = $this->getDebtors($vatid, $qry_lang)[0];                    
                    $debtors_date = date_format(date_create(explode("T",$this->getDebtors($vatid, $qry_lang)[1][0]["callret-0"]["value"])[0]),"d-m-Y");
                    $years_debtors="2018";
    				if (!empty($debtors[0])) {	
                        if(array_key_exists("amountEfka2017", $debtors[0])) {	
                           $years_debtors="2017";						
    					   $debtors[0]["amountEfkalabel"]["value"] = number_format($debtors[0]["amountEfka2017"]["value"], 0, ",", ".");                           
                           } else {
                            $debtors[0]["amountEfkalabel"]["value"] = 0;
                           }
                        if(array_key_exists('amount2017', $debtors[0])) {			
    					   $debtors[0]["amountlabel"]["value"] = number_format($debtors[0]["amount2017"]["value"], 0, ",", ".");	
                           } else {
                            $debtors[0]["amountlabel"]["value"] = 0;
                           }	
                         if(array_key_exists("amountEfka2018", $debtors[0]) || array_key_exists('amount2018', $debtors[0])) {
                             $debtors[0]["amountEfkalabel"]["value"] = 0;
                             $debtors[0]["amountlabel"]["value"] = 0;
                             $years_debtors="2018";  
                           
                             if(array_key_exists("amountEfka2018", $debtors[0])) {                          
                               $debtors[0]["amountEfkalabel"]["value"] = number_format($debtors[0]["amountEfka2018"]["value"], 0, ",", ".");
                               } 
                             if(array_key_exists('amount2018', $debtors[0])) {           
                               $debtors[0]["amountlabel"]["value"] = number_format($debtors[0]["amount2018"]["value"], 0, ",", ".");   
                               } 

                           }  			
    				}

                    // Transform vatid back to link format
                    $vatid = str_replace("/","-",$vatid); 

                    //print_r($debtors);
                } catch (\Exception $e) {
                    return response()->view('errors.500', [], 500);
                }     

				return view('company.debtors')->with(compact('stst', 'years_debtors','urlparam', 'company', 'listdata', 'debtors', 'debtors_date', 'vatid', 'section', 'monthNum', 'monthName', 'dayName', 'dayMonthNum', 'lang'));
			}

            if ($section == "ownership") {
                
                try {

                    $ownership = null;
                    $ownership_participations = null;

                    $ownership = DB::connection('mysql_gemh')->select("select p.name as person,p.vatNumber as personVat, p.isGsisCompany, o.partnerPercentage, o.totalAmountAllPartners * o.partnerPercentage  as amount, m.name as company, m.brandname as brand , m.vatid as companyVat , o.rowconfidence, m.gemhnumber, m.status  
                                from OwnershipData o
                                inner join PersonalData p                
                                on p.id = o.personId                
                                inner join Main m                
                                on m.gemhNumber = o.gemhNumber          
                                where m.vatid = '".$vatid."' and o.rowconfidence > 0.0");


                    if ($ownership) {    
                        $total = 0;

                        for ($i=0; $i<=count($ownership)-1; $i++) {
                            

                            if ($ownership[$i]->isGsisCompany == 0) {                            
                               
                                $qry_results = "SELECT ?perUri
                                                FROM <http://linkedeconomy.org/Persons> 
                                                WHERE { 
                                                ?perUri gr:vatID \"".$ownership[$i]->personVat."\"^^<http://www.w3.org/2001/XMLSchema#string> 
                                                }
                                                LIMIT 1";
 
                                $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                                $res_results = json_decode($json_requestGsis, true); 

                                if (!empty($res_results["results"]["bindings"])) {
                                    $ownership[$i]->vatId = str_replace("http://linkedeconomy.org/resource/Person/", "", $res_results["results"]["bindings"][0]["perUri"]["value"]);
                                } else {
                                    $ownership[$i]->vatId = null;
                                }   

                            } else {
                                
                                $qry_results = "SELECT ?orgUri
                                                FROM <http://linkedeconomy.org/GemhOrganizations> 
                                                WHERE { 
                                                ?orgUri gr:vatID \"".$ownership[$i]->personVat."\"^^<http://www.w3.org/2001/XMLSchema#string> 
                                                }
                                                LIMIT 1";

                                $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                                $res_results = json_decode($json_requestGsis, true); 

                                if (!empty($res_results["results"]["bindings"])) {
                                    $ownership[$i]->vatId = str_replace("http://linkedeconomy.org/resource/Organization/", "", $res_results["results"]["bindings"][0]["orgUri"]["value"]);
                                } else {
                                    $ownership[$i]->vatId = null;
                                }
                                               
                            }
                            

                            $total = $total + $ownership[$i]->amount;
                        }  

                        if (abs($total) > 1000 || abs($total) <= 1000) {
                            $ownership[0]->amount_label = round($total / 1000, 2)."K";                            
                        } 
                        if (abs($total) > 1000000) {
                            $ownership[0]->amount_label = round($total / 1000000, 2)."M";
                        } 
                        if (abs($total) > 1000000000) {
                            $ownership[0]->amount_label = round($total / 1000000000, 2)."B";                                
                        }
                        
                    }

                    //print_r($ownership);

                    $statement = DB::statement("SET SESSION group_concat_max_len = 1000000");
                    
                    $ownership_participations = DB::connection('mysql_gemh')->select("select mc.vatId, mc.gemhnumber, mc.status, mc.name, o.totalAmountAllPartners * o.partnerPercentage as amount,o.partnerPercentage, cl.title as cpaName
                                ,(select (group_concat(p2.vatNumber,'#',p2.isGsisCompany,'#',p2.name,'#',o2.partnerPercentage SEPARATOR '~') )from  OwnershipData o2 inner join PersonalData p2 on o2.personID = p2.id where gemhnumber = o.gemhNumber and o2.personID<>o.personID  ) as coPartners
                                from OwnershipData o
                                inner join PersonalData p
                                on o.personId=p.id                                
                                inner join Main mc
                                on mc.gemhNumber = o.gemhNumber
                                inner join companyCpa cc 
                                on cc.gemhnumber = mc.gemhNumber
                                inner join CpaList cl
                                 on cl.apiCpa=cc.apiCpa
                                where p.vatNumber='".$vatid."'
                                and cc.main = 1
                                and o.rowconfidence > 0.0
                                group by mc.vatId
                                order by name asc");           
                    


                    if ($ownership_participations) {

                        $total_participations = 0;

                        for ($i=0; $i<=count($ownership_participations)-1; $i++) {
                            $total_participations = $total_participations + $ownership_participations[$i]->amount;
                        }                     
 
                        if (abs($total_participations) > 1000 || abs($total_participations) <= 1000) {
                            $ownership_participations[0]->amount_label = round($total_participations / 1000, 2)."K";                            
                        } 
                        if (abs($total_participations) > 1000000) {
                            $ownership_participations[0]->amount_label = round($total_participations / 1000000, 2)."M";
                        } 
                        if (abs($total_participations) > 1000000000) {
                            $ownership_participations[0]->amount_label = round($total_participations / 1000000000, 2)."B";                                
                        }

//print_r($ownership_participations);
                        
                        for ($i=0; $i<=count($ownership_participations)-1; $i++) 
                        {
                            if ($ownership_participations[$i]->amount<>0 && $total_participations<>0) {
                                $ownership_participations[$i]->total_percentage = round($ownership_participations[$i]->amount / $total_participations, 2);
                            } else {
                                $ownership_participations[$i]->total_percentage = 0;
                            }
                            
                            if ($ownership_participations[$i]->coPartners) {
                                
                                $countpartner=0;

                                
                                foreach(explode('~', $ownership_participations[$i]->coPartners) as $copartner) {
                                
                                    $cpartner = explode('#', $copartner);
                                                                     
                                    $ownership_participations[$i]->partners[$countpartner]["vatid"] = $cpartner[0];
                                    $ownership_participations[$i]->partners[$countpartner]["isGsisCompany"] = $cpartner[1];
                                   

                                    if ($cpartner[1] == 0) {
                            
                                        $qry_results = "SELECT ?perUri
                                                        FROM <http://linkedeconomy.org/Persons> 
                                                        WHERE { 
                                                        ?perUri gr:vatID \"".trim($cpartner[0])."\"^^<http://www.w3.org/2001/XMLSchema#string> 
                                                        }
                                                        LIMIT 1";

                                                        //echo $qry_results."<br>";


                                        $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                                        $res_results = json_decode($json_requestGsis, true); 

                                        if (!empty($res_results["results"]["bindings"])) {
                                            $ownership_participations[$i]->partners[$countpartner]["vatid_new"] = str_replace("http://linkedeconomy.org/resource/Person/", "", $res_results["results"]["bindings"][0]["perUri"]["value"]);
                                        } else {
                                            $ownership_participations[$i]->partners[$countpartner]["vatid_new"] = null;
                                        }   

                                    } else {
                                        
                                        $qry_results = "SELECT ?orgUri
                                                        FROM <http://linkedeconomy.org/GemhOrganizations> 
                                                        WHERE { 
                                                        ?orgUri gr:vatID \"".trim($cpartner[0])."\"^^<http://www.w3.org/2001/XMLSchema#string> 
                                                        }
                                                        LIMIT 1";

                                                        //echo $qry_results."<br>";

                                        $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                                        $res_results = json_decode($json_requestGsis, true); 

                                        if (!empty($res_results["results"]["bindings"])) {
                                            $ownership_participations[$i]->partners[$countpartner]["vatid_new"] = str_replace("http://linkedeconomy.org/resource/Organization/", "", $res_results["results"]["bindings"][0]["orgUri"]["value"]);
                                        } else {
                                            $ownership_participations[$i]->partners[$countpartner]["vatid_new"] = null;
                                        }
                                                       
                                    }

                                    $ownership_participations[$i]->partners[$countpartner]["name"] = $cpartner[2];
                                    //echo $cpartner[3]."<br>";
                                    if (array_key_exists(3, $cpartner)) {
                                       
                                        $ownership_participations[$i]->partners[$countpartner]["percentage"] = $cpartner[3];    
                                    } else {

                                        $ownership_participations[$i]->partners[$countpartner]["percentage"] = 0.0;    
                                    }
                                     
                                    $countpartner++;
                                }

                            }

                        }
                    }

                    $stores = $this->getStores($vatid);

                    // Transform vatid back to link format
                    $vatid = str_replace("/","-",$vatid); 

                    $list_export_limit = $this->user->list_export_limit;

////////////// XSOZ START
                    if($company["orgTypeCode_nolang"]!="FR") {
                         $ownership_date_qr = DB::connection('mysql_gemh')->select("select  max(o.announceddate) as lastUpdate
                                                                                    from OwnershipData o
                                                                                    inner join Main m
                                                                                    on m.gemhNumber = o.gemhNumber 
                                                                                    where m.vatid = '".$vatid."'  and  o.rowconfidence > 0.0
                                                                                    "); 
                         $ownership_date=date_format(date_create($ownership_date_qr[0]->lastUpdate),"d-m-Y");
                         
                    } else {
                        $ownership_date="";
                    }
///////////// XSOZ END

                } catch (\Exception $e) {
                    return response()->view('errors.500', [], 500);
                }

                //print_r($ownership_participations);

                return view('company.ownership')->with(compact('stst', 'urlparam', 'stores', 'ownership_participations', 'ownership', 'ownership_date', 'list_export_limit', 'company', 'listdata', 'ownership', 'vatid', 'section', 'monthNum', 'monthName', 'dayName', 'dayMonthNum', 'lang'));
            }

            if ($section == "results") {

                try {
                    $fresults = $this->getFinancialResults($vatid, $qry_lang);
                   
                    if ($fresults) {
                        for ($i=0; $i<=count($fresults)-1; $i++) {

                            if (abs($fresults[$i]["ebit"]["value"]) > 1000 || abs($fresults[$i]["ebit"]["value"]) <= 1000) {
                                $fresults[$i]["ebit_label"] = round($fresults[$i]["ebit"]["value"] / 1000, 2)."K";                            
                            } 
                            if (abs($fresults[$i]["ebit"]["value"]) > 1000000) {
                                $fresults[$i]["ebit_label"] = round($fresults[$i]["ebit"]["value"] / 1000000, 2)."M";
                            } 
                            if (abs($fresults[$i]["ebit"]["value"]) > 1000000000) {
                                $fresults[$i]["ebit_label"] = round($fresults[$i]["ebit"]["value"] / 1000000000, 2)."B";                                
                            }
                            
                            if (abs($fresults[$i]["turnover"]["value"]) > 1000 || abs($fresults[$i]["turnover"]["value"]) <= 1000) {
                                $fresults[$i]["turnover_label"] = round($fresults[$i]["turnover"]["value"] / 1000, 2)."K";
                            }
                            if (abs($fresults[$i]["turnover"]["value"]) > 1000000) {
                                $fresults[$i]["turnover_label"] = round($fresults[$i]["turnover"]["value"] / 1000000, 2)."M";
                            }
                            if (abs($fresults[$i]["turnover"]["value"]) > 1000000000) {
                                $fresults[$i]["turnover_label"] = round($fresults[$i]["turnover"]["value"] / 1000000000, 2)."B";
                            }
                            
                            if($fresults[$i]["year"]["value"]==2016) {
                                $tmpchangevalues=$fresults[$i]["turnover_label"];
                                $fresults[$i]["turnover_label"]=$fresults[$i]["ebit_label"];
                                $fresults[$i]["ebit_label"]=$tmpchangevalues;
                            }
                            $fresults[$i]["size"] = $this->getCompanySize($fresults[$i]["ebit"]["value"], $fresults[$i]["employeesNo"]["value"]);
                            
                        }
                    }

                    // Transform vatid back to link format
                    $vatid = str_replace("/","-",$vatid);

                    //print_r($fresults);

                     $qry_results_date = "SELECT distinct (str(?legalName ) as ?legalName) (str(?year) as ?year) (xsd:decimal(?ebit) as ?ebit) (xsd:decimal(?turnover) as ?turnover) (str(?employeesNo) as ?employeesNo) ?lastUpdate
                            FROM <http://linkedeconomy.org/Financials>
                            FROM <http://linkedeconomy.org/GemhOrganizations>
                            WHERE {
                            ?finances a elod:Finances ;
                                             dcterms:modified ?lastUpdate ;
                                             elod:financesOf <http://linkedeconomy.org/resource/Organization/".$vatid."> ;
                                             elod:financesOfYear ?financesOfYear .
                            ?financesOfYear dcterms:year ?year .
                            optional { ?financesOfYear  elod:ebit ?ebit . }
                            optional { ?financesOfYear elod:turnover ?turnover . }
                            optional { ?financesOfYear elod:employeesNo ?employeesNo . }                           
                            <http://linkedeconomy.org/resource/Organization/".$vatid."> a foaf:Organization .
                            optional{ <http://linkedeconomy.org/resource/Organization/099369013> gr:legalName ?legalName . }
                            }";

                    $json_results_date = $this->request($qry_results_date, config('sparql.connection_username'), config('sparql.connection_password'));            
                    $res_results_date_tmp = json_decode($json_results_date, true); 
                    if($res_results_date_tmp["results"]["bindings"]!=NULL) {                    
                        $res_results_date = date_format(date_create(explode("+",$res_results_date_tmp["results"]["bindings"][0]["lastUpdate"]["value"])[0]),"d-m-Y");
                    } else {
                        $res_results_date="";
                    }
                    //return var_dump($fresults);

                } catch (\Exception $e) {
                    return response()->view('errors.500', [], 500);
                }     

                return view('company.results')->with(compact('stst', 'urlparam', 'company', 'res_results_date', 'listdata', 'fresults', 'vatid', 'section', 'monthNum', 'monthName', 'dayName', 'dayMonthNum', 'lang'));
            }

            if ($section == "announcements") {

                try{

                    $gemhNumber = $company["gemhNumber"];

                    $announcements = $this->getAnnouncementsStats($gemhNumber,$vatid);

                    //print_r($announcements); 
//return $announcements;
                    // Transform vatid back to link format
                    $vatid = str_replace("/","-",$vatid); 
                    //return $announcements;
                } catch (\Exception $e) {
                    return response()->view('errors.500', [], 500);
                }    
//return $company;
                return view('company.announcements')->with(compact('stst', 'urlparam', 'company', 'listdata', 'gemhNumber', 'announcements', 'vatid', 'section', 'monthNum', 'monthName', 'dayName', 'dayMonthNum', 'lang'));
            }

            if ($section == "management") {

                if($company["orgTypeCode_nolang"]=="FR") $mans = $this->getManStats($vatid); 
                   else $mans="";

                try {
                
                    $list_export_limit = $this->user->list_export_limit;

                    //print_r($company);
                    $id = $company["vatId"];

                    if ($company["orgTypeCode_nolang"] == "FR") {

                        $ceoQry = DB::connection('mysql_gemh')->select("SELECT count(*) as ceo
                                    FROM MemberPosition mp
                                    inner join Positions p
                                    on p.id = mp.positionId
                                    inner join
                                    PositionsList pl1
                                    on pl1.id = p.mappedPosition_1
                                    inner join
                                    PositionsList pl2
                                    on pl2.id = p.mappedPosition_2
                                    inner join PersonalData pd
                                    on pd.id=mp.personId
                                    where pd.vatNumber='".$id."'
                                    and (
                                    pl1.id in ('3','4','11') or
                                    pl2.id in ('3','4','11'))");

                        $management["ceo"] = $ceoQry[0]->ceo;

                        $presidentQry = DB::connection('mysql_gemh')->select("SELECT count(*) as president
                                    FROM MemberPosition mp
                                    inner join Positions p
                                    on p.id = mp.positionId
                                    inner join
                                    PositionsList pl1
                                    on pl1.id = p.mappedPosition_1
                                    inner join
                                    PositionsList pl2
                                    on pl2.id = p.mappedPosition_2
                                    inner join PersonalData pd
                                    on pd.id=mp.personId
                                    where pd.vatNumber='".$id."'
                                    and (
                                    pl1.id in ('1','2','5') or
                                    pl2.id in('1','2','5'))");
                        
                        $management["president"] = $presidentQry[0]->president;

                        $membersQry = DB::connection('mysql_gemh')->select("SELECT count(*) as member
                                    FROM MemberPosition mp
                                    inner join Positions p
                                    on p.id = mp.positionId
                                    inner join
                                    PositionsList pl1
                                    on pl1.id = p.mappedPosition_1
                                    inner join
                                    PositionsList pl2
                                    on pl2.id = p.mappedPosition_2
                                    inner join PersonalData pd
                                    on pd.id=mp.personId
                                    where pd.vatNumber='".$id."'
                                    and pl1.id in ('0','6','7','8','9','10','12','13','14','15','16','99')");

                        $management["member"] = $membersQry[0]->member;
                
                        $listQry = DB::connection('mysql_gemh')->select("SELECT m.name as companyName, m.vatId as compVat, m.status, mp.positionFreeText, mp.isActive, mp.fromDate, mp.toDate,fd.fekNumber, fd.fekDate
                                    FROM MemberPosition mp
                                    inner join PersonalData pd
                                    on pd.id = mp.personId
                                    inner join Positions p
                                    on p.id = mp.positionId
                                    inner join
                                    PositionsList pl1
                                    on pl1.id = p.mappedPosition_1
                                    inner join
                                    PositionsList pl2
                                    on pl2.id = p.mappedPosition_2
                                    inner join Main m
                                    on m.gemhnumber = mp.gemhnumber
                                    left join
                                    FekProd.Documents fd
                                    on mp.fekId = fd.id  
                                    where pd.vatNumber='".$id."'
                                    and mp.forDelete <> 1 
                                    and mp.fekId is null
                                    order by mp.toDate desc
");
                                
                        for ($i=0; $i<=count($listQry)-1; $i++) 
                        {
                        
                            $qry_results = "SELECT ?orgUri
                                            FROM <http://linkedeconomy.org/GemhOrganizations> 
                                            WHERE { 
                                            ?orgUri gr:vatID \"".$listQry[$i]->compVat."\"^^<http://www.w3.org/2001/XMLSchema#string> 
                                            }
                                            LIMIT 1";

                            $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                            $res_results = json_decode($json_requestGsis, true); 

                            if (!empty($res_results["results"]["bindings"])) {
                                $listQry[$i]->vatId = str_replace("http://linkedeconomy.org/resource/Organization/", "", $res_results["results"]["bindings"][0]["orgUri"]["value"]);
                            } else {
                                $listQry[$i]->vatId = null;
                            }
                        }
                        
                        $management["list"] = $listQry;

//fek project
                       
                         $listQryF = DB::connection('mysql_gemh')->select("SELECT m.name as companyName, m.vatId as compVat, m.status, mp.positionFreeText, mp.isActive, mp.fromDate, mp.toDate,fd.fekNumber, fd.fekDate, fd.pdfDbLink
                                    FROM MemberPosition mp
                                    inner join PersonalData pd
                                    on pd.id = mp.personId
                                    inner join Positions p
                                    on p.id = mp.positionId
                                    inner join
                                    PositionsList pl1
                                    on pl1.id = p.mappedPosition_1
                                    inner join
                                    PositionsList pl2
                                    on pl2.id = p.mappedPosition_2
                                    inner join Main m
                                    on m.gemhnumber = mp.gemhnumber
                                    left join
                                    FekProd.Documents fd
                                    on mp.fekId = fd.id  
                                    where pd.vatNumber='".$id."'
                                    and mp.forDelete <> 1 
                                    and mp.fekId is not null
                                    order by mp.toDate desc
");

                        for ($i=0; $i<=count($listQryF)-1; $i++) 
                        {
                        
                            $qry_results = "SELECT ?orgUri
                                            FROM <http://linkedeconomy.org/GemhOrganizations> 
                                            WHERE { 
                                            ?orgUri gr:vatID \"".$listQryF[$i]->compVat."\"^^<http://www.w3.org/2001/XMLSchema#string> 
                                            }
                                            LIMIT 1";

                            $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                            $res_results = json_decode($json_requestGsis, true); 

                            if (!empty($res_results["results"]["bindings"])) {                                
                                $listQryF[$i]->vatId = str_replace("http://linkedeconomy.org/resource/Organization/", "", $res_results["results"]["bindings"][0]["orgUri"]["value"]);
                            } else {
                                $listQryF[$i]->vatId = null;
                            }

                        }
                       
                        $management["list_fek"]=$listQryF;
                   
                    } else {

                        $gemhNumber = $company["gemhNumber"];
                 
                        // $qry_results = "SELECT (count(?role) as ?count) 
                        //                 FROM <http://linkedeconomy.org/AdministrativeBoards> 
                        //                 WHERE {
                        //                 ?board a elod:BoardEntity ;
                        //                        elod:boardOf <http://linkedeconomy.org/resource/Organization/".$id."> ;
                        //                        org:hasMembership ?membership .
                        //                 ?membership org:role ?role .
                        //                 filter ( (regex(str(?role), \"http://linkedeconomy.org/resource/Role/CEO\")) || (regex(str(?role), \"http://linkedeconomy.org/resource/Role/SubstituteCEO\")) || (regex(str(?role), \"http://linkedeconomy.org/resource/Role/Admin\")) || (regex(str(?role), \"http://linkedeconomy.org/resource/Role/ExecutiveDirector\")) )
                        //                 }
                        //                 ";

                        // $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                        // $res_results = json_decode($json_requestGsis, true); 

                        // $management["ceo"] = $res_results["results"]["bindings"][0]["count"]["value"];
                       

                        $ceoQry = DB::connection('mysql_gemh')->select("SELECT count(*) as ceo
                                    FROM MemberPosition mp
                                    inner join Positions p
                                    on p.id = mp.positionId
                                    inner join
                                    PositionsList pl1
                                    on pl1.id = p.mappedPosition_1
                                    inner join
                                    PositionsList pl2
                                    on pl2.id = p.mappedPosition_2
                                    where gemhnumber='".$gemhNumber."'
                                    and (
                                    pl1.id in ('3','4','11') or
                                    pl2.id in ('3','4','11'))");

                        $management["ceo"] = $ceoQry[0]->ceo;

                        // $qry_results = "SELECT (count(?role) as ?count) 
                        //                 FROM <http://linkedeconomy.org/AdministrativeBoards> 
                        //                 WHERE {
                        //                 ?board a elod:BoardEntity ;
                        //                        elod:boardOf <http://linkedeconomy.org/resource/Organization/".$id."> ;
                        //                        org:hasMembership ?membership .
                        //                 ?membership  org:role ?role .
                        //                 filter ( (regex(str(?role), \"http://linkedeconomy.org/resource/Role/VicePresident\")) || (regex(str(?role), \"http://linkedeconomy.org/resource/Role/Chairman\")) || (regex(str(?role), \"http://linkedeconomy.org/resource/Role/SubstitutePresident\")) )
                        //                 }
                        //                 ";

                        // $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                        // $res_results = json_decode($json_requestGsis, true); 

                        // $management["president"] = $res_results["results"]["bindings"][0]["count"]["value"];


                        $presidentQry = DB::connection('mysql_gemh')->select("SELECT count(*) as president
                                    FROM MemberPosition mp
                                    inner join Positions p
                                    on p.id = mp.positionId
                                    inner join
                                    PositionsList pl1
                                    on pl1.id = p.mappedPosition_1
                                    inner join
                                    PositionsList pl2
                                    on pl2.id = p.mappedPosition_2
                                    where gemhnumber='".$gemhNumber."'
                                    and (
                                    pl1.id in ('1','2','5') or
                                    pl2.id in('1','2','5'))");
                        
                        $management["president"] = $presidentQry[0]->president;

                        $membersQry = DB::connection('mysql_gemh')->select("SELECT count(*) as member
                                    FROM MemberPosition mp
                                    inner join Positions p
                                    on p.id = mp.positionId
                                    inner join
                                    PositionsList pl1
                                    on pl1.id = p.mappedPosition_1
                                    inner join
                                    PositionsList pl2
                                    on pl2.id = p.mappedPosition_2
                                    where gemhnumber='".$gemhNumber."'
                                    and
                                    pl1.id in ('0','6','7','8','9','10','12','13','14','15','16','99')");

                         $management["member"] = $membersQry[0]->member;

                     
                        $listQry = DB::connection('mysql_gemh')->select("SELECT mp.name, mp.positionFreeText, mp.fromDate, mp.toDate, mp.isActive, pd.vatNumber as vatId, pd.isGsisCompany, fd.fekNumber, fd.fekDate, fd.pdfDbLink
                                    FROM MemberPosition mp
                                    inner join Positions p
                                    on p.id = mp.positionId
                                    inner join
                                    PositionsList pl1
                                    on pl1.id = p.mappedPosition_1
                                    inner join
                                    PositionsList pl2
                                    on pl2.id = p.mappedPosition_2
                                    left join
                                    PersonalData pd 
                                    on pd.id = mp.personId 
                                    
                                    left join
                                    FekProd.Documents fd
                                    on mp.fekId = fd.id  
                                    where
                                    gemhnumber='".$gemhNumber."'
                                    and mp.forDelete <> 1
                                    and mp.fekId is null 
                                    order by mp.toDate desc"); 
                   
 
//return json_encode($listQry);
                        // $qry_results = "SELECT (count(?role) as ?count) 
                        //                 FROM <http://linkedeconomy.org/AdministrativeBoards> 
                        //                 WHERE {
                        //                 ?board a elod:BoardEntity ;
                        //                        elod:boardOf <http://linkedeconomy.org/resource/Organization/".$id."> ;
                        //                        org:hasMembership ?membership .
                        //                 ?membership org:role ?role .
                        //                 filter ( (regex(str(?role), \"http://linkedeconomy.org/resource/Role/Member\")) || (regex(str(?role), \"http://linkedeconomy.org/resource/Role/GeneralMember\")) || (regex(str(?role), \"http://linkedeconomy.org/resource/Role/HeteroityMember\")) || (regex(str(?role), \"http://linkedeconomy.org/resource/Role/NonExecutiveMember\")) || (regex(str(?role), \"http://linkedeconomy.org/resource/Role/IndependentNonExecutiveMember\")) || (regex(str(?role), \"http://linkedeconomy.org/resource/Role/ExecutiveMember\")) )
                        //                 }
                        //                 ";

                        // $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                        // $res_results = json_decode($json_requestGsis, true); 

                        //$management["member"] = $res_results["results"]["bindings"][0]["count"]["value"];
                      

                        //  $qry_results = "SELECT ?membership, ?memberUri, str(?memberName) as ?memberName, str(?active) as ?active, str(?startDate) as ?startDate, str(?endDate) as ?endDate
                        //                 FROM <http://linkedeconomy.org/AdministrativeBoards> 
                        //                 WHERE {
                        //                 ?board a elod:BoardEntity ;
                        //                        elod:boardOf <http://linkedeconomy.org/resource/Organization/".$id."> ;
                        //                        org:hasMembership ?membership .
                        //                 ?membership gr:name ?memberName .
                        //                 optional { ?membership pc:startDate ?startDate . }
                        //                 optional { ?membership elod:endDate ?endDate . }
                        //                 optional { ?membership org:member ?memberUri . }
                        //                 optional { ?membership elod:memberActive ?active . }
                        //                 }
                        //                 ";

                        // $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                        // $res_results = json_decode($json_requestGsis, true); 

                        //$listQry_t = $res_results["results"]["bindings"];
                        // for($i=0;$i<count($listQry_t);$i++) {
                        //     $listQry[$i]= (object)[];                            
                        //     $listQry[$i]->name=$listQry_t[$i]["memberName"]["value"];
                        //     if($listQry_t[$i]["active"]["value"]=="true") {
                        //         $listQry[$i]->isActive=1;
                        //     } else {
                        //         $listQry[$i]->isActive=0;
                        //     }
                        //     if(isset($listQry_t[$i]["startDate"]["value"])) {
                        //         $listQry[$i]->fromDate=date_format(date_create($listQry_t[$i]["startDate"]["value"]),'Y-m-d');
                        //     }

                        //     if(isset($listQry_t[$i]["endDate"]["value"])) {
                        //         $listQry[$i]->toDate=date_format(date_create($listQry_t[$i]["endDate"]["value"]),'Y-m-d');
                        //     } else {
                        //         $listQry[$i]->toDate=null;
                        //     }
                        //     $tempd=explode("/",$listQry_t[$i]["memberUri"]["value"]);
                        //     $listQry[$i]->vatNumber=$tempd[5];
                        //     if($tempd[4]=="Person") {
                        //         $listQry[$i]->isGsisCompany=addslashes(0);
                        //     } else {
                        //         $listQry[$i]->isGsisCompany=addslashes(1);

                            // $listQry[$i]->isGsisCompany=addslashes(0);
                            // if(isset($listQry_t[$i]["memberUri"]["value"])) {
                            //     $tempd=explode("/",$listQry_t[$i]["memberUri"]["value"]);
                            //     $listQry[$i]->vatId=$tempd[5];

                            //     if($tempd[4]=="Person") {
                            //         $listQry[$i]->isGsisCompany=addslashes(0);
                            //     } else {
                            //         $listQry[$i]->isGsisCompany=addslashes(1);
                            //     }
                            //}

                          // $qry_results_t = "SELECT str(?elRole) as ?elRole
                          //                   FROM <http://linkedeconomy.org/AdministrativeBoards> 
                          //                   WHERE {
                          //                   <http://linkedeconomy.org/resource/Membership/".explode("/",$listQry_t[$i]["membership"]["value"])[5]."/".explode("/",$listQry_t[$i]["membership"]["value"])[6]."> org:role ?role .
                          //                   ?role skos:prefLabel ?elRole .
                          //                   filter (lang(?elRole) = 'el')
                          //                   }";


                            // $qry_results_t = "SELECT str(?elRole) as ?elRole
                            //                 FROM <http://linkedeconomy.org/AdministrativeBoards> 
                            //                 WHERE {
                            //                 <".$listQry_t[$i]["membership"]["value"]."> org:role ?role .
                            //                 ?role skos:prefLabel ?elRole .
                            //                 filter (lang(?elRole) = 'el')
                            //                 }";

                            // $json_request_t = $this->request($qry_results_t, config('sparql.connection_username'), config('sparql.connection_password'));      

                            // $res_results_t = json_decode($json_request_t, true); 
                          
                            // $listQry[$i]->positionFreeText="";
                            
                            // $jj=0;
                            // foreach ($res_results_t["results"]["bindings"] as $keyk => $valuev) {
                            //     //return $valuev;
                            //     if($jj>0) $listQry[$i]->positionFreeText.=", ";
                            //     $listQry[$i]->positionFreeText.=$valuev["elRole"]["value"];
                            //     $jj++;
                            // }


                      //  }
                      //$listQry=json_encode($listQry);
                       //return json_encode($listQry) ;  

                        for ($i=0; $i<=count($listQry)-1; $i++) {
//return $listQry[$i]->isGsisCompany;
  //                           if ($listQry[$i]->isGsisCompany == 0) {                            
  // //return 10;                           
  //                               $qry_results = "SELECT ?perUri
  //                                               FROM <http://linkedeconomy.org/Persons> 
  //                                               WHERE { 
  //                                               ?perUri gr:vatID \"".$listQry[$i]->vatNumber."\"^^<http://www.w3.org/2001/XMLSchema#string> 
  //                                               }
  //                                               LIMIT 1";

  //                               $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
  //                               $res_results = json_decode($json_requestGsis, true); 

  //                               if (!empty($res_results["results"]["bindings"])) {
  //                                   $listQry[$i]->vatId = str_replace("http://linkedeconomy.org/resource/Person/", "", $res_results["results"]["bindings"][0]["perUri"]["value"]);
  //                               } else {
  //                                   $listQry[$i]->vatId = null;
  //                               }   

  //                           } else {
              //  return $listQry[$i]->vatNumber;                
                            //     $qry_results = "SELECT ?orgUri
                            //                     FROM <http://linkedeconomy.org/GemhOrganizations> 
                            //                     WHERE { 
                            //                     ?orgUri gr:vatID \"".$listQry[$i]->vatNumber."\"^^<http://www.w3.org/2001/XMLSchema#string> 
                            //                     }
                            //                     LIMIT 1";

                            //     $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                            //     $res_results = json_decode($json_requestGsis, true); 

                            //     if (!empty($res_results["results"]["bindings"])) {
                            //         $listQry[$i]->vatId = str_replace("http://linkedeconomy.org/resource/Organization/", "", $res_results["results"]["bindings"][0]["orgUri"]["value"]);
                            //     } else {
                            //         $listQry[$i]->vatId = null;
                            //     }
                                               
                            // }

                            /////////// XSOZ START  - get dioikisi se alles etairies sto management tab
                           // if(isset($listQry[$i]->vatNumber)) {
                               $listQryd = DB::connection('mysql_gemh')->select("SELECT m.vatId as companyVat, m.name as companyName, m.status as companyStatus
                                            FROM MemberPosition mp
                                            inner join PersonalData pd
                                            on pd.id = mp.personId
                                            inner join Positions p
                                            on p.id = mp.positionId
                                            inner join
                                            PositionsList pl1
                                            on pl1.id = p.mappedPosition_1
                                            inner join
                                            PositionsList pl2
                                            on pl2.id = p.mappedPosition_2
                                            inner join Main m
                                            on m.gemhnumber = mp.gemhnumber
                                            where pd.vatNumber= '".$listQry[$i]->vatId."' and m.vatId <> '".$id."'
                                             order by mp.toDate desc
                                            "); 
                               //return var_dump(array_key_exists("companyName", $listQryd));
                                if (!empty($listQryd)) {
                                   
                                    for($ii=0;$ii<count($listQryd);$ii++) {           
                                        $listQry[$i]->mantoother[$ii][0] = $listQryd[$ii]->companyName;//substr(explode(" ",$listQryd[$ii]->companyName)[0] . " " . explode(" ",$listQryd[$ii]->companyName)[1] . " " . explode(" ",$listQryd[$ii]->companyName)[2] . " " . explode(" ",$listQryd[$ii]->companyName)[3],0,55) . '...';
                                        $listQry[$i]->mantoother[$ii][1] = $listQryd[$ii]->companyVat;
                                        $listQry[$i]->mantoother[$ii][2] = $listQryd[$ii]->companyStatus;
                                        //return $listQry[$i]->mantoother[$ii][2] . '<br>';
                                    }
                                } else {                                    
                                        // $listQry[$i]->mantoother[0][0] = null;
                                        // $listQry[$i]->mantoother[0][1] = null;
                                        // $listQry[$i]->mantoother[0][2] = null;
                                
                                    //return $listQry[$i]->mantoother[$ii][0] . '<br>';
                                }
                            }
                                //return var_dump($listQry[$i]);
                            //////////// XSOZ END

                       // }

                        for ($i=0; $i<=count($listQry)-1; $i++) 
                        {
                        
                            $qry_results = "SELECT ?orgUri
                                            FROM <http://linkedeconomy.org/GemhOrganizations> 
                                            WHERE { 
                                            ?orgUri gr:vatID \"".$listQry[$i]->vatId."\"^^<http://www.w3.org/2001/XMLSchema#string> 
                                            }
                                            LIMIT 1";

                            $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                            $res_results = json_decode($json_requestGsis, true); 

                            if (!empty($res_results["results"]["bindings"])) {
                                $listQry[$i]->vatId = str_replace("http://linkedeconomy.org/resource/Organization/", "", $res_results["results"]["bindings"][0]["orgUri"]["value"]);
                            } else {
                                //$listQry[$i]->vatId = null;
                            }
                        }
                        
                        $management["list"] = $listQry;
                        //return json_encode($management["list"]);

                        if($gemhNumber!="-") { 
                         $listQryF = DB::connection('mysql_gemh')->select("SELECT mp.name, mp.positionFreeText, mp.fromDate, mp.toDate, mp.isActive, pd.vatNumber as vatId, pd.isGsisCompany, fd.fekNumber, fd.fekDate, fd.pdfDbLink
                                    FROM MemberPosition mp
                                    inner join Positions p
                                    on p.id = mp.positionId
                                    inner join
                                    PositionsList pl1
                                    on pl1.id = p.mappedPosition_1
                                    inner join
                                    PositionsList pl2
                                    on pl2.id = p.mappedPosition_2
                                    left join
                                    PersonalData pd 
                                    on pd.id = mp.personId 
                                    
                                    left join
                                    FekProd.Documents fd
                                    on mp.fekId = fd.id  
                                    where
                                    gemhnumber='".$gemhNumber."'
                                    and mp.forDelete <> 1
                                    and mp.fekId is not null
                                    order by mp.toDate desc");
                        } else {
                         $listQryF = DB::connection('mysql_gemh')->select("SELECT mp.name, mp.positionFreeText, mp.fromDate, mp.toDate, mp.isActive, pd.vatNumber as vatId, pd.isGsisCompany, fd.fekNumber, fd.fekDate, fd.pdfDbLink
                                    FROM  NonGemhMemberPosition mp
                                    inner join Positions p
                                    on p.id = mp.positionId
                                    inner join
                                    PositionsList pl1
                                    on pl1.id = p.mappedPosition_1
                                    inner join
                                    PositionsList pl2
                                    on pl2.id = p.mappedPosition_2
                                    left join
                                    PersonalData pd 
                                    on pd.id = mp.personId 
                                    
                                    left join
                                    FekProd.Documents fd
                                    on mp.fekId = fd.id  
                                    where
                                    vatId='".$id."'
                                    and mp.forDelete <> 1
                                    order by mp.toDate desc"); 
                        }        


                         for ($i=0; $i<=count($listQryF)-1; $i++) {

                            /////////// XSOZ START  - get dioikisi se alles etairies sto management tab
                          
                               $listQrydF = DB::connection('mysql_gemh')->select("SELECT m.vatId as companyVat, m.name as companyName, m.status as companyStatus
                                            FROM MemberPosition mp
                                            inner join PersonalData pd
                                            on pd.id = mp.personId
                                            inner join Positions p
                                            on p.id = mp.positionId
                                            inner join
                                            PositionsList pl1
                                            on pl1.id = p.mappedPosition_1
                                            inner join
                                            PositionsList pl2
                                            on pl2.id = p.mappedPosition_2
                                            inner join Main m
                                            on m.gemhnumber = mp.gemhnumber
                                            where pd.vatNumber= '".$listQryF[$i]->vatId."' and m.vatId <> '".$id."'
                                             order by mp.toDate desc
                                            "); 
                               //return json_encode($listQrydF);
                               //return var_dump(array_key_exists("companyName", $listQryd));
                               
                                if (!empty($listQrydF)) {
                                   
                                    for($ii=0;$ii<count($listQrydF);$ii++) {           
                                        $listQryF[$i]->mantoother[$ii][0] = $listQrydF[$ii]->companyName;
                                        $listQryF[$i]->mantoother[$ii][1] = $listQrydF[$ii]->companyVat;
                                        $listQryF[$i]->mantoother[$ii][2] = $listQrydF[$ii]->companyStatus;
                                       
                                    }
                                } else {                                    
                                       
                                }
                            }

                        for ($i=0; $i<=count($listQryF)-1; $i++) 
                        {
                        
                            $qry_results = "SELECT ?orgUri
                                            FROM <http://linkedeconomy.org/GemhOrganizations> 
                                            WHERE { 
                                            ?orgUri gr:vatID \"".$listQryF[$i]->vatId."\"^^<http://www.w3.org/2001/XMLSchema#string> 
                                            }
                                            LIMIT 1";

                            $json_requestGsis = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));            
                            $res_results = json_decode($json_requestGsis, true); 

                            if (!empty($res_results["results"]["bindings"])) {
                                $listQryF[$i]->vatId = str_replace("http://linkedeconomy.org/resource/Organization/", "", $res_results["results"]["bindings"][0]["orgUri"]["value"]);
                            } else {
                                //$listQry[$i]->vatId = null;
                            }
                        }
                       
                        $management["list_fek"]=$listQryF;
                    }    

                    // Transform vatid back to link format
                    $vatid = str_replace("/","-",$vatid);                    

                } catch (\Exception $e) {
                    return response()->view('errors.500', [], 500);
                }
                
///// XSOZ START - FILTER same company to table
                //return  $management;
               
                    $im=0;
                    foreach($management["list"] as $k=>$v) {
                        if(isset($v->mantoother)) {
                            $array=$v->mantoother;
                            $key=1;
                            $temp_array = array(); 
                            $i = 0; 
                            $key_array = array(); 
                            
                            foreach($array as $val) { 
                                if (!in_array($val[$key], $key_array)) { 
                                    $key_array[$i] = $val[$key]; 
                                    $temp_array[$i] = $val; 
                                } 
                                $i++; 
                            } 
                            $management["list"][$im]->mantoother=$temp_array; 
                        }
                        $im++;
                    }
                    //return $management;
///// XSOZ END - FILTER same company to table
                //return  $management;
                
                return view('company.management')->with(compact('stst', 'urlparam', 'management', 'mans', 'list_export_limit', 'monthNamePrev', 'status_box', 'company', 'listdata', 'fsize', 'vatid', 'section', 'monthNum', 'monthName', 'dayName', 'dayMonthNum', 'lang'));
            }

            if ($section == "money") {

                //try {
                    
                    $stats = array();
                    $graph = array();

                    $stats["diaugeia"] = $this->getMoneyStats("diaugeia", $vatid);
                    $stats["kimdis"] = $this->getMoneyStats("kimdis", $vatid);
                    $stats["espa"] = $this->getMoneyStats("espa", $vatid);
                    $stats["ted"] = $this->getMoneyStats("ted", $vatid);
 
                    $cpv = $this->getMoneyCpv($vatid, $qry_lang);
                 
                    // Transform vatid back to link format
                    $vatid = str_replace("/","-",$vatid);  

                //} catch (\Exception $e) {
                //   return response()->view('errors.500', [], 500);
                //}     

//print_r($stats);
//die;
                    //return json_encode($cpv);
                return view('company.money')->with(compact('stst', 'urlparam', 'cpv', 'stats', 'monthNamePrev', 'company', 'listdata', 'vatid', 'section', 'monthNum', 'monthName', 'dayName', 'dayMonthNum', 'lang'));
            }

            if ($section == "basic") {
                
                try {
                
                    $fsize = array();
                    $fresults = $this->getFinancialResults($vatid, $qry_lang);

                    if ($fresults) {
                        //return $fresults[0]["employeesNo"]["value"];
                        $fsize = $this->getCompanySize($fresults[0]["ebit"]["value"], $fresults[0]["employeesNo"]["value"]);

                    }

                    $mainCpa = str_replace("http://linkedeconomy.org/resource/CPA/","",$company["cpa"]);

                    $status_box = $this->getCompanyCpaStatus($mainCpa);

                    // Transform vatid back to link format
                    $vatid = str_replace("/","-",$vatid);  

                    //print_r($company);

////////////////// XSOZ - ENIMEROSI start

                    $feed=json_decode('[{"type":"basic","date":"06-02-2018","message":"hello world"},{"type":"basic","date":"06-02-2018","message":"hello third world"},{"type":"management","date":"06-02-2018","message":"hello world"},{"type":"ownership","date":"06-02-2018","message":"hello world"},{"type":"money","date":"06-02-2018","message":"hello world"}]',true);

////////////////// XSOZ - ENIMEROSI end
//return var_dump(strlen($this->cytopage($qry_lang,$vatid)));
                    if($company["orgTypeCode_nolang"]=="FR") {                         
                        if(strlen($this->cytopage($qry_lang,$vatid))>350) {
                            $company["showCyto"]="yes";                   
                        } else {
                            $company["showCyto"]="no";
                        }    
                    } else {
                        $company["showCyto"]="no";
                    }
                     

                } catch (\Exception $e) {
                    return response()->view('errors.500', [], 500);
                }    

                //print_r(json_encode($status_box) . json_encode($mainCpa));
//return var_dump($status_box) . var_dump($company);
                //return json_encode($company);
                $lang = "gr";
                App::setLocale($lang);
                return view('company.profile_bak')->with(compact('stst', 'feed','urlparam', 'monthNamePrev', 'status_box', 'company', 'listdata', 'fsize', 'vatid', 'section', 'monthNum', 'monthName', 'dayName', 'dayMonthNum', 'lang'));
            }

    }

    public function partialgraph(Request $request)
    {       
        $lang = $request->input("lang");
        $vatid = $request->input("vatid");
        $type = $request->input("type");

        $graph["simvolaia_espa"] = $this->getMoneyGraph("simvolaia_espa", $vatid, $type);                
        $graph["eggriseis_diaugeia"] = $this->getMoneyGraph("eggriseis_diaugeia", $vatid, $type);
        $graph["pliromes_diaugeia"] = $this->getMoneyGraph("pliromes_diaugeia", $vatid, $type);
        $graph["anatheseis_diaugeia"] = $this->getMoneyGraph("anatheseis_diaugeia", $vatid, $type);                
        $graph["katakyrwseis_diaugeia"] = $this->getMoneyGraph("katakyrwseis_diaugeia", $vatid, $type);
        $graph["simvaseis_kimdis"] = $this->getMoneyGraph("simvaseis_kimdis", $vatid, $type);
        $graph["pliromes_kimdis"] = $this->getMoneyGraph("pliromes_kimdis", $vatid, $type);
        $graph["simvaseis_ted"] = $this->getMoneyGraph("simvaseis_ted", $vatid, $type);

        return view('company.moneygraph')->with(compact('graph', 'lang'));
    }

    public function getMoneyCpv($vatid, $qry_lang, $type = "Organization") 
    {
        $res = null;

        if (Cache::has('getMoneyCpv-'.$vatid."-".$type."-".$qry_lang)) {
            $json_requestGetCpv = Cache::get('getMoneyCpv-'.$vatid."-".$type."-".$qry_lang);         
        } else {

            $qry_results = "
                SELECT distinct (str(?cpvName) AS ?cpvName)
                WHERE
                {
                {
                SELECT distinct (str(?cpvName) AS ?cpvName) (count(?expenditureLine) as ?count)
                FROM <http://publicspending.net/DiavgeiaI/CPV>
                FROM <http://linkedeconomy.org/Diavgeia>
                WHERE{
                ?expenditureLine elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;
                        elod:hasCpv ?cpv . 
                ?cpv skos:prefLabel ?cpvName .
                filter langMatches(lang(?cpvName), '".$qry_lang."')
                }
                }
                UNION
                {
                SELECT distinct (str(?cpvName) AS ?cpvName) (count(?contract) as ?count)
                FROM <http://publicspending.net/DiavgeiaI/CPV>
                FROM <http://linkedeconomy.org/Diavgeia>
                WHERE{
                ?contract elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;
                        pc:mainObject ?cpv . 
                ?cpv skos:prefLabel ?cpvName .
                filter langMatches(lang(?cpvName), '".$qry_lang."')
                }
                }
                UNION
                {
                SELECT distinct (str(?cpvName) as ?cpvName) (count(?contract) as ?count)
                FROM <http://linkedeconomy.org/EprocurementUpdate> 
                FROM <http://publicspending.net/DiavgeiaI/CPV>
                WHERE {
                ?contract elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> .
                ?contract pc:item ?item . 
                ?item gr:includesObject ?tqn . 
                ?tqn gr:typeOfGood ?some . 
                ?some elod:productCategory ?cpv . 
                ?cpv skos:prefLabel ?cpvName . 
                filter langMatches(lang(?cpvName), '".$qry_lang."')
                } 
                }
                }
                order by desc (?count)
                limit 5
            ";

            $json_requestGetCpv = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));
            Cache::put('getMoneyCp-'.$vatid."-".$type, $json_requestGetCpv, config('sparql.cache_time'));
        }

        $res_results = json_decode($json_requestGetCpv, true);
       
        
        if ($res_results["results"]["bindings"]) {
            $res = $res_results["results"]["bindings"];
        } 
        
        return $res;
    }

    public function getMoneyGraph($section, $vatid, $type = "Organization") 
    {
        $res = null;
     
        if (Cache::has('getMoneyGraph-'.$section."-".$vatid."-".$type)) {
            $json_requestGetGraph = Cache::get('getMoneyGraph-'.$section."-".$vatid."-".$type);         
        } else { 

            if ($section == "simvolaia_espa") {
                $qry_results = "
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
                
            } elseif ($section == "eggriseis_diaugeia") {

                $qry_results = "                  
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
                            
            } elseif ($section == "pliromes_diaugeia") {
                

                $qry_results = "
                    PREFIX elod: <http://linkedeconomy.org/ontology#>
                    PREFIX gr: <http://purl.org/goodrelations/v1#>
                    PREFIX dcterms: <http://purl.org/dc/terms/>

                    SELECT distinct (str(?date) as ?date) (xsd:decimal(?amount) AS ?amount) 
                    FROM <http://linkedeconomy.org/Diavgeia>
                    WHERE {
                    ?payment elod:hasExpenditureLine ?expenditureLine ;
                                    dcterms:issued ?date ;
                                    elod:buyer ?buyer ;
                                    rdf:type elod:SpendingItem .
                    ?expenditureLine elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;
                                     elod:amount ?ups .
                    ?ups gr:hasCurrencyValue ?amount .
                    FILTER NOT EXISTS {?payment elod:hasCorrectedDecision ?correctedDecision} .
                    FILTER NOT EXISTS {?ups elod:riskError \"1\"^^xsd:boolean} .
                    }
                ";
               
            } elseif ($section == "anatheseis_diaugeia") {

                $qry_results = "
                    PREFIX elod: <http://linkedeconomy.org/ontology#>
                    PREFIX gr: <http://purl.org/goodrelations/v1#>
                    PREFIX dcterms: <http://purl.org/dc/terms/>

                    SELECT distinct (str(?date) AS ?date) (xsd:decimal(?amount) AS ?amount) 
                    FROM <http://linkedeconomy.org/Diavgeia>
                    WHERE {
                    ?contract elod:decisionTypeId ?cntrTypeId ;
                              dcterms:issued ?date ;
                              elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;
                              pc:agreedPrice ?ups ;
                                  rdf:type pc:Contract .
                    ?ups gr:hasCurrencyValue ?amount .
                    FILTER NOT EXISTS {?contract elod:hasCorrectedDecision ?correctedDecision} .
                    FILTER NOT EXISTS {?ups elod:riskError \"1\"^^xsd:boolean} .
                    FILTER (?cntrTypeId = \"Δ.1\"^^xsd:string) .
                    }
                ";
                
            } elseif ($section == "katakyrwseis_diaugeia") {

                $qry_results = "
                    PREFIX elod: <http://linkedeconomy.org/ontology#>
                    PREFIX gr: <http://purl.org/goodrelations/v1#>
                    PREFIX dcterms: <http://purl.org/dc/terms/>

                    SELECT distinct (str(?date) AS ?date) (xsd:decimal(?amount) AS ?amount) 
                    FROM <http://linkedeconomy.org/Diavgeia>
                    WHERE {
                    ?contract elod:decisionTypeId ?cntrTypeId ;
                                dcterms:issued ?date ;
                                elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;
                                pc:actualPrice ?ups ;
                                    rdf:type pc:Contract .
                    ?ups gr:hasCurrencyValue ?amount .
                    FILTER NOT EXISTS {?contract elod:hasCorrectedDecision ?correctedDecision} .
                    FILTER NOT EXISTS {?ups elod:riskError \"1\"^^xsd:boolean} .
                    FILTER (?cntrTypeId = \"Δ.2.2\"^^xsd:string) .
                    }
                ";
                
            } elseif ($section == "simvaseis_kimdis") {

                $qry_results = "
                    SELECT distinct (str(?date) as ?date) (xsd:decimal(?amount) as ?amount)
                    FROM <http://linkedeconomy.org/EprocurementUpdate> 
                    WHERE {
                    ?contract pc:agreedPrice ?ups ; 
                              elod:signatureDate ?date ;
                              elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> .
                    ?ups gr:hasCurrencyValue ?amount . 
                    } 
                ";
               
            } elseif ($section == "pliromes_kimdis") {

                $qry_results = "
                    SELECT distinct (str(?date) as ?date) (xsd:decimal(?amount) as ?amount)
                    FROM <http://linkedeconomy.org/EprocurementUpdate> 
                    WHERE {
                    ?contract pc:actualPrice ?ups ; 
                              elod:signatureDate ?date ; 
                              elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> .
                    ?ups gr:hasCurrencyValue ?amount . OPTIONAL{?contract pc:item ?item .} 
                    } 
                ";
              
            } elseif ($section == "simvaseis_ted") {
                
                $qry_results = "
                    SELECT distinct (str(?date) as ?date) (xsd:decimal(?amount) as ?amount)
                    FROM <http://yourdatastories.eu/TEDGreece> 
                    where {
                    ?contract a pc:Contract ;
                          elod:documentType \"Contract award notice\"^^<http://www.w3.org/2001/XMLSchema#string> ;
                          pc:awardDate ?date ;
                          elod:seller ?seller ;
                          pc:agreedPrice ?ups .
                    ?seller gr:vatID \"".$vatid."\" .
                    ?ups gr:hasCurrencyValue ?amount . 
                    } 
                ";
            }


            $json_requestGetGraph = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));
            Cache::put('getMoneyGraph-'.$section."-".$vatid."-".$type, $json_requestGetGraph, config('sparql.cache_time'));
        }    

        $res_results = json_decode($json_requestGetGraph, true);
       
        if ($res_results["results"]["bindings"]) {
            $res = $res_results["results"]["bindings"];
        } 

        return $res;
    }

    public function getMoneyStats($section, $vatid, $type = "Organization") 
    {
        $res = null;

        if (Cache::has('getMoneyStats-'.$section."-".$vatid."-".$type."-new_query_18_10_2018")) {
            $json_requestGetStats = Cache::get('getMoneyStats-'.$section."-".$vatid."-".$type."-new_query_18_10_2018");         
        } else {            
            if ($section == "diaugeia") {

                // $qry_results = "
                //     SELECT distinct (SUM(xsd:decimal(?amount)) as ?totalAmount) (count(distinct ?expLine) as ?expLineCount)
                //     FROM <http://linkedeconomy.org/Diavgeia>
                //     WHERE
                //     {
                //     ?payment a elod:SpendingItem ;
                //                      elod:hasExpenditureLine ?expLine .
                //     ?expLine elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid.">  ;
                //                     elod:amount ?ups .
                //     ?ups gr:hasCurrencyValue ?amount .
                //     FILTER NOT EXISTS {?payment elod:hasCorrectedDecision ?correctedDecision} .
                //     FILTER NOT EXISTS {?ups elod:riskError \"1\"^^xsd:boolean} .
                //     }
                // ";
                $qry_results = "PREFIX gr: <http://purl.org/goodrelations/v1#>
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

                $json_requestGetStats = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));
                Cache::put('getMoneyStats-'.$section."-".$vatid."-".$type."-new_query_18_10_2018", $json_requestGetStats, config('sparql.cache_time'));

            } elseif ($section == "kimdis") {

            $qry_results = "                    
                SELECT count(distinct ?contract ) as ?paymentsCount sum(xsd:decimal(?amount)) as ?totalAmount
                FROM <http://linkedeconomy.org/EprocurementUpdate> 
                WHERE {
                ?contract pc:actualPrice ?ups ;
                          elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> . 
                ?ups gr:hasCurrencyValue ?amount . 
                }
            ";

                $json_requestGetStats = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));
                Cache::put('getMoneyStats-'.$section."-".$vatid, $json_requestGetStats, config('sparql.cache_time'));

            } elseif ($section == "espa") {

                $qry_results = "
                    SELECT distinct (SUM(xsd:decimal(?amount)) as ?totalAmount) (count(distinct ?contract) as ?contractCount)
                    FROM <http://linkedeconomy.org/NSRF>
                    WHERE {
                    ?project a elod:PublicWork ;
                             elod:hasRelatedContract ?contract.
                    ?contract elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ; 
                              elod:price ?ups .
                    ?ups gr:hasCurrencyValue ?amount.
                    }
                ";

             
                $json_requestGetStats = $this->request($qry_results, config('sparql.connection_username'), config('sparql.connection_password'));
                Cache::put('getMoneyStats-'.$section."-".$vatid, $json_requestGetStats, config('sparql.cache_time'));

            } else {
               
                // ted
                $qry_results = "                    
                    SELECT (count(distinct ?contract) as ?paymentsCount) (sum(xsd:decimal(?amount)) as ?totalAmount)
                    FROM <http://yourdatastories.eu/TEDGreece> 
                    WHERE {
                    ?contract pc:agreedPrice ?ups ;
                              elod:documentType \"Contract award notice\"^^<http://www.w3.org/2001/XMLSchema#string> ;
                              elod:seller ?seller . 
                    ?seller gr:vatID \"".$vatid."\" .
                    ?ups gr:hasCurrencyValue ?amount . 
                    }
                ";

                $json_requestGetStats = $this->request2($qry_results, config('sparql.connection_username2'), config('sparql.connection_password2'));
                Cache::put('getMoneyStats-'.$section."-".$vatid, $json_requestGetStats, config('sparql.cache_time2'));

            }

            //$requestCheck = $this->getUrlDbpediaAbstract($qry_results);           
                    
            
         }   
        
        $res_results = json_decode($json_requestGetStats, true);             
        $res = $res_results["results"]["bindings"][0];

        //print_r($res);
        //die;

        //if ($section == "diaugeia") {
        if(isset($res["totalAmount"])) {
            //if (array_key_exists("totalAmount", $res)) {

                if (abs($res["totalAmount"]["value"]) > 1000 || abs($res["totalAmount"]["value"]) <= 1000) {
                    $res["totalAmount"]["label"] = round($res["totalAmount"]["value"] / 1000, 1)."K";                            
                } 
                if (abs($res["totalAmount"]["value"]) > 1000000) {
                    $res["totalAmount"]["label"] = round($res["totalAmount"]["value"] / 1000000, 1)."M";
                } 
                if (abs($res["totalAmount"]["value"]) > 1000000000) {
                    $res["totalAmount"]["label"] = round($res["totalAmount"]["value"] / 1000000000, 1)."B";                                
                }
            //}
        } 
        //}    

        return $res;
    }

    public function getStores ($vatid) 
    {
        $res = null;

        if (Cache::has('getCompanyStores-'.$vatid)) {
            $json_requestGetStores = Cache::get('getCompanyStores-'.$vatid);         
        } else {            
            $qry_results = "
                SELECT str(?gemhNumber) as ?gemhNumber str(?gemhUrl) as ?gemhUrl str(?gemhName) as ?gemhName str(?officeName) as ?officeName str(?gemhDate) as ?gemhDate str(?street) as ?street str(?locality) as ?locality
                FROM <http://linkedeconomy.org/GemhOrganizations>
                FROM <http://linkedeconomy.org/Taxonomies> 
                WHERE {
                <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:hasGemhEntry ?gemhEntry ;
                        vcard2006:hasAddress ?address .
                ?gemhEntry elod:gemhNumber ?gemhNumber ;
                     elod:gemhName ?gemhName ;
                     elod:gemhDate ?gemhDate ;
                     elod:gemhOffice ?gemhOffice ;
                     elod:gemhUrl ?gemhUrl .
                ?gemhOffice skos:prefLabel ?officeName .
                OPTIONAL{ ?address vcard2006:locality ?locality . }
                OPTIONAL{ ?address vcard2006:street-address ?street . }
                filter langMatches(lang(?officeName), 'el')
                filter (!strEnds(str(?gemhEntry), \"000\"))
                filter (strEnds(str(?address), ?gemhNumber))
                }
            ";          

            $requestCheck = $this->getUrlDbpediaAbstract($qry_results);           
                    
            $json_requestGetStores = $this->request($requestCheck, config('sparql.connection_username'), config('sparql.connection_password'));
            Cache::put('getCompanyStores-'.$vatid, $json_requestGetStores, config('sparql.cache_time'));
         }   
        
        $res_results = json_decode($json_requestGetStores, true); 
        $res = $res_results["results"]["bindings"];

        //print_r($res);
        return $res;
    }


    public function getCompanyCpaStatus ($cpa)
    {
        $month = date('n', mktime(0, 0, 0, date('m')-1, 1, date('Y')));
        $year = date('Y', mktime(0, 0, 0, date('m')-1, 1, date('Y'))); 

        $res = null;

        if (Cache::has('getCompanyCpaStatus-'.$cpa)) {
            $json_requestGetResults = Cache::get('getCompanyCpaStatus-'.$cpa);         
        } else {            
            $qry_results = "
                SELECT str(?dateModified) as ?dateModified str(?cpaName) as ?cpaName str(?topCpaName ) as ?topCpaName str(?topCpaCode) as ?topCpaCode 
                ?startsOfMonth ?deletesOfMonth
                ?activesCount ?activesPercentage 
                ?personalCompaniesCount ?personalCompaniesPercentage 
                ?avgAge str(?topLocality) as ?topLocality ?topLocalityCount ?topLocalityPercentage
                FROM <http://linkedeconomy.org/CpaStats> 
                FROM <http://linkedeconomy.org/CPA>  
                WHERE {
                ?statsUri elod:statsOf <http://linkedeconomy.org/resource/CPA/".$cpa."> ;
                          elod:hasMonthlyStats ?monthlyStats ;
                          elod:grandPaCode ?topCpaCode ;
                          dcterms:modified ?dateModified .
                <http://linkedeconomy.org/resource/CPA/".$cpa."> skos:broaderTransitive ?topCpa ;
                     skos:prefLabel ?cpaName .
                ?topCpa skos:altLabel ?topCpaName .
                ?monthlyStats elod:startsOfMonth ?startsOfMonth ;
                              elod:deletesOfMonth ?deletesOfMonth ;
                              elod:activesCount ?activesCount ;
                              elod:personalCompaniesCount ?personalCompaniesCount;
                              elod:avgAge ?avgAge ;
                              dcterms:month \"".$month."\"^^<http://www.w3.org/2001/XMLSchema#integer> ;
                              dcterms:year \"".$year."\"^^<http://www.w3.org/2001/XMLSchema#integer> .
                Optional{ ?monthlyStats elod:topLocality ?topLocality ;
                              elod:topLocalityCount ?topLocalityCount . }
                Optional{ ?monthlyStats elod:activesPercentage ?activesPercentage . }
                Optional{ ?monthlyStats elod:personalCompaniesPercentage ?personalCompaniesPercentage . }
                Optional{ ?monthlyStats elod:topLocalityPercentage ?topLocalityPercentage . }
                filter langMatches(lang(?topCpaName), 'el')
                filter langMatches(lang(?cpaName), 'el')
                } 
            ";
           

            $requestCheck = $this->getUrlDbpediaAbstract($qry_results);           
                    
            $json_requestGetResults = $this->request($requestCheck, config('sparql.connection_username'), config('sparql.connection_password'));
            Cache::put('getCompanyCpaStatus-'.$cpa, $json_requestGetResults, config('sparql.cache_time'));
        }   
        
        $res_results = json_decode($json_requestGetResults, true); 

        if (!empty($res_results["results"]["bindings"])) {
            $res = $res_results["results"]["bindings"][0];
        } 

        return $res;        
    }    

    public function partial (Request $request)
    {
        $gemhNumber = $request->input("gemhNumber");
        $lang = $request->input("lang");
        $part = $request->input("part");
        $vatid = $request->input("vatId");
        $page = $request->input("page");

        $lang = $this->setLanguage($lang); 
        $upd_date_q="";
        $isperson=false;
        switch ($part) {
            case 1:
                $res = DB::connection('mysql_gemh')->select("select m.gemhdate, er.documentUrl, er.dropboxFileLink
                    from gemhV2.Main m 
                    inner join gemhV2.ExtraRecommendatioData er on m.gemhnumber = er.gemhnumber
                    where
                    m.gemhnumber = '".$gemhNumber."'");

                $upd_date_q = DB::connection('mysql_gemh')->select("select max(er.dateModified)  as lastUpdate
                    from gemhV2.Main m 
                    inner join gemhV2.ExtraRecommendatioData er on m.gemhnumber = er.gemhnumber
                    where
                    m.gemhnumber = '".$gemhNumber."'");

                break;
            case 2:
                $res = DB::connection('mysql_gemh')->select("select DISTINCT ea.dateSubmitted, ea.title, ea.abstract, ea.documentUrl, ea.dropboxFileLink
                    from gemhV2.Main m 
                    inner join gemhV2.ExtraAnnouncements ea on m.gemhnumber = ea.gemhnumber
                    where
                    m.gemhnumber = '".$gemhNumber."'"); 
                    //return var_dump($res);               
                    $res=json_decode(json_encode($res),true);
//// XSOZ - delete same rows - start
                    $res_un=array();
                    $i=0;
                    $ii=0;
                    foreach($res as $k1=>$v1) {
                       $exist=false;
                       for($j=$i+1;$j<count($res);$j++) {
                            if($v1["dateSubmitted"]==$res[$j]["dateSubmitted"] && $v1["title"]==$res[$j]["title"]) {
                                $exist=true;
                            }
                       }
                       if(!$exist) {
                            $res_un[$ii]=$v1;
                            $ii++;
                       } 
                       $i++;                    
                    }
                    arsort($res_un);
                    $res=$res_un;
//// XSOZ - delete same rows - end   
               //return var_dump($res_un);
/////// XSOZ - Ready to change to RDF
                     // $qry_results = "
                     //        SELECT ?dateSubmitted str(?subject) as ?subject str(?abstract) as ?abstract str(?documentUrl) as ?documentUrl str(?dropboxLink) as ?dropboxLink
                     //        FROM <http://linkedeconomy.org/GemhCorporateEvents> 
                     //        WHERE {
                     //        ?corporateEvent elod:corporateEventCategory <http://linkedeconomy.org/ontology/resource/CorporateEventCategory/1> ;
                     //            dcterms:dateSubmitted ?dateSubmitted;
                     //            dcterms:subject ?sub ;
                     //            elod:abstract ?abstract ;
                     //            elod:documentUrl ?documentUrl ;
                     //            elod:dropboxLink ?dropboxLink ;
                     //            elod:isCorporateEventOf <http://linkedeconomy.org/resource/Organization/" . $vatid . "> .
                     //        ?sub skos:prefLabel ?subject .
                     //        }
                     //        ";
                     // $requestCheck = $this->getUrlDbpediaAbstract($qry_results);
                     // $json_requestGetResults = $this->request($requestCheck, config('sparql.connection_username'), config('sparql.connection_password'));
                     // $res = json_decode($json_requestGetResults,true)['results']['bindings'];
//////// XSOZ END - Change to rdf for part 2
//return var_dump($res);
                $upd_date_q = DB::connection('mysql_gemh')->select("select max(ea.dateModified)  as lastUpdate
                    from gemhV2.Main m 
                    inner join gemhV2.ExtraAnnouncements ea on m.gemhnumber = ea.gemhnumber
                    where
                    m.gemhnumber = '".$gemhNumber."'");

                break;
            case 3:
                $res = DB::connection('mysql_gemh')->select("select ed.dateSubmitted, ed.abstract, ed.type, ed.documentUrl, ed.dropboxFileLink
                    from gemhV2.Main m 
                    inner join gemhV2.ExtraDecisionBodies ed on m.gemhnumber = ed.gemhnumber
                    where
                    m.gemhnumber = '".$gemhNumber."'");

                $upd_date_q = DB::connection('mysql_gemh')->select("select max(ed.dateModified)  as lastUpdate
                    from gemhV2.Main m 
                    inner join gemhV2.ExtraDecisionBodies ed on m.gemhnumber = ed.gemhnumber
                    where
                    m.gemhnumber = '".$gemhNumber."'");

                break;
            case 4:
                $res = DB::connection('mysql_gemh')->select("select el.dateSubmitted, el.title, el.authorityControl, el.documentUrl, el.dropboxFileLink
                    from gemhV2.Main m 
                    inner join gemhV2.ExtraLegalityControl el on m.gemhnumber = el.gemhnumber
                    where
                    m.gemhnumber = '".$gemhNumber."'");

                $upd_date_q = DB::connection('mysql_gemh')->select("select max(el.dateModified)  as lastUpdate
                    from gemhV2.Main m 
                    inner join gemhV2.ExtraLegalityControl el on m.gemhnumber = el.gemhnumber
                    where
                    m.gemhnumber = '".$gemhNumber."'");

                break;
            case 5:
                if($page=="man_FR") {
                        $isperson=true;
                        $res_t = 'SELECT DISTINCT ?dateAccepted ?publishedDate str(?fek) as ?fek str(?fekNumber) as ?fekNumber str(?documentUrl) as ?documentUrl str(?dropboxLink) as ?dropboxLink
                        FROM <http://linkedeconomy.org/FekAnnouncements> 
                        WHERE {
                        ?corporateAnnouncement elod:isAnnouncementOf <http://linkedeconomy.org/resource/Organization/'.str_replace('-', '/',$vatid).'> ;
                              elod:relatedFek ?relatedFek .
                        ?relatedFek dcterms:dateAccepted ?dateAccepted ;                                    
                                    elod:fekNumber ?fekNumber ;
                                    elod:fekType ?fekType .
                        optional { ?relatedFek elod:dropboxLink ?dropboxLink . }
                        optional { ?relatedFek dcterms:dateAccepted ?publishedDate . }
                        optional { ?relatedFek elod:documentUrl ?documentUrl . }
                        ?fekType skos:prefLabel ?fek .
                        } 
                        order by desc (?publishedDate)';
                } else {
                            $res_t = 'SELECT DISTINCT str(?subject) as ?subject ?publishedDate str(?fek) as ?fek str(?fekNumber) as ?fekNumber ?dateAccepted str(?documentUrl) as ?documentUrl str(?dropboxLink) as ?dropboxLink
                            FROM <http://linkedeconomy.org/FekAnnouncements> 
                            WHERE {
                            ?corporateAnnouncement elod:isAnnouncementOf <http://linkedeconomy.org/resource/Organization/'.str_replace('-', '/',$vatid).'>;
                                  elod:relatedFek ?relatedFek .
                            Optional { ?corporateAnnouncement dcterms:subject ?anSubject. 
                                       ?anSubject skos:prefLabel ?subject. 
                                       filter langMatches(lang(?subject), \'el\') 
                            }
                            ?relatedFek dcterms:dateAccepted ?dateAccepted ;
                                        elod:fekNumber ?fekNumber ;
                                        elod:fekType ?fekType ;
                                        elod:documentUrl ?documentUrl .
                            optional { ?relatedFek elod:dropboxLink ?dropboxLink }
                            optional { ?relatedFek dcterms:dateAccepted ?publishedDate }
                            ?fekType skos:prefLabel ?fek .
                            } 
                            order by desc (?publishedDate)'; 

                }
                $requestCheck = $this->getUrlDbpediaAbstract($res_t);    
                                  
                $json_requestGetResults = $this->request($requestCheck, config('sparql.connection_username'), config('sparql.connection_password'));
                //return $json_requestGetResults;
                $res = json_decode($json_requestGetResults,true)['results']['bindings'];
//return $json_requestGetResults;
                $upd_date_qr = 'SELECT max(?modified)
                    FROM <http://linkedeconomy.org/FekAnnouncements> 
                    WHERE {
                    ?corporateAnnouncement elod:isAnnouncementOf <http://linkedeconomy.org/resource/Organization/'.str_replace('-', '/',$vatid).'> ;
                          dcterms:modified ?modified .
                    } 
                    ';
                $requestCheck2 = $this->getUrlDbpediaAbstract($upd_date_qr);    
                                  
                $json_requestGetResults = $this->request($requestCheck2, config('sparql.connection_username'), config('sparql.connection_password'));                
                $upd_date_q = array();
                if(array_key_exists('callret-0', json_decode($json_requestGetResults,true)['results']['bindings'][0])) {
                        $upd_date_q[0] = json_decode('{"lastUpdate":"'.json_decode($json_requestGetResults,true)['results']['bindings'][0]['callret-0']['value'].'"}');    
                } else {
                        $upd_date_q[0] = json_decode('{"lastUpdate":""}');
                }  
                 
                
                break;
        }
        
        $upd_date = date_format(date_create($upd_date_q[0]->lastUpdate),"d-m-Y");
        // identify file extension
        for ($i = 0; $i < count($res); $i++) { 
////////////////////// XSOZ - for part 5 we use sparql for feks //////////////////////////////////    
         if($part==5 || $part==2) {
            if(isset($res[$i]['dropboxLink']['value'])) {
                $path = parse_url($res[$i]['dropboxLink']['value'], PHP_URL_PATH);
                $res[$i]['ext'] = pathinfo($path, PATHINFO_EXTENSION);
            } else {
                $res[$i]['ext'] = '';
            }

         }
         else {        
            $path = parse_url($res[$i]->dropboxFileLink, PHP_URL_PATH);
            $res[$i]->ext = pathinfo($path, PATHINFO_EXTENSION);
        }
        }
                           
        return view('partial_announcements')->with(compact('res', 'upd_date', 'part', 'isperson', 'lang', 'vatid', 'gemhNumber'));
    }

    public function getAnnouncementsStats ($gemhNumber, $vatid='000000000')
    {
        $ann = array();

        $ann["stats"] = DB::connection('mysql_gemh')->select("select count(distinct ea.number) as 'annoCount', count(distinct ed.number) as 'decisionCount', count(distinct el.number) as 'legalityCount', count(distinct er.number) as 'recommendationCount'
            from gemhV2.Main m 
            left join gemhV2.ExtraAnnouncements ea on ea.gemhnumber=m.gemhnumber
            left join gemhV2.ExtraDecisionBodies ed on ed.gemhnumber=m.gemhnumber
            left join gemhV2.ExtraLegalityControl el on el.gemhnumber=m.gemhnumber
            left join gemhV2.ExtraRecommendatioData er on er.gemhnumber=m.gemhnumber
            where
            m.gemhnumber = '".$gemhNumber."'");
//return $ann;
        $qry = 'SELECT count(str(?documentUrl)) as ?fekCount
                FROM <http://linkedeconomy.org/FekAnnouncements> 
                WHERE {
                ?corporateAnnouncement elod:isAnnouncementOf <http://linkedeconomy.org/resource/Organization/'.$vatid.'> ;
                        elod:relatedFek ?relatedFek .
                        Optional { ?corporateAnnouncement dcterms:subject ?anSubject. 
                                   ?anSubject skos:prefLabel ?subject. 
                                   filter langMatches(lang(?subject), \'el\') 
                        }
                        ?relatedFek dcterms:dateAccepted ?dateAccepted ;
                                    elod:fekNumber ?fekNumber ;
                                    elod:fekType ?fekType ;
                                    elod:documentUrl ?documentUrl .
                        optional { ?relatedFek elod:dropboxLink ?dropboxLink }
                        optional { ?relatedFek dcterms:issued ?publishedDate }
                        ?fekType skos:prefLabel ?fek .
                        } 
                        ';
        $requestCheck = $this->getUrlDbpediaAbstract($qry); 
        $json_requestGetResults = $this->request($requestCheck, config('sparql.connection_username'), config('sparql.connection_password'));        
        $ann['stats'][0]->fekCount = json_decode($json_requestGetResults,true)['results']['bindings'][0]['fekCount']['value'];
//return $ann['stats'];
        $qry = 'SELECT count(str(?documentUrl)) as ?annCount
                FROM <http://linkedeconomy.org/GemhCorporateEvents> 
                WHERE {
                ?corporateEvent elod:corporateEventCategory <http://linkedeconomy.org/ontology/resource/CorporateEventCategory/1> ;
                    dcterms:dateSubmitted ?dateSubmitted;
                    dcterms:subject ?sub ;
                    elod:abstract ?abstract ;
                    elod:documentUrl ?documentUrl ;
                    elod:dropboxLink ?dropboxLink ;
                    elod:isCorporateEventOf <http://linkedeconomy.org/resource/Organization/'.$vatid.'> .
                ?sub skos:prefLabel ?subject .
                }
                ';
        $requestCheck = $this->getUrlDbpediaAbstract($qry); 
        $json_requestGetResults = $this->request($requestCheck, config('sparql.connection_username'), config('sparql.connection_password'));  
        //return $json_requestGetResults;      
        $ann['stats'][0]->annCount = json_decode($json_requestGetResults,true)['results']['bindings'][0]['annCount']['value'];

        return $ann;
    }

    public function getManStats ($vatid)
    {
             

        $qry = 'SELECT count(str(?fekNumber)) as ?fekCount
                FROM <http://linkedeconomy.org/FekAnnouncements> 
                WHERE {
                ?corporateAnnouncement elod:isAnnouncementOf <http://linkedeconomy.org/resource/Organization/'.$vatid.'> ;
                      elod:relatedFek ?relatedFek .
                Optional { ?corporateAnnouncement dcterms:subject ?anSubject. 
                           ?anSubject skos:prefLabel ?subject. 
                           filter langMatches(lang(?subject), \'el\') 
                }
                ?relatedFek dcterms:dateAccepted ?dateAccepted ;                           
                            elod:fekNumber ?fekNumber ;
                            elod:fekType ?fekType .
                optional { ?relatedFek elod:dropboxLink ?dropboxLink . }
                optional { ?relatedFek elod:documentUrl ?documentUrl . }
                optional { ?relatedFek dcterms:issued ?publishedDate . }
                ?fekType skos:prefLabel ?fek .
                }';
                //return $vatid;
        $requestCheck = $this->getUrlDbpediaAbstract($qry);           
                    
        $json_requestGetResults = $this->request($requestCheck, config('sparql.connection_username'), config('sparql.connection_password')); 
                
        $ann = json_decode($json_requestGetResults,true)['results']['bindings'][0]['fekCount']['value'];

        return $ann;
    }

    public function getGemhNumber ($vatid)
    {
        $res = DB::select(DB::raw("SELECT gemhNumber
                                   FROM companies
                                   WHERE vatId = '".$vatid."' LIMIT 1"));

        if ($res) {            
            return $res[0]->gemhNumber;
        }

        return null;
    }

    public function getCompanySize ($ebit, $employees) 
    {
        $size = array("Πολύ Μικρή", "Μικρή", "Μεσαία", "Μεγάλη");
        $size_en = array("Very Small", "Small", "Middle", "Large");

        if ($employees >= 250) {
            $emp = 3;            
            //$emp_label = ">250";
        } elseif($employees >= 50 && $employees < 249) {
            $emp = 2;
            //$emp_label = "50 - 249";            
        } elseif($employees >= 10 && $employees <= 49) {
            $emp = 1;
            //$emp_label = "10 - 49";            
        } elseif($employees < 10) {
            $emp = 0;            
            //$emp_label = "<10";
        }

        if ($ebit >= 50000000 || $emp==3) {
            $tl = 3;            
            $tl_label = ">€50M";
            $emp_label = ">250";
        } elseif(($ebit >= 10000000 && $ebit < 50000000) || $emp==2) {
            $tl = 2;
            $tl_label = "€10M - €50M";
            $emp_label = "50 - 249";              
        } elseif(($ebit >= 2000000 && $ebit < 10000000) || $emp==1) {
            $tl = 1;
            $tl_label = "€2M - €10M";
            $emp_label = "10 - 49";            
        } elseif($ebit < 2000000 || $emp==0) {
            $tl = 0;
            $tl_label = "<€2M";
            $emp_label = "<10";
        }        


        $res = array("ebit"=>$tl, "employees"=>$emp, "title"=>$size[$tl], "title_en"=>$size_en[$tl], "ebit_label"=>$tl_label, "employees_label"=>$emp_label);

        return $res;
    }

    public function getFinancialResults ($vatid, $qry_lang) 
    {
        if (Cache::has('getFinancialResults-'.$vatid)) {
            $json_requestGetResults = Cache::get('getFinancialResults-'.$vatid);         
        } else {            
            $qry_results = "
                SELECT distinct (str(?legalName ) as ?legalName) (str(?year) as ?year) (xsd:decimal(?ebit) as ?ebit) (xsd:decimal(?turnover) as ?turnover) (str(?employeesNo) as ?employeesNo)
                FROM <http://linkedeconomy.org/Financials>
                FROM <http://linkedeconomy.org/GemhOrganizations>
                WHERE {
                ?finances a elod:Finances ;
                elod:financesOf <http://linkedeconomy.org/resource/Organization/".str_replace('-', '/',$vatid)."> ;
                elod:financesOfYear ?financesOfYear .
                ?financesOfYear dcterms:year ?year ;
                elod:ebit ?ebit ;
                elod:turnover ?turnover ;
                elod:employeesNo ?employeesNo .
                <http://linkedeconomy.org/resource/Organization/".str_replace('-', '/',$vatid)."> a foaf:Organization .
                optional{ <http://linkedeconomy.org/resource/Organization/".str_replace('-', '/',$vatid)."> gr:legalName ?legalName . }
                }
            ";
           

            $requestCheck = $this->getUrlDbpediaAbstract($qry_results);           
                    
            $json_requestGetResults = $this->request($requestCheck, config('sparql.connection_username'), config('sparql.connection_password'));
            Cache::put('getFinancialResults-'.$vatid, $json_requestGetResults, config('sparql.cache_time'));
        }   
        
        $res_results = json_decode($json_requestGetResults, true); 
        
        //print_r($res_debtors);
        
        if (!empty($res_results["results"]["bindings"])) {
            $result = $res_results["results"]["bindings"];
        } else {
            $result = null;
        }
                
        return $result;
    }
    
	public function getDebtors ($vatid, $qry_lang) 
	{
        //Uncomment to clear cache for current debtors vatid
        //Cache::forget( 'getDebtors-'.$vatid);
        //die;
		if (Cache::has('getDebtors-'.$vatid) && Cache::has('dateoflastupdate_debtors')) {
			$json_requestGetDebtors = Cache::get('getDebtors-'.$vatid);	
            $json_requestGetDebtorsDate = Cache::get('dateoflastupdate_debtors'); 		
        } else {			
            $qry_check = "
                SELECT distinct (str(?debtor) as ?debtor) (xsd:decimal(?amount2013) as ?amount2013) (xsd:decimal(?amount2016) as ?amount2016) (xsd:decimal(?amount2017) as ?amount2017) (xsd:decimal(?amountEfka2017) as ?amountEfka2017) (xsd:decimal(?amount2018) as ?amount2018) (xsd:decimal(?amountEfka2018) as ?amountEfka2018)
                FROM <http://linkedeconomy.org/Debtors> 
                FROM <http://linkedeconomy.org/GemhOrganizations> 
                WHERE {
                Optional{
                ?decision2013 a elod:FinancialDecision ; 
                              elod:debtor <http://linkedeconomy.org/resource/Organization/".$vatid."> ; 
                              elod:amount ?ups2013 ;
                              elod:financialYear \"2013\"^^xsd:gYear .
                ?ups2013 elod:hasCurrencyValue ?amount2013 .
                filter not exists{?decision2013 dc:source \"ΕΦΚΑ\"^^xsd:string}
                <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:name ?debtor .
                }
                Optional{
                ?decision2016 a elod:FinancialDecision ; 
                              elod:debtor <http://linkedeconomy.org/resource/Organization/".$vatid."> ; elod:amount ?ups2016 ;
                              elod:financialYear \"2016\"^^xsd:gYear .
                ?ups2016 elod:hasCurrencyValue ?amount2016 .
                filter not exists{?decision2016 dc:source \"ΕΦΚΑ\"^^xsd:string}
                <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:name ?debtor .
                }
                Optional{
                ?decision2017 a elod:FinancialDecision ; 
                             elod:debtor <http://linkedeconomy.org/resource/Organization/".$vatid."> ; 
                             elod:amount ?ups2017 ;
                             elod:financialYear \"2017\"^^xsd:gYear .
                ?ups2017 elod:hasCurrencyValue ?amount2017 .
                filter not exists{?decision2017 dc:source \"ΕΦΚΑ\"^^xsd:string}
                <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:name ?debtor .
                }
                Optional{
                ?decisionEfka2017 a elod:FinancialDecision ; 
                                  elod:debtor <http://linkedeconomy.org/resource/Organization/".$vatid."> ; 
                                  elod:amount ?upsEfka2017 ;
                elod:financialYear \"2017\"^^xsd:gYear .
                ?decisionEfka2017 dc:source \"ΕΦΚΑ\"^^xsd:string .
                ?upsEfka2017 elod:hasCurrencyValue ?amountEfka2017 .
                <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:name ?debtor .
                }
                Optional{
                ?decision2018 a elod:FinancialDecision ; 
                             elod:debtor <http://linkedeconomy.org/resource/Organization/".$vatid."> ; 
                             elod:amount ?ups2018 ;
                             elod:financialYear \"2018\"^^xsd:gYear .
                ?ups2018 elod:hasCurrencyValue ?amount2018 .
                filter not exists{?decision2018 dc:source \"ΕΦΚΑ\"^^xsd:string}
                <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:name ?debtor .
                }
                Optional{
                ?decisionEfka2018 a elod:FinancialDecision ; 
                                  elod:debtor <http://linkedeconomy.org/resource/Organization/".$vatid."> ; 
                                  elod:amount ?upsEfka2018 ;
                elod:financialYear \"2018\"^^xsd:gYear .
                ?decisionEfka2018 dc:source \"ΕΦΚΑ\"^^xsd:string .
                ?upsEfka2018 elod:hasCurrencyValue ?amountEfka2018 .
                <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:name ?debtor .
                }
                }

            ";

			$requestCheck = $this->getUrlDbpediaAbstract($qry_check);			
					
			$json_requestGetDebtors = $this->request($requestCheck, config('sparql.connection_username'), config('sparql.connection_password'));
			Cache::put('getDebtors-'.$vatid, $json_requestGetDebtors, config('sparql.cache_time'));

            $qry_check2 = "SELECT max(?date) 
            FROM <http://linkedeconomy.org/Debtors> 
            WHERE {
            ?decision a elod:FinancialDecision ; 
                      elod:submissionTimestamp ?date .
            }
            ";
            $requestCheck2 = $this->getUrlDbpediaAbstract($qry_check2);   
            $json_requestGetDebtorsDate = $this->request($requestCheck2, config('sparql.connection_username'), config('sparql.connection_password'));
            Cache::put('dateoflastupdate_debtors', $json_requestGetDebtorsDate, config('sparql.cache_time'));
		}	
		
		$res_debtors = json_decode($json_requestGetDebtors, true); 
        $res_debtors_date = json_decode($json_requestGetDebtorsDate, true); 
		
		//print_r($res_debtors);
		
		if (!empty($res_debtors["results"]["bindings"])) {
			$result[0] = $res_debtors["results"]["bindings"];
		} else {
			$result[0] = null;
		}

        if (!empty($res_debtors_date["results"]["bindings"])) {
            $result[1] = $res_debtors_date["results"]["bindings"];
        } else {
            $result[1] = null;
        }
				
		return $result;
	}
	
    public function checkProfileCalls($vatid)
    {
        // get mumber of profiles for specific user for this month
        // $resCount = DB::select(DB::raw("SELECT count(*) as count
        //                            FROM company_profile_calls
        //                            WHERE cpc_userid=".$this->user->id."
        //                            AND (MONTH(cpc_date) = MONTH(NOW()) AND YEAR(cpc_date) = YEAR(NOW()))"));
        $resCount = DB::select(DB::raw("SELECT count(*) as count
                                   FROM company_profile_calls
                                   WHERE cpc_userid=".$this->user->id));
        //print_r($resCount[0]->count);
        
        // get the limit for specific user
        $userCalls = DB::select(DB::raw("SELECT company_profile_calls
                                   FROM users
                                   WHERE id=".$this->user->id." LIMIT 1"));

        // if recorded profiles for specific user are less than limit 
        if ($resCount[0]->count < $userCalls[0]->company_profile_calls) {
            
            //check if recorded for current month - year
            // $res = DB::select(DB::raw("SELECT *
            //                            FROM company_profile_calls
            //                            WHERE cpc_vatid='".$vatid."'
            //                            AND cpc_userid=".$this->user->id."
            //                            AND (MONTH(cpc_date) = MONTH(NOW()) AND YEAR(cpc_date) = YEAR(NOW()))"));
             $res = DB::select(DB::raw("SELECT *
                                       FROM company_profile_calls
                                       WHERE cpc_vatid='".$vatid."'
                                       AND cpc_userid=".$this->user->id));
            
            if (empty($res)) {
                // if not recorded insert in DB  
            
                DB::insert("INSERT INTO company_profile_calls (cpc_vatid, cpc_userid, cpc_date) VALUES 
                            ('".$vatid."', 
                            '".$this->user->id."',					
                            NOW())");
            }
            
            return true;
        } else {
            return false;
        }
    }

    public function XSOZcheckProfileCalls($vatid,$stst,$iscpacompany)
    {
        $r[1]=true;
       //return true an to profil einai ok na provlithei
        // $resCount = DB::select(DB::raw("SELECT count(*) as count
        //                            FROM company_profile_calls
        //                            WHERE cpc_userid=".$this->user->id));
        
        // $userCalls = DB::select(DB::raw("SELECT company_profile_calls
        //                            FROM users
        //                            WHERE id=".$this->user->id." LIMIT 1"));
       
             $res = DB::select(DB::raw("SELECT *
                                       FROM company_profile_calls
                                       WHERE cpc_vatid='".$vatid."'
                                       AND cpc_userid=".$this->user->id));
            
            if (empty($res) && $iscpacompany==false) {
                // if not recorded insert in DB  
               // if ($resCount[0]->count < $userCalls[0]->company_profile_calls) {
                if($stst>0) {
                    DB::insert("INSERT INTO company_profile_calls (cpc_vatid, cpc_userid, cpc_date) VALUES 
                                ('".$vatid."', 
                                '".$this->user->id."',                  
                                NOW())");
                    $r[0]=true;
                    $r[1]=false;
                } else {
                    $r[0]=false;
                }
                
            } else {
                $r[0]=true;
            }
        return $r;
    }

    
    public function getCompanyData($vatid, $qry_lang)
    {
            $vatid = str_replace("-","/",$vatid);
 
            $company = array();
   
            // Check if company has branches, if result = 1 company has no branches
            
            $branches_res = $this->getCompanyBraches($vatid);

            $numOfBranches = $branches_res["results"]["bindings"][0]["gemhCount"]["value"];
          
            if ($numOfBranches == 0) {
  
                // Not registered in Gemh

                $company_profile = $this->getCompanyProfile($vatid, $qry_lang, 0, "");

                //if ($company_profile["results"]["bindings"][0]["vatId"]["value"]) {                    
                //    $vatid = $company_profile["results"]["bindings"][0]["vatId"]["value"];                    
                //}

                $company_profileLocation = $this->getCompanyProfileLocation($vatid, $qry_lang, 0, "");                
                $company_profileMainActivities = $this->getCompanyProfileMainActivities($vatid, $qry_lang, 0, "");
                $company_profileSecondaryActivities = $this->getCompanyProfileSecActivities($vatid, $qry_lang, 0, "");

            } elseif ($numOfBranches == 1) {   
              
                // Has no branch
                
                $company_profile = $this->getCompanyProfile($vatid, $qry_lang, 1, null);
 
                //if ($company_profile["results"]["bindings"][0]["vatId"]["value"]) {                    
                //    $vatid = $company_profile["results"]["bindings"][0]["vatId"]["value"];                    
                //}
                
                $company_profileLocation = $this->getCompanyProfileLocation($vatid, $qry_lang, 1, null);
                $company_profileGemh = $this->getCompanyProfileGemh($vatid, $qry_lang, 1, null);
                $company_profileMainActivities = $this->getCompanyProfileMainActivities($vatid, $qry_lang, 1, null);
                $company_profileSecondaryActivities = $this->getCompanyProfileSecActivities($vatid, $qry_lang, 1, null);
                
            } else {
                    
                // Has branches                    

                // Check if vatId is correct                
                $isCorrectVatId = $this->checkVatId($vatid);

                $company_profile = $this->getCompanyProfile($vatid, $qry_lang, $numOfBranches, $isCorrectVatId);

                $company_profileLocation = $this->getCompanyProfileLocation($vatid, $qry_lang, $numOfBranches, $isCorrectVatId);                            
                $company_profileGemh = $this->getCompanyProfileGemh($vatid, $qry_lang, $numOfBranches, $isCorrectVatId);
                $company_profileMainActivities = $this->getCompanyProfileMainActivities($vatid, $qry_lang, $numOfBranches, $isCorrectVatId);
                $company_profileSecondaryActivities = $this->getCompanyProfileSecActivities($vatid, $qry_lang, $numOfBranches, $isCorrectVatId);
            }

			$arrtoparse = $company_profile["results"]["bindings"];
            $arrtoparseLoc = $company_profileLocation["results"]["bindings"];            
            if ($numOfBranches != 0) {
                $arrtoparseGemh = $company_profileGemh["results"]["bindings"];        
            }
            $arrtoparseAct = $company_profileMainActivities["results"]["bindings"];
            $arrtoparseSecAct = $company_profileSecondaryActivities["results"]["bindings"];
            
            $allSecCpas = array();
                    
           //print_r($company_profile);

            //if (!empty($arrtoparse)) {
                if (!empty($arrtoparse)) {

                    if ($numOfBranches == 0) {
                        if (array_key_exists("legalName", $arrtoparse[0])) {
                            $company["name"] = $arrtoparse[0]["legalName"]["value"];
                        } else {
                            $company["name"] = $arrtoparse[0]["name"]["value"];
                        }
                    } else {
                        if (array_key_exists("legalName", $arrtoparse[0])) {
                            $company["name"] = $arrtoparse[0]["legalName"]["value"];
                        } else {
                            $company["name"] = $arrtoparse[0]["gemhName"]["value"];
                        }
                    }    
                } else {
                    $company["name"] = "-";
                }

                $company["numOfBranches"] = $numOfBranches;
                
                if (!empty($arrtoparse) && array_key_exists("regDate", $arrtoparse[0])) {
                    $company["regDate"] = date_format(date_create($arrtoparse[0]["regDate"]["value"]),"d-m-Y");
                } else {
                    $company["regDate"] = "-";
                }
                
                if (!empty($arrtoparse) && array_key_exists("regDate", $arrtoparse[0])) {
                    $company["regDate_notformated"] = "'".date_format(date_create($arrtoparse[0]["regDate"]["value"]),"Y:m:d H:i:s")."'";                    
                } else {
                    $company["regDate_notformated"] = "NULL";
                }
                
                if (!empty($arrtoparse) && array_key_exists("stopDate", $arrtoparse[0])) {
                    $company["stopDate"] = date_format(date_create($arrtoparse[0]["stopDate"]["value"]),"d-m-Y");
                } else {
                    $company["stopDate"] = "";
                }                
                
                if (!empty($arrtoparse) && array_key_exists("gemhName", $arrtoparse[0])) {
                    $company["gemhName"] = $arrtoparse[0]["gemhName"]["value"];
                } else {
                    $company["gemhName"] = "-";
                }
                
                if (!empty($arrtoparseLoc) && array_key_exists("street", $arrtoparseLoc[0])) {
                    $company["street"] = $arrtoparseLoc[0]["street"]["value"];
                } else {
                    $company["street"] = "-";
                }
                
                if (!empty($arrtoparseLoc) && array_key_exists("locality", $arrtoparseLoc[0])) {
                    $company["locality"] = $arrtoparseLoc[0]["locality"]["value"];
                } else {
                    $company["locality"] = "-";
                }
                
                if (!empty($arrtoparseLoc) && array_key_exists("pcode", $arrtoparseLoc[0])) {
                    $company["pcode"] = $arrtoparseLoc[0]["pcode"]["value"];
                } else {
                    $company["pcode"] = "-";
                }
                
                if (!empty($arrtoparseGemh) && array_key_exists("officeName", $arrtoparseGemh[0])) {
                    $company["officeName"] = $arrtoparseGemh[0]["officeName"]["value"];
                } else {
                    $company["officeName"] = "-";
                }
                
                if (!empty($arrtoparseGemh) && array_key_exists("office", $arrtoparseGemh[0])) {                    
                    $company["office"] = substr($arrtoparseGemh[0]["office"]["value"], strrpos($arrtoparseGemh[0]["office"]["value"],"/") + 1);
                } else {
                    $company["office"] = "NULL";
                }
                
                if (!empty($arrtoparseGemh) && array_key_exists("gemhNumber", $arrtoparseGemh[0])) {
                    $company["gemhNumber"] = $arrtoparseGemh[0]["gemhNumber"]["value"];
                } else {
                    $company["gemhNumber"] = "-";
                }
                
                if (!empty($arrtoparse) && array_key_exists("gemhEntry", $arrtoparse[0])) {
                    $company["gemhEntry"] = $arrtoparse[0]["gemhEntry"]["value"];
                } else {
                    $company["gemhEntry"] = "-";
                }
                
                if (!empty($arrtoparseGemh) && array_key_exists("statusName", $arrtoparseGemh[0])) {
                    $company["gemhStatus"] = $arrtoparseGemh[0]["statusName"]["value"];
                } else {
                    $company["gemhStatus"] = "-";
                }
                
                if (!empty($arrtoparseGemh) && array_key_exists("statusDate", $arrtoparseGemh[0])) {
                    $company["statusDate"] = date_format(date_create($arrtoparseGemh[0]["statusDate"]["value"]),"d-m-Y");
                } else {
                    $company["statusDate"] = "-";
                }
                
                if (!empty($arrtoparseGemh) && array_key_exists("statusDate", $arrtoparseGemh[0])) {
                    $company["statusDate_noformat"] = "'".date_format(date_create($arrtoparseGemh[0]["statusDate"]["value"]),"Y:m:d H:i:s")."'";
                } else {
                    $company["statusDate_noformat"] = "NULL";
                }
                
                if (!empty($arrtoparseAct) && array_key_exists("cpa", $arrtoparseAct[0])) {
                    $company["cpa"] = $arrtoparseAct[0]["cpa"]["value"];
                } else {
                    $company["cpa"] = "-";
                }

                if (!empty($arrtoparseAct) && array_key_exists("topCpaName", $arrtoparseAct[0])) {
                    $company["topCpaName"] = $arrtoparseAct[0]["topCpaName"]["value"];
                } else {
                    $company["topCpaName"] = "-";
                }
                
                if (!empty($arrtoparse) && array_key_exists("validVat", $arrtoparse[0])) {
                    // $company["validVat"] = substr($arrtoparse[0]["validVat"]["value"], -1);                     
                    if(substr($arrtoparse[0]["validVat"]["value"],0,28)=="ΕΠΙΤΗΔΕΥΜΑΤΙΑΣ")
                     $company["validVat"] = "1";
                    else $company["validVat"] = "0";
                } else {
                    $company["validVat"] = "NULL";
                }

                if (!empty($arrtoparse) && array_key_exists("officialSite", $arrtoparse[0])) {
                    //$company["officialSite"] = substr($arrtoparse[0]["officialSite"]["value"], -1);
                    $company["officialSite"] = $arrtoparse[0]["officialSite"]["value"];
                } else {
                    $company["officialSite"] = null;
                }

                //$company["vatId"] = $vatid;

                if (!empty($arrtoparse) && array_key_exists("vatId", $arrtoparse[0])) {
                    $company["vatId"] = $arrtoparse[0]["vatId"]["value"];
                } else {
                    $company["vatId"] = "NULL";
                }
                
                if (!empty($arrtoparseGemh) && array_key_exists("gemhDate", $arrtoparseGemh[0])) {
                    $company["gemhDate"] = date_format(date_create($arrtoparseGemh[0]["gemhDate"]["value"]),"d-m-Y");
                } else {
                    $company["gemhDate"] = "-";
                }
                
                if (!empty($arrtoparseGemh) && array_key_exists("gemhDate", $arrtoparseGemh[0])) {
                    $company["gemhDate_noformat"] = "'".date_format(date_create($arrtoparseGemh[0]["gemhDate"]["value"]),"Y:m:d H:i:s")."'";                   
                } else {
                    $company["gemhDate_noformat"] = "NULL";
                }
                
                if (!empty($arrtoparse) && array_key_exists("orgTypeCode", $arrtoparse[0])) {
                    $company["orgTypeCode"] = $this->getOrgType($arrtoparse[0]["orgTypeCode"]["value"], $qry_lang);
                } else {
                    $company["orgTypeCode"] = "-";
                }
               
                if (!empty($arrtoparse) && array_key_exists("orgTypeCode", $arrtoparse[0])) {
                    $company["orgTypeCode_nolang"] = $arrtoparse[0]["orgTypeCode"]["value"];
                } else {
                    $company["orgTypeCode_nolang"] = "-";
                }
                
                if (!empty($arrtoparseLoc) && array_key_exists("coords", $arrtoparseLoc[0])) {
                    $company["lng"] = explode(" ", rtrim(str_replace("POINT(","",$arrtoparseLoc[0]["coords"]["value"]),""))[0];
                } else {
                    $company["lng"] = "-";
                }
                
                if (!empty($arrtoparseLoc) && array_key_exists("coords", $arrtoparseLoc[0])) {
                    $company["lat"] = explode(" ", rtrim(str_replace("POINT(","",$arrtoparseLoc[0]["coords"]["value"]),")"))[1];
                } else {
                    $company["lat"] = "-";
                }
                
                if (!empty($arrtoparseAct) && array_key_exists("cpaName", $arrtoparseAct[0])) {
                    $company["cpaName"] = $arrtoparseAct[0]["cpaName"]["value"];
                } else {
                    $company["cpaName"] = "-";
                }
                
                if (!empty($arrtoparse) && array_key_exists("lastUpdateDate", $arrtoparse[0])) {
                    $company["lastupdated"] = date_format(date_create($arrtoparse[0]["lastUpdateDate"]["value"]),"d-m-Y");
                } else {
                    $company["lastupdated"] = "-";
                }
                
                if (!empty($arrtoparse) && array_key_exists("lastUpdateDate", $arrtoparse[0])) {
                    $company["lastupdated_noformat"] = "'".date_format(date_create($arrtoparse[0]["lastUpdateDate"]["value"]),"Y:m:d H:i:s")."'";
                } else {
                    $company["lastupdated_noformat"] = "NULL";
                }
                
                if (!empty($arrtoparseLoc) && array_key_exists("municipName", $arrtoparseLoc[0])) {
                    $company["municipName"] = $arrtoparseLoc[0]["municipName"]["value"];
                } else {
                     $company["municipName"] = "-";
                }
                
                if (!empty($arrtoparseLoc) && array_key_exists("coords", $arrtoparseLoc[0])) {
                    $company["coords"] = $arrtoparseLoc[0]["coords"]["value"];
                } else {
                    $company["coords"] = "NULL";
                }
                
                if (!empty($arrtoparseAct) && array_key_exists("topCpa", $arrtoparseAct[0])) {
                    $company["topCpa"] = $arrtoparseAct[0]["topCpa"]["value"];
                } else {
                    $company["topCpa"] = "-";
                }
                
                if (!empty($arrtoparseAct) && array_key_exists("cpa", $arrtoparseAct[0])) {
                    $company["cpa"] = $arrtoparseAct[0]["cpa"]["value"];
                } else {
                    $company["cpa"] = "-";
                }
                
                if (!empty($arrtoparseAct) && array_key_exists("topCpa", $arrtoparseAct[0])) {
                    $topCpaCode = substr($arrtoparseAct[0]["topCpa"]["value"], strrpos($arrtoparseAct[0]["topCpa"]["value"], "/") + 1);
                    $company["topCpaIcon"] = $this->getTopCpaInfo($topCpaCode)->topcpa_icon;
                } else {
                    $company["topCpaIcon"] = "NULL";
                }
                
                $allSecCpas = array();
                $allSecCpaNames = array();
                
                if ($arrtoparseSecAct) {         
                    if (array_key_exists("secCpaName", $arrtoparseSecAct[0])) {
                        for ($i=0; $i<= count($arrtoparseSecAct)-1; $i++) {
                            $allSecCpas[$i] = $arrtoparseSecAct[$i]["secCpa"]["value"];
                            $allSecCpaNames[$i] = $arrtoparseSecAct[$i]["secCpaName"]["value"];
                        }
                    }                    
                    $company["secCpas"] = $allSecCpas;
                    $company["secCpaNames"] = $allSecCpaNames;
                } else {
                    $company["secCpas"] = array();
                    $company["secCpaNames"] = array();
                }
                
                if (!empty($arrtoparse)) {
                    if (array_key_exists("brandName", $arrtoparse[0])) {
                        $company["brandName"] = $arrtoparse[0]["brandName"]["value"];
                    } else {                    
                        if (array_key_exists("name", $arrtoparse[0])) {
                            $company["brandName"] = $arrtoparse[0]["name"]["value"];
                        } else {
                            $company["brandName"] = $arrtoparse[0]["gemhName"]["value"];
                        }
                    }
                } else {
                    $company["brandName"] = "-";
                }
                
                if (!empty($arrtoparse) && array_key_exists("stopDate", $arrtoparse[0])) {
                    $company["stopDate"] = date_format(date_create($arrtoparse[0]["stopDate"]["value"]),"d-m-Y");
                    $company["stopDateDB"] = "'".date_format(date_create($arrtoparse[0]["stopDate"]["value"]),"Y:m:d H:i:s")."'";
                } else {
                    $company["stopDate"] = "";
                    $company["stopDateDB"] = "NULL";
                }
                
                if (!empty($arrtoparse) && array_key_exists("companyAge", $arrtoparse[0])) {
                    $company["age"] = round($arrtoparse[0]["companyAge"]["value"] / 12); //in years
                } else {
                    $company["age"] = "NULL";
                }
                
                if (!empty($arrtoparse) && array_key_exists("officialSite", $arrtoparse[0])) {
                    $company["webpage"] = $arrtoparse[0]["officialSite"]["value"];
                    $company["webpage_status"] = 1;
                } else {
                    $company["webpage"] = "";
					$company["webpage_status"] = 0;
                }
                
            //} else {
			//	return redirect()->route('home', ['lang' => $lang]);
            //}

            //print_r($company);
                
            return $company;
    }
    
	public function addtolist(Request $request)
    {   
        $isperson = $request->input("isperson");
		$list_limit = Auth::user()->list_capacity_limit;
		//return $isperson;
		$listid =  $request->input("listid");
		$companyid =  $request->input("companyid");
		$isperson =  $request->input("isperson");
		
		$itemsInList = $this->getNumberOfItemsInList($listid);
				
		$listdata = DB::select(DB::raw("SELECT ctl_id
										FROM company_to_list
										WHERE ctl_company_id='".$companyid."' 
										AND ctl_list_id=".$listid."
										LIMIT 1"));
        $listdatap = DB::select(DB::raw("SELECT ctl_id
                                        FROM person_to_list
                                        WHERE ctl_person_id='".$companyid."' 
                                        AND ctl_list_id=".$listid."
                                        LIMIT 1"));
		//return $listdatap;
		if (empty($listdata) && empty($listdatap)) {
			
			if ($itemsInList < $list_limit) {
				
				//insert list
                if ($isperson == 1) {

                    //get data for person - convert to delimited string, $companyid considered to be person vatid

                    // $personController = new PersonProfileController();

                    // $person_management = DB::connection('mysql_gemh')->select("SELECT m.name as companyName, m.vatId as compVat, m.status, m.gemhnumber
                    //         FROM MemberPosition mp
                    //         inner join PersonalData pd
                    //         on pd.id = mp.personId
                    //         inner join Positions p
                    //         on p.id = mp.positionId
                    //         inner join
                    //         PositionsList pl1
                    //         on pl1.id = p.mappedPosition_1
                    //         inner join
                    //         PositionsList pl2
                    //         on pl2.id = p.mappedPosition_2
                    //         inner join Main m
                    //         on m.gemhnumber = mp.gemhnumber
                    //         where pd.vatNumber='".$companyid."'
                    //          order by mp.toDate desc");                    

                    // //print_r($person_management);

                    // $statement = DB::statement("SET SESSION group_concat_max_len = 1000000");
                    // $ownership_participations = DB::connection('mysql_gemh')->select("select mc.vatId, mc.gemhnumber, mc.status, mc.name
                    //         from OwnershipData o
                    //         inner join PersonalData p
                    //         on o.personId=p.id                       
                    //         inner join Main mc
                    //         on mc.gemhNumber = o.gemhNumber
                    //         inner join companyCpa cc 
                    //         on cc.gemhnumber = mc.gemhNumber
                    //         inner join CpaList cl
                    //          on cl.apiCpa=cc.apiCpa
                    //         where p.vatNumber='".$companyid."'
                    //         and cc.main = 1
                    //         and o.rowconfidence > 0.0
                    //         group by mc.vatId
                    //         order by name asc");                     

                    //print_r($ownership_participations);

                 
                    //insert to list, isperson = 1 

                    DB::insert("INSERT INTO person_to_list (ctl_list_id, ctl_person_id) VALUES 
    							(".$listid.", 
    							'".$companyid."')");

                } else {

                    //get data for company - convert to delimited string

                    //insert to list
                    
                    DB::insert("INSERT INTO company_to_list (ctl_list_id, ctl_company_id) VALUES 
                                (".$listid.", 
                                '".$companyid."')");
                }    
								
				return "true";			
			} else {
				return "reachedlimit";
			}
		} else {
			return "false";
		}
						
    }
	
	public function getNumberOfItemsInList($listid) 
	{
		$res = DB::select(DB::raw("SELECT count(*) as num
									FROM company_to_list
									WHERE ctl_list_id=".$listid));

        $resp = DB::select(DB::raw("SELECT count(*) as num
                                    FROM person_to_list
                                    WHERE ctl_list_id=".$listid));
					
		return $res[0]->num + $resp[0]->num;
	}
	
	
	public function removefromlist(Request $request)
    {

		$listid =  $request->input("listid");
		$companyid =  $request->input("companyid");

		if ($listid && $companyid) {

			$delres = DB::delete('delete from company_to_list WHERE ctl_list_id = '.$listid.' AND ctl_company_id = "'.$companyid.'"');

			if ($delres) {
				return "true";
			} else {
				return "false";
			}
		}
		
		return "false";						
    }
    
    
    
    public function getCompanyBraches($vatid)
    {        
        if (Cache::has('companyBranches-'.$vatid)) {
			$json_requestBranches = Cache::get('companyBranches-'.$vatid);	
			
		} else {
			
            $qry_branches = "
                SELECT count(distinct ?gemhEntry) as ?gemhCount 
                FROM <http://linkedeconomy.org/GemhOrganizations> 
                WHERE {
                <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:hasGemhEntry ?gemhEntry
                } 
            ";
            
			$requestBranches = $this->getUrlDbpediaAbstract($qry_branches);			
					
			$json_requestBranches = $this->request($requestBranches, config('sparql.connection_username'), config('sparql.connection_password'));
			Cache::put('companyBranches-'.$vatid, $json_requestBranches, config('sparql.cache_time'));
		}
		
		$res_branches = json_decode($json_requestBranches, true);
        
		return $res_branches;
    }
    
    public function checkVatId($vatid)
    {
        $res = null;

        if (Cache::has('checkVatId-'.$vatid)) {
			$json_requestCheckVatId = Cache::get('checkVatId-'.$vatid);			
        } else {			
            $qry_check = "
                SELECT distinct ?correctVatId
                FROM <http://linkedeconomy.org/GemhOrganizations> 
                WHERE {
                <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:correctVatId ?correctVatId.                
                } 
            ";

			$requestCheck = $this->getUrlDbpediaAbstract($qry_check);			
					
			$json_requestCheckVatId = $this->request($requestCheck, config('sparql.connection_username'), config('sparql.connection_password'));
			Cache::put('checkVatId-'.$vatid, $json_requestCheckVatId, config('sparql.cache_time'));
		}
		
		$res_check = json_decode($json_requestCheckVatId, true);
        if ($res_check["results"]["bindings"]) {
            $res = $res_check["results"]["bindings"][0]["correctVatId"]["value"];
        }
        
		return $res;
    }

	
	public function getCompanyProfile($vatid, $qry_lang, $numOfBranches = null, $isCorrectVatId = null) 
	{            

        //echo $vatid.", ".$numOfBranches.", ".$isCorrectVatId."<br>";

		if ($numOfBranches == 0) {
            // not registered in Gemh

            $qry_company = "
                    PREFIX rov: <http://www.w3.org/ns/regorg#>
                    PREFIX gr:<http://purl.org/goodrelations/v1#>
                    SELECT ?legalName ?name ?brandName ?orgTypeCode ?regDate ?stopDate ?validVat ?companyAge ?lastUpdateDate ?vatId
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/Taxonomies>
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid.">  gr:vatID ?vatId ;
                            dcterms:modified ?lastUpdateDate ; 
                            gr:name ?name .
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:firmDescription ?validVat . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> rov:orgType ?orgType . ?orgType skos:notation ?orgTypeCode . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:registrationDate ?regDate . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:stopDate ?stopDate . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:hasBrand ?brand . ?brand gr:name ?brandName . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:legalName ?legalName . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:companyAge ?companyAge . }
                    }
                ";

        } elseif ($numOfBranches == 1) {
            // has no brances

            $qry_company = "                    
                    PREFIX rov: <http://www.w3.org/ns/regorg#>
                    PREFIX gr:<http://purl.org/goodrelations/v1#>
                    SELECT ?legalName ?gemhName ?gemhEntry ?brandName ?orgTypeCode ?regDate ?stopDate ?validVat ?officialSite ?companyAge ?lastUpdateDate ?vatId
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/Taxonomies>
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:vatID ?vatId ;
                         dcterms:modified ?lastUpdateDate ;
                         elod:hasGemhEntry ?gemhEntry .
                    OPTIONAL{ ?gemhEntry elod:gemhName ?gemhName . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:firmDescription ?validVat . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> rov:orgType ?orgType . ?orgType skos:notation ?orgTypeCode . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:registrationDate ?regDate . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:stopDate ?stopDate . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:hasBrand ?brand . ?brand gr:name ?brandName . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:legalName ?legalName . }
                    OPTIONAL{ ?gemhEntry elod:officialSite ?officialSite . }
                    OPTIONAL{ ?gemhEntry elod:companyAge ?companyAge . }
                    } LIMIT 1
                ";
        } else {
                       
            if ($isCorrectVatId == 1) {
                
                $qry_company = "
                    PREFIX rov: <http://www.w3.org/ns/regorg#>
                    PREFIX gr:<http://purl.org/goodrelations/v1#>
                    SELECT ?legalName ?gemhName ?gemhEntry ?brandName ?orgTypeCode ?regDate ?stopDate ?validVat ?officialSite ?companyAge ?lastUpdateDate ?vatId
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/Taxonomies> 
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:vatID ?vatId ;
                         elod:hasGemhEntry ?gemhEntry . 
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> dcterms:modified ?lastUpdateDate . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:firmDescription ?validVat . }
                    OPTIONAL{ ?gemhEntry elod:gemhName ?gemhName . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> rov:orgType ?orgType . ?orgType skos:notation ?orgTypeCode . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:registrationDate ?regDate . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:stopDate ?stopDate . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:hasBrand ?brand . ?brand gr:name ?brandName . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:legalName ?legalName . }
                    OPTIONAL{ ?gemhEntry elod:officialSite ?officialSite . }
                    OPTIONAL{ ?gemhEntry elod:companyAge ?companyAge . }
                    filter (strEnds(str(?gemhEntry), \"000\"))
                    }
                ";
            } else {
               
                $qry_company = "
                    PREFIX rov: <http://www.w3.org/ns/regorg#>
                    PREFIX gr:<http://purl.org/goodrelations/v1#>
                    SELECT ?legalName ?gemhName ?gemhEntry ?brandName ?orgTypeCode ?regDate ?stopDate ?validVat ?officialSite ?companyAge ?lastUpdateDate ?vatId
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/Taxonomies> 
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:vatID ?vatId;
                         elod:hasGemhEntry ?gemhEntry . 
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> dcterms:modified ?lastUpdateDate  . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:firmDescription ?validVat . }
                    OPTIONAL{ ?gemhEntry elod:gemhName ?gemhName . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> rov:orgType ?orgType . ?orgType skos:notation ?orgTypeCode . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:registrationDate ?regDate . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:stopDate ?stopDate . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:hasBrand ?brand . ?brand gr:name ?brandName . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> gr:legalName ?legalName . }
                    OPTIONAL{ ?gemhEntry elod:officialSite ?officialSite . }
                    OPTIONAL{ ?gemhEntry elod:companyAge ?companyAge . }
                    filter (strEnds(str(?gemhEntry), \"000\"))
                    }  LIMIT 1
                ";
            }            
        }  
        
        if (Cache::has('companyProfile-'.$vatid.'-'.$qry_lang)) {    
            $json_requestCompany = Cache::get('companyProfile-'.$vatid.'-'.$qry_lang);	            
        } else {            
            $requestCompany = $this->getUrlDbpediaAbstract($qry_company);
            $json_requestCompany = $this->request($requestCompany, config('sparql.connection_username'), config('sparql.connection_password'));
            Cache::put('companyProfile-'.$vatid.'-'.$qry_lang, $json_requestCompany, config('sparql.cache_time'));
        }
        
		$res_company = json_decode($json_requestCompany, true);      
		
		return $res_company;	
		
	}
    
    public function getCompanyProfileLocation($vatid, $qry_lang, $numOfBranches = null, $isCorrectVatId = null) 
	{        
		if ($numOfBranches == 0) {     
            //not registered in Gemh

            $qry_company_loc = "
                PREFIX vcard2006: <http://www.w3.org/2006/vcard/ns#>
                PREFIX geo: <http://www.opengis.net/ont/geosparql#>
                SELECT ?municipName ?street ?locality ?pcode ?coords
                FROM <http://linkedeconomy.org/GemhOrganizations>  
                FROM <http://linkedeconomy.org/GeoData> 
                WHERE {
                <http://linkedeconomy.org/resource/Organization/".$vatid."> vcard2006:hasAddress ?address .
                ?address vcard2006:postal-code ?pcode .
                OPTIONAL{
                ?pcodeArea elodGeo:postalCode ?pcode . 
                ?municip a elodGeo:Municipality ;
                         elodGeo:hasPart ?pcodeArea ;
                         elodGeo:name ?municipName.
                filter langMatches(lang(?municipName), '".$qry_lang."')
                }
                OPTIONAL{ ?address vcard2006:locality ?locality . }
                OPTIONAL{ ?address vcard2006:street-address ?street . }
                OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:hasRelatedFeature ?feature . ?feature geo:hasGeometry ?geometry . ?geometry geo:asWKT ?coords. }
                }
            ";

        } elseif ($numOfBranches == 1) { 
            // has no branch

            $qry_company_loc = "                
                PREFIX vcard2006: <http://www.w3.org/2006/vcard/ns#>
                PREFIX geo: <http://www.opengis.net/ont/geosparql#>
                SELECT ?municipName ?street ?locality ?pcode ?coords
                FROM <http://linkedeconomy.org/GemhOrganizations>  
                FROM <http://linkedeconomy.org/GeoData> 
                WHERE {
                <http://linkedeconomy.org/resource/Organization/".$vatid."> vcard2006:hasAddress ?address .
                ?address vcard2006:postal-code ?pcode .
                OPTIONAL{
                ?pcodeArea elodGeo:postalCode ?pcode . 
                ?municip a elodGeo:Municipality ;
                         elodGeo:hasPart ?pcodeArea ;
                         elodGeo:name ?municipName.
                filter langMatches(lang(?municipName), '".$qry_lang."')
                }
                OPTIONAL{ ?address vcard2006:locality ?locality . }
                OPTIONAL{ ?address vcard2006:street-address ?street . }
                OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:hasRelatedFeature ?feature . ?feature geo:hasGeometry ?geometry . ?geometry geo:asWKT ?coords. }
                }

            ";
        } else {
                      
            // has branches           

            if ($isCorrectVatId == 1) {
                $qry_company_loc = "
                    PREFIX vcard2006: <http://www.w3.org/2006/vcard/ns#>
                    PREFIX geo: <http://www.opengis.net/ont/geosparql#>
                    SELECT ?municipName ?street ?locality ?pcode ?coords
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/GeoData>
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> vcard2006:hasAddress ?address .
                    ?address vcard2006:postal-code ?pcode .
                    OPTIONAL{
                    ?pcodeArea elodGeo:postalCode ?pcode .
                    ?municip a elodGeo:Municipality ;
                             elodGeo:hasPart ?pcodeArea ;
                             elodGeo:name ?municipName.
                    filter langMatches(lang(?municipName), '".$qry_lang."')
                    }
                    OPTIONAL{ ?address vcard2006:locality ?locality . }
                    OPTIONAL{ ?address vcard2006:street-address ?street . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:hasRelatedFeature
                    ?feature . ?feature geo:hasGeometry ?geometry . ?geometry geo:asWKT ?coords. }
                    filter ( strlen(str(?address)) = 51 )
                    filter ( strlen(str(?feature)) = 51 )
                    }
                ";
            } else {
                $qry_company_loc = "
                    PREFIX vcard2006: <http://www.w3.org/2006/vcard/ns#>
                    PREFIX geo: <http://www.opengis.net/ont/geosparql#>
                    SELECT ?municipName ?street ?locality ?pcode ?coords
                    FROM <http://linkedeconomy.org/GemhOrganizations>  
                    FROM <http://linkedeconomy.org/GeoData> 
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> vcard2006:hasAddress ?address .
                    ?address vcard2006:postal-code ?pcode .
                    OPTIONAL{
                    ?pcodeArea elodGeo:postalCode ?pcode . 
                    ?municip a elodGeo:Municipality ;
                             elodGeo:hasPart ?pcodeArea ;
                             elodGeo:name ?municipName.
                    filter langMatches(lang(?municipName), '".$qry_lang."')
                    }
                    OPTIONAL{ ?address vcard2006:locality ?locality . }
                    OPTIONAL{ ?address vcard2006:street-address ?street . }
                    OPTIONAL{ <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:hasRelatedFeature ?feature . ?feature geo:hasGeometry ?geometry . ?geometry geo:asWKT ?coords. }
                    filter (strEnds(str(?geometry), \"000\"))
                    filter (strEnds(str(?feature), \"000\"))
                    }
                    Limit 1
                ";
            }            
        }
		
		if (Cache::has('companyProfileLocation-'.$vatid.'-'.$qry_lang)) {
			$json_requestCompanyLoc = Cache::get('companyProfileLocation-'.$vatid.'-'.$qry_lang);				
		} else {			
			$requestCompanyLoc = $this->getUrlDbpediaAbstract($qry_company_loc);			
			$json_requestCompanyLoc = $this->request($requestCompanyLoc, config('sparql.connection_username'), config('sparql.connection_password'));
			Cache::put('companyProfileLocation-'.$vatid.'-'.$qry_lang, $json_requestCompanyLoc, config('sparql.cache_time'));
		}
		
		$res_company = json_decode($json_requestCompanyLoc, true);
		
		return $res_company;	
		
	}
    
    public function getCompanyProfileMainActivities($vatid, $qry_lang, $numOfBranches = null, $isCorrectVatId = null) 
	{
		if ($numOfBranches == 0) {
            // not registered in Gemh

            $qry_company_act = "
                    PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                    SELECT ?topCpaName ?topCpa ?cpa ?cpaName 
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/CPA> 
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:mainCpa ?cpa .
                    ?cpa skos:broaderTransitive ?topCpa ;
                         skos:prefLabel ?cpaName .
                    ?topCpa skos:altLabel ?topCpaName .
                    filter langMatches(lang(?topCpaName), '".$qry_lang."')
                    filter langMatches(lang(?cpaName), '".$qry_lang."')
                    }    
                ";

        } elseif ($numOfBranches == 1) {     
            // has no branch

            $qry_company_act = "                    
                    PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                    SELECT  ?cpa ?topCpaName ?topCpa ?cpa ?cpaName 
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/CPA> 
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:mainCpa ?cpa .
                    ?cpa skos:broaderTransitive ?topCpa ;
                         skos:prefLabel ?cpaName .
                    ?topCpa skos:altLabel ?topCpaName .
                    filter langMatches(lang(?topCpaName), '".$qry_lang."')
                    filter langMatches(lang(?cpaName), '".$qry_lang."')
                    } 
                ";
        } else {
            // has branches

            if ($isCorrectVatId == 1) {
                $qry_company_act = "
                    PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                    SELECT  ?cpa ?topCpaName ?topCpa ?cpa ?cpaName
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/CPA>
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:mainCpa ?cpa .
                    ?cpa skos:broaderTransitive ?topCpa ;
                         skos:prefLabel ?cpaName .
                    ?topCpa skos:altLabel ?topCpaName .
                    filter langMatches(lang(?topCpaName), '".$qry_lang."')
                    filter langMatches(lang(?cpaName), '".$qry_lang."')
                    }
                ";
            } else {
                $qry_company_act = "
                    PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                    SELECT  ?cpa ?topCpaName ?topCpa ?cpa ?cpaName 
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/CPA> 
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:mainCpa ?cpa .
                    ?cpa skos:broaderTransitive ?topCpa ;
                         skos:prefLabel ?cpaName .
                    ?topCpa skos:altLabel ?topCpaName .
                    filter langMatches(lang(?topCpaName), '".$qry_lang."')
                    filter langMatches(lang(?cpaName), '".$qry_lang."')
                    }   
                ";
            }            
        }
		
		if (Cache::has('companyProfileMainActivities-'.$vatid.'-'.$qry_lang)) {
			$json_requestCompanyAct = Cache::get('companyProfileMainActivities-'.$vatid.'-'.$qry_lang);				
		} else {
			$requestCompanyAct = $this->getUrlDbpediaAbstract($qry_company_act);			
			$json_requestCompanyAct = $this->request($requestCompanyAct, config('sparql.connection_username'), config('sparql.connection_password'));
			Cache::put('companyProfileMainActivities-'.$vatid.'-'.$qry_lang, $json_requestCompanyAct, config('sparql.cache_time'));
		}
		
		$res_company = json_decode($json_requestCompanyAct, true);
		
		return $res_company;		
	}
    
    public function getCompanyProfileSecActivities($vatid, $qry_lang, $numOfBranches = null, $isCorrectVatId = null) 
	{
        if ($numOfBranches == 0) {
            // not registered in Gemh

            $qry_company_act = "
                    PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                    SELECT ?secCpa ?secCpaName
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/CPA> 
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:secondaryCpa ?secCpa .
                    ?secCpa skos:prefLabel ?secCpaName . 
                    filter langMatches(lang(?secCpaName ), '".$qry_lang."')
                    }  
                ";

        } elseif ($numOfBranches == 1) {
            // has no branch

            $qry_company_act = "                      
                    PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                    SELECT ?secCpa ?secCpaName
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/CPA> 
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:secondaryCpa ?secCpa .
                    ?secCpa skos:prefLabel ?secCpaName . 
                    filter langMatches(lang(?secCpaName ), '".$qry_lang."')
                    }
                ";
        } else {
            // has branches
                       
            if ($isCorrectVatId == 1) {
                $qry_company_act = "
                    PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                    SELECT ?secCpa ?secCpaName
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/CPA>
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:secondaryCpa ?secCpa .
                    ?secCpa skos:prefLabel ?secCpaName .
                    filter langMatches(lang(?secCpaName ), '".$qry_lang."')
                    }
                ";
            } else {
                $qry_company_act = "
                    PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                    SELECT ?secCpa ?secCpaName
                    FROM <http://linkedeconomy.org/GemhOrganizations>
                    FROM <http://linkedeconomy.org/CPA> 
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:secondaryCpa ?secCpa .
                    ?secCpa skos:prefLabel ?secCpaName .
                    filter langMatches(lang(?secCpaName ), '".$qry_lang."')
                    }    
                ";
            }            
        }
		
		if (Cache::has('companyProfileSecActivities-'.$vatid.'-'.$qry_lang)) {
			$json_requestCompanySecAct = Cache::get('companyProfileSecActivities-'.$vatid.'-'.$qry_lang);				
		} else {
			$requestCompanySecAct = $this->getUrlDbpediaAbstract($qry_company_act);			
			$json_requestCompanySecAct = $this->request($requestCompanySecAct, config('sparql.connection_username'), config('sparql.connection_password'));
			Cache::put('companyProfileSecActivities-'.$vatid.'-'.$qry_lang, $json_requestCompanySecAct, config('sparql.cache_time'));
		}
		
		$res_company = json_decode($json_requestCompanySecAct, true);
		
		return $res_company;		
	}
    
    public function getCompanyProfileGemh($vatid, $qry_lang, $numOfBranches = null, $isCorrectVatId = null) 
	{
              
		if ($numOfBranches == 1) {             
            // has no brances

            $qry_company_gemh = "
                    SELECT ?gemhName ?gemhNumber ?gemhDate ?statusName ?statusDate ?officeName ?office ?gemhUrl
                    FROM <http://linkedeconomy.org/GemhOrganizations> 
                    FROM <http://linkedeconomy.org/Taxonomies> 
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:hasGemhEntry ?gemhEntry .
                    ?gemhEntry elod:gemhNumber ?gemhNumber ;
                               elod:gemhUrl ?gemhUrl .
                    Optional { ?gemhEntry elod:gemhDate ?gemhDate . }
                    Optional { ?gemhEntry elod:gemhOffice ?office . ?office skos:prefLabel ?officeName . filter langMatches(lang(?officeName), '".$qry_lang."') }
                    Optional { ?gemhEntry elod:gemhStatus ?gemhStatus . ?gemhStatus skos:prefLabel ?statusName . filter langMatches(lang(?statusName ), '".$qry_lang."') }
                    Optional { ?gemhEntry elod:statusDate ?statusDate . }
                    Optional { ?gemhEntry elod:gemhName ?gemhName . }
                    }
                ";
        } else {
            // has branches

            if ($isCorrectVatId == 1) {
                $qry_company_gemh = "
                    SELECT ?gemhName ?gemhNumber ?gemhDate ?statusName ?statusDate ?officeName ?office ?gemhUrl
                    FROM <http://linkedeconomy.org/GemhOrganizations> 
                    FROM <http://linkedeconomy.org/Taxonomies> 
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:hasGemhEntry ?gemhEntry .
                    ?gemhEntry elod:gemhNumber ?gemhNumber ;
                               elod:gemhUrl ?gemhUrl .
                    Optional { ?gemhEntry elod:gemhDate ?gemhDate . }
                    Optional { ?gemhEntry elod:gemhOffice ?office . ?office skos:prefLabel ?officeName . filter langMatches(lang(?officeName), '".$qry_lang."') }
                    Optional { ?gemhEntry elod:gemhStatus ?gemhStatus . ?gemhStatus skos:prefLabel ?statusName . filter langMatches(lang(?statusName ), '".$qry_lang."') }
                    Optional { ?gemhEntry elod:statusDate ?statusDate . }
                    Optional { ?gemhEntry elod:gemhName ?gemhName . }
                    filter (strEnds(str(?gemhEntry), \"000\"))
                    }    
                ";
            } else {
                $qry_company_gemh = "
                    SELECT ?gemhName ?gemhNumber ?gemhDate ?statusName ?statusDate ?officeName ?office ?gemhUrl
                    FROM <http://linkedeconomy.org/GemhOrganizations> 
                    FROM <http://linkedeconomy.org/Taxonomies> 
                    WHERE {
                    <http://linkedeconomy.org/resource/Organization/".$vatid."> elod:hasGemhEntry ?gemhEntry .
                    ?gemhEntry elod:gemhNumber ?gemhNumber ;
                               elod:gemhUrl ?gemhUrl .
                    Optional { ?gemhEntry elod:gemhDate ?gemhDate . }
                    Optional { ?gemhEntry elod:gemhOffice ?office . ?office skos:prefLabel ?officeName . filter langMatches(lang(?officeName), '".$qry_lang."') }
                    Optional { ?gemhEntry elod:gemhStatus ?gemhStatus . ?gemhStatus skos:prefLabel ?statusName . filter langMatches(lang(?statusName ), '".$qry_lang."') }
                    Optional { ?gemhEntry elod:statusDate ?statusDate . }
                    Optional { ?gemhEntry elod:gemhName ?gemhName . }
                    filter (strEnds(str(?gemhEntry), \"000\"))
                    } Limit 1  
                ";
            }            
        }
		
		if (Cache::has('companyProfileGemh-'.$vatid.'-'.$qry_lang)) {
			$json_requestCompanyGemh = Cache::get('companyProfileGemh-'.$vatid.'-'.$qry_lang);				
		} else {			
			$requestCompanyGemh = $this->getUrlDbpediaAbstract($qry_company_gemh);								
			$json_requestCompanyGemh = $this->request($requestCompanyGemh, config('sparql.connection_username'), config('sparql.connection_password'));
			Cache::put('companyProfileGemh-'.$vatid.'-'.$qry_lang, $json_requestCompanyGemh, config('sparql.cache_time'));
		}
		
		$res_company = json_decode($json_requestCompanyGemh, true);
		
		return $res_company;	
		
	}

    public function partialtables(Request $request)
    {
        $table = $request->input("table");
        $tab = $request->input("tab");
        $vatid = $request->input("vatid");
        $lang = $request->input("lang");
        $type = $request->input("type");

        if ($lang == null) {
            App::setLocale("gr");
        } else {
            App::setLocale($lang);
        }       
        
        if ($lang == "en") {            
            $qry_lang = "en";           
        } else {                    
            $lang = "gr";
            $qry_lang = "el";
        }

        if ($table == "diaugeia") 
        {
            $res_diaugeia = array();

            if ($tab == 1) 
            {
                $qry_diaugeia = "PREFIX pc: <http://purl.org/procurement/public-contracts#>
                    PREFIX elod: <http://linkedeconomy.org/ontology#>
                    PREFIX gr: <http://purl.org/goodrelations/v1#>
                    PREFIX dcterms: <http://purl.org/dc/terms/>
                    PREFIX dc: <http://purl.org/dc/elements/1.1/>
                    PREFIX org: <http://www.w3.org/ns/org#>
                    SELECT distinct (str(?date) AS ?date) ?org (xsd:decimal(?amount) AS ?paymentAmount) (str(?cpvName) AS ?cpvName) (str(?ada) AS ?ada) (str(?documentUrl) AS ?documentUrl) 
                    FROM <http://publicspending.net/DiavgeiaI/CPV>
                    FROM <http://linkedeconomy.org/Diavgeia>
                    WHERE {
                    ?expenseApproval elod:hasExpenditureLine ?expenditureLine ;
                                    dcterms:issued ?date ;
                                    elod:ada ?ada ;
                                    elod:buyer ?org ;
                                    rdf:type elod:ExpenseApprovalItem .
                    ?expenditureLine elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;
                                         elod:amount ?ups .
                    ?ups gr:hasCurrencyValue ?amount .
                    OPTIONAL { ?expenseApproval elod:documentUrl ?documentUrl . } .
                    OPTIONAL { ?expenditureLine elod:hasCpv ?cpv . ?cpv skos:prefLabel ?cpvName . filter langMatches(lang(?cpvName), 'el')
                     } .
                    FILTER NOT EXISTS {?expenseApproval elod:hasCorrectedDecision ?correctedDecision} .
                    FILTER NOT EXISTS {?ups elod:riskError \"1\"^^xsd:boolean} .
                    }
                ";           

            } elseif ($tab == 2) {

                $qry_diaugeia = "
                    PREFIX pc: <http://purl.org/procurement/public-contracts#>
                    PREFIX elod: <http://linkedeconomy.org/ontology#>
                    PREFIX gr: <http://purl.org/goodrelations/v1#>
                    PREFIX dcterms: <http://purl.org/dc/terms/>
                    PREFIX dc: <http://purl.org/dc/elements/1.1/>
                    PREFIX org: <http://www.w3.org/ns/org#>

                    SELECT distinct (str(?date) as ?date) ?org (xsd:decimal(?amount) AS ?paymentAmount) (str(?cpvName) AS ?cpvName) (str(?ada) AS ?ada) (str(?documentUrl) AS ?documentUrl) 
                    FROM <http://publicspending.net/DiavgeiaI/CPV>
                    FROM <http://linkedeconomy.org/Diavgeia>
                    WHERE {
                    ?payment elod:hasExpenditureLine ?expenditureLine ;
                                    dcterms:issued ?date ;
                                    elod:ada ?ada ;
                                    elod:buyer ?org ;
                                    rdf:type elod:SpendingItem .
                    ?expenditureLine elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;
                                     elod:amount ?ups .
                    ?ups gr:hasCurrencyValue ?amount .
                    OPTIONAL { ?payment elod:documentUrl ?documentUrl . } .
                    OPTIONAL { ?expenditureLine elod:hasCpv ?cpv . ?cpv skos:prefLabel ?cpvName .  filter langMatches(lang(?cpvName), 'el') } .
                    FILTER NOT EXISTS {?payment elod:hasCorrectedDecision ?correctedDecision} .
                    FILTER NOT EXISTS {?ups elod:riskError \"1\"^^xsd:boolean} .
                    }
                ";           

            } elseif ($tab == 3) {

                $qry_diaugeia = "
                    PREFIX pc: <http://purl.org/procurement/public-contracts#>
                    PREFIX elod: <http://linkedeconomy.org/ontology#>
                    PREFIX gr: <http://purl.org/goodrelations/v1#>
                    PREFIX dcterms: <http://purl.org/dc/terms/>
                    PREFIX dc: <http://purl.org/dc/elements/1.1/>
                    
                    SELECT distinct (str(?date) AS ?date) ?org (xsd:decimal(?amount) AS ?paymentAmount) (str(?cpvName) AS ?cpvName) (str(?ada) AS ?ada) (str(?documentUrl) AS ?documentUrl) 
                    FROM <http://publicspending.net/DiavgeiaI/CPV>
                    FROM <http://linkedeconomy.org/Diavgeia>
                    WHERE {
                    ?contract elod:decisionTypeId ?cntrTypeId ;
                              dcterms:issued ?date ;
                              elod:ada ?ada ;
                              elod:buyer ?org ;
                              elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;
                              pc:agreedPrice ?ups ;
                              rdf:type pc:Contract .
                    ?ups gr:hasCurrencyValue ?amount .
                    OPTIONAL { ?contract pc:mainObject ?cpv . ?cpv skos:prefLabel ?cpvName . filter langMatches(lang(?cpvName), 'el')  } .
                    OPTIONAL { ?contract elod:documentUrl ?documentUrl . } .
                    FILTER NOT EXISTS {?contract elod:hasCorrectedDecision ?correctedDecision} .
                    FILTER NOT EXISTS {?ups elod:riskError \"1\"^^xsd:boolean} .
                    FILTER (?cntrTypeId = \"Δ.1\"^^xsd:string) .
                    }

                ";
          
            } else {

                $qry_diaugeia = "
                    PREFIX pc: <http://purl.org/procurement/public-contracts#>
                    PREFIX elod: <http://linkedeconomy.org/ontology#>
                    PREFIX gr: <http://purl.org/goodrelations/v1#>
                    PREFIX dcterms: <http://purl.org/dc/terms/>
                    PREFIX dc: <http://purl.org/dc/elements/1.1/>
                    
                    SELECT distinct (str(?date) AS ?date) ?org (xsd:decimal(?amount) AS ?paymentAmount) (str(?subject) AS ?subject) (str(?ada) AS ?ada) (str(?documentUrl) AS ?documentUrl)  
                    FROM <http://publicspending.net/DiavgeiaI/CPV>
                    FROM <http://linkedeconomy.org/Diavgeia>
                    WHERE {
                    ?contract elod:decisionTypeId ?cntrTypeId ;
                              dcterms:issued ?date ;
                              elod:ada ?ada ;
                              elod:buyer ?org ;
                              elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;
                              pc:actualPrice ?ups ;
                                 dc:subject ?subject ;
                                 rdf:type pc:Contract .
                   ?ups gr:hasCurrencyValue ?amount .
                    OPTIONAL { ?contract elod:documentUrl ?documentUrl . } .
                    FILTER NOT EXISTS {?contract elod:hasCorrectedDecision ?correctedDecision} .
                    FILTER NOT EXISTS {?ups elod:riskError \"1\"^^xsd:boolean} .
                    FILTER (?cntrTypeId = \"Δ.2.2\"^^xsd:string) .
                    }

                ";
                
            }


            if (Cache::has('moneypartial-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang."-".$type)) {
                $json_requestPartial = Cache::get('moneypartial-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang."-".$type);              
            } else {            
                //$requestPartial = $this->getUrlDbpediaAbstract($qry_diaugeia);                              
                $json_requestPartial = $this->request($qry_diaugeia, config('sparql.connection_username'), config('sparql.connection_password'));
                Cache::put('moneypartial-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang."-".$type, $json_requestPartial, config('sparql.cache_time'));
            }
            
            $res_diaugeia = json_decode($json_requestPartial, true);
            
            for ($i=0; $i<= count($res_diaugeia["results"]["bindings"])-1; $i++) {
                $orgname_temp = $res_diaugeia["results"]["bindings"][$i]["org"]["value"];

                $qry_orgname = " 
                    SELECT ?buyerAfm ?buyerName1 ?buyerName2
                    FROM <http://linkedeconomy.org/OrganizationalUnits>
                    WHERE {
                      {
                      <".$orgname_temp."> rov:unitOf ?buyer . 
                       OPTIONAL { ?buyer gr:vatID ?buyerAfm . }
                       OPTIONAL { ?buyer gr:name ?buyerName2 . }
                       OPTIONAL { ?buyer gr:legalName ?buyerName1 . }
                       }
                       UNION
                       {
                       OPTIONAL { <".$orgname_temp."> gr:vatID ?buyerAfm . }
                       OPTIONAL { <".$orgname_temp."> gr:name ?buyerName2 . }
                       OPTIONAL { <".$orgname_temp."> gr:legalName ?buyerName1 . }
                       }
                    }
                    order by desc (?buyerAfm)                  
                ";

                if (Cache::has('moneypartial-org-'.$orgname_temp)) {
                    $json_requestPartial_orgname = Cache::get('moneypartial-org-'.$orgname_temp);              
                } else {            
                    //$requestPartial = $this->getUrlDbpediaAbstract($qry_diaugeia);                              
                    $json_requestPartial_orgname = $this->request($qry_orgname, config('sparql.connection_username'), config('sparql.connection_password'));
                    Cache::put('moneypartial-org-'.$orgname_temp, $json_requestPartial_orgname, config('sparql.cache_time'));
                }

                $res_orgnane = json_decode($json_requestPartial_orgname, true);

                if (array_key_exists("buyerName1", $res_orgnane["results"]["bindings"][0])) {
                    $res_diaugeia["results"]["bindings"][$i]["buyerName1"]["value"] = $res_orgnane["results"]["bindings"][0]["buyerName1"]["value"];
                } elseif (array_key_exists("buyerName2", $res_orgnane["results"]["bindings"][0])) {
                    $res_diaugeia["results"]["bindings"][$i]["buyerName2"]["value"] = $res_orgnane["results"]["bindings"][0]["buyerName2"]["value"];
                } 
            }
////////////////XSOZ START - UPDATE DATE            
                $q_date="SELECT MAX(?date) as ?date MAX(?lastSearched) as ?lastSearched 
                            FROM <http://linkedeconomy.org/Diavgeia>
                            FROM <http://linkedeconomy.org/GemhOrganizations>
                            FROM <http://linkedeconomy.org/Persons>
                            WHERE { 
                            {
                            ?expenseApproval elod:hasExpenditureLine ?expenditureLine ; 
                                 elod:submissionTimestamp ?date . 
                            ?expenditureLine elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> . 
                            } 
                            UNION 
                            { 
                            ?contract elod:submissionTimestamp ?date ; 
                                      elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> . 
                            }
                            UNION 
                            {
                            <http://linkedeconomy.org/resource/".$type."/".$vatid."> elod:diavgeiaLastSearch ?lastSearched . 
                            } 
                            }";
                    
              $json_requestPartial_date = $this->request($q_date, config('sparql.connection_username'), config('sparql.connection_password'));
              $res_date_tmp = json_decode($json_requestPartial_date, true);
              
              if (array_key_exists("lastSearched", $res_date_tmp["results"]["bindings"][0])) {
                 $res_date = date_format(date_create(explode("+",$res_date_tmp["results"]["bindings"][0]["lastSearched"]["value"])[0]),"d-m-Y");
              } else if (array_key_exists("date", $res_date_tmp["results"]["bindings"][0])) {
                 $res_date = date_format(date_create(explode("+",$res_date_tmp["results"]["bindings"][0]["date"]["value"])[0]),"d-m-Y");
              } else {
                 $res_date = "unknown";
              }
              if ($res_date==null) {
                 $res_date = "unknown";
              }
             
////////////////XSOZ END 
            return view('company.partialtable')->with(compact('vatid', 'res_diaugeia', 'res_date', 'type', 'tab', 'table', 'lang'));
        }

        if ($table == "kimdis") 
        {
            $res_kimdis = array();

            if ($tab == 1) {
                $qry_kimdis = "
                    SELECT distinct (str(?date) as ?date) (SAMPLE(str(?buyerName)) as ?buyerName) (str(?cpvName) as ?cpvName)  (str(?title) as ?title) (str(?contractId) as ?contractId) (xsd:decimal(?amount) as ?amount)
                    FROM <http://linkedeconomy.org/EprocurementUpdate> 
                    FROM <http://publicspending.net/DiavgeiaI/CPV>
                    FROM <http://linkedeconomy.org/GemhOrganizations> 
                    WHERE {
                    ?contract pc:agreedPrice ?ups ; 
                              dcterms:title ?title ;
                              elod:signatureDate ?date ;
                              elod:contractId ?contractId ;
                              elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> .
                    OPTIONAL{?contract elod:buyer ?buyer . ?buyer gr:name ?buyerName }
                    ?ups gr:hasCurrencyValue ?amount . 
                    OPTIONAL{
                    ?contract pc:item ?item . 
                    ?item gr:includesObject ?tqn . 
                    ?tqn gr:typeOfGood ?some . 
                    ?some elod:productCategory ?cpv . 
                    ?cpv skos:prefLabel ?cpvName . filter langMatches(lang(?cpvName), 'el')
                    }
                    } 
                ";
              

            } else {

                $qry_kimdis = "
                    SELECT distinct (str(?date) as ?date) (str(?contractId) as ?contractId) (SAMPLE(str(?buyerName)) as ?buyerName) (str(?cpvName) as ?cpvName) (str(?title) as ?title) (xsd:decimal(?amount) as ?amount)
                    FROM <http://linkedeconomy.org/EprocurementUpdate> 
                    FROM <http://publicspending.net/DiavgeiaI/CPV>
                    FROM <http://linkedeconomy.org/GemhOrganizations> 
                    WHERE {
                    ?contract pc:actualPrice ?ups ; 
                              dcterms:title ?title ;
                              elod:signatureDate ?date ; 
                              elod:contractId ?contractId ;
                              elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> .
                    OPTIONAL{?contract elod:buyer ?buyer . ?buyer gr:name ?buyerName }
                    ?ups gr:hasCurrencyValue ?amount . 
                    OPTIONAL{
                    ?contract pc:item ?item .
                    ?item gr:includesObject ?tqn . 
                    ?tqn gr:typeOfGood ?some . 
                    ?some elod:productCategory ?cpv . 
                    ?cpv skos:prefLabel ?cpvName . 
                    filter langMatches(lang(?cpvName), 'el')
                    }
                    }
                ";

            }
            
            if (Cache::has('moneypartial-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang)) {
                $json_requestPartial = Cache::get('moneypartial-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang);              
            } else {            
                                           
                $json_requestPartial = $this->request($qry_kimdis, config('sparql.connection_username'), config('sparql.connection_password'));
                Cache::put('moneypartial-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang, $json_requestPartial, config('sparql.cache_time'));
            }
            
            $res_kimdis = json_decode($json_requestPartial, true);

            //print_r ($res_kimdis);
            $resfile = null;

            if ($tab==1) {  
                     
                for ($i=0; $i<= count($res_kimdis["results"]["bindings"])-1; $i++) {
                    $resfile = DB::connection('mysql_khmdhs')->select("select file from khmdhs.ContractInformation where referenceNumber='".$res_kimdis["results"]["bindings"][$i]["contractId"]["value"]."' and file like 'http%'
                                                                       union
                                                                       select file from khmdhsDaily.ContractInformation where referenceNumber='".$res_kimdis["results"]["bindings"][$i]["contractId"]["value"]."' and file like 'http%'");
                    if ($resfile) {
                        $res_kimdis["results"]["bindings"][$i]["pdffile"] = $resfile[0]->file;
                    } else {
                        $res_kimdis["results"]["bindings"][$i]["pdffile"] = null;
                    }
                }
            } else {
                for ($i=0; $i<= count($res_kimdis["results"]["bindings"])-1; $i++) {
                    $resfile = DB::connection('mysql_khmdhs')->select("select file from khmdhs.PayInformation where referenceNumber='".$res_kimdis["results"]["bindings"][$i]["contractId"]["value"]."' and file like 'http%'
                                                                       union
                                                                       select file from khmdhsDaily.PayInformation where referenceNumber='".$res_kimdis["results"]["bindings"][$i]["contractId"]["value"]."' and file like 'http%'");
                    if ($resfile) {
                        $res_kimdis["results"]["bindings"][$i]["pdffile"] = $resfile[0]->file;
                    } else {
                        $res_kimdis["results"]["bindings"][$i]["pdffile"] = null;    
                    }
                }
            }
                
            //print_r ($res_kimdis);
////////////////XSOZ START - UPDATE DATE            
                $q_date="SELECT MAX(?date) as ?date
                        FROM <http://linkedeconomy.org/EprocurementUpdate> 
                        WHERE {
                        ?contract elod:signatureDate ?date ;
                                  elod:seller <http://linkedeconomy.org/resource/Organization/".$vatid."> .
                        } 
                        order by desc (?date)
                        limit 1

                        ";            
              $json_requestPartial_date = $this->request($q_date, config('sparql.connection_username'), config('sparql.connection_password'));
              $res_date_tmp = json_decode($json_requestPartial_date, true);
              if (array_key_exists("date", $res_date_tmp["results"]["bindings"][0])) {
              $res_date = date_format(date_create(explode("+",$res_date_tmp["results"]["bindings"][0]["date"]["value"])[0]),"d-m-Y");
              } else {
                $res_date = "unknown";
              }
              //return $res_date;
////////////////XSOZ END 
            return view('company.partialtable')->with(compact('res_kimdis', 'res_date', 'vatid', 'type', 'tab', 'table', 'lang'));
        }


        if ($table == "espa") 
        {
            $res_espa = array();

            //if ($tab == 1) {

            $qry_espa = "SELECT distinct (str(?startDate) as ?startDate) (str(?endDate) as ?endDate) (str(SAMPLE(?buyerName)) as ?buyerName) (str(?buyerVatId) as ?buyerVatId) (str(?contract) as ?contract) (str(?title) as ?title)   (xsd:decimal(?completionOfContracts) as ?completionOfContracts) (xsd:decimal(?budgetAmount) as ?budgetAmount) (xsd:decimal(?amount) as ?amount) (str(?projectId) as ?projectId)
                FROM <http://linkedeconomy.org/NSRF>
                FROM <http://linkedeconomy.org/GemhOrganizations>
                WHERE {
                ?project a elod:PublicWork ;
                         elod:hasRelatedContract ?contract ;
                         elod:completionOfContracts ?completionOfContracts ;
                         elod:projectId ?projectId ;
                         elod:hasBudgetAggregate ?budgetAggregate .
                ?contract elod:seller <http://linkedeconomy.org/resource/".$type."/".$vatid."> ;   
                          dcterms:title ?title ;
                          elod:price ?ups ;
                          elod:buyer ?buyer ;
                          pc:startDate ?startDate ;
                          pc:actualEndDate ?endDate .
                OPTIONAL { ?buyer foaf:name ?buyerName ; gr:vatID ?buyerVatId . }
                ?ups gr:hasCurrencyValue ?amount.
                ?budgetAggregate elod:aggregatedAmount ?budgetAmount .
                }
            ";

            
            if (Cache::has('moneypartial-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang)) {
                $json_requestPartial = Cache::get('moneypartial-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang);              
            } else {            
                                           
                $json_requestPartial = $this->request($qry_espa, config('sparql.connection_username'), config('sparql.connection_password'));
                Cache::put('moneypartial-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang, $json_requestPartial, config('sparql.cache_time'));
            }
            
            $res_espa = json_decode($json_requestPartial, true);
            

            if (!empty($res_espa["results"]["bindings"])) {
                for ($i=0; $i<= count($res_espa["results"]["bindings"])-1; $i++) {

                    $qry_espa_anadoxos = "
                        SELECT count(distinct ?seller) as ?sellerCount
                        FROM <http://linkedeconomy.org/NSRF>
                        WHERE {
                        <".$res_espa["results"]["bindings"][$i]["contract"]["value"]."> elod:seller ?seller .
                        }
                    ";

                    //if (Cache::has('anadoxos-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang)) {
                    //    $json_requestAnadoxos = Cache::get('anadoxos-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang);              
                    //} else {                     
                        $json_requestAnadoxos = $this->request($qry_espa_anadoxos, config('sparql.connection_username'), config('sparql.connection_password'));
                    //    Cache::put('anadoxos-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang, $json_requestAnadoxos, config('sparql.cache_time'));
                    //}
                    
                    $anadoxos = json_decode($json_requestAnadoxos, true);
                  
                    if (!empty($anadoxos["results"]["bindings"])) {                        
                        $res_espa["results"]["bindings"][$i]["anadoxos"] = $anadoxos["results"]["bindings"][0]["sellerCount"]["value"];
                    } else {
                        $res_espa["results"]["bindings"][$i]["anadoxos"] = null;
                    }                                   
                }
            }
            
            //print_r($res_espa);
////////////////XSOZ START - UPDATE DATE            
                $q_date="SELECT MAX(?date) as ?date
                        FROM <http://linkedeconomy.org/NSRF>
                        WHERE {
                        ?project a elod:PublicWork ;
                                 elod:hasRelatedContract ?contract ;
                                 dcterms:modified ?date .
                        ?contract elod:seller <http://linkedeconomy.org/resource/Organization/".$vatid."> .
                        }
                        order by desc (?date)
                        limit 1
                        ";            
              $json_requestPartial_date = $this->request($q_date, config('sparql.connection_username'), config('sparql.connection_password'));
              $res_date_tmp = json_decode($json_requestPartial_date, true);
             if (array_key_exists("date", $res_date_tmp["results"]["bindings"][0])) {
              $res_date = date_format(date_create(explode("+",$res_date_tmp["results"]["bindings"][0]["date"]["value"])[0]),"d-m-Y");
              } else {
                $res_date = "unknown";
              }
              //return $res_date;
////////////////XSOZ END 
            
            return view('company.partialtable')->with(compact('res_espa', 'res_date', 'vatid', 'type', 'tab', 'table', 'lang'));
        }


        if ($table == "ted") 
        {
            $res_ted = array();

            //if ($tab == 1) {
                $qry_ted = "                    
                    SELECT distinct (str(?date) as ?date) (SAMPLE(str(?buyerName)) AS ?buyerName)  (xsd:decimal(?amount) as ?amount) (str(?cpv) as ?cpv) (str(?contract) AS ?contract)
                    FROM <http://yourdatastories.eu/TEDGreece> 
                    where {
                    ?contract a pc:Contract ;
                          elod:documentType \"Contract award notice\"^^<http://www.w3.org/2001/XMLSchema#string> ;
                          pc:awardDate ?date ;
                          elod:buyer ?buyer ;
                          elod:seller ?seller ;
                          pc:agreedPrice ?ups .
                    OPTIONAL { ?contract pc:mainObject ?cpv . }
                    OPTIONAL { ?buyer foaf:name ?buyerName . }
                    ?seller gr:vatID \"".$vatid."\" .
                    ?ups gr:hasCurrencyValue ?amount . 
                    } 
                ";

            //} 

            if (Cache::has('moneypartial-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang)) {
                $json_requestPartial = Cache::get('moneypartial-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang);              
            } else {            
                                           
                $json_requestPartial = $this->request2($qry_ted, config('sparql.connection_username'), config('sparql.connection_password'));
                Cache::put('moneypartial-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang, $json_requestPartial, config('sparql.cache_time'));
            }
            
            $res_ted = json_decode($json_requestPartial, true);
            
            //print_r($res_ted);

            if (!empty($res_ted["results"]["bindings"])) {
                for ($i=0; $i<= count($res_ted["results"]["bindings"])-1; $i++) {

                    $qry_ted_cpv = "
                        SELECT (str(?cpvName) as ?cpvName) 
                        FROM <http://publicspending.net/DiavgeiaI/CPV>
                        WHERE {
                        <".$res_ted["results"]["bindings"][$i]["cpv"]["value"]."> skos:prefLabel ?cpvName . 
                        filter langMatches(lang(?cpvName), 'el')
                        } 
                    ";

                    //if (Cache::has('anadoxos-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang)) {
                    //    $json_requestAnadoxos = Cache::get('anadoxos-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang);              
                    //} else {                     
                        $json_requestCpv = $this->request($qry_ted_cpv, config('sparql.connection_username'), config('sparql.connection_password'));
                    // Cache::put('anadoxos-'.$table.'-'.$vatid.'-'.$tab.'-'.$lang, $json_requestAnadoxos, config('sparql.cache_time'));
                    //}
                    
                    $cpv = json_decode($json_requestCpv, true);
                  
                    if (!empty($cpv["results"]["bindings"])) {                        
                        $res_ted["results"]["bindings"][$i]["cpvName"] = $cpv["results"]["bindings"][0]["cpvName"]["value"];
                    } else {
                        $res_ted["results"]["bindings"][$i]["cpvName"] = null;
                    }                                   

                    $part = str_replace("http://linkedeconomy.org/resource/Contract/AwardNotice/","",$res_ted["results"]["bindings"][$i]["contract"]["value"]);
                    $pos = strpos($part, "/");
                    $part = substr($part, 0, $pos);
                    
                    $res_ted["results"]["bindings"][$i]["linkdate"] = substr($part, 0, 4);
                    $res_ted["results"]["bindings"][$i]["linkcode"] = substr($part, 4);
                }
            }
           
            //print_r($res_ted);
            //die;
////////////////XSOZ START - UPDATE DATE            
                $q_date="SELECT MAX(?date) as ?date
                        FROM <http://yourdatastories.eu/TEDGreece> 
                        where {
                        ?contract a pc:Contract ;
                              pc:awardDate ?date ;
                              elod:seller ?seller .
                        ?seller gr:vatID \"".$vatid."\" .
                        } 
                        order by desc (?date)
                        limit 1
                        ";            
              $json_requestPartial_date = $this->request2($q_date, config('sparql.connection_username2'), config('sparql.connection_password2'));
              $res_date_tmp = json_decode($json_requestPartial_date, true);
              if (array_key_exists("date", $res_date_tmp["results"]["bindings"][0])) {
              $res_date = date_format(date_create(explode("+",$res_date_tmp["results"]["bindings"][0]["date"]["value"])[0]),"d-m-Y");
              } else {
                $res_date = "unknown";
              }
              //return $res_date;
////////////////XSOZ END 
            
            return view('company.partialtable')->with(compact('res_ted', 'res_date',  'vatid', 'type', 'tab', 'table', 'lang'));
        }
    }

	
	public function getUrlDbpediaAbstract($qry)
	{
		/*
        $format = 'json';
        
		$searchUrl = config('sparql.connection_url')
		  .'query='.urlencode($qry)
		  .'&format='.$format;		
		
		//$searchUrl = urlencode($qry);
        */
        
		$searchUrl = $qry;
		
		return $searchUrl;
	} 


    public function request2 ($url, $username = null, $password = null)
    {    
        
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
          CURLOPT_URL => config('sparql.connection_url2'),
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
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "Error #:" . $err;        
        } 
        
        return $response;
    }

	public function request($url, $username = null, $password = null)
	{	 
		
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
		  CURLOPT_URL => config('sparql.connection_url'),
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
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "Error #:" . $err;		 
		} 
		
		return $response;
	}
	
	public function getOrgType($orgType, $lang) 
	{
		$res = "";		
		$orgTypeCode = DB::select(DB::raw("SELECT * FROM orgtype WHERE orgtype_code_en='".$orgType."' LIMIT 1"));
		
		if ($lang == "en") {
			$res = $orgTypeCode[0]->orgtype_code_en;
		} else {
			$res = $orgTypeCode[0]->orgtype_code;
		}

						
		return $res;
	}
    
    public function getTopCpaInfo($cpaCode) 
	{
				
		$topCpaInfo = DB::select(DB::raw("SELECT topcpa_color, topcpa_icon FROM topcpa WHERE topcpa_code_sparql='".$cpaCode."' LIMIT 1"));
		//return var_dump($cpaCode);
        if (count($topCpaInfo)==0) {	

            $topCpaInfo = DB::select(DB::raw("SELECT topcpa_color, topcpa_icon FROM topcpa WHERE topcpa_code='".$cpaCode."' LIMIT 1"));	

            if (count($topCpaInfo)==0) {
            		$topCpaInfo[0]=(object) [
                        'topcpa_color' => "#9798a8",
                        'topcpa_icon' => "star-pin.svg",
                      ];  
              }        
        }
        
        return $topCpaInfo[0];
	}
    
	
	public function getAgeUrlForCompany($company_id) 
	{
			
		if (Cache::has('companyAgeUrl-'.$company_id)) {
			$res_arr = Cache::get('companyAgeUrl-'.$company_id);	
			
		} else {
			
			$res_arr = DB::connection('mysql_gemh')->select(DB::raw("select companyAge, url, siteurl from Main m where m.gemhnumber='".$company_id."'"));	
			
			Cache::put('companyAgeUrl-'.$company_id, $res_arr, config('sparql.cache_time'));								
			
		}
				
		return $res_arr;
	}

    public function cytopage($lang = null, $vatid) 
    {
        $vatid=explode("-",$vatid)[0];
        if ($lang == null) {            
            App::setLocale("gr");
        } else {                    
            App::setLocale($lang);
        }       
        
        if (!$lang == "en") {               
            $lang = "gr";            
        }

        //return $lang;
        //$vatid = "BID930000538/143938503000";
        //$vatid = "800567577";
/////////// XSOZ - CHECK IF THERE EXISTS A NETWORK BEFORE LOADING THE CYTOSCAPE //////////////////////
        

       $quer = 'select  p.name as person,p.vatNumber as personVat, o.partnerPercentage, o.totalAmountAllPartners as amount, m.name as company, m.brandname as brand , m.vatid as companyVat , o.rowconfidence, e.dropboxFileLink, m.gemhnumber, m.status  
from         OwnershipData o
inner join PersonalData p                
on p.id = o.personId                
inner join Main m                
on m.gemhNumber = o.gemhNumber        
inner join ExtraRecommendatioData e
on m.gemhNumber = e.gemhNumber        
where m.vatid = \'' . $vatid . '\'  and  o.rowconfidence > 0.0';

      $quer2 = 'select p.name as person,p.vatNumber as personVat, o.partnerPercentage, o.totalAmountAllPartners as amount, m.name as company, m.brandname as brand, m.vatid as companyVat, o.rowconfidence, e.dropboxFileLink, m.gemhnumber, m.status  
from OwnershipData o
inner join PersonalData p
on p.id = o.personId
inner join Main m
on m.gemhNumber = o.gemhNumber
inner join ExtraRecommendatioData e
on m.gemhNumber = e.gemhNumber
where p.VatNumber = \'' . $vatid . '\' and o.rowconfidence > 0.0';

      $quer3 = 'SELECT mp.name as personName, pd.vatNumber as personVat, m.name as companyName, m.brandname as brand, m.vatId as companyVat, mp.positionFreeText, mp.fromDate, mp.toDate, mp.gemhnumber, m.status
FROM MemberPosition mp
inner join PersonalData pd
on pd.id = mp.personId
inner join Positions p
on p.id = mp.positionId
inner join
PositionsList pl1
on pl1.id = p.mappedPosition_1
inner join
PositionsList pl2
on pl2.id = p.mappedPosition_2
inner Join Main m
on m.gemhnumber = mp.gemhnumber
where m.vatid=\'' . $vatid . '\'
 order by mp.toDate desc';

      $quer4 = 'SELECT m.name as companyName, mp.name as personName, pd.vatNumber as personVat, m.vatId as companyVat, m.brandname as brand, mp.positionFreeText, mp.fromDate, mp.toDate, mp.gemhnumber, m.status
FROM MemberPosition mp
inner join PersonalData pd
on pd.id = mp.personId
inner join Positions p
on p.id = mp.positionId
inner join
PositionsList pl1
on pl1.id = p.mappedPosition_1
inner join
PositionsList pl2
on pl2.id = p.mappedPosition_2
inner join Main m
on m.gemhnumber = mp.gemhnumber
where pd.vatNumber=\'' . $vatid . '\'
 order by mp.toDate desc';
                 $diagtmp[0] = DB::connection('mysql_gemh')->select($quer);
                 $diagtmp[1] = DB::connection('mysql_gemh')->select($quer2);
                 $diagtmp[2] = DB::connection('mysql_gemh')->select($quer3);
                 $diagtmp[3] = DB::connection('mysql_gemh')->select($quer4);
                 //echo var_dump($diagtmp[0]) . '<br>-------------------<br><br>';
                 if(count($diagtmp[0])<=0 && count($diagtmp[1])<=0 && count($diagtmp[2])<=0 && count($diagtmp[3])<=0) return "<link type=\"text/css\" rel=\"stylesheet\" href=\"" . asset('css/all_pout.css') . "\"><body style=\"background-color:#0000;\"><br><p>" . __('lang.nodatacyto1') . "<a target='_blank' href='" . route('home') . "/" . $lang . "/cytopage/800567577'>" . __('lang.nodatacyto2') . "</a>.</p></body>";
//////////// XSOZ END ////////////////////////////////////////////////////////////////////////////////



        $cytospacelevel = $this->user->corporatenet_graph_level + 1;

        return view('company.cytopage')->with(compact('vatid', 'cytospacelevel', 'lang'));
    }
}
