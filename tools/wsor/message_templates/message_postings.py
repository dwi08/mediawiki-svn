import sys, MySQLdb, MySQLdb.cursors, argparse, os, logging, types, time
import urllib, urllib2
import wmf

def encode(v):
	if v == None: return "\N"
	
	if type(v) == types.LongType:     v = int(v)
	elif type(v) == types.UnicodeType: v = v.encode('utf-8')
	
	return str(v).encode("string-escape")

#                     |  year |   month |     day |    hour |  minute |  second |
MW_DATE = re.compile(r"[0-9]{4}[0-1][0-9][0-3][0-9][0-2][0-9][0-5][0-9][0-5][0-9]")

def mwDate(string):
	if MW_DATE.match(string) == None:
		raise ValueError("%s is not a valid date.  Expected YYMMDDHHmmSS" % string)
	else:
		return string

def main():
	parser = argparse.ArgumentParser(
		description='Gathers template message postings based on comment and diff matching regular expressions.'
	)
	parser.add_argument(
		'-c', '--cnf',
		metavar="<path>",
		type=str, 
		help='the path to MySQL config info (defaults to ~/.my.cnf)',
		default=os.path.expanduser("~/.my.cnf")
	)
	parser.add_argument(
		'-s', '--host',
		type=str, 
		help='the database host to connect to (defaults to localhost)',
		default="localhost"
	)
	parser.add_argument(
		'-d', '--db',
		type=str, 
		help='the language db to run the query in (defaults to enwiki)',
		default="enwiki"
	)
	parser.add_argument(
		'-a', '--api_uri',
		type=str, 
		help='the default Wikimedia API to connect to in order to retrieve message content (defaults to http://en.wikipedia.org/w/api.php)',
		default="http://en.wikipedia.org/w/api.php"
	)
	parser.add_argument(
		'--before',
		type=str, 
		help='the default Wikimedia API to connect to in order to retrieve message content (defaults to http://en.wikipedia.org/w/api.php)',
		default="http://en.wikipedia.org/w/api.php"
	)
	parser.add_argument(
		'after',
		type=mwDate,
		help='regular expression to match against message content'
	)
	parser.add_argument(
		'comment',
		type=re.compile,
		help='regular expression to match against message posting comment'
	)
	parser.add_argument(
		'message',
		type=re.compile,
		help='regular expression to match against message content'
	)
	args = parser.parse_args()
	
	LOGGING_STREAM = sys.stderr
	logging.basicConfig(
		level=logging.DEBUG,
		stream=LOGGING_STREAM,
		format='%(asctime)s %(levelname)-8s %(message)s',
		datefmt='%b-%d %H:%M:%S'
	)
	
	logging.info("Connecting to %s:%s using %s." % (args.host, args.db, args.cnf))
	db = Database(
		host=args.host, 
		db=args.db, 
		read_default_file=args.cnf
	)



class Database:
	
	def __init__(self, *args, **kwargs):
		self.args   = args
		self.kwargs = kwargs
		self.conn   = MySQLdb.connect(*args, **kwargs)
	
	def getPostings(self, afterDate, commentPattern):
		cursor = self.conn.cursor(MySQLdb.cursors.DictCursor)
		cursor.execute(
			"""
			SELECT * FROM
			FROM revision
			WHERE rev_timestamp > %(afterDate)s
			AND rev_comment REGEXP %(commentPattern)s
			""",
			{
				'afterDate': afterDate,
				'commentPattern': commentPattern
			}
		)
		
		for row in cursor:
			yield row
		
	

class WPAPI:
	
	def __init__(self, uri):
		self.uri = uri
	
	def getDiff(self, revId):
		
		response = urllib2.urlopen(
			self.uri,
			data=urllib.urlencode({
				'action': "query",
				'prop':   "revisions",
				'revids': revId,
				'rvprop': "diff",
				'format': "json"
			})
		)
		
		js = json.load(response)
		
		
		
		try:
			if 'badrevids' in js['query']:
				raise KeyError(revId)
			else:
				return js['query']['pages'].values()[0]['revisions'][0]['diff']['*']
		except KeyError:
			
	
	
