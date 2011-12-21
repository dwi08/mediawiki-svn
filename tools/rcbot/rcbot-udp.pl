#!/usr/bin/perl

use strict;
use warnings;
use POE qw(Component::IRC Component::IRC::Plugin::Connector);
use IO::Socket::INET;

use constant DATAGRAM_MAXLEN => 1024;

my $nickname = 'rcbot' . $$;
my $ircname  = 'RCBot for a Wiki';
my $UDPport  = 8675;                 #UDP Port to listen on
my $debug    = 0;                    #Only set if you really really mean it
my $spam     = 0;                    #For High-Volume wikis, to keep up with RC Feed, set to 1.
                                     #NOTE: May require special ircd configuration or bot may
                                     #be disconnected for flooding.

my $settings = {
    'irc.yourserver.com'  => { port => 6667, channels => ['#WPFeed'], },
    'irc.otherserver.com' => { port => 6667, channels => ['#OtherChan'], },
};

foreach my $server ( keys %{$settings} ) {
    POE::Component::IRC->spawn(
        alias   => $server,
        nick    => $nickname,
        ircname => $ircname,
        Flood   => $spam,
    );
}

POE::Session->create(
    package_states => [
        'main' => [
            qw(
                irc_start
                irc_registered
                irc_001
                _default )
        ],
    ],
    inline_states => {
        _start       => \&server_start,
        get_datagram => \&server_read,
    },
    heap => { config => $settings },
);

POE::Kernel->run();
exit;

sub irc_start {
    my ( $kernel, $session ) = @_[ KERNEL, SESSION ];
    $kernel->signal( $kernel, 'POCOIRC_REGISTER', $session->ID(), 'all' );
    undef;
}

sub server_start {
    my ( $kernel, $session ) = $_[KERNEL];

    my $socket = IO::Socket::INET->new(
        Proto     => 'udp',
        LocalPort => $UDPport,
    );

    die "Couldn't create server socket: $!" unless $socket;
    $kernel->select_read( $socket, "get_datagram" );
    $kernel->yield('irc_start');
    undef;
}

sub server_read {
    my ( $kernel, $heap, $socket ) = @_[ KERNEL, HEAP, ARG0 ];

    my $remote_address = recv( $socket, my $message = "", DATAGRAM_MAXLEN, 0 );
    return unless defined $remote_address;

    my ( $peer_port, $peer_addr ) = unpack_sockaddr_in($remote_address);
    my $human_addr = inet_ntoa($peer_addr);
    print "(server) $human_addr : $peer_port sent us $message\n" if $debug;

    my $servers = $heap->{senders};
    foreach my $alias ( keys %{$servers} ) {
        my @channels = @{ $heap->{config}->{$alias}->{channels} };
        $kernel->post( $alias => privmsg => $_ => $message ) for @channels;
    }
}

sub irc_registered {
    my ( $kernel, $heap, $sender, $irc_object ) = @_[ KERNEL, HEAP, SENDER, ARG0 ];
    my $alias = $irc_object->session_alias();

    print "PoCo registered\n" if $debug;
    $heap->{senders}->{$alias} = $sender;

    $heap->{connectors}->{$alias} = POE::Component::IRC::Plugin::Connector->new();
    $irc_object->plugin_add( 'Connector' => $heap->{connectors}->{$alias} );

    my %conn_hash = (
        server => $alias,
        port   => $heap->{config}->{$alias}->{port},
    );

    $kernel->post( $sender, 'connect', \%conn_hash );
    undef;
}

sub irc_001 {
    my ( $kernel, $heap, $sender ) = @_[ KERNEL, HEAP, SENDER ];

    my $poco_object = $sender->get_heap();
    print "Connected to ", $poco_object->server_name(), "\n" if $debug;

    my $alias    = $poco_object->session_alias();
    my @channels = @{ $heap->{config}->{$alias}->{channels} };
    $kernel->post( $sender => join => $_ ) for @channels;

    undef;
}

sub _default {
    my ( $event, $args ) = @_[ ARG0 .. $#_ ];
    my @output = ("$event: ");

    return unless $debug;
    foreach my $arg (@$args) {
        if ( ref($arg) eq 'ARRAY' ) {
            push( @output, "[" . join( " ,", @$arg ) . "]" );
        } else {
            push( @output, "'$arg'" );
        }
    }
    print STDOUT join ' ', @output, "\n";
    return 0;
}

