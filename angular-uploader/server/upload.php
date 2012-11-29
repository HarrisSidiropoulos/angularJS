<?php

if (file_exists(dirname(__FILE__).'/upload.config.php')) {
  include dirname(__FILE__).'/upload.config.php';
}
else {
 
  if (!isset($_GET['fileChecksum']))
  $_GET['fileChecksum'] = -1;

  if (!isset($_FILES['Filedata']))
  $_FILES['Filedata'] = 0;

  $working_dir = dirname(__FILE__);
  $working_upload = dirname(__FILE__).'\uploads';


  define('UPLOAD_MAX_SIZE', 1000 * 1024 * 1024); // 1 GB

  define('DESTINATION_DIR', $working_upload);
  
}


$_GET['fileName'] = cleanForShortURL(urldecode($_GET['fileName']));
/*

echo $_GET['fileName'];
*/
include($working_dir . '/class.FileUpload.php');

$upload = new FileUpload(DESTINATION_DIR);
$upload->set_max_file_size(UPLOAD_MAX_SIZE);

#$upload->set_allowed_file_endings(array('php'));

$upload->parse_request();



function cleanForShortURL($toClean) {
	$extension = get_file_extension($toClean);
	$toClean = get_file_name_without_extension($toClean);
	
	$GLOBALS['normalizeGreeklishChars'] = array(
		'«'=>'-', '»'=>'-', 
		
		'Αι'=>'Ai', 'Αι'=>'Ai', 'αι'=>'ai', 'αί'=>'ai', 'ΕΙ'=>'EI', 'Ει'=>'Ei', 'ει'=>'ei', 'εί'=>'ei', 'ΟΙ'=>'OI', 'Οι'=>'Oi', 'οι'=>'oi', 'οί'=>'oi', 
		'ΥΙ'=>'I', 'Υι'=>'I', 'υι'=>'i', 'ΟΥ'=>'OU', 'Ου'=>'Ou', 'ου'=>'ou', 'ού'=>'ou', 'ΑΥ'=>'AV', 'Αυ'=>'Av', 'αυ'=>'av', 'αύ'=>'av', 
		'ΕΥ'=>'EV', 'Ευ'=>'Ev', 'ευ'=>'ev', 'εύ'=>'ev', 'ΗΥ'=>'IV', 'Ηυ'=>'Iv', 'ηυ'=>'iv', 
		
		'ΑΥ'=>'AF', 'Αυ'=>'Af', 'αυ'=>'af', 'ΕΥ'=>'EF', 'Ευ'=>'Ef', 'ευ'=>'ef', 'ΗΥ'=>'IF', 'Ηυ'=>'If', 'ηυ'=>'if', 
		'ΓΓ'=>'GG', 'γγ'=>'gg', 'ΓΚ'=>'GK', 'Γκ'=>'Gk', 'γκ'=>'gk', 'ΓΧ'=>'NH', 'Γχ'=>'Nh', 'γχ'=>'nh', 
		'ΜΠ'=>'MP', 'Μπ'=>'Mp', 'μπ'=>'mp', 'ΝΤ'=>'NT', 'Ντ'=>'Nt', 'ντ'=>'nt', 'ΣΛ'=>'SL', 'Σλ'=>'Sl', 'σλ'=>'sl', 
		'ΤΖ'=>'ΤZ', 'Τζ'=>'Tz', 'τζ'=>'tz', 'ΤΘ'=>'ΤTH', 'Τθ'=>'Tth', 'τθ'=>'tth', 
		
	    'Α'=>'A', 'α'=>'a', 'Ά'=>'A', 'ά'=>'a', 'Β'=>'V','β'=>'v', 'Γ'=>'G', 'γ'=>'g', 'Δ'=>'D', 'δ'=>'d', 'Ε'=>'E', 'ε'=>'e', 'Έ'=>'E', 'έ'=>'e', 
	    'Ζ'=>'Z', 'ζ'=>'z', 'Η'=>'I', 'η'=>'i', 'Ή'=>'I', 'ή'=>'i', 'Θ'=>'TH', 'θ'=>'th', 'Ι'=>'I', 'ι'=>'i', 'Ί'=>'I', 'ί'=>'i', 'Κ'=>'K', 'κ'=>'k', 
	    'Λ'=>'L', 'λ'=>'l', 'Μ'=>'M', 'μ'=>'m', 'Ν'=>'N', 'ν'=>'n', 'Ξ'=>'X', 'ξ'=>'x', 'Ο'=>'O', 'ο'=>'o','Ό'=>'O', 'ό'=>'o', 
	    'Π'=>'P', 'π'=>'p', 'Ρ'=>'R', 'ρ'=>'r', 'Σ'=>'S', 'σ'=>'s', 'ς'=>'s', 'Τ'=>'T', 'τ'=>'t', 'Υ'=>'Y', 'υ'=>'y', 'Ύ'=>'Y', 'ύ'=>'y',
	    'Φ'=>'F', 'φ'=>'f', 'Χ'=>'X', 'χ'=>'x', 'Ψ'=>'Ps', 'ψ'=>'ps', 'Ω'=>'W', 'ω'=>'w', 'Ώ'=>'W', 'ώ'=>'w'
	);

	$GLOBALS['normalizeGreekChars'] = array(
		'«'=>'-', '»'=>'-', 
		
	    'Α'=>'&Alpha;', 'α'=>'&alpha;', 'Ά'=>'&#x386;', 'ά'=>'&#x3ac;', 'Β'=>'&Beta;','β'=>'&beta;', 'Γ'=>'&Gamma;', 'γ'=>'&gamma;', 
		'Δ'=>'&Delta;', 'δ'=>'&delta;', 'Ε'=>'&Epsilon;', 'ε'=>'&epsilon;', 'Έ'=>'&#x388;', 'έ'=>'&#x3ad;', 
	    'Ζ'=>'&Zeta;', 'ζ'=>'&zeta;', 'Η'=>'&Eta;', 'η'=>'&eta;', 'Ή'=>'&#x389;', 'ή'=>'&#x3ae;', 'Θ'=>'&Theta;', 'θ'=>'&theta;', 
		'Ι'=>'&Iota;', 'ι'=>'&iota;', 'Ί'=>'&#x38a;', 'ί'=>'&#x3af;', 'Ϊ'=>'&#x3aa;', 'ΐ'=>'&#x390;', 'Κ'=>'&Kappa;', 'κ'=>'&kappa;', 
	    'Λ'=>'&Lambda;', 'λ'=>'&lambda;', 'Μ'=>'&Mu;', 'μ'=>'&mu;', 'Ν'=>'&Nu', 'ν'=>'&nu;', 'Ξ'=>'&Xi;', 'ξ'=>'&xi;', 
		'Ο'=>'&Omicron;', 'ο'=>'&omicron;','Ό'=>'&#x38c;', 'ό'=>'&#x03CC;', 
	    'Π'=>'&Pi;', 'π'=>'&pi;', 'Ρ'=>'&Rho;', 'ρ'=>'&rho;', 'Σ'=>'&Sigma;', 'σ'=>'&sigma;', 'ς'=>'&sigmaf;', 'Τ'=>'&Tau;', 'τ'=>'&tau;', 
		'Υ'=>'&Upsilon;', 'υ'=>'&upsilon;', 'Ύ'=>'&#x38e;', 'ύ'=>'&#x3cd;', 'Ϋ'=>'&#x3ab;', 'ϋ'=>'&#x3cb;', 'ΰ'=>'&#x3b0;',
	    'Φ'=>'&Phi;', 'φ'=>'&phi;', 'Χ'=>'&Chi;', 'χ'=>'&chi;', 'Ψ'=>'&Psi;', 'ψ'=>'&psi;', 'Ω'=>'&Omega;', 'ω'=>'&omega;', 'Ώ'=>'&#x38f;', 'ώ'=>'&#x3ce;'
	);

	$GLOBALS['normalizeSlavicChars']  = array(
		'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
		'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
		'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
		'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
		'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
		'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
		'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
		'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
	);
	$GLOBALS['normalizeChars'] = array(
	    'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 
	    'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 
	    'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 
	    'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 
	    'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 
	    'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 
	    'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
	);
	
	$toClean 	=	strtr($toClean, $GLOBALS['normalizeGreeklishChars']);
	$toClean 	=	strtr($toClean, $GLOBALS['normalizeSlavicChars']);
	
	$toClean	=	str_replace('/', '-', $toClean);
	$toClean	=	str_replace('&', '-and-', $toClean);
	$toClean	=	trim(preg_replace('/[^\w\d_ -]/si', '', $toClean));//remove all illegal chars
	$toClean	=	str_replace(' ', '-', $toClean);
	$toClean	=	str_replace('--', '-', $toClean);
	
	$toClean	=	substr(strtr($toClean, $GLOBALS['normalizeChars']),0, 80);

	return $toClean.".".$extension;
}
function addFileIdToFileName($file_name, $fileId) {
	return get_file_name_without_extension($file_name)."-".$fileId.".".get_file_extension($file_name);
}
function get_file_extension($file_name) {
	return cut_string_using_last('.',$file_name,'right',false);
}
function get_file_name_without_extension($file_name) {
	return cut_string_using_last('.',$file_name,'left',false);
}
function cut_string_using_last($character, $string, $side, $keep_character=true) { 
    $offset = ($keep_character ? 1 : 0); 
    $whole_length = strlen($string); 
    $right_length = (strlen(strrchr($string, $character)) - 1); 
    $left_length = ($whole_length - $right_length - 1); 
    switch($side) { 
        case 'left': 
            $piece = substr($string, 0, ($left_length + $offset)); 
            break; 
        case 'right': 
            $start = (0 - ($right_length + $offset)); 
            $piece = substr($string, $start); 
            break; 
        default: 
            $piece = false; 
            break; 
    } 
    return($piece); 
}
?>