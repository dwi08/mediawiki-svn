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
        public class channel
        {
            /// <summary>
            /// Channel name
            /// </summary>
            public string name;
            public bool logged;
            /// <summary>
            /// Log
            /// </summary>
            public string log;
            public bool info;
            /// <summary>
            /// Keys
            /// </summary>
            public irc.dictionary Keys;
            /// <summary>
            /// Configuration text
            /// </summary>
            private string conf;
            /// <summary>
            /// Users
            /// </summary>
            public irc.IRCTrust Users;
            /// <summary>
            /// Path of db
            /// </summary>
            public string keydb = "";

            /// <summary>
            /// Add a line to config
            /// </summary>
            /// <param name="a">Name of key</param>
            /// <param name="b">Value</param>
            private void AddConfig(string a, string b)
            {
                conf = conf + "\n" + a + "=" + b + ";";
            }

            /// <summary>
            /// Load config of channel :)
            /// </summary>
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
                if (config.parseConfig(conf, "infodb") != "")
                {
                    info = bool.Parse(config.parseConfig(conf, "infodb"));
                }
            }

            /// <summary>
            /// Save config
            /// </summary>
            public void SaveConfig()
            {
                conf = "";
                AddConfig("infodb", info.ToString());
                AddConfig("logged", logged.ToString());
                AddConfig("keysdb", keydb);
                System.IO.File.WriteAllText(name + ".setting", conf);
            }

            /// <summary>
            /// Constructor
            /// </summary>
            /// <param name="Name">Channel</param>
            public channel(string Name)
            {
                conf = "";
                keydb = Name + ".db";
                info = true;
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

        /// <summary>
        /// Add line to the config file
        /// </summary>
        /// <param name="a"></param>
        /// <param name="b"></param>
        private static void AddConfig(string a, string b)
        {
            text = text + "\n" + a + "=" + b + ";";
        }

        public static void Save()
        {
            text = "";
            AddConfig("username", username);
            AddConfig("password", password);
            AddConfig("network", network);
            AddConfig("debug", debugchan);
            AddConfig("nick", login);
            text = text + "\nchannels=";
            foreach (channel current in channels)
            {
                text = text + current.name + ",\n";
            }
            text = text + ";";
            System.IO.File.WriteAllText("wmib", text);
        }

        /// <summary>
        /// Parse config data text
        /// </summary>
        /// <param name="text"></param>
        /// <param name="name"></param>
        /// <returns></returns>
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

        /// <summary>
        /// Load config of bot
        /// </summary>
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
            debugchan = parseConfig(text, "debug");
            password = parseConfig(text, "password");
            if (!System.IO.Directory.Exists(config.DumpDir))
            {
                System.IO.Directory.CreateDirectory(config.DumpDir);
            }
        }
        public static string text;
        /// <summary>
        /// Network
        /// </summary>
        public static string network = "irc.freenode.net";
        /// <summary>
        /// Nick name
        /// </summary>
        public static string username = "wm-bot";
        public static string debugchan = "";
        /// <summary>
        /// Login name
        /// </summary>
        public static string login = "";
        /// <summary>
        /// Login pw
        /// </summary>
        public static string password = "";
        /// <summary>
        /// Dump
        /// </summary>
        public static string DumpDir = "dump";
        /// <summary>
        /// Version
        /// </summary>
        public static string version = "wikimedia bot v. 1.1.4";
        /// <summary>
        /// Separator
        /// </summary>
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
