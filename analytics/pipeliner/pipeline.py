from abc import ABCMeta
import zlib
import sys

READ_BLOCK_SIZE = 1024*8


class DataPipeline(object):	
	__metaclass__ = ABCMeta
		
	def decompress(self):
		fh = open(self.filename, 'rb')
		d = zlib.decompressobj(16+zlib.MAX_WBITS)
		data = ''
		while True:
			raw_data = fh.read(READ_BLOCK_SIZE)
			data += d.decompress(raw_data)
			if not data:
				break
			elif not data.endswith('\n'):
				position = data.rfind('\n') +1
				lines = data[:position]
				lines = lines.split('\n')
				data=data[position:]
			else:
				lines = data
			yield lines
		
	def extract(self):
		while True:
			line = sys.stdin.readline()
			if not line:
				break
			self.aggregate(line)
	
	
	def aggregate(self, obs):
		return

	def transform(self, obs):
		return
	
	def load(self):
		return
	
	def run(self):
		return
	
	def post_processing(self):
		return

	def pre_processing(self):
		return

	def _prepare_obs(self, obs):
		return



