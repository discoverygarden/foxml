# FOXML Utilities

## Introduction

A module to facilitate I7 to I8 migraiton.

## Requirements


## Usage


## Installation

Install as usual, see
[this](https://drupal.org/documentation/install/modules-themes/modules-8) for
further information.

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

## Maintainers/Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development

If you would like to contribute to this module create an issue, pull request
and or contact
[discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
