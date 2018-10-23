<?php
/**
 * job's name: add_ref_template
 * job's description: add ref template
 *
 * @return new content to update, or null to cancel and skip
 */

 $JOBS_NAMES['add_ref_template'] = 'إضافة قالب مراجع';


function add_ref_template($content, $PAGE = null, $xvalues = null){

	//PAGE is page class handler

	$simple_replace = array(

	);

	$regex_replace = array(

	);





	//TODO https://ar.wikipedia.org/wiki/%D9%85%D8%B3%D8%AA%D8%AE%D8%AF%D9%85:Mr.Ibrahembot
	//TODO إضافة قالب مراجع
	//TODO إضافة قالب تحويل تصنيف للصيانة.
	//TODO إضافة قالب معرفات الأصنوفة للمقالات.
	//TODO إضافة قالب ضبط استنادي للمقالات.
	//TODO اضافة قالب {{معلومات مدينة}}
	//TODO اضافة قالب {{صندوق معلومات شخص }}
	//TODO اضافة قالب معلومات
	//TODO اضافة قالب بذرة من مقالات اصغر من 4 كب .


	$new_content = str_replace(array_keys($simple_replace), array_values($simple_replace), $content);
	$new_content = preg_replace(array_keys($regex_replace), array_values($regex_replace), $new_content);


	if($content == $new_content){
		return null;
	}

	return $new_content;
}
