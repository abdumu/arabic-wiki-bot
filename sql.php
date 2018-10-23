#!/usr/bin/env php
<?php

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

function xecho($msg){ print $msg . "\n";}

$cli_options = getopt("q::f");


if(empty($cli_options['q'])){
	if(isset($cli_options['f'])){


		$catgory = 'مقالات_يتيمة_منذ_{arabic_month}_{year}';

		$months = array(
			1 => "يناير",
			2 => "فبراير",
			3 => "مارس",
			4 => "أبريل",
			5 => "مايو",
			6 => "يونيو",
			7 => "يوليو",
			8 => "أغسطس",
			9 => "سبتمبر",
			10 => "أكتوبر",
			11 => "نوفمبر",
			12 => "ديسمبر"
		);

		$catgory = str_replace(array('{year}', '{arabic_month}'), array(rand(2009, date('Y')), $months[rand(1, 12)]), $catgory);

		$catgory = 'جميع_المقالات_اليتيمة';

		$sql = 'SELECT p.page_title, COUNT(p2.page_id) as nom
				FROM categorylinks c
				LEFT JOIN page p ON p.page_id = c.cl_from
				LEFT JOIN pagelinks pl ON p.page_title = pl.pl_title AND p.page_namespace = pl.pl_namespace
				LEFT JOIN page p2 ON p2.page_id = pl.pl_from AND p2.page_is_redirect=0  AND p2.page_namespace = p.page_namespace
				WHERE c.cl_to = "جميع_المقالات_اليتيمة"
				GROUP BY p.page_title
				having COUNT(p2.page_id)>1
                ORDER BY COUNT(p2.page_id) DESC
				LIMIT 500;
		';
	}else{
		exit(xecho('Query SQL [-q option] is not specified! this is required!'));
	}
}else{
	$sql = $cli_options['q'];
}


if(!empty($sql)){
	$wmflDB = new wmflDB();

	if($wmflDB->reachable()){
		#if no limit, make it 1000
		if(strpos(strtoupper($sql), 'LIMIT') === false){
			$sql = rtrim(trim($sql), ';') . ' LIMIT 1000;';
		}

		xecho('[Executed SQL] ' . $sql);
		timer();
		if($results = $wmflDB->getResults($sql)){
			$x = sizeof($results) > 5 ? 4 : sizeof($results);

			xecho('[rows] ' . sizeof($results));
			for ($i = 0; $i < $x; $i++) {
				xecho('[row '.$i.'] ' . implode('|', $results[$i]));
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
