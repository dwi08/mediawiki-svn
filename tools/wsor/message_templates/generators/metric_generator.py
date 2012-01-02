class MetricGenerator:
	def __init__(self): pass
	def headers(self): raise NotImplementedError()
	def values(self, username, timestamp): raise NotImplementedError()
