<?php

/*
| Academic year and semester on every activity, so records can be filtered
| and reported by school year.
|
| Activities saved before this step are filled in from their date, using the
| ISU Ilagan calendar: the school year starts in August, 1st Semester runs
| August to December, 2nd Semester January to May, and Midyear June to July.
| The admin can change any of them afterwards.
*/

return function(Migrator $m){

	$m->add_column('activities', 'academic_year', "VARCHAR(20) NULL DEFAULT NULL");
	$m->add_column('activities', 'semester', "VARCHAR(20) NULL DEFAULT NULL");
	$m->add_index('activities', 'idx_academic_year', '`academic_year`');
	$m->add_index('activities', 'idx_activity_date', '`activity_date`');

	$m->run("
		UPDATE activities
		SET academic_year = CASE
				WHEN MONTH(activity_date) >= 8
					THEN CONCAT(YEAR(activity_date), '-', YEAR(activity_date) + 1)
					ELSE CONCAT(YEAR(activity_date) - 1, '-', YEAR(activity_date))
			END,
			semester = CASE
				WHEN MONTH(activity_date) BETWEEN 8 AND 12 THEN '1st Semester'
				WHEN MONTH(activity_date) BETWEEN 1 AND 5  THEN '2nd Semester'
				ELSE 'Midyear'
			END
		WHERE (academic_year IS NULL OR academic_year = '')
		AND activity_date IS NOT NULL
	");

	$m->note("existing activities given an academic year and semester from their date");
};
