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
# @return int $r_id
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
			# Add to provincies-table (only table with no foreign keys)
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
			##
			# Add to deelgemeentes-table
			##
			if (&insert_deelgemeente ($dbh, $row->{'deelgem_id'}, $row->{'deelgemeente'}, $row->{'gem_id'}) != 1) {
				die "Error: failed to insert deelgemeente!";
			}
			##
			# Add to straten-table
			##
			if (&insert_straat ($dbh, $row->{'straat_id'}, $row->{'straat'}, $row->{'deelgem_id'}) != 1) {
				die "Error: failed to insert straat!";
			}
			##
			# Difficult bit: add adressen
			##
			my $a_id = &insert_adres ($dbh, $row->{'prov_id'}, $row->{'gem_id'}, $row->{'deelgem_id'}, $row->{'straat_id'}, $row->{'huisnummer'}, 0, 0);
			##
			# Now add to relicten
			##
			my $r_id = &insert_relict ($dbh, $row->{'relict_id'}, $row->{'naam'}, $row->{'alt_naam'}, $row->{'url'}, $a_id);
		}
		$dbh->commit ();
	};
	if ($@) {
		warn "Error: transaction aborted due to errors. ".$@;
		$dbh->rollback () or die $dbh->errstr;
		return 0;
	}
	return $r_id;
}

##
# Function to insert a province into the provincies-table
# @param dbh $dbh
# @param string $id (mapped to nis) # NIS is a designed-to-be-unique code given to every Belgian community
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

##
# Function to insert a deelgemeente into table deelgemeentes
# @param dbh $dbh
# @param string $id (mapped to nis)
# @param string $name (mapped to naam)
# @param string $gem_nis (translated to prov_id)
# @return 1/0
##
#CREATE TABLE deelgemeentes (
#	id int(16) NOT NULL AUTO_INCREMENT,
#	naam varchar(255) COLLATE utf8_unicode_ci NOT NULL,
#	nis varchar(255) COLLATE utf8_unicode_ci NOT NULL,
#	gem_id int(16) NOT NULL,
#	PRIMARY KEY (`id`),
#	KEY `naam` (`naam`),
#	KEY `gem_id` (`gem_id`),
#	FOREIGN KEY (gem_id) 
#		REFERENCES gemeentes (id)
#) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  AUTO_INCREMENT=1;
##
sub insert_deelgemeente {
	my ($dbh, $id, $name, $gem_nis) = @_;
	# Fetch gem_id #
	my $r = mysqlMeta::fetch_item_by_nis ($dbh, 'gemeentes', $gem_nis);
	if (scalar @{$r} != 1) {
		# We want only 1 province for this NIS, so error out #
		die "Error: NIS ".$gem_nis." translates to multiple or none items from the gemeentes-table.";
		return 0;
	}
	if (mysqlMeta::check_if_exists ($dbh, 'deelgemeentes', 'nis', $id) == 1) {
		# Deelgemeente already in DB, but this is not fatal, so return true #
		return 1;
	}
	# Insert #
	my $sth = $dbh->prepare ("INSERT INTO deelgemeentes (naam, nis, gem_id) VALUES (?, ?, ?)") or die $dbh->errstr;
	my $rv = $sth->execute (($name, $id, $r->[0]->{'id'})) or die $sth->errstr;
	if ($rv >= 1 || $rv == 0) {
		return 1;
	} else {
		return 0;
	}
}

##
# Function to insert a street into the straten-table
# @param dbh $dbh
# @param string $id (this is not a legal NIS-code, but is unique as well)
# @param string $name
# @param string $deelgem_nis (straten depend on deelgemeentes, not on gemeentes)
# @return 1/0
##
#CREATE TABLE straten (
#	id int(16) NOT NULL AUTO_INCREMENT,
#	naam varchar(255) COLLATE utf8_unicode_ci NOT NULL,
#	nis varchar(255) COLLATE utf8_unicode_ci NOT NULL,
#	deelgem_id int(16) NOT NULL,
#	PRIMARY KEY (`id`),
#	KEY `naam` (`naam`),
#	KEY `deelgem_id` (`deelgem_id`),
#	FOREIGN KEY (deelgem_id) 
#		REFERENCES deelgemeentes (id)
#) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  AUTO_INCREMENT=1;
##
sub insert_straat {
	my ($dbh, $id, $name, $deelgem_nis) = @_;
	# Fetch gem_id #
	my $r = mysqlMeta::fetch_item_by_nis ($dbh, 'deelgemeentes', $deelgem_nis);
	if (scalar @{$r} != 1) {
		# We want only 1 province for this NIS, so error out #
		die "Error: NIS ".$deelgem_nis." translates to multiple or none items from the deelgemeentes-table.";
		return 0;
	}
	if (mysqlMeta::check_if_exists ($dbh, 'straten', 'nis', $id) == 1) {
		# Gemeente already in DB, but this is not fatal, so return true #
		return 1;
	}
	# Insert #
	my $sth = $dbh->prepare ("INSERT INTO straten (naam, nis, deelgem_id) VALUES (?, ?, ?)") or die $dbh->errstr;
	my $rv = $sth->execute (($name, $id, $r->[0]->{'id'})) or die $sth->errstr;
	if ($rv >= 1 || $rv == 0) {
		return 1;
	} else {
		return 0;
	}
}

##
# Function to enter an address. This one must not be unique (but should be)
# @param dbh $dbh
# @param string $prov_nis, $gem_nis, $deelgem_nis, $str_nis
# @param string $huisnummer
# @param string $wgs84_lat, wgs84_long
# @return int $adr_id
##
#CREATE TABLE adres (
#	id int(16) NOT NULL AUTO_INCREMENT,
#	prov_id int(16) NOT NULL,
#	gem_id int(16) NOT NULL,
#	deelgem_id int(16) NOT NULL,
#	str_id int(16) NOT NULL,
#	huisnummer varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
#	wgs84_lat varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
#	wgs84_long varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
#	PRIMARY KEY (`id`),
#	KEY `prov_id` (`prov_id`),
#	KEY `gem_id` (`gem_id`),
#	KEY `deelgem_id` (`deelgem_id`),
#	KEY `str_id` (`str_id`),
#	FOREIGN KEY (prov_id) 
#		REFERENCES provincies (id),
#	FOREIGN KEY (gem_id) 
#		REFERENCES gemeentes (id),
#	FOREIGN KEY (deelgem_id) 
#		REFERENCES deelgemeentes (id),
#	FOREIGN KEY (str_id) 
#		REFERENCES straten (id)
#) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  AUTO_INCREMENT=1;
##
sub insert_adres {
	my ($dbh, $prov_nis, $gem_nis, $deelgem_nis, $str_nis, $huisnummer, $wgs84_lat, $wgs84_long) = @_;
	my ($id, $prov_id, $gem_id, $deelgem_id, $str_id);
	##
	# Fetch foreign keys
	##
	$prov_id = mysqlMeta::fetch_item_by_nis ($dbh, 'provincies', $prov_nis);
	$gem_id = mysqlMeta::fetch_item_by_nis ($dbh, 'gemeentes', $gem_nis);
	$deelgem_id = mysqlMeta::fetch_item_by_nis ($dbh, 'deelgemeentes', $deelgem_nis);
	$str_id = mysqlMeta::fetch_item_by_nis ($dbh, 'straten', $str_nis);
	# Insert #
	my $sth = $dbh->prepare ("INSERT INTO adres (prov_id, gem_id, deelgem_id, str_id, huisnummer, wgs84_lat, wgs84_long) VALUES (?, ?, ?, ?, ?, ?, ?)") or die $dbh->errstr;
	my $rv = $sth->execute (($prov_id, $gem_id, $deelgem_id, $str_id, $huisnummer, $wgs84_lat, $wgs84_long)) or die $sth->errstr;
	if ($rv >= 1 || $rv == 0) {
		die "Error: failed to insert adres.";
	}
	return mysqlMeta::fetch_highest_ai ($dbh, 'adres', 'id');
}
##
# Function to insert a tuple into relicten-table
# @param dbh $dbh
# @param string $relict_id, $naam, $alt_naam, $url
# @param int $adres_id (references adres (id)
# @return int r_id mysql-id for this item (!= relict_id)
##
#CREATE TABLE relicten (
#	id int(16) NOT NULL AUTO_INCREMENT,
#	relict_id int(16) NOT NULL,
#	naam varchar(255) COLLATE utf8_unicode_ci NOT NULL,
#	alt_naam varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
#	url varchar(255) COLLATE utf8_unicode_ci NOT NULL,
#	adres_id int(16) NOT NULL,
#	PRIMARY KEY (`id`),
#	KEY `relict_id` (`relict_id`),
#	KEY `naam` (`naam`),
#	KEY `adres_id` (`adres_id`),
#	FOREIGN KEY (adres_id) 
#		REFERENCES adres (id)
#		ON DELETE CASCADE
#) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci  AUTO_INCREMENT=1;
##
sub insert_relict {
	my ($dbh, $relict_id, $naam, $alt_naam, $url, $adres_id) = @_;
	my ($r_id);
	# Insert #
	my $sth = $dbh->prepare ("INSERT INTO relicten (relict_id, naam, alt_naam, url, adres_id) VALUES (?, ?, ?, ?, ?)") or die $dbh->errstr;
	my $rv = $sth->execute (($relict_id, $naam, $alt_naam, $url, $adres_id)) or die $sth->errstr;
	if ($rv >= 1 || $rv == 0) {
		die "Error: failed to insert relict.";
	}
	return mysqlMeta::fetch_highest_ai ($dbh, 'relicten', 'id');
}