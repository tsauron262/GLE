#!/opt/local/bin/perl -w
#
# rtadduser: add a local user to RT
# David Maze <dmaze@cag.lcs.mit.edu>
# $Id$
#

use lib "/opt/rt/lib";
use strict;
use English;
use RT::Interface::CLI qw(CleanEnv);
use RT::User;

my $username = shift;
my @result;
if ($username) {
  @result = getpwnam($username);
} else {
  @result = getpwuid($UID);
}

my ($name, $passwd, $uid, $gid, $quota, $comment, $gcos,
    $dir, $shell, $expire) = @result;

CleanEnv();
RT::LoadConfig();
RT::Init();

my $UserObj = new RT::User(RT::SystemUser);
$UserObj->Create(Name => $name,
         EmailAddress => "$name\@cag.lcs.mit.edu",
                 RealName => $gcos,
         Privileged => 1);
