import os
from multiprocessing import Process, Queue, cpu_count

import argparse


def _check_folder_exists(path):
	if os.path.exists(path):
		return path
	else:
		raise Exception('Please enter a valid source.')

def _prepare_target(pipeline):
	try:
		module = __import__('%s' % pipeline)
	except ImportError, e:
		raise Exception('It does not seem that %s has a function called main().\n The full error is: \n %s.' % (pipeline, e))
	func = getattr(module, 'main')
	return func

	

def main(args):
	'''
	This function initializes the multiprocessor, and loading the queue with
	files
	'''

	queue = Queue()
	target = _prepare_target(args.pipeline)
	
	files = os.listdir(args.source)
		
	count_files=0
	for filename in files:
		filename = os.path.join(args.source, filename)
		if filename.endswith('gz'):
			print filename
			count_files+=1
			queue.put(filename)

	if count_files > cpu_count():
		processors = cpu_count() - 1
	else:
		processors = count_files

	for x in xrange(processors):
		print 'Inserting poison pill %s...' % x
		queue.put(None)

	pipelines = [Process(target=target, args=[queue])
				  for process_id in xrange(processors)]
	
	queue.close()
	for pipeline in pipelines:
		pipeline.start()



if __name__ == '__main__':
	parser = argparse.ArgumentParser(description='Generic DataPipeline Cruncher')

	parser.add_argument('--source', '-s', metavar='source', action='store', type=_check_folder_exists,
					   help='The full path where the input files are stored')
	
	parser.add_argument('--pipeline', '-p', metavar='pipeline', action='store',
					   help='The filename of the script that does the actual heavy lifting.')
	

	args = parser.parse_args()
	main(args)