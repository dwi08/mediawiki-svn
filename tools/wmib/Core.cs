﻿//This program is free software: you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation, either version 3 of the License, or
//(at your option) any later version.

//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

using System;
using System.Collections.Generic;
using System.Text;
using System.Net;


namespace wmib
{
    public static class irc
    {
        private static System.Net.Sockets.NetworkStream data;
        public static System.Threading.Thread dumphtmt;
        public static System.Threading.Thread check_thread;
        public static System.IO.StreamReader rd;
        private static System.IO.StreamWriter wd;
        private static List<user> User = new List<user>();

        public static void Ping()
        {
            while (true)
            {
                System.Threading.Thread.Sleep(20000);
                wd.WriteLine("PING :" + config.network);
                wd.Flush();
            }
        }

        public static string encode(string text)
        {
            return text.Replace(config.separator, "<separator>");
        }

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

        public static string decode(string text)
        {
            return text.Replace("<separator>", config.separator);
        }

        public class user
        {
            public user(string level, string name)
            {
                this.level = level;
                this.name = name;
            }
            public string name;
            public string level;
        }

        public class IRCTrust
        {
            private List<user> Users = new List<user>();
            public string _Channel;
            public string File;
            public IRCTrust(string channel)
            {
                // Load
                File = channel + "_user";
                if (!System.IO.File.Exists(File))
                {
                    // Create db
                    Program.Log("Creating user file for " + channel);
                    System.IO.File.WriteAllText(File, "");
                }
                string[] db = System.IO.File.ReadAllLines(channel + "_user");
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
            public bool Save()
            {
                System.IO.File.WriteAllText(File, "");
                foreach (user u in Users)
                {
                    System.IO.File.AppendAllText(File, encode(u.name) + config.separator + u.level + "\n");
                }
                return true;
            }

            public bool addUser(string level, string user)
            {
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

            public user getUser(string user)
            {
                user lv = new user("null", "");
                int current = 0;
                foreach (user b in Users)
                {
                    System.Text.RegularExpressions.Regex id = new System.Text.RegularExpressions.Regex(b.name);
                    if (id.Match(user).Success)
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

            public void listAll()
            {
                string users_ok = "";
                foreach (user b in Users)
                {
                    users_ok = users_ok + " " + b.name + ",";
                }
                Message("I trust: " + users_ok, _Channel);
            }

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
            public string datafile = "";
            // if we need to update dump
            public bool update = true;
            public class item
            {
                public item(string Key, string Text, string User, string Lock = "false")
                {
                    text = Text;
                    key = Key;
                    locked = Lock;
                    user = User;
                }
                public string text;
                public string key;
                public string user;
                public string locked;
            }
            public class staticalias
            {
                public string Name;
                public string Key;
                public staticalias(string name, string key)
                {
                    Name = name;
                    Key = key;
                }
            }
            public List<item> text = new List<item>();
            public List<staticalias> Alias = new List<staticalias>();
            public string Channel;
            public void Load()
            {
                text.Clear();
                if (!System.IO.File.Exists(datafile))
                {
                    // Create db
                    System.IO.File.WriteAllText(datafile, "");
                }

                string[] db = System.IO.File.ReadAllLines(datafile);
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
                            text.Add(new item(name, value, ""));
                        }
                        else
                        {
                            Alias.Add(new staticalias(name, value));
                        }
                    }
                }
            }

            public dictionary(string database, string channel)
            {
                datafile = database;
                Channel = channel;
                Load();
            }

            public void Save()
            {
                update = true;
                try
                {
                    System.IO.File.WriteAllText(datafile, "");
                    foreach (staticalias key in Alias)
                    {
                        System.IO.File.AppendAllText(datafile, key.Name + config.separator + key.Key + config.separator + "alias" + "\n");
                    }
                    foreach (item key in text)
                    {
                        System.IO.File.AppendAllText(datafile, key.key + config.separator + key.text + config.separator + "key" + config.separator + key.locked + config.separator + key.user + "\n");
                    }
                }
                catch (Exception b)
                {
                    handleException(b, Channel);
                }
            }

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
                             this.aliasKey(name.Substring(name.IndexOf("alias") + 6), parm[0], "");
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
                string p1 = "";
                if (p.Length > 1)
                {
                    p1 = p[1];
                }
                string keyv = getValue(p[0]);
                    if (!(keyv == ""))
                    {
                        keyv = keyv.Replace("$1", p1);
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
                            keyv = keyv.Replace("$1", p1);
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

            public void RSearch(string key, config.channel Chan)
            { 
                if (!key.StartsWith("@regsearch"))
                {
                    return;
                }
                if (key.Length < 11)
                {
                    Message("Could you please tell me what I should search for :P", Chan.name);
                    return;
                }
                key = key.Substring(11);
                System.Text.RegularExpressions.Regex value = new System.Text.RegularExpressions.Regex(key);
                string results = "";
                foreach (item data in text)
                {
                    if (data.key == key || value.Match(data.text).Success)
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

            public void setKey(string Text, string key, string user)
            {
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

        public static void handleException(Exception ex, string chan)
        {
            Message("DEBUG Exception: " + ex.Message + " I feel crushed, uh :|", chan);
        }

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

        public static bool Message(string message, string channel)
        {
            wd.WriteLine("PRIVMSG " + channel + " :" + message);
            wd.Flush();
            return true;
        }

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

        public static void chanLog(string message, config.channel channel, string user, string host, bool noac = true)
        {
            try
            {
                if (channel.logged)
                {
                    string log;
                    if (!noac)
                    {
                        log = "[" + System.DateTime.Now.Hour + ":" + System.DateTime.Now.Minute + ":" + System.DateTime.Now.Second + "] * " + user + " " + message + "\n";
                    }
                    else
                    {
                        log = "[" + System.DateTime.Now.Hour + ":" + System.DateTime.Now.Minute + ":" + System.DateTime.Now.Second + "] " + "<" + user + "> " + message + "\n";
                    }
                    System.IO.File.AppendAllText(channel.log + System.DateTime.Now.Year + System.DateTime.Now.Month + System.DateTime.Now.Day +".txt", log);
                }
            }
            catch (Exception er)
            { 
                // nothing
                Console.WriteLine(er.Message);
            }
        }

        public static bool getAction(string message, string Channel, string host, string nick)
        {
            config.channel curr = getChannel(Channel);
            chanLog(message, curr, nick, host, false);
            return false;
        }

        public static void addChannel(config.channel chan, string user, string host, string message)
        {
            if (message.StartsWith("@add"))
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    if (message.Contains(" "))
                    {
                        string channel=message.Substring(message.IndexOf(" ") + 1);
                        if (channel.Contains(" ") || (channel.Contains("#") == false))
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
                        Chan.Users.addUser("admin", user + ".*" + host );
                    } else
                    {
                        Message("Invalid name", chan.name);
                    }
                } else
                {
                    Message("Permission denied", chan.name);
                }
            }
        }

        public static void partChannel(config.channel chan, string user, string host, string message)
        {
            if (message.StartsWith("@part"))
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    wd.WriteLine("PART " + chan.name);
                    System.Threading.Thread.Sleep(100);
                    wd.Flush();
                    config.channels.Remove(chan);
                    config.Save();
                } else
                {
                    Message("Permission denied", chan.name);
                }
            }
        }

        public static void admin(config.channel chan, string user, string host, string message)
        {
            if (message.StartsWith("@reload"))
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                        chan.LoadConfig();
                        chan.Keys = new dictionary(chan.keydb, chan.name);
                        Message("Channel config was reloaded", chan.name);
                }
                else
                {
                    Message("Permission denied", chan.name);
                }
            }
            if (message.StartsWith("@logon"))
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    if (chan.logged)
                    {
                        Message("Channel is already logged", chan.name);
                    }
                    else
                    {
                        Message("Channel is now logged", chan.name);
                        chan.logged = true;
                        chan.SaveConfig();
                        config.Save();
                    }
                }
                else
                {
                    Message("Permission denied", chan.name);
                }
            }
            if (message.StartsWith("@logoff"))
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    if (!chan.logged)
                    {
                        Message("Channel was already not logged", chan.name);
                    }
                    else
                    {
                        chan.logged = false;
                        config.Save();
                        chan.SaveConfig();
                        Message("Channel is not logged", chan.name); 
                    }
                }
                else
                {
                    Message("Permission denied", chan.name);
                }
            }
            if (message.StartsWith("@channellist"))
            {
                string channels = "";
                foreach (config.channel a in config.channels)
                {
                    channels = channels + a.name + ", ";
                }
                Message("I am now in following channels: " + channels, chan.name);
            }
        }

        public static bool getMessage(string channel, string nick, string host, string message)
        {
            config.channel curr = getChannel(channel);
            if (curr != null)
            {
                chanLog(message, curr, nick, host);
                if (message.StartsWith("!"))
                {
                    curr.Keys.print(message, nick, curr, host);
                }
                if (message.StartsWith("@"))
                {
                    curr.Keys.Find(message, curr);
                    curr.Keys.RSearch(message, curr);
                    modifyRights(message, curr, nick, host);
                    addChannel(curr, nick, host, message);
                    admin(curr, nick, host, message);
                    partChannel(curr, nick, host, message);
                }
            }




            return false;
        }

        public static bool Reconnect()
        {
            data = new System.Net.Sockets.TcpClient(config.network, 6667).GetStream();
            rd = new System.IO.StreamReader(data, System.Text.Encoding.UTF8);
            wd = new System.IO.StreamWriter(data);
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

        public static int Connect()
        {
            data = new System.Net.Sockets.TcpClient(config.network, 6667).GetStream();
            rd = new System.IO.StreamReader(data, System.Text.Encoding.UTF8);
            wd = new System.IO.StreamWriter(data);

            dumphtmt = new System.Threading.Thread(new System.Threading.ThreadStart(HtmlDump.Start));
            dumphtmt.Start();
            check_thread = new System.Threading.Thread(new System.Threading.ThreadStart(Ping));
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
                                    string info = text.Substring(1, text.IndexOf(":", 2));
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
                                        channel = channel.Substring(0, channel.IndexOf(" "));
                                        message = text.Replace(info, "");
                                        message = message.Substring(message.IndexOf(":") + 1);
                                        if (message.Contains(delimiter.ToString() + "ACTION"))
                                        {
                                            getAction(message.Replace("", "").Replace(delimiter.ToString() +"ACTION ", ""), channel, host, nick);
                                            continue;
                                        }
                                        else
                                        {
                                            getMessage(channel, nick, host, message);
                                            continue;
                                        }
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
                                            wd.WriteLine("NOTICE " + nick + " :" + delimiter.ToString() + "PING " + message.Substring(message.IndexOf(delimiter.ToString() + "PING" + 6)));
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
                catch (System.IO.IOException xx)
                {
                    Program.Log("Reconnecting, connection failed");
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
