from abc import ABCMeta
import subprocess
import sys

class DataPipeline(object):	
	__metaclass__ = ABCMeta

	def decompress(self):
		p = subprocess.Popen(['gunzip','-c', self.filename], stdout=subprocess.PIPE, shell=False)
		for line in iter(p.stdout.readline, ""):
			yield line
		
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



