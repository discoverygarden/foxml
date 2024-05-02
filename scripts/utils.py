import requests


def perform_http_request(query, endpoint_url, user, password, output_format="CSV"):
    """
    Perform an HTTP request to a specified endpoint URL with the given query.

    Args:
        query (str): The SPARQL query to be executed.
        endpoint_url (str): The URL of the SPARQL endpoint.
        user (str): The username for authentication.
        password (str): The password for authentication.
        output_format (str, optional): The desired format of the response. Defaults to "CSV".

    Returns:
        str: The response text if the request is successful, None otherwise.
    """
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


def process_pid_file(filepath):
    """
    Process a file containing PIDs (Persistent Identifiers) and return a list of PIDs.
    Supports comments in the file using '#' character.
    Replace '%3A' with ':' in PIDs.

    Args:
        filepath (str): The path to the file containing PIDs.

    Returns:
        list: A list of PIDs extracted from the file.
    """
    pids = []
    with open(filepath, "r") as file:
        for line in file:
            line = line.strip()
            if "#" in line:
                line = line[: line.index("#")].strip()
            if line:
                line = line.replace("%3A", ":")
                pids.append(line)
    return pids
