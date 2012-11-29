#!/bin/bash
#
# backupRTDB.sh
#
# $Author: www-data $
# $Revision: 1.3 $
# $Id: backupRTDB,v 1.3 2006/04/10 15:48:21 www-data Exp $
#
# Runs a mysqldump backup of the contents of the
# RT Database.
#
# Into files named rtdb-main-{date}.tar.gz
#                  rtdb-attach-{date}.tar.gz
#
# This script assumes that root can login to mysql locally
# without a password. If you change this then you will need to revise
# the connection strings.
#

#
# Implementation Specific Variables
#
# You can change this to another directory for the tmp files.
#  I don't like to dump in /tmp
SCRTMPDIR=/root/tmp/
MAILHEADER=$SCRTMPDIRrtbakfiles-mail
rtDB=###YOURRTDATABASENAME###
rtbakWATCHER=###YOUREMAIL###
MAILSERVER=###YOUREMAILSERVER###
rtUSER=###YOURRTUSERNAME###

#
# Script Variables
#
curday=`date +"%Y%m%d%H%M%S"`
backdir=/var/backups/rt/
mainprefn="rtdb-main-"
attachprefn="rtdb-attach-"
mainfullfn=$backdir$mainprefn$curday.gz
attachfullfn=$backdir$attachprefn$curday.gz
rtdbbakCHANGES_FILE=$SCRTMPDIRrtbakfiles
HOSTNAME=`/bin/hostname`
FQDN=`/bin/hostname -f`
rtMainTables="ACL Attributes CachedGroupMembers CustomFieldValues CustomFields GroupMembers \
Groups Links ObjectCustomFieldValues ObjectCustomFields Principals Queues ScripActions \
ScripConditions Scrips Templates Tickets Transactions Users sessions"

#
# Make/Verify target dirs
#
if [ ! -d $backdir ]; then
  mkdir $backdir;
fi

if [ ! -d $SCRTMPDIR ]; then
  mkdir $SCRTMPDIR;
fi

# do the Main backup
mysqldump $rtDB --opt $rtMainTables  | gzip > $mainfullfn

# do the Attachment backup
mysqldump $rtDB --opt --default-character-set=binary Attachments  | gzip > $attachfullfn


#create message file and send to watcher
if [ -s $CHANGES_FILE ] ; then
  echo HELO $MAILSERVER > $MAILHEADER
  echo MAIL FROM:root@$FQDN  >> $MAILHEADER
  echo RCPT TO:$rtbakWATCHER >> $MAILHEADER
  echo DATA >> $MAILHEADER
  echo Subject: $HOSTNAME - $curday - RT Database backup >> $MAILHEADER
  echo Backup of files from the RT install on ${HOSTNAME}: >> $MAILHEADER
  echo This file is located at: >> $MAILHEADER
  echo ${HOSTNAME}:$mainfullfn >> $MAILHEADER
  echo >> $MAILHEADER
  echo . >> $MAILHEADER
  echo QUIT >> $MAILHEADER
  exim4 -bs < $MAILHEADER
fi
