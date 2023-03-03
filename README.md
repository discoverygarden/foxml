# FOXML Utilities

![](https://github.com/discoverygarden/foxml/actions/workflows/auto_lint.yml/badge.svg)
![](https://github.com/discoverygarden/foxml/actions/workflows/auto-semver.yml/badge.svg)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

## Introduction

Provides migration plugins and utility scripts to facilitate I7 to Modern Islandora migration.

## Table of Contents

* [Features](#features)
* [Installation](#installation)
* [Configuration](#configuration)
* [Troubleshooting/Issues](#troubleshootingissues)
* [Maintainers and Sponsors](#maintainers-and-sponsors)
* [Development/Contribution](#developmentcontribution)
* [License](#license)

## Features

### Migrate source plugin `foxml`

Source `foxml` plugin that iterates over an "objectStore" implementation.

### Migrate process plugin `foxml.parse`

Given the path to the foxml, this plugin will parse the contents for migration.

### Utility Scripts

Scripts to analyse and export metadata from an FCREPO3 instance. Refer to the [README](https://github.com/discoverygarden/foxml/blob/main/scripts/README.md) for more details.

## Installation

Install as usual, see
[this](https://drupal.org/documentation/install/modules-themes/modules-8) for
further information.

## Configuration

Configuration to use the
"Archival FOXML" migration:

|Key|Description|Default|
|---|---|---|
|`foxml_archival_object_basepath`|The path to the export of archival FOXML over which to iterate.|`private://exports`|
|`foxml_archival_object_file_pattern`|A regex pattern against which to match files.|`NULL` (none necessary; defaulting to iterate over _ALL_ files)|

## Usage/Examples

## Troubleshooting/Issues

Having problems or solved a problem? Contact
[discoverygarden](http://support.discoverygarden.ca).

### Known Issues:
* `php://filter` use can lead to large memory usage
  * we should probably look at rolling another stream wrapper to wrap up our
    usage of OpenSSL to Base64 decode
* There are some expensive assertions made in the code,
  particularly regarding binary datastream content with digests. Assertions should
  typically be disabled in production environments, so these shouldn't have any
  impact on execution there; however, in development environments, could
  potentially lead to issues, especially with larger datastreams, exacerbated by
  the `php://filter` usage to Base64-decode the contents
  * hesitant to remove the assertions without having any other mechanism to
  * could instead roll some unit tests?

## Maintainers and Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

Sponsors:

* [FLVC](@todo Add link)

## Development/Contribution

If you would like to contribute to this module, please check out github's helpful
[Documentation for Developers](https://docs.github.com/en/get-started/quickstart/contributing-to-projects) to create an issue or pull request and/or
contact [discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
