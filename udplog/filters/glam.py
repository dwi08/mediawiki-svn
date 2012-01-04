import re
import sys
nara = re.compile('_(NARA|National_Archives)_.*\.(jpg|tif)')

'''
This is a simple GLAM UDP filter that will filter images that are part of
the National Archives and Records Administration project. 
For questions, email Diederik van Liere at dvanliere@wikimedia.org

'''


def test():
    t1 = 'http://commons.wikimedia.org/wiki/File:German_prisoners_in_a_French_prison_camp._French_Pictorial_Service.,_1917_-_1919_-_NARA_-_533724.tif'
    t2 = 'http://commons.wikimedia.org/wiki/File:Aerial_view_of_ruins_of_Vaux,_France,_1918,_ca._03-1918_-_ca._11-1918_-_NARA_-_512862.tif'
    t3 = 'http://commons.wikimedia.org/wiki/File:The_patient%27s_skin_is_burned_in_a_pattern_corresponding_to_the_dark_portions_of_a_kimono_-_NARA_-_519686.jpg'
    t4 = 'http://commons.wikimedia.org/wiki/File:Ansel_Adams_-_National_Archives_79-AAB-02.jpg'
    t5 = 'http://commons.wikimedia.org/wiki/File:THE_CENTRAL_POLICE_CONTROL_STATION,_MANNED_24_HOURS_A_DAY_CONTROLS_ALL_TRAFFIC_LIGHTS,_RECEIVES_REMOTE_TV_INPUTS_FROM..._-_NARA_-_551905.tif'
    
    pics = [t1,t2,t3,t4,t5]
    for pic in pics:
        match = re.search(nara, pic)
    
def main():
    while True:
        line = sys.stdin.readline()
        match = re.search(nara, pic)
        if match:
            sys.stdout.write(line)

if __name__ == '__main__':
    #test()
    main()