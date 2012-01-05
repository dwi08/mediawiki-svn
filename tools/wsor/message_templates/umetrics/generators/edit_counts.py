import itertools
from .metric_generator import MetricGenerator 

class EditCounts(MetricGenerator):
	
	def __init__(self, conn, api):
		self.conn = conn
	
	def headers(self):
		return itertools.chain(*[
			[
				'ns_%s_revisions_before' % ns,
				'ns_%s_revisions_after' % ns,
				'ns_%s_revisions_deleted_before' % ns,
				'ns_%s_revisions_deleted_after' % ns
			]
			for ns in itertools.chain(range(0,16), [100, 101, 108, 109])
		])
	
	def values(self, username, timestamp):
		rowData = {}
		
		cursor = self.conn.cursor()
		cursor.execute("""
			(
				SELECT
					page_namespace as ns,
					IF(rev_timestamp < %(timestamp)s, "before", "after") as whence,
					"" as deleted,
					count(*) as revisions
				FROM enwiki.revision
				INNER JOIN enwiki.page ON rev_page = page_id
				WHERE rev_user_text = %(username)s
				GROUP BY 1, 2
			)
			UNION (
				SELECT
					ar_namespace as ns,
					IF(ar_timestamp < %(timestamp)s, "before", "after") as whence,
					"_deleted" as deleted,
					count(*) as revisions
				FROM enwiki.archive
				WHERE ar_user_text = %(username)s
				GROUP BY 1, 2
			)""",
			{
				'timestamp': timestamp,
				'username': username.encode('utf-8')
			}
		)
		for row in cursor:
			rowData['ns_%(ns)s_revisions%(deleted)s_%(whence)s' % row] = row['revisions']
			
		return [rowData.get(c, 0) for c in self.headers()]
