import argparse
import os
from utils import perform_http_request
from queries import queries


def parse_args():
    parser = argparse.ArgumentParser(
        description="Process SPARQL queries and save results."
    )
    parser.add_argument("--url", type=str, help="Fedora server URL", required=True)
    parser.add_argument("--user", type=str, help="Fedora username", required=True)
    parser.add_argument("--password", type=str, help="Fedora password", required=True)
    parser.add_argument(
        "--output_dir",
        type=str,
        default="./results",
        help="Directory to save CSV files",
    )
    return parser.parse_args()


def save_to_csv(data, filename, output_dir):
    """
    Save the given data to a CSV file.

    Args:
        data (str): The data to be written to the CSV file.
        filename (str): The name of the CSV file.
        output_dir (str): The directory where the CSV file will be saved.

    Returns:
        None
    """
    os.makedirs(output_dir, exist_ok=True)
    with open(os.path.join(output_dir, filename), "w", newline="") as file:
        file.write(data)


def main():
    args = parse_args()

    for query_name, query in queries.items():
        print(f"Processing query '{query_name}'...")
        result = perform_http_request(query, args.url, args.user, args.password)
        if result:
            csv_filename = f"{query_name}.csv"
            print(f"Saving results to {csv_filename}...\n")
            save_to_csv(result, csv_filename, args.output_dir)
        else:
            print(f"Failed to retrieve data for query '{query_name}'.\n")


if __name__ == "__main__":
    main()
