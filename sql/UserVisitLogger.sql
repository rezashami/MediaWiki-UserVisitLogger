CREATE TABLE IF NOT EXISTS /*_*/user_visit_log (
	user_id INT(5) UNSIGNED NOT NULL,
	page_id INT(8) UNSIGNED NOT NULL,
	count_per_day INT(10) UNSIGNED NOT NULL,
	visit_day DATE DEFAULT NULL,
	PRIMARY KEY ( user_id, page_id,visit_day )
);
