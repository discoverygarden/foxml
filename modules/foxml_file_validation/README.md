# FOXML File Validation

## Introduction

In migrations, we provide URIs in the `foxml://` scheme which do not have
meaningful file extensions, such as:

- `foxml://object/some:pid`; or,
- `foxml://datastream/some:pid+DSID+DSID.0`

Given these are served from a dedicated endpoint and the usual extension
validation does not have anything meaningful to work with, this module wraps
the base `FileExtension` validation such that it is bypassed when the `uri` of
the underlying file entity has the `foxml://` scheme. File _not_ in the
`foxml://` scheme should continue to validate extensions unchanged.

## Installation

Enable the (sub)module.

## Configuration

None.
