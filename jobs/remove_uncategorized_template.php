<?php
/**
 * job's name: remove_uncategorized_template
 * job's description: remove templates if not longer needed
 *
 * @return new content to update, or null to cancel and skip
 */

 $JOBS_NAMES['remove_uncategorized_template'] = 'إزالة قالب غير مصنفة';

function remove_uncategorized_template($content, $PAGE = null, $xvalues = null){

	//PAGE is page class handler

	$cats = $PAGE->get_categories();

	if(is_array($cats) && sizeof($cats) > 0){

	}else{
		return null;
	}

	$new_content = $content;


	//{{غير مصنفة|تاريخ=مايو 2017}}


	if($content == $new_content){
		return null;
	}

	return $new_content;
}
