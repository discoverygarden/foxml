import requests


def perform_http_request(query, endpoint_url, user, password, output_format="CSV"):
    headers = {"Content-Type": "application/x-www-form-urlencoded"}
    payload = {
        "type": "tuples",
        "lang": "sparql",
        "format": output_format,
        "limit": "",
        "dt": "on",
        "query": query,
    }
    response = requests.post(
        f"{endpoint_url}/fedora/risearch",
        auth=(user, password),
        headers=headers,
        data=payload,
    )
    if response.status_code == 200:
        return response.text
    else:
        print(f"Error {response.status_code} while querying: {query}")
        return None
