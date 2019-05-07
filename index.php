<?php
	# squidGuard Reports.
	# Copyright (C) 2019  Ramón Román Castro <ramonromancastro@gmail.com>

	# This program is free software; you can redistribute it and/or
	# modify it under the terms of the GNU General Public License
	# as published by the Free Software Foundation; either version 2
	# of the License, or (at your option) any later version.

	# This program is distributed in the hope that it will be useful,
	# but WITHOUT ANY WARRANTY; without even the implied warranty of
	# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	# GNU General Public License for more details.

	# You should have received a copy of the GNU General Public License
	# along with this program; if not, write to the Free Software
	# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

	####################################################################################
	# GLOBAL CONSTANTS
	####################################################################################

	define('APP_VERSION','0.2.5');
	define('APP_PATH', getcwd());
	define('APP_CONTEXT', str_replace($_SERVER['DOCUMENT_ROOT'],'',APP_PATH));
	define('LANGUAGE_PATH',APP_PATH . DIRECTORY_SEPARATOR . 'langs');
	define('DEFAULT_LANGUAGE','es-es');
	define('LANGUAGE_SEPARATOR','|');
	
	####################################################################################
	# GLOBAL VARIABLES
	####################################################################################
	
	$languages = array();
	$config = array();
	
	$config['language'] = DEFAULT_LANGUAGE;
	
	####################################################################################
	# GLOBAL INCLUDES
	####################################################################################
	
	require "config.php";
	
	####################################################################################
	# FUNCTIONS
	####################################################################################

	#-----------------------------------------------------------------------------------
	# Helpers
	#-----------------------------------------------------------------------------------
	
	function url_getSiteUrl(){
		# Scheme
		$scheme = ((isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS']=='on'))?'https':'http';
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') $scheme = 'https';
		if (isset($_SERVER['FORWARDED']) && preg_match('/proto=([^;]+)/',$_SERVER['FORWARDED'],$matches)) $scheme = $matches[1];
		
		# Host
		$host = $_SERVER['HTTP_HOST'];
		if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
		if (isset($_SERVER['FORWARDED']) && preg_match('/host=([^;: ]*):?([^; ]*)/',$_SERVER['FORWARDED'],$matches)) $host = $matches[1];
		
		# Port
		if (isset($_SERVER['SERVER_PORT'])) $port = $_SERVER['SERVER_PORT'];
		if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) $port = $_SERVER['HTTP_X_FORWARDED_PORT'];
		if (isset($_SERVER['FORWARDED']) && preg_match('/host=([^;: ]*):?([^; ]*)/',$_SERVER['FORWARDED'],$matches)) $port = $matches[2];
		$port = ((($scheme == 'http') && ($port != 80)) || (($scheme == 'https') && ($port != 443)))?$port:null;
		
		# Context
		$context = APP_CONTEXT;
		
		return $scheme . '://' . $host . (isset($port)?":$port":'') . $context . '/';
	}

	function _q($query_data){
		return http_build_query(array_merge($_GET,$query_data));
	}
	
	function _t($id){
		global $config,$languages;
		if (isset($_GET['lang']) && isset($languages[$_GET['lang']])){
			return (isset($languages[$_GET['lang']][$id]))?$languages[$_GET['lang']][$id]:$id;
		}
		else{
			return (isset($languages[$config['language']][$id]))?$languages[$config['language']][$id]:$id;
		}
	}
	
	function loadLanguages(){
		global $config,$languages;

		$langdir = array_diff(scandir(LANGUAGE_PATH. DIRECTORY_SEPARATOR), array('..', '.'));
		foreach ($langdir as $key => $value){
			$curfile = LANGUAGE_PATH . DIRECTORY_SEPARATOR . $value;
			if (is_file($curfile) && preg_match('/(.+)\.cfg$/', $value, $matches)){
				$lang = $matches[1];
				$file = fopen($curfile,'r');
				while (!feof($file)) {
					$line = trim(fgets($file));
					$fields = explode(LANGUAGE_SEPARATOR,$line);
					$languages[$lang][$fields[0]] = $fields[1];
				}
				fclose($file);				
			}
		}
	}
	
	function checkSandbox($path){
		global $config;
		if (($realpath = realpath($path)) === FALSE) die(_t('SandBoxFS'));
		if (($pos = strpos($realpath,$config['reportpath'])) === FALSE) die(_t('SandBoxFS'));
		if ($pos) die(_t('SandBoxFS'));
		return;
	}
	
	#-----------------------------------------------------------------------------------
	# Reports
	#-----------------------------------------------------------------------------------
	
	function indexPage(){
		global $config,$pyear,$pmonth,$pday;

		# Selector de años y meses
		
		$years = array();
		
		$reportdir = array_diff(scandir($config['reportpath'],SCANDIR_SORT_DESCENDING), array('..', '.'));
		foreach ($reportdir as $key => $value){
			$curdir = $config['reportpath'] . DIRECTORY_SEPARATOR . $value;
			if (is_dir($curdir)){
				preg_match('/(\d+)\-(\d+)\-(\d+)/', $value, $matches);
				$year = $matches[1];
				$month = $matches[2];
				$day = $matches[3];
				$years[$year][$month] = 1;
			}
		}
		ksort($years);
		
		echo "<div class='w3-card w3-section w3-bar w3-border' style='width:auto'><span class='w3-bar-item w3-black w3-small'>"._t('Year')."</span>";
		foreach ($years as $key => $value){
			echo "<a href='?year=$key&month=all' class='w3-bar-item w3-button w3-small ".(($key==$pyear)?"w3-blue":"")."'>$key</a>";
		}
		echo "</div>";
		
		echo "<div class='w3-card w3-section w3-bar w3-border'><span class='w3-bar-item w3-black w3-small'>"._t('Month')."</span>";
		for($i=1;$i<=12;$i++){
			$iFormat=($i<10)?"0$i":$i;
			if (isset($years[$pyear][$iFormat]))
				echo "<a href='?year=$pyear&month=$iFormat' class='w3-bar-item w3-button w3-small ".(($iFormat==$pmonth)?"w3-blue":"")."'>$iFormat</a>";
			else
				echo "<span class='w3-bar-item w3-small w3-disabled'>$iFormat</span>";
		}
		echo "</div>";
		
		# Listado del año y mes seleccionado
		
		$regExpDate = "/^$pyear\-".(($pmonth=='all')?'':$pmonth)."/";
		echo "<h2>"._t('Period').": $pyear-$pmonth</h2>";
		$reportdir = array_diff(scandir($config['reportpath'],SCANDIR_SORT_DESCENDING), array('..', '.'));
		echo "<table class='w3-card w3-section w3-table-all'>";
		echo "<thead><tr><th>"._t('Period')."</th><th>"._t('Users')."</th><th>"._t('Hits')."</th></tr></thead><tbody>";
		foreach ($reportdir as $key => $value){
			$curdir = $config['reportpath'] . DIRECTORY_SEPARATOR . $value;
			if (is_dir($curdir)){
				if (preg_match($regExpDate,$value)){
					$total = fopen("$curdir/.total", "r");
					$line = fgets($total);
					$matches = explode(' ',$line);
					$user = $matches[1];
					$line = fgets($total);
					$matches = explode(' ',$line);
					$hits = $matches[1];
					preg_match('/(\d+)\-(\d+)\-(\d+)/', $value, $matches);
					$year = $matches[1];
					$month = $matches[2];
					$day = $matches[3];
					echo "<tr><td><a href='?year=$year&month=$month&day=$day&report=date'>$value</a></td><td>$user</td><td>$hits</td></tr>";
					fclose($total);
				}
			}
		}
		echo "</tbody></table>";
	}

	function datePage(){
		global $config,$pyear,$pmonth,$pday;
		
		showPeriod();
		echo "<table class='w3-card w3-section w3-table-all'>";
		echo "<thead><tr><th>"._t('Reports')."</th></tr></thead><tbody>";
		echo "<tr><td><i class='fas fa-filter fa-fw'></i> <a href='?year=$pyear&month=$pmonth&day=$pday&report=destination'>"._t('TopClassifications')."</a></td></tr>";
		echo "<tr><td><i class='fas fa-globe fa-fw'></i> <a href='?year=$pyear&month=$pmonth&day=$pday&report=topsites'>"._t('TopSites')."</a></td></tr>";
		echo "</tbody></table>";
		echo "<table class='ww3-card w3-section w3-table-all'>";
		echo "<thead><tr><th class='w3-exact'>#</th><th class='w3-exact'>"._t('Hour')."</th><th>"._t('User')."</th><th>"._t('Hits')."</th></tr></thead><tbody>";
		$curdir = $config['reportpath'] . DIRECTORY_SEPARATOR . "$pyear-$pmonth-$pday";
		checkSandbox($curdir);
		if (file_exists("$curdir/.total")){
			$total = fopen("$curdir/.total", "r");
			$line = fgets($total);
			$line = fgets($total);
			$linecnt=1;
			while($line=fgets($total)) {
				$matches = explode(' ',$line);
				$user=$matches[0];
				$hits=$matches[1];
				echo "<tr><td class='w3-exact'>$linecnt</td><td class='w3-exact'><a href='?year=$pyear&month=$pmonth&day=$pday&user=$user&report=userhour'><i class='fas fa-clock fa-fw'></i></a></td><td><a href='?year=$pyear&month=$pmonth&day=$pday&user=$user&report=user'>$user</a></td><td>$hits</td></tr>";
				$linecnt++;
			}
			fclose($total);
		}
		echo "</tbody></table>";
	}

	function userhourPage(){
		global $config,$pyear,$pmonth,$pday;
		
		$user = $_GET['user'];

		showPeriod();
		echo "<h3>"._t('User').": <a href='?year=$pyear&month=$pmonth&day=$pday&site=$sitekey&user=$user&report=user'>$user</a></h3>";
		echo "<h4>"._t('Report').": "._t('HoursReport')."</h4>";
		echo "<table class='w3-card w3-section w3-table-all w3-responsive'>";
		echo "<thead><tr><th class='w3-exact'>#</th><th>"._t('AccessedSites')."</th><th>"._t('Classification')."</th><th>"._t('Hits')."</th>";
		for($i=0;$i<=23;$i++){ echo "<th>".(($i<10)?"0$i":$i)."</th>"; }
		echo "</tr></thead><tbody>";
		$curdir = $config['reportpath'] . DIRECTORY_SEPARATOR . "$pyear-$pmonth-$pday";
		checkSandbox($curdir);
		if (file_exists("$curdir/$user")){
			$total = fopen("$curdir/$user", "r");
			$linecnt=1;
			while($line=fgets($total)) {
				$matches = explode(' ',$line);
				$site=$matches[0];
				$destination=$matches[1];
				$hits=$matches[2];
				echo "<tr><td class='w3-exact'>$linecnt</td><td class='w3-no-wrap'><a target='_blank' href='http://$site'>$site</a></td><td class='w3-no-wrap'>$destination</td><td>$hits</td>";
				for($i=3;$i<27;$i++){ echo "<td>".(($matches[$i]!=0)?$matches[$i]:'-')."</td>"; }
				echo "</tr>";
				$linecnt++;
			}
			fclose($total);
		}
		echo "</tbody></table>";
	}

	function userPage(){
		global $config,$pyear,$pmonth,$pday;
		
		$user = $_GET['user'];

		showPeriod();
		echo "<h3>Usuario: $user</h3>";
		echo "<table class='w3-card w3-section w3-table-all'>";
		echo "<thead><tr><th>"._t('Reports')."</th></tr></thead><tbody>";
		echo "<tr><td><i class='fas fa-clock fa-fw'></i> <a href='?year=$pyear&month=$pmonth&day=$pday&user=$user&report=userhour'>"._t('HoursReport')."</a></td></tr>";
		echo "</tbody></table>";
		echo "<table class='w3-card w3-section w3-table-all'>";
		echo "<thead><tr><th class='w3-exact'>#</th><th>"._t('AccessedSites')."</th><th>"._t('Classification')."</th><th>"._t('Hits')."</th></tr></thead><tbody>";

		$curdir = $config['reportpath'] . DIRECTORY_SEPARATOR . "$pyear-$pmonth-$pday";
		checkSandbox($curdir);
		if (file_exists("$curdir/$user")){
			$total = fopen("$curdir/$user", "r");
			$linecnt=1;
			while($line=fgets($total)) {
				$matches = explode(' ',$line);
				$site=$matches[0];
				$destination=$matches[1];
				$hits=$matches[2];
				echo "<tr><td class='w3-exact'>$linecnt</td><td><a target='_blank' href='http://$site'>$site</a></td><td>$destination</td><td>$hits</td></tr>";
				$linecnt++;
			}
			fclose($total);
		}
		echo "</tbody></table>";
	}
	
	function topsitesPage(){
		global $config,$pyear,$pmonth,$pday;
		$topsites = array();
		$alldestination = array();
		$allhits = array();
		
		showPeriod();
		echo "<h3>"._t('Report').": "._t('TopSites')."</h3>";
		echo "<table class='w3-card w3-section w3-table-all'>";
		echo "<thead><tr><th class='w3-exact'>#</th><th class='w3-exact'>"._t('Users')."</th><th>"._t('AccessedSites')."</th><th>"._t('Classification')."</th><th>"._t('Hits')."</th></tr></thead><tbody>";

		$curdir = $config['reportpath'] . DIRECTORY_SEPARATOR . "$pyear-$pmonth-$pday";
		checkSandbox($curdir);
		$reportdir = preg_grep('/^([^.])/', scandir($curdir,SCANDIR_SORT_DESCENDING));

		foreach ($reportdir as $key => $value){
			$curfile = $curdir . DIRECTORY_SEPARATOR . $value;
			if (is_file($curfile)){
				$total = fopen($curfile, "r");
				while($line=fgets($total)) {
					$matches = explode(' ',$line);
					$site=$matches[0];
					$destination=$matches[1];
					$hits=$matches[2];
					$topsites[$site][$destination]+=$hits;
					$alldestination[$site] = $destination;
					$allhits[$site] += $hits;
				}
				fclose($total);
			}
		}
		
		array_multisort($allhits, SORT_DESC, $alldestination, SORT_ASC, $topsites);
		
		$linecnt=1;
		foreach($topsites as $sitekey => $sitevalue){
			foreach($sitevalue as $destinationkey => $destinationvalue){
				echo "<tr><td class='w3-exact'>$linecnt</td><td><a href='?year=$pyear&month=$pmonth&day=$pday&site=$sitekey&destination=$destinationkey&report=topsitesusers'><i class='fas fa-users fa-fw'></i></a></td><a href='?year=$pyear&month=$pmonth&day=$pday&user=$user&report=userhour'><td><a target='_blank' href='http://$sitekey'>$sitekey</a></td><td>$destinationkey</td><td>$destinationvalue</td></tr>";
				$linecnt++;
			}
		}

		echo "</tbody></table>";
	}
	
	function topsitesusersPage(){
		global $config,$pyear,$pmonth,$pday;
		$users = array();
		
		$psite = $_GET['site'];
		$pdestination = $_GET['destination'];
		
		showPeriod();
		echo "<h3>"._t('Site').": $psite ($pdestination)</h3>";
		echo "<h4>"._t('Report').": "._t('Users')."</h4>";
		echo "<table class='w3-card w3-section w3-table-all'>";
		echo "<thead><tr><th class='w3-exact'>#</th><th class='w3-exact'>"._t('Hour')."</th><th>"._t('User')."</th><th>"._t('Hits')."</th></tr></thead><tbody>";

		$curdir = $config['reportpath'] . DIRECTORY_SEPARATOR . "$pyear-$pmonth-$pday";
		checkSandbox($curdir);
		$reportdir = preg_grep('/^([^.])/', scandir($curdir,SCANDIR_SORT_DESCENDING));

		foreach ($reportdir as $key => $value){
			$curfile = $curdir . DIRECTORY_SEPARATOR . $value;
			if (is_file($curfile)){
				$total = fopen($curfile, "r");
				while($line=fgets($total)) {
					$matches = explode(' ',$line);
					$site=$matches[0];
					$destination=$matches[1];
					$hits=$matches[2];
					if (($site == $psite) && ($destination == $pdestination)){ $users[$value]+=$hits; }
				}
				fclose($total);
			}
		}
		
		arsort($users);
		
		$linecnt=1;
		foreach($users as $key => $value){
			echo "<tr><td class='w3-exact'>$linecnt</td><td class='w3-exact'><a href='?year=$pyear&month=$pmonth&day=$pday&user=$key&report=userhour'><i class='fas fa-clock fa-fw'></i></a></td><td><a href='?year=$pyear&month=$pmonth&day=$pday&user=$key&report=user'>$key</a></td><td>$value</td></tr>";
			$linecnt++;
		}

		echo "</tbody></table>";
	}

	function destinationPage(){
		global $config,$pyear,$pmonth,$pday;

		showPeriod();
		echo "<h3>"._t('Report').": "._t('TopClassifications')."</h3>";
		echo "<table class='w3-card w3-section w3-table-all'>";
		echo "<thead><tr><th class='w3-exact'>#</th><th class='w3-exact'>"._t('Users')."</th><th>"._t('Sites')."</th><th>"._t('Classification')."</th><th>"._t('Hits')."</th></tr></thead><tbody>";
		$curdir = $config['reportpath'] . DIRECTORY_SEPARATOR . "$pyear-$pmonth-$pday";
		checkSandbox($curdir);
		if (file_exists("$curdir/.dest")){
			$total = fopen("$curdir/.dest", "r");
			$linecnt=1;
			while($line=fgets($total)) {
				$matches = explode(' ',$line);
				$target=$matches[0];
				$hits=$matches[1];
				echo "<tr><td class='w3-exact'>$linecnt</td><td class='w3-exact'><a href='?year=$pyear&month=$pmonth&day=$pday&destination=$target&report=destinationusers'><i class='fas fa-users fa-fw'></i></a><td class='w3-exact'><a href='?year=$pyear&month=$pmonth&day=$pday&destination=$target&report=destinationsites'><i class='fas fa-globe fa-fw'></i></a></td></td><td>$target</td><td>$hits</td></tr>";
				$linecnt++;
			}
			fclose($total);
		}
		echo "</tbody></table>";
	}

	function destinationusersPage(){
		global $config,$pyear,$pmonth,$pday;
		$users = array();
		
		$pdestination = $_GET['destination'];
		
		showPeriod();
		echo "<h3>"._t('Classification').": $pdestination</h3>";
		echo "<h4>"._t('Report').": "._t('Users')."</h4>";
		echo "<table class='w3-card w3-section w3-table-all'>";
		echo "<thead><tr><th class='w3-exact'>#</th><th class='w3-exact'>"._t('Users')."</th><th>"._t('Classification')."</th><th>"._t('Hits')."</th></tr></thead><tbody>";

		$curdir = $config['reportpath'] . DIRECTORY_SEPARATOR . "$pyear-$pmonth-$pday";
		checkSandbox($curdir);
		$reportdir = array_diff(scandir($curdir,SCANDIR_SORT_DESCENDING), array('..', '.'));
		
		foreach ($reportdir as $key => $value){
			$curfile = $curdir . DIRECTORY_SEPARATOR . $value;
			if (is_file($curfile)){
				$total = fopen($curfile, "r");
				while($line=fgets($total)) {
					$matches = explode(' ',$line);
					$site=$matches[0];
					$destination=$matches[1];
					$hits=$matches[2];
					if ($destination == $pdestination){ $users[$value]+=$hits; }
				}
				fclose($total);
			}
		}
		
		arsort($users);
		
		$linecnt=1;
		foreach($users as $key => $value){
			echo "<tr><td class='w3-exact'>$linecnt</td><td class='w3-exact'><a href='?year=$pyear&month=$pmonth&day=$pday&user=$key&report=userhour'><i class='fas fa-clock fa-fw'></i></a></td><td><a href='?year=$pyear&month=$pmonth&day=$pday&user=$key&report=user'>$key</a></td><td>$value</td></tr>";
			$linecnt++;
		}

		echo "</tbody></table>";
	}
	
	function destinationsitesPage(){
		global $config,$pyear,$pmonth,$pday;
		$sites = array();
		
		$pdestination = $_GET['destination'];
		
		showPeriod();
		echo "<h3>"._t('Classification').": $pdestination</h3>";
		echo "<h4>"._t('Report').": "._t('Sites')."</h4>";
		echo "<table class='w3-card w3-section w3-table-all'>";
		echo "<thead><tr><th class='w3-exact'>#</th><th class='w3-exact'>"._t('Users')."</th><th>"._t('AccessedSites')."</th><th>"._t('Hits')."</th></tr></thead><tbody>";

		$curdir = $config['reportpath'] . DIRECTORY_SEPARATOR . "$pyear-$pmonth-$pday";
		checkSandbox($curdir);
		$reportdir = array_diff(scandir($curdir,SCANDIR_SORT_DESCENDING), array('..', '.'));

		foreach ($reportdir as $key => $value){
			$curfile = $curdir . DIRECTORY_SEPARATOR . $value;
			if (is_file($curfile)){
				$total = fopen($curfile, "r");
				while($line=fgets($total)) {
					$matches = explode(' ',$line);
					$site=$matches[0];
					$destination=$matches[1];
					$hits=$matches[2];
					if ($destination == $pdestination){ $sites["$site|$destination"]+=$hits; }
				}
				fclose($total);
			}
		}
		
		arsort($sites);
		
		$linecnt=1;
		foreach($sites as $key => $value){
			$key=explode("|",$key);
			$site=$key[0];
			$destination=$key[1];
			echo "<tr><td class='w3-exact'>$linecnt</td><td class='w3-exact'><a href='?year=$pyear&month=$pmonth&day=$pday&site=$site&destination=$destination&report=topsitesusers'><i class='fas fa-users fa-fw'></i></a></td><td class='w3-no-wrap'><a target='_blank' href='http://$site'>$site</a></td><td>$value</td></tr>";
			$linecnt++;
		}

		echo "</tbody></table>";
	}
	
	function unknownPage(){
		showPeriod();
		echo "<div class='w3-panel w3-yellow w3-leftbar w3-border-amber'><h3>"._t('Warning')."</h3><p>"._t('UnknownReport')."</p></div>";
	}
	
	function showPeriod(){
		global $pyear,$pmonth,$pday;
		echo "<h2><a class='w3-text-blue' href='?'><i class='fas fa-home fa-fw'></i></a> "._t('Period').": <a href='?year=$pyear&month=$pmonth&day=$pday&site=$sitekey&user=$user&report=date'>$pyear-$pmonth-$pday</a></h2>";
	}
?>
<!DOCTYPE html>
<html lang="es">
<title>squidGuard Reports v<?php echo APP_VERSION; ?></title>

<!-- META data -->
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">
<meta name="lang" content="es" />
<meta name="keywords" content="squid, squidGuard, reports, rrc2software" />
<meta name="description" content="rrc2software @ squidGuard Reports es una herramienta para procesar y visualizar los archivos de registro generados por squidGuard." />

<!-- META OG data -->
<meta property="og:title" content="squidGuard Reports v<?php echo APP_VERSION; ?>" />
<meta property="og:type" content="website" />
<meta property="og:url" content="<?php echo url_getSiteUrl(); ?>" />
<meta property="og:image" content="<?php echo url_getSiteUrl(); ?>images/logo-og.png" />
<meta property="og:site_name" content="squidGuard Reports" />
<meta property="og:description" content="rrc2software @ squidGuard Reports es una herramienta para procesar y visualizar los archivos de registro generados por squidGuard." />
    
<!-- CSS styles -->
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
<link rel="stylesheet" href="css/theme.css">
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
<body>

<!-- Top -->
<div id="header" class="w3-border-bottom">
	<div class="w3-content w3-row w3-padding-large">
		<div class="w3-threequarter"><h1>squidGuard Reports <span class="w3-opacity w3-small">by <a href="http://www.rrc2software.com">rrc2software</a></span></h1></div>
		<div class="w3-quarter w3-right-align w3-hide-small"><a href="?"><img style="color:white" src="images/logo.svg" alt="squidGuard logo"/></a></div>
	</div>
</div>

<!-- Page content -->
<div id="main">
	<div class="w3-content w3-padding-large">
<?php
$pyear  = isset($_GET['year'])?$_GET['year']:date("Y");
$pmonth = isset($_GET['month'])?$_GET['month']:date("m");
$pday   = isset($_GET['day'])?$_GET['day']:date("d");

loadLanguages();

if (isset($_GET['report'])){
	if ($_GET['report'] == 'user'){ userPage(); }
	elseif ($_GET['report'] == 'userhour'){ userhourPage(); }
	elseif ($_GET['report'] == 'destination') { destinationPage(); }
	elseif ($_GET['report'] == 'destinationusers') { destinationusersPage(); }
	elseif ($_GET['report'] == 'date') { datePage(); }
	elseif ($_GET['report'] == 'topsites') { topsitesPage(); }
	elseif ($_GET['report'] == 'topsitesusers') { topsitesusersPage(); }
	elseif ($_GET['report'] == 'destinationsites') { destinationsitesPage(); }
	else { unknownPage(); }
}
else {
	indexPage();
}

?>
<!-- End Page Content -->
	</div>
</div>

<!-- Footer -->
<footer id="footer" class="w3-border-top">
	<div class="w3-content w3-row-padding w3-padding-large">
		<div class="w3-col l2 m4 s12">
			<img class="w3-opacity" src="images/rrc2software.png" alt="rrc2software logo"/>
		</div>
		<div class="w3-col l5 m4 s12">
			<p>
				<strong><?php echo _t('Version'); ?></strong><br/>
				rrc2software @ squidGuard Reports<br/>
				v<?php echo APP_VERSION; ?>
			</p>
			<p>
				<strong><?php echo _t('MoreInfo'); ?></strong><br/>
				<?php printf(_t('MoreInfoDetails'),"<a href='http://www.rrc2software.com'>www.rrc2software.com</a>"); ?>
			</p>
		</div>
		<div class="w3-col l5 m4 s12">
			<p>
				<a href="?<?php echo _q(['lang'=>'es-es'])?>"><img src="images/es-es.png" title="<?php echo _t('Spanish'); ?>" alt="<?php echo _t('Spanish'); ?>"/></a>
				<a href="?<?php echo _q(['lang'=>'en-uk'])?>"><img src="images/en-uk.png" title="<?php echo _t('English'); ?>" alt="<?php echo _t('English'); ?>"/></a>
				<a href="?<?php echo _q(['lang'=>'fr-fr'])?>"><img src="images/fr-fr.png" title="<?php echo _t('French'); ?>" alt="<?php echo _t('French'); ?>"/></a>
			</p>
			<p><?php echo _t('PoweredBy'); ?></p>
		</div>
	</div>
</footer>
</body>
</html>