#!/bin/sh
#Written by benapetr@gmail.com

temp=/tmp/parser_`date +%H%M%d%m`
if [ -f parser ];then
        if [ -d "$temp" ];then
                rm -rf "$temp"
        fi
        if [ -d "$temp" ];then
                echo "Fatal: can't clean $temp!"
                exit 1
        fi
        mkdir "$temp"
        wget https://labsconsole.wikimedia.org/wiki/Special:Ask/-5B-5BResource-20Type::instance-5D-5D/-3FInstance-20Name/-3FInstance-20Type/-3FProject/-3FImage-20Id/-3FFQDN/-3FLaunch-20Time/-3FPuppet-20Class/-3FModification-20date/-3FInstance-20Host/-3FNumber-20of-20CPUs/-3FRAM-20Size/-3FAmount-20of-20Storage/limit%3D500/format%3Djson -O $temp/novalist
        if [ "`diff old/* $temp/*`" = "" ];then
                echo OK
        out=`./parser "$temp"`
        if [ `echo $out | grep -c Error` -gt 0 ];then
                echo Unable to parse the file
                exit 0
        fi
        instances=`echo "$out" | awk '{ if ($0 ~ /SSH:/) exit } { print $0 }'`
        list1=`echo "$out" | awk '{ if (p==1) print $0; p=2 } { if ($0 ~ /WWW:/) p=1 }'`
        list2=`echo "$out" | awk '{ if (p==1) print $0; p=2 } { if ($0 ~ /SSH:/) p=1 }'`
        for instance in `echo "$instances"`
        do
                cat default.cfg | sed "s/NAME/$instance/" > conf/$instance".cfg"
        done
        cp generic/* conf
        cat hostgroup | sed "s/#web/$list1/" | sed "s/#SSH/$list2/" > conf/hostgroups_nagios2.cfg
        cp /etc/nagios3/conf.d/* backup
        rm -f /etc/nagios3/conf.d/*
        mv conf/* /etc/nagios3/conf.d
        service nagios3 reload
        fi
        mv "$temp"/* old
        rm -rf "$temp"
else
        echo "Can't find binary file"
        exit 1
fi

