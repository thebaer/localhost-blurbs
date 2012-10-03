<!--
   Copyright 2012 Matt Baer

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
-->
<html>
<head>

	<?php
	include_once "Blurbs.class.php";
	
	// BEGIN CONFIGURATION //////////////////////
	$statusfile = "status.txt";
	$sitesfile = "sites.txt";
	
	define('DISPLAY_LIMIT', 35);
	// END CONFIGURATION ////////////////////////
	
	$b = new Blurbs($statusfile);
	
	if ($_POST['save'] || isset($_GET['text'])) {
		$statuses = file($statusfile);
		
		$b->add($_REQUEST['text']);
		
		if (isset($_GET['text'])) {
			header("Location: /");
			exit;
		}
	} else if ($_POST['newsite']) {
		$statuses = file($sitesfile);
		
		$fp = fopen($sitesfile, "a");
		if ($fp) {
			fwrite($fp, '<a href="'.$_POST['url'].'">'.$_POST['name'].'</a>'."\r\n");
			fclose($fp);
		} else {
			echo "Could not write file.";
		}
	}
	
	$statuses = $b->getBlurbs('desc'); //file($statusfile);
	
	if (isset($_GET['all']) || sizeof($statuses) < DISPLAY_LIMIT)
		$showing = sizeof($statuses);
	else
		$showing = DISPLAY_LIMIT;
		
		//print_r($b->getCategories());
	
	$hostname = file_get_contents("/etc/hostname");
	if (!$hostname)
		$hostname = gethostname();  // Requires PHP 5.3
	if (!$hostname)
		$hostname = php_uname('n'); // Before PHP 5.3
	?>
	
	<title><?php echo $hostname; ?></title>
	
	<link type="text/css" rel="stylesheet" href="/main.css" />
	<style>
	h2 a {
		font-weight: normal;
	}
	</style>
	
</head>
<body>

	<h1>Matt's Computer (<?php echo trim($hostname); ?>)</h1>
	<h2><a href="/">Home</a> | <a href="/?cat=sticky">Sticky Notes</a></h2>
	
	<div style="float: left; width: 60%;">
		<form method="post">
			<input type="text" name="text" size="40" />
			<input type="submit" name="save" value="Save" /> <a style="font-size:10pt" href="?<?= isset($_GET['all']) ? '">View Less' : 'all">View All' ?></a><br />

			<span>Displaying <strong><?php echo $showing.'</strong> of '.sizeof($statuses).' blurb'.(sizeof($statuses)!=1?'s':'') ?></span>
		</form>
		<p style="color:grey"><?php
		if (isset($_GET['all']) || sizeof($statuses) < DISPLAY_LIMIT) {
			foreach ($statuses as $status) {
				echo $status->toString()."<br />\n";
			}
		} else {
			for ($i=0; $i<DISPLAY_LIMIT; $i++) {
				echo $statuses[$i]->toString()."<br />\n";
			}
		}
		?></p>
	</div>
	
	<div style="float: left; margin-left: 2em; width: 35%"><h2>Development Sites</h2>
		<ul class="sites">
		<?php
		$sites = file($sitesfile);
		if (sizeof($sites) > 0) {
			foreach ($sites as $site) {
				echo "<li>$site</li>";
			}
		}
		?>
		</ul>
		<form method="post">
			<input type="text" name="url" size="20" value="http://" /> <input type="text" name="name" size="20" />
			<input type="submit" name="newsite" value="Add" />
		</form>
		
		<form method="post" style="margin-top: 8px">
			<textarea name="phpcode" rows="4" cols="48"><?= $_POST['phpcode'] ?></textarea><br />
			<input type="submit" name="eval" value="Run" />
		</form>
		<?php if ($_POST['eval']) { ?>
		<div style="background: #eee; border: 1px solid #ccc; padding: 3px; width: 500px;">
			<?= eval($_POST['phpcode']) ?>
		</div>
		<? } ?>

	</div>
	
</body>
</html>
