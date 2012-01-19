import os
from multiprocessing import Process, JoinableQueue, cpu_count
from datetime import datetime

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

	
def start_pipeline(queue, target, process_id):
	while True:
		filename = queue.get()
		queue.task_done()
		if not filename:
			print '%s files left in the queue' % queue.qsize()
			break
		t0 = datetime.now()
		target(filename, process_id)
		t1 = datetime.now()
		print 'Worker %s: Processing of %s took %s' % (process_id, filename, (t1 - t0))
		print 'There are %s files left in the queue' % (queue.qsize())

		
		
def main(args):
	'''
	This function initializes the multiprocessor, and loading the queue with
	files
	'''

	queue = JoinableQueue()
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

	pipelines = [Process(target=start_pipeline, args=[queue, target, process_id])
				  for process_id in xrange(processors)]
	
	
	for pipeline in pipelines:
		pipeline.start()
	
	queue.join()


if __name__ == '__main__':
	parser = argparse.ArgumentParser(description='Generic DataPipeline Cruncher')

	parser.add_argument('--source', '-s', metavar='source', action='store', type=_check_folder_exists,
					   help='The full path where the input files are stored')
	
	parser.add_argument('--pipeline', '-p', metavar='pipeline', action='store',
					   help='The filename of the script that does the actual heavy lifting.')
	

	args = parser.parse_args()
	main(args)