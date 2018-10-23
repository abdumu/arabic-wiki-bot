<?php
/**
 * job's name: remove_orphaned_template
 * job's description: remove templates if not longer needed
 *
 * @return new content to update, or null to cancel and skip
 */

 $JOBS_NAMES['remove_orphaned_template'] = 'إزالة قالب يتيمة';

function remove_orphaned_template($content, $PAGE = null, $xvalues = null){

	//PAGE is page class handler

	if(!$PAGE){
		return null;
	}

	try{
		$articles = $PAGE->get_backlinks(array(0), 'nonredirects', false);
	}catch(Exception $e){
		echo '[remove_orphaned_template][backlinks exception] ',  $e->getMessage(), "\n";
		return null;
	}

	print 'Backlinks: '. sizeof($articles) . " \n";

	if(is_array($articles) && sizeof($articles) > 2){
		$new_content = $content;

		//remove it here
		//{{يتيمة|تاريخ=مايو 2017}}

		$regex_replace = array(
				'!\{\{يتيمة\}\}!u' => '',
				'!\{\{يتيمة\|([^\}]*)\}\}!u' => '',
		);

		$new_content = preg_replace(array_keys($regex_replace), array_values($regex_replace), $new_content);

	}else{
		return null;
	}


	if($content == $new_content){
		return null;
	}

	return $new_content;
}


if(isset($_GET['test'])):

$test = "

wf
qwf
q
wf
qw
f
qwf

{{يتيمة|تاريخ=مايو 2017}} qwfqwf
// qwfqw
";


$content = remove_orphaned_template($test, null);

echo $content;

endif;
