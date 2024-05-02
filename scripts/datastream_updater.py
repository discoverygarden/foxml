#!/usr/bin/env python

import base64
import datetime
import argparse
from lxml import etree as ET
from lxml.etree import QName
import mimetypes
import logging

# Setting up basic logging
logging.basicConfig(level=logging.INFO)


def format_xml_element(element, level=0, indent="  "):
    """
    Formats an XML element by adding appropriate spacing and indentation.

    Args:
      element (Element): The XML element to format.
      level (int, optional): The current level of indentation. Defaults to 0.
      indent (str, optional): The string used for indentation. Defaults to "  ".

    Returns:
      None
    """
    spacing = "\n" + level * indent

    if len(element):
        if not element.text or not element.text.strip():
            element.text = spacing + indent
        if not element.tail or not element.tail.strip():
            element.tail = spacing
        for child in element:
            format_xml_element(child, level + 1, indent)
    else:
        if level and (not element.tail or not element.tail.strip()):
            element.tail = spacing


def compress_and_encode(file_path):
    """
    Compresses and encodes the binary data from the given file path.

    Args:
      file_path (str): The path to the file containing the binary data.

    Returns:
      tuple: A tuple containing the indented base64-encoded data and the original size of the binary data.
    """
    with open(file_path, "rb") as f_in:
        binary_data = f_in.read()
        original_size = len(binary_data)
        base64_data = base64.b64encode(binary_data)
        base64_lines = [
            base64_data[i : i + 80].decode("utf-8")
            for i in range(0, len(base64_data), 80)
        ]
        indented_base64 = "\n              ".join(base64_lines)
        return indented_base64, original_size


def register_namespaces(xml_path):
    """
    Registers XML namespaces from the given XML file.

    Args:
      xml_path (str): The path to the XML file.

    Raises:
      Exception: If there is an error registering the namespaces.
    """
    try:
        namespaces = dict(
            [node for _, node in ET.iterparse(xml_path, events=["start-ns"])]
        )
        for ns in namespaces:
            ET.register_namespace(ns, namespaces[ns])
    except Exception as e:
        logging.error(f"Error registering namespaces: {e}")
        raise


def add_datastream_version(
    xml_path, dsid, base64_data, original_size, mimetype, label=None
):
    """
    Adds a new version of a datastream to an XML file.

    Args:
      xml_path (str): The path to the XML file.
      dsid (str): The ID of the datastream.
      base64_data (str): The base64-encoded content of the datastream.
      original_size (int): The original size of the datastream in bytes.
      mimetype (str): The MIME type of the datastream.
      label (str, optional): The label for the datastream version. If not provided, a default label will be used.

    Returns:
      str: The XML string with the new datastream version added.

    Raises:
      ET.ParseError: If there is an error parsing the XML file.
      Exception: If there is an error creating the XML string.
    """
    try:
        root = ET.parse(xml_path).getroot()
    except ET.ParseError as e:
        logging.exception(f"XML parsing error: {e}")
        return

    nsmap = {
        "foxml": "info:fedora/fedora-system:def/foxml#",
        "xsi": "http://www.w3.org/2001/XMLSchema-instance",
        "audit": "info:fedora/fedora-system:def/audit#",
        "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
        "fedora": "info:fedora/fedora-system:def/relations-external#",
        "fedora-model": "info:fedora/fedora-system:def/model#",
        "islandora": "http://islandora.ca/ontology/relsext#",
    }

    # Have to use qualified names when creating an element.
    ds_version_tag = QName(nsmap["foxml"], "datastreamVersion")
    binary_content_tag = QName(nsmap["foxml"], "binaryContent")

    datastream = root.find(f".//foxml:datastream[@ID='{dsid}']", namespaces=nsmap)
    if datastream is None:
        logging.warning(f"Datastream with ID of {dsid} does not exist.")
        return

    if label is None:
        datastream_version = datastream.find(
            ".//foxml:datastreamVersion[last()]", namespaces=nsmap
        )
        label = (
            datastream_version.get("LABEL")
            if datastream_version is not None
            else "default_label"
        )

    new_id = "{}.{}".format(
        dsid, len(datastream.findall(".//foxml:datastreamVersion", namespaces=nsmap))
    )
    datastream_version = ET.SubElement(
        datastream,
        ds_version_tag,
        {
            "ID": new_id,
            "LABEL": label,
            "MIMETYPE": mimetype,
            "SIZE": str(original_size),
        },
    )

    dt = datetime.datetime.utcnow().strftime("%Y-%m-%dT%H:%M:%S.%f")[:-3] + "Z"
    datastream_version.set("CREATED", dt)

    binary_content = ET.SubElement(datastream_version, binary_content_tag)
    binary_content.text = "\n    " + base64_data + "\n    "

    try:
        ET.indent(root, space="  ")
        format_xml_element(root)
        xml_string = ET.tostring(
            root, encoding="utf-8", method="xml", xml_declaration=True
        )
    except Exception as e:
        logging.exception(f"Error creating XML string: {e}")
        raise

    return xml_string


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--xml", help="path to the XML file to modify", required=True)
    parser.add_argument("--dsid", help="ID of the datastream to modify", required=True)
    parser.add_argument(
        "--content",
        help="path to the binary content to add as a new datastreamVersion",
        required=True,
    )
    parser.add_argument("--label", help="label of the new datastream version")
    parser.add_argument("--output", help="path to the output XML file", required=True)
    args = parser.parse_args()

    try:
        mimetype, _ = mimetypes.guess_type(args.content)
        mimetype = mimetype or "application/octet-stream"

        base64_data, original_size = compress_and_encode(args.content)
        register_namespaces(args.xml)
        updated_xml = add_datastream_version(
            args.xml, args.dsid, base64_data, original_size, mimetype, args.label
        )

        if updated_xml:
            with open(args.output, "w") as f_out:
                f_out.write(updated_xml.decode("utf-8"))
    except Exception as e:
        logging.exception(f"Error in script execution: {e}")
