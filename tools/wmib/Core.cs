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
using System.Threading;
using System.Text.RegularExpressions;
using System.Net;
using System.IO;


namespace wmib
{
    public class variables
    {
        /// <summary>
        /// Configuration directory
        /// </summary>
        public static readonly string config = "configuration";
    }
    public class misc
    {
        public static bool IsValidRegex(string pattern)
        {
            if (pattern == null) return false;

            try
            {
                Regex.Match("", pattern);
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
        public static Thread _Queue;
        private static System.Net.Sockets.NetworkStream data;
        public static Thread dumphtmt;
        public static Thread rc;
        public static Thread check_thread;
        private static StreamReader rd;
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

        public class SlowQueue
        {
            public struct Message
            {
                public string message;
                public string channel;
            }
            public static List<Message> messages = new List<Message>();
            public static List<Message> newmessages = new List<Message>();
            private static bool locked;

            public static void DeliverMessage(string Message, string Channel)
            {
                Message text = new Message();
                text.message = Message;
                text.channel = Channel;
                if (locked)
                {
                    newmessages.Add(text);
                    return;
                }
                messages.Add(text);
            }

            public static void Run()
            {
                while (true)
                {
                    locked = true;
                    foreach (Message message in messages)
                    {
                        irc.Message(message.message, message.channel);
                        Thread.Sleep(1000);
                    }
                    messages.Clear();
                    locked = false;
                    foreach (Message message in newmessages)
                    {
                        messages.Add(message);
                    }
                    newmessages.Clear();
                    Thread.Sleep(200);
                }
            }
        }

        public class RegexCheck
        {
            public string value;
            public string regex;
            public bool searching;
            public bool result = false;
            public RegexCheck(string Regex, string Data)
            {
                result = false;
                value = Data;
                regex = Regex;
            }
            private void Run()
            {
                Regex c = new Regex(regex);
                result = c.Match(value).Success;
                searching = false;
            }
            public int IsMatch()
            {
                Thread quick = new Thread(Run);
                searching = true;
                quick.Start();
                int check = 0;
                while (searching)
                {
                    check++;
                    Thread.Sleep(10);
                    if (check > 50)
                    {
                        quick.Abort();
                        return 2;
                    }
                }
                if (result)
                {
                    return 1;
                }
                return 0;
            }
        }

        public class IRCTrust
        {
            private List<user> GlobalUsers = new List<user>();
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
            public string File;

            /// <summary>
            /// Constructor
            /// </summary>
            /// <param name="channel"></param>
            public IRCTrust(string channel)
            {
                // Load
                File = variables.config + "/" + channel + "_user";
                if (!System.IO.File.Exists(File))
                {
                    // Create db
                    Program.Log("Creating user file for " + channel);
                    System.IO.File.WriteAllText(File, "");
                }
                if (!System.IO.File.Exists(variables.config + "/" + "admins"))
                {
                    // Create db
                    Program.Log("Creating user file for admins");
                    System.IO.File.WriteAllText(variables.config + "/" + "admins", "");
                }
                string[] db = System.IO.File.ReadAllLines(File);
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
                string[] dba = System.IO.File.ReadAllLines(variables.config + "/" + "admins");
                _Channel = channel;
                foreach (string x in dba)
                {
                    if (x.Contains(config.separator))
                    {
                        string[] info = x.Split(Char.Parse(config.separator));
                        string level = info[1];
                        string name = decode(info[0]);
                        GlobalUsers.Add(new user(level, name));
                    }
                }
            }

            /// <summary>
            /// Save
            /// </summary>
            /// <returns></returns>
            public bool Save()
            {
                System.IO.File.WriteAllText(File, "");
                foreach (user u in Users)
                {
                    System.IO.File.AppendAllText(File, encode(u.name) + config.separator + u.level + "\n");
                }
                return true;
            }

            public static string normalize(string name)
            {
                name = Regex.Escape(name);
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
            public bool delUser(user trusted, string user)
            {
                foreach (user u in Users)
                {
                    if (u.name == user)
                    {
                        if (getLevel("admin") > getLevel(trusted.level))
                        {
                            Message("This user has higher level than you, sorry", _Channel);
                            return true;
                        }
                        if (u == trusted)
                        {
                            Message("You can't delete yourself from db", _Channel);
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
                foreach (user b in GlobalUsers)
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
                    users_ok += " " + b.name + " (16" + b.level + "1) " + ",";
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

        /// <summary>
        /// Ping
        /// </summary>
        public static void Ping()
        {
            while (true)
            {
                try
                {
                    Thread.Sleep(20000);
                    wd.WriteLine("PING :" + config.network);
                    wd.Flush();
                }
                catch (Exception)
                { }
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
                Thread.Sleep(4000);
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
                SlowQueue.DeliverMessage("DEBUG Exception: " + ex.Message + " I feel crushed, uh :|", config.debugchan);
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
                return "0" + number.ToString();
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
            chanLog(message, curr, config.username, "");
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
                            return 0;
                        }
                    }
                    else
                    {
                        Message("You are not autorized to perform this, sorry", channel.name);
                        return 0;
                    }
                }
                if (message.StartsWith("@trusted"))
                {
                    channel.Users.listAll();
                    return 0;
                }
                if (message.StartsWith("@trustdel"))
                {
                    string[] rights_info = message.Split(' ');
                    if (rights_info.Length > 1)
                    {
                        string x = rights_info[1];
                        if (channel.Users.isApproved(user, host, "trustdel"))
                        {
                            channel.Users.delUser(channel.Users.getUser(user + "!@" + host), rights_info[1]);
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
                        log = "[" + timedateToString(DateTime.Now.Hour) + ":" +
                            timedateToString(DateTime.Now.Minute) + ":" +
                            timedateToString(DateTime.Now.Second) + "] * " +
                            user + " " + message + "\n";
                    }
                    else
                    {
                        log = "[" + timedateToString(DateTime.Now.Hour) + ":"
                            + timedateToString(DateTime.Now.Minute) + ":" +
                            timedateToString(DateTime.Now.Second) + "] " + "<" +
                            user + ">\t " + message + "\n";
                    }
                    File.AppendAllText(channel.log + DateTime.Now.Year + DateTime.Now.Month + DateTime.Now.Day + ".txt", log);
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
            return !(name.Contains(" ") || name.Contains("?") || name.Contains("|") || name.Contains("/")
                || name.Contains("\\") || name.Contains(">") || name.Contains("<") || name.Contains("*"));
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
                            if (!validFile(channel) || (channel.Contains("#") == false))
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
                            Thread.Sleep(100);
                            config.channel Chan = getChannel(channel);
                            Chan.Users.addUser("admin", IRCTrust.normalize(user) + "!.*@" + host);
                            return;
                        }
                        Message("Invalid name", chan.name);
                        return;
                    }
                    Message(messages.PermissionDenied, chan.name);
                    return;
                }
            }
            catch (Exception b)
            {
                handleException(b);
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
                        Thread.Sleep(100);
                        chan.feed = false;
                        wd.Flush();
                        try
                        {
                            if (Directory.Exists(chan.log))
                            {
                                Directory.Delete(chan.log, true);
                            }
                        }
                        catch (Exception)
                        { }
                        File.Delete(variables.config + "/" + chan.name + ".setting");
                        File.Delete(variables.config + "/" + chan.Users.File);
                        if (File.Exists(variables.config + "/" + chan.name + ".list"))
                        {
                            File.Delete(variables.config + "/" + chan.name + ".list");
                        }
                        config.channels.Remove(chan);
                        config.Save();
                        return;
                    }
                    Message(messages.PermissionDenied, chan.name);
                    return;
                }
                if (message == "@part")
                {
                    if (chan.Users.isApproved(user, host, "admin"))
                    {
                        wd.WriteLine("PART " + chan.name);
                        chan.feed = false;
                        Thread.Sleep(100);
                        wd.Flush();
                        config.channels.Remove(chan);
                        config.Save();
                        return;
                    }
                    Message(messages.PermissionDenied, chan.name);
                    return;
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
                Message(messages.PermissionDenied, chan.name);
                return;
            }
            if (message == "@refresh")
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    _Queue.Abort();
                    SlowQueue.newmessages.Clear();
                    _Queue = new System.Threading.Thread(new System.Threading.ThreadStart(SlowQueue.Run));
                    SlowQueue.messages.Clear();
                    _Queue.Start();
                    Message("Message queue was reloaded", chan.name);
                    return;
                }
                Message(messages.PermissionDenied, chan.name);
                return;
            }

            if (message == "@recentchanges-on")
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    if (chan.feed)
                    {
                        Message("Channel had already feed enabled", chan.name);
                        return;
                    }
                    else
                    {
                        Message("Feed is enabled", chan.name);
                        chan.feed = true;
                        chan.SaveConfig();
                        config.Save();
                        return;
                    }
                }
                SlowQueue.DeliverMessage(messages.PermissionDenied, chan.name);
                return;
            }

            if (message.StartsWith("@recentchanges+"))
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    if (chan.feed)
                    {
                        if (!message.Contains(" "))
                        {
                            SlowQueue.DeliverMessage("Invalid wiki", chan.name);
                            return;
                        }
                        string channel = message.Substring(message.IndexOf(" ") + 1);
                        if (RecentChanges.InsertChannel(chan, channel))
                        {
                            Message("Wiki inserted", chan.name);
                        }
                        return;
                    }
                    else
                    {
                        Message("Channel doesn't have enabled recent changes", chan.name);
                        return;
                    }
                }
                SlowQueue.DeliverMessage(messages.PermissionDenied, chan.name);
                return;
            }

            if (message.StartsWith("@recentchanges- "))
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    if (chan.feed)
                    {
                        if (!message.Contains(" "))
                        {
                            SlowQueue.DeliverMessage("Invalid wiki", chan.name);
                            return;
                        }
                        string channel = message.Substring(message.IndexOf(" ") + 1);
                        if (RecentChanges.DeleteChannel(chan, channel))
                        {
                            Message("Wiki deleted", chan.name);
                        }
                        return;
                    }
                    else
                    {
                        Message("Channel doesn't have enabled recent changes", chan.name);
                        return;
                    }
                }
                SlowQueue.DeliverMessage(messages.PermissionDenied, chan.name);
                return;
            }

            if (message.StartsWith("@RC+ "))
            {
                if (chan.Users.isApproved(user, host, "trust"))
                {
                    if (chan.feed)
                    {
                        string[] a = message.Split(' ');
                        if (a.Length < 3)
                        {
                            SlowQueue.DeliverMessage("Error, " + user + ": Wrong number of parameters!", chan.name);
                            return;
                        }
                        string wiki = a[1];
                        string Page = a[2];
                        chan.RC.insertString(wiki, Page);
                        return;
                    }
                    else
                    {
                        SlowQueue.DeliverMessage("Channel doesn't have enabled recent changes", chan.name);
                        return;
                    }
                }
                SlowQueue.DeliverMessage(messages.PermissionDenied, chan.name);
                return;
            }

            if (message.StartsWith("@help"))
            {
                string parameter = "";
                if (message.Contains(" "))
                {
                    parameter = message.Substring(message.IndexOf(" ") + 1);
                }
                if (parameter != "")
                {
                    ShowHelp(parameter, chan.name);
                    return;
                }
                else
                {
                    SlowQueue.DeliverMessage ("Type @commands for list of commands. This bot is running http://meta.wikimedia.org/wiki/WM-Bot version " + config.version + " source code licensed under GPL and located in wikimedia svn", chan.name);
                    return;
                }
            }

            if (message.StartsWith("@RC-"))
            {
                if (chan.Users.isApproved(user, host, "trust"))
                {
                    if (chan.feed)
                    {
                        string[] a = message.Split(' ');
                        if (a.Length < 3)
                        {
                            SlowQueue.DeliverMessage("Error, " + user + ": Wrong number of parameters!", chan.name);
                            return;
                        }
                        string wiki = a[1];
                        string Page = a[2];
                        chan.RC.removeString(wiki, Page);
                        return;
                    }
                    else
                    {
                        SlowQueue.DeliverMessage("Channel doesn't have enabled recent changes", chan.name);
                        return;
                    }
                }
                SlowQueue.DeliverMessage(messages.PermissionDenied, chan.name);
                return;
            }

            if (message == "@recentchanges-off")
            {
                if (chan.Users.isApproved(user, host, "admin"))
                {
                    if (!chan.feed)
                    {
                        Message("Channel had already feed disabled", chan.name);
                        return;
                    }
                    else
                    {
                        Message("Feed disabled", chan.name);
                        chan.feed = false;
                        chan.SaveConfig();
                        config.Save();
                        return;
                    }
                }
                SlowQueue.DeliverMessage(messages.PermissionDenied, chan.name);
                return;
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
                SlowQueue.DeliverMessage(messages.PermissionDenied, chan.name);
                return;
            }

            if (message == "@whoami")
            {
                user current = chan.Users.getUser(user + "!@" + host);
                if (current.level == "null")
                {
                    SlowQueue.DeliverMessage("You are unknown to me :)", chan.name);
                    return;
                }
                Message("You are " + current.level + " identified by name " + current.name, chan.name);
                return;
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
                SlowQueue.DeliverMessage(messages.PermissionDenied, chan.name);
                return;
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
                SlowQueue.DeliverMessage(messages.PermissionDenied, chan.name);
                return;
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
                    chan.info = true;
                    config.Save();
                    chan.SaveConfig();
                    Message("Infobot enabled", chan.name);
                    return;
                }
                SlowQueue.DeliverMessage(messages.PermissionDenied, chan.name);
                return;
            }
            if (message == "@commands")
            {
                SlowQueue.DeliverMessage("Commands: channellist, trusted, trustadd, trustdel, infobot-off, refresh, infobot-on, drop, whoami, add, reload, help, RC-, recentchanges-on, recentchanges-off, logon, logoff, recentchanges-, recentchanges+, RC+", chan.name);
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

        private static void showInfo(string name, string info, string channel)
        {
            SlowQueue.DeliverMessage("Info for " + name + ": " + info, channel);
        }

        private static bool ShowHelp(string parameter, string channel)
        {
            switch (parameter.ToLower())
            { 
                case "trustdel":
                    showInfo("trustdel", "Remove an entry from access list, example @trustdel regex", channel);
                    return false;
                case "refresh":
                    showInfo("refresh", "Remove data from queue", channel);
                    return false;
                case "infobot-on":
                    showInfo("infobot-on", "Turn on the infobot", channel);
                    return false;
                case "infobot-off":
                    showInfo("infobot-off", "Turn off the infobot, preserve db", channel);
                    return false;
                case "channellist":
                    showInfo("channellist", "Display the list of channels where bot should be used (only if it can join them)", channel);
                    return false;
                case "trusted":
                    showInfo("trusted", "Display access list for this channel", channel);
                    return false;
                case "trustadd":
                    showInfo("trustadd", "Make an entry to the access list, example @trustadd regex admin", channel);
                    return false;
                case "drop":
                    showInfo("drop", "Remove bot from a channel together with all collected data", channel);
                    return false;
                case "part":
                    showInfo("part", "Remove bot from a channel and preserve all config", channel);
                    return false;
                case "whoami":
                    showInfo("whoami", "Display your current status in access list", channel);
                    return false;
                case "add":
                    showInfo("add", "Insert bot to a specified channel and give you admin rights for that", channel);
                    return false;
                case "reload":
                    showInfo("reload", "Read a config from disk", channel);
                    return false;
                case "logon":
                    showInfo("logon", "Start logging to a file", channel);
                    return false;
                case "logoff":
                    showInfo("logoff", "Disable logging", channel);
                    return false;
                case "recentchanges-on":
                    showInfo("recentchanges-on", "Turn on a feed of changes on wmf wikis", channel);
                    return false;
                case "recentchanges-off":
                    showInfo("recentchanges-off", "Turn off the wiki feed", channel);
                    return false;
                case "recentchanges-":
                    showInfo("recentchanges-", "Remove a wiki from a feed, example @recentchanges- en_wikipedia", channel);
                    return false;
                case "recentchanges+":
                    showInfo("recentchanges+", "Insert a wiki to feed, example @recentchanges+ en_wikipedia", channel);
                    return false;
                case "RC-":
                    showInfo("RC-", "Remove a page from rc list", channel);
                    return false;
                case "RC+":
                    showInfo("RC+", "Create entry for feed of specified page, example @RC+ wiki page", channel);
                    return false;
            }
            SlowQueue.DeliverMessage("Unknown command type @commands for a list of all commands I know", channel);
            return false;
        }

        /// <summary>
        /// Connection
        /// </summary>
        /// <returns></returns>
        public static bool Reconnect()
        {
            _Queue.Abort();
            data = new System.Net.Sockets.TcpClient(config.network, 6667).GetStream();
            rd = new StreamReader(data, System.Text.Encoding.UTF8);
            wd = new StreamWriter(data);
            wd.WriteLine("USER " + config.name + " 8 * :" + config.name);
            wd.WriteLine("NICK " + config.username);
            Authenticate();
            _Queue = new Thread(SlowQueue.Run);
            foreach (config.channel ch in config.channels)
            {
                Thread.Sleep(2000);
                wd.WriteLine("JOIN " + ch.name);
                wd.Flush();
            }
            SlowQueue.newmessages.Clear();
            SlowQueue.messages.Clear();
            wd.Flush();
            _Queue.Start();
            return false;
        }

        /// <summary>
        /// Connection
        /// </summary>
        /// <returns></returns>
        public static void Connect()
        {
            data = new System.Net.Sockets.TcpClient(config.network, 6667).GetStream();
            rd = new System.IO.StreamReader(data, System.Text.Encoding.UTF8);
            wd = new System.IO.StreamWriter(data);

            _Queue = new Thread(SlowQueue.Run);
            dumphtmt = new Thread(HtmlDump.Start);
            dumphtmt.Start();
            rc = new Thread(RecentChanges.Start);
            rc.Start();
            check_thread = new Thread(Ping);
            check_thread.Start();

            wd.WriteLine("USER " + config.name + " 8 * :" + config.name);
            wd.WriteLine("NICK " + config.username);

            _Queue.Start();
            Thread.Sleep(2000);

            Authenticate();

            foreach (config.channel ch in config.channels)
            {
                if (ch.name != "")
                {
                    wd.Flush();
                    wd.WriteLine("JOIN " + ch.name);
                    Thread.Sleep(2000);
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
                                            getAction(message.Replace(delimiter.ToString() + "ACTION", ""), channel, host, nick);
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
                                            wd.WriteLine("NOTICE " + nick + " :" + delimiter.ToString() + "PING" + message.Substring(message.IndexOf(delimiter.ToString() + "PING") + 5));
                                            wd.Flush();
                                            continue;
                                        }
                                        if (message.StartsWith(":" + delimiter.ToString() + "VERSION"))
                                        {
                                            wd.WriteLine("NOTICE " + nick + " :" + delimiter.ToString() + "VERSION " + config.version);
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
                        Thread.Sleep(50);
                    }
                    Program.Log("Reconnecting, end of data stream");
                    Reconnect();
                }
                catch (System.IO.IOException xx)
                {
                    Program.Log("Reconnecting, connection failed " + xx.Message + xx.StackTrace);
                    Reconnect();
                }
                catch (Exception xx)
                {
                    handleException(xx, channel);
                }
            }
        }
        public static int Disconnect()
        {
            wd.Flush();
            return 0;
        }
    }
}
