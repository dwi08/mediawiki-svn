from .edit_counts import EditCounts
from .talk import Talk
from .blocks import Blocks
from .warnings import Warnings
from .metric_generator import MetricGenerator

GENERATORS = {
	'editcounts': EditCounts,
	'talk': Talk,
	'blocks': Blocks,
	'warnings': Warnings
}

class Metrics(MetricGenerator):
	
	def __init__(self, generators):
		self.generators = list(generators)
	
	def headers(self):
		row = ['username', 'timestamp']
		for generator in self.generators:
			row.extend(generator.headers())
		
		return row
	
	def values(self, username, timestamp):
		row = [username, timestamp]
		for generator in self.generators:
			row.extend(generator.values(username, timestamp))
		
		return row
