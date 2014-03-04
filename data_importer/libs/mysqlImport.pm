#!/usr/bin/perl

use strict;
use warnings;
use diagnostics;

##
# Modules
##

use Exporter;

use DBI;
use lib "libs";
use mysqlMeta;

our @ISA = ('Exporter', 'DBI', 'mysqlMeta');
our @EXPORT_OK = ('data_to_mysql');

package mysqlImport;

##
# Function to write the data to the database
# this function does some (a lot) of processing
# @param dbh $dbh
# @param arrayref $data with $rows (hashref) of data in dbf-format
##
# "relict_id" => $row->{"RELICT_ID"},
# "naam" => $row->{"NAAM"},
# "alt_naam" => $row->{"ALT_NAAM"},
# "prov_id" => $row->{"PROV_ID"},
# "provincie" => $row->{"PROV"},
# "gem_id" => $row->{"GEM_ID"},
# "gemeente" => $row->{"GEM"},
# "deelgem_id" => $row->{"DEELGEM_ID"},
# "deelgemeente" => $row->{"DEELGEM"},
# "adres_id" => $row->{"ADRES_ID"},
# "straat_id" => $row->{"STRAAT_ID"},
# "straat" => $row->{"STRAAT"},
# "huisnummer" => $row->{"HUISNR"},
# "url" => $row->{"URL"}
##
# @return true/false
sub data_to_mysql {
	my ($dbh, $data) = @_;
	# All in one transaction #
	$dbh->{AutoCommit} = 0;
	$dbh->{RaiseError} = 1;
	eval {
		foreach $row (@{$data}) {
			# relict_id should be unique, check #
			if (mysqlMeta::check_if_exists ($dbh, 'relicten', 'relict_id', $row->{'relict_id'}) == 1) {
				warn "Item ".$row->{'relict_id'}." already exists.";
				next;
			}
			##
			# Add to provincies-table (only table with no foreign keys
			##
			if (&insert_province ($dbh, $row->{'prov_id'}, $row->{'provincie'}) != 1) {
				die "Error: failed to insert provincie!";
			}
			##
			# Add to gemeentes-table (next hop)
			##
			if (&insert_gemeente ($dbh, $row->{'gem_id'}, $row->{'gemeente'}, $row->{'prov_id'}) != 1) {
				die "Error: failed to insert gemeente!";
			}
		}
		$dbh->commit ();
	};
	if ($@) {
		warn "Error: transaction aborted due to errors. ".$@;
		$dbh->rollback () or die $dbh->errstr;
		return 0;
	}
}

##
# Function to insert a province into the provincies-table
# @param dbh $dbh
# @param string $id (mapped to nis)
# @param string $name (mapped to naam)
# @return true/false
##
#CREATE TABLE provincies (
#	id int(16) NOT NULL AUTO_INCREMENT,
#	naam varchar(255) COLLATE utf8_unicode_ci NOT NULL,
#	nis varchar(255) COLLATE utf8_unicode_ci NOT NULL,
#	PRIMARY KEY (`id`),
#	KEY `naam` (`naam`)
#) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  AUTO_INCREMENT=1;
##
sub insert_province {
	my ($dbh, $id, $name) = @_;
	if (mysqlMeta::check_if_exists ($dbh, 'provincies', 'nis', $id) == 1) {
		# Province already in DB, but this is not fatal, so return true #
		return 1;
	}
	my $sth = $dbh->prepare ("INSERT INTO provincies (naam, nis) VALUES (?, ?)") or die $dbh->errstr;
	my $rv = $sth->execute (($name, $id)) or die $sth->errstr;
	if ($rv >= 1 || $rv == 0) {
		return 1;
	} else {
		return 0;
	}
}

##
# Function to insert a city into the gemeentes-table
# @param dbh $dbh
# @param string $id (mapped to nis)
# @param string $name (mapped to naam)
# @param string $prov_nis (translated to prov_id)
# @return 1/0
##
#CREATE TABLE gemeentes (
#	id int(16) NOT NULL AUTO_INCREMENT,
#	naam varchar(255) COLLATE utf8_unicode_ci NOT NULL,
#	nis varchar(255) COLLATE utf8_unicode_ci NOT NULL,
#	prov_id int(16) NOT NULL,
#	PRIMARY KEY (`id`),
#	KEY `naam` (`naam`),
#	KEY `prov_id` (`prov_id`),
#	FOREIGN KEY (prov_id) 
#		REFERENCES provincies (id)
#) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  AUTO_INCREMENT=1;
sub insert_gemeente {
	my ($dbh, $id, $name, $prov_nis) = @_;
	# Fetch prov_id #
	my $r = mysqlMeta::fetch_item_by_nis ($dbh, 'provincies', $prov_nis);
	if (scalar @{$r} != 1) {
		# We want only 1 province for this NIS, so error out #
		die "Error: NIS ".$prov_nis." translates to multiple or none items from the provincies-table.";
		return 0;
	}
	if (mysqlMeta::check_if_exists ($dbh, 'gemeentes', 'nis', $id) == 1) {
		# Gemeente already in DB, but this is not fatal, so return true #
		return 1;
	}
	# Insert #
	my $sth = $dbh->prepare ("INSERT INTO gemeentes (naam, nis, prov_id) VALUES (?, ?, ?)") or die $dbh->errstr;
	my $rv = $sth->execute (($name, $id, $r->[0]->{'id'})) or die $sth->errstr;
	if ($rv >= 1 || $rv == 0) {
		return 1;
	} else {
		return 0;
	}
}