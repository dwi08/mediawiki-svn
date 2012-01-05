import itertools, wmf, difflib, re
from .metric_generator import MetricGenerator


class Warnings(MetricGenerator):
	
	WARN_RE = re.compile(r'<!--\s*Template:uw-')
	
	def __init__(self, conn, api):
		self.conn = conn
		self.api  = api
	
	def headers(self):
		return [
			'warns_before',
			'warns_after',
			'first_warn_before',
			'last_warn_before',
			'first_warn_after',
			'last_warn_after'
		]
	
	def values(self, username, timestamp):
		rowValues = {
			'warns_before':      0,
			'warns_after':       0
		}
		
		timestamp = wmf.wp2Timestamp(timestamp)
		
		for rev in self.getProcessedRevs(username):
			#determine if we have a warning
			if self.WARN_RE.search(rev['added']) != None:
				if rev['timestamp'] < timestamp:
					whence = "before"
				elif rev['timestamp'] > timestamp:
					whence = "after"
				else:
					continue
				
				rowValues['warns_%s' % whence] += 1
				
				if 'first_warn_%s' % whence not in rowValues:
					rowValues['first_warn_%s' % whence] = wmf.timestamp2WP(rev['timestamp'])
				
				rowValues['last_warn_%s' % whence] = wmf.timestamp2WP(rev['timestamp'])
		
		return [rowValues.get(c) for c in self.headers()]
	
	def getProcessedRevs(self, username):
		return self.processRevs(self.getUserPageRevisions(username))
	
	def getUserPageRevisions(self, username, rvcontinue={}):
		js = self.api.request(
			action="query",
			prop="revisions",
			titles="User_talk:%s" % username.encode('utf-8'),
			rvprop="ids|timestamp|content",
			rvdir="newer",
			rvlimit=50,
			**rvcontinue
		)
		
		for rev in js['query']['pages'].values()[0].get('revisions', []):
			yield rev
		
		if 'query-continue' in js:
			for rev in self.getUserPageRevisions(username, js['query-continue']['revisions']):
				yield rev
			
		
	
	def processRevs(self, revs):
		
		previousLines = []
		for rev in revs:
			lines = rev.get('*', "").split("\n")
			
			try:             del rev['*']
			except KeyError: pass
			
			added = []
			sm = difflib.SequenceMatcher(None, previousLines, lines)
			for tag, i1, i2, j1, j2 in sm.get_opcodes():
				if tag == "insert":
					added.extend(lines[j1:j2])
				elif tag == "replace":
					added.extend(lines[j1:j2])
				
			
			rev['added'] = "\n".join(added)
			rev['timestamp'] = wmf.wp2Timestamp(rev['timestamp'])
			yield rev
			previousLines = lines
			
		
			
	
def test():
	from umetrics.generators import Warnings
	from umetrics.util import MWAPI
	w = Warnings(None, MWAPI('http://en.wikipedia.org/w/api.php'))
	for rev in w.getProcessedRevs('EpochFai'):
		print(rev[id])

