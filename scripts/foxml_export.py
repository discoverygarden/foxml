import argparse
import requests
from tqdm import tqdm
import concurrent.futures
import os
import mimetypes
from utils import process_pid_file


def parse_args():
    parser = argparse.ArgumentParser(
        description="Export metadata using SPARQL query and save as XML."
    )
    parser.add_argument("--url", required=True, help="Fedora base URL")
    parser.add_argument("--user", required=True, help="Username for Fedora access")
    parser.add_argument("--password", required=True, help="Password for Fedora access")
    parser.add_argument(
        "--output_dir", default="./output", help="Directory to save XML files"
    )
    parser.add_argument(
        "--pid_file", type=str, required=True, help="File containing PIDs to process"
    )
    return parser.parse_args()


def fetch_foxml(base_url, user, password, output_dir, pid):
    """
    Fetches the archival FOXML for a given PID from a Fedora repository.

    Args:
        base_url (str): The base URL of the Fedora repository.
        user (str): The username for authentication.
        password (str): The password for authentication.
        output_dir (str): The directory where the fetched data will be saved.
        pid (str): The ID of the object that contains the datastream.

    Returns:
        bool: True if the datastream content was successfully fetched and saved, False otherwise.
    """
    pid = pid.replace("info:fedora/", "")
    url = f"{base_url}/fedora/objects/{pid}/export?context=archive"
    print(f"Downloading FOXML for PID: {pid}")
    try:
        response = requests.get(url, auth=(user, password))
        response.raise_for_status()
        foxml_dir = os.path.join(output_dir, "FOXML")
        os.makedirs(foxml_dir, exist_ok=True)
        content_type = response.headers.get("Content-Type", "")
        extension = mimetypes.guess_extension(content_type) if content_type else ""
        filename = f"{pid}-FOXML{extension}"
        with open(os.path.join(foxml_dir, filename), "wb") as f:
            f.write(response.content)
        print(f"Successfully saved {filename}\n")
        return True
    except Exception as e:
        print(f"Failed to fetch FOXML for {pid}, error: {str(e)}\n")
        return False


def main():
    args = parse_args()
    os.makedirs(args.output_dir, exist_ok=True)

    pids = []

    pids = process_pid_file(args.pid_file)

    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as executor, tqdm(
        total=len(pids), desc="Downloading FOXML"
    ) as progress:
        futures = {
            executor.submit(
                fetch_foxml,
                args.url,
                args.user,
                args.password,
                args.output_dir,
                pid,
            ): pid
            for pid in pids
        }
        for future in concurrent.futures.as_completed(futures):
            pid = futures[future]
            try:
                success = future.result()
                if success:
                    progress.update(1)
            except Exception as exc:
                print(f"{pid} generated an exception: {exc}")


if __name__ == "__main__":
    main()
