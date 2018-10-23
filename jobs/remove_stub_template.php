<?php
/**
 * job's name: remove_templates
 * job's description: remove templates if not longer needed
 *
 * @return new content to update, or null to cancel and skip
 */

 $JOBS_NAMES['remove_stub_template'] = 'إزالة قالب بذرة';

function remove_stub_template($content, $PAGE = null, $xvalues = null){

	//PAGE is page class handler

	$simple_replace = array(

	);

	$regex_replace = array(

	);

	$new_content = $content;

	// 16 => 'قالب:بذرة',
    // 17 => 'قالب:بذرة فيلم مصري',
	// var_export($PAGE->get_templates());




	// if($content == $new_content){
		return null;
	// }

	return $new_content;
}
