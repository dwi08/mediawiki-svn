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
using System.Linq;
using System.Text;
using System.Net;


namespace wmib
{
    class Program
    {
        public static bool Log(string msg )
        {
            Console.WriteLine("LOG: " + msg);
            return false;
        }
        static void Main(string[] args)
        {
            Log("Connecting");
            irc.Connect();
        }
    }

    public static class config
    {
        public class channel
        {
            public string name;
            public bool logged;
            public string log;
            public irc.dictionary Keys = new irc.dictionary();
            public irc.trust Users;
            public channel(string Name)
            {
                logged = true;
                name = Name;   
                log = Name + ".txt";
                Keys.Load(name);
                Users = new irc.trust(name);
            }
        }
        /// <summary>
        /// Network
        /// </summary>
        public static string network = "irc.freenode.net";
        public static string username = "wm-bot";
        /// <summary>
        /// 
        /// </summary>
        public static string version = "wikimedia bot v. 1.0.1";
        /// <summary>
        /// User name
        /// </summary>
        public static string name = "wm-bot";
        /// <summary>
        /// Channels
        /// </summary>
        public static channel[] channels = { new channel("#bhfsjhhdhgf"), new channel( "#wikimedia-testbot") };
    }

    public static class irc
    {
        private static System.Net.Sockets.NetworkStream data;
        public static System.IO.StreamReader rd;
        private static System.IO.StreamWriter wd;
        private static List<user> User = new List<user>();

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

        public class trust
        {
            private List<user> Users = new List<user>();
            public string _Channel;
            public string File;
            public trust(string channel)
            { 
                // Load
                File = _Channel + "_user";
                if (!System.IO.File.Exists(File))
                {
                    // Create db
                    Program.Log("Creating user file for " + channel);
                    System.IO.File.WriteAllText(File, "");
                }
                string[] db = System.IO.File.ReadAllLines(channel + "_user");
                this._Channel = channel;
                foreach (string x in db)
                {
                    if (x.Contains("|"))
                    {
                        string[] info = x.Split('|');
                        string level = info[1];
                        string name = info[0];
                        Users.Add(new user(level, name));
                    }
                }
            }
            public bool Save()
            {
                System.IO.File.WriteAllText(File, "");
                foreach (user u in Users)
                {
                    System.IO.File.AppendAllText(File, u.name + "|" + u.level + "\n");
                }
                return true;
            }

            public bool addUser(string level, string user)
            {
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
                    }
                }
                Save();
                return true;
            }

            public user getUser(string user)
            {
                foreach (user b in Users)
                {
                    if (b.name == user)
                    {
                        return b;
                    }
                }
                return new user("null", "");
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
                user current = getUser(User);
                if (current.level == "null")
                {
                    return false;
                }

                if (command == "alias_key")
                {
                        return matchLevel(1, current.level);
                }
                if (command == "new_key")
                {
                        return matchLevel(1, current.level);
                }
                if (command == "shutdown")
                {
                        return matchLevel(1, current.level);
                }
                if (command == "delete_key")
                {
                        return matchLevel(1, current.level);
                }
                if (command == "trust")
                {
                        return matchLevel(1, current.level);
                }
                if (command == "trustadd")
                {
                    return matchLevel(1, current.level);
                }
                if (command == "trustdel")
                {
                        return matchLevel(1, current.level);
                }
                return false;
            }
        }

        public class dictionary
        {
            public Dictionary<string, string> text = new Dictionary<string, string>();
            public void Load(string channel)
            { 
                string file = channel + ".db";
                if (!System.IO.File.Exists(file))
                {
                    // Create db
                    System.IO.File.WriteAllText(file, "");
                }
            }
            public void setKey(string text, string key, string user, config.channel channel)
            { 
                
            }
            public void aliasKey(string key, string alias, string user, config.channel channel)
            { 
                
            }
            public void rmKey(string key, string user, config.channel channel)
            { 
            
            }
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
            if (message.StartsWith("@trustadd"))
            {
                string[] rights_info = message.Split(' ');
                if (channel.Users.isApproved(user, host, "trustadd"))
                {
                    channel.Users.addUser(rights_info[2], rights_info[1]);
                }
                else
                {
                    Message("You are not autorized to perform this, sorry", channel.name);
                    
                }
            }
            if (message.StartsWith("@trusted"))
            {
                
            }
            if (message.StartsWith("@trustdel"))
            {
                string[] rights_info = message.Split(' ');
                string x = rights_info[1];
                if (channel.Users.isApproved(user, host, "trustdel"))
                {
                    channel.Users.delUser(rights_info[1]);
                }
                else
                {
                    Message("You are not autorized to perform this, sorry", channel.name);
                }
            }
            return 0;
        }

        public static void chanLog(string message, config.channel channel, string user, string host)
        {
            if (channel.logged)
            { 
                string log = "\n" + "[" + System.DateTime.Today.Hour + ":" + System.DateTime.Today.Minute + ":" + System.DateTime.Today.Second + "] " + "<" + user + "> " + message;
                System.IO.File.AppendAllText(channel.log, log);
            }
        }

        public static bool getMessage(string channel, string nick, string host, string message)
        {
            config.channel curr = getChannel(channel);
            if (curr != null)
            {
                    chanLog(message, curr, nick, host);
                    modifyRights(message, curr, nick, host);
            }
            

            return false;
        }

        public static int Connect()
        {
            data = new System.Net.Sockets.TcpClient(config.network, 6667).GetStream();
            rd = new System.IO.StreamReader(data, System.Text.Encoding.UTF8);
            wd = new System.IO.StreamWriter(data);

            wd.WriteLine("USER " + config.name + " 8 * :" + config.name);
            wd.WriteLine("NICK " + config.username);

            //System.Threading.Thread.Sleep(2000);

            foreach (config.channel ch in config.channels)
            {
                wd.WriteLine("JOIN " + ch.name);
            }
            wd.Flush();
            string text = "";
            string nick = "";
            string host = "";
            string message = "";
            string channel = "";

            while (true)
            {
                while (!rd.EndOfStream)
                {
                    text = rd.ReadLine();
                    if (text.StartsWith(":"))
                    {
                        if (text.Contains("PRIVMSG"))
                        {
                            // we got a message here :)
                            if (text.Contains("!") && text.Contains("@"))
                            {
                                nick = text.Substring(1, text.IndexOf("!") - 1);
                                host = text.Substring(text.IndexOf("@") + 1, text.IndexOf(" ", text.IndexOf("@")) - 1 - text.IndexOf("@"));
                            }
                            if (text.Substring(text.IndexOf("PRIVMSG ", text.IndexOf(" "), text.IndexOf("PRIVMSG "))).Contains("#"))
                            {
                                channel = text.Substring(text.IndexOf("#"), text.IndexOf(" ", text.IndexOf("#")) - text.IndexOf("#"));
                                message = text.Substring(text.IndexOf("PRIVMSG"));
                                message = message.Substring(message.IndexOf(":") + 1);
                                getMessage(channel, nick, host, message);
                            }
                            else
                            { 
                                // private message
                            }
                        }
                    }
                    Console.Write(text + "\n\n\n");
                    System.Threading.Thread.Sleep(50);
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
