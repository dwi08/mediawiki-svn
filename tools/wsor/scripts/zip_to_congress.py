
"""
    WSOR script that determines how many vandals go on being vandals after there first vandal revert
"""


""" Script meta """
__author__ = "Ryan Faulkner"
__date__ = "January 21st, 2012"


""" Import python base modules """
import sys, re, datetime, logging, csv
import settings

""" Modify the classpath to include local projects """
sys.path.append(settings.__project_home__)

""" Import Analytics modules """
from WSOR.scripts.classes.WSORSlaveDataLoader import VandalLoader
import classes.DataLoader as DL

"""
    Execution body of main
"""
def main(args):
    
    """ Configure the logger """
    LOGGING_STREAM = sys.stderr
    logging.basicConfig(level=logging.DEBUG, stream=LOGGING_STREAM, format='%(asctime)s %(levelname)-8s %(message)s', datefmt='%b-%d %H:%M:%S')
       
    # Use a dataloader to extract zipcode lookup counts from db1008
    dl = DL.DatLoader()
    sql = 'select count(*) from SOPA_zip_codes where zip_code = %s'
    
    # CSV with zips + congressional districts
    # Generate counts for congressional districts
    
    zip_to_cd = csv.reader(open('zip_to_cd.csv', 'rb'), delimiter=' ', quotechar='|')
    cd_counts = csv.writer(open('cd_counts.csv', 'wb'), delimiter=' ', quotechar='|', quoting=csv.QUOTE_MINIMAL)

    zip_index = 0
    cd_index = 1
    weight_index = 2
    
    # Stores congressional district look-up counts
    cd_counts = dict()
    
    # Populate dict with congressional district counts
    for row in zip_to_cd:
        
        cd_hash = row[cd_index]
        zip_hash = row[zip_index]

        cd_hash = convert_congressional_district_name(cd_hash)

        # Retrieve ZIPcode count from DB  
        result = dl.execute_SQL(sql % zip_hash)[0][0]        
        count = int(float(result) * float(row[weight_index]))
        
        try:
            cd_counts[cd_hash] = cd_counts[cd_hash] + count
        
        except:
            cd_counts[cd_hash] = count
            
        # Process ZIP/Congress record
        
    # Write congressional district name and counts to csv     
    for elem in cd_counts:
        cd_counts.writerow([elem,cd_counts[elem]])
        
    return 0

"""
    Convert congressional district name from format 'XX-##' to 'XX_##' 
"""
def convert_congressional_district_name(name_str):    
    parts = name_str.split('-')
    return parts[0] + '_' + parts[1]


"""
    Call main, exit when execution is complete
    
    Argument parsing (argparse) and pass to main

""" 
if __name__ == "__main__":
    sys.exit(main([]))
