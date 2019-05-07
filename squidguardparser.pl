#!/usr/bin/perl

# squidGuard Reports.
# Copyright (C) 2019  Ramón Román Castro <ramonromancastro@gmail.com>

# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

# -------------------- INITILIZATION  ------------------------------

use File::Basename;
use Time::Local;

push (@INC,(fileparse($0))[1]);
require "squidguardparser.cfg";

# -------------------- GLOBAL CONFIG  ------------------------------

my $squidGuardConf = "/etc/squid/squidGuard.conf";
my $reportpath = "/var/www/html/squidguard/report";
my $linesProcessed = 1000;
my $debug = 1;

# -------------------- GLOBAL VARIABLES  ---------------------------

my $undefIdent="-";

my $logdir,@logs,$logscnt;
undef @logs;$logscnt=0;

my %totalhits;undef %totalhits;
my %peruser;undef %peruser;

my $filterdate;
my $filterdatestart;

my $totallines,$parsedlines,$skipfilterdatecntr;

# -------------------- FUNCTIONS -----------------------------------

sub getLPS($$) {
  my $time=shift;
  my $lines=shift;
  $time||=1;
  $lines||=1;
  return ($lines/$time);
}

sub MakeHtmlTable{
	print 
}

sub DebugMessage{
	my ( $msg, $level ) = @_;
	print ">>> $msg\n" if ($debug >= $level);
}

sub MakeReport{

	my $reppath,$total,$tmp,$tmpuser,$tmphits;
	my ( $tdate ) = @_;
	
	print "Making report $tdate ...\n";
	
	$tmp="";$tmpuser=0;$tmphits=0;
	
	# Create day dir
	$reppath="$reportpath/$tdate";
	unless ( -d $reppath ){ mkdir $reppath, 0755 or die "Can't create dir '$reppath': $!"; }
	
	# Create .total file
	open TOTALFILE,">$reppath/.total" || die "can't create file	 $reppath/.total - $!";
	foreach $tuser (sort {$totalhits{$tdate}{$b} <=> $totalhits{$tdate}{$a}} keys %{$totalhits{$tdate}} ) {
		$tmpuser++;
		$tmphits+=$totalhits{$tdate}{$tuser};
		$tmp.=sprintf("%s %s\n",$tuser,$totalhits{$tdate}{$tuser}+0);
		
		# Create .dest file
		open DESTFILE,">$reppath/.dest" || die "can't create file	 $reppath/.dest - $!";
		foreach $tdest (sort {$totaldest{$tdate}{$b} <=> $totaldest{$tdate}{$a}} keys %{$totaldest{$tdate}} ) {
			printf DESTFILE ("%s %s %s\n",$tdest,$totaldest{$tdate}{$tdest}+0);
		}
		close DESTFILE;
		
		# Create user file
		open USERFILE,">$reppath/$tuser" || die "can't create file	 $reppath/$tuser - $!";
		foreach $tsite (sort {$usersite{$tdate}{$tuser}{$b} <=> $usersite{$tdate}{$tuser}{$a}} keys %{$usersite{$tdate}{$tuser}} ) {
			printf USERFILE ("%s %s %s %s %s %s %s %s %s %s %s %s %s %s %s %s %s %s %s %s %s %s %s %s %s %s %s\n",$tsite,$usersitedest{$tdate}{$tuser}{$tsite}{'destination'},$usersite{$tdate}{$tuser}{$tsite}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'00'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'01'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'02'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'03'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'04'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'05'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'06'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'07'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'08'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'09'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'10'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'11'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'12'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'13'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'14'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'15'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'16'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'17'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'18'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'19'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'20'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'21'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'22'}+0,$usersitedest{$tdate}{$tuser}{$tsite}{'23'}+0);
		}
		close USERFILE;
	}
	
	print TOTALFILE "user: $tmpuser\n";
	print TOTALFILE "hits: $tmphits\n";
	print TOTALFILE "$tmp";
	close TOTALFILE;
};


sub ReadSquidGuardConfig(){
	print "Reading squidGuard configuration file ...\n";
	my $line;
	open FF, $squidGuardConf || die "can't access config file $squidGuardConf\n";
	while (<FF>) {
		chomp;
		$line = $_;
		if ($line =~ m/^\s*logdir\s+(.+)$/o) { $logdir = $1; DebugMessage("squidGuard logdir: $1",2); }
		if ($line =~ m/^\s*log\s+(.+)$/o) { $logs[$logscnt] = $1; $logscnt++; DebugMessage("squidGuard log: $1",2);}
	}
};


sub ProcessSquidGuardLogs(){
	print "Processing log files ...\n";
	my $logfile;
	$totallines = 0;
	foreach(@logs){
		if ($_ =~ m/^\/.*$/o) { $logfile = "$_"; } else { $logfile = "$logdir/$_"; }
		DebugMessage("Processing log file $logfile",2);
		open (FF, $logfile) || die "can't access log file $logfile\n";
		while (<FF>) {
			$totallines++;
			chomp;
			# 2019-04-05 09:25:30 [26455] Request(default/mobile-phone/-) market.android.com:443 192.168.1.1/pc-user.epgpc.epgpc - CONNECT REDIRECT
			($date,$time,$pid,$request,$url,$client,$user,$method,$result)=split();
			
			# Filter date
			if ($filterdate) {
				if ($date ne $filterdate) {
					$skipfilterdatecntr++;
					next;
				}
			}
			
			($source,$destination,$rewrite)=split("/",$request);
			($ip,$fqdn)=split("/",$client);

			if ($url =~ m/([a-z]+:\/\/)??([a-z0-9\-]+\.){1}(([a-z0-9\-]+\.){0,})([a-z0-9\-]+){1}(:[0-9]+)?(\/(.*))?/o) {
			   $site = $2.$3.$5;
			} else {
			   $site = $url;
			}
			$site = $url if ($site eq "");
			$user = $ip if ($user eq $undefIdent);
			($hour,$minute,$second)=split(":",$time);
			
			$parsedlines++;
			
			$totalhits{$date}{$user}++;
			$totaldest{$date}{$destination}++;
			$usersite{$date}{$user}{$site}++;
			$usersitedest{$date}{$user}{$site}{'destination'}=$destination;
			$usersitedest{$date}{$user}{$site}{$hour}++;
			
			
			print "$totallines lines readed ...\n" if (!($totallines % $linesProcessed));
		}
		close (FF);
	}
}

sub ExecutionSummary(){
	$worktime = ( time() - $^T );
	print "run TIME: $worktime sec\n";
	print "squidGuard parser statistic report\n\n";
	printf( "	   %10u lines processed (average %.2f lines per second)\n",
		$totallines, getLPS( $worktime, $totallines ) );
	printf( "	   %10u lines parsed\n",				  $parsedlines );
	printf( "	   %10u lines skiped by date filter\n",	  $skipfilterdatecntr );

	if ( $parsedlines == 0 ) {
		print "\nWARNING !!!!, parsed 0 lines from total : $totallines\n";
		print "please check configuration !!!!\n";
		print "may be wrong log format selected ?\n";
	}
}
# -------------------- MAIN CODE -----------------------------------

# Process parameters

$fToday=1 if ($ARGV[0] eq "today");
$fToday=1 if ($ARGV[0] eq "yesterday");

if ($fToday) {
   ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime;
   $filterdate=sprintf("%04d-%02d-%02d",$year+1900,$mon+1,$mday);;
   $filterdatestart=timelocal( 0, 0, 0,$mday,$mon,$year);
   DebugMessage("Filter today: $filterdate",1);
}

if ($ARGV[0] eq "yesterday") {
   $filterdatestart=$filterdatestart-(24*60*60);
   ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($filterdatestart);
   $filterdate=sprintf("%04d-%02d-%02d",$year+1900,$mon+1,$mday);;
   DebugMessage("Filter yesterday: $filterdate",1);
}

if ($ARGV[0] =~ m/^(\d\d\d\d)\-(\d\d)\-(\d\d)$/) {
   $filterdate=$ARGV[0];
   $filterdatestart=timelocal( 0, 0, 0,$3,$2-1,$1);
   ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($filterdatestart);
   $filterdate=sprintf("%04d-%02d-%02d",$year+1900,$mon+1,$mday);;
   DebugMessage("Filter date: $filterdate",1);
}

ReadSquidGuardConfig();
ProcessSquidGuardLogs();
foreach $tdate (sort keys %totalhits) {
	MakeReport($tdate);
}
ExecutionSummary();

print "Process finished.\n";
