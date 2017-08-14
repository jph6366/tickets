<?php
$timezone = date_default_timezone_get();
date_default_timezone_set($timezone);
require_once('../incs/functions.inc.php');
@session_start();
session_write_close();
if($_GET['q'] != $_SESSION['id']) {
	exit();
	}
$id = $_GET['id'];

$time = microtime(true); // Gets microseconds
$eols = array ("\r\n", "\n", "\r");		// all flavors of eol
$internet = ((isset($_SESSION['internet'])) && ($_SESSION['internet'] == true)) ? true: false;
$istest = FALSE;
$iw_width= "270px";					// map infowindow with
$nature = get_text("Nature");			// 12/03/10
$disposition = get_text("Disposition");
$patient = get_text("Patient");
$incident = get_text("Incident");
$incidents = get_text("Incidents");
$gt_status = get_text("Status");
$ret_arr = array();

//	Count number of actions on Ticket

$acts_ary = $pats_ary = array();				// 6/2/10
$query = "SELECT `ticket_id`, COUNT(*) AS `the_count` FROM `$GLOBALS[mysql_prefix]action` GROUP BY `ticket_id`";
$result_temp = mysql_query($query) or do_error($query, 'mysql query failed', mysql_error(), basename( __FILE__), __LINE__);
while ($row = stripslashes_deep(mysql_fetch_assoc($result_temp))) 	{
	$acts_ary[$row['ticket_id']] = $row['the_count'];
	}

//	Count number of patients on Ticket

$query = "SELECT `ticket_id`, COUNT(*) AS `the_count` FROM `$GLOBALS[mysql_prefix]patient` GROUP BY `ticket_id`";
$result_temp = mysql_query($query) or do_error($query, 'mysql query failed', mysql_error(), basename( __FILE__), __LINE__);
while ($row = stripslashes_deep(mysql_fetch_assoc($result_temp))) 	{
	$pats_ary[$row['ticket_id']] = $row['the_count'];
	}	
	
$query = "SELECT *,problemstart AS problemstart,
	`problemend` AS `problemend`,
	`booked_date` AS `booked_date`,	
	`date` AS `date`, 
	`$GLOBALS[mysql_prefix]ticket`.`scope` AS scope, 
	`$GLOBALS[mysql_prefix]ticket`.`street` AS ticket_street, 
	`$GLOBALS[mysql_prefix]ticket`.`state` AS ticket_city, 
	`$GLOBALS[mysql_prefix]ticket`.`city` AS ticket_state,
	`$GLOBALS[mysql_prefix]ticket`.`updated` AS `updated`,
	`$GLOBALS[mysql_prefix]ticket`.`id` AS `tick_id`,
	`$GLOBALS[mysql_prefix]in_types`.`type` AS `type`, 
	`$GLOBALS[mysql_prefix]in_types`.`id` AS `t_id`,
	`$GLOBALS[mysql_prefix]ticket`.`description` AS `tick_descr`, 
	`$GLOBALS[mysql_prefix]ticket`.lat AS `lat`,
	`$GLOBALS[mysql_prefix]ticket`.lng AS `lng`, 
	`$GLOBALS[mysql_prefix]facilities`.lat AS `fac_lat`,
	`$GLOBALS[mysql_prefix]facilities`.lng AS `fac_lng`, 
	`$GLOBALS[mysql_prefix]facilities`.`name` AS `fac_name`,
	(SELECT  COUNT(*) as numfound FROM `$GLOBALS[mysql_prefix]assigns` 
		WHERE `$GLOBALS[mysql_prefix]assigns`.`ticket_id` = `$GLOBALS[mysql_prefix]ticket`.`id`  
		AND `clear` IS NULL OR DATE_FORMAT(`clear`,'%y') = '00' ) 
		AS `units_assigned`			
	FROM `$GLOBALS[mysql_prefix]ticket` 
	LEFT JOIN `$GLOBALS[mysql_prefix]allocates` 
		ON `$GLOBALS[mysql_prefix]ticket`.id=`$GLOBALS[mysql_prefix]allocates`.`resource_id`			
	LEFT JOIN `$GLOBALS[mysql_prefix]in_types` 
		ON `$GLOBALS[mysql_prefix]ticket`.in_types_id=`$GLOBALS[mysql_prefix]in_types`.`id` 
	LEFT JOIN `$GLOBALS[mysql_prefix]facilities` 
		ON `$GLOBALS[mysql_prefix]ticket`.rec_facility=`$GLOBALS[mysql_prefix]facilities`.`id`
	WHERE `$GLOBALS[mysql_prefix]ticket`.`id` = " . $id;
$result = mysql_query($query) or do_error($query, 'mysql query failed', mysql_error(), basename( __FILE__), __LINE__);
$num_rows = mysql_num_rows($result);
$row = stripslashes_deep(mysql_fetch_assoc($result));
$problemstart = strtotime($row['problemstart']);
$now = mysql_format_date(time() - (get_variable('delta_mins')*60));
$now = strtotime($now);
$difference = round(abs($now - $problemstart) / 60,2);
$type = shorten($row['type'], 18);
$severity = $row['severity'];
$status = $row['status'];
$the_id = $row['tick_id'];		// 11/27/09
$radius = $row['radius'];
$updated = format_sb_date_2($row['updated']);
$the_scope = htmlentities(shorten($row['scope'], 30), ENT_QUOTES);
$address_street=htmlentities(shorten($row['ticket_street'] . " " . $row['ticket_city'], 20), ENT_QUOTES);
$lat = $row['lat'];
$lng = $row['lng'];
$num_assigned = $row['units_assigned'];
$num_actions = array_key_exists ($the_id , $acts_ary)? $acts_ary[$the_id]: 0;		// 6/2/10
$num_patients = array_key_exists ($the_id , $pats_ary)? $pats_ary[$the_id]: 0;
if ($status== $GLOBALS['STATUS_CLOSED']) {
	$strike = "<strike>"; $strikend = "</strike>";
	}
else { $strike = $strikend = "";}

if (intval($row['radius']) > 0) {
	$color= (substr($row['color'], 0, 1)=="#")? $row['color']: "blue";		// black default
	}				// end if (intval($row['radius']) 
$color = isset($color) ? $color : "blue";
if ($row['tick_descr'] == '') $row['tick_descr'] = '[no description]';	// 8/12/09
if (get_variable('abbreviate_description'))	{	//do abbreviations on description, affected if neccesary
	if (strlen($row['tick_descr']) > get_variable('abbreviate_description')) {
		$row['tick_descr'] = substr($row['tick_descr'],0,get_variable('abbreviate_description')).'...';
		}
	}
if (get_variable('abbreviate_affected')) {
	if (strlen($row['affected']) > get_variable('abbreviate_affected')) {
		$row['affected'] = substr($row['affected'],0,get_variable('abbreviate_affected')).'...';
		}
	}

$A = array_key_exists ($the_id , $acts_ary)? $acts_ary[$the_id]: "&nbsp;";		// 6/2/10
$P = array_key_exists ($the_id , $pats_ary)? $pats_ary[$the_id]: "&nbsp;";
$pats_count = (isset($pats_ary[$the_id])) ? $pats_ary[$the_id] : "&nbsp;";
$acts_count = (isset($acts_ary[$the_id])) ? $acts_ary[$the_id] : "&nbsp;";	

$locale = get_variable('locale');	// 08/03/09			
if (my_is_float($row['lat'])) {		// 6/21/10
	$temp_array[0] = $row['lat'];
	$temp_array[1] = $row['lng'];
	$temp_array[2] = htmlentities(shorten($row['scope'], 48), ENT_QUOTES);
	$temp_array[3] = htmlentities(shorten(str_replace($eols, " ", $row['tick_descr']), 256), ENT_QUOTES);
	$street = empty($row['ticket_street'])? "" : replace_quotes($row['ticket_street']) . "<BR/>" . replace_quotes($row['ticket_city']) . " " . replace_quotes($row['ticket_state']) ;
	$todisp = (is_guest()|| is_unit())? "": "<A id='disp_" . $the_id . "' CLASS='plain' style='float: none; color: #000000;' HREF='{$_SESSION['routesfile']}?ticket_id={$the_id}' onMouseOver=\"do_hover(this.id);\" onMouseOut=\"do_plain(this.id);\">Dispatch</A>";	// 7/27/10

	$rand = ($istest)? "&rand=" . chr(rand(65,90)) : "";													// 10/21/08
	$theTabs = "<div class='infowin'><BR />";
	$theTabs .= '<div class="tabBox" style="float: left; width: 100%;">';
	$theTabs .= '<div class="tabArea">';
	$theTabs .= '<span id="tab1" class="tabinuse" style="cursor: pointer;" onClick="do_tab(\'tab1\', 1, null, null);">Summary</span>';
	$theTabs .= '<span id="tab2" class="tab" style="cursor: pointer;" onClick="do_tab(\'tab2\', 2, null, null);">Details</span>';
	$theTabs .= '<span id="tab3" class="tab" style="cursor: pointer;" onClick="do_tab(\'tab3\', 3, ' . $row['lat'] . ',' . $row['lng'] . ');">Location</span>';
	$theTabs .= '</div>';
	$theTabs .= '<div class="contentwrapper">';

	$tab_1 = "<TABLE width='280px' style='height: auto;'><TR><TD><TABLE width='98%'>";
	$tab_1 .= "<TR CLASS='even'><TD CLASS='td_data text text_center' COLSPAN=2><B>$strike" . htmlentities(shorten($row['scope'], 48), ENT_QUOTES)  . "$strikend</B></TD></TR>";
	$tab_1 .= "<TR CLASS='odd'><TD class='td_label text text_left'>As of:</TD><TD CLASS='td_data text text_left'>" . format_date_2(($row['updated'])) . "</TD></TR>";
	if (is_date($row['booked_date'])){
		$tab_1 .= "<TR CLASS='odd'><TD class='td_label text text_left'>Booked Date:</TD><TD CLASS='td_data text text_left'>" . format_date_2($row['booked_date']) . "</TD></TR>";	//10/27/09, 3/15/11
		}
	$tab_1 .= "<TR CLASS='even'><TD class='td_label text text_left'>Reported by:</TD><TD CLASS='td_data text text_left'>" . replace_quotes(shorten($row['contact'], 32)) . "</TD></TR>";
	$tab_1 .= "<TR CLASS='odd'><TD class='td_label text text_left'>Phone:</TD><TD CLASS='td_data text text_left'>" . format_phone($row['phone']) . "</TD></TR>";
	$tab_1 .= "<TR CLASS='even'><TD class='td_label text text_left'>Addr:</TD><TD CLASS='td_data text text_left'>$address_street</TD></TR>";

	$elapsed = get_elapsed_time ($row);
	$tab_1 .= "<TR CLASS='odd'><TD class='td_label text text_left'>Status:</TD><TD CLASS='td_data text text_left'>" . get_status($row['status']) . "&nbsp;&nbsp;&nbsp;($elapsed)</TD></TR>";	// 3/27/10
	$tab_1 .= (empty($row['fac_name']))? "" : "<TR CLASS='even'><TD class='td_label text text_left'>Receiving Facility:</TD><TD CLASS='td_data text text_left'>" . replace_quotes(shorten($row['fac_name'], 30))  . "</TD></TR>";	//3/27/10, 3/15/11
	$utm = get_variable('UTM');
	if ($utm==1) {
		$coords =  $row['lat'] . "," . $row['lng'];																	// 8/12/09
		$tab_1 .= "<TR CLASS='even'><TD class='td_label text text_left'>UTM grid:</TD><TD CLASS='td_data text text_left'>" . toUTM($coords) . "</TD></TR>";
		}
	$tab_1 .= "</TABLE></TD></TR>";
	$tab_1 .= 	"</FONT></TD></TR></TABLE>";			// 11/6/08	

	$tab_2 = "<TABLE width='280px' style='height: 280px;' ><TR><TD><TABLE width='98%'>";
	$tab_2 .= "<TR CLASS='even'><TD class='td_label text text_left'>Description:</TD><TD CLASS='td_data text text_left'>" . replace_quotes(shorten(str_replace($eols, " ", $row['tick_descr']), 48)) . "</TD></TR>";
	$tab_2 .= "<TR CLASS='even'><TD class='td_label text text_left'>911 contact:</TD><TD CLASS='td_data text text_left'>" . shorten($row['nine_one_one'], 48) . "</TD></TR>";
	$tab_2 .= "<TR CLASS='odd'><TD class='td_label text text_left'>{$disposition}:</TD><TD CLASS='td_data text text_left'>" . shorten(replace_quotes($row['comments']), 48) . "</TD></TR></TABLE></TD></TR>";		// 8/13/09, 3/15/11
	$tab_2 .= "<TR><TD COLSPAN=2><DIV style='max-height: 200px; overflow-y: scroll;'>" . show_assigns(0, $the_id) . "</DIV></TD></TR>";

	$tab_2 .= "</TABLE>";			// 11/6/08			
	
	$tab_3 = "<TABLE width='280px' style='height: 280px;'><TR><TD>";
	$tab_3 .= "<TABLE width='98%'>";

	switch($locale) { 
		case "0":
		$tab_3 .= "<TR CLASS='odd'><TD class='td_label text text_left'>USNG:</TD><TD CLASS='td_data text text_left'>" . LLtoUSNG($row['lat'], $row['lng']) . "</TD></TR>";	// 8/23/08, 10/15/08, 8/3/09
		break;
	
		case "1":
		$tab_3 .= "<TR CLASS='odd'>	<TD class='td_label text text_left'>OSGB:</TD><TD CLASS='td_data text text_left'>" . LLtoOSGB($row['lat'], $row['lng']) . "</TD></TR>";	// 8/23/08, 10/15/08, 8/3/09
		break;
	
		case "2":
		$coords =  $row['lat'] . "," . $row['lng'];							// 8/12/09
		$tab_3 .= "<TR CLASS='odd'>	<TD class='td_label text text_left'>UTM:</TD><TD CLASS='td_data text text_left'>" . toUTM($coords) . "</TD></TR>";	// 8/23/08, 10/15/08, 8/3/09
		break;
	
		default:
		print "ERROR in " . basename(__FILE__) . " " . __LINE__ . "<BR />";
		}
	$tab_3 .= "<TR><TD class='td_label text text_left'>Lat</TD><TD class='td_data text text_left'>" . $row['lat'] . "</TD></TR>";
	$tab_3 .= "<TR><TD class='td_label text text_left'>Lng</TD><TD class='td_data text text_left'>" . $row['lng'] . "</TD></TR>";
	$tab_3 .= "</TABLE></TD></TR><R><TD><TABLE width='100%'>";			// 11/6/08
	$tab_3 .= "<TR><TD style='text-align: center;'><CENTER><DIV id='minimap' style='height: 180px; width: 180px; border: 2px outset #707070;'>Map Here</DIV></CENTER></TD></TR>";
	$tab_3 .= "</TABLE></TD</TR></TABLE>";
	}
	
$theTabs .= "<div class='content' id='content1' style = 'display: block;'>" . $tab_1 . "</div>";
$theTabs .= "<div class='content' id='content2' style = 'display: none;'>" . $tab_2 . "</div>";
$theTabs .= "<div class='content' id='content3' style = 'display: none;'>" . $tab_3 . "</div>";
$theTabs .= "</div>";
$theTabs .= "</div>";
$theTabs .= "</div>";
$ret_arr[0] = $theTabs;
print json_encode($ret_arr);

exit();
?>