<?php
error_reporting(E_ALL);				// 9/13/08
$units_side_bar_height = .6;		// max height of units sidebar as decimal fraction of screen height - default is 0.6 (60%)
$do_blink = TRUE;					// or FALSE , only - 4/11/10
$temp = get_variable('auto_poll');				// 1/28/09
$poll_val = ($temp==0)? "none" : $temp ;
$day_night = ((array_key_exists('day_night', ($_SESSION))) && ($_SESSION['day_night']))? $_SESSION['day_night'] : 'Day';	//	3/15/11
require_once('./incs/functions.inc.php');

$the_inc = ((array_key_exists('internet', ($_SESSION))) && ($_SESSION['internet']))? './incs/functions_major.inc.php' : './incs/functions_major_nm.inc.php';
$the_level = (isset($_SESSION['level'])) ? $_SESSION['level'] : 0 ;
require_once($the_inc);
?>

<SCRIPT>
window.onresize=function(){set_size()};

window.onload = function(){set_size();};

var mapWidth;
var mapHeight;
var listHeight;
var colwidth;
var listwidth;
var inner_listwidth;
var celwidth;
var res_celwidth;
var fac_celwidth;
var viewportwidth;
var viewportheight;
var colheight;
var outerwidth;
var outerheight;
var r_interval = null;
var latest_responder = 0;
var do_resp_update = true;
var responders_updated = new Array();
var inc_sortby = "tick_id";		//	options tick_id, scope, ticket_street, type, updated
var inc_sortdir = "ASC";		// Initial sort direction ascending;
var inc_sortbyfield = "";
var inc_sortvalue = "";
var inc_period = 0;
var baseIcon = L.Icon.extend({options: {shadowUrl: './our_icons/shadow.png',
	iconSize: [20, 32],	shadowSize: [37, 34], iconAnchor: [10, 31],	shadowAnchor: [10, 32], popupAnchor: [0, -20]
	}
	});
var baseFacIcon = L.Icon.extend({options: {iconSize: [28, 28], iconAnchor: [14, 29], popupAnchor: [0, -20]
	}
	});
var baseSqIcon = L.Icon.extend({options: {iconSize: [20, 20], iconAnchor: [10, 21], popupAnchor: [0, -20]
	}
	});
var basecrossIcon = L.Icon.extend({options: {iconSize: [40, 40], iconAnchor: [20, 41], popupAnchor: [0, -41]
	}
	});
			
var colors = new Array ('odd', 'even');

function set_size() {
	if (typeof window.innerWidth != 'undefined') {
		viewportwidth = window.innerWidth,
		viewportheight = window.innerHeight
		} else if (typeof document.documentElement != 'undefined'	&& typeof document.documentElement.clientWidth != 'undefined' && document.documentElement.clientWidth != 0) {
		viewportwidth = document.documentElement.clientWidth,
		viewportheight = document.documentElement.clientHeight
		} else {
		viewportwidth = document.getElementsByTagName('body')[0].clientWidth,
		viewportheight = document.getElementsByTagName('body')[0].clientHeight
		}
	mapWidth = viewportwidth * .40;
	mapHeight = mapWidth * .9;
	outerwidth = viewportwidth * .99;
	outerheight = viewportheight * .95;
	colwidth = outerwidth * .42;
	colheight = outerheight * .95;
	$('outer').style.width = outerwidth + "px";
	$('outer').style.height = outerheight + "px";
	$('leftcol').style.width = colwidth + "px";
	$('leftcol').style.height = colheight + "px";	
	$('rightcol').style.width = colwidth + "px";
	$('rightcol').style.height = colheight + "px";	
	$('map_canvas').style.width = mapWidth + "px";
	$('map_canvas').style.height = mapHeight + "px";
	map.invalidateSize();
	}

var max_zoom = <?php print get_variable('def_zoom');?>;

function validate(theForm) {						// form contents validation
	if (theForm.frm_remove) {
		if (theForm.frm_remove.checked) {
			var str = "Please confirm removing '" + theForm.frm_name.value + "'";
			if(confirm(str)) 	{
				theForm.submit();
				return true;}
			else 				{return false;}
			}
		}

	var errmsg="";
	if (theForm.frm_name.value.trim()=="")											{errmsg+="Location NAME is required.\n";}
	if (theForm.frm_descr.value.trim()=="")											{errmsg+="Location DESCRIPTION is required.\n";}
	if ((theForm.frm_lat.value=="") || (theForm.frm_lng.value==""))					{errmsg+="Location LOCATION must be set - click map location to set.\n";}	// 11/11/09 position mandatory
	
	if (errmsg!="") {
		alert ("Please correct the following and re-submit:\n\n" + errmsg);
		return false;
		}
	else {														// good to go!
//			top.upper.calls_start();
		theForm.submit();
//			return true;
		}
	}				// end function va lidate(theForm)

function add_res () {		// turns on add responder form
	showit('loc_add_form');
	hideIcons();			// hides responder icons
	map.setView(<?php echo get_variable('def_lat'); ?>, <?php echo get_variable('def_lng'); ?>);
	}

</SCRIPT>
</HEAD>
<BODY onLoad='set_size();'>
	<DIV ID='to_bottom' style='position:fixed; top:2px; left:50px; height: 12px; width: 10px;' onclick = 'to_bottom()'><IMG SRC='markers/down.png'  BORDER=0 /></DIV>
	<DIV id='outer' style='position: absolute; left: 0px;'>
		<DIV id='leftcol' style='position: absolute; left: 10px;'>
			<A NAME='top'>
			<FORM NAME= "loc_add_Form" METHOD="POST" ACTION="warn_locations.php?goadd=true">
			<TABLE BORDER="0" ID='addform' WIDTH='98%'>
				<TR>
					<TD ALIGN='center' COLSPAN='2'><FONT CLASS='header'><FONT SIZE=-1><FONT COLOR='green'><?php print get_text("Add Warn Location"); ?></FONT></FONT><BR /><BR />
						<FONT SIZE=-1>(mouseover caption for help information)</FONT></FONT><BR /><BR />
					</TD>
				</TR>		
				<TR CLASS = "even">
					<TD CLASS="td_label">
						<A CLASS="td_label" HREF="#" TITLE="Location Name - fill in with Name of the Location"><?php print get_text("Name"); ?></A>:&nbsp;<FONT COLOR='red' SIZE='-1'>*</FONT>&nbsp;
					</TD>
					<TD COLSPAN=3 >
						<INPUT MAXLENGTH="48" SIZE="48" TYPE="text" NAME="frm_name" VALUE="" />
					</TD>
				</TR>
				<TR class='spacer'>
					<TD class='spacer' COLSPAN=99>&nbsp;</TD>
				</TR>			
				<TR CLASS='even'>
					<TD CLASS="td_label">
						<A CLASS="td_label" HREF="#" TITLE="Street Address - type in street address in fields or click location on map "><?php print get_text("Street"); ?></A>:
					</TD>
					<TD>
						<INPUT SIZE="61" TYPE="text" NAME="frm_street" VALUE="" MAXLENGTH="61">
					</TD>
				</TR>
				<TR CLASS='odd'>
					<TD CLASS="td_label">
						<A CLASS="td_label" HREF="#" TITLE="City - defaults to default city set in configuration. Type in City if required"><?php print get_text("City"); ?></A>
							:&nbsp;&nbsp;&nbsp;&nbsp;<button type="button" onClick="Javascript:loc_lkup(document.loc_add_form);"><img src="./markers/glasses.png" alt="Lookup location." /></button>
					</TD>
					<TD>
						<INPUT SIZE="32" TYPE="text" NAME="frm_city" VALUE="<?php print get_variable('def_city'); ?>" MAXLENGTH="32" onChange = "this.value=capWords(this.value)">
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<A CLASS="td_label" HREF="#" TITLE="State - US State or non-US Country code e.g. UK for United Kingdom">St</A>:&nbsp;&nbsp;
						<INPUT SIZE="<?php print $st_size;?>" TYPE="text" NAME="frm_state" VALUE="<?php print get_variable('def_st'); ?>" MAXLENGTH="<?php print $st_size;?>">
					</TD>
				</TR> <!-- 7/5/10 -->
				<TR class='even'>
					<TD class='td_label'>
						<A CLASS="td_label" HREF="#" TITLE="Select Warning Type">Warning Type</A>:&nbsp;&nbsp;
					</TD>
					<TD CLASS='td_data'>
						<SELECT NAME="frm_loc_type" tabindex=6>
							<OPTION VALUE="0"><?php print $GLOBALS['LOC_TYPES_NAMES'][0];?></OPTION>
							<OPTION VALUE="1"><?php print $GLOBALS['LOC_TYPES_NAMES'][1];?></OPTION>
							<OPTION VALUE="2"><?php print $GLOBALS['LOC_TYPES_NAMES'][2];?></OPTION>
							<OPTION VALUE="3"><?php print $GLOBALS['LOC_TYPES_NAMES'][3];?></OPTION>
							<OPTION VALUE="4" SELECTED><?php print $GLOBALS['LOC_TYPES_NAMES'][4];?></OPTION>
						</SELECT>
					</TD>
				</TR>
				<TR CLASS = "even">
					<TD CLASS="td_label">
						<A CLASS="td_label" HREF="#" TITLE="Description - additional details about unit">Description</A>:&nbsp;<font color='red' size='-1'>*</font>
					</TD>
					<TD COLSPAN=3 >
						<TEXTAREA NAME="frm_descr" COLS=60 ROWS=2></TEXTAREA>
					</TD>
				</TR>
				<TR CLASS = "odd">
					<TD CLASS="td_label">
						<A CLASS="td_label" HREF="#" TITLE="Latitude and Longitude - set from map click"></A>
						<SPAN onClick = 'javascript: do_coords(document.loc_add_form.frm_lat.value ,document.loc_add_form.frm_lng.value)'>
						<?php print get_text("Lat/Lng"); ?>
						</SPAN>:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<IMG ID='lock_p' BORDER=0 SRC='./markers/unlock2.png' STYLE='vertical-align: middle'onClick = 'do_unlock_pos(document.loc_add_form);'>
					</TD>
					<TD COLSPAN=3>
						<INPUT TYPE="text" NAME="show_lat" SIZE=11 VALUE="" disabled />
						<INPUT TYPE="text" NAME="show_lng" SIZE=11 VALUE="" disabled />&nbsp;&nbsp;
<?php
						$locale = get_variable('locale');
						switch($locale) { 
							case "0":
?>
								<SPAN ID = 'usng_link' onClick = "do_usng_conv(loc_add_form)" style='font-weight: bold;'>USNG:</SPAN><INPUT TYPE="text" SIZE=19 NAME="frm_ngs" VALUE="" disabled />
<?php
								break;
							case "1":
?>
								<SPAN ID = 'osgb_link' style='font-weight: bold;'>OSGB:</SPAN><INPUT TYPE="text" SIZE=19 NAME="frm_ngs" VALUE="" disabled />
<?php
								break;
	
							default:
?>
								<SPAN ID = 'utm_link' style='font-weight: bold;'>UTM:</SPAN><INPUT TYPE="text" SIZE=19 NAME="frm_ngs" VALUE="" disabled />
<?php

							}
?>
					</TD>
				</TR>
				<TR CLASS='even'>
					<TD COLSPAN=4 ALIGN='center'><font color='red' size='-1'>*</FONT> Required
					</TD>
				</TR>
				<TR CLASS="odd" style='height: 30px; vertical-align: middle;'>
					<TD COLSPAN="2" ALIGN="center" style='vertical-align: middle;'>
						<SPAN id='can_but' CLASS='plain' style='float: none; width: 100px; display: inline-block;' onMouseover='do_hover(this.id);' onMouseout='do_plain(this.id);' onClick='document.can_Form.submit();'>Cancel</SPAN>
						<SPAN id='reset_but' CLASS='plain' style='float: none; width: 100px; display: inline-block;' onMouseover='do_hover(this.id);' onMouseout='do_plain(this.id);' onClick='do_add_reset(this.form);'>Reset</SPAN>
						<SPAN id='sub_but' CLASS='plain' style='float: none; width: 100px; display: inline-block;' onMouseover='do_hover(this.id);' onMouseout='do_plain(this.id);' onClick='validate(document.loc_add_Form);'>Submit</SPAN>
					</TD>
				</TR>
			</TABLE>
			<INPUT TYPE='hidden' NAME = 'frm_lat' VALUE=''/>
			<INPUT TYPE='hidden' NAME = 'frm_lng' VALUE=''/>
			<INPUT TYPE='hidden' NAME = 'frm_log_it' VALUE=''/>
			</FORM>
		</DIV>
		<DIV id='rightcol' style='position: absolute; right: 170px;'>
			<DIV id='map_canvas' style='border: 1px outset #707070;'></DIV>
			<BR /><BR /><B>Drag/Click to add Location</B>
		</DIV>
	</DIV>
<?php
$allow_filedelete = ($the_level == $GLOBALS['LEVEL_SUPER']) ? TRUE : FALSE;
print add_sidebar(TRUE, TRUE, TRUE, FALSE, $allow_filedelete, 0, 0, 0, 0);
?>
	<FORM NAME='can_Form' METHOD="post" ACTION = "warn_locations.php"></FORM>
	<FORM NAME='reset_Form' METHOD='get' ACTION='<?php print basename(__FILE__); ?>'> <!-- 9/4/12 -->
	<INPUT TYPE='hidden' NAME='func' VALUE='responder'>
	<INPUT TYPE='hidden' NAME='add' VALUE='true'>
	</FORM>
	<A NAME="bottom" />
	<DIV ID='to_top' style="position:fixed; bottom:50px; left:50px; height: 12px; width: 10px;" onclick = "location.href = '#top';"><IMG SRC="markers/up.png"  BORDER=0></div>
	<SCRIPT>
	var latLng;
	var boundary = [];			//	exclusion zones array
	var bound_names = [];
	var mapWidth = <?php print get_variable('map_width');?>+20;
	var mapHeight = <?php print get_variable('map_height');?>+20;;
	$('map_canvas').style.width = mapWidth + "px";
	$('map_canvas').style.height = mapHeight + "px";
	var theLocale = <?php print get_variable('locale');?>;
	var useOSMAP = <?php print get_variable('use_osmap');?>;
	init_map(2, <?php print get_variable('def_lat');?>, <?php print get_variable('def_lng');?>, "", 13, theLocale, useOSMAP, "tr");
	var bounds = map.getBounds();	
	var zoom = map.getZoom();
	var got_points = false;	// map is empty of points
	function onMapClick(e) {
	if(marker) {map.removeLayer(marker); }
		var iconurl = "./our_icons/yellow.png";
		icon = new baseIcon({iconUrl: iconurl});	
		marker = new L.marker(e.latlng, {id:1, icon:icon, draggable:'true'});
		marker.addTo(map);
		newGetAddress(e.latlng, "wa");
		};

	map.on('click', onMapClick);
<?php
	do_kml();
?>
	</SCRIPT>
	</BODY>
	</HTML>