<?php

/*
| Records WHEN each evaluation was submitted.
|
| Evaluations saved before this step have no time of their own, so they are
| given their activity's date. That is the closest true value available, and
| it lets the Evaluation Trend chart use real evaluation data.
*/

return function(Migrator $m){

	$m->add_column('evaluation_answers', 'submitted_at', 'DATETIME NULL DEFAULT NULL');
	$m->add_index('evaluation_answers', 'idx_submitted_at', '`submitted_at`');
	$m->add_index('evaluation_answers', 'idx_activity', '`activity_id`');

	// Fill in the ones saved before this change, from their activity's date
	$m->run("
		UPDATE evaluation_answers ea
		INNER JOIN activities a ON a.id = ea.activity_id
		SET ea.submitted_at = TIMESTAMP(a.activity_date)
		WHERE ea.submitted_at IS NULL
	");

	// Anything left (an activity that no longer exists) gets today's date
	$m->run("UPDATE evaluation_answers SET submitted_at = NOW() WHERE submitted_at IS NULL");

	$m->note("existing evaluations dated from their activity");
};
