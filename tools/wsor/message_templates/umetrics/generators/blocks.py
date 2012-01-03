import itertools
from .metric_generator import MetricGenerator 

class Blocks(MetricGenerator):
	
	def __init__(self, conn, api):
		self.conn = conn
	
	def headers(self):
		return [
			'blocks_before',
			'blocks_after',
			'first_block_before',
			'last_block_before',
			'first_block_after',
			'last_block_after',
			'bans_before',
			'bans_after',
			'first_ban_before',
			'last_ban_before',
			'first_ban_after',
			'last_ban_after'
		]
	
	def values(self, username, timestamp):
		rowValues = {}
		
		cursor = self.conn.cursor()
		cursor.execute("""
				SELECT
					IF(log_params LIKE "%%indefinite%%", "ban", "block") as type,
					IF(log_timestamp > %(timestamp)s, "after", "before") as whense,
					count(*) as count,
					min(log_timestamp) as first,
					max(log_timestamp) as last
				FROM logging
				WHERE log_type = "block"
				AND log_action = "block"
				AND log_title = %(username)s
				GROUP BY 1, 2
			""",
			{
				'timestamp': timestamp,
				'username': username.encode('utf-8').replace(" ", "_")
			}
		)
		for row in cursor:
			rowValues['%(type)ss_%(whense)s' % row] = row['count']
			rowValues['first_%(type)s_%(whense)s' % row] = row['first']
			rowValues['last_%(type)s_%(whense)s' % row] = row['last']
		
		rowValues['blocks_before'] = rowValues.get('blocks_before', 0)
		rowValues['blocks_after']  = rowValues.get('blocks_after', 0)
		rowValues['bans_before']   = rowValues.get('bans_before', 0)
		rowValues['bans_after']    = rowValues.get('bans_after', 0)
			
		return [rowValues.get(c) for c in self.headers()]
