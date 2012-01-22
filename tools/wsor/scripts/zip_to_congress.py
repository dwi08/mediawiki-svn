
"""
    WSOR script that determines how many vandals go on being vandals after there first vandal revert
"""


""" Script meta """
__author__ = "Ryan Faulkner"
__date__ = "January 21st, 2012"


""" Import python base modules """
import sys, logging, csv
import settings

""" Modify the classpath to include local projects """
sys.path.append(settings.__project_home__)

""" Import Analytics modules """
import classes.DataLoader as DL
import classes.Helper as Hlp

"""
    Execution body of main
"""
def main(args):
    
    """ Configure the logger """
    LOGGING_STREAM = sys.stderr
    logging.basicConfig(level=logging.DEBUG, stream=LOGGING_STREAM, format='%(asctime)s %(levelname)-8s %(message)s', datefmt='%b-%d %H:%M:%S')
       
    # Use a dataloader to extract zipcode lookup counts from db1008
    dl = DL.DataLoader()
    sql = "select date_format(request_time,'%Y%m%d%H'), zip_code, count(*) from SOPA_zip_codes " + \
    "where zip_code regexp '[0-9]{5}' and request_time >= '20120118050000' and request_time < '20120119050000' group by 1, 2 order by 2, 1"
    
    # CSV with zips + congressional districts
    # Generate counts for congressional districts
    
    zip_to_cd = csv.reader(open('zipProportions.tsv', 'rb'), delimiter=' ', quotechar='|')
    # cd_counts_csv = csv.writer(open('cd_counts.csv', 'wb'), delimiter=' ', quotechar='|', quoting=csv.QUOTE_MINIMAL)
    cd_counts_tsv = open('cd_counts.tsv', 'wb')
    
    zip_index = 0
    cd_index = 1
    weight_index = 2
    
    # Stores congressional district look-up counts
    cd_counts = Hlp.AutoVivification()
    zip_counts = Hlp.AutoVivification()
            
    # Populate zip counts from table
    logging.info('Generating zipcode results from table ...')
    results = dl.execute_SQL(sql)
    for row in results:
        zip_hash = row[1]
        timestamp = row[0]
        try:                    
            zip_counts[zip_hash][timestamp] = zip_counts[zip_hash][timestamp] + int(row[2])    
        except:
            zip_counts[zip_hash][timestamp] = int(row[2])

    # Populate dict with congressional district counts
    for row_cd in zip_to_cd:
        
        cd_hash = row_cd[cd_index]
        zip_hash = row_cd[zip_index]
        district_weight = row_cd[weight_index]
        
        cd_hash = convert_congressional_district_name(cd_hash)

        # logging.info('%s - %s - %s' % (cd_hash, zip_hash, district_weight))
        
        # Generate the count corresponding to the zip code and the weight 
        
        for timestamp in zip_counts[zip_hash]:
            
            # extract count and weight
            count = zip_counts[zip_hash][timestamp]
            count = int(float(count) * float(district_weight))
            
            try:        
                cd_counts[cd_hash][timestamp] = cd_counts[cd_hash] + count    
            except:
                cd_counts[cd_hash][timestamp] = count
                
        # Process ZIP/Congress record

    # Write congressional district name and counts to csv     
    cd_keys = cd_counts.keys()
    for cd_ind in range(len(cd_keys)):
        cd_hash = cd_keys[cd_ind]
        ts_counts = cd_counts[cd_hash]
        ts_keys = ts_counts.keys()
        
        for ts_ind in range(len(ts_keys)):
            timestamp = ts_keys[ts_ind]
            count = ts_counts[timestamp]

            cd_counts_tsv.write(cd_hash + '\t' + timestamp + '00\t' + str(count) + '\n')
            # cd_counts_tsv.writerow([cd_hash, timestamp, count])
    
    cd_counts_tsv.close()
    
    return 0

"""
    Convert congressional district name from format 'XX-##' to 'XX_##' 
"""
def convert_congressional_district_name(name_str):
    
    try:
        parts = name_str.split('-')
        return parts[0] + '_' + parts[1]
    
    except:
        return name_str

"""
    Call main, exit when execution is complete
    
    Argument parsing (argparse) and pass to main

""" 
if __name__ == "__main__":
    sys.exit(main([]))
