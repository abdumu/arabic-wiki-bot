<?php
/**
 * job's name: fix_dead_link
 * job's description: if there is a dead link, try to recover it using archive websites
 *
 * Support Memento Time Travel Find: searches in Web archives: archive.today, Archive-It, Arquivo.pt: the Portuguese Web
 * Archive, Bibliotheca Alexandrina Web Archive, DBpedia archive, DBpedia Triple Pattern Fragments archive, Canadian
 * Government Web Archive, Croatian Web Archive, Estonian Web Archive, Icelandic web archive, Internet Archive, Library of
 * Congress Web Archive, NARA Web Archive, National Library of Ireland Web Archive, perma.cc, PRONI Web Archive, Slovenian
 * Web Archive, Stanford Web Archive, UK Government Web Archive, UK Parliament's Web Archive, UK Web Archive, Web Archive
 * Singapore, WebCite, Bayerische Staatsbibliothek
 *
 * @return new content to update, or null to cancel and skip
 */

 $JOBS_NAMES['fix_dead_link'] = 'إصلاح وصلة خارجية مكسورة';

function fix_dead_link($content, $PAGE = null, $xvalues = null){

	//PAGE is page class handler
	// file_put_contents('test_content.txt', $content);
	// if(!$PAGE){
	// 	// return null;
	// }
	//
	// todo
	// * [http://www.barcelona2004.org/eng/actualidad/noticias/html/f043167.htm Comments of Touraine at Forum Barcelona 2004]{{وصلة مكسورة|تاريخ=أكتوبر 2013}}


	#if no deadlink template exists, why bother?
	if(!preg_match('/\{\{(وصلة مكسورة|مرجع مكسور|مصدر مكسور|Broken ref|Dead link|Deadlink)[^\}]*\}\}/ui', $content, $m)){
		return null;
	}


	//anything except url
	$encode = array('[' =>'&#91;', ']' => '&#93;', '|' => '&#124;');

	$new_content = $content;

	//if news then |issue= and |volume= exists
	$new_content = preg_replace_callback('!<ref([^>/]*)>(.*?)<\/ref>!ui',
	function($m){
;
		//!\{\{(cite|مرجع)\h*(web|news|ويب)(.*?)\}\}!u
		if(preg_match('!\{\{(وصلة مكسورة|مرجع مكسور|مصدر مكسور|Broken ref|Dead link|Deadlink)[^\}]*\}\}!ui', $m[2])){
			// var_dump($m);
			$m_2 = preg_replace_callback('!\{\{(cite|مرجع|استشهاد)\h*(web|news|ويب|بخبر|journal)(.*?)\}\}!ui', function($ref_m){
				// var_export($ref_m);
				$inside_return = '{{' .  $ref_m[1] . ' ' . $ref_m[2]. " \n".'|';

					// var_export($ref_m[3]);
					if(preg_match_all('!\|*\h*([\w\d\-]+)\h*=+\h*([^\|]+)!u', $ref_m[3], $ref_m_c)){
						// echo "ref_m_c\n";var_dump($ref_m_c);
						if(sizeof($ref_m_c[1]) && sizeof($ref_m_c[2])){

							$output = '';
							$return_original = true;
							foreach($ref_m_c[1] as $id=>$key){
								if($key == 'url' || $key == 'المسار' || $key == 'URL' || $key == 'مسار'){
									$link = isset($ref_m_c[2][$id]) ? $ref_m_c[2][$id] : null;
									if(!empty($link)){
										// if($linkx = url_time_machine($link)){
										if($linkx = url_memento_machine($link)){
											if(is_array($linkx) && $linkx['url'] != $link){
												$return_original = false;
												$output .= ($output == '' ? '' : "\n".'|'). 'مسار الأرشيف=' . $linkx['url'];
												$output .= ($output == '' ? '' : "\n".'|') . 'تاريخ الأرشيف=' . $linkx['date'];
												$output .= ($output == '' ? '' : "\n".'|') . 'وصلة مكسورة=no';
											}
										}
									}
								}

								if(in_array(strtolower($key), array('archive-url', 'archive-date', 'dead-url', 'وصلة ميتة', 'وصلة مكسورة', 'تاريخ الأرشيف', 'مسار الأرشيف', 'الأرشيف'))){
									continue;
								}


								$output .= ($output == '' ? '' : "\n".'|') . $key . '=' . $ref_m_c[2][$id];
							}

							if(!$return_original){
								return  $inside_return . $output . "\n" .'}}'."\n";
							}
						}
					}

					return $ref_m[0];

			}, $m[2]);

			// else if(preg_match('', $m[2], $ref_m_ll)){
			// 	//external link [link text]
			// 	// var_dump($ref_m_ll);
			// }else if(preg_match('!\[(.*?)\]!u', $m[2], $ref_m_l)){
			// 	//external link [link]
			// 	// var_dump($ref_m_l);
			//
			// }

			if($m_2 == $m[2]){
				//external links
				$m_2 = preg_replace_callback('!\[([^\]]+)\]!u', function($ref_ll){
					if(!empty($ref_ll[1])){
						$l = $ref_ll[1];
						if(strpos(trim($ref_ll[1]), ' ') !== false){
							list($l, $t) = array_filter(array_map('trim', explode(' ', trim($ref_ll[1]), 2)));
						}

						if($linkx = url_time_machine($ref_ll[1])){
							if(is_array($linkx) && $linkx['url'] != $ref_ll[1]){
								return '['.$linkx['url']. (!empty($t) ? ' ' . $t : '') . ' (نسخة مؤرشفة منذ '.$linkx['date'].')' .']';
							}
						}
					}
					return $ref_ll[0];
				}, $m[2]);
			}

			if($m_2 != $m[2]){
				$m_2 = preg_replace('!\{\{(وصلة مكسورة|مرجع مكسور|مصدر مكسور|Broken ref|Dead link|Deadlink)[^\}]*\}\}!ui', '', $m_2);

				return '<ref'.$m[1].'><!--arabic-wiki-bot-->' . trim($m_2) . '</ref>';
			}
		}


		return $m[0];
	},
	$new_content);




	if($content == $new_content){
		return null;
	}

	return $new_content;
}


function url_memento_machine($link){

	$api_url = 'http://timetravel.mementoweb.org/timegate/';
	$URL = $api_url . rawurldecode($link);

	$contents = '';
	$headers = array();
	// $contents = '{"archived_snapshots":{"closest":{"available":true,"url":"http://web.archive.org/web/20090521224840/http://www.arwu.org//rank2008//ARWU2008_TopEuro%28EN%29.htm","timestamp":"20090521224840","status":"200"}}}';
	if(function_exists('curl_init')){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_URL, $URL);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION,
		  function($curl, $header) use(&$headers)
		  {
		    $len    = strlen($header);
		    $header = explode(':', $header, 2);
		    if (count($header) < 2)
		      return $len;

		    $headers[strtolower(trim($header[0]))] = trim($header[1]);
		    return $len;
		  }
		);

		$contents = curl_exec($ch);
		curl_close($ch);

	}else if( ini_get('allow_url_fopen') ) {
		$contents = file_get_contents($URL);
		$headers = $http_response_header;
	}

	// var_export($headers);

	// //   ["location"]=> "http://web.archive.org/web/20130712222821/http://wwww.google.com"
	// $x = '<http://wwww.google.com>; rel="original", <http://web.archive.org/web/timemap/link/http://wwww.google.com>; rel="timemap"; type="application/link-format", <http://web.archive.org/web/http://wwww.google.com>; rel="timegate", <http://web.archive.org/web/20130711153503/http://wwww.google.com/>; rel="first memento"; datetime="Thu, 11 Jul 2013 15:35:03 GMT", <http://web.archive.org/web/20130711173655/http://wwww.google.com/>; rel="prev memento"; datetime="Thu, 11 Jul 2013 17:36:55 GMT", <http://web.archive.org/web/20130712222821/http://wwww.google.com>; rel="memento"; datetime="Fri, 12 Jul 2013 22:28:21 GMT", <http://web.archive.org/web/20130712222821/http://wwww.google.com>; rel="last memento"; datetime="Fri, 12 Jul 2013 22:28:21 GMT"';



	if(!empty($headers['link'])){
		$result = parse_memento_links($headers['link']);
	}else{
		return false;
	}


	#return link with econded chars as documented in wikimedia
	$a = array(' '=>'%20', '"'=>'%22', '\''=>'%27', '<'=>'%3c', '>'=>'%3e', '['=>'%5b', ']'=>'%5d', '{'=>'%7b', '|'=>'%7c', '}'=>'%7d');


	if(is_array($result) && !empty($result['memento'])){
		return array(
			'url'=> str_replace(array_keys($a), array_values($a), $result['memento']['url']),
			'date'=> date('Y-m-d', is_numeric($result['memento']['datetime']) ? strtotime($result['memento']['datetime']) : time())
		);
	}

	return false;
}

function parse_memento_links($link_header){

	$links = array();
	$replace_chars = ' \'"';

	$splited_values = preg_split('/,\ *</', $link_header);

	if(empty($splited_values)){
		return $links;
	}

	foreach($splited_values as $val){
		if(strpos($val, ';') !== false){
			list($url, $params) = explode(';', $val, 2);
		}else{
			list($url, $params) = array($val, '');
		}

		$link = array();
		$link['url'] = str_replace(array('<', '>', ' ', '"', "'"), '', $url);
		if(strpos($params, ';')){
			$split_params = explode(';', $params);
			foreach($split_params as $param){
				if(strpos($param, '=') !== false){
					list($key, $value) = explode('=', $param, 2);
					$link[str_replace(array(' ', '"', "'"), '', $key)] = str_replace(array(' ', '"', "'"), '', $value);
				}else{
					continue;
				}
			}
		}

		if(!empty($link['rel'])){
			$links[$link['rel']] = $link;
		}else{
			array_push($links, $link);
		}
	}

	return $links;
}


#only archive.org
function url_time_machine($link){

	$api_url = 'http://archive.org/wayback/available?url=';

	$URL = $api_url . urlencode(rawurldecode($link));

	$contents = '';
	// $contents = '{"archived_snapshots":{"closest":{"available":true,"url":"http://web.archive.org/web/20090521224840/http://www.arwu.org//rank2008//ARWU2008_TopEuro%28EN%29.htm","timestamp":"20090521224840","status":"200"}}}';
	if(function_exists('curl_init')){
		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $URL);
		$contents = curl_exec($c);
		curl_close($c);
	}else if( ini_get('allow_url_fopen') ) {
		$contents = file_get_contents($URL);
	}

	#return link with econded chars as documented in wikimedia
	$a = array(' '=>'%20', '"'=>'%22', '\''=>'%27', '<'=>'%3c', '>'=>'%3e', '['=>'%5b', ']'=>'%5d', '{'=>'%7b', '|'=>'%7c', '}'=>'%7d');


	if (!empty($contents)){
		$result = json_decode($contents, true);
		if(json_last_error() == JSON_ERROR_NONE && is_array($result)){
			if((isset($result['archived_snapshots']['closest']['status']) && $result['archived_snapshots']['closest']['status'] == 200)
				&& (isset($result['archived_snapshots']['closest']['available']) && $result['archived_snapshots']['closest']['available'])
			){
				return array(
					'url'=> str_replace(array_keys($a), array_values($a), $result['archived_snapshots']['closest']['url']),
					'date'=> date('Y-m-d', is_numeric($result['archived_snapshots']['closest']['timestamp']) ? strtotime($result['archived_snapshots']['closest']['timestamp']) : time())
				);
			}
		}
	}

	 return false;
}


if(isset($_GET['test'])):
echo 'test:' . "\n";
$test = file_get_contents('../test_content.txt');

$content = fix_dead_link($test, null);

echo $content;
// var_export(url_memento_machine('http://php.net/manual/en/function.strip-tags.php'));

endif;
