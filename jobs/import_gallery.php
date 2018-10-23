<?php
/**
 * job's name: import_gallery
 * job's description: import gallery if not exists from other wikis
 *
 * @return new content to update, or null to cancel and skip
 * Some ideas were taken from cosmetic_changes.py
 *
 */


 $JOBS_NAMES['import_gallery'] = 'إضافة معرض صور';



function import_gallery($content, $PAGE = null, $xvalues = null){

	//PAGE is page class handler, useful to get access to other functions, like links etc.

	static $WIKI_EN = null;

	if($WIKI_EN == null){
		$WIKI_EN = getReadOnlyWiki('en');
	}


	//-------------------> [check if current page has interwikis]
	//no return null
	//yes contunue


	//-------------------> [check if current page has gallery tag]
	//yes return null
	//no continue

	//-------------------> [large pages - ignore]
	// > 15000


	//-------------------> [get the English page content]
	//if empty, redirect or no gallery, return null

	//-------------------> [extract images from gallery, then check if they are commons and not local]
	//if sizeof(imgs) > 1 continue
	// < 1 return null




	// if($content == $new_content){
		return null;
	// }

	return $new_content;
}




//for testing only
if(isset($_GET['test'])){

	$test = '';
	echo import_gallery($text);
}
