import sys, argparse, os
import logging, types
import MySQLdb, MySQLdb.cursors
import traceback

from .generators import GENERATORS, Metrics
from .util import MWAPI, MWAPIError

def encode(v):
	if v == None: return "\N"
	
	if type(v) == types.LongType:     v = int(v)
	elif type(v) == types.UnicodeType: v = v.encode('utf-8')
	
	return str(v).encode("string-escape")


def main():
	
	parser = argparse.ArgumentParser(
		description="""
		Gathers metrics for users around a timestamp.
		""",
		conflict_handler="resolve"
	)
	parser.add_argument(
		'-c', '--cnf',
		metavar="<path>",
		type=str, 
		help='the path to MySQL config info (defaults to ~/.my.cnf)',
		default=os.path.expanduser("~/.my.cnf")
	)
	parser.add_argument(
		'-h', '--host',
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
		'-a', '--api',
		type=MWAPI, 
		help='the mediawiki API to connect to in order to retrieve message content (defaults to http://en.wikipedia.org/w/api.php)',
		default="http://en.wikipedia.org/w/api.php"
	)
	parser.add_argument(
		'-o', '--old',
		type=lambda fn: open(fn, 'r'), 
		help='a previous output file to read from.  When provided, this script will skip all of the complete username/timestamp pairs found in the file.',
	)
	parser.add_argument(
		'--debug',
		action="store_true",
		default=False
	)
	parser.add_argument(
		'--headers',
		action="store_true",
		default=False
	)
	parser.add_argument(
		'generator',
		type=lambda g: GENERATORS[g],
		nargs="+",
		help='the metric generators to run (%s)' % ', '.join(GENERATORS.keys())
	)
	args = parser.parse_args()
	
	LOGGING_STREAM = sys.stderr
	if args.debug: logLevel = logging.DEBUG
	else:          logLevel = logging.INFO
	logging.basicConfig(
		level=logLevel,
		stream=LOGGING_STREAM,
		format='%(asctime)s %(levelname)-8s %(message)s',
		datefmt='%b-%d %H:%M:%S'
	)
	
	if sys.stdin.isatty():
		logging.error("No data piped to standard in!")
		return 1
	
	
	logging.info("Connecting to %s:%s using %s." % (args.host, args.db, args.cnf))
	conn = MySQLdb.connect(
		host=args.host, 
		db=args.db, 
		read_default_file=args.cnf,
		cursorclass=MySQLdb.cursors.DictCursor
	)
	
	logging.info("Loading generators...")
	metrics = Metrics(g(conn, args.api) for g in args.generator)
	
	
	oldPairs = set()
	if args.old != None:
		logging.info("Loading in old data file...")
		for line in args.old:
			username, timestamp = line.strip().split("\t")[0:2]
			username = unicode(username.decode('string-escape'), 'utf-8')
			
			oldPairs.add((username, timestamp))
			LOGGING_STREAM.write(".")
		
		LOGGING_STREAM.write("\n")
		
	else: 
		if args.headers:
			print("\t".join(encode(h) for h in metrics.headers()))
	
	
	logging.info("Processing users...")
	for line in sys.stdin:
		try:
			username, timestamp = line.strip().split("\t")[0:2]
			username = unicode(username.decode('string-escape'), 'utf-8')
			
			if (username, timestamp) in oldPairs:
				LOGGING_STREAM.write("s")
			else:
				logging.debug("\t%s at %s:" % (username, timestamp))
				print("\t".join(encode(v) for v in metrics.values(username, timestamp)))
				sys.stdout.flush()
				LOGGING_STREAM.write(".")
		except Exception as e:
			logging.error("An error occurred while processing %s at %s." % (username, timestamp))
			LOGGING_STREAM.write(traceback.format_exc())
			return 1
		
	LOGGING_STREAM.write("\n")
	return 0
	



	
if __name__ == "__main__": 
	sys.exit(main())
