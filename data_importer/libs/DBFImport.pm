#!/usr/bin/perl

use strict;
use warnings;
use diagnostics;

##
# Modules
##

use Exporter;

use DBI;
use lib "libs/";
use mysqlMeta;

our @ISA = ('Exporter', 'DBI', 'mysqlMeta');
our @EXPORT_OK = ('dbf_import');

package DBFImport;

##
# Function to load a DBF file
# @param string $dir
# @return array_ref data with data->[x] = hashref (clnname, clvalue)
sub dbf_import {
	my ($dir) = @_;
	my ($row, @data);
	our $dbh = DBI->connect ("DBI:XBase:$dir") or die $DBI::errstr;
	my $sth = $dbh->prepare ("SELECT * FROM dibe_relicten") or die $dbh->errstr ();
	$sth->execute () or die $sth->errstr ();
	##
	# Import data into array of arrayrefs
	##
	#RELICT_ID,N,11,0	NAAM,C,200	ALT_NAAM,C,254	PROV_ID,N,11,0	PROV,C,30	GEM_ID,N,11,0	GEM,C,50	DEELGEM_ID,C,6	DEELGEM,C,50	ADRES_ID,N,11,0	STRAAT_ID,N,11,0	STRAAT,C,100	HUISNR,C,20	STATUS,C,20	URL,C,44	VASTGEST,N,11,0
	while ($row = $sth->fetchrow_hashref ()) {
		push (@data, {
			"relict_id" => $row->{"RELICT_ID"},
			"naam" => $row->{"NAAM"},
			"alt_naam" => $row->{"ALT_NAAM"},
			"prov_id" => $row->{"PROV_ID"},
			"provincie" => $row->{"PROV"},
			"gem_id" => $row->{"GEM_ID"},
			"gemeente" => $row->{"GEM"},
			"deelgem_id" => $row->{"DEELGEM_ID"},
			"deelgemeente" => $row->{"DEELGEM"},
			"adres_id" => $row->{"ADRES_ID"},
			"straat_id" => $row->{"STRAAT_ID"},
			"straat" => $row->{"STRAAT"},
			"huisnummer" => $row->{"HUISNR"},
			"url" => $row->{"URL"}
		});
	}
	return \@data;
}