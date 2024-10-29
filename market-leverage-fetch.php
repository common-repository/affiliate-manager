<?php
require_once("domparser.php");
require_once("nusoap/nusoap.php");

global $ch, $cookiefile;
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

function marketLeverageLogin($email, $password, $get_adcode = false) {
	global $ch, $cookiefile;
	
	$cookiefile = (string)tempnam("/tmp", "____");
	curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookiefile);
	
	// Load the initial site, so we get the cookies and stuff
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_URL, "http://www.marketleverage.com/");
	curl_exec($ch);
	
	// Submit the login form
	curl_setopt($ch, CURLOPT_URL, "https://users.marketleverage.com");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "DL_AUTH_USERNAME=$email&DL_AUTH_PASSWORD=$password&submit=LOGIN&next=/");
	curl_exec($ch);
	
	if ($get_adcode) {
		curl_setopt($ch, CURLOPT_URL, "https://users.marketleverage.com/partners/view_program_banners.html?program_id=4110");
		curl_setopt($ch, CURLOPT_POST, 0);
		$output = curl_exec($ch);
		if(preg_match("/\.com\/a\.php\?a=([^\&]*)\&/", $output, $link)) {
			curl_close($ch);
			unlink($cookiefile);
			return $link[1];
		}
		else {
			curl_close($ch);
			unlink($cookiefile);
			return false;
		}
	}
	else {
		return true;
	}
}

function fetchMarketLeverageProgramIDs($keyword) {
	global $ch, $cookiefile;
	
	curl_setopt($ch, CURLOPT_URL, "https://users.marketleverage.com/partners/program_selective.html");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "keyword=" . urlencode($keyword) . "&keywordmatch=B&submit=Submit");
	$output = curl_exec($ch);
	if(file_exists($cookiefile)) {
		curl_close($ch);
		unlink($cookiefile);
	}
	$output = str_get_html($output);
	$programs = $output->find('a[href*=view_program_banners.html]');
	$output->clear();
	$output = array();
	foreach($programs as $program) {
		if (preg_match("/.*program_id=(?!4110)/", $program->attr['href']) || $keyword == "referral")
			$output[count($output)] = preg_replace("/.*program_id=/", "", $program->attr['href']);
	}
	if (count($output) > 0) {
		return $output;
	}
	else {
		return false;
	}
}

function fetchMarketLeverageCreatives($adcode, $password, $id) {
	$proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
	$proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
	$proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
	$proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';
	$useCURL = isset($_POST['usecurl']) ? $_POST['usecurl'] : '0';
	$client = new nusoap_client("http://users.marketleverage.com/api/soap_affiliate.php", false,
							$proxyhost, $proxyport, $proxyusername, $proxypassword);
	$err = $client->getError();
	if ($err) {
		return false;
	}
	$client->setUseCurl($useCURL);
	// This is an archaic parameter list
	$params = array(
	    'client' => "marketleverage",
	    'add_code'         => $adcode,
	    'password'         => $password,
	    'program_id'          => $id,
	    'agreed_to_terms'       => 1
	);
	$result = $client->call('creativeInfo', $params, 'http://soapinterop.org/', 'http://users.marketleverage.com/api/soap_affiliate.php/creativeInfo');
	if (is_string($result)){
		$result = htmlspecialchars_decode(htmlspecialchars_decode($result));
		$creatives = getTag("creative", $result);
		if(count($creatives) > 0) {
			$output = array();
			$imagecount = 0;
			foreach ($creatives as $creative) {
				$type = getTag("banner_type", $creative);
				switch ($type[0]) {
					case "TEXT_CREATIVE":
						$code = getTag("banner_code", $creative);
						$code = preg_replace("/([^\n]*)\n.*/", "$1", $code[0]);
						if (!$output["text"])
							$output["text"] = array();
						array_push($output["text"], $code);
						break;
						
					case "IMAGE_CREATIVE":
						$code = getTag("banner_code", $creative);
						$code = $code[0];
						$size = getTag("size", $creative);
						$size = preg_replace("/ /", "", $size[0]);
						$size = preg_replace("/X/", "x", $size);
						if (!$output["image"][$size])
							$output["image"][$size] = array();
						array_push($output["image"][$size], $code);
						$imagecount++;
						break;
				}
			}
			if (count($output) > 0) {
				$output["textcount"] = count($output["text"]);
				$output["imagecount"] = $imagecount;
				$client = null;
				return $output;
			}
			else {
				$client = null;
				return false;
			}
		}
		else {
			$client = null;
			return false;
		}
	}
	else {
		if ($result["faultcode"] == "No creatives available for this campaign" || $result["faultcode"] == "You do not have access to this campaign" || $result["faultcode"] == "Must agree to terms of this campaign to view creative information") {
			$client = null;
			return false;
		}
		else {
			echo "<div class=\"updated\"><p>Error: <pre>" . print_r($result) . "</pre></p></div>";
			$client = null;
			return "error";
		}
	}
}

function getTag($tag, $string) {
	if (preg_match_all("/<" . preg_quote($tag) . ">((?:(?!<\/" . preg_quote($tag) . ">).)*)<\/" . preg_quote($tag) . ">/is", $string, $matches) > 0) {
		return $matches[1];
	}
	else {
		return false;
	}
}

function fetchAllMarketLeverageCreatives($adcode, $password, $keyword) {
	$ids = fetchMarketLeverageProgramIDs($keyword);
	$all_creatives = array();
	$textcount = 0;
	$imagecount = 0;
	if (count($ids) > 0) {
		foreach($ids as $id) {
			$options = get_option("affiliate_manager_options");
			if (!$options['market-leverage-ignore-list'])
				$options['market-leverage-ignore-list'] = array();
			if (!in_array($id, $options['market-leverage-ignore-list'])) {
				array_push($options['market-leverage-ignore-list'], $id);
				update_option("affiliate_manager_options", $options);
				$creatives = fetchMarketLeverageCreatives($adcode, $password, $id);
				array_pop($options['market-leverage-ignore-list']);
				update_option("affiliate_manager_options", $options);
				if ($creatives && $creatives != "error") {
					$creatives["id"] = $id;
					$all_creatives[count($all_creatives)] = $creatives;
					$textcount += $creatives["textcount"];
					$imagecount += $creatives["imagecount"];
				}
				$creatives = null;
			}
		}
	}
	if (count($all_creatives) > 0) {
		$all_creatives["textcount"] = $textcount;
		$all_creatives["imagecount"] = $imagecount;
		return $all_creatives;
	}
	else
		return false;
}
?>
