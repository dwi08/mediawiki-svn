from urlparse import urlparse
from datetime import datetime
from wurfl import devices
from pywurfl.algorithms import TwoStepAnalysis
import GeoIP

from pipeline import DataPipeline
from dataset import UserAgentObservation

search_algorithm = TwoStepAnalysis(devices)

class Variable:
	def __init__(self, name, func_name, location, store=True):
		self.name = name 
		self.func_name = func_name
		self.location = location
		self.store = store

class UserAgentPipeline(DataPipeline):
	def __init__(self, observation_class, filename, process_id, number_of_fields=144):
		self.start = datetime.now()
		self.output_counter = 0
		self.variables = {
					'language_code': ['language_code', '_determine_language_code', 8],
					'project': ['project', '_determine_project', 8],
					'geography': ['geography', '_determine_geography', 4],	#ip address
					'timestamp': ['timestamp', '_determine_date', 2],
					'user_agent': ['user_agent', '_convert_to_unicode', 13],
					}
		if self.variables == {}:
			raise Exception('''You have to define both a mapping of your 
				properties and the functions that generate them and a mapping 
				of your raw input data and their position in a raw data line.''')
		self.vars=  {}
		for key, value in self.variables.iteritems():
			self.vars[key]= Variable(*value)
			
		self.observations = {}
		self.observation_class = observation_class
		self.filename = filename
		self.gi = GeoIP.new(GeoIP.GEOIP_MEMORY_CACHE)
		self.process_id = process_id
		self.number_of_fields = number_of_fields
	
	def _prepare_obs(self, obs):
		return obs.strip().split(' ')
	
	def _generate_key(self, vars):
		value = '_'.join(vars.values())
		return hash(value)
	
	def _determine_language_code(self, url):
		url	= urlparse(url)
		return url.netloc.split('.')[0]
	
	def _determine_project(self, url):
		url	= urlparse(url)
		return url.netloc.split('.')[-2]

	def _determine_geography(self, ip):
		geography = self.gi.country_code_by_addr(ip)
		if not geography:
			return 'unknown_geography'
		else:
			return geography
	
	def _determine_date(self, timestamp):
		'''
		timestamp format: 2011-08-25T23:43:29.72
		'''
		return timestamp[:10]
	
	def _add_observation(self, key, vars):
		'''
		Vars should be a dictionary
		'''
		if not isinstance(vars, dict):
			raise Exception('You have to feed an instance of a Datamodel a dictionary.')

		obs = self.observations.get(key, UserAgentObservation(**vars))
		obs.count +=1
		self.observations[key] =obs

	def _convert_to_unicode(self, obs):
		return obs.decode('utf-8')
		
	def _total_pageviews(self):
		pageviews = 0
		for obs in self.observations.values():
			pageviews += obs.count
		return pageviews

	
	def transform(self, obs):
		vars = {}
		for key in self.vars.keys():
			try:
				func_name = getattr(self.vars[key], 'func_name')
				location = getattr(self.vars[key], 'location')
				if func_name:
					func = getattr(self, func_name)
			except:
				raise Exception('You have not defined function %s' % (func_name))
			#print key, obs[location]
			vars[key] = func(obs[location])
		return vars
		
	def aggregate(self, obs):
		obs = self._prepare_obs(obs)
		vars = self.transform(obs)
		key = self._generate_key(vars)
		self._add_observation(key, vars)
	
	def post_processing(self):
		for obs in self.observations.values():
			obs.device = devices.select_ua(obs.user_agent, search=search_algorithm)
	
	def pre_processing(self, line):
		count = line.count(' ')
		if count == self.number_of_fields:
			return line
		else:
			return None
	
	def load(self):
		fh = open('output/chunk-%s_process-%s.tsv' % (self.output_counter,self.process_id), 'w')
		observations = self.observations.values()
		for obs in observations:
			try:
				fh.write('%s\t%s\t%s\t%s\t%s\t%s\t%s\n' % (obs.count, obs.device.brand_name, obs.device.model_name, obs.geography, obs.language_code, obs.project, obs.timestamp))
			except:
				pass
		fh.close()
		self.output_counter+=1
		
	def shutdown(self):
		print 'Total number of observation instances: %s' % (len(self.observations.keys()))
		print 'Total number of pageviews: %s' % self._total_pageviews()
		print 'Total processing time: %s' % (datetime.now() - self.start)
	
	def run(self):
		for line in self.decompress():
			line = self.pre_processing(line)
			if line:
				self.aggregate(line)
	
		self.post_processing()
		self.load()
		self.shutdown()


def main(filename, process_id):
	pipeline = UserAgentPipeline(UserAgentObservation, filename, process_id)
	pipeline.run()


def debug():
	pl = UserAgentPipeline(UserAgentObservation, 'mobile.log-20110826.gz', 0, 13)
	pl.run()
	
if __name__ == '__main__':
	#main()
	debug()
