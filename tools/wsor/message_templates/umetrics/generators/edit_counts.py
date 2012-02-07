import itertools, datetime, time
from .metric_generator import MetricGenerator 

class EditCounts(MetricGenerator):
	
	def __init__(self, conn, api):
		self.conn = conn
	
	def headers(self):
		return itertools.chain(*[
			[
				'ns_%s_revisions_before' % ns,
				'ns_%s_revisions_after_0_3' % ns,
				'ns_%s_revisions_after_3_30' % ns,
				'ns_%s_revisions_after_gt_30' % ns,
				'ns_%s_revisions_deleted_before' % ns,
				'ns_%s_revisions_deleted_after_0_3' % ns,
				'ns_%s_revisions_deleted_after_3_30' % ns,
				'ns_%s_revisions_deleted_after_gt_30' % ns
			]
			for ns in [0,3]
			# for ns in itertools.chain(range(0,16), [100, 101, 108, 109])
		])
	
	def values(self, username, timestamp):
		rowData = {}
		
		# Partition timestamps into:
		#
		#	0-3 days after
		#	3-30 days after
		#	more than 30 days after
		
		timeobj = datetime.datetime(year=int(timestamp[:4]), month=int(timestamp[4:6]), day=int(timestamp[6:8]), hour=int(timestamp[8:10]), minute=int(timestamp[10:12]), second=int(timestamp[12:14])) 
		
		timeobj_3_days = timeobj + datetime.timedelta(days=3)
		timeobj_30_days = timeobj + datetime.timedelta(days=30)
		
		timestamp_3_days = time.strftime("%Y%m%d%H%M%S", timeobj_3_days.timetuple())
		timestamp_30_days = time.strftime("%Y%m%d%H%M%S", timeobj_30_days.timetuple())

		cursor = self.conn.cursor()
		cursor.execute("""
			(
				SELECT
					page_namespace as ns,
					IF(rev_timestamp < %(timestamp)s, "before", IF(rev_timestamp < %(timestamp_3_days)s, "after_0_3", IF(rev_timestamp < %(timestamp_30_days)s, "after_3_30", "after_gt_30"))) as whence,
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
					IF(ar_timestamp < %(timestamp)s, "before", IF(ar_timestamp < %(timestamp_3_days)s, "after_0_3", IF(ar_timestamp < %(timestamp_30_days)s, "after_3_30", "after_gt_30"))) as whence,
					"_deleted" as deleted,
					count(*) as revisions
				FROM enwiki.archive
				WHERE ar_user_text = %(username)s
				GROUP BY 1, 2
			)""",
			{
				'timestamp': timestamp,
				'timestamp_3_days': timestamp_3_days,
				'timestamp_30_days': timestamp_30_days,
				'username': username.encode('utf-8')
			}
		)
		for row in cursor:
			rowData['ns_%(ns)s_revisions%(deleted)s_%(whence)s' % row] = row['revisions']
			
		return [rowData.get(c, 0) for c in self.headers()]
