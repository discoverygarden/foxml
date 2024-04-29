# FCREPO3 Analysis Helpers
## Introduction
Tools to analyse and export metadata from an FCREPO3 instance using Python scripts.

## Table of Contents
* [Setup](#setup)
* [Features](#features)
* [Usage](#usage)

## Setup
These tools are designed to be run with a Python environment. Ensure Python 3.6 or higher is installed on your system; you can check the version with `python3 --version`. You will need to set up a Python virtual environment and install the required packages; this can be done using these command within this 'scripts' directory:

```bash
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

The scripts also require an FCREPO3 instance. If these tools are run on a system separate from where the repository is hosted, modifications might be necessary in the `fedora-xacml-policies` directory at `$FEDORA_HOME/data/fedora-xacml-policies`.

## Features
### Metadata Analysis
Script to run SPARQL queries against an FCREPO's RI and gather information. Current queries include:
 - Content model distribution
 - Total object count
 - Count of active and deleted objects
 - List of deleted objects
 - Datastream distribution
 - Owner distribution
 - Collection distribution
 - List of relationships
 - List of orphaned objects
 - MIME type distribution

### Metadata Export
Script to export all objects within the repository that contain a specified metadata datastream ID, saving results as XML.

### FOXML Export
Script to export FOXML archival objects from a Fedora repository given a list of PIDs.

### Datastream Updater
Script to inject a binary into an archival FOXML as base64 encoded data within a datastream.

## Usage
### Metadata Analysis
#### Command
```bash
python3 data_analysis.py --url=<http://your-fedora-url> --user=<admin> --password=<secret> --output_dir=<./results>
```
#### Output
Exports all queries found in `queries.py` to their own CSV in the `results` folder by default. Can be changed with the `--output_dir` flag.

### Metadata Export
#### Command
```bash
python3 datastream_export.py --url=<http://your-fedora-url:8080> --user=<admin> --password=<secret> --dsid=<DSID> --output_dir=<./output> --pid_file=<./some_pids>
```
> The script supports adding comments in the pid_file using `#`. PIDs can also contain URL encoded characters (e.g., `%3A` for `:` which will be automatically decoded).

#### Output
Exports all metadata entries related to the specified DSID into XML files stored in the defined output directory.
Each file's name will be in the format `pid-DSID.xml`.

### FOXML Export
#### Command
```bash
python3 foxml_export.py --url=<http://your-fedora-url:8080> --user=<admin> --pasword=<secret> --pid_file=<./some_pids_to_export> --output_dir=<./output>
```
> The script supports adding comments in the pid_file using `#`. PIDs can also contain URL encoded characters (e.g., `%3A` for `:` which will be automatically decoded).

#### Output
Exports all archival FOXML found in the associated PID file passed in through arguments to their own folder in `output_dir/FOXML`.

### Datastream Updater
#### Command
```bash
python3 datastream_updater.py --xml=input.xml --dsid=DSID --content=content.bin --label='New Version' --output=output.xml
```
> This script allows you to specify the XML file to modify, the datastream ID, the binary content file (which will be base64 encoded), and optionally a label for the new datastream version.

The only non-required argument is `label` which is in the case if you want to specify a custom label. If previous datastream versions do not have a label and you didn't specify one in the args, it will prompt you for a new one.

#### Output
Updates the specified XML file with a new version of the datastream, encoding the provided binary content into base64. The updated XML is saved to the specified output file.

