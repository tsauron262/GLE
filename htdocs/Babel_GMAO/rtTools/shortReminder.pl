#!/opt/local/bin/perl -w
#
# This script outputs all reminders that are due within the next e.g. 2 days
# and mails the result to the corresponding owners. christian <ch AT westend.com>
#####
# FING - UdelaR
# Modifed by Mario A. del Riego <delriego AT fing DOT edu DOT uy>
# Added tainting checks
# Fixed an old variable dont defined
# Changed mail format (ticket id added)
# Getopt added for parameters: --debug --reminder --ticket --interval and --help
# File: http://www.fing.edu.uy/~delriego/RT_Reminder.pl
# v 1.1

package RT;

# Show reminders that are due between today and the day after tomorrow.
## Bug fixed by sean <smahan (works for) smwm.com>
my $CONFIG_DUE_DAYS = 2;
# The mail "From" address.
my $CONFIG_FROM = 'rt-reminder@example.com';
# The mail "To" address if the Reminder *or* Ticket owner is Nobody.
my $CONFIG_TO_NOBODY = 'rt-reminder@example.com';
# The mail "Subject" line.
my $CONFIG_SUBJECT = 'RT Reminder';

# Send mail to REMINDER OWNER
my $ReminderOwner;
# Send mail to TICKER OWNER
my $TicketOwner;
# Only show the mails for debugging
my $debug = 0;
my $interval = undef;

my $help;

##########################################################################

use strict;
use lib '/opt/rt/lib/';
use Getopt::Long;
use Data::Dumper;
use Date::Calc qw(Today Add_Delta_Days);
use RT::Interface::CLI qw(CleanEnv GetMessageContent loc);

# Clean our the environment
CleanEnv();
# Load the RT configuration
RT::LoadConfig();
# Initialise RT
RT::Init();
# Load rest of the RT stuff (seems to go after initialization)
use RT::Date;
use RT::Queue;
use RT::Tickets;
use RT::Action::SendEmail;


sub usage {
    print "--reminder|-r    Send mail to REMINDER OWNER\n";
    print "--ticket|-t  Send mail to TICKER OWNER\n";
    print "--interval|-i <int>  Days left to end time (default < $CONFIG_DUE_DAYS)\n";
    print "--debug|-d   Only show mails to send for debugging\n";
    print "--help|-h    This help!\n";
    exit;
}

if(not GetOptions(
            'r|reminder' => \$ReminderOwner,
            't|ticket' => \$TicketOwner,
            'd|debug' => \$debug,
            'i|interval=i' => \$interval,
            'h|help' => \$help
                )
        ) {
        usage();
}

if ($help) { usage(); }
if (not defined $interval) { $interval = $CONFIG_DUE_DAYS; }

# Calculate date boundaries.
my($dy,$dm,$dd) = Today();
my $d_now  = sprintf('%04d-%02d-%02d 00:00:00', $dy, $dm, $dd);
my $d_then = sprintf('%04d-%02d-%02d 23:59:59', Add_Delta_Days($dy, $dm, $dd, $interval));

# Fetch list of matching tickets.
my $tickets = RT::Tickets->new($RT::SystemUser);
$tickets->FromSQL(
               'Type = "reminder" AND '.
               '(Status = "new" OR Status = "open") AND '.
               'Due >= "'.$d_now.'" AND '.
               'Due <= "'.$d_then.'"');
$tickets->OrderBy(FIELD => 'Due', ORDER => 'DESC');

# Format result and group by e-mail address.
my %rcpts = ();
while (my $ticket = $tickets->Next) {
   my $out;

   # Format:
   # "Reminder: <subject_reminder> ([RT #<TicketID> <TicketSubject>])
   #     Terminar en <left_hour> hours"

    my $t = RT::Ticket->new($RT::SystemUser);
    $t->Load($ticket->RefersTo->First->LocalTarget);

        $out = sprintf(
                "Reminder: %s ([RT #%s] %s )\n".
                        "    Termina en %s\n".
                        "\n",
                $ticket->Subject, $ticket->RefersTo->First->LocalTarget, $t->Subject,
         loc($ticket->DueObj->AgeAsString));

    my $tmp_rcpt_reminder = undef;
    if ($ReminderOwner) {
        # Push reminder to array of distinct e-mail addresses for this ticket.
        $tmp_rcpt_reminder = $ticket->OwnerObj->EmailAddress || $CONFIG_TO_NOBODY;
        if (not defined $rcpts{$tmp_rcpt_reminder}) { $rcpts{$tmp_rcpt_reminder} = "" };
        $rcpts{ $tmp_rcpt_reminder } .= $out;
    }

    if ($TicketOwner) {
        #Notify ticket owner or "nobody" if ticket is unowned
        my $tmp_rcpt_ticket   = $t->OwnerObj->EmailAddress || $CONFIG_TO_NOBODY;
        if( defined $CONFIG_TO_NOBODY && $tmp_rcpt_ticket ne $tmp_rcpt_reminder ){
        $rcpts{ $tmp_rcpt_ticket } .= $out;
        }
    }

}


# Iterate over each of the tickets and send the email
foreach my $rcpt (keys %rcpts) {
    my $mail = "From: $CONFIG_FROM\n".
               "To: $rcpt\n".
               "Subject: $CONFIG_SUBJECT\n".
               "\n".
               $rcpts{$rcpt};
    if ($debug) {
        print $mail . "\n";
    } else {
        # FIXME: Is there no proper RT library for this?
        open(MAIL, "| $RT::SendmailPath $RT::SendmailBounceArguments $RT::SendmailArguments") or die("open  sendmail: $!");
        print(MAIL $mail) or die("print sendmail: $!");
        close(MAIL) or die("close sendmail: $!");
    }
}