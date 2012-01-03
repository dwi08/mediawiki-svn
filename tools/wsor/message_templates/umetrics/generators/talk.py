import itertools
from .metric_generator import MetricGenerator 

class Talk(MetricGenerator):
	
	def __init__(self, conn, api):
		self.conn = conn
	
	def headers(self):
		return [
			'other_talk_before',
			'first_other_talk_before',
			'last_other_talk_before',
			'other_talk_after',
			'first_other_talk_after',
			'last_other_talk_after',
		]
	
	def values(self, username, timestamp):
		rowValues = {}
		
		cursor = self.conn.cursor()
		cursor.execute("""
				SELECT
					IF(rev_timestamp > %(timestamp)s, "after", "before") as whense,
					COUNT(*) as count,
					MAX(rev_timestamp) as last,
					MIN(rev_timestamp) as first
				FROM revision
				INNER JOIN page ON rev_page = page_id
				WHERE page_namespace = 3
				AND rev_timestamp != %(timestamp)s
				AND page_title = %(page_title)s
				AND rev_user_text != %(username)s
				GROUP BY 1
			""",
			{
				'timestamp': timestamp,
				'page_title': username.encode('utf-8').replace(" ", "_"),
				'username': username.encode('utf-8')
			}
		)
		for row in cursor:
			rowValues['other_talk_%(whence)s'] = row['count']
			rowValues['first_other_talk_%(whence)s'] = row['first']
			rowValues['last_other_talk_%(whence)s'] = row['last']
		
		rowValues['other_talk_before'] = rowValues.get('other_talk_before', 0)
		rowValues['other_talk_after']  = rowValues.get('other_talk_after', 0)
			
		return [rowValues.get(c) for c in self.headers()]
