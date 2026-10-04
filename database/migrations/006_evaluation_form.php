<?php

/*
| EVALUATION FORM: COMMENTS, AND ONE RESPONSE PER DEVICE
|
| The printed form ends with "Other comments/suggestions". The online form
| showed that box but never saved what was typed; responses now keep it.
|
| Each device may answer an activity's evaluation once. The form remembers
| the device with a cookie, and this makes the database refuse a second
| response from the same device even when two arrive at the same moment
| (a double tap on Submit). Responses saved before this have no device and
| are not affected.
*/

return function(Migrator $m){

	$m->add_column('evaluation_responses', 'comments', 'TEXT NULL');

	if($m->table_exists('evaluation_responses') && !$m->index_exists('evaluation_responses', 'uq_device_response')){
		$m->run("
			ALTER TABLE `evaluation_responses`
			ADD UNIQUE KEY `uq_device_response` (`questionnaire_id`, `activity_id`, `device_token`)
		");
		$m->note('added: one response per device for each activity');
	}
};
