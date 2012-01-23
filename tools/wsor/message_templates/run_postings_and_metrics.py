
"""
    Script to execute a series of commands to perform data retrieval for template tests and  

"""


""" Script meta """
__author__ = "Ryan Faulkner"
__date__ = "January 12th, 2012"


""" Import python base modules """
import sys, logging, datetime, settings, os


""" CONFIGURE THE LOGGER """
LOGGING_STREAM = sys.stderr
logging.basicConfig(level=logging.DEBUG, stream=LOGGING_STREAM, format='%(asctime)s %(levelname)-8s %(message)s', datefmt='%b-%d %H:%M:%S')

"""
    Define script usage
"""
class Usage(Exception):
    
    def __init__(self, msg):
        self.msg = msg

"""
    Execution body of main
"""
def main(args):
    
    test_keys = ['Huggle_3_z60', 'Huggle_3_z61', 'Huggle_3_z62', 'Huggle_3_z63', 'Huggle_3_z64', 'Huggle_3_z65', 'Huggle_3_z66', \
                 'Huggle_3_z67', 'Huggle_3_z68', 'Huggle_3_z69', 'Huggle_3_z70', 'Huggle_3_z71', 'Huggle_3_z72', 'Huggle_3_z73', \
                 'Huggle_3_z74', 'Huggle_3_z75', 'Huggle_3_z76', \
                 'Huggle_3_z77', 'Huggle_3_z1', 'Huggle_3_z2', 'Huggle_3_z3'] # "Huggle 1 Portugese"
    
    gather_data = {'Huggle_3_z60' : False, 'Huggle_3_z61' : False, 'Huggle_3_z62' : False, 'Huggle_3_z63' : False, 'Huggle_3_z64' : False, 'Huggle_3_z65' : False, 'Huggle_3_z66' : False, 'Huggle_3_z67' : False, \
                   'Huggle_3_z68' : False, 'Huggle_3_z69' : False, 'Huggle_3_z70' : False, 'Huggle_3_z71' : False, 'Huggle_3_z72' : False, 'Huggle_3_z73' : False, 'Huggle_3_z74' : False, 'Huggle_3_z75' : False, \
                   'Huggle_3_z76' : False, 'Huggle_3_z77' : False, \
                   'Huggle_3_z1' : True, 'Huggle_3_z2' : True, 'Huggle_3_z3' : True}
    
    templates = {'Huggle_3_z60' : 'z60', 'Huggle_3_z61' : 'z61', 'Huggle_3_z62' : 'z62', 'Huggle_3_z63' : 'z63', 'Huggle_3_z64' : 'z64', 'Huggle_3_z65' : 'z65', 'Huggle_3_z66' : 'z66', 'Huggle_3_z67' : 'z67', \
                 'Huggle_3_z68' : 'z68', 'Huggle_3_z69' : 'z69', 'Huggle_3_z70' : 'z70', 'Huggle_3_z71' : 'z71', 'Huggle_3_z72' : 'z72', 'Huggle_3_z73' : 'z73', 'Huggle_3_z74' : 'z74', 'Huggle_3_z75' : 'z75', \
                 'Huggle_3_z76' : 'z76', 'Huggle_3_z77' : 'z77', \
                 'Huggle_3_z1' : 'z1', 'Huggle_3_z2' : 'z2', 'Huggle_3_z3' : 'z3'}
    
    start_times = {'Huggle_3_z60' : '20111018000000', 'Huggle_3_z61' : '20111018000000', 'Huggle_3_z62' : '20111018000000', 'Huggle_3_z63' : '20111018000000', 'Huggle_3_z64' : '20111018000000', 'Huggle_3_z65' : '20111018000000', \
                   'Huggle_3_z66' : '20111018000000', 'Huggle_3_z67' : '20111018000000', 'Huggle_3_z68' : '20111018000000', 'Huggle_3_z69' : '20111018000000', 'Huggle_3_z70' : '20111018000000', 'Huggle_3_z71' : '20111018000000', \
                   'Huggle_3_z72' : '20111018000000', 'Huggle_3_z73' : '20111018000000', 'Huggle_3_z74' : '20111018000000', 'Huggle_3_z75' : '20111018000000', 'Huggle_3_z76' : '20111018000000', 'Huggle_3_z77' : '20111018000000', \
                   'Huggle_3_z1' : '20111027000000', 'Huggle_3_z2' : '20111027000000', 'Huggle_3_z3' : '20111027000000'}
     
    end_times = {'Huggle_3_z60' : '20111119000000', 'Huggle_3_z61' : '20111119000000', 'Huggle_3_z62' : '20111119000000', 'Huggle_3_z63' : '20111119000000', 'Huggle_3_z64' : '20111119000000', 'Huggle_3_z65' : '20111119000000', \
                   'Huggle_3_z66' : '20111119000000', 'Huggle_3_z67' : '20111119000000', 'Huggle_3_z68' : '20111119000000', 'Huggle_3_z69' : '20111119000000', 'Huggle_3_z70' : '20111119000000', 'Huggle_3_z71' : '20111119000000', \
                   'Huggle_3_z72' : '20111119000000', 'Huggle_3_z73' : '20111119000000', 'Huggle_3_z74' : '20111119000000', 'Huggle_3_z75' : '20111119000000', 'Huggle_3_z76' : '20111119000000', 'Huggle_3_z77' : '20111119000000', 
                   'Huggle_3_z1' : '20111128000000', 'Huggle_3_z2' : '20111128000000', 'Huggle_3_z3' : '20111128000000'} 
    
    # Run postings and metrics
    
    generator = 'editcounts'
    postings_cmd = './postings -h db1047 --start=%(start_time)s --end=%(end_time)s --comment="\(\[\[WP:HG\|HG\]\]\)" --message="{{%(template)s}}" --outfilename postings_%(file_name)s.tsv'
    metrics_cmd = 'cat ./output/postings_%(file_name)s.tsv | ./metrics -h db1047 --header --outfilename metrics_%(file_name)s.tsv %(generator)s'
    
    for key in test_keys:   
             
        if gather_data[key]:
            logging.info('Generating postings for %s' % key)
            filename_part = start_times[key][4:8] + '_' + end_times[key][4:8] + '_' + templates[key]

            # os.system(postings_cmd % {'start_time' : start_times[key], 'end_time' : end_times[key], 'template' : templates[key], 'file_name' : filename_part})
            os.system(metrics_cmd % {'file_name' : filename_part, 'file_name' : filename_part, 'generator' : generator})
        else:
            logging.info('Skipping postings for %s' % key)
            
    return 0

"""
    Call main, exit when execution is complete

""" 
if __name__ == "__main__":
    main([])