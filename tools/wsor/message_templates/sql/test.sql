(
	SELECT
		False as deleted,
		page_namespace as ns,
		count(*) as revisions
	FROM enwiki.revision
	INNER JOIN enwiki.page ON rev_page = page_id
	WHERE rev_timestamp <= "20110101000000"
	AND rev_user_text = "EpochFail"
	GROUP BY page_namespace
)
UNION
(
	SELECT
		True as deleted,
		ar_namespace as ns,
		count(*) as revisions
	FROM enwiki.archive
	WHERE ar_timestamp <= "20110101000000"
	AND ar_user_text = "EpochFail"
	GROUP BY ar_namespace
)

SELECT 
	r.rev_id,
	r.rev_timestamp,
	r.rev_comment,
	r.rev_user                      AS poster_id,
	r.rev_user_text                 AS poster_name,
	REPLACE(p.page_title, "_", " ") AS recipient_name
FROM revision r
INNER JOIN page p ON r.rev_page = p.page_id
WHERE rev_timestamp BETWEEN "20111222000000" AND "20111223000000"
AND page_namespace = 3;


SELECT
	IF(log_params LIKE "%indefinite%", "ban", "block") as type,
	IF(log_timestamp > "20110101000000", "after", "before") as tense,
	count(*) as count,
	min(log_timestamp) as first,
	max(log_timestamp) as last
FROM logging
WHERE log_type = "block"
AND log_action = "block"
AND log_title = "EpochFail"
GROUP BY 1, 2;
