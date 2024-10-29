<?php
/*
Plugin Name: Affiliate Manager
Plugin URI: http://geeklad.com/affiliate-manager
Description: Manage your affiliate program advertisements.  To edit, visit the <a href="tools.php?page=affiliate_manager">Affiliate Manager configuration page</a>.
Version: 0.5
Author: GeekLad
Author URI: http://geeklad.com/
*/

global $keywords_table, $affiliates_table, $campaigns_table, $ads_table, $displayed_campaigns;
$keywords_table = $wpdb->prefix . "affiliate_manager_keywords";
$affiliates_table = $wpdb->prefix . "affiliate_manager_affiliates";
$campaigns_table = $wpdb->prefix . "affiliate_manager_campaigns";
$ads_table = $wpdb->prefix . "affiliate_manager_ads";
$displayed_campaigns = array();

add_action('admin_menu', 'affiliate_manager_add_admin');
function affiliate_manager_add_admin() {
	add_submenu_page('tools.php', 'Affiliate Manager', 'Affiliate Manager', 8, 'affiliate_manager', 'affiliate_manager_admin');
}

function affiliate_manager_admin() {
	global $wpdb, $keywords_table, $affiliates_table, $campaigns_table, $ads_table;
	
	affiliate_manager_set_defaults();
	$options = get_option('affiliate_manager_options');
	if (!$options['installed'] == "yes")
		affiliate_manager_install();
	if ($_POST['action']) {
		switch($_POST['action']) {
			case "affiliate-manager-masking-options":
				echo "<div class=\"updated\"><p>Updated URL masking options.</p></div>";
				$options['base-url-string'] = $_POST['affiliate_manager_base_url_string'];
				$options['image-url-string'] = $_POST['affiliate_manager_image_url_string'];
				$options['target-url-string'] = $_POST['affiliate_manager_target_url_string'];				
				update_option('affiliate_manager_options', $options);
				break;
				
			case "affiliate-manager-update-market-leverage":
				if ($options['market-leverage-email'] != $_POST['marketleverage_email'] || $options['market-leverage-password'] != $_POST['marketleverage_password']) {
					require_once("market-leverage-fetch.php");
					$options['market-leverage-email'] = $_POST['marketleverage_email'];
					$options['market-leverage-password'] = $_POST['marketleverage_password'];
					$adcode = marketLeverageLogin($options['market-leverage-email'], $options['market-leverage-password'], true);
					
					if ($adcode) {
						echo "<div class=\"updated\"><p>Updated Market Leverage login information.</p></div>";
						$options['market-leverage-adcode'] = $adcode;
					}
					else {
						echo "<div class=\"updated\"><p>Unable to log into your account.  Please enter the correct email address and password.</p></div>";
						$options['market-leverage-email'] = $_POST['marketleverage_email'];
						$options['market-leverage-password'] = "";
						$options['market-leverage-adcode'] = "";
					}
					update_option('affiliate_manager_options', $options);
				}
				break;
				
			case "add-market-leverage-keyword":
				affiliate_manager_add_marketleverage_keyword($_POST['marketleverage_new_keyword']);
				break;
			
			case "affiliate-manager-update-market-leverage-display-options":
				echo "<div class=\"updated\"><p>Updated market leverage ad display options.</p></div>";
				$options['market-leverage-mask-images'] = $_POST['market_leverage_mask_images'];
				$options['market-leverage-mask-pages'] = $_POST['market_leverage_mask_pages'];
				$options['market-leverage-track-impressions'] = $_POST['market_leverage_track_impressions'];
				$options['market-leverage-track-clicks'] = $_POST['market_leverage_track_clicks'];
				if (!isset($_POST['market_leverage_display_link']))
					$options['market-leverage-display-link'] = 0;
				else
					$options['market-leverage-display-link'] = 1;
				update_option('affiliate_manager_options', $options);
				$mask_image = $options['market-leverage-mask-images'];
				$mask_target = $options['market-leverage-mask-pages'];
				$track_impressions = $options['market-leverage-track-impressions'];
				$track_clicks = $options['market-leverage-track-clicks'];
				$display_link = $options['market-leverage-display-link'];
				$query = "UPDATE $affiliates_table SET mask_image = $mask_image, mask_target = $mask_target, track_impressions = $track_impressions, track_clicks = $track_clicks, display_link =  $display_link WHERE affiliate_id = 1";
				$wpdb->query($query);
				break;
		}
	}
	
	if ($_GET['delete']) {
		echo "<div class=\"updated\"><p>Deleted keyword '" . $_GET['delete'] . "'.</p></div>";
		affiliate_manager_delete_keyword($_GET['delete']);
	}
	if ($_GET['delete_campaign']) {
		echo "<div class=\"updated\"><p>Deleted campaign " . $_GET['delete_campaign'] . ".</p></div>";
		affiliate_manager_delete_campaign($_GET['delete_campaign']);
	}
	if ($_GET['delete_ad']) {
		echo "<div class=\"updated\"><p>Deleted ad ID '" . $_GET['delete_ad'] . "'.</p></div>";
		affiliate_manager_delete_ad($_GET['delete_ad']);
	}
?>
	<div class="wrap">
<?php if(!$_GET['campaigns']) {
?>
		<h2>Affiliate Manager</h2>
		<div class="metabox-holder">
			<div class="inner-sidebar">
			</div>
			<div class="has-sidebar sm-padded">
				<div class="has-sidebar-content">
					<div class="meta-box-sortabless">
						<div class="postbox">
							<h3 class="hndle"><span>URL Masking Options</span></h3> 
							<div class="inside">
								<ul>
									<li>
										URL masking allows you to prevent users to know the source of your affilate advertisements.  You can mask either or both the images and the target locations for tha advertisements.  The options below allow you to determine what the masked URLs will look like.  Enable or disable the masking in the ad display options.
									</li>
									<li>
										<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
											<?php wp_nonce_field('update-options'); ?>
											<table class="form-table">
												<tr valign="top">
													<th scope="row">Base URL String (Default: ml)</th>
													<td><input type="text" name="affiliate_manager_base_url_string" size="50" value="<?php echo $options['base-url-string']; ?>" /></td>
												</tr>
												<tr valign="top">
													<th scope="row">Image URL String (Default: img)</th>
													<td><input type="text" name="affiliate_manager_image_url_string" size="50" value="<?php echo $options['image-url-string']; ?>" /></td>
												</tr>
												<tr valign="top">
													<th scope="row">Target URL String (Default: out)</th>
													<td><input type="text" name="affiliate_manager_target_url_string" size="50" value="<?php echo $options['target-url-string']; ?>" /></td>
												</tr>
												<tr>
													<td align="center" colspan="2">
														<input type="hidden" name="action" value="affiliate-manager-masking-options" />
														<input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
													</td>
												</tr>
											</table>
										</form>
									</li>
									<li>
										Image URLs will look like: <?php echo "http://" . $_SERVER['HTTP_HOST'] . "/" . $options['base-url-string'] . "/" . $options['image-url-string'] . "/1"; ?>
									</li>
									<li>
										Target URLs will look like: <?php echo "http://" . $_SERVER['HTTP_HOST'] . "/" . $options['base-url-string'] . "/" . $options['target-url-string'] . "/1"; ?>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="inner-sidebar">
				<div class="has-sidebar-content">
					<div class="meta-box-sortabless">
						<div class="postbox">
							<h3 class="hndle"><span>Current Keywords</span></h3> 
							<div class="inside" style="padding-left: 10px">
<?php
		$all_keywords = $wpdb->get_results("SELECT * from $keywords_table");
		if ($all_keywords) {
			foreach($all_keywords as $keyword) {
				$campaign_id = $keyword->campaign_id;
				$keyword = $keyword->keyword;
				echo "<h4><strong>Keyword: </strong><a href=\"" . str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) . "&campaigns=$keyword\" title=\"View all banners for $keyword\">$keyword</a> <span style=\"color: red;\"><a href=\"" . str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) . "&delete=$keyword\">[delete]</a></span><br></h4>";
				
				$query = "SELECT CONCAT(a.width, 'x', a.height) as size, COUNT(*) as adcount, SUM(a.impressions) as impressioncount, SUM(a.clicks) as clickcount FROM $keywords_table k, $campaigns_table c, $ads_table a WHERE k.keyword = '$keyword' AND k.keyword_id = c.keyword_id AND c.campaign_id = a.campaign_id AND a.adtype = 'image' GROUP BY a.width, a.height ORDER BY a.width DESC, a.height DESC";
				$images = $wpdb->get_results($query);
				if ($images) {
					echo "<table style=\"padding: 10px\"><tr><th>Image Size</th><th style=\"padding-left: 50px;\">Count</th>";
					foreach($images as $image) {
						$size = $image->size;
						$adcount = $image->adcount;
						$impressions = $image->impressioncount;
						$clickcount = $image->clickcount;
						echo "<tr><td>$size</td><td style=\"padding-left: 50px;\">$adcount</td></tr>";
					}
				}
				$query = "SELECT COUNT(*) as linkcount from $keywords_table k, $campaigns_table c, $ads_table a WHERE k.keyword = '$keyword' AND k.keyword_id = c.keyword_id AND c.campaign_id = a.campaign_id AND a.adtype = 'text'";
				$links = $wpdb->get_results($query);
				$linkcount = $links[0]->linkcount;
				echo "<tr><th>Text Link Count:</th><th style=\"padding-left: 50px;\">$linkcount</th></tr></table>";
			}
		}
		else
			echo "No keywords added yet.  Add some!";
?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="has-sidebar sm-padded">
				<div class="has-sidebar-content">
					<div class="meta-box-sortabless">
						<div class="postbox">
							<h3 class="hndle"><span>Market Leverage Login Information</span></h3> 
							<div class="inside">
								<ul>
									<li>
										<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
											<?php wp_nonce_field('update-options'); ?>
											<table class="form-table">
												<tr valign="top">
													<th scope="row">Account Email: </th>
													<td><input type="text" name="marketleverage_email" size="50" value="<?php echo $options['market-leverage-email']; ?>" /></td>
												</tr>
												<tr valign="top">
													<th scope="row">Account Password: </th>
													<td><input type="password" name="marketleverage_password" size="50" value="<?php echo $options['market-leverage-password']; ?>" /></td>
												</tr>
												<tr>
													<td align="center" colspan="2">
														<input type="hidden" name="action" value="affiliate-manager-update-market-leverage" />
														<input type="submit" class="button-primary" value="<?php _e('Update Account Info') ?>" />
													</td>
												</tr>
											</table>
										</form>
									</li>
								</ul>
							</div>
						</div>
						<div class="postbox">
							<h3 class="hndle"><span>Market Leverage Ad Display Options</span></h3> 
							<div class="inside">
								<ul>
									<li>
										<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
											<?php wp_nonce_field('update-options'); ?>
											<table class="form-table">
												<tr valign="top">
													<th scope="row">Mask Image URL?</th>
													<td>
														<select name="market_leverage_mask_images">
															<option value="1"<?php if($options['market-leverage-mask-images'] == 1) echo " selected";?>>Yes</option>
															<option value="0"<?php if($options['market-leverage-mask-images'] == 0) echo " selected";?>>No</option>
														</select>
													</td>
													<th scope="row">Track Image Impressions?</th>
													<td>
														<select name="market_leverage_track_impressions">
															<option value="1"<?php if($options['market-leverage-track-impressions'] == 1) echo " selected";?>>Yes</option>
															<option value="0"<?php if($options['market-leverage-track-impressions'] == 0) echo " selected";?>>No</option>
														</select>
													</td>
												</tr>
												<tr valign="top">
													<th scope="row">Mask Target URL?</th>
													<td>
														<select name="market_leverage_mask_pages">
															<option value="1"<?php if($options['market-leverage-mask-pages'] == 1) echo " selected";?>>Yes</option>
															<option value="0"<?php if($options['market-leverage-mask-pages'] == 0) echo " selected";?>>No</option>
														</select>
													</td>
													<th scope="row"><a href="#" title="Tracking clicks requires that you also mask the target URL.">Track Target clicks?</a></th>
													<td>
														<select name="market_leverage_track_clicks">
															<option value="1"<?php if($options['market-leverage-track-clicks'] == 1) echo " selected";?>>Yes</option>
															<option value="0"<?php if($options['market-leverage-track-clicks'] == 0) echo " selected";?>>No</option>
														</select>
													</td>
												</tr>
												<tr>
													<th scope="row">Display link to plugin homepage?</th>
													<td>
														<input type="checkbox" name="market_leverage_display_link" <?php if($options['market-leverage-display-link'] == 1) echo " checked";?> value="1">
													</td>
												</tr>
												<tr>
													<td align="right" colspan="3">
														<input type="hidden" name="action" value="affiliate-manager-update-market-leverage-display-options" />
														<input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
													</td>
												</tr>
											</table>
										</form>
									</li>
								</ul>
							</div>
						</div>
						<div class="postbox">
							<h3 class="hndle"><span>Add Market Leverage Campaigns with Keywords</span></h3> 
							<div class="inside"> 
								<ul>
									<li>
										<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
											<?php wp_nonce_field('update-options'); ?>
											<table class="form-table">
												<tr valign="top">
													<th scope="row">New Keyword: </th>
													<td><input type="text" name="marketleverage_new_keyword" size="50" /> 
														<input type="hidden" name="action" value="add-market-leverage-keyword" />
														<input type="submit" class="button-primary" value="<?php _e('Add') ?>" />
													</td>
												</tr>
											</table>
										</form>
									</ul>
								</li>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
	}
	else {
		$keyword = $_GET['campaigns'];
		$query = "SELECT a.campaign_id FROM $keywords_table k, $campaigns_table c, $ads_table a WHERE k.keyword = '$keyword' AND k.keyword_id = c.keyword_id AND c.campaign_id = a.campaign_id AND a.adtype = 'image' GROUP BY a.campaign_id ORDER BY a.campaign_id";
		$campaigns = $wpdb->get_results($query);
?>
		<h2>Campaigns for Keyword '<?php echo $keyword; ?>'</h2>
		<a href="tools.php?page=affiliate_manager">&lt;&lt; Go Back to Affiliate Manager Options</a>
		<div class="metabox-holder">
			<div class="inner-sidebar">
			</div>
			<div class="has-sidebar sm-padded">
				<div class="has-sidebar-content">
					<div class="meta-box-sortabless">
<?php
		foreach($campaigns as $campaign) {
			$campaign_id = $campaign->campaign_id;
?>
						<div class="postbox">
							<h3 class="hndle"><span>Campaign ID :<?php echo "$campaign_id <a href=\"" . str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) . "&delete_campaign=$campaign_id\">[delete]</a></span>"; ?></span></h3>
							<div class="inside">
								<table>
									<tr><th>Size</th><th>Image</th><th>Impressions</th><th>Clicks</th><th> </th></tr>
										
<?php
		$query = "SELECT * FROM $ads_table WHERE campaign_id = $campaign_id AND adtype = 'image' ORDER BY height*width";
		$ads = $wpdb->get_results($query);
		foreach($ads as $ad) {
			$ad_id = $ad->ad_id;
			$original_image_url = $ad->original_image_url;
			$width = $ad->width;
			$height = $ad->height;
			$thumb_height = 60;
			$thumb_width = 400;
			if ($height > $thumb_height)
				$scale = $thumb_height / $height;
			else
				$scale = 1;			
			if ($width > $thumb_width && $thumb_width / $width < $scale)
				$scale = $thumb_width / $width;
				
			$scale_width = $scale * $width;
			$scale_height = $scale * $height;
			$impressions = $ad->impressions;
			$clicks = $ad->clicks;
			
				
			$code = "<a href=\"#\" title=\"Click to see full-size banner in a new window\" onClick=\"MyWindow=window.open('$original_image_url','MyWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=" . intval($width+20) . ",height=" . intval($height+20) . "'); return false;\"><img src=\"$original_image_url\" width=\"$scale_width\" height=\"$scale_height\"/></a>";
			$delete = "<a href=\"" . str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) . "&delete_ad=$ad_id\">[delete ad]</a>";
			echo "<tr><td>$width x $height</td><td>$code</td><td>$impressions</td><td>$clicks</td><td>$delete</td></tr>\n";
		}
?>
								</table>
							</div>
						</div>
<?php
		}
?>
					</div>
				</div>
			</div>
		</div>
<?php
	}
?>
	</div>
<?php
}

function affiliate_manager_set_defaults() {
	global $wpdb, $affiliates_table;
	
	$options = get_option('affiliate_manager_options');
	if (!isset($options['base-url-string']))
		$options['base-url-string'] = "ml";
	if (!isset($options['image-url-string']))
		$options['image-url-string'] = "img";
	if (!isset($options['target-url-string']))
		$options['target-url-string'] = "out";
	if (!isset($options['market-leverage-mask-images'])) {
		$options['market-leverage-mask-images'] = "1";
		$mask_image = $options['market-leverage-mask-images'];
		$query = "UPDATE $affiliates_table SET mask_image = $mask_image WHERE affiliate_id = 1";
		$wpdb->query($query);
	}
	if (!isset($options['market-leverage-mask-pages'])) {
		$options['market-leverage-mask-pages'] = "1";
		$mask_target = $options['market-leverage-mask-pages'];
		$query = "UPDATE $affiliates_table SET mask_target = $mask_target WHERE affiliate_id = 1";
		$wpdb->query($query);
	}
	if (!isset($options['market-leverage-track-impressions'])) {
		$options['market-leverage-track-impressions'] = "1";
		$track_impressions = $options['market-leverage-track-impressions'];
		$query = "UPDATE $affiliates_table SET track_impressions = $track_impressions WHERE affiliate_id = 1";
		$wpdb->query($query);
	}
	if (!isset($options['market-leverage-track-clicks'])) {
		$options['market-leverage-track-clicks'] = "1";
		$track_clicks = $options['market-leverage-track-clicks'];
		$query = "UPDATE $affiliates_table SET track_clicks = $track_clicks WHERE affiliate_id = 1";
		$wpdb->query($query);
	}
	if (!isset($options['market-leverage-display-link'])) {
		$options['market-leverage-display-link'] = "1";
		$display_link = $options['market-leverage-display-link'];
		$query = "UPDATE $affiliates_table SET display_link = $display_link WHERE affiliate_id = 1";
		$wpdb->query($query);
	}
	update_option('affiliate_manager_options', $options);
}

function affiliate_manager_add_marketleverage_keyword($keywords) {
	$options = get_option('affiliate_manager_options');
	require_once("market-leverage-fetch.php");
	
	$email = $options['market-leverage-email'];
	$adcode = $options['market-leverage-adcode'];
	$password = $options['market-leverage-password'];
	
	echo "<div class=\"updated\"><p>Obtaining campaigns for keyword '$keywords'.  Please wait...</p></div>";
	set_time_limit(60);
	$result = marketLeverageLogin($email, $password);
	if (!$result) {
		echo "<div class=\"updated\"><p>Unable to log into your account.  Please enter the correct email address and password.</p></div>";
		return;
	}
	$creatives = fetchAllMarketLeverageCreatives($adcode, $password, $keywords);
	if ($creatives == "error") {
		return;
	}

	if ($creatives) {
		$campaigncount = count($creatives)-2;
		$imagecount = $creatives["imagecount"];
		$textcount = $creatives["textcount"];

		echo "<div class=\"updated\"><p>Done obtaining campaigns for '$keywords'.  Loading campaigns into database.  Please wait...</p></div>";
		$creatives["lastupdate"] = time();
		$creatives["imagecount"] = $imagecount;
		$creatives["textcount"] = $textcount;
		$creatives["campaigncount"] = $campaigncount;
		$campaigns[$keywords] = $creatives;

		affiliate_manager_update_marketleverage_db($keywords, $creatives);
	}
	else {
		echo "<div class=\"updated\"><p>No campaigns found for '$keywords'.</p></div>";
	}
}

function affiliate_manager_random_banner($size) {
	global $wpdb, $campaigns_table, $ads_table, $displayed_campaigns;
	
	if (preg_match("/^(\d*)x(\d*)$/", $size, $dimensions)) {
		$width = $dimensions[1];
		$height = $dimensions[2];
		if (count($displayed_campaigns) > 0) {
			$checkads = "c.campaign_id NOT IN (" . implode(", ", $displayed_campaigns) . ") AND";
		}
		else
			$checkads = "";
		$query = "SELECT * FROM $campaigns_table c, $ads_table a WHERE $checkads width = $width AND height = $height AND c.campaign_id = a.campaign_id ORDER BY RAND() LIMIT 1";
		$ad = $wpdb->get_results($query);
		if ($ad)
			affiliate_manager_displayAd($ad[0]->ad_id);
	}
}

function affiliate_manager_by_keyword($keyword, $size) {
	global $wpdb, $keywords_table, $campaigns_table, $ads_table, $displayed_campaigns;
	
	if (preg_match("/^(\d*)x(\d*)$/", $size, $dimensions)) {		
		$width = $dimensions[1];
		$height = $dimensions[2];
		if (count($displayed_campaigns) > 0) {
			$checkads = "c.campaign_id NOT IN (" . implode(", ", $displayed_campaigns) . ") AND";
		}
		else
			$checkads = "";
			
		$query = "SELECT * FROM $keywords_table k, $campaigns_table c, $ads_table a WHERE $checkads k.keyword = '$keyword' AND k.keyword_id = c.keyword_id AND c.campaign_id = a.campaign_id AND a.width = $width and a.height = $height ORDER BY RAND() LIMIT 1";
		$ad = $wpdb->get_results($query);
		if ($ad) {
			affiliate_manager_displayAd($ad[0]->ad_id);
		}
	}
}

function affiliate_manager_displayAd($id) {
	global $wpdb, $affiliates_table, $campaigns_table, $ads_table, $displayed_campaigns;
	
	if (count($displayed_campaigns) > 0) {
		$checkads = "c.campaign_id NOT IN (" . implode(", ", $displayed_campaigns) . ") AND";
	}
	else
		$checkads = "";
			
	$query = "SELECT * from $affiliates_table af, $campaigns_table c, $ads_table a WHERE $checkads af.affiliate_id = c.affiliate_id AND c.campaign_id = a.campaign_id AND ad_id = $id";
	$ad = $wpdb->get_results($query);
	if ($ad) {
		array_push($displayed_campaigns, $ad[0]->campaign_id);
		$options = get_option('affiliate_manager_options');
		$base = $options['base-url-string'];
		$image = $options['image-url-string'];
		$target = $options['target-url-string'];
		if ($ad[0]->mask_image == 1)
			$image_url = "http://" . $_SERVER['HTTP_HOST'] . "/$base/$image/$id";
		else
			$image_url = $ad[0]->image_url;
		if ($ad[0]->mask_target == 1)
			$target_url = "http://" . $_SERVER['HTTP_HOST'] . "/$base/$target/$id";
		else
			$target_url = $ad[0]->page_url;
		if ($ad[0]->track_impressions == 1) {
			$query = "UPDATE $ads_table SET impressions = impressions+1 WHERE ad_id = $id";
			$wpdb->query($query);
		}
		if ($ad[0]->display_link == 1) {
			$display_link = "<br><a href=\"http://geeklad.com/affiliate-manager\" onmouseover=\"this.style.textdecoration='underline';\" onmouseout=\"this.style.textdecoration='none';\" >Make Money Online</a>";
		}
		else {
			$display_link = "";
		}
		echo "<div style=\"font-family: arial,sans-serif; font-size: 10px; text-align: right; text-decoration: none; line-height: 10px;\"><a href=\"$target_url\" rel=\"nofollow\"><img src=\"$image_url\" style=\"border: 0 none;\" /></a>$display_link</div>";
	}
}

function affiliate_manager_image_proxy($id) {
	global $wpdb, $ads_table;
	
	$query = "SELECT * FROM $ads_table WHERE ad_id = $id";
	$ad = $wpdb->get_results($query);
	affiliate_manager_remote_load_file($ad[0]->original_image_url);
}

function affiliate_manager_remote_load_file($url) {
	preg_match("/http:\/\/([^\/]*)\//", $url, $matches);
	$host = $matches[1];
	$fp = @fsockopen($host, 80, $errno, $errstr, 20);
	if(!$fp)
		return false;
	
	fputs($fp, "GET $url HTTP/1.1\r\n");
	fputs($fp, "HOST: $host\r\n");
	fputs($fp, "Connection: close\r\n\r\n");
	while(!feof($fp)) {
		if (!$headersfound)
			$headers .= fread($fp, 1);
		else
			$file .= fread($fp, 1024);

		if(preg_match("/\r\n\r\n/", $headers))
			$headersfound = true;

		if(preg_match("/Moved/", $headers))
			$redirect = true;

		if($redirect && preg_match("/Location: ([^\s]*)\s$/", $headers, $newurl)) {
			affiliate_manager_remote_load_file($newurl[1]);
			return;
		}
	}
	fclose ($fp);
	preg_match("/Content-Length.*/", $headers, $length);
	preg_match("/Content-Type.*/", $headers, $type);
	header('Expires: ' . gmdate('D, d M Y H:i:s', time()+7*24*60*60) . ' GMT');
	header($length[0]);
	header($type[0]);
	echo $file;
	die();
}

function affiliate_manager_delete_keyword($keyword) {
	global $wpdb, $keywords_table, $campaigns_table, $ads_table;
	$query = "DELETE k, c, a FROM $keywords_table AS k INNER JOIN $campaigns_table AS c INNER JOIN $ads_table AS a WHERE k.keyword = '$keyword' AND k.keyword_id = c.keyword_id AND c.campaign_id = a.campaign_id";
	$wpdb->query($query);
}

function affiliate_manager_delete_campaign($campaign_id) {
	global $wpdb, $keywords_table, $campaigns_table, $ads_table;
	
	// Get the keyword id, in case there are no campaigns left when we're done
	$query = "SELECT * FROM $keywords_table k, $campaigns_table c WHERE k.keyword_id = c.keyword_id";
	$results = $wpdb->get_results($query);
	$keyword = $results[0]->keyword;
	$query = "DELETE c, a FROM $campaigns_table AS c INNER JOIN $ads_table AS a WHERE c.campaign_id = '$campaign_id' AND c.campaign_id = a.campaign_id";
	$wpdb->query($query);
	$query = "SELECT * FROM $keywords_table k, $campaigns_table c WHERE k.keyword = '$keyword' AND k.keyword_id = c.keyword_id";
	$results = $wpdb->get_results($query);
	if (!$results) {
		$query = "DELETE FROM $keywords_table WHERE keyword = '$keyword'";
		$wpdb->query($query);
	}
}

function affiliate_manager_delete_ad($id) {
	global $wpdb, $keywords_table, $campaigns_table, $ads_table;
	
	// Get the campaign id, in case there is nothing left when we're done deleting the ad
	$query = "SELECT * FROM $keywords_table k, $campaigns_table c, $ads_table a WHERE a.ad_id = $id and a.campaign_id = c.campaign_id and c.keyword_id = k.keyword_id";
	$results = $wpdb->get_results($query);
	$campaign_id = $results[0]->campaign_id;
	$query = "DELETE FROM $ads_table WHERE ad_id = $id";
	$wpdb->query($query);
	$query = "SELECT * FROM $campaigns_table WHERE campaign_id = $campaign_id";
	$results = $wpdb->get_results($query);
	if (!$results) {
		affiliate_manager_delete_campaign($campaign_id);
	}
}

function affiliate_manager_remote_original_url($url) {
	preg_match("/http:\/\/([^\/]*)\//", $url, $matches);
	$host = $matches[1];
	$fp = @fsockopen($host, 80, $errno, $errstr, 20);
	if(!$fp)
		return false;
	
	fputs($fp, "HEAD $url HTTP/1.1\r\n");
	fputs($fp, "HOST: $host\r\n");
	fputs($fp, "Connection: close\r\n\r\n");
	while(!feof($fp)) {
		if (!$headersfound)
			$headers .= fread($fp, 1);
		else
			$file .= fread($fp, 1024);

		if(preg_match("/\r\n\r\n/", $headers))
			$headersfound = true;

		if(preg_match("/Moved/", $headers))
			$redirect = true;

		if($redirect && preg_match("/Location: ([^\s]*)\s$/", $headers, $newurl)) {
			fclose ($fp);
			return affiliate_manager_remote_original_url($newurl[1]);
		}
	}
	fclose ($fp);
	return $url;
}

function affiliate_manager_install() {
	global $wpdb, $keywords_table, $affiliates_table, $campaigns_table, $ads_table;
	
	$query = "
		CREATE TABLE IF NOT EXISTS `$keywords_table` (
		  `keyword_id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `keyword` varchar(128) NOT NULL,
		  PRIMARY KEY (`keyword_id`),
		  UNIQUE KEY `keyword` (`keyword`)
		)";
	$wpdb->query($query);

	$query = "
		CREATE TABLE IF NOT EXISTS `$affiliates_table` (
		  `affiliate_id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `affiliate_override` varchar(64) NOT NULL,
		  `mask_image` tinyint(1) NOT NULL DEFAULT '1',
		  `mask_target` tinyint(1) NOT NULL DEFAULT '1',
		  `track_impressions` tinyint(1) NOT NULL DEFAULT '1',
		  `track_clicks` tinyint(1) NOT NULL DEFAULT '1',
		  `display_link` tinyint(1) NOT NULL DEFAULT '1',
		  PRIMARY KEY (`affiliate_id`)
		)";
	$wpdb->query($query);
	$query = "INSERT INTO `$affiliates_table` VALUES(1, 'CD17841', 1, 1, 1, 1, 1)";
	$wpdb->query($query);

	$query = "
		CREATE TABLE IF NOT EXISTS `$campaigns_table` (
		  `campaign_id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `affiliate_id` bigint(20) NOT NULL,
		  `keyword_id` bigint(20) NOT NULL,
		  `ml_id` int(11) NOT NULL,
		  `lastupdated` timestamp NULL DEFAULT NULL,
		  PRIMARY KEY (`campaign_id`),
		  UNIQUE KEY `ml_id` (`ml_id`),
		  KEY `keyword_id` (`keyword_id`)
		)";
	$wpdb->query($query);

	$query = "
		CREATE TABLE IF NOT EXISTS `$ads_table` (
		  `ad_id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `campaign_id` bigint(20) NOT NULL,
		  `adtype` enum('text','image') NOT NULL,
		  `width` int(11) DEFAULT NULL,
		  `height` int(11) DEFAULT NULL,
		  `image_url` varchar(256) NOT NULL,
		  `original_image_url` varchar(256) DEFAULT NULL,
		  `page_url` varchar(256) NOT NULL,
		  `impressions` bigint(20) NOT NULL,
		  `clicks` bigint(20) NOT NULL,
		  `expires` date DEFAULT NULL,
		  PRIMARY KEY (`ad_id`),
		  KEY `campaign_id` (`campaign_id`),
		  KEY `adtype` (`adtype`),
		  KEY `width` (`width`),
		  KEY `height` (`height`),
		  UNIQUE KEY (`page_url`)
		)";
	$wpdb->query($query);
	$options = get_option('affiliate_manager_options');
	$options['installed'] = "yes";
	update_option('affiliate_manager_options', $options);
}

function affiliate_manager_update_marketleverage_db($keywords, $campaigns) {
	global $wpdb, $keywords_table, $campaigns_table, $ads_table;
	
	require_once("domparser.php");
	$query = "INSERT INTO `$keywords_table` (`keyword`) VALUES ('$keywords')";
	$wpdb->query($query);
	$query = "SELECT * FROM `$keywords_table` WHERE `keyword` = '$keywords'";
	$result = $wpdb->get_results($query);
	$keyword_id = $result[0]->keyword_id;
	
	foreach($campaigns as $campaign) {
		$ml_id = $campaign['id'];
		$query = "INSERT INTO `$campaigns_table` (`affiliate_id`, `keyword_id`, `ml_id`, `lastupdated`) VALUES (1, $keyword_id, $ml_id, NOW())";
		$wpdb->query($query);
		$query = "SELECT * FROM `$campaigns_table` WHERE `ml_id` = $ml_id";
		$result = $wpdb->get_results($query);
		$campaign_id = $result[0]->campaign_id;
		$wpdb->query($query);
		if ($campaign["image"]) {
			foreach($campaign["image"] as $size=>$codes) {
				preg_match("/^(\d*)x(\d*)$/", $size, $split_size);
				$width = $split_size[1];
				$height = $split_size[2];
				foreach($codes as $code) {
					$code = mysql_real_escape_string($code);
					$html = str_get_html($code);
					$image_url = $html->find("img",0);
					$image_url = str_replace("\\", "", $image_url->src);
					$image_url = str_replace("\"", "", $image_url);
					$original_image_url = affiliate_manager_remote_original_url($image_url);
					$page_url = $html->find("a",0);
					$page_url = str_replace("\\", "", $page_url->href);
					$page_url = str_replace("\"", "", $page_url);
					$right_page_url = preg_replace("/http:\/\/[^\/]*\/(.*)/", "$1", $page_url);
					$html->clear();
					$html = null;
					$query = "SELECT * FROM `$ads_table` WHERE `adtype` = 'image' AND `original_image_url` = '$original_image_url' AND `page_url` LIKE '%$right_page_url'";
					$oldrecord = $wpdb->get_results($query);
					if (count($oldrecord) > 0)
						$query = "UPDATE `$ads_table` SET `campaign_id` = $campaign_id, `adtype` = 'image', `width` = $width, `height` = $height, `image_url` = '$image_url', `original_image_url` = '$original_image_url', `page_url` = '$page_url' WHERE `ad_id` = " . $oldrecord[0]->ad_id;
					else
						$query = "INSERT INTO `$ads_table` (`campaign_id`, `adtype`, `width`, `height`, `image_url`, `original_image_url`, `page_url`) VALUES ($campaign_id, 'image', '$width', '$height', '$image_url', '$original_image_url', '$page_url')";
					$wpdb->query($query);
				}
			}
		}
		if ($campaign["text"]) {
			foreach($campaign["text"] as $link) {
				$code = mysql_real_escape_string($code);
				$html = str_get_html($code);
				$page_url = $html->find("a",0);
				$page_url = str_replace("\\", "", $page_url->href);
				$page_url = str_replace("\"", "", $page_url);
				$right_page_url = preg_replace("/http:\/\/[^\/]*\/(.*)/", "$1", $page_url);
				$html->clear();
				$html = null;
				$query = "SELECT * FROM `$ads_table` WHERE `adtype` = 'text' AND `page_url` LIKE '%$right_page_url'";
				$oldrecord = $wpdb->get_results($query);
				if (count($oldrecord) > 0)
					$query = "UPDATE `$ads_table` SET `campaign_id` = $campaign_id, `adtype` = 'text', `page_url` = '$page_url' WHERE `ad_id` = " . $oldrecord[0]->ad_id;
				else
					$query = "INSERT INTO `$ads_table` (`campaign_id`, `adtype`, `page_url`) VALUES ($campaign_id, 'text', '$page_url')";
				$wpdb->query($query);
			}
		}
	}
}

add_action('init','affiliate_manager_setup_rewrite');
function affiliate_manager_setup_rewrite() {
	global $wp_rewrite;
	
 	if (isset($wp_rewrite) && $wp_rewrite->using_permalinks()) {
		add_filter('rewrite_rules_array', 'affiliate_manager_rewrite_rules');
		add_filter('query_vars','affiliate_manager_rewrite_rules_add_query_vars');
		add_action('parse_request','affiliate_manager_rewrite_rules_parse_request');
		$wp_rewrite->flush_rules();
	}
}

function affiliate_manager_rewrite_rules($rules) {
	global $wp_rewrite, $wpdb;
	
	if ($wp_rewrite->use_verbose_rules || !isset($wp_rewrite->use_verbose_rules)) {
		$ad_id = '$1';
	} else {
		$ad_id = '$matches[1]';
	}

	$options = get_option('affiliate_manager_options');
	$base = $options['base-url-string'];
	$image = $options['image-url-string'];
	$target = $options['target-url-string'];
	$newrules["$base/$target/([0-9]{1,})/?$"] = "index.php?affiliate_manager_command=out&affiliate_manager_ad_id=$ad_id";
	$newrules["$base/$image/([0-9]{1,})/?$"] = "index.php?affiliate_manager_command=img&affiliate_manager_ad_id=$ad_id";
	$rules = array_merge($newrules, $rules);
	return $rules;
}

function affiliate_manager_rewrite_rules_add_query_vars($query_vars) {
	return array_merge($query_vars,array('affiliate_manager_command', 'affiliate_manager_ad_id'));
}

function affiliate_manager_rewrite_rules_parse_request($req) {
	global $wpdb, $affiliates_table, $campaigns_table, $ads_table;
	
	if(!empty($req->query_vars['affiliate_manager_command'])) {
		$ad_id = $req->query_vars['affiliate_manager_ad_id'];
		$query = "SELECT af.*, a.* from $affiliates_table af, $campaigns_table c, $ads_table a WHERE af.affiliate_id = c.affiliate_id AND c.campaign_id = a.campaign_id AND ad_id = $ad_id";
		$ad = $wpdb->get_results($query);
		switch ($req->query_vars['affiliate_manager_command']) {
			case "out":
				$url = $ad[0]->page_url;
				if ($ad[0]->track_clicks == 1) {
					$query = "UPDATE $ads_table SET clicks = " . intval($ad[0]->clicks+1) . " WHERE ad_id = $ad_id";
					$wpdb->query($query);
				}
				header("Location: $url");
				die();
				break;
				
			case "img":
				affiliate_manager_image_proxy($ad_id);
				break;
		}
	}
}

// THIS IS ALL WIDGET CODE
// Got most of it from this tutorial: http://brainfart.com.ua/post/lesson-wordpress-multi-widgets/
add_action('init', 'affiliate_manager_widget_multi_register');
function affiliate_manager_widget_multi_register() {
 
	$prefix = 'affiliate-manager-multi'; // $id prefix
	$name = __('Affiliate Manager Widget');
	$widget_ops = array('classname' => 'affiliate_manager_widget_multi', 'description' => __('Place Affiliate Manager advertisements as widgets.'));
	$control_ops = array('width' => 200, 'height' => 200, 'id_base' => $prefix);
 
	$options = get_option('affiliate_manager_widget_multi');
	if(isset($options[0])) unset($options[0]);
 
	if(!empty($options)){
		foreach(array_keys($options) as $widget_number){
			wp_register_sidebar_widget($prefix.'-'.$widget_number, $name, 'affiliate_manager_widget_multi', $widget_ops, array( 'number' => $widget_number ));
			wp_register_widget_control($prefix.'-'.$widget_number, $name, 'affiliate_manager_widget_multi_control', $control_ops, array( 'number' => $widget_number ));
		}
	} else{
		$options = array();
		$widget_number = 1;
		wp_register_sidebar_widget($prefix.'-'.$widget_number, $name, 'affiliate_manager_widget_multi', $widget_ops, array( 'number' => $widget_number ));
		wp_register_widget_control($prefix.'-'.$widget_number, $name, 'affiliate_manager_widget_multi_control', $control_ops, array( 'number' => $widget_number ));
	}
}

function affiliate_manager_widget_multi($args) {
	global $wpdb, $keywords_table, $campaigns_table, $ads_table, $displayed_campaigns;
	
    extract($args);
	$id = str_replace("affiliate-manager-multi-", "", $widget_id);
	$affilate_manager_widget_options = get_option('affiliate_manager_widget_multi');
	$adsize = $affilate_manager_widget_options[$id]['ad-size'];
	$keyword = $affilate_manager_widget_options[$id]['keyword'];
	preg_match("/^(\d*)x(\d*)$/", $adsize, $size_parameters);
	$width = $size_parameters[1];
	$height = $size_parameters[2];
	if ($keyword == "_any")
		$keyword = "";
	else
		$keyword = "k.keyword = '$keyword' AND";
	if (count($displayed_campaigns) > 0) {
		$checkads = "c.campaign_id NOT IN (" . implode(", ", $displayed_campaigns) . ") AND";
	}
	else
		$checkads = "";
	
	$query = "SELECT a.* FROM $keywords_table k, $campaigns_table c, $ads_table a WHERE $checkads $keyword k.keyword_id = c.keyword_id AND c.campaign_id = a.campaign_id AND a.width = $width and a.height = $height ORDER BY RAND() LIMIT 1";
	$ad = $wpdb->get_results($query);
	if ($ad) {
		echo "<br>";
		affiliate_manager_displayAd($ad[0]->ad_id);
		echo "<br>";
	}
 }
 
 function affiliate_manager_widget_multi_control($args) {
	global $wpdb, $keywords_table, $campaigns_table, $ads_table;
 
	$prefix = 'affiliate-manager-multi'; // $id prefix
 
	$options = get_option('affiliate_manager_widget_multi');
	if(empty($options)) $options = array();
	if(isset($options[0])) unset($options[0]);
 
	// update options array
	if(!empty($_POST[$prefix]) && is_array($_POST)){
		foreach($_POST[$prefix] as $widget_number => $values){
			if(empty($values) && isset($options[$widget_number])) // user clicked cancel
				continue;
 
			if(!isset($options[$widget_number]) && $args['number'] == -1){
				$args['number'] = $widget_number;
				$options['last_number'] = $widget_number;
			}
			$options[$widget_number] = $values;
		}
 
		// update number
		if($args['number'] == -1 && !empty($options['last_number'])){
			$args['number'] = $options['last_number'];
		}
 
		// clear unused options and update options in DB. return actual options array
		$options = bf_smart_multiwidget_update($prefix, $options, $_POST[$prefix], $_POST['sidebar'], 'affiliate_manager_widget_multi');
	}
 
	// $number - is dynamic number for multi widget, gived by WP
	// by default $number = -1 (if no widgets activated). In this case we should use %i% for inputs
	//   to allow WP generate number automatically
	$number = ($args['number'] == -1)? '%i%' : $args['number'];
 
	// now we can output control
	$opts = @$options[$number];
 
	$selected_adsize = @$opts['ad-size'];
	$selected_keyword = @$opts['keyword'];
	$query = "SELECT DISTINCT keyword FROM $keywords_table ORDER BY keyword";
	$keywords = $wpdb->get_results($query);
	$query = "SELECT DISTINCT CONCAT(width, 'x', height) as adsize FROM $ads_table ORDER BY width, height";
	$adsizes = $wpdb->get_results($query);
 
	?>
    Keyword<br />
		<select name="<?php echo $prefix; ?>[<?php echo $number; ?>][keyword]">
			<option value="_any"<?php if($selected_keyword == "_any") echo " selected";?>>Any Keyword</option>
<?php
	foreach($keywords as $index=>$values) {
?>
			<option value="<?php echo $values->keyword; ?>"<?php if($values->keyword == $selected_keyword) echo " selected";?>><?php echo $values->keyword; ?></option>
<?php
	}
?>
		</select><br />
    Ad Size<br />
		<select name="<?php echo $prefix; ?>[<?php echo $number; ?>][ad-size]">
<?php
	foreach($adsizes as $index=>$values) {
?>
			<option value="<?php echo $values->adsize; ?>"<?php if($values->adsize == $selected_adsize) echo " selected";?>><?php echo $values->adsize; ?></option>
<?php
	}
?>
		</select>
	<?php
}

if(!function_exists('bf_smart_multiwidget_update')){
	function bf_smart_multiwidget_update($id_prefix, $options, $post, $sidebar, $option_name = ''){
		global $wp_registered_widgets;
		static $updated = false;
 
		// get active sidebar
		$sidebars_widgets = wp_get_sidebars_widgets();
		if ( isset($sidebars_widgets[$sidebar]) )
			$this_sidebar =& $sidebars_widgets[$sidebar];
		else
			$this_sidebar = array();
 
		// search unused options
		foreach ( $this_sidebar as $_widget_id ) {
			if(preg_match('/'.$id_prefix.'-([0-9]+)/i', $_widget_id, $match)){
				$widget_number = $match[1];
 
				// $_POST['widget-id'] contain current widgets set for current sidebar
				// $this_sidebar is not updated yet, so we can determine which was deleted
				if(!in_array($match[0], $_POST['widget-id'])){
					unset($options[$widget_number]);
				}
			}
		}
 
		// update database
		if(!empty($option_name)){
			update_option($option_name, $options);
			$updated = true;
		}
 
		// return updated array
		return $options;
	}
}
?>
