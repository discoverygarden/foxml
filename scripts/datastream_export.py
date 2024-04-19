import argparse
import requests
from tqdm import tqdm
import concurrent.futures
import os
import mimetypes
from utils import perform_http_request


def parse_args():
    parser = argparse.ArgumentParser(
        description="Export metadata using SPARQL query and save as XML."
    )
    parser.add_argument("--url", required=True, help="Fedora base URL")
    parser.add_argument("--user", required=True, help="Username for Fedora access")
    parser.add_argument("--password", required=True, help="Password for Fedora access")
    parser.add_argument("--dsid", required=True, help="Datastream ID for querying")
    parser.add_argument(
        "--output_dir", default="./output", help="Directory to save XML files"
    )
    parser.add_argument(
        "--pid_file", type=str, help="File containing PIDs to process", required=False
    )
    return parser.parse_args()


def fetch_data(dsid, base_url, user, password, output_dir, obj_id):
    """
    Fetches the datastream content for a given datastream ID (dsid) and object ID (obj_id) from a Fedora repository.

    Args:
        dsid (str): The ID of the datastream to fetch.
        base_url (str): The base URL of the Fedora repository.
        user (str): The username for authentication.
        password (str): The password for authentication.
        output_dir (str): The directory where the fetched data will be saved.
        obj_id (str): The ID of the object that contains the datastream.

    Returns:
        bool: True if the datastream content was successfully fetched and saved, False otherwise.
    """
    obj_id = obj_id.replace("info:fedora/", "")
    url = f"{base_url}/fedora/objects/{obj_id}/datastreams/{dsid}/content"
    print(f"Downloading {dsid} for PID: {obj_id}")
    try:
        response = requests.get(url, auth=(user, password))
        response.raise_for_status()
        dsid_dir = os.path.join(output_dir, dsid)
        os.makedirs(dsid_dir, exist_ok=True)
        content_type = response.headers.get("Content-Type", "")
        extension = mimetypes.guess_extension(content_type) if content_type else ""
        filename = f"{obj_id}-{dsid}{extension}"
        with open(os.path.join(dsid_dir, filename), "wb") as f:
            f.write(response.content)
        print(f"Successfully saved {filename}")
        return True
    except Exception as e:
        print(f"Failed to fetch data for {obj_id}, error: {str(e)}")
        return False


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


def main():
    args = parse_args()
    os.makedirs(args.output_dir, exist_ok=True)

    object_ids = []

    # If a PID file is provided, process the file to get the list of PIDs.
    if args.pid_file:
        object_ids = process_pid_file(args.pid_file)
    else:
        query = f"""
        SELECT ?obj WHERE {{
          ?obj <fedora-model:hasModel> <info:fedora/fedora-system:FedoraObject-3.0>;
               <fedora-model:hasModel> ?model;
               <fedora-view:disseminates> ?ds.
          ?ds <fedora-view:disseminationType> <info:fedora/*/{args.dsid}>
          FILTER(!sameTerm(?model, <info:fedora/fedora-system:FedoraObject-3.0>))
          FILTER(!sameTerm(?model, <info:fedora/fedora-system:ContentModel-3.0>))
        }}
        """

        result = perform_http_request(query, args.url, args.user, args.password)
        object_ids.extend(result.strip().split("\n")[1:])

    # Download metadata for each PID in parallel using ThreadPoolExecutor.
    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as executor, tqdm(
        total=len(object_ids), desc="Downloading Metadata"
    ) as progress:
        futures = {
            executor.submit(
                fetch_data,
                args.dsid,
                args.url,
                args.user,
                args.password,
                args.output_dir,
                obj_id,
            ): obj_id
            for obj_id in object_ids
        }
        for future in concurrent.futures.as_completed(futures):
            obj_id = futures[future]
            try:
                success = future.result()
                if success:
                    progress.update(1)
            except Exception as exc:
                print(f"{obj_id} generated an exception: {exc}")


if __name__ == "__main__":
    main()
