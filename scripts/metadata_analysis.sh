#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

. "$SCRIPT_DIR/util.in"

# Base RI query to build things up.
BASE_QUERY=$(cat << EOQ
WHERE {
  ?obj <fedora-model:hasModel> <info:fedora/fedora-system:FedoraObject-3.0> ;
       <fedora-model:hasModel> ?model ;
FILTER(!sameTerm(?model, <info:fedora/fedora-system:FedoraObject-3.0>))
FILTER(!sameTerm(?model, <info:fedora/fedora-system:ContentModel-3.0>))
}
EOQ
)

# Retrieves a list of content models and their count to CSV.
model_breakdown() {
  local QUERY=$(cat << EOQ
SELECT ?model (COUNT(?model) as ?count)
${BASE_QUERY}
GROUP BY ?model
EOQ
)

  do_curl "$QUERY" "CSV" > "$SCRIPT_DIR"/models.csv
  echo "Outputted model breakdown to CSV (${SCRIPT_DIR}/models.csv)."
}

# Retrieves the total amount of objects in the repository.
total_count() {
  local QUERY=$(cat << EOQ
SELECT ?obj
${BASE_QUERY}
EOQ
)

  local COUNT=$(do_curl "$QUERY" "count")
  echo "The total number of objects is ${COUNT}."
}

# Breaks down the unique datastream IDs and their count to CSV.
dsid_breakdown() {
  local QUERY=$(cat << EOQ
SELECT ?ds (COUNT(?ds) as ?count)
WHERE {
  ?obj <fedora-model:hasModel> <info:fedora/fedora-system:FedoraObject-3.0> ;
       <fedora-model:hasModel> ?model ;
       <fedora-view:disseminates> [<fedora-view:disseminationType> ?ds]
FILTER(!sameTerm(?model, <info:fedora/fedora-system:FedoraObject-3.0>))
FILTER(!sameTerm(?model, <info:fedora/fedora-system:ContentModel-3.0>))
}
GROUP BY ?ds
EOQ
)

  do_curl "$QUERY" "CSV" > "$SCRIPT_DIR"/dsids.csv
  echo "Outputted DSID breakdown to CSV (${SCRIPT_DIR}/dsids.csv)."
}

total_count
model_breakdown
dsid_breakdown

exit 0
