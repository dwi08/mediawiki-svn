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
using System.Text;
using System.Net;


namespace wmib
{
public class HtmlDump
    {
        public config.channel Channel;
        public string dumpname;
        public static void Start()
        {
            while (true)
            {
                foreach (config.channel chan in config.channels)
                {
                    if (chan.Keys.update)
                    {
                        HtmlDump dump = new HtmlDump(chan);
                        dump.Make();
                        chan.Keys.update = false;
                    }
                }
                System.Threading.Thread.Sleep(320000);
            }
        }
        public HtmlDump(config.channel channel)
        {
            dumpname = config.DumpDir + "/" + channel.name + ".htm";
            Channel = channel;
        }
        public string CreateFooter()
        {
            return "</body></html>\n";
        }
        public string CreateHeader()
        {
            return "<html><head></head><body>\n";
        }
        public string Encode(string text)
        {
            text = text.Replace("<", "&lt;");
            text = text.Replace(">", "&gt;");
            return text;
        }
        public string AddLine(string name, string value)
        {
            return "<tr><td>" + Encode(name) + "</td><td>" + Encode(value) + "</td></tr>\n";
        }
        public void Make()
        {
            string text;
            text = CreateHeader();
            text = text + "<table border=1 width=100%>\n<tr><td width=10%>Key</td><td>Value</td></tr>\n";
            if (Channel.Keys.text.Count > 0)
            {
                foreach (irc.dictionary.item Key in Channel.Keys.text)
                {
                    text = text + AddLine(Key.key, Key.text);
                }
            }
            text = text + "<table>\n";
            text = text + CreateFooter();
            System.IO.File.WriteAllText(dumpname, text);
        }
    }
}
