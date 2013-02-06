#!/usr/bin/perl
####################### RT Email Notification Script ####################
####
#### Author: Daniely Yoav / Qball Technologies Ltd.
#### Email: yoavd@qballtech.net
#### Date: 05/06/05
####
#### Modified by: Tomas Borland Valenta
#### Email: tomas at trustica dot cz
#### Date: 2007/03/12
####
#### Modified by: Tim Schaller
#### Email: tim-rt@torzo.com
#### Date: 2008/06/17
####
#### Modified by: Vaclav Vobornik
#### Email: vaclav dot vobornik at commerzbank dot com
#### Date: 2008/07/04
####
#### Purpose: Send Email Notification on all open/new tickets in RT that have their due date expired
####
#### Version: 3.1
####
#### Changes from 3 ( Vaclav Vobornik )
#### - Added Cc and Bcc possibilities.
####
#### Changes from 2 ( Tim Schaller )
#### - Added multiple command line options.
#### - Adaptive subject line.
#### - Sending Admin CC emails optional.
####
#### Changes from 1.2 ( Tomas Borland Valenta )
#### - rewriten mail subsystem
#### - code cleanup
#### - adopted for RT 3.6.x
#### - used some global RT config variables
####
#### ======================================================================
####
#### Command line options
####          -d          : turns dubugging on
####          -A          : Send to AdminCC ( made default not to )
####          -a <n>      : Send reminders to tickets due in <n> or less days.
####                      : Includes overdue tickets.
####          -q <queue>  : Only send reminder for tickets in <queue>
####          -o <owner>  : Only send reminders for tickets owned by <owner>
####          -c <email>  : Email in Cc
####          -b <email>  : Email in Bcc
####
#### ======================================================================
####
####  Usage: Invoke via cron every working day at 8 morning
####  0 8 * * 1-5 /path/to/script/remind_email_due.pl -A
####
####  Usage: Invoke via cron every working day at 8 morning
####       : and send notices to everyone in the SysAdmin Queue
####  0 8 * * 1-5 /path/to/script/remind_email_due.pl -q SysAdmin
####
####  Usage: Invoke via cron every working day at 8 morning
####       : and send notices to everyone in the SysAdmin Queue
####       : Remind them about tickets due in less then 5 days.
####  0 8 * * 1-5 /path/to/script/remind_email_due.pl -a 5 -q SysAdmin
####
####  Usage: Invoke via cron every working day at 8 morning
####       : and send notices to everyone in the SysAdmin Queue.
####       : Send copies to all AdminCC on the tickest.
####  0 8 * * 1-5 /path/to/script/remind_email_due.pl -A -q SysAdmin

### External libraries ###
use strict;
use Getopt::Std;

use lib ("/opt/rt/lib");  # Change this to your RT lib path!
package RT;
use RT::Interface::CLI qw(CleanEnv GetCurrentUser GetMessageContent loc);
use RT::Date;
use RT::Queue;
use RT::Queues;
use RT::Tickets;

################## Init ##################
# Clean our environment
CleanEnv();
# Load the RT configuration
RT::LoadConfig();
RT::Init();
# Set config variables
my $debug=0;
my $from_address = $RT::CorrespondAddress; #From: address used in reports
my $rt_url = $RT::WebURL;
my $sendmail = "$RT::SendmailPath $RT::SendmailArguments";

################## Args ##################
my $queue         = '';
my $owner         = '';
my $advDate       = 0;
my $secInDay      = 60*60*24;
my $sendToAdminCC = 0;
my $cc            = '';
my $bcc           = '';

my %options=();
Getopt::Std::getopts("Ada:q:o:c:b:",\%options);

$queue         = $options{q} if defined $options{q};
$owner         = $options{o} if defined $options{o};
$advDate       = ( $options{a} * $secInDay ) if defined $options{a};
$debug         = $options{d} if defined $options{d};
$sendToAdminCC =  $options{A} if defined $options{A};
$cc            = $options{c} if defined $options{c};
$bcc           = $options{b} if defined $options{b};


################## Variables Init ##################
my $User = new RT::User($RT::SystemUser); # Define an RT User variable
my $date = new RT::Date($RT::SystemUser); # Define a date variable (used for comparisions)
my $tickets = new RT::Tickets($RT::SystemUser); # Used to store Ticket search results
my $now = new RT::Date($RT::SystemUser); # get current time
$now->SetToNow();
my $report; # Used for output
my $subject; # Used as subject line

################## Main Program ##################
# Limit the ticket search to new and open only.
$tickets->LimitStatus(VALUE => 'new');
$tickets->LimitStatus(VALUE => 'open');

# Loop through new/open tickets
while (my $Ticket = $tickets->Next) {
    # Construct POP-Up Message
    $User->Load($Ticket->Owner);

    # Compare Dates to check whether the ticket's due date is in the past + Due date exists
    $date->Set(Format => "ISO",Value => $Ticket->Due);
    if ($now->Unix - $date->Unix < (1 - $advDate ) or $date->Unix == -1 or $date->Unix == 0) { next; }

    # Compare owner and queue if given. Skip current ticket if invalid.
    if ($owner) { if ( lc($User->Name) ne lc($owner) ) { next; } }
    if ($queue) { if ( lc($Ticket->QueueObj->Name) ne lc($queue) ) { next; } }

    # Generate a report
    $report = "";
    $report .= "Ticket #: " . $Ticket->id . "\n";
    $report .= "Subject:  " . $Ticket->Subject . "\n";
    $report .= "Queue:    " . $Ticket->QueueObj->Name . " (". $Ticket->QueueObj->AdminCcAddresses .") \n";
    $report .= "Owner:    " . $User->Name ."\n";
    $report .= "Due date: " . $date->ISO . "\n";
    $report .= "URL:      " . $rt_url . "Ticket/Display.html?id=" . $Ticket->id . "\n";

    # Set the subject based on the due date.
    if( ($now->Unix - $date->Unix < 0  ) or $date->Unix == -1 ) {
        $subject =  "Ticket #". $Ticket->id . " with owner " . $User->Name ." is due on " . $date->ISO;
    } else {
        $subject =  "Ticket #". $Ticket->id . " with owner " . $User->Name ." is overdue";
    }

    # Get Queue Admin CC
    # Do we send to Admin CC as well as to owner?
    my @emails = ();
    if ( $sendToAdminCC ) {
        @emails = ($User->EmailAddress, split(/,/, $Ticket->AdminCcAddresses), split(/,/ , $Ticket->QueueObj->AdminCcAddresses));
    } else {
        @emails = ($User->EmailAddress);
    }

    # remove duplicates
    my %temp = (); @emails = grep ++$temp{$_} < 2, @emails;
    send_report(@emails);
}

# Close RT Handle
$RT::Handle->Disconnect();
exit 0;

# This procedure will send a report by mail to the owner
#  parameter 1 - email addresses to send to
# Global variables refered to:
#  $subject - Subject line
#  @report - Message content
#  $from_address - address to send from
#  $cc - CarbonCoby email address
#  $bcc - BlindCarbonCopy email address
sub send_report {
    my @tos = @_;
    my $addr;

    foreach $addr (@tos) {
        next if (length($addr) == 0);
        my $msg = "";
        $msg .= "From: $from_address\n";
        $msg .= "To: $addr\n";
        $msg .= "Cc: $cc\n" if $cc;
        $msg .= "Bcc: $bcc\n" if $bcc;
        $msg .= "Subject: $subject\n";
        $msg .= "\n";
        $msg .= $report;

        if ($debug) {
            print "====== Would call '$sendmail' with this input:\n";
            print "$msg\n\n";
        } else {
            open(SENDMAIL, "|$sendmail") || die "Error sending mail: $!";
            print SENDMAIL $msg;
            close(SENDMAIL);
        }
    }
}
