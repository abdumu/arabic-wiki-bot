<?php

#wiki code
$_info['wiki'] = 'ar';

# Use (peachy_config.cfg) to configure username + password of the bot member
# The account's name should identify the bot function (e.g. <Task>Bot), or the operator's main account (e.g. <Username>Bot).


#bot title for summary
$_info['BOT_NAME'] = '[[مستخدم:NameOfBot|NameOfBot]]';

#for production mode, set this to false
$_info['testing_mode'] = false;

#if testing_mode=true, these items will be tested (sepereated by $_info['sep'])
$_info['testing_mode_queue'] = array('إعصار_إيزابيل::--sep--::import_gallery');

#a file to save pages title in, no need to change
$_info['current_queue_file'] = 'current_queue';
#files to save current status, no need to change
$_info['queries_status_file'] = 'queries_status';
$_info['daily_status_file'] = 'schedule_status';

#interval [seconds] between edits/no_edits
#BOT POLICY: Bots' editing speed should be regulated in some way; subject to approval, bots doing non-urgent tasks may edit approximately once every ten seconds, while bots doing more urgent tasks may edit approximately once every five seconds.
$_info['sleep_edit'] = 10;
$_info['sleep_no_edit'] = 4;

#limit of page edits in one round
$_info['limit'] = 10;

#sepatrator between values, no need to change
$_info['sep'] = '::--sep--::';

/**
 * Process that executed on schedule
 * keys are the interval time:  1d = daily, 2d every other day, 2h = every 2 hours .., 30m = every 30 minutes
 */
 $_info['schedule'] = array(
	'1d' => array(
		#name => jobs to apply to every page
		// 'recent' => array('clean', 'arabize'), #latest 250 edits
		// 'fix_images' => array('fix_broken_images'),
	),

	'3d' => array(
		// 'remove_orphan_templates'	=> array('remove_orphaned_template'),
		// 'fix_dead_links' 			=> array('fix_dead_link'),
	)
);
