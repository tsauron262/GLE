#!/usr/bin/perl -w

#todo :> install.sh => include cpan libs

use DBI;
use Proc::PID::File;
use Proc::Daemon;
use SOAP::Lite;

BEGIN{

}

# did we get a stop command?
  if (@ARGV && $ARGV[0] eq "stop")
  {
    # we need to send a signal to the running process to tell it
    # to quit
    # get the pid file (in /var/run by default)
    my $pid = Proc::PID::File->running(name => "syncDaemon");
    unless ($pid)
     { die "Not already running!" }
    # and send a signal to that process
    kill(2,$pid);  # you may need a different signal for your system
    print "Stop signal sent!\n";
    exit;
  }

    Proc::Daemon::Init;
# write the pid file, exiting if there's one there already.
  # this pid file will automatically be deleted when this script
  # exits.
  if (Proc::PID::File->running(name => "syncDaemon"))
   { die "Already running!" }

my $LOG = "/var/log/magento_daemon.log";
my $ERR = "/var/log/magento_daemon_err.log";

open(STDOUT, ">>$LOG")
    or die "Failed to re-open STDOUT to $LOG";
open(STDERR, ">>$ERR")
    or die "Failed to re-open STDERR to $ERR";


while (1)
{
    exit if $::exit;
    system('php syncTools.php');
    sleep(60);
    exit if $::exit;

}
END{
}
