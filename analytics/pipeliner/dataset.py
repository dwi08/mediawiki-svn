from abc import ABCMeta

class Observation(object):
	def __init__(self, *args):
		self.count = 0

# class Datamodel(object):
# 	def __init__(self, *args):
# 		self.obs = {}
# 	
# 	def add_observation(self, key, obs):
# 		return
		

class UserAgentObservation:
	__metaclass__ = ABCMeta
	
	def __init__(self, **kwargs):
		self.count=0
		for key, value in kwargs.iteritems():
			setattr(self, key, value)
	
	def __str__(self):
		if self.device:
			return '%s observations using %s:%s in %s for %s%s on %s' % (self.count, self.device.brand_name, self.device.model_name, self.geography, self.language_code, self.project, self.timestamp)
		else:
			return '%s observations in %s for %s%s on %s' % (self.count, self.geography, self.language_code, self.project, self.timestamp)

# class UserAgentDatamodel(object):
# 	__metaclass__ = ABCMeta
# 	
# 	def __init__(self, full_string, key):
# 		self.full_string = full_string
# 		self.key = key
# 	
# 	def __str__(self):
# 		return self.full_string
# 	
# 	def add_observation(self, key, vars):
# 		'''
# 		Vars should be a dictionary
# 		'''
# 		if not isinstance(vars, dict):
# 			raise Exception('You have to feed an instance of a Datamodel a dictionary.')
# 			
# 		obs = self.obs.get(key, UserAgentObservation(vars))
# 		obs.count +=1
# 		self.obs[key] =obs

UserAgentObservation.register(Observation)

		
