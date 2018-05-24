<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of keyWord
 *
 * @author dimitris negkas
 */
class keyWord {
   function tranlateAbbFull($varKeyword){
       $translated = '';       
       $abbLength = 8;
        if  (((mb_strlen($varKeyword, 'UTF-8')) <= $abbLength) || (strpos($varKeyword,'.') !== false)  || ($varKeyword=='Εθνικη Τραπεζα') || ($varKeyword=='ΕΘΝΙΚΗ ΤΡΑΠΕΖΑ')  ){
            $varKeyword= str_replace('.','',$varKeyword,$DbPath);
            $varKeyword= str_replace(',','',$varKeyword,$DbPath);
            $varKeyword=mb_convert_case($varKeyword, MB_CASE_UPPER, "UTF-8");


            switch ($varKeyword) {
            case "ΑΠΘ":
                $varKeyword='"ΑΡΙΣΤΟΤΕΛΕΙΟ+ΠΑΝΕΠΙΣΤΗΜΙΑ"';
                 break;
            case "ΓΓΔΕ":
                $varKeyword='"ΓΕΝΙΚΗ+ΓΡΑΜΜΑΤΕΙΑ+ΔΗΜΟΣΙΩΝ"';
                 break;
            case "ΔΕ":
                  $varKeyword='"ΔΕΥΤΕΡΟΒΑΘΜΙΑΣ+ΕΚΠΑΙΔΕΥΣΗΣ"'; 
                  break;	  
            case "ΔΕΗ":
                  $varKeyword='"ΔΗΜΟΣΙΑ+ΕΠΙΧΕΙΡΗΣΗ+ΗΛΕΚΤΡΙΣΜΟΥ"';
                  break;
            case "ΕΑΠ":
                  $varKeyword='"ΕΛΛΗΝΙΚΟ+ΑΝΟΙΚΤΟ+ΠΑΝΕΠΙΣΤΗΜΙΟ"';
                  break;	  
            case "ΕΑΤΑ":
                  $varKeyword='"ΕΤΑΙΡΕΙΑ+ΑΝΑΠΤΥΞΗΣ+ΚΑΙ+ΤΟΥΡΙΣΤΙΚΗΣ"';
                  break;
            case "ΕΔΕΤ":
                  $varKeyword='"ΕΘΝΙΚΟ+ΔΙΚΤΥΟ+ΕΡΕΥΝΑΣ"';
                  break;
            case "ΕΔΣΝΑ":
                  $varKeyword='"ΕΙΔΙΚΟΣ+ΔΙΑΒΑΘΜΙΔΙΚΟΣ+ΣΥΝΔΕΣΜΟΣ"';
                  break;	  
            case "ΕΙΕ":
                  $varKeyword='"ΕΘΝΙΚΟ+ΙΔΡΥΜΑ+ΕΡΕΥΝΩΝ"';
                  break;
            case "ΕΚΕΤΑ":
                  $varKeyword='"ΕΘΝΙΚΟ+ΚΕΝΤΡΟ+ΕΡΕΥΝΑΣ+ΚΑΙ+ΤΕΧΝΟΛΟΓΙΚΗΣ"';
                  break;
            case "ΕΚΕΦΕ":
                  $varKeyword='"ΕΘΝΙΚΟ+ΚΕΝΤΡΟ+ΕΡΕΥΝΑΣ+ΦΥΣΙΚΩΝ+ΕΠΙΣΤΗΜΩΝ"';
                  break;
            case "ΕΚΠΑ":
                  $varKeyword='"ΕΘΝΙΚΟΝ+ΚΑΙ+ΚΑΠΟΔΙΣΤΡΙΑΚΟΝ"';
                  break;
            case "ΕΛΓΟ":
                  $varKeyword='"ΕΛΛΗΝΙΚΟΣ+ΓΕΩΡΓΙΚΟΣ+ΟΡΓΑΝΙΣΜΟΣ"';
                  break;
            case "ΕΛΚΕ":
                  $varKeyword='"ΕΙΔΙΚΟΣ+ΛΟΓΑΡΙΑΣΜΟΣ+ΚΟΝΔΥΛΙΩΝ"';
                  break;		  
            case "ΕΛΤΑ":
                  $varKeyword='"ΕΛΛΗΝΙΚΑ+ΤΑΧΥΔΡΟΜΕΙΑ"';
                  break;
            case "ΕΜΠ":
                  $varKeyword='"ΕΘΝΙΚΟ+ΜΕΤΣΟΒΙΟ"';
                  break;
            case "ΕΟΠΥΥ":
                  $varKeyword='"ΕΘΝΙΚΟΣ+ΟΡΓΑΝΙΣΜΟΣ+ΠΑΡΟΧΩΝ"';
                  break;	  
            case "ΕΟΤ":
                  $varKeyword='"ΕΛΛΗΝΙΚΟΣ+ΟΡΓΑΝΙΣΜΟΣ+ΤΟΥΡΙΣΜΟΥ"'; 
                  break;
            case "ΕΟΦ":
                  $varKeyword='"ΕΘΝΙΚΟΣ+ΟΡΓΑΝΙΣΜΟΣ+ΦΑΡΜΑΚΩΝ"'; 
                  break;
            case "ΕΡΤ":
                  $varKeyword='"ΕΛΛΗΝΙΚΗ+ΡΑΔΙΟΦΩΝΙΑ+ΤΗΛΕΟΡΑΣΗ"'; 
                  break;
            case "ΕΣΔΑΚ":
                  $varKeyword='"ΕΝΙΑΙΟΣ+ΣΥΝΔΕΣΜΟΣ+ΔΙΑΧΕΙΡΙΣΗΣ+ΑΠΟΡΙΜΜΑΤΩΝ"'; 
                  break;	
            case "ΕΤΑΜ":
                  $varKeyword='"ΕΝΙΑΙΟ+ΤΑΜΕΙΟ+ΑΣΦΑΛΙΣΗΣ"'; 
                  break;
            case "ΕΤΕ":
                  $varKeyword='"ΤΡΑΠΕΖΑ+ΕΘΝΙΚΗ"'; 
                  break;
            case "ETE":
                  $varKeyword='"ΤΡΑΠΕΖΑ+ΕΘΝΙΚΗ"'; 
                  break;
                           
            case "ΕΥΔΑΠ":
                  $varKeyword='"ΕΤΑΙΡΙΑ+ΥΔΡΕΥΣΕΩΣ+ΚΑΙ+ΑΠΟΧΕΤΕΥΣΕΩΣ+ΠΡΩΤΕΥΟΥΣΗΣ"';
                  break;
            case "ΕΦΕΤ":
                  $varKeyword='"ΕΝΙΑΙΟΣ+ΦΟΡΕΑΣ+ΕΛΕΓΧΟΥ+ΤΡΟΦΙΜΩΝ"';
                  break;
            case "ΙΚΑ":
                  $varKeyword='"ΙΔΡΥΜΑ+ΚΟΙΝΩΝΙΚΩΝ+ΑΣΦΑΛΙΣΕΩΝ"'; //EINAI SAN IKA
                  break;
            case "ΙΤΕ":
                  $varKeyword='"ΙΔΡΥΜΑ+ΤΕΧΝΟΛΟΓΙΑΣ+ΕΡΕΥΝΑΣ"'; 
                  break;
            /*case "ΟΑΕ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΑΝΑΣΥΓΚΡΟΤΗΣΗΣ+ΕΠΙΧΕΙΡΗΣΕΩΝ"';  //den yparxei
                  break; */
            case "ΟΑΕΠ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΑΣΦΑΛΙΣΗΣ+ΕΞΑΓΩΓΙΚΩΝ"'; 
                  break;
            case "ΟΑΣΑ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΑΣΤΙΚΩΝ+ΣΥΓΚΟΙΝΩΝΙΩΝ+ΑΘΗΝΩΝ"';
                  break;
            case "ΟΑΣΘ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΑΣΤΙΚΩΝ+ΣΥΓΚΟΙΝΩΝΙΩΝ+ΘΕΣΣΑΛΟΝΙΚΗΣ"';
                  break;
            case "ΟΔΔΗΧ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΔΙΑΧΕΙΡΙΣΗΣ+ΔΗΜΟΣΙΟΥ+ΧΡΕΟΥΣ"';
                  break;
            case "ΟΕΚ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΕΡΓΑΤΙΚΗΣ+ΚΑΤΟΙΚΙΑΣ"';
                  break;
            case "ΟΚΑΑ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΚΕΝΤΡΙΚΗΣ+ΑΓΟΡΑΣ+ΑΘΗΝΩΝ"'; //ΔΕΝ ΥΠΑΡΧΕΙ
                  break;	
            case "ΟΛΘ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΛΙΜΕΝΟΣ+ΘΕΣΣΑΛΟΝΙΚΗΣ"'; 
                  break;
            case "ΟΛΠ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΛΙΜΕΝΟΣ+ΠΕΙΡΑΙΩΣ"'; 
                  break;
            /*case "ΟΠΑΠ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΠΡΟΓΝΩΣΤΙΚΩΝ+ΑΓΩΝΩΝ+ΠΟΔΟΣΦΑΙΡΟΥ"'; 
                  break;	*/   //ΔΕΝ ΥΠΑΡΧΕΙ
            case "ΟΠΕΚΕΠΕ":
                  $varKeyword='"ΟΠΕΚΕΠΕ"'; 
                  break; 
            case "ΟΣΕ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΣΙΔΗΡΟΔΡΟΜΩΝ"'; 
                  break;
            case "ΟΣΚ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΣΧΟΛΙΚΩΝ+ΚΤΙΡΙΩΝ"'; 
                  break;	 	  
            case "ΟΤΕ":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΤΗΛΕΠΙΚΟΙΝΩΝΙΩΝ"'; 
                  break;
            case "OTE":
                  $varKeyword='"ΟΡΓΑΝΙΣΜΟΣ+ΤΗΛΕΠΙΚΟΙΝΩΝΙΩΝ"'; 
                  break;
            case "ΠΕ":
                  $varKeyword='"ΠΡΩΤΟΒΑΘΜΙΑΣ+ΕΚΠΑΙΔΕΥΣΗΣ"'; 
                  break;
            case "ΤΑΥΤΕΚΩ":
                  $varKeyword='"ΤΑΜΕΙΟ+ΑΣΦΑΛΙΣΗΣ+ΥΠΑΛΛΗΛΩΝ+ΤΡΑΠΕΖΩΝ"'; 
                  break;	  	  
            case "ΥΠΑ":
                  $varKeyword='"ΥΠΗΡΕΣΙΑ+ΠΟΛΙΤΙΚΗΣ+ΑΕΡΟΠΟΡΙΑΣ"'; 
                  break;


}


}
$translated=$varKeyword;
return $translated;  
}

   function unAccent($string){
       $string =  str_replace('Ά','Α',$string);
       $string =  str_replace('Έ','Ε',$string);
       $string =  str_replace('Ί','Ι',$string);
       $string =  str_replace('Ή','Η',$string);
       $string =  str_replace('Ύ','Υ',$string);
       $string =  str_replace('Ό','Ο',$string);
       $string =  str_replace('Ώ','Ω',$string);
       return $string;
   }
   
   function prepareKeyword($word){
    $word= rtrim(ltrim(str_replace('ς','σ', $word)));
    $word=mb_convert_case($word, MB_CASE_UPPER, "UTF-8");
    $word= $this->unAccent($word);
    /*$word = str_replace('ά','α',$word);
    $word = str_replace('έ','ε',$word);
    $word = str_replace('ή','η',$word);
    $word = str_replace('ί','ι',$word);
    $word = str_replace('ύ','υ',$word);
    $word = str_replace('ό','ο',$word);
    $word = str_replace('ώ','ω',$word);
    $word = str_replace('πατρ.',' ',$word);
    $word = str_replace('<',' ',$word);
    $word = str_replace('>',' ',$word);
    $word = str_replace('&',' ',$word);
    $word = str_replace('-',' ',$word);
    $word = str_replace('||',' ',$word);
    $word = str_replace(' ||',' ',$word);
    $word = str_replace('|| ',' ',$word);
    $word = str_replace(',,',' ',$word);
    $word = str_replace(',',' ',$word);*/
    return $word;
    }
    

   function prepareExactKeyword($word){
        $word = ltrim($word);
        $words = explode(' ', $word);
        $wordArray = array();
        if (count($words) >1) {
            $t1=' ';
            $t2=' ';
            $t12=' ';
            $word ='';
            $i = 0;
            $len=count($words);
            foreach($words as $w) {		
               if ($i == 0)  {	
                   if	(mb_strlen($w, 'utf-8') >3) { 			   
			$t1 = $w;
		    }
			$w='"'.$w.'+';			 
               }
	       else
			   if (($i > 0) && ($i < ($len-1)))
               { 	
			       
                   if ($i == 1) 	
                   {
				      if	(mb_strlen($w, 'utf-8') >3 ){ 
				      $t2 = $w;	
                      }					 
				   }	
                   $w=$w.'+';				   
			  
			   }		
			   else
               if ($i == ($len-1) )	
			   {
			       
			       if ($i == 1) 
				   {
				    $t2 = $w;					
				   }	
                  $w=$w.'"';				   
			       
				}      			  			  
			 	                  	  
			  $word .=$w; 	
	          $i++;			  
		      }   
$t12= '"'.$t1."+".$t2.'"';
}
return array($t1,$t2,$t12,$word);
}
}
