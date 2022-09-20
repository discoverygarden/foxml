#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

. "$SCRIPT_DIR/util.in"

# Ensure GNU Parallel is installed.
if ! type parallel >/dev/null 2>&1 ; then
  printf "*****************************************************\n"
  printf "* Error: GNU Parallel is not installed.             *\n"
  printf "*****************************************************\n"
  exit 1
fi

DSID_QUERY=$(cat << EOQ
SELECT ?obj
WHERE {
  ?obj <fedora-model:hasModel> <info:fedora/fedora-system:FedoraObject-3.0> ;
       <fedora-model:hasModel> ?model ;
       <fedora-view:disseminates> ?ds .
  ?ds  <fedora-view:disseminationType> <info:fedora/*/${DSID}>
FILTER(!sameTerm(?model, <info:fedora/fedora-system:FedoraObject-3.0>))
FILTER(!sameTerm(?model, <info:fedora/fedora-system:ContentModel-3.0>))
}
EOQ
)

# Go perform the query for all objects and pass off to parallel to do the heavy lifting. To note the URI here chops off
# the info:fedora/ piece from the front with Perl.
# @see: https://www.gnu.org/software/parallel/parallel_tutorial.html#perl-expression-replacement-string
do_curl "${DSID_QUERY}" "CSV" | parallel --jobs 3 --skip-first-line curl --location "${FEDORA_URL}:8080/fedora/objects/{= s/info:fedora\/// =}/datastreams/${DSID}/content" \
-u "${FEDORA_USER}:${FEDORA_PASS}" \
-o "${SCRIPT_DIR}/{= s/info:fedora\/// =}-${DSID}.xml"
