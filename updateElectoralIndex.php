<?php
include 'config.php';
$couchUserPwd = couchUser.':'.couchPass;
$time_pre = microtime(true);
$counter = 1;
$ch = curl_init();
$db = 'lb_electoral';

$connectionInfo = array( "UID"=>uid ,                              
                         "PWD"=>pwd,                              
                         "Database"=>databaseName); 

#$conn = sqlsrv_connect(sqlserver, $connectionInfo);   
$sql ="SELECT *   FROM DHMOI";
//https://support.winhost.com/kb/a687/sample-code-to-connect-to-an-ms-sql-database-using-php.aspx
//http://robsphp.blogspot.com/2012/09/how-to-install-microsofts-sql-server.html
echo $sql;
$inputPath='C:\sqldata\csv/';
$files=array_diff(scandir($inputPath), array('..', '.'));

foreach($files as $let=>$word){
    if (($handle = fopen($inputPath.$word, "r")) !== FALSE){
        while (($row = fgetcsv($handle, 100000, ",")) !== FALSE) {
            if(!mb_detect_encoding($row[0] , 'utf-8', true)){
                $row[0]  = utf8_encode($row[0]);
             }
             $id = $row[0];
             $Eponymo= isset($row[5]) ? $row[5] : '';
             if ($Eponymo==='NULL')  {
                 $Eponymo='';                 
             }
             $Eponymo_b= isset($row[6]) ? $row[6] : '';
             if ($Eponymo_b==='NULL')  {
                 $Eponymo_b='';                 
             }
             $Onoma= isset($row[7]) ? $row[7] : '';
             if ($Onoma==='NULL')  {
                 $Onoma='';                 
             }
             $Onoma_b= isset($row[8]) ? $row[8] : '';
             if ($Onoma_b==='NULL')  {
                 $Onoma_b='';                 
             }
             $on_pat= isset($row[9]) ? $row[9] : '';
             if ($on_pat==='NULL')  {
                 $on_pat='';                 
             }
             $name = $Eponymo.' '.$Eponymo_b.' '. $Onoma.' '.$Onoma_b.' '. $on_pat;
         $arr = array(
                    "id"   => $row[0],
                    "name"   => $name,
                    "upd_prefix"   =>  str_replace('NULL','',$row[1]),                 
                    "kod_dhm_enot"   =>str_replace('NULL','',$row[2]),  
                    "Kod_ekl_diam"   =>str_replace('NULL','',$row[3]),  
                    "Fylo"   =>str_replace('NULL','',$row[4]),  
                    "Eponymo"   => str_replace('NULL','',$row[5]),  
                    "Eponymo_b"   =>str_replace('NULL','',$row[6]),  
                    "Onoma"   =>str_replace('NULL','',$row[7]),  
                    "Onoma_b"   =>str_replace('NULL','',$row[8]),  
                    "on_pat"   =>str_replace('NULL','',$row[9]),  
                   "epon_pat"   =>str_replace('NULL','',$row[10]),  
                   "on_mht"   =>str_replace('NULL','',$row[11]),            
                   "on_syz"   =>str_replace('NULL','',$row[12]),  
                   "etos_gen"   =>str_replace('NULL','',$row[13]),  
                   "mhn_gen"   =>str_replace('NULL','',$row[14]),              
                   "mer_gen"   =>str_replace('NULL','',$row[15]),           
                   "dhmot"   =>str_replace('NULL','',$row[16]),  
                   "Odos_tax_dieyt"   =>str_replace('NULL','',$row[17]),  
                   "Ar_tax_dieyt"   =>str_replace('NULL','',$row[18]),              
                   "Tax_kod"   =>str_replace('NULL','',$row[19]),             
                   "Poly_periox"   =>str_replace('NULL','',$row[20]),  
                   "Eid_ekl_ar"   =>str_replace('NULL','',$row[21]),  
                   "Et_Id"   =>str_replace('NULL','',$row[22]),  
                   "Et_dhmos_diamon"   =>str_replace('NULL','',$row[23]),            
                  "Et_Odos_tax_dieyt"   =>str_replace('NULL','',$row[24]),  
                   "Et_Ar_tax_dieyt"   =>str_replace('NULL','',$row[25]),  
                   "Et_Tax_kod"   =>str_replace('NULL','',$row[26]),  
             
             "Et_Poly_periox"   =>str_replace('NULL','',$row[27]),  
                   "Et_ked_diamon"   =>str_replace('NULL','',$row[28]),  
                   "Dipldiafdim_Flag"   =>str_replace('NULL','',$row[29]),  
             
               );
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
                print_r($response);
        }
    }
}


/*$id = $row['Id'];
$name = $row['Eponymo'].' '.$row['Eponymo_b'].' '.$row['Onoma'].' '.$row['Onoma_b'].' '.$row['on_pat'];
         $arr = array(
                    "id"   => $row['Id'],
                    "upd_prefix"   =>$row['upd_prefix'],                 
                    "kod_dhm_enot"   =>$row['kod_dhm_enot'],
                    "Kod_ekl_diam"   =>$row['Kod_ekl_diam'],
                    "Fylo"   =>$row['Fylo'],
                    "Eponymo"   =>$row['Eponymo'],
                    "Eponymo_b"   =>$row['Eponymo_b'],
                    "Onoma"   =>$row['Onoma'],
                    "Onoma_b"   =>$row['Onoma_b'],
                    "on_pat"   =>$row['on_pat'],
                   "epon_pat"   =>$row['epon_pat'],
                   "on_mht"   =>$row['on_mht'],             
                   "on_syz"   =>$row['on_syz'],
                   "etos_gen"   =>$row['etos_gen'],
                   "mhn_gen"   =>$row['mhn_gen'],             
                   "mer_gen"   =>$row['mer_gen'],             
                   "dhmot"   =>$row['dhmot'],
                   "Odos_tax_dieyt"   =>$row['Odos_tax_dieyt'],
                   "Ar_tax_dieyt"   =>$row['Ar_tax_dieyt'],             
                   "Tax_kod"   =>$row['Tax_kod'],             
                   "Poly_periox"   =>$row['Poly_periox'],
                   "Eid_ekl_ar"   =>$row['Eid_ekl_ar'],
                   "Et_Id"   =>$row['Et_Id'],
                   "Et_dhmos_diamon"   =>$row['Et_dhmos_diamon'],             
                  "Et_Odos_tax_dieyt"   =>$row['Et_Odos_tax_dieyt'],
                   "Et_Ar_tax_dieyt"   =>$row['Et_Ar_tax_dieyt'],
                   "Et_Tax_kod"   =>$row['Et_Tax_kod'],
             
             "Et_Poly_periox"   =>$row['Et_Poly_periox'],
                   "Et_ked_diamon"   =>$row['Et_ked_diamon'],
                   "Dipldiafdim_Flag"   =>$row['Dipldiafdim_Flag'],
             
               );
         $file_contents=json_encode($arr,JSON_UNESCAPED_UNICODE);
                curl_setopt($ch, CURLOPT_URL, 'http://83.212.86.158:5984/'.$db.'/'.$id);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); /* or PUT */
               # curl_setopt($ch, CURLOPT_POSTFIELDS, $file_contents);
               # curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
               # curl_setopt($ch, CURLOPT_USERPWD, $couchUserPwd );
                #curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                 #              'Content-type: application/json',
                  #             'Accept: */*'
               # ));
               # $response = curl_exec($ch);
               # print_r($response); */


$time_post = microtime(true);
$exec_time = $time_post - $time_pre;

echo '(In '.number_format($exec_time/60,2).' mins)'.PHP_EOL ;
curl_close($ch);


