#!/bin/bash
#
# backupRT.sh
#
# $Author: www-data $
# $Revision: 1.5 $
# $Id: backupRT,v 1.5 2006/04/10 15:55:01 www-data Exp $
#
# Runs a file level backup of the contents of the following
# directories:
#
# /usr/local/share/request-tracker3.4
# /usr/share/request-tracker3.4
#
# Into a file named rtappbak-{date}.tar.gz
#

#
# Implementation Specific Variables
#
# You can change this to another directory for the tmp files.
# I don't like to dump in /tmp

SCRTMPDIR=/tmp/
MAILHEADER=$SCRTMPDIRrtbakfiles-mail
rtbakWATCHER=shift
MAILSERVER=shift

#
# Script Variables
#
curday=`date +"%Y%m%d%H%M%S"`
prefn="rtappbak-"
backdir=/var/backups/rt/
fullfn=$backdir$prefn$curday.tgz
rtbakCHANGES_FILE=/tmp/rtbakfiles
HOSTNAME=`/bin/hostname`
FQDN=`/bin/hostname -f`
fullFILENAME=`pwd`$fullfn

#
# Make/Verify target dirs
#
if [ ! -d $backdir ]; then
  mkdir $backdir;
fi

if [ ! -d $SCRTMPDIR ]; then
  mkdir $SCRTMPDIR;
fi

# do the backup
tar cvzf $fullfn /opt/rt

#get the file list and send to $rtbakCHANGES_FILE
tar tvzf $fullfn > $rtbakCHANGES_FILE

#create message file and send to watcher
if [ -s $CHANGES_FILE ] ; then
  echo HELO $MAILSERVER > $MAILHEADER
  echo MAIL FROM:root@$FQDN  >> $MAILHEADER
  echo RCPT TO:$rtbakWATCHER >> $MAILHEADER
  echo DATA >> $MAILHEADER
  echo Subject: $HOSTNAME RT Application Backup >> $MAILHEADER
  echo Backup of files from the RT install on ${HOSTNAME}: >> $MAILHEADER
  echo This file is located at: >> $MAILHEADER
  echo ${HOSTNAME}:$fullFILENAME >> $MAILHEADER
  echo >> $MAILHEADER
  cat $rtbakCHANGES_FILE >> $MAILHEADER
  echo . >> $MAILHEADER
  echo QUIT >> $MAILHEADER
  exim4 -bs < $MAILHEADER
fi
