import argparse
import os
from utils import perform_http_request

def parse_args():
    parser = argparse.ArgumentParser(description="Fetch at least one PID per content model, per datastream from Fedora.")
    parser.add_argument("--url", required=True, help="Fedora base URL")
    parser.add_argument("--user", required=True, help="Username for Fedora access")
    parser.add_argument("--password", required=True, help="Password for Fedora access")
    parser.add_argument("--dsids", required=True, help="Comma-separated list of datastream IDs to consider")
    parser.add_argument("--output_dir", default="./output", help="Directory to save the output file")
    return parser.parse_args()

def get_content_models(base_url, user, password):
    query = """
    SELECT DISTINCT ?model WHERE {
      ?obj <fedora-model:hasModel> ?model .
      FILTER(!sameTerm(?model, <info:fedora/fedora-system:FedoraObject-3.0>))
      FILTER(!sameTerm(?model, <info:fedora/fedora-system:ContentModel-3.0>))
    }
    """
    response_text = perform_http_request(query, base_url, user, password, output_format="CSV")
    if not response_text:
        return []
    
    lines = response_text.splitlines()
    models = [line.strip() for line in lines[1:]]  # Skip the header
    return models

def get_pids_for_model_and_dsid(base_url, user, password, model, dsid):
    query = f"""
    SELECT ?obj ?model ?dsid WHERE {{
      ?obj <fedora-model:hasModel> <{model}> ;
           <fedora-view:disseminates> ?ds .
      ?ds <fedora-view:disseminationType> <info:fedora/*/{dsid}> .
      BIND(<{model}> AS ?model)
      BIND("{dsid}" AS ?dsid)
    }} LIMIT 1
    """
    response_text = perform_http_request(query, base_url, user, password, output_format="CSV")
    if not response_text:
        return []
    
    lines = response_text.splitlines()
    results = []
    for line in lines[1:]:  # Skip the header
        parts = line.split(',')
        if len(parts) < 3:
            continue
        pid = parts[0].strip()
        model = parts[1].strip()
        dsid = parts[2].strip()
        results.append((pid, model, dsid))
    return results

def get_pids(base_url, user, password, dsids):
    content_models = get_content_models(base_url, user, password)
    all_pids = {}
    for model in content_models:
        for dsid in dsids:
            results = get_pids_for_model_and_dsid(base_url, user, password, model, dsid)
            for pid, model, dsid in results:
                if model not in all_pids:
                    all_pids[model] = {}
                if dsid not in all_pids[model]:
                    all_pids[model][dsid] = []
                all_pids[model][dsid].append(pid)
    return all_pids

def write_pids_to_file(pids, output_dir):
    os.makedirs(output_dir, exist_ok=True)
    output_file = os.path.join(output_dir, "sample_data_pids.txt")
    with open(output_file, "w") as f:
        for model, ds_dict in pids.items():
            for dsid, pid_list in ds_dict.items():
                for pid in pid_list:
                    f.write(f"{pid}\n")
    print(f"PIDs written to {output_file}")

def main():
    args = parse_args()
    dsids = args.dsids.split(',')

    print("Starting script...")
    pids = get_pids(args.url, args.user, args.password, dsids)
    
    if not pids:
        print("No PIDs found")
    else:
        for model, ds_dict in pids.items():
            print(f"\nContent Model: {model}")
            for dsid, pid_list in ds_dict.items():
                print(f"  Datastream: {dsid}")
                for pid in pid_list:
                    print(f"    PID: {pid}")
        
        write_pids_to_file(pids, args.output_dir)

if __name__ == "__main__":
    main()
