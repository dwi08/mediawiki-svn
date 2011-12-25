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
using System.IO;

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
            public dictionary Keys;

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
                conf += "\n" + a + "=" + b + ";";
            }

            /// <summary>
            /// Load config of channel :)
            /// </summary>
            public void LoadConfig()
            {
                string conf_file = name + ".setting";
                if (!File.Exists(conf_file))
                {
                    File.WriteAllText(conf_file, "");
                    Program.Log("Creating datafile for channel " + name);
                    return;
                }
                conf = File.ReadAllText(conf_file);
                if (parseConfig(conf, "keysdb") != "")
                {
                    keydb = (parseConfig(conf, "keysdb"));
                }
                if (parseConfig(conf, "logged") != "")
                {
                    logged = bool.Parse(parseConfig(conf, "logged"));
                }
                if (parseConfig(conf, "infodb") != "")
                {
                    info = bool.Parse(parseConfig(conf, "infodb"));
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
                File.WriteAllText(name + ".setting", conf);
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
                logged = false;
                name = Name;
                LoadConfig();
                if (!Directory.Exists("log"))
                {
                    Directory.CreateDirectory("log");
                }
                if (!Directory.Exists("log/" + Name))
                {
                    Directory.CreateDirectory("log/" + Name);
                }
                Keys = new dictionary(keydb, name);
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
            text += text + "\nchannels=";
            foreach (channel current in channels)
            {
                text = text + current.name + ",\n";
            }
            text = text + ";";
            File.WriteAllText("wmib", text);
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
            try
            {
                text = File.ReadAllText("wmib");
                foreach (string x in parseConfig(text, "channels").Replace("\n", "").Split(','))
                {
                    string name = x.Replace(" ", "");
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
            }
            catch (Exception ex)
            {
                irc.handleException(ex);
            }
                if (!Directory.Exists(config.DumpDir))
                {
                    Directory.CreateDirectory(config.DumpDir);
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

        public static string debugchan = null;

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
