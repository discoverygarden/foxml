queries = {
    "content_model_distribution": """
        SELECT ?model (COUNT(?obj) as ?count)
        FROM <#ri>
        WHERE {
            ?obj <info:fedora/fedora-system:def/model#hasModel> ?model;
        }
        GROUP BY ?model
    """,

    "object_count": """
        SELECT (COUNT(?obj) as ?count)
        FROM <#ri>
        WHERE {
            ?obj <info:fedora/fedora-system:def/model#hasModel> <info:fedora/fedora-system:FedoraObject-3.0> .
        }
        LIMIT 1
    """,

    "active_deleted_count": """
        SELECT (COUNT(?activeObj) AS ?active) (COUNT(?deletedObj) AS ?deleted) (COUNT(?inactiveObj) AS ?inactive)
        FROM <#ri>
        WHERE {
            {
                SELECT ?activeObj
                WHERE {
                    ?activeObj <info:fedora/fedora-system:def/model#state> <info:fedora/fedora-system:def/model#Active> .
                }
            } UNION {
                SELECT ?deletedObj
                WHERE {
                    ?deletedObj <info:fedora/fedora-system:def/model#state> <info:fedora/fedora-system:def/model#Deleted> .
                }
            } UNION {
                SELECT ?inactiveObj
                WHERE {
                    ?inactiveObj <info:fedora/fedora-system:def/model#state> <info:fedora/fedora-system:def/model#Inactive> .
                }
            }
        }
    """,

    "deleted_objects": """
        SELECT ?obj
        FROM <#ri>
        WHERE {
            ?obj <info:fedora/fedora-system:def/model#state> <info:fedora/fedora-system:def/model#Deleted>
        }
    """,

    "inactive_objects": """
        SELECT ?obj
        FROM <#ri>
        WHERE {
            ?obj <info:fedora/fedora-system:def/model#state> <info:fedora/fedora-system:def/model#Inactive>
        }
    """,

    "datastream_distribution": """
        SELECT ?datastream (COUNT(?datastream) as ?count)
        FROM <#ri>
        WHERE {
            ?obj <info:fedora/fedora-system:def/model#hasModel> <info:fedora/fedora-system:FedoraObject-3.0>;
            OPTIONAL {
                ?obj <info:fedora/fedora-system:def/view#disseminates> ?c .
                ?c <info:fedora/fedora-system:def/view#disseminationType> ?datastream ;
            }
        }
        GROUP BY ?datastream
    """,

    "owner_distribution": """
        SELECT ?owner (COUNT(?obj) as ?count)
        FROM <#ri>
        WHERE {
            ?obj <info:fedora/fedora-system:def/model#ownerId> ?owner;
        }
        GROUP BY ?owner
    """,

    "collection_distribution": """
        SELECT ?collection (COUNT(?obj) as ?count)
        FROM <#ri>
        WHERE {
            ?obj <info:fedora/fedora-system:def/relations-external#isMemberOfCollection> ?collection .
            ?collection <info:fedora/fedora-system:def/model#hasModel> <info:fedora/fedora-system:FedoraObject-3.0>
        }
        GROUP BY ?collection
    """,

    "relationships": """
        SELECT DISTINCT ?relationship
        FROM <#ri>
        WHERE {
            ?o ?relationship ?s .
            ?o <info:fedora/fedora-system:def/model#hasModel> <info:fedora/fedora-system:FedoraObject-3.0>
        }
    """,

    "orphaned_objects": """
        SELECT DISTINCT ?orphan
        FROM <#ri>
        WHERE {
            ?orphan <info:fedora/fedora-system:def/model#hasModel> <info:fedora/fedora-system:FedoraObject-3.0>
            FILTER NOT EXISTS {
                ?orphan <info:fedora/fedora-system:def/relations-external#isMemberOf> ?subject .
            }
            FILTER NOT EXISTS {
                ?orphan <info:fedora/fedora-system:def/relations-external#isMemberOfCollection> ?subject .
            }
            FILTER NOT EXISTS {
                ?orphan <info:fedora/fedora-system:def/relations-external#isConstituentOf> ?subject
            }
        }
    """,

    "mimetype_distribution": """
        SELECT ?mimetype (COUNT(?mimetype) as ?count)
        FROM <#ri>
        WHERE {
            ?o <info:fedora/fedora-system:def/view#mimeType> ?mimetype
        }
        GROUP BY ?mimetype
    """
}
