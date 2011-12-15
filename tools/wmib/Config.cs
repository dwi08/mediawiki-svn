//This program is free software: you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation, either version 3 of the License, or
//(at your option) any later version.

//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

// Created by Petr Bena benapetr@gmail.com

using System;
using System.Collections.Generic;
using System.Text;
using System.Net;


namespace wmib
{
    public static class config
    {
        public static string text;
        private static void AddConfig(string a, string b)
        {
            text = text + "\n" + a + "=" + b + ";";
        }
        public static void Save()
        {
            text ="";
            AddConfig("username", username);
            AddConfig("password", password);
            AddConfig("network", network);
            AddConfig("nick", login);
            text = text + "\nchannels=";
            foreach (channel current in channels)
            {
                text = text + current.name + ",\n";
            }
            text = text + ";";
            System.IO.File.WriteAllText("wmib", text);
        }
        public class channel
        {
            public string name;
            public bool logged;
            public string log;
            public irc.dictionary Keys;
            private string conf;
            public irc.IRCTrust Users;
            public string keydb = "";
            private void AddConfig(string a, string b)
            {
                conf = conf + "\n" + a + "=" + b + ";";
            }
            public void LoadConfig()
            {
                string conf_file = name +  ".setting";
                if (!System.IO.File.Exists(conf_file))
                {
                    System.IO.File.WriteAllText(conf_file, "");
                    Program.Log("Creating datafile for channel " + name);
                    return;
                }
                conf = System.IO.File.ReadAllText(conf_file);
                if (config.parseConfig(conf, "keysdb") != "")
                {
                    keydb = (config.parseConfig(conf, "keysdb"));
                }
                if (config.parseConfig(conf, "logged") != "")
                {
                    logged = bool.Parse(config.parseConfig(conf, "logged"));
                }
            }

            public void SaveConfig()
            {
                conf = "";
                AddConfig("keysdb", keydb);
                AddConfig("logged", logged.ToString());
                System.IO.File.WriteAllText(name + ".setting", conf);
            }
            public channel(string Name)
            {
                conf = "";
                keydb = Name + ".db";
                logged = true;
                name = Name;
                LoadConfig();
                if (!System.IO.Directory.Exists("log"))
                {
                    System.IO.Directory.CreateDirectory("log");
                }
                if (!System.IO.Directory.Exists("log/" + Name))
                {
                    System.IO.Directory.CreateDirectory("log/" + Name);
                }
                Keys = new irc.dictionary(keydb, name);
                log = "log/" + Name + "/";
                Users = new irc.IRCTrust(name);
            }
        }

        public static string parseConfig(string text, string name)
        {
            if (text.Contains(name))
            {
                string x = text;
                x = text.Substring(text.IndexOf(name + "=")).Replace(name + "=", "");
                x = x.Substring(0, x.IndexOf(";"));
                return x;
            }
            return "";
        }

        public static void Load()
        {
            text = System.IO.File.ReadAllText("wmib");
            foreach (string x in parseConfig(text, "channels").Replace("\n", "").Split(','))
            {
                string name=x.Replace(" ", "");
                if (!(name == ""))
                {
                    channels.Add(new channel(name));
                }
            }
            username = parseConfig(text, "username");
            network = parseConfig(text, "network");
            login = parseConfig(text, "nick");
            password = parseConfig(text, "password");
            if (!System.IO.Directory.Exists(config.DumpDir))
            {
                System.IO.Directory.CreateDirectory(config.DumpDir);
            }
        }
        /// <summary>
        /// Network
        /// </summary>
        public static string network = "irc.freenode.net";
        /// <summary>
        /// Nick name
        /// </summary>
        public static string username = "wm-bot";
        public static string login = "";
        public static string password = "";
        public static string DumpDir = "dump";
        /// <summary>
        /// Version
        /// </summary>
        public static string version = "wikimedia bot v. 1.1.4";
        public static string separator = "|";
        /// <summary>
        /// User name
        /// </summary>
        public static string name = "wm-bot";
        /// <summary>
        /// Channels
        /// </summary>
        public static List<channel> channels = new List<channel>();
    }
}
