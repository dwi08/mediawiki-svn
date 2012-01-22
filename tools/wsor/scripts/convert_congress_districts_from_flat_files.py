from __future__ import division
import sys

valid_zip = r'[0-9]{5}'

#read in FIPS
fips_dict = {}
fips_file = open ("FIPS2AB.txt")
fip_line = fips_file.readline()
while fip_line:
	fips_data = fip_line.split("\t")
	fips_code = fips_data[1].strip()
	fips_state = fips_data[2].strip()
	fips_dict[fips_code] = fips_state
	fip_line = fips_file.readline()

#ZIP convert
zip_dict = {}
zip_file = open ("zcta_cd111_rel_10.txt")
zip_line = zip_file.readline() #header
zip_line = zip_file.readline()
while zip_line:
	zip_data = zip_line.split(",")
	zip_code = zip_data[0]
	state = "XX"
	if( zip_data[1] in fips_dict):
		state = fips_dict[zip_data[1]]
	
	dist = zip_data[2]
	if(zip_code not in zip_dict):
		zip_dict[zip_code] = []
	zip_dict[zip_code].append(state+"_"+dist)
	zip_line = zip_file.readline()

for zipline in zip_dict:
	for district in zip_dict[zipline]:
		print zipline, district, 1/len(zip_dict[zipline])


