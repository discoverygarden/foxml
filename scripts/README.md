# FCREPO3 Analysis Helpers

## Introduction
Scripts to analyse and export metadata from an FCREPO3 instance.

## Table of Contents

* [Setup](#setup)
* [Features](#features)
* [Usage](#usage)

## Setup

These scripts require an FCREPO3 instance to be run over. In the event, these scripts are run on a separate system from where
the repository lives, modifications may be required to the `fedora-xacml-policies` directory located at `$FEDORA_HOME/data/fedora-xacml-policies`.

The metadata export command requires [GNU Parallel](https://www.gnu.org/software/parallel/parallel.html) to be installed
for faster processing.

## Features

### Metadata Analysis
A script to generate the following:
1. A total count of all objects in the repository.
2. A breakdown of objects by content models and their count in CSV form (`models.csv`).
3. A breakdown of unique datastream IDs and their count in CSV form (`dsids.csv`).

### Metadata Export
A script to export all objects within the repository that contain a specified metadata datastream ID.

## Usage

### Metadata Analysis
#### Command
```bash
sudo bash /path_to_the_module/scripts/metadata_analysis.sh --fedora_pass=the_password
```

#### Output
```
The total number of objects is 40.
Outputted model breakdown to CSV (models.csv).
Outputted DSID breakdown to CSV (dsids.csv).
```

### Metadata Export
#### Command
```bash
sudo bash shell_scripts/export_metadata.sh --fedora_pass=the_password --skip_auth_check
```

> Utilizing the `--skip_auth_check` flag here is an important performance optimization as it will greatly speed up the
export operation due to not needing to validate the request prior.

#### Output
The command does not output anything but will export all objects in the form of `the:pid-DSID.xml`.
