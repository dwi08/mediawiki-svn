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
using System.Text;
using System.IO;

namespace wmib
{
    public class RecentChanges
    {
        public class IWatch
        {
            public string Channel;
            public string Page;
            public wiki URL;
            public IWatch(wiki site, string page, string channel)
            {
                Channel = channel;
                Page = page;
                URL = site;
            }
        }
        public class wiki
        {
            public string name;
            public string channel;
            public string url;
            public wiki(string _channel, string _url, string _name)
            {
                url = _url;
                name = _name;
                channel = _channel;
            }
        }

        private List<IWatch> pages = new List<IWatch>();
        public static List<wiki> wikiinfo = new List<wiki>();
        private static List<string> channels;
        private static List<RecentChanges> rc = new List<RecentChanges>();
        private static StreamReader RD;
        private static string channeldata = variables.config + "/feed";
        public static StreamWriter WD;
        public static System.Net.Sockets.NetworkStream stream;
        public static System.Text.RegularExpressions.Regex line = new System.Text.RegularExpressions.Regex(":rc-pmtpa!~rc-pmtpa@[^ ]* PRIVMSG #[^:]*:14\\[\\[07([^]*)14\\]\\]4 (M?)(B?)10 02.*di" +
                                                                                                            "ff=([^&]*)&oldid=([^]*) 5\\* 03([^]*) 5\\* \\(?([^]*)?\\) 10([^]*)?");
        public config.channel channel;

        ~RecentChanges()
        {
            try {
        	    rc.Remove(this);
            } catch (Exception) {}
        }

        public RecentChanges(config.channel _channel)
        {
            channel = _channel;
            Load();
            rc.Add(this);
        }

        public static bool InsertChannel(config.channel target, string name)
        {
            wiki web = null;
            foreach (wiki site in wikiinfo)
            {
                if (name == site.name)
                {
                    web = site;
                    break;
                }
            }
            if (web == null)
            {
                irc.Message("There is no such a wiki in list of wikis", target.name);
                return false;
            }
            if (channels.Contains(web.channel))
            {
                irc.Message("This channel is already watched", target.name);
                return false;
            }
            channels.Add(web.channel);
            WD.WriteLine("JOIN " + web.channel);
            WD.Flush();
            System.IO.File.WriteAllText(channeldata, "");
            foreach (string x in channels)
            {
                System.IO.File.AppendAllText(channeldata, x + "\n");
            }
            return true;
        }

        public static bool DeleteChannel(config.channel target, string WikiName)
        {
            wiki W = null;
            foreach (wiki site in wikiinfo)
            {
                if (WikiName == site.name)
                {
                    W = site;
                    break;
                }
            }
            if (W == null)
            {
                irc.Message("There is no such a wiki in list of wikis", target.name);
                return false;
            }
            if (!channels.Contains(W.channel))
            {
                irc.Message("This channel is already not being watched", target.name);
                return false;
            }
            channels.Remove(W.channel);
            WD.WriteLine("PART " + W.channel);
            WD.Flush();
            System.IO.File.WriteAllText(channeldata, "");
            foreach (string x in channels)
            {
                System.IO.File.AppendAllText(channeldata, x + "\n");
            }
            return true;
        }

        public static void Connect()
        {
            try
            {
                stream = new System.Net.Sockets.TcpClient("irc.wikimedia.org", 6667).GetStream();
                WD = new StreamWriter(stream);
                RD = new StreamReader(stream, System.Text.Encoding.UTF8);
                System.Threading.Thread pinger = new System.Threading.Thread(Pong);
                WD.WriteLine("USER " + "wm-bot" + " 8 * :" + "wm-bot");
                WD.WriteLine("NICK " + "wm-bot" + System.DateTime.Now.ToShortDateString().Replace("/", "").Replace(":", "").Replace("\\", "").Replace(".", ""));
                WD.Flush();
                pinger.Start();
                foreach (string b in channels)
                {
                    System.Threading.Thread.Sleep(800);
                    WD.WriteLine("JOIN " + b);
                    WD.Flush();
                }
            }
            catch (Exception)
            { 
            
            }
        }

        /// <summary>
        /// get the wiki from a name
        /// </summary>
        /// <param name="Name"></param>
        /// <returns></returns>
        private static wiki getWiki(string Name)
        {
            foreach (wiki curr in wikiinfo)
            {
                if (curr.name == Name)
                {
                    return curr;
                }
            }
            return null;
        }

        /// <summary>
        /// Load the list
        /// </summary>
        public void Load()
        {
            string name = variables.config + "/" + channel.name + ".list";
            if (File.Exists(name))
            {
                string[] content = File.ReadAllLines(name);
                pages.Clear();
                foreach (string value in content)
                {
                    string[] values = value.Split('|');
                    if (values.Length == 3)
                    {
                        pages.Add(new IWatch(getWiki(values[0]), values[1].Replace("<separator>", "|"), values[2]));
                    }
                }
            }
        }

        /// <summary>
        /// Save the list
        /// </summary>
        public void Save()
        {
            string dbn = variables.config + "/" + channel.name + ".list";
            string content = "";
            foreach (IWatch values in pages)
            {
                content = content + values.URL.name + "|" + values.Page.Replace("|", "<separator>") + "|" + values.Channel + "\n";
            }
            File.WriteAllText(dbn, content);
        }

        private static void Pong()
        {
            try {
                while (true)
                {
                    WD.WriteLine("PING irc.wikimedia.org");
                    WD.Flush();
                    System.Threading.Thread.Sleep(12000);
                }
            } catch ( System.IO.IOException )
            {
                Thread.CurrentThread.Abort();
            }
            catch (Exception) { }
        }

        public bool removeString(string WS, string Page)
        {
            Page = Page.Replace("_", " ");
            wiki site = null;
            foreach (wiki Site in wikiinfo)
            {
                if (Site.name == WS)
                {
                    site = Site;
                }
            }
            if (site != null)
            {
                if (channels.Contains(site.channel))
                {
                    IWatch currpage = null;
                    foreach (IWatch iw in pages)
                    {
                        if (iw.Page == Page)
                        {
                            currpage = iw;
                            break;
                        }
                    }
                    if (pages.Contains(currpage))
                    {
                        pages.Remove(currpage);
                        Save();
                        irc.SlowQueue.DeliverMessage("Deleted item from feed", channel.name);
                        return true;
                    }
                    irc.SlowQueue.DeliverMessage("Can't find item in a list", channel.name);
                    return true;
                }
                irc.SlowQueue.DeliverMessage("Unable to delete the string because the channel is not being watched now", channel.name);
                return false;
            }
            irc.SlowQueue.DeliverMessage("Unable to delete the string from the list because there is no such wiki site known by a bot", channel.name);
            return false;
        }

        public static int InsertSite()
        {
            wikiinfo.Add(new wiki("#cs.wikinews", "https://cs.wikipedia.org/w/index.php", "cs_wikinews"));
            wikiinfo.Add(new wiki("#en.wikinews", "https://en.wikipedia.org/w/index.php", "en_wikinews"));
            wikiinfo.Add(new wiki("#de.wikinews", "https://de.wikipedia.org/w/index.php", "de_wikinews"));
            wikiinfo.Add(new wiki("#fr.wikinews", "https://fr.wikipedia.org/w/index.php", "fr_wikinews"));
            wikiinfo.Add(new wiki("#pt.wikinews", "https://pt.wikipedia.org/w/index.php", "pt_wikinews"));
            wikiinfo.Add(new wiki("#zh.wikinews", "https://fr.wikipedia.org/w/index.php", "zh_wikinews"));
            wikiinfo.Add(new wiki("#es.wikinews", "https://fr.wikipedia.org/w/index.php", "es_wikinews"));
            wikiinfo.Add(new wiki("#ru.wikinews", "https://fr.wikipedia.org/w/index.php", "ru_wikinews"));
            wikiinfo.Add(new wiki("#it.wikinews", "https://fr.wikipedia.org/w/index.php", "it_wikinews"));
            wikiinfo.Add(new wiki("#nl.wikinews", "https://fr.wikipedia.org/w/index.php", "nl_wikinews"));
            wikiinfo.Add(new wiki("#ja.wikinews", "https://fr.wikipedia.org/w/index.php", "ja_wikinews"));
            wikiinfo.Add(new wiki("#en.wiktionary", "https://en.wiktionary.org/w/index.php", "en_wiktionary"));
            wikiinfo.Add(new wiki("#cs.wiktionary", "https://cs.wikipedia.org/w/index.php", "cs_wiktionary"));
            wikiinfo.Add(new wiki("#de.wiktionary", "https://de.wikipedia.org/w/index.php", "de_wiktionary"));
            wikiinfo.Add(new wiki("#fr.wiktionary", "https://fr.wikipedia.org/w/index.php", "fr_wiktionary"));
            wikiinfo.Add(new wiki("#pt.wiktionary", "https://pt.wikipedia.org/w/index.php", "pt_wiktionary"));
            wikiinfo.Add(new wiki("#zh.wiktionary", "https://fr.wikipedia.org/w/index.php", "zh_wiktionary"));
            wikiinfo.Add(new wiki("#es.wiktionary", "https://fr.wikipedia.org/w/index.php", "es_wiktionary"));
            wikiinfo.Add(new wiki("#ru.wiktionary", "https://fr.wikipedia.org/w/index.php", "ru_wiktionary"));
            wikiinfo.Add(new wiki("#it.wiktionary", "https://fr.wikipedia.org/w/index.php", "it_wiktionary"));
            wikiinfo.Add(new wiki("#nl.wiktionary", "https://fr.wikipedia.org/w/index.php", "nl_wiktionary"));
            wikiinfo.Add(new wiki("#ja.wiktionary", "https://fr.wikipedia.org/w/index.php", "ja_wiktionary"));
            wikiinfo.Add(new wiki("#cs.wikipedia", "https://cs.wikipedia.org/w/index.php", "cs_wikipedia"));
            wikiinfo.Add(new wiki("#en.wikipedia", "https://en.wikipedia.org/w/index.php", "en_wikipedia"));
            wikiinfo.Add(new wiki("#de.wikipedia", "https://de.wikipedia.org/w/index.php", "de_wikipedia"));
            wikiinfo.Add(new wiki("#fr.wikipedia", "https://fr.wikipedia.org/w/index.php", "fr_wikipedia"));
            wikiinfo.Add(new wiki("#pt.wikipedia", "https://pt.wikipedia.org/w/index.php", "pt_wikipedia"));
            wikiinfo.Add(new wiki("#zh.wikipedia", "https://fr.wikipedia.org/w/index.php", "zh_wikipedia"));
            wikiinfo.Add(new wiki("#es.wikipedia", "https://fr.wikipedia.org/w/index.php", "es_wikipedia"));
            wikiinfo.Add(new wiki("#ru.wikipedia", "https://fr.wikipedia.org/w/index.php", "ru_wikipedia"));
            wikiinfo.Add(new wiki("#it.wikipedia", "https://fr.wikipedia.org/w/index.php", "it_wikipedia"));
            wikiinfo.Add(new wiki("#nl.wikipedia", "https://fr.wikipedia.org/w/index.php", "nl_wikipedia"));
            wikiinfo.Add(new wiki("#ja.wikipedia", "https://fr.wikipedia.org/w/index.php", "ja_wikipedia"));
            wikiinfo.Add(new wiki("#mediawiki.wikipedia", "https://www.mediawiki.org/w/index.php", "mediawiki"));
            return 0;
        }

        public bool insertString(string WS, string Page)
        {
            wiki site = null;
            Page = Page.Replace("_", " ");
            foreach (wiki Site in wikiinfo)
            {
                if (Site.name == WS)
                {
                    site = Site;
                }
            }
            if (site != null)
            {
                if (channels.Contains(site.channel))
                {
                    IWatch currpage = null;
                    foreach (IWatch iw in pages)
                    {
                        if (iw.Page == Page)
                        {
                            currpage = iw;
                            break;
                        }
                    }
                    if (pages.Contains(currpage))
                    {
                        irc.SlowQueue.DeliverMessage("There is already this string in a list of watched items", channel.name);
                        return true;
                    }
                    pages.Add(new IWatch(site, Page, site.channel));
                    irc.SlowQueue.DeliverMessage("Inserted new item to feed of changes", channel.name);
                    Save();
                    return true;
                }
                irc.SlowQueue.DeliverMessage("Unable to insert the string because the channel is not being watched now", channel.name);
                return false;
            }
            irc.SlowQueue.DeliverMessage("Unable to insert the string to the list because there is no such wiki site known by a bot, contact some developer with svn access in order to insert it", channel.name);
            return false;
        }

        public static void Start()
        {
            channels = new List<string>();
            if (!File.Exists(channeldata))
            {
                File.WriteAllText(channeldata, "");
            }
            try
            {
                string[] list = System.IO.File.ReadAllLines(channeldata);
                foreach (string chan in list)
                {
                    channels.Add(chan);
                }
                Connect();
                while (true)
                {
                    try
                    {
                        string message;
                        while (!RD.EndOfStream)
                        {
                            message = RD.ReadLine();
                            System.Text.RegularExpressions.Match Edit = line.Match(message);
                            if (line.IsMatch(message))
                            {
                                string _channel = message.Substring(message.IndexOf("PRIVMSG"));
                                _channel = _channel.Substring(_channel.IndexOf("#"));
                                _channel = _channel.Substring(0, _channel.IndexOf(" "));
                                string username = Edit.Groups[6].Value;
                                string change = Edit.Groups[7].Value;
                                string page = Edit.Groups[1].Value;
                                string link = Edit.Groups[4].Value;
                                string summary = Edit.Groups[8].Value;

                                if (summary.Length > 20)
                                {
                                    summary = summary.Substring(0, 16) + " ...";
                                }
                                foreach (RecentChanges curr in rc)
                                {
                                    if (curr.channel.feed)
                                    {
                                        foreach (IWatch w in curr.pages)
                                        {
                                            if (w.Channel == _channel && page == w.Page)
                                            {
                                                irc.SlowQueue.DeliverMessage("Change on 12" + w.URL.name + "1 a page " + page + " was modified, summary: " + summary + " changed by " + username + " link " + w.URL.url + "?diff=" + link, curr.channel.name);
                                            }
                                        }
                                    }
                                }
                            }
                            System.Threading.Thread.Sleep(100);
                        }
                        System.Threading.Thread.Sleep(100);
                    }
                    catch (System.IO.IOException)
                    {
                        Connect();
                    }
                    catch (Exception x)
                    {
                        Console.WriteLine(x.Message);
                    }
                }

            }
            catch (Exception x)
            {
                Console.WriteLine(x.Message);
                // abort
            }
        }
    }
}
