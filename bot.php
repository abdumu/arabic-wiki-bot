#!/usr/bin/env php
<?php
/**
 * Arabic-Wiki-Bot
 * 2017 by Abdulrahman Alshuwayi
 *
 *
 * Built using GPL-Y stuff in 2017
 */


set_time_limit(0);


if (!file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php') ) {
	die("The configuration file '".dirname(__FILE__) . DIRECTORY_SEPARATOR."config.php' doesn't exist. The bot cannot proceed its work.\n");
}

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';


/**
 * extra functions & classes
 */
function timer()
{
	static $microtime_start = null;
	if($microtime_start === null)
	{
	 	$microtime_start = microtime(true);
	 	return 0.0;
	}
	return microtime(true) - $microtime_start;
}

class wmflDB{

	private $reachable = false;
	private $link = null;

	function __construct(){
		if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../replica.my.cnf')){
			$confInfo = parse_ini_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../replica.my.cnf');
			if(!empty($confInfo['user']) && !empty($confInfo['password'])){

				$dbname = 'arwiki_p';
				if(substr($dbname, -2) == '_p')
					$dbname = substr($dbname, 0, -2);

				if($dbname == 'meta')
					$host = 'enwiki.labsdb';
				else
					$host = $dbname . ".labsdb";

				$this->link = mysqli_connect($host, trim($confInfo['user']), trim($confInfo['password']), $dbname . '_p');
				if (!$this->link) {
					echo "Failed to connect to MySQL: (" . mysqli_connect_errno() . ") " . mysqli_connect_error() . "\n";
				}else{
					mysqli_set_charset($this->link, 'utf8mb4');
					$this->reachable = true;
				}
			}
		}
	}

	function reachable(){
		return $this->reachable;
	}

	function getResults($sql){
		if($this->reachable && $this->link){
			$res = mysqli_query($this->link, $sql);
			if($res){
				return mysqli_fetch_all($res);
			}
		}else{
			return null;
		}
	}
	function __destruct(){
		if($this->reachable && $this->link){
			mysqli_close($this->link);
		}
	}
}


#no queue file? create one
if(!file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file'])){
	@touch(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file']);
}


#TODO to be romoved once peachy's team fixes it
if(!defined('CURLCLOSEPOLICY_LEAST_RECENTLY_USED')){
	define('CURLCLOSEPOLICY_LEAST_RECENTLY_USED', 2); #fix bug in peachy
	define('CURLOPT_CLOSEPOLICY', 100001); #fix bug in peachy
}


/**
 * CLI
 * QUARRY QUEUE
 * if there is new query, get it and add the pages to the queue, skip in case of big queue file
 */
$cli_options = getopt("i::j::t:v:hfr");

if(sizeof($cli_options)):

	function xecho($msg){ print $msg . "\n";}

	if(isset($cli_options['h'])){
		xecho('	[-i] number of a query from quarry.wmflabs.org. note that we use production mediawiki database to execute the query and get lastest results. (for example -i=120)');
		xecho('	[-t] title of a page . (for example -t=title_of_page)');
		xecho('	[-t] values to pass to the job . (for example -v="value1||value2||value3")');
		xecho('	[-j] job,job,job where job is any job in jobs folder. (for example -j=clean,arabize)');
		xecho('	[-f] to foce adding a query that was added before. (for example -f)');
		xecho('	[-r] reset processes & empty queue (for example -r)');
		xecho('	[-h] to show this list. (for example -h)');
		exit;
	}

	$enable_title_mode = false;

	if(isset($cli_options['r'])){
		file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file'], "");
		file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['daily_status_file'], "");
		file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['queries_status_file'], "");
		exit(xecho('Queue is empty now.'));
	}



	if(!empty($cli_options['t'])){
		$enable_title_mode = true;
	}

	if(empty($cli_options['i']) && !$enable_title_mode){
		exit(xecho($cli_options['i'].'ID [-i option] is not specified! this is required! (-h for help)'));
	}

	if(empty($cli_options['j'])){
		exit(xecho('Jobs [-j option] is not specified! this is required! (-h for help)'));
	}

	if(!empty($cli_options['i']) && intval($cli_options['i']) == 0 && !$enable_title_mode){
		exit(xecho('-i option is not a number! this is the id of a query from quarry.wmflabs.org! include -h for help.', PECHO_NOTICE));
	}

	$current_jobs = array_map('trim', explode(',', $cli_options['j']));
	$current_qid = !$enable_title_mode ? trim($cli_options['i']) : 0;

	foreach($current_jobs as $j){
		if(!file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'jobs' . DIRECTORY_SEPARATOR . $j.'.php')){
			exit(xecho($j.' is not valid job.'));
		}
	}


 if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file']) && filesize(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file']) < 100000 && !$enable_title_mode){
	$queue_status = array();
	if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['queries_status_file'])){
		$queue_status = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['queries_status_file']);
		$queue_status = array_map('trim', explode("\n", $queue_status));
	}

	#if not done before
	$force_added = false;
	if(in_array($current_qid, $queue_status)){
		if(!isset($cli_options['f'])){
			exit(xecho('['.$current_qid.'] was added before! to force adding this again, use -f option.'));
		}else{
			$force_added = true;
		}
	}

	$new_lines_for_queue = array();
	if($current_qid > 0 && is_array($current_jobs)){
		require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'peachy/Includes/Hooks.php';
		require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'peachy/HTTP.php';
		$HTTP = new HTTP();
		$HTTP->setUserAgent('Mozilla/5.0 (Android 4.4; Mobile; rv:41.0) Gecko/41.0 Firefox/41.0');


		if(!$force_added){
			file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['queries_status_file'], "\n" . $current_qid, FILE_APPEND | LOCK_EX);
		}
		$qcontent = $HTTP->get(sprintf('https://quarry.wmflabs.org/query/%d', $current_qid));
		$qhttp_code = $HTTP->get_HTTP_code();


		if($qcontent && $qhttp_code == 200){
			#get run id
			// <textarea id="code">xxxxsql</textarea>

			preg_match('/<textarea\h+id="code"[^>]*>(.*?)<\/textarea>/s', $qcontent, $matches);
			$sql = trim(preg_replace('!USE [A-Za-z_]+\h*;\h*!', '', $matches[1]));


			if(!empty($sql)){
				$wmflDB = new wmflDB();

				if($wmflDB->reachable()){
					#if no limit, make it 1000
					if(strpos($sql, 'LIMIT') === false){
						$sql = rtrim(trim($sql), ';') . ' LIMIT 1000;';
					}

					xecho('[Executed SQL] ' . $sql);
					timer();
					if($results = $wmflDB->getResults($sql)){
						foreach($results as $row){
							array_push($new_lines_for_queue, trim(implode("\t", $row)) . $_info['sep'] .  implode(',', $current_jobs));
						}
					}
					xecho('[Execution time] ' . timer());
				}#reachable
				else{
					exit(xecho('Can not connect to database!'));
				}
			}else{
				exit(xecho('empty SQL!'));
			}
		}else{
			exit(xecho('Not valid response! http code:' . $qhttp_code));
		}


		if(sizeof($new_lines_for_queue)){
			file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file'], implode("\n", $new_lines_for_queue), FILE_APPEND | LOCK_EX);
			exit(xecho(sprintf('added (%d) new articles for queue from given query.', sizeof($new_lines_for_queue))));
		}
	}
}else{
	if(!$enable_title_mode){
		exit(xecho('Can not add more to the large queue, skipped for now.'));
	}
}

if(!$enable_title_mode){
	exit; //only cli
}else{
	$_info['testing_mode'] = true;
	$vals = '';
	if(!empty($cli_options['v'])){
		$vals = "\t".implode("\t", explode('||', $cli_options['v']));
	}
	$_info['testing_mode_queue'] = array($cli_options['t'].$vals.'::--sep--::' . implode(',', $current_jobs));
}

endif; #sizeof cli_options


#Initiate Peachy
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'peachy/Init.php';
$WIKI = Peachy::newWiki('redirector');
$JOBS_NAMES = array();

pecho('Current username: ' . $WIKI->get_username(). "\n", PECHO_NOTICE);

function getReadOnlyWiki($lang = 'en'){
	return Peachy::newWiki(null, null, null, 'https://'.$lang.'.wikipedia.org/w/api.php');
}

/**
 * DAILY QUEUE
 * check the daily status
 */

 #if not done before, also if we have too big queue file, then ignore it for now

 if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file']) && filesize(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file']) < 100000 && !$_info['testing_mode']){

	 $daily_status = array();
	 if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['daily_status_file'])){
	   $daily_status_t= file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['daily_status_file']);
	   $daily_status_x = array_filter(array_map('trim', explode("\n", $daily_status_t)));

	   foreach($daily_status_x as $dd){
		 if(strpos($dd, ':') !== false){
			 list($dn, $dl) = explode(':', $dd);
			 $daily_status[$dn] = $dl;
		 }
	   }
	 }

	function parse_scheduled_time($jtime){
		return intval(preg_replace_callback('!^(\d)\h*(\w)$!', function($m){
			$x = array('m' =>60, 'h' =>3600, 'd'=>(3600 * 24));
			return intval($m[1]) * (isset($x[trim($m[2])]) ? $x[trim($m[2])] : 0);
		}, $jtime));
	}

	 $wmflDB = null;
	 foreach($_info['schedule'] as $scheduled_time => $scheduled_jobs){

	 if(!($xxtime = parse_scheduled_time($scheduled_time))){
 		pecho($scheduled_time . " is not valid scheduled time format.\n", PECHO_NOTICE);
 		continue;
 	}

	pecho("[".$scheduled_time."] jobs is being processed.\n", PECHO_NOTICE);

	 foreach($scheduled_jobs as $dname=>$djobs){
	 	if(empty($daily_status[$dname]) || ($daily_status[$dname] > 0 && time() - $daily_status[$dname] > $xxtime)){

			$new_lines_for_queue = array();

			#begin recent
			if($dname == 'recent'){
				$timestamp = $daily_status[$dname] > 0 ? $daily_status[$dname] : time() - 3600*24;
				$new_pages = $WIKI->recentchanges(/*namespace=*/0, /*pgTag=*/null, /*start=*/$timestamp, /*end=*/null, /*user=*/null, /*excludeuser=*/$WIKI->get_username(), /*dir=*/'older', /*minor=*/null, /*bot=*/null, /*anon=*/null, /*redirect=*/false, /*patrolled=*/null, /*prop=*/null, /*limit*/250);
				if(sizeof($new_pages)){
					foreach($new_pages as $row){
						//ignore simple edits
						if($row['newlen'] - $row['oldlen'] > 50 && trim($row['title']) != ''){
							array_push($new_lines_for_queue, trim(str_replace(' ', '_', $row['title'])) . $_info['sep'] . implode(',', $djobs));
						}
					}
				}
			}
			#end recent

			#begin fix_images
			if($dname =='fix_images'){
				if(!$wmflDB){
					$wmflDB = new wmflDB();
				}

				if($wmflDB->reachable()){

					$sql = 'SELECT page_title, il_to
							FROM page
							JOIN imagelinks ON page_id = il_from
							WHERE (NOT EXISTS(SELECT 1 FROM image WHERE img_name = il_to))
							AND (NOT EXISTS(SELECT 1 FROM commonswiki_p.page WHERE page_title = il_to AND page_namespace = 6))
							AND (NOT EXISTS(SELECT 1 FROM page WHERE page_title = il_to AND page_namespace = 6))
							AND page_namespace = 0
							LIMIT 300;'; //limit daily to fix

					pecho('[Executing SQL] ' . $dname. " ...\n", PECHO_NOTICE);
					timer();
					if($results = $wmflDB->getResults($sql)){
						foreach($results as $row){
							array_push($new_lines_for_queue, trim(implode("\t", $row)) . $_info['sep'] .  implode(',', $djobs));
						}
					}
					pecho('[Executing time] ' . timer(). " seconds \n", PECHO_NOTICE);
				}#reachable
			}
			#end fix_images


			#begin remove_orphan_templates
			if($dname =='remove_orphan_templates'){
				if(!$wmflDB){
					$wmflDB = new wmflDB();
				}

				if($wmflDB->reachable()){

					$sql = 'SELECT p.page_title
							FROM categorylinks c
							LEFT JOIN page p ON p.page_id = c.cl_from
							LEFT JOIN pagelinks pl ON p.page_title = pl.pl_title AND p.page_namespace = pl.pl_namespace
							LEFT JOIN page p2 ON p2.page_id = pl.pl_from AND p2.page_is_redirect=0  AND p2.page_namespace = p.page_namespace
							WHERE c.cl_to = "جميع_المقالات_اليتيمة"
							GROUP BY p.page_title
							having COUNT(p2.page_id)>2
							LIMIT 5;'; //limit daily to fix

					pecho('[Executing SQL] ' . $dname. " ...\n", PECHO_NOTICE);
					timer();
					if($results = $wmflDB->getResults($sql)){
						foreach($results as $row){
							array_push($new_lines_for_queue, trim(implode("\t", $row)) . $_info['sep'] .  implode(',', $djobs));
						}
					}
					pecho('[Executing time] ' . timer(). " seconds \n", PECHO_NOTICE);
				}#reachable
			}
			#end remove_orphan_templates


			#begin fix_dead_links
			if($dname =='fix_dead_links'){
				if(!$wmflDB){
					$wmflDB = new wmflDB();
				}

				if($wmflDB->reachable()){

					$sql = 'SELECT p.page_title
							FROM templatelinks t
							RIGHT JOIN page p ON p.page_id = t.tl_from
							where t.tl_title = "وصلة_مكسورة" AND t.tl_namespace = 10 AND t.tl_from_namespace = 0
							GROUP BY p.page_id
							LIMIT 2; '; //limit daily to fix

					pecho('[Executing SQL] ' . $dname. " ...\n", PECHO_NOTICE);
					timer();
					if($results = $wmflDB->getResults($sql)){
						foreach($results as $row){
							array_push($new_lines_for_queue, trim(implode("\t", $row)) . $_info['sep'] .  implode(',', $djobs));
						}
					}
					pecho('[Executing time] ' . timer(). " seconds \n", PECHO_NOTICE);
				}#reachable
			}
			#end fix_dead_links

			if(sizeof($new_lines_for_queue)){
				file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file'], implode("\n", $new_lines_for_queue), FILE_APPEND | LOCK_EX);
				// exit('added new pages for queue.');
			}

			$daily_status[$dname] = time();
		}

		sleep(3);
	} #end foreach;
	} #end foreach;

	$d_to_save = '';
	foreach($daily_status as $dnn=>$dtt){
		$d_to_save .= $dnn . ":" . $dtt . "\n";
	}

	file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['daily_status_file'], $d_to_save);
	pecho('Added new pages to queue and updated daily status.' ."\n", PECHO_NOTICE);

	if($wmflDB){
		$wmflDB = null; //close connection & desroty object
	}

 }else{
	 if($_info['testing_mode']){
		 pecho('Testing Mode: ON'. "\n", PECHO_NOTICE);
	 }else{
	 	pecho('Can not add more to the large queue, skipped for now.'. "\n", PECHO_NOTICE);
 	}
 }


/**
 * RUN THE JOBS, GET THE QUEUE
 */
if(!file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file'])){
	exit(pecho('No queue file'. "\n", PECHO_NOTICE));
}

if(!is_writable(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file'])){
	exit(pecho('queue file(' . dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file'] . ') is not writable!'. "\n", PECHO_FATAL));
}


#this need to be fixed later, if it's too many lines, memeory issue
if(!$_info['testing_mode']){
	$lines = file(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file']);
	//REMOVE duplicate lines
	$lines = array_unique($lines);
	asort($lines);

	if(!sizeof($lines)){
		exit(pecho('No queue!'. "\n", PECHO_NOTICE));
	}
}else{
	$lines = $_info['testing_mode_queue'];
}

// $_info['limit'] = 10;
$still_editing = $current_same_as_next = false;
$still_editing_content = '';
$still_editing_jobs = '';
$PAGE = null;

if(sizeof($lines) < $_info['limit']){
	$_info['limit'] = sizeof($lines);
}

pecho("[!] Lines in the queue:(".sizeof($lines).") | current proccess limit: (".$_info['limit'].") \n", PECHO_NOTICE);


$start_from = 0;

//in case of interputed proccess before, continue from there.
if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR  . 'current_proccess_line')){
	$start_from = intval(file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'current_proccess_line'));
}

if($start_from > 0){
	pecho("Recovering the last process, stopped at [".$start_from."/".$_info['limit']."] \n", PECHO_NOTICE);
}

$edited_total = 0;

for($i=$start_from; $i < $_info['limit']; $i++) {

	file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'current_proccess_line', $i);

	$line = $lines[$i];
	if(strpos($line, $_info['sep']) !== false){
		list($values, $jobs) = explode($_info['sep'], $line);
	}else{
		continue;
	}

	if(trim($jobs) == '' || trim($values) == ''){
		continue;
	}

	// echo $line . "\n";
	// exit;

	$xjobs = array_map('trim', explode(',', $jobs));
	$xvalues = array_map('trim', explode("\t", $values));

	if(!$still_editing){
		pecho("-------------[" . $xvalues[0] . "]----------\n", PECHO_NOTICE);
		// pecho('current article: ' . $xvalues[0] . "\n", PECHO_NOTICE);
	}

	#if next line is the same article
	$current_same_as_next = false;
	$next_line = empty($lines[$i+1]) ? '' : $lines[$i+1];
	// echo $next_line . "\n";
	if(strpos($next_line, $_info['sep']) !== false){
		list($next_values, $next_jobs) = explode($_info['sep'], $next_line);
		$next_article = explode("\t", $next_values);

		// echo($next_article[0]) . "\n";
		if($next_article[0] === $xvalues[0] && trim($next_jobs) != ''){
			// echo '['.$i.']['.$xvalues[0].']['.($i).'] same as next [' . $next_article[0] . ']['.($i+1).']'."\n";
			$current_same_as_next = true;
		}
	}

	#avoid problems
	if($_info['limit'] <= ($i+1)){
		$current_same_as_next = false;
	}

	if(!$still_editing){
		$timestamp = null;
		$PAGE = $WIKI->initPage($xvalues[0], null, false, true, $timestamp);
		if ($PAGE->get_exists()) { //TODO use $PAGE->get_protection() too
			if($PAGE->is_discussion()){
				pecho('[is_discussion] true' ."\n", PECHO_NOTICE);
				continue;
			}
			$original_content = $new_content = $PAGE->get_text();

		}else{
			pecho('[not exists] ' ."\n", PECHO_NOTICE);
			continue;
		}

	}else{
		$original_content = $new_content = $still_editing_content;
	}

	if($current_same_as_next){
		$still_editing = true;
	}


	$jobs_done = &$still_editing_jobs;
	$xcontent = null;
	foreach($xjobs as $currentJob){

		$currentJob = rtrim(trim($currentJob), '.php');
		if(!function_exists($currentJob)){
			if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'jobs/' . $currentJob . '.php')){
				include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'jobs/' . $currentJob . '.php';
			}
		}

		if(function_exists($currentJob)){
			pecho("[applying job] ".$currentJob."\n", PECHO_NOTICE);
			if(sizeof($xvalues) > 1){
				pecho("[given values] ".implode('|', $xvalues)."\n", PECHO_NOTICE);
			}
			$xcontent = call_user_func($currentJob, $new_content, $PAGE, $xvalues);
		}else{
			pecho("[job not exists] ".$currentJob."\n", PECHO_NOTICE);
		}

		if(!empty($xcontent)){
			$jobs_done[$currentJob] = empty($jobs_done[$currentJob]) ? 1 : $jobs_done[$currentJob]+1;
			$new_content = $xcontent;
		}
	}

	if($still_editing && empty($still_editing_content)){
		$still_editing_content = $new_content;
	}

	$sleep_seconds = $_info['sleep_edit'];

	if($current_same_as_next){
		if(!empty($new_content) && ($new_content != $original_content)){
			$still_editing_content = $new_content;
		}
	}else{
		if(!empty($new_content) && ($new_content != $original_content)){
			$summary_jobs = '';
			foreach($jobs_done as $j => $times){
					$summary_jobs .= ($summary_jobs != '' ? '،' : '') . (isset($JOBS_NAMES[$j]) ? $JOBS_NAMES[$j] : $j) . ($times > 1 ? '('.$times.')' : '');
			}
			$summary = $_info['BOT_NAME'] . ': ' . $summary_jobs;
			$minor = true;

			pecho("[summary] ".$summary."\n", PECHO_NOTICE);
			pecho("[date] " . date('d-m-Y h:i a') . "\n", PECHO_NOTICE);

			try {
				$PAGE->edit(
					$new_content,
					$summary,
					$minor , //is it a minor edit
					true //is it a bot?
				);
			} catch (Exception $e) {
				echo '[error,Caught exception] ',  $e->getMessage(), "\n";
			}
			$edited_total++;
		}else{
			pecho("[nothing to update,skip saving]\n", PECHO_NOTICE);
			$sleep_seconds = $_info['sleep_no_edit'];
		}


		$still_editing = false;
		$PAGE = null;
		$still_editing_jobs = '';
		$still_editing_content = '';
	}


	if(!$still_editing){
		pecho("wait for " .$sleep_seconds . " sec\n", PECHO_NOTICE);
		pecho("--------------------------------------\n", PECHO_NOTICE);
		sleep($sleep_seconds);
	}else{
		sleep(1);
	}
}


if(!$_info['testing_mode']){
	#slice done lines
	if($_info['limit'] >= sizeof($lines)){
		$newLines = array();
	}else{
		$newLines = array_filter(array_splice($lines, $_info['limit']));
	}

	if(file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR  . 'current_proccess_line')){
		@unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'current_proccess_line');
	}

	file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_info['current_queue_file'], implode($newLines));
}

pecho('Bot finished doing this round,(edited:'.$edited_total.'  out of  ' . $_info['limit'] . ") \n", PECHO_NOTICE);
exit;
#end of file
