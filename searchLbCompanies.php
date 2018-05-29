<?php


?>
<html> 
    <header>   
    <h2><center>Επιχειρήσεις</center></h2>
     
  </header>
    <head>
        <!-- DataTables CSS -->
        <link rel="stylesheet" type="text/css" href="/sites/all/js/DataTables/media/css/jquery.dataTables.css"> 
        <link rel="stylesheet" type="text/css" href="/sites/all/js/dataTable/jquery-ui/jquery-ui.css" />
        <!-- DataTables JS -->
        <script type="text/javascript" src="/sites/all/js/dataTable/jQuery/jquery-2.0.3.js"></script>
        <script type="text/javascript" src="/sites/all/js/dataTable/jQuery/jquery-ui.js"></script>  
        <script type="text/javascript" charset="utf8"  src="/sites/all/js/dataTable/dataTables/jquery.dataTables1.js"></script> 
        <script type="text/javascript" src="/sites/all/js/dataTable/dataTables/dataTables.sorting.js"></script>
        <script type="text/javascript" src="/sites/all/js/dataTable/date-eu.js"></script>
        <script> 
            $(document).ready( function () {
            $('#searchResults').DataTable(
            {
            "aaSorting": [[ 1, "desc" ]], //"aaSorting": [[ 3, "desc" ]],
                    "bJQueryUI": true,
                    "aLengthMenu": [
            [25, 50, 100, 200, -1],
            [25, 50, 100, 200, "All"]
            ],

                    "oLanguage":{ 	       
                       "sLengthMenu": "Eμφάνιση _MENU_ ",
                       "sZeroRecords": "Δεν βρέθηκαν εγγραφές...",
                       "sInfo": " _START_ - _END_ από _TOTAL_ ",
                       "sInfoEmpty": "Δεν βρέθηκαν εγγραφές",
                       "sInfoFiltered": "(επιλεγμένες από τις _MAX_ εγγραφές)"
                       },
                        "aoColumnDefs": [ 
                                                    //{  "bVisible": false, "aTargets": [ 3 ] },
                                                    {  "bVisible": false, "aTargets": [ 1 ] },
                                            //	{  "bVisible": false, "aTargets": [ 2 ] }

                                            ]

        } 


            );

            } ); 
       </script>
       <script>
		function showHideAdvanceSearch() {
			if(document.getElementById("advanced-search-box").style.display=="none") {
				document.getElementById("advanced-search-box").style.display = "block";
				document.getElementById("advance_search_submit").value= "1";
			} else {
				document.getElementById("advanced-search-box").style.display = "none";
				document.getElementById("crf1").value= "" 
				document.getElementById("crf2").value= "";
				document.getElementById("crf3").value= "";
				document.getElementById("search_in").value= "";
				document.getElementById("advance_search_submit").value= "";
			}
		}
	</script>
       <style>



            li.ex1{
            list-style-type: none;
            display:inline; 
            padding-right: 7px;
            }

            li.ex1:hover {
            color: #30b42b;
            padding-bottom: 1px;
             }

            .general{
            width:80%;
            align:center;
            }

            .mblink:visited, a:visited {
             <!--  color: #609; --> 
            <!--  color: #FFA500;-->
              <!-- color: #1a0dab; --> 
              }
              a:link {
                color: #1a0dab;
            }

            /* visited link */
            a:visited {
                color: #609;
            }
             /* a:link, .w, #prs a:visited, #prs a:active, .q:active, .q:visited, .kl:active, .tbotu {

              <!--  color: #FFA500; -->
               color: #1a0dab; 
            } */
            h1, #cdr_min, #cdr_max, .cpbb, .kpbb, .kprb, .kpgb, .kpgrb, .ksb {
              font-family: arial,sans-serif;
            }


            #wrapNew {
            width: 80%;
            margin: 0 auto;
            background: #99c;
            }
            a {
              padding-left: 0 !important;
            <!--  color: #ffb141; -->
            <!--  color: #609
            } -->


            #mainNew {
            float:left;
            width:75%;
            padding-top:5px;
            padding-bottom:5px;

            }

            #sidebarNew {
            float:right;
            width:25%;
            padding-top:5px;
            padding-bottom:5px;
            }


            #mainNewInternal {
            float:left;
            width:80%;
            padding-top:5px;
            padding-bottom:5px;

            }

            #sidebarNewInternal {
            float:right;
            width:18%;
            padding-top:5px;
            padding-bottom:5px;
            padding-right:5px;

              text-align:center;


              height:10px;
            }





            /* CSSTerm.com Simple CSS menu */

            li.ex2{
            list-style-type: none;
            border-top: solid 1px #E8E8E8;
             height: 47px;
             line-height: 40px;

            }


            .menu_simple ul {
                margin: 0; 
                padding: 0;
                width:220px;
                list-style-type: none;

            }

            .menu_simple ul li a {
                text-decoration: none;
                color: black; 
                padding: 10px 10px;

                display:block;

              border-right: solid 1px #E8E8E8;
              border-left: solid 1px #E8E8E8;
              border-bottom: solid 1px #E8E8E8;

            }

            .menu_simple ul li a:visited {
                color: orange;
            }

            .menu_simple ul li a:hover, .menu_simple ul li .current {
                color: black;
                background-color: #5FD367;
            }

            .imageLi{
            vertical-align:middle;
            }

            .alignleft {
                    float: left;
                   width: 80%;
            }
            .alignright {

                    float: right;
                  padding-right: 30px;
            }

            a.searchTabs:link {
            color: #ffb141; 
            }
            a.searchTabs:visited {
            color: #ffb141; 
            }

            a.searchTabs:hover {
            color: #30b42b;
            }

            a.searchTabs:active {
            color: #1C94C4;
            }
            a.hereTabs{
            font-weight: bold;
            font-size: 125%;

            }
            a.hereTabs:active {
            color: #4285f4;
            }
            a.hereTabs:link {
            color: #4285f4;
            }
            a.hereTabs:hover {
            color: #30b42b;
            }
            a.hereTabs:visited {
            color: #4285f4;
            }
            a.nameLink{
            font-weight: bold;
            }
       <!-- advanced search       -->     
            body{
			 <!--width: 900px;-->
			font-family: "Segoe UI",Optima,Helvetica,Arial,sans-serif;
			line-height: 25px;
		}
		.search-box {
			padding: 30px;
			background-color:#C8EEFD;
		}
		.search-label{
			margin:2px;
                        border: 1;
                        border-radius: 4px;
			
		}
		.demoInputBox {    
			padding: 10px;
			border: 2;
			border-radius: 4px;
			margin: 0px 5px 15px;
			width: 250px;
		}
		.btnSearch{    
			padding: 8px;
                        position: relative;
                        /*left: -80px; */
			background: #84D2A7;
			border: 0;
			border-radius: 4px;
			margin: 0px 5px;
			color: #FFF;
			width: 150px;
		}
		#advance_search_link {
			color: #001FFF;
			cursor: pointer;
		}
                #search_link {
			color: #001FFF;
			cursor: pointer;
		}
		.result-description{
			margin: 5px 0px 15px;
		}
                #flags img {
                
                margin-left: 160px;
                }
                .table a
                {
                display:inline;
                text-decoration:none;
                font-size: 6px;
                color: #989898; 
                border: 0;
	        border-radius: 0px;
                align-self: center;
                align-items: center;
                }


    </style>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
      <title>Search LB Companies</title>
   
       
            
             
             
              
        
</head>
<body>

<div class="row-fluid margin-bottom" align="center" >
 <!--<form action="index.php"  method="post" accept-charset="UTF-8"> -->
 <form action="searchLbCompanies.php?varKeyword=<?php if (isset($_POST['formKeyword'])) echo $_POST['formKeyword'];  else echo $_GET['varKeyword']?>"    method="post" accept-charset="UTF-8"> 
<p>			
 <input type="text" style="width: 580px; height: 32px;" name="formKeyword" placeholder="Αναζήτηση με ΑΦΜ & επωνυμία  σε 1.340.567 επιχειρήσεις" value="<?php if (isset($_POST['formKeyword'])) echo $_POST['formKeyword']; else echo $_GET['varKeyword'] ?>"  maxlength="70" autofocus /> 
 <!--<input type="text" style="width: 580px; height: 32px;" name="formKeyword" placeholder="ΑΦΜ ή Όνομα" value="<?php  echo $_GET['varKeyword'] ?>"  maxlength="70" autofocus /> 	-->		
  <input type="submit"  name="formSubmit" value="searchLbCompanies.php"  style="display: none;" > 
  
    
            
<p>
   
     

 
   
				
				
</p>

</p>
<div align="center" >
 
 <br>
<hr align="center" width="80%">

<li class="ex1" >Αποτελέσματα</li>
</div>
</form>
<?php

#print_r($_POST['formKeyword']);
#print_r($_POST['advSearch']);
#$advChoiceArea = $_POST['advSearch']['search_in_area'];
#$advChoiceAmount = $_POST['advSearch']['search_in_amount'];
#echo $advChoiceAmount; 
#echo 'choices:'.$advChoiceArea.' '.$advChoiceAmount.PHP_EOL;   

#adv search variables
#$search_in = "";
#adv search variables

include 'collectData.php'; 
include 'keyWord.php';
include 'showResults.php';
include 'config.php';

$time_pre = microtime(true);
$prefix = '' ;
$varKeyword = $_POST['formKeyword']; 
$rowKeyword = $varKeyword;
/*$globalKeyword = $_GET['varKeyword'];
if (isset($globalKeyword )) {

    $varKeyword = $globalKeyword ;
} */

$Db='';  
$DesignDoc = '';
$Index ='';
$Limit = 25;
$Sort = 'id';
$Wc = '';
$calls = 0;
$Results = [[]];
$AlreadyFound = 0;
$Boost = 1;
$Actual_link = '';
$Lang = '';
$Domain ='';
$term1 = '';
$term2 = '';
$term12 = '';

$newKeyWord = new keyWord();

#if($_POST['formSubmit'] == "index.php" || (isset($_GET['varKeyword']))) {   

if ((isset($_POST['formSubmit']) && ($_POST['formSubmit'] <> "") )|| (isset($_GET['varKeyword']))) {
    if(strlen($varKeyword) != mb_strlen($varKeyword, 'utf-8')){ #not only english     
        $varKeyword = $newKeyWord->prepareKeyword($varKeyword) ;   
    }
    else {
        $varKeyword = rtrim(ltrim($varKeyword));  
        $varKeyword = $newKeyWord->prepareKeyword($varKeyword) ;   
    }
    $words = explode(' ', $varKeyword);  

 #read all data
    $search = new collectData();
    if (is_numeric($varKeyword)){ //probaby afm
        if (strlen(utf8_decode($varKeyword)) ==9 ) {
             #$search->getAll(solrPath,companiesSolrCore,'vat', $varKeyword,'',companiesUrl);
             #$search->getAll(solrPath,FRSolrCore,'vat', $varKeyword,'',companiesUrl);	
            # $search->getAllCompaniesCouch(DbPath, companiescouchDB ,'buyerVatIdOrName','by_buyerDtls_VatIdOrName', $Wc, $Limit, $Sort, $varKeyword, couchUser, couchPass,companiesUrl); 
             $search->getAllCompaniesCouch(DbPath, FRcouchDB , 'buyerVatIdOrName', 'by_buyerDtls_VatIdOrName', $Wc, $Limit, $Sort, $varKeyword, couchUser, couchPass,companiesUrl,'vat');            
             #$search->getAllCompaniesCouch(DbPath, nonGemhcouchDB , $DesignDoc, $Index, $Wc, $Limit, $Sort, $varKeyword, $couchUser, $couchPass,companiesUrl);
        }
        else {
            if (strlen(utf8_decode($varKeyword)) == 12 ) {
                # $search->getAll(solrPath,companiesSolrCore,'gemhNumber', $varKeyword,'',companiesUrl);	
                 # $search->getAll(solrPath,FRSolrCore,'gemhNumber', $varKeyword,'',companiesUrl);	
            }
        }
             
        #}
        #else {
         #   $search->getAllShort(solrPath,personsSolrCore,$varKeyword );	
        #}
    }
    else {
      #$search->getAll(solrPath,companiesSolrCore,'name', $varKeyword,'*',companiesUrl);	
      #$search->getAll(solrPath,FRSolrCore,'name', $varKeyword,'*',companiesUrl);	
    }
    $resultsPresentation = new showResults();
    
    $resultsPresentation -> presentResults(solrPath);
    
    
    $time_post = microtime(true);
    $exec_time = $time_post - $time_pre;
    echo  "<div ALIGN='CENTER'>";
    echo '(Σε '.number_format($exec_time,2).' δευτερόλεπτα)' ;
    echo "</div>";
   
    $varKeyword =  str_replace('+',' ',$varKeyword);
    $varKeyword =  str_replace('"',' ',$varKeyword);
}
?>
<html> 
<footer>

</footer>
 </html> 



 