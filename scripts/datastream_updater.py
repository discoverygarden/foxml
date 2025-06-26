import argparse
import base64
import os
import mimetypes
from datetime import datetime
import xml.etree.ElementTree as ET

NAMESPACES = {
    'foxml': 'info:fedora/fedora-system:def/foxml#'
}

def register_namespaces():
    """Registers all known namespaces with ElementTree for clean output."""
    for prefix, uri in NAMESPACES.items():
        ET.register_namespace(prefix, uri)

def update_foxml_datastream(input_path, output_path, dsid, content_file, label, mimetype, control_group):
    """
    Adds or replaces a datastream in a FOXML file with Base64 encoded content,
    with precise indentation and multi-line formatting that preserves the original document's style.

    Args:
        input_path (str): Path to the source FOXML file.
        output_path (str): Path to save the modified FOXML file.
        dsid (str): The ID of the datastream to add/update (e.g., 'OBJ', 'MODS').
        content_file (str): Path to the file containing the new content.
        label (str): The label for the new datastream version.
        mimetype (str): The MIME type of the content file.
        control_group (str): The control group for the datastream (e.g., 'M', 'X').
    """
    if not os.path.exists(content_file):
        print(f"Error: Content file not found at '{content_file}'")
        return

    print(f"Reading content from '{content_file}'...")
    with open(content_file, 'rb') as f:
        binary_content_bytes = f.read()
    
    encoded_content_string = base64.b64encode(binary_content_bytes).decode('ascii')
    content_size = os.path.getsize(content_file)
    print(f"Content read successfully. Original size: {content_size} bytes.")

    register_namespaces()
    try:
        tree = ET.parse(input_path)
        root = tree.getroot()
    except ET.ParseError as e:
        print(f"Error parsing XML file '{input_path}': {e}")
        return

    datastream_xpath = f"./foxml:datastream[@ID='{dsid}']"
    datastream = root.find(datastream_xpath, NAMESPACES)
    
    datastream_indent = '  '
    version_indent = datastream_indent + '  '
    content_indent = version_indent + '  '
    base64_indent = ' ' * 14
    
    if datastream is None:
        print(f"Datastream with ID '{dsid}' not found. Creating a new one.")
        datastream = ET.SubElement(root, f"{{{NAMESPACES['foxml']}}}datastream", {
            'ID': dsid, 'STATE': 'A', 'CONTROL_GROUP': control_group, 'VERSIONABLE': 'true'
        })
        if len(root) > 1:
            prev_sibling = root[-2]
            datastream.tail = prev_sibling.tail
            prev_sibling.tail = '\n' + datastream_indent
        else:
            root.text = '\n' + datastream_indent
            datastream.tail = '\n'
            
        datastream.text = '\n' + version_indent
        last_version = None
        version_num = 0

    else:
        print(f"Found existing datastream with ID '{dsid}'. Adding a new version.")
        versions = datastream.findall(f"{{{NAMESPACES['foxml']}}}datastreamVersion", NAMESPACES)
        last_version = versions[-1] if versions else None
        version_num = len(versions)
        if last_version is not None:
            last_version.tail = '\n' + version_indent
        else:
            datastream.text = '\n' + version_indent

    new_version_id = f"{dsid}.{version_num}"
    now = datetime.utcnow()
    main_part = now.strftime('%Y-%m-%dT%H:%M:%S')
    milliseconds = f'{now.microsecond // 1000:03d}'
    created_timestamp = f'{main_part}.{milliseconds}Z'

    if not mimetype:
        mimetype, _ = mimetypes.guess_type(content_file)
        mimetype = mimetype or 'application/octet-stream'
        print(f"Guessed MIME type: '{mimetype}'")

    if not label:
        label = f"{dsid} datastream"

    ds_version_attrs = {
        'ID': new_version_id, 'LABEL': label, 'CREATED': created_timestamp,
        'MIMETYPE': mimetype, 'SIZE': str(content_size)
    }
    ds_version = ET.SubElement(datastream, f"{{{NAMESPACES['foxml']}}}datastreamVersion", ds_version_attrs)
    
    ds_version.text = '\n' + content_indent
    ds_version.tail = '\n' + datastream_indent

    binary_content_element = ET.SubElement(ds_version, f"{{{NAMESPACES['foxml']}}}binaryContent")
    
    LINE_WIDTH = 76
    chunks = [encoded_content_string[i:i + LINE_WIDTH] for i in range(0, len(encoded_content_string), LINE_WIDTH)]
    
    binary_content_element.text = (
        f"\n{base64_indent}" + 
        f"\n{base64_indent}".join(chunks) +
        f"\n{content_indent}"
    )

    binary_content_element.tail = '\n' + version_indent

    try:
        tree.write(output_path, encoding='UTF-8', xml_declaration=True)
        print(f"Successfully created new version '{new_version_id}'.")
        print(f"Modified FOXML file saved to '{output_path}'")
    except IOError as e:
        print(f"Error writing to output file '{output_path}': {e}")


if __name__ == '__main__':
    parser = argparse.ArgumentParser(
        description='Add or update a datastream in a FOXML file with Base64 encoded content.',
        formatter_class=argparse.RawTextHelpFormatter
    )
    parser.add_argument(
        '-i', '--input-foxml',
        required=True,
        help='Path to the input FOXML file.'
    )
    parser.add_argument(
        '-o', '--output-foxml',
        required=True,
        help='Path to save the modified output FOXML file.'
    )
    parser.add_argument(
        '--dsid',
        required=True,
        help='The ID for the datastream (e.g., "OBJ", "MODS", "FULL_TEXT").'
    )
    parser.add_argument(
        '-f', '--file',
        required=True,
        dest='content_file',
        help='Path to the file to be used as the new datastream content.'
    )
    parser.add_argument(
        '--label',
        default=None,
        help='A human-readable label for the new datastream version. \n(default: "[dsid] datastream")'
    )
    parser.add_argument(
        '--mimetype',
        default=None,
        help='The MIME type of the content file (e.g., "application/pdf").\n(default: auto-detected or "application/octet-stream")'
    )
    parser.add_argument(
        '--control-group',
        default='M',
        choices=['M', 'X', 'R', 'E'],
        help='The control group for the datastream. \'M\' (Managed) is typical for binary content. \n(default: M)'
    )
    args = parser.parse_args()

    update_foxml_datastream(
        input_path=args.input_foxml,
        output_path=args.output_foxml,
        dsid=args.dsid,
        content_file=args.content_file,
        label=args.label,
        mimetype=args.mimetype,
        control_group=args.control_group
    )