#!/usr/bin/perl -w

# RT3 reminder script
# One big reminder report about ALL tickets that are getting to be too long since LastUpdated
# Sends multipart HTML + plaintext email, so HTML capable mail readers can click links
# Many pieces cannibalized from the other reminder scripts in the wiki.

# You will run this script from cron as a user with perms to read the RT_SiteConfig.pm config file
# Crontab would look like this to run twice a day, at 10:15am and 3:15pm
## send unified remind email twice per day
#15 10 * * * /home/crystalfontz/scripts/rt-unifiedreminders
#15 15 * * * /home/crystalfontz/scripts/rt-unifiedreminders

### Configuration

# Location of RT3's libs -- Change this to where yours are
use lib ("/opt/rt/lib");

# list of email addresses who will receive this report
#my(@sendto) = qw[you@yours.com someone@somewhere.com];
my $username = shift;
my (@sendto) = qw[$username];


# Address emails should originate from
my($from) = 'RT Reminder <your@returnaddress.com>';

# maximum number of seconds since LastUpdated, beyond which the ticket will be reported
my %max_untouched_ages = ("new"     => 60 * 60 * 21,       # 21 hrs before warn about new
                          "open"    => 60 * 60 * 45,       # 45 hrs before warn about open
                          "stalled" => 60 * 60 * 24 * 10,  # 10 days before warn about stalled
                         );

my $tickets_per_status = 15; # how many tickets should be included for each status. bigger number = longer email

my $timezone_offset = 7; # how many hours difference is your timezone from GMT? US-Pacific = 7

# Queues to operate on. Default is all
# my @goodqueues = qw[ThisQueue ThatQueue]; # to look only in specific queues, uncomment this line and comment the next one
my @goodqueues = (); # leave like this to look in all queues

# Queues to skip. Default is none. Use either @goodqueues or @badqueues, not both.
my @badqueues = (); # If you have no queues to exclude, uncomment this line and comment the next one
#my @badqueues = qw[Ignoreme IgnoreAnother]; # To exclude certain queues, list them here and comment line above

my($debug) = 0; # nonzero will print plaintext report to STOUT instead of emailing it

# The length at which lines for plaintext mail will be truncated. 78, or thereabouts looks
# best for most people. Setting to 0 will stop lines being truncated.
my($linelen) = 78; # has no effect on HTML mail


### Code

use strict;
use Carp;
use MIME::Lite;
use URI::Escape;

my $msg = MIME::Lite->new(From    => $from,
                          To      =>  join(',', @sendto),
                          Subject => 'Outstanding Tickets Report',
                          Type    => 'multipart/alternative');

# Pull in the RT stuff
package RT;
use RT::Interface::CLI qw(CleanEnv GetCurrentUser GetMessageContent loc);

CleanEnv();       # Clean our the environment
RT::LoadConfig(); # Load the RT configuration
RT::Init();       # Initialise RT

use RT::Date;
use RT::Queue;
use RT::Queues;
use RT::Tickets;

my $max_age = 0;
my $plainbody = "";
my $htmlbody = "<body>";

my $user = new RT::User($RT::SystemUser); # Define an RT User variable
my $tickets = new RT::Tickets($RT::SystemUser); # Used to store Ticket search results
my $date = new RT::Date($RT::SystemUser); # Define a date variable (used for comparisions)
my $now = new RT::Date($RT::SystemUser); # get current time
$now->SetToNow();

# Limit the ticket search to new and open only.
$tickets->LimitStatus(VALUE => 'new');
$tickets->LimitStatus(VALUE => 'open');
$tickets->LimitStatus(VALUE => 'stalled');

my $searchqueue = '';

if($#goodqueues != -1)
{
    $tickets->_OpenParen();
    foreach my $queue (@goodqueues)
    {
        $tickets->LimitQueue(VALUE => $queue, OPERATOR => '=');
    }
    $tickets->_CloseParen();
    $searchqueue = " AND (Queue = '". join("' OR Queue = '", @goodqueues) ."')";
}
elsif($#badqueues != -1)
{
    foreach my $queue (@badqueues)
    {
        $tickets->LimitQueue(VALUE => $queue, OPERATOR => '!=');
    }
    $searchqueue = " AND Queue != '". join("' AND Queue != '", @badqueues) ."'";
}

$tickets->OrderByCols( {FIELD => 'Status', ORDER => 'ASC'},
                       {FIELD => 'queue', ORDER => 'ASC'},
                       {FIELD => 'Priority', ORDER => 'DESC'},
                       {FIELD => 'LastUpdated', ORDER => 'ASC'},
                      );

# We might not want to print the message if there are no tickets
my($printmsg) = 0;

my $j = 0;
my $bgcolor = '';
my $last_status = '';
my $status_title = '';
my $count_by_status = 0;
my $searchURL = '';

# Loop through tickets
while (my $Ticket = $tickets->Next)
{
    # Compare Dates to see if LastUpdated date is old enough to report
    $max_age = $max_untouched_ages{$Ticket->Status} || 0;

    $date->Set(Format => "ISO", Value => $Ticket->LastUpdated);
    if ($now->Unix - $date->Unix < $max_age) { next; } # skip it if too young

    $j++;
    if($j % 2) { $bgcolor = "#e8e8e8"; }
    else { $bgcolor = "#fff"; }

    $user->Load($Ticket->Owner);
    if($printmsg == 0)
    {
        # Put heading on top
        $plainbody .= sprintf "%5s %-7s %3s %-13s %-7s %-6s %-30s\n",
                              "Id", "Status", "Pri", "Updated", "Queue", "Owner", "Subject";
        $htmlbody .= "<table style='width: 900px; white-space: nowrap; border-collapse: collapse;'>
                          <tr>
                              <th>Id</th>
                              <th>Status</th>
                              <th>Pri</th>
                              <th>Updated</th>
                              <th>Queue</th>
                              <th>Owner</th>
                              <th>Subject</th>
                          </tr>\n";
        $printmsg = 1;
    }

    if($Ticket->Status ne $last_status)
    {
        if($count_by_status > $tickets_per_status)
        {
            ($htmlbody, $plainbody) = &see_more_link($searchURL, $count_by_status, $htmlbody, $plainbody);
        }

        $last_status = $Ticket->Status;

        $count_by_status = 0; # reset on every difft status

        $status_title = uc($last_status). " and last updated more than ";
        if($max_age/(60 * 60) <= 48) { $status_title .= ($max_age/(60 * 60))." hours ago"; }
        else { $status_title .= ($max_age/(60 * 60 * 24))." days ago"; }

        # get a date object that can give us the datetime cutoff for this query
        $date->SetToNow();
        $date->AddSeconds( -($max_age + (3600 * $timezone_offset)) ); # add GMT hours offset cos timezone not implemented in RT date object

        $searchURL = RT->Config->Get('WebURL') . "Search/Results.html?Query=".
                     URI::Escape::uri_escape("LastUpdated < '". $date->ISO. "' AND Status = '$last_status'$searchqueue").
                     "&amp;Order=".
                     URI::Escape::uri_escape("ASC|ASC|DESC|ASC").
                     "&amp;OrderBy=".
                     URI::Escape::uri_escape("Status|queue|Priority|LastUpdated");

        $htmlbody .= "    <tr style='background-color: #F5DEB3;'>
                              <td colspan='7' style='padding: 5px; text-align: center;'><b>".
                     "<a href=\"$searchURL\">$status_title</a></b></td></tr>\n";
        $plainbody .= "\n$status_title since ". $date->ISO ."\n";
    }

    $count_by_status++;

    if($count_by_status > $tickets_per_status)
    {
        next;
    }

    # Use our own date formatting routine
    my($updated) = &formatDate($Ticket->LastUpdatedObj->Unix);
    my($subject) = $Ticket->Subject ? $Ticket->Subject : "(No subject)";
    my($queue) = substr($Ticket->QueueObj->Name, 0, 7);

    my($line) = sprintf "%5d  %-7s %3d  %-13s %-7s %-6s %-30s",
                         $Ticket->Id, $Ticket->Status, $Ticket->Priority, $updated, $queue, $user->Name, $subject;

    # Truncate lines if required
    if($linelen)
    {
        $line = substr($line, 0, $linelen);
    }

    $plainbody .= $line ."\n";

    $htmlbody .= "   <tr style='background-color: $bgcolor;'>
                         <td style='text-align: right; padding: 2px;'><a href='".
                 RT->Config->Get('WebURL') . "Ticket/Display.html?id=". $Ticket->Id ."'>".
                 $Ticket->Id ."</a></td>
                         <td style='text-align: center; padding: 2px;'>". $Ticket->Status ."</td>
                         <td style='text-align: right; padding: 2px;'>". $Ticket->Priority ."</td>
                         <td style='padding: 2px;'>". $updated ."</td>
                         <td style='padding: 2px;'>". $queue ."</td>
                         <td style='padding: 2px;'>". $user->Name ."</td>
                         <td style='padding: 2px;'><div style='width: 550px; overflow: hidden;'>". $subject ."</div></td>
                     </tr>\n";
}

# finish up last status group
if($count_by_status > $tickets_per_status)
{
    ($htmlbody, $plainbody) = &see_more_link($searchURL, $count_by_status, $htmlbody, $plainbody);
}

$htmlbody .= "</table></body>\n";


# Send the message
if($printmsg)
{
    ### Alternative #1 is the plain text:
    my $plain = $msg->attach(Type => 'text/plain',
                             Data => [$plainbody]);

    ### Alternative #2 is the HTML-with-content:
    my $fancy = $msg->attach(Type => 'multipart/related');
    $fancy->attach(Type => 'text/html; charset=UTF-8', # utf8 charset to avoid MIME::Lite wide character error
                   Data => [$htmlbody]);

    if ($debug)
    {
        # print "====== Would email this report:\n";
        print "$plainbody\n\n";
        # $msg->print;
        # print $msg->as_string;
        # print $msg->body_as_string;
    }
    else
    {
         $msg->send;
    }
}
else
{
    print "No message to print.\n\n";
}

# Disconnect before we finish off
$RT::Handle->Disconnect();
exit 0;


sub see_more_link() {
    my ($searchURL, $count_by_status, $htmlbody, $plainbody) = @_;
    $htmlbody .= "    <tr>
                         <td colspan='7' style='padding: 7px; text-align: center;'>
                            <a href=\"$searchURL\">See all $count_by_status getting stale in ".
                 uc($last_status) ." status</a></td>
                      </tr>\n";
    $plainbody .= "$searchURL\n";
    return ($htmlbody, $plainbody);
}

# Formats a date like: Thu 10-07-03
# Designed to be consice yet useful
sub formatDate() {
    my($unixtime) = @_;
    my(@days) = ( "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" );
    # Return an empty string if we haven't been given a time
    return "" if $unixtime <= 0;
    my($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($unixtime);
    return sprintf "%s %02d-%02d-%02d", $days[$wday], $mon+1, $mday, $year%100;
}