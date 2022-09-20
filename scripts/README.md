# FCREPO3 Analysis Helpers

## Setup

These scripts require a FCREPO3 instance to be run over. In the event these scripts are ran on a separate box from where
the repository lives modifications may be required to the `fedora-xacml-policies` directory located at `$FEDORA_HOME/data/fedora-xacml-policies`.

The metadata export command requires [GNU Parallel](https://www.gnu.org/software/parallel/parallel.html) to be installed
for faster processing.

## Usage

### Metadata Analysis

This script will generate the following:
1. A total count of all objects in the repository.
2. A breakdown of objects by content models and their count in CSV form (`models.csv`).
3. A breakdown of unique datastream IDs and their count in CSV form (`dsids.csv`).

```bash
sudo bash /path_to_the_module/scripts/metadata_analysis.sh --fedora_pass=the_password
```

```
The total number of objects is 40.
Outputted model breakdown to CSV (models.csv).
Outputted DSID breakdown to CSV (dsids.csv).
```

### Metadata Export

This command exports all objects within the repository that contain a specified metadata datastream ID.

Utilizing the `--skip_auth_check` flag here is an important performance optimization as it will greatly speed up the
export operation due to not needing to validate the request prior.

```bash
sudo bash shell_scripts/export_metadata.sh --fedora_pass=the_password --skip_auth_check
```

The command itself does not output anything but will export all objects in the form of `the:pid-DSID.xml`.

