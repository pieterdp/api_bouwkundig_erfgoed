#!/usr/bin/perl

use strict;
use warnings;
use diagnostics;

##
# Modules
##

use Exporter;

use DBI;

our @ISA = ('Exporter', 'DBI');
our @EXPORT_OK = ('create_tables', 'check_if_exists');

package mysqlMeta;

##
# Function to create the required tables for the data-import
# @param $dbh
# @return true/false
#"prov_id" => $row->{"PROV_ID"},
#			"provincie" => $row->{"PROV"},
#			"gem_id" => $row->{"GEM_ID"},
#			"gemeente" => $row->{"GEM"},
#			"deelgem_id" => $row->{"DEELGEM_ID"},
#			"deelgemeente" => $row->{"DEELGEM"},
#			"adres_id" => $row->{"ADRES_ID"},
#			"straat_id" => $row->{"STRAAT_ID"},
#			"straat" => $row->{"STRAAT"},
#			"huisnummer" => $row->{"HUISNR"},
sub create_tables {
	my ($dbh) = @_;
	my @tables = (
		"CREATE TABLE provincies (
			id int(16) NOT NULL AUTO_INCREMENT,
			naam varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			nis varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			PRIMARY KEY (`id`),
			KEY `naam` (`naam`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  AUTO_INCREMENT=1;",
		"CREATE TABLE gemeentes (
			id int(16) NOT NULL AUTO_INCREMENT,
			naam varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			nis varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			prov_id int(16) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `naam` (`naam`),
			KEY `prov_id` (`prov_id`),
			FOREIGN KEY (prov_id) 
				REFERENCES provincies (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  AUTO_INCREMENT=1;",
		"CREATE TABLE deelgemeentes (
			id int(16) NOT NULL AUTO_INCREMENT,
			naam varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			nis varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			gem_id int(16) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `naam` (`naam`),
			KEY `gem_id` (`gem_id`),
			FOREIGN KEY (gem_id) 
				REFERENCES gemeentes (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  AUTO_INCREMENT=1;",
		"CREATE TABLE straten (
			id int(16) NOT NULL AUTO_INCREMENT,
			naam varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			nis varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			deelgem_id int(16) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `naam` (`naam`),
			KEY `deelgem_id` (`deelgem_id`),
			FOREIGN KEY (deelgem_id) 
				REFERENCES deelgemeentes (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  AUTO_INCREMENT=1;",
		"CREATE TABLE adres (
			id int(16) NOT NULL AUTO_INCREMENT,
			prov_id int(16) NOT NULL,
			gem_id int(16) NOT NULL,
			deelgem_id int(16) NOT NULL,
			str_id int(16) NOT NULL,
			huisnummer varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
			wgs84_lat varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
			wgs84_long varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `prov_id` (`prov_id`),
			KEY `gem_id` (`gem_id`),
			KEY `deelgem_id` (`deelgem_id`),
			KEY `str_id` (`str_id`),
			FOREIGN KEY (prov_id) 
				REFERENCES provincies (id),
			FOREIGN KEY (gem_id) 
				REFERENCES gemeentes (id),
			FOREIGN KEY (deelgem_id) 
				REFERENCES deelgemeentes (id),
			FOREIGN KEY (str_id) 
				REFERENCES straten (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  AUTO_INCREMENT=1;",
		"CREATE TABLE relicten (
			id int(16) NOT NULL AUTO_INCREMENT,
			relict_id int(16) NOT NULL,
			naam varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			alt_naam varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
			url varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			adres_id int(16) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `relict_id` (`relict_id`),
			KEY `naam` (`naam`),
			KEY `adres_id` (`adres_id`),
			FOREIGN KEY (adres_id) 
				REFERENCES adres (id)
				ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  AUTO_INCREMENT=1;"
	);
	# All in one transaction #
	$dbh->{AutoCommit} = 0;
	$dbh->{RaiseError} = 1;
	eval {
		foreach my $table (@tables) {
			my $sth = $dbh->prepare ($table);
			$sth->execute ();
		}
		$dbh->commit ();
	};
	if ($@) {
		warn "Error: transaction aborted due to errors. ".$@;
		$dbh->rollback () or die $dbh->errstr;
		return 0;
	}
	return 1;
}

##
# Function to check whether a given tuple exists
# @param dbh $dbh
# @param string $table
# @param string $identifier
# @param string $identified_by
# @return 1/0
sub check_if_exists {
	my ($dbh, $table, $identifier, $identified_by) = @_;
	my $sth = $dbh->prepare ("SELECT COUNT(*) FROM ".$dbh->quote_identifier ($table)." WHERE ".$dbh->quote_identifier ($identifier)." = ?") or die $dbh->errstr;
	$sth->bind_param (1, $identified_by);
	$sth->execute () or die $sth->errstr;
	my $result = $sth->fetchrow_arrayref (); # We only want 1 result
	if ($result->[0] != 0) {
		return 1;
	} else {
		return 0;
	}
}

##
# Function to fetch a tuple using nis-field
# @param dbh $dbh
# @param string $table
# @param string $nis
# @return arrayref of hashrefs $result
sub fetch_item_by_nis {
	my ($dbh, $table, $nis) = @_;
	my @result;
	my $sth = $dbh->prepare ("SELECT * FROM ".$dbh->quote_identifier ($table)." WHERE nis = ?") or die $dbh->errstr;
	$sth->execute (($nis)) or die $sth->errstr;
	while (my $row = $sth->fetchrow_hashref ()) {
		push (@result, $row);
	}
	return \@result;
}