<?php
/**
 * job's name: arabize
 * job's description: to convert English terms to Arabic
 *
 * @return new content to update, or null to cancel and skip
 */

$JOBS_NAMES['arabize'] = 'تعريب';

function arabize($content, $PAGE = null, $xvalues = null){

	//PAGE is page class handler


	$simple_replace = array(
		'{{Reflist}}' => '{{مراجع}}',
		'<references>' => '{{مراجع}}',
		'<references/>' => '{{مراجع}}',
		'<references />' => '{{مراجع}}',
	);

	$regex_replace = array(

	);

	/**
	 *
	 */

	$new_content = str_replace(array_keys($simple_replace), array_values($simple_replace), $content);
	$new_content = preg_replace(array_keys($regex_replace), array_values($regex_replace), $new_content);


	//TODO https://ar.wikipedia.org/wiki/%D9%85%D8%B3%D8%AA%D8%AE%D8%AF%D9%85:Mr.Ibrahembot/%D8%A8%D9%88%D8%AA_%D8%A7%D9%84%D8%AA%D8%B9%D8%B1%D9%8A%D8%A8

	if($content == $new_content){
		return null;
	}

	return $new_content;
}
