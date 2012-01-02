import itertools
from .metric_generator import MetricGenerator 

class EditCounts(MetricGenerator):
	
	def __init__(self, conn, api_uri):
		self.conn = conn
	
	def headers(self):
		return itertools.chain(*[
			[
				'ns_%s_before_revisions_deleted' % ns,
				'ns_%s_after_revisions_deleted' % ns,
				'ns_%s_before_revisions_not_deleted' % ns,
				'ns_%s_after_revisions_not_deleted' % ns
			]
			for ns in itertools.chain(range(0,16), [100, 101, 108, 109])
		])
	
	def values(self, username, timestamp):
		rowData = {}
		
		cursor = self.conn.cursor()
		cursor.execute("""
			(
				SELECT
					False as deleted,
					page_namespace as ns,
					count(*) as revisions
				FROM enwiki.revision
				INNER JOIN enwiki.page ON rev_page = page_id
				WHERE rev_timestamp <= %(timestamp)s
				AND rev_user_text = %(username)s
				GROUP BY page_namespace
			)
			UNION (
				SELECT
					True as deleted,
					ar_namespace as ns,
					count(*) as revisions
				FROM enwiki.archive
				WHERE ar_timestamp <= %(timestamp)s
				AND ar_user_text = %(username)s
				GROUP BY ar_namespace
			)""",
			{
				'timestamp': timestamp,
				'username': username.encode('utf-8')
			}
		)
		for row in cursor:
			if(row['deleted']):
				deleted = "deleted"
			else:
				deleted = "not_deleted"
			
			rowData['ns_%s_before_revisions_%s' % (row['ns'], deleted)] = row['revisions']
			
		return [rowData.get(c, 0) for c in self.headers()]
