// Parser code for nagios
// created by Petr Bena for wmf labs

// licensed under gpl v 3


#include <iostream>
#include <fstream>
#include <stdio.h>
#include <string>
using namespace std;

class instance
{
        public:
                string host;
                bool web;
                instance(string name)
                {
                        host = name;
                        web = false;
                }
};

int main (int argc, char* argv[])
{
        instance * config[8000];
        if ( argc < 2 )
        {
                printf ("Missing parameter!\n\n");
                return 2;
        }
        string filepath( argv[1] );
        filepath = filepath + "/novalist";
        //cout << filepath << endl;
        //cout << "Parsing" << endl;
        ifstream data( (char*) filepath.c_str() );
        string file;
        if (data.is_open())
        {
                // we can process the data file
                while ( data.good() )
                {
                        string filepart;
                        getline( data, filepart );
                        file = file + filepart;
                }
                data.close();
                size_t pos;
                string x1("\"instance_name\"");
                string x2("");
                string x3("generic::webserver::");
                string x4("misc::apache");
                int curr = 0;
                pos = file.find(x1);
                if (pos != string::npos)
                {
                        bool done = false;
                        pos = file.find(x1);
                        file = file.substr(pos);
                        while (!done)
                        {
                                string name = file.substr(x1.length() + 2);
                                name = name.substr(0, name.find("\"", 4));
                                if ( name.find(" \"valueType") == string::npos )
                                {
                                        name = name.substr(1);
                                        cout << name << endl;
                                        config[curr] = new instance(  name  );
                                        string data = file.substr(0, file.find("}"));
                                        if (data.find(x4) != string::npos || data.find(x3) != string::npos)
                                        {
                                                config[curr]->web=true;
                                        }
                                        curr++;
                                }
                                pos = file.find(x1, 20);
                                if ( pos != string::npos )
                                {
                                        file = file.substr(pos);
                                } else
                                {
                                        done = true;
                                }
                        }
                        string web_i;
                        string ssh_i;
                        int id = 0;
                        while (id < curr)
                        {
                                if ( config[id]->web == true )
                                {
                                        web_i = web_i + config[id]->host + ",";
                                }
                                ssh_i = ssh_i + config[id]->host + ",";
                                id++;
                        }
                        ssh_i = ssh_i.substr(0, ssh_i.length() -1 );
                        if (web_i.length() != 0)
                        {
                                web_i = web_i.substr(0, web_i.length() -1 );
                        }
                        cout << "SSH:" << endl << ssh_i << endl;
                        cout << "WWW:" << endl << web_i << endl;
                }
                else
                {
                        cout << "Error: file is broken!" << endl;
                }
        }
        else
        {
                cout << "Error: unable to open the data file!" << endl;
        }

        return 0;
}

