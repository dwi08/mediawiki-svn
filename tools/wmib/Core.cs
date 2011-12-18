//This program is free software: you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation, either version 3 of the License, or
//(at your option) any later version.

//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

using System;
using System.Collections.Generic;
using System.IO;

namespace wmib
{
    public class misc
    { 
        public static bool IsValidRegex(string pattern)
        {
            if (pattern == null) return false;

            try
            {
                System.Text.RegularExpressions.Regex.Match("", pattern);
            }
            catch (ArgumentException)
            {
                return false;
            }

            return true;
        }
    }
    public class irc
    {
        private static System.Net.Sockets.NetworkStream data;
        public static System.Threading.Thread dumphtmt;
        public static System.Threading.Thread check_thread;
        public static StreamReader rd;
        private static StreamWriter wd;
        private static List<user> User = new List<user>();

        public class messages
        {
            public static readonly string PermissionDenied = "Permission denied";
            public static readonly string Authorization = "You are not authorized to perform this, sorry";
        }

        public class user
        {
            /// <summary>
            /// Regex
            /// </summary>
            public string name;
            /// <summary>
            /// Level
            /// </summary>
            public string level;
            /// <summary>
            /// Constructor
            /// </summary>
            /// <param name="level"></param>
            /// <param name="name"></param>
            public user(string level, string name)
            {
                this.level = level;
                this.name = name;
            }
        }

        public class RegexCheck
        {
            public string value;
            public string regex;
            public bool searching;
            public bool result;

            public RegexCheck(string Regex, string Data)
            {
                result = false;
                value = Data;
                regex = Regex;
            }

            private void Run()
            {
                System.Text.RegularExpressions.Regex c = new System.Text.RegularExpressions.Regex(regex);
                result = c.Match(value).Success;
                searching = false;
            }

            public int IsMatch()
            {
                System.Threading.Thread quick = new System.Threading.Thread(Run);
                searching = true;
                quick.Start();
                int check = 0;
                while (searching)
                {
                    check++;
                    System.Threading.Thread.Sleep(10);
                    if (check <= 50) continue;
                    quick.Abort();
                    return 2;
                }
                return result ? 1 : 0;
            }
        }

        public class IRCTrust
        {
            /// <summary>
            /// List of all users in a channel
            /// </summary>
            private List<user> Users = new List<user>();
            /// <summary>
            /// Channel this class belong to
            /// </summary>
            public string _Channel;
            /// <summary>
            /// File where data are stored
            /// </summary>
            public string DataFile;

            /// <summary>
            /// Constructor
            /// </summary>
            /// <param name="channel"></param>
            public IRCTrust(string channel)
            {
                // Load
                DataFile = channel + "_user";
                if (!File.Exists(DataFile))
                {
                    // Create db
                    Program.Log("Creating user file for " + channel);
                    File.WriteAllText(DataFile, "");
                }
                string[] db = File.ReadAllLines(channel + "_user");
                _Channel = channel;
                foreach (string x in db)
                {
                    if (x.Contains(config.separator))
                    {
                        string[] info = x.Split(Char.Parse(config.separator));
                        string level = info[1];
                        string name = decode(info[0]);
                        Users.Add(new user(level, name));
                    }
                }
            }

            /// <summary>
            /// Save
            /// </summary>
            /// <returns></returns>
            public bool Save()
            {
                File.WriteAllText(DataFile, "");
                foreach (user u in Users)
                {
                    File.AppendAllText(DataFile, encode(u.name) + config.separator + u.level + "\n");
                }
                return true;
            }

            public static string normalize(string name)
            {
                name = System.Text.RegularExpressions.Regex.Escape(name);
                name = name.Replace("?", "\\?");
                return name;
            }

            /// <summary>
            /// New
            /// </summary>
            /// <param name="level">Level</param>
            /// <param name="user">Regex</param>
            /// <returns></returns>
            public bool addUser(string level, string user)
            {
                if (!misc.IsValidRegex(user))
                {
                    return false;
                }
                foreach (user u in Users)
                {
                    if (u.name == user)
                    {
                        return false;
                    }
                }
                Users.Add(new user(level, user));
                Save();
                return true;
            }

            /// <summary>
            /// Delete user
            /// </summary>
            /// <param name="user">Regex</param>
            /// <returns>bool</returns>
            public bool delUser(string user)
            {
                foreach (user u in Users)
                {
                    if (u.name == user)
                    {
                        if (u.level == "admin")
                        {
                            Message("This user is admin which can't be deleted from db, sorry", _Channel);
                            return true;
                        }
                        Users.Remove(u);
                        Save();
                        Message("User was deleted from access list", _Channel);
                        return true;
                    }
                }
                Message("User not found, sorry", _Channel);
                return true;
            }

            /// <summary>
            /// Return level
            /// </summary>
            /// <param name="level">User level</param>
            /// <returns>0</returns>
            private int getLevel(string level)
            {
                if (level == "admin")
                {
                    return 10;
                }
                if (level == "trusted")
                {
                    return 2;
                }
                return 0;
            }

            /// <summary>
            /// Return user object from a name
            /// </summary>
            /// <param name="user"></param>
            /// <returns></returns>
            public user getUser(string user)
            {
                user lv = new user("null", "");
                int current = 0;
                foreach (user b in Users)
                {
                    RegexCheck id = new RegexCheck(b.name, user);
                    if (id.IsMatch() == 1)
                    {
                        if (getLevel(b.level) > current)
                        {
                            current = getLevel(b.level);
                            lv = b;
                        }
                    }
                }
                return lv;
            }

            /// <summary>
            /// List all users to a channel
            /// </summary>
            public void listAll()
            {
                string users_ok = "";
                foreach (user b in Users)
                {
                    users_ok = users_ok + " " + b.name + ",";
                }
                Message("I trust: " + users_ok, _Channel);
            }

            /// <summary>
            /// Check if user match the necessary level
            /// </summary>
            /// <param name="level">Permission level</param>
            /// <param name="rights">Userrights</param>
            /// <returns></returns>
            public bool matchLevel(int level, string rights)
            {
                if (level == 2)
                {
                    return (rights == "admin");
                }
                if (level == 1)
                {
                    return (rights == "trusted" || rights == "admin");
                }
                return false;
            }

            /// <summary>
            /// Check if user is approved to do operation requested
            /// </summary>
            /// <param name="User">Username</param>
            /// <param name="Host">Hostname</param>
            /// <param name="command"></param>
            /// <returns></returns>
            public bool isApproved(string User, string Host, string command)
            {
                user current = getUser(User + "!@" + Host);
                if (current.level == "null")
                {
                    return false;
                }
                switch (command)
                {
                    case "alias_key":
                    case "delete_key":
                    case "trust":
                    case "info":
                    case "trustadd":
                    case "trustdel":
                        return matchLevel(1, current.level);
                    case "admin":
                    case "shutdown":
                        return matchLevel(2, current.level);
                }
                return false;
            }
        }

        public class dictionary
        {
            /// <summary>
            /// Data file
            /// </summary>
            public string datafile = "";
            // if we need to update dump
            public bool update = true;
            /// <summary>
            /// Locked
            /// </summary>
            public bool locked = false;
            public class item
            {
                /// <summary>
                /// Text
                /// </summary>
                public string text;
                /// <summary>
                /// Key
                /// </summary>
                public string key;
                public string user;
                public string locked;
                /// <summary>
                /// Constructor
                /// </summary>
                /// <param name="Key">Key</param>
                /// <param name="Text">Text of the key</param>
                /// <param name="User">User who created the key</param>
                /// <param name="Lock">If key is locked or not</param>
                public item(string Key, string Text, string User, string Lock = "false")
                {
                    text = Text;
                    key = Key;
                    locked = Lock;
                    user = User;
                }
            }
            public class staticalias
            {
                /// <summary>
                /// Name
                /// </summary>
                public string Name;
                /// <summary>
                /// Key
                /// </summary>
                public string Key;
                /// <summary>
                /// Constructor
                /// </summary>
                /// <param name="name">Alias</param>
                /// <param name="key">Key</param>
                public staticalias(string name, string key)
                {
                    Name = name;
                    Key = key;
                }
            }
            /// <summary>
            /// List of all items in class
            /// </summary>
            public List<item> text = new List<item>();
            /// <summary>
            /// List of all aliases we want to use
            /// </summary>
            public List<staticalias> Alias = new List<staticalias>();
            /// <summary>
            /// Channel name
            /// </summary>
            public string Channel;
            private bool running;
            private string search_key;
            /// <summary>
            /// Load it
            /// </summary>
            public void Load()
            {
                text.Clear();
                if (!File.Exists(datafile))
                {
                    // Create db
                    File.WriteAllText(datafile, "");
                }

                string[] db = File.ReadAllLines(datafile);
                foreach (string x in db)
                {
                    if (x.Contains(config.separator))
                    {
                        string[] info = x.Split(Char.Parse(config.separator));
                        string type = info[2];
                        string value = info[1];
                        string name = info[0];
                        if (type == "key")
                        {
                            string locked = info[3];
                            text.Add(new item(name, value, "", locked));
                        }
                        else
                        {
                            Alias.Add(new staticalias(name, value));
                        }
                    }
                }
            }

            /// <summary>
            /// Constructor
            /// </summary>
            /// <param name="database"></param>
            /// <param name="channel"></param>
            public dictionary(string database, string channel)
            {
                datafile = database;
                Channel = channel;
                Load();
            }

            /// <summary>
            /// Save to a file
            /// </summary>
            public void Save()
            {
                update = true;
                try
                {
                    File.WriteAllText(datafile, "");
                    foreach (staticalias key in Alias)
                    {
                        File.AppendAllText(datafile, key.Name + config.separator + key.Key + config.separator + "alias" + "\n");
                    }
                    foreach (item key in text)
                    {
                        File.AppendAllText(datafile, key.key + config.separator + key.text + config.separator + "key" + config.separator + key.locked + config.separator + key.user + "\n");
                    }
                }
                catch (Exception b)
                {
                    handleException(b, Channel);
                }
            }

            /// <summary>
            /// Get value of key
            /// </summary>
            /// <param name="key">Key</param>
            /// <returns></returns>
            public string getValue(string key)
            {
                foreach (item data in text)
                {
                    if (data.key == key)
                    {
                        return decode(data.text);
                    }
                }
                return "";
            }

            /// <summary>
            /// Print a value to channel if found this message doesn't need to be a valid command
            /// </summary>
            /// <param name="name">Name</param>
            /// <param name="user">User</param>
            /// <param name="chan">Channel</param>
            /// <param name="host">Host name</param>
            /// <returns></returns>
            public bool print(string name, string user, config.channel chan, string host)
            {
                if (!name.StartsWith("!"))
                {
                    return true;
                }
                name = name.Substring(1);
                if (name.Contains(" "))
                {
                    string[] parm = name.Split(' ');
                    if (parm[1] == "is")
                    {
                        config.channel _Chan = getChannel(Channel);
                        if (chan.Users.isApproved(user, host, "info"))
                        {
                            if (parm.Length < 3)
                            {
                                Message("It would be cool to give me also a text of key", Channel);
                                return true;
                            }
                            string key = name.Substring(name.IndexOf(" is") + 4);
                            setKey(key, parm[0], "");
                        }
                        else
                        {
                            Message("You are not autorized to perform this, sorry", Channel);
                        }
                        return false;
                    }
                    if (parm[1] == "alias")
                    {
                        config.channel _Chan = getChannel(Channel);
                        if (chan.Users.isApproved(user, host, "info"))
                        {
                            if (parm.Length < 3)
                            {
                                Message("It would be cool to give me also a name of key", Channel);
                                return true;
                            }
                             this.aliasKey(name.Substring(name.IndexOf(" alias") + 7), parm[0], "");
                        }
                        else
                        {
                            Message("You are not autorized to perform this, sorry", Channel);
                        }
                        return false;
                    }
                    if (parm[1] == "unalias")
                    {
                        config.channel _Chan = getChannel(Channel);
                        if (chan.Users.isApproved(user, host, "info"))
                        {
                            foreach (staticalias b in Alias)
                            {
                                if (b.Name == parm[0])
                                {
                                    Alias.Remove(b);
                                    Message("Alias removed", Channel);
                                    Save();
                                    return false;
                                }
                            }
                            return false;
                        }
                        else
                        {
                            Message("You are not autorized to perform this, sorry", Channel);
                        }
                        return false;
                    }
                    if (parm[1] == "del")
                    {
                        if (chan.Users.isApproved(user, host, "info"))
                        {
                            rmKey(parm[0], "");
                        }
                        else
                        {
                            Message("You are not autorized to perform this, sorry", Channel);
                        }
                        return false;
                    }
                }
                string User ="";
                if (name.Contains("|"))
                {
                    User = name.Substring(name.IndexOf("|"));
                    User = User.Replace("|", "");
                    User = User.Replace(" ", "");
                    name = name.Substring(0, name.IndexOf("|"));
                }
                string[] p = name.Split(' ');
                int parameters = p.Length;
                string keyv = getValue(p[0]);
                    if (!(keyv == ""))
                    {
                        if ( parameters > 1)
                        {
                            int curr = 1;
                            while ( parameters > curr )
                            {
                                keyv = keyv.Replace("$" + curr.ToString(), p[curr]);
                                curr++;
                            }
                        }
                        if (User == "")
                        {
                            Message(keyv, Channel);
                        } else
                        {
                            Message(User + ": " + keyv, Channel);
                        }
                        return true;
                    }
                foreach (staticalias b in Alias)
                {
                    if (b.Name == p[0])
                    {
                        keyv = getValue(b.Key);
                        if (keyv != "")
                        {
                            if ( parameters > 1)
                            {
                                int curr = 1;
                                while ( parameters > curr )
                                {
                                    keyv = keyv.Replace("$" + curr.ToString(), p[curr]);
                                    curr++;
                                }
                            }
                            if (User == "")
                            {
                                Message(keyv, Channel);
                            }
                            else
                            {
                                Message(User + ":" + keyv, Channel);
                            }
                            return true;
                        }
                    }
                }
                return true;
            }

            private void StartSearch()
            {
                System.Text.RegularExpressions.Regex value = new System.Text.RegularExpressions.Regex(search_key, System.Text.RegularExpressions.RegexOptions.Compiled);
                string results = "";
                foreach (item data in text)
                {
                    if (data.key == search_key || value.Match(data.text).Success)
                    {
                        results = results + data.key + ", ";
                    }
                }
                if (results == "")
                {
                    Message("No results found! :|", Channel);
                }
                else
                {
                    Message("Results: " + results, Channel);
                }
                running = false;
            }

            /// <summary>
            /// Search
            /// </summary>
            /// <param name="key">Key</param>
            /// <param name="Chan"></param>
            public void RSearch(string key, config.channel Chan)
            {
                if (!key.StartsWith("@regsearch"))
                {
                    return;
                }
                if (!misc.IsValidRegex(key))
                {
                    Message("This is pretty bad regex", Chan.name);
                    return;
                }
                if (key.Length < 11)
                {
                    Message("Could you please tell me what I should search for :P", Chan.name);
                    return;
                }
                search_key = key.Substring(11);
                running = true;
                System.Threading.Thread th = new System.Threading.Thread(new System.Threading.ThreadStart(StartSearch));
                th.Start();
                int check = 1;
                while (running)
                {
                    check++;
                    System.Threading.Thread.Sleep(10);
                    if (check > 80)
                    {
                        th.Abort();
                        Message("Search took more than 800 micro seconds try a better regex", Channel);
                        running = false;
                        return;
                    }
                }
            }

            public void Find(string key, config.channel Chan)
            {
                if (!key.StartsWith("@search"))
                {
                    return;
                }
                if (key.Length < 9)
                {
                    Message("Could you please tell me what I should search for :P", Chan.name);
                     return;
                }
                key = key.Substring(8);
                string results = "";
                foreach (item data in text)
                {
                    if (data.key == key || data.text.Contains(key))
                    {
                        results = results + data.key + ", ";
                    }
                }
                if (results == "")
                {
                    Message("No results found! :|", Chan.name);
                }
                else
                {
                    Message("Results: " + results, Chan.name);
                }
            }

            /// <summary>
            /// Save a new key
            /// </summary>
            /// <param name="Text">Text</param>
            /// <param name="key">Key</param>
            /// <param name="user">User who created it</param>
            public void setKey(string Text, string key, string user)
            {
                while (locked)
                {
                        System.Threading.Thread.Sleep(200);
                }
                try
                {
                        foreach (item data in text)
                        {
                            if (data.key == key)
                            {
                                Message("Key exist!", Channel);
                                return;
                            }
                        }
                        text.Add(new item(key, encode(Text), user, "false"));
                        Message("Key was added!", Channel);
                    Save();
                }
                catch (Exception b)
                {
                    handleException(b, Channel);
                }
            }

            /// <summary>
            /// Alias
            /// </summary>
            /// <param name="key">Key</param>
            /// <param name="al">Alias</param>
            /// <param name="user">User</param>
            public void aliasKey(string key, string al, string user)
            {
                foreach(staticalias stakey in this.Alias)
                {
                    if (stakey.Name == al)
                    {
                        Message("Alias is already existing!", Channel);
                        return;
                    }
                }
                this.Alias.Add(new staticalias(al, key));
                Message("Successfully created", Channel);
                Save();
            }

            public void rmKey(string key, string user)
            {
                while (locked)
                {
                        System.Threading.Thread.Sleep(200);
                }
                foreach (item keys in text)
                {
                    if (keys.key == key)
                    {
                        text.Remove(keys);
                        Message("Successfully removed " + key, Channel);
                        Save();
                        return;
                    }
                }
                Message("Unable to find the specified key in db", Channel);
            }
        }

        /// <summary>
        /// Ping
        /// </summary>
        public static void Ping()
        {
            while (true)
            {
                System.Threading.Thread.Sleep(20000);
                wd.WriteLine("PING :" + config.network);
                wd.Flush();
            }
        }

        /// <summary>
        /// Encode a data before saving it to a file
        /// </summary>
        /// <param name="text">Text</param>
        /// <returns></returns>
        public static string encode(string text)
        {
            return text.Replace(config.separator, "<separator>");
        }

        /// <summary>
        /// Nickserv
        /// </summary>
        /// <returns></returns>
        public static bool Authenticate()
        {
            if (config.login != "")
            {
                wd.WriteLine("PRIVMSG nickserv :identify " + config.login + " " + config.password);
                wd.Flush();
                System.Threading.Thread.Sleep(4000);
            }
            return true;
        }

        /// <summary>
        /// Decode
        /// </summary>
        /// <param name="text">String</param>
        /// <returns></returns>
        public static string decode(string text)
        {
            return text.Replace("<separator>", config.separator);
        }

        /// <summary>
        /// Exceptions :o
        /// </summary>
        /// <param name="ex">Exception pointer</param>
        /// <param name="chan">Channel name</param>
        public static void handleException(Exception ex, string chan = "")
        {
            if (config.debugchan != null)
            {
                Message("DEBUG Exception: " + ex.Message + " I feel crushed, uh :|", config.debugchan);
            }
            Program.Log(ex.Message + ex.Source + ex.StackTrace);
        }

        /// <summary>
        /// Get a channel object
        /// </summary>
        /// <param name="name">Name</param>
        /// <returns></returns>
        public static config.channel getChannel(string name)
        {
            foreach (config.channel current in config.channels)
            {
                if (current.name == name)
                {
                    return current;
                }
            }
            return null;
        }

        /// <summary>
        /// Convert the number to format we want to have in log
        /// </summary>
        public static string timedateToString(int number)
        {
            if (number <= 9 && number >= 0)
            {
                    return "0"+number.ToString();
            }
            return number.ToString();
        }

        /// <summary>
        /// Send a message to channel
        /// </summary>
        /// <param name="message">Message</param>
        /// <param name="channel">Channel</param>
        /// <returns></returns>
        public static bool Message(string message, string channel)
        {
            config.channel curr = getChannel(channel);
            wd.WriteLine("PRIVMSG " + channel + " :" + message);
            chanLog( message, curr, config.username, "" );
            wd.Flush();
            return true;
        }

        /// <summary>
        /// Change rights of user
        /// </summary>
        /// <param name="message">Message</param>
        /// <param name="channel">Channel</param>
        /// <param name="user">User</param>
        /// <param name="host">Host</param>
        /// <returns></returns>
        public static int modifyRights(string message, config.channel channel, string user, string host)
        {
            try
            {
                if (message.StartsWith("@trustadd"))
                {
                    string[] rights_info = message.Split(' ');
                    if (channel.Users.isApproved(user, host, "trustadd"))
                    {
                        if (rights_info.Length < 3)
                        {
                            Message("Wrong number of parameters, go fix it - example @trustadd regex (admin|trusted)", channel.name);
                            return 0;
                        }
                        if (!(rights_info[2] == "admin" || rights_info[2] == "trusted"))
                        {
                            Message("Unknown user level!", channel.name);
                            return 2;
                        }
                        if (rights_info[2] == "admin")
                        {
                            if (!channel.Users.isApproved(user, host, "admin"))
                            {
                                Message("Permission denied", channel.name);
                                return 2;
                            }
                        }
                        if (channel.Users.addUser(rights_info[2], rights_info[1]))
                        {
                            Message("Successfuly added " + rights_info[1], channel.name);
                        }
                    }
                    else
                    {
                        Message("You are not autorized to perform this, sorry", channel.name);
                    }
                }
                if (message.StartsWith("@trusted"))
                {
                    channel.Users.listAll();
                }
                if (message.StartsWith("@trustdel"))
                {
                    string[] rights_info = message.Split(' ');
                    if (rights_info.Length > 1)
                    {
                        string x = rights_info[1];
                        if (channel.Users.isApproved(user, host, "trustdel"))
                        {
                            channel.Users.delUser(rights_info[1]);
                            return 0;
                        }
                        else
                        {
                            Message("You are not autorized to perform this, sorry", channel.name);
                            return 0;
                        }
                    }
                    Message("Invalid user", channel.name);
                }
            }
            catch (Exception b)
            {
                handleException(b, channel.name);
            }
            return 0;
        }

        /// <summary>
        /// Log file
        /// </summary>
        /// <param name="message">Message</param>
        /// <param name="channel">Channel</param>
        /// <param name="user">User</param>
        /// <param name="host">Host</param>
        /// <param name="noac">Action (if true it's logged as message, if false it's action)</param>
        public static void chanLog(string message, config.channel channel, string user, string host, bool noac = true)
        {
            try
            {
                if (channel.logged)
                {
                    string log;
                    if (!noac)
                    {
                        log = "[" + timedateToString(System.DateTime.Now.Hour) + ":" + timedateToString(System.DateTime.Now.Minute) + ":" + timedateToString( System.DateTime.Now.Second) + "] * " + user + " " + message + "\n";
                    }
                    else
                    {
                        log = "[" + timedateToString( System.DateTime.Now.Hour) + ":" + timedateToString( System.DateTime.Now.Minute ) + ":" + timedateToString( System.DateTime.Now.Second) + "] " + "<" + user + ">\t " + message + "\n";
                    }
                    File.AppendAllText(channel.log + System.DateTime.Now.Year + System.DateTime.Now.Month + System.DateTime.Now.Day +".txt", log);
                }
            }
            catch (Exception er)
            { 
                // nothing
                Console.WriteLine(er.Message);
            }
        }

        /// <summary>
        /// Called on action
        /// </summary>
        /// <param name="message">Message</param>
        /// <param name="Channel">Channel</param>
        /// <param name="host">Host</param>
        /// <param name="nick">Nick</param>
        /// <returns></returns>
        public static bool getAction(string message, string Channel, string host, string nick)
        {
            config.channel curr = getChannel(Channel);
            chanLog(message, curr, nick, host, false);
            return false;
        }

        public static bool validFile(string name)
        {
            return !(name.Contains(" ") || name.Contains("?") || name.Contains("|") || name.Contains("/") || name.Contains("\\") || name.Contains(">") || name.Contains("<") || name.Contains("*"));
        }

        /// <summary>
        /// 
        /// </summary>
        /// <param name="chan">Channel</param>
        /// <param name="user">User</param>
        /// <param name="host">Host</param>
        /// <param name="message">Message</param>
        public static void addChannel(config.channel chan, string user, string host, string message)
        {
            try
            {
                if (message.StartsWith("@add"))
                {
                    if (chan.Users.isApproved(user, host, "admin"))
                    {
                        if (message.Contains(" "))
                        {
                            string channel = message.Substring(message.IndexOf(" ") + 1);
                            if ( !validFile(channel) || (channel.Contains("#") == false))
                            {
                                Message("Invalid name", chan.name);
                                return;
                            }
                            foreach (config.channel cu in config.channels)
                            {
                                if (channel == cu.name)
                                {
                                    return;
                                }
                            }
                            config.channels.Add(new config.channel(channel));
                            config.Save();
                            wd.WriteLine("JOIN " + channel);
                            wd.Flush();
                            System.Threading.Thread.Sleep(100);
                            config.channel Chan = getChannel(channel);
                            Chan.Users.addUser("admin", IRCTrust.normalize(user) + "!.*@" + host);
                        }
                        else
                        {
                            Message("Invalid name", chan.name);
                        }
                    }
                    else
                    {
                        Message(messages.PermissionDenied, chan.name);
                    }
                }
            }
            catch (Exception b)
            { 
                
            }
        }

        /// <summary>
        /// Part a channel
        /// </summary>
        /// <param name="chan">Channel object</param>
        /// <param name="user">User</param>
        /// <param name="host">Host</param>
        /// <param name="message">Message</param>
        public static void partChannel(config.channel chan, string user, string host, string message)
        {
            try
            {
                if (message == "@drop")
                {
                    if (chan.Users.isApproved(user, host, "admin"))
                    {
                        wd.WriteLine("PART " + chan.name);
                        System.Threading.Thread.Sleep(100);
                        wd.Flush();
                        if (!Directory.Exists(chan.log))
                        {
                            Directory.Delete(chan.log, true);
                        }
                        File.Delete(chan.name + ".setting");
                        File.Delete(chan.Users.DataFile);
                        config.channels.Remove(chan);
                        config.Save();
                        return;
                    }
                    else
                    {
                        Message(messages.PermissionDenied, chan.name);
                        return;
                    }
                }
                if (message == "@part")
                {
                    if (chan.Users.isApproved(user, host, "admin"))
                    {
                        wd.WriteLine("PART " + chan.name);
                        System.Threading.Thread.Sleep(100);
                        wd.Flush();
                        config.channels.Remove(chan);
                        config.Save();
                    }
                    else
                    {
                        Message(messages.PermissionDenied, chan.name);
                        return;
                    }
                }
            }
            catch (Exception x)
            {
                handleException(x);
            }
        }

        /// <summary>
        /// Display admin command
        /// </summary>
        /// <param name="chan"></param>
        /// <param name="user"></param>
        /// <param name="host"></param>
        /// <param name="message"></param>
        public static void admin(config.channel chan, string user, string host, string message)
        {
            if (message == "@reload")
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                        chan.LoadConfig();
                        chan.Keys = new dictionary(chan.keydb, chan.name);
                        Message("Channel config was reloaded", chan.name);
                        return;
                }
                else
                {
                    Message(messages.PermissionDenied, chan.name);
                    return;
                }
            }
            if (message == "@logon")
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    if (chan.logged)
                    {
                        Message("Channel is already logged", chan.name);
                        return;
                    }
                    else
                    {
                        Message("Channel is now logged", chan.name);
                        chan.logged = true;
                        chan.SaveConfig();
                        config.Save();
                        return;
                    }
                }
                else
                {
                    Message(messages.PermissionDenied, chan.name);
                    return;
                }
            }
            if (message == "@whoami")
            {
                user current = chan.Users.getUser(user + "!@" + host);
                if(current.level == "null")
                {
                    Message("You are unknown to me :)", chan.name);
                    return;
                } else
                {
                    Message("You are " + current.level + " identified by name " + current.name, chan.name);
                    return;
                }
            }

            if (message == "@logoff")
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    if (!chan.logged)
                    {
                        Message("Channel was already not logged", chan.name);
                        return;
                    }
                    else
                    {
                        chan.logged = false;
                        config.Save();
                        chan.SaveConfig();
                        Message("Channel is not logged", chan.name);
                        return;
                    }
                }
                else
                {
                    Message(messages.PermissionDenied, chan.name);
                }
            }
            if (message == "@channellist")
            {
                string channels = "";
                foreach (config.channel a in config.channels)
                {
                    channels = channels + a.name + ", ";
                }
                Message("I am now in following channels: " + channels, chan.name);
                return;
            }
            if (message == "@infobot-off")
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    if (!chan.info)
                    {
                        Message("Channel had infobot disabled", chan.name);
                        return;
                    }
                    else
                    {
                        Message("Infobot disabled", chan.name);
                        chan.info = false;
                        chan.SaveConfig();
                        config.Save();
                        return;
                    }
                }
                else
                {
                    Message(messages.PermissionDenied, chan.name);
                    return;
                }
            }
            if (message == "@infobot-on")
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    if (!chan.logged)
                    {
                        Message("Infobot was already enabled :O", chan.name);
                        return;
                    }
                    else
                    {
                        chan.info = true;
                        config.Save();
                        chan.SaveConfig();
                        Message("Infobot enabled", chan.name);
                        return;
                    }
                }
                Message(messages.PermissionDenied, chan.name);
                return;
            }
            if (message == "@commands")
            {
                Message("Commands: channellist, trusted, trustadd, trustdel, infobot-off, infobot-on, drop, whoami, add, reload, logon, logoff", chan.name);
                return;
            }
        }

        /// <summary>
        /// Called when someone post a message to server
        /// </summary>
        /// <param name="channel">Channel</param>
        /// <param name="nick">Nick</param>
        /// <param name="host">Host</param>
        /// <param name="message">Message</param>
        /// <returns></returns>
        public static bool getMessage(string channel, string nick, string host, string message)
        {
            config.channel curr = getChannel(channel);
            if (curr != null)
            {
                chanLog(message, curr, nick, host);
                if (message.StartsWith("!") && curr.info)
                {
                    curr.Keys.print(message, nick, curr, host);
                }
                if (message.StartsWith("@"))
                {
                    if (curr.info)
                    {
                        curr.Keys.Find(message, curr);
                        curr.Keys.RSearch(message, curr);
                    }
                    modifyRights(message, curr, nick, host);
                    addChannel(curr, nick, host, message);
                    admin(curr, nick, host, message);
                    partChannel(curr, nick, host, message);
                }
            }

            return false;
        }

        /// <summary>
        /// Connection
        /// </summary>
        /// <returns></returns>
        public static bool Reconnect()
        {
            data = new System.Net.Sockets.TcpClient(config.network, 6667).GetStream();
            rd = new StreamReader(data, System.Text.Encoding.UTF8);
            wd = new StreamWriter(data);
            wd.WriteLine("USER " + config.name + " 8 * :" + config.name);
            wd.WriteLine("NICK " + config.username);
            Authenticate();
            foreach (config.channel ch in config.channels)
            {
                System.Threading.Thread.Sleep(2000);
                wd.WriteLine("JOIN " + ch.name);
            }
            wd.Flush();
            return false;
        }

        /// <summary>
        /// Connection
        /// </summary>
        /// <returns></returns>
        public static int Connect()
        {
            data = new System.Net.Sockets.TcpClient(config.network, 6667).GetStream();
            rd = new StreamReader(data, System.Text.Encoding.UTF8);
            wd = new StreamWriter(data);

            dumphtmt = new System.Threading.Thread(HtmlDump.Start);
            dumphtmt.Start();
            check_thread = new System.Threading.Thread(Ping);
            check_thread.Start();

            wd.WriteLine("USER " + config.name + " 8 * :" + config.name);
            wd.WriteLine("NICK " + config.username);

            System.Threading.Thread.Sleep(2000);

            Authenticate();

            foreach (config.channel ch in config.channels)
            {
                if (ch.name != "")
                {
                    wd.WriteLine("JOIN " + ch.name);
                    System.Threading.Thread.Sleep(2000);
                }
            }
            wd.Flush();
            string text = "";
            string nick = "";
            string host = "";
            string message = "";
            string channel = "";
            char delimiter = (char)001;

            while (true)
            {
                try
                {
                    while (!rd.EndOfStream)
                    {
                        text = rd.ReadLine();
                        if (text.StartsWith(":"))
                        {
                            string check = text.Substring(text.IndexOf(" "));
                            if (check.StartsWith(" 005"))
                            {

                            }
                            else
                            {
                                if (text.Contains("PRIVMSG"))
                                {
                                    string info = text.Substring(1, text.IndexOf(" :", 1) - 1);
                                    string info_host;
                                    // we got a message here :)
                                    if (text.Contains("!") && text.Contains("@"))
                                    {
                                        nick = info.Substring(0, info.IndexOf("!"));
                                        host = info.Substring(info.IndexOf("@") + 1, info.IndexOf(" ", info.IndexOf("@")) - 1 - info.IndexOf("@"));
                                    }
                                    info_host = info.Substring(info.IndexOf("PRIVMSG "));

                                    if (info_host.Contains("#"))
                                    {
                                        channel = info_host.Substring(info_host.IndexOf("#"));
                                        message = text.Replace(info, "");
                                        message = message.Substring(message.IndexOf(" :") + 2);
                                        if (message.Contains(delimiter.ToString() + "ACTION"))
                                        {
                                            getAction(message.Replace(delimiter.ToString() +"ACTION", ""), channel, host, nick);
                                            continue;
                                        }
                                        getMessage(channel, nick, host, message);
                                        continue;
                                    }
                                    else
                                    {
                                        message = text.Substring(text.IndexOf("PRIVMSG"));
                                        message = message.Substring(message.IndexOf(":"));
                                        // private message
                                        if (message.StartsWith(":" + delimiter.ToString() + "FINGER"))
                                        {
                                            wd.WriteLine("NOTICE " + nick + " :" + delimiter.ToString() + "FINGER" + " I am a bot don't finger me");
                                            wd.Flush();
                                            continue;
                                        }
                                        if (message.StartsWith(":" + delimiter.ToString() + "TIME"))
                                        {
                                            wd.WriteLine("NOTICE " + nick + " :" + delimiter.ToString() + "TIME " + System.DateTime.Now.ToString());
                                            wd.Flush();
                                            continue;
                                        }
                                        if (message.StartsWith(":" + delimiter.ToString() + "PING"))
                                        {
                                            wd.WriteLine("NOTICE " + nick + " :" + delimiter.ToString() + "PING" + message.Substring(message.IndexOf(delimiter.ToString() + "PING")+5));
                                            wd.Flush();
                                            continue;
                                        }
                                        if (message.StartsWith(":" + delimiter.ToString() + "VERSION"))
                                        {
                                            wd.WriteLine("NOTICE " + nick + " :" + delimiter.ToString() + "VERSION "  + config.version);
                                            wd.Flush();
                                            continue;
                                        }
                                    }
                                }
                                if (text.Contains("PING "))
                                {
                                    wd.WriteLine("PONG " + text.Substring(text.IndexOf("PING ") + 5));
                                    wd.Flush();
                                }
                            }
                        }
                        System.Threading.Thread.Sleep(50);
                    }
                    Program.Log("Reconnecting, end of data stream");
                    Reconnect();
                }
                catch (IOException xx)
                {
                    Program.Log("Reconnecting, connection failed " + xx.Message + xx.StackTrace);
                    Reconnect();
                }
                catch (Exception xx)
                {
                    handleException(xx, channel);
                }
            }
            return 0;
        }

        public static int Disconnect()
        {
            wd.Flush();
            return 0;
        }   
    }
}
