"""Builds the installable ZIP for com_custompages.

The ZIP is named com_custompages-<version>.zip, with the version taken from the
manifest. Its root holds the manifest plus administrator/, site/, language/ and
media/. Every file and folder the manifest references is checked to be in the ZIP.

With --repo and --tag it also writes updates.xml, the Joomla update server feed
for that GitHub release. The manifest's update server must point at
https://github.com/<repo>/releases/latest/download/updates.xml.

Usage:
    python build.py                          # writes ./com_custompages-<version>.zip
    python build.py --version 1.2.0          # fails unless the manifest says 1.2.0
    python build.py --out-dir dist
    python build.py --out-dir dist --repo owner/name --tag v1.2.0
"""

import argparse
import hashlib
import pathlib
import sys
import xml.etree.ElementTree as ET
import zipfile

ROOT = pathlib.Path(__file__).resolve().parent
SRC = ROOT / "com_custompages"
ELEMENT = "com_custompages"
MANIFEST = "custompages.xml"
INCLUDE = [MANIFEST, "script.php", "administrator", "site", "language", "media"]

# Joomla versions offered the update, matched as a regex against the site's version.
TARGET_PLATFORM = r"5\.[0-9]+"
PHP_MINIMUM = "8.1"


def manifest_paths(manifest):
    """Yields every path the manifest expects in the package, relative to the ZIP root."""
    admin = manifest.find("administration")

    for section in (manifest, admin):
        for tag in ("files", "languages", "media"):
            for group in section.findall(tag):
                base = group.get("folder", "")
                for child in group:
                    yield f"{base}/{child.text.strip()}".strip("/")

    for sql in manifest.iterfind(".//sql/file"):
        yield f"administrator/{sql.text.strip()}"

    for schema in manifest.iterfind(".//schemas/schemapath"):
        yield f"administrator/{schema.text.strip()}"


def write_update_xml(manifest, version, zip_path, repo, tag):
    """Writes updates.xml next to the ZIP, describing it as the newest release."""
    expected = f"https://github.com/{repo}/releases/latest/download/updates.xml"
    servers = [s.text.strip() for s in manifest.iterfind("updateservers/server")]

    if expected.lower() not in (s.lower() for s in servers):
        sys.exit(f"error: {MANIFEST} update server must be {expected} (found: {', '.join(servers) or 'none'})")

    updates = ET.Element("updates")
    update = ET.SubElement(updates, "update")

    for tag_name, text in (
        ("name", "Custom Pages"),
        ("description", "Custom Pages update"),
        ("element", ELEMENT),
        ("type", "component"),
        ("client", "administrator"),
        ("version", version),
    ):
        ET.SubElement(update, tag_name).text = text

    ET.SubElement(update, "infourl", title="Release notes").text = f"https://github.com/{repo}/releases/tag/{tag}"
    downloads = ET.SubElement(update, "downloads")
    ET.SubElement(downloads, "downloadurl", type="full", format="zip").text = (
        f"https://github.com/{repo}/releases/download/{tag}/{zip_path.name}"
    )
    ET.SubElement(update, "sha512").text = hashlib.sha512(zip_path.read_bytes()).hexdigest()
    ET.SubElement(ET.SubElement(update, "tags"), "tag").text = "stable"
    ET.SubElement(update, "maintainer").text = manifest.findtext("author", "").strip()
    ET.SubElement(update, "maintainerurl").text = manifest.findtext("authorUrl", "").strip()
    ET.SubElement(update, "targetplatform", name="joomla", version=TARGET_PLATFORM)
    ET.SubElement(update, "php_minimum").text = PHP_MINIMUM

    ET.indent(updates, space="    ")
    out = zip_path.parent / "updates.xml"
    ET.ElementTree(updates).write(out, encoding="utf-8", xml_declaration=True)

    return out


def main():
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--version", help="expected version; the build fails if the manifest differs")
    parser.add_argument("--out-dir", default=str(ROOT), help="directory to write the ZIP to")
    parser.add_argument("--repo", help="GitHub owner/name; with --tag, also writes updates.xml")
    parser.add_argument("--tag", help="release tag the ZIP is attached to, e.g. v1.2.0")
    args = parser.parse_args()

    if bool(args.repo) != bool(args.tag):
        parser.error("--repo and --tag must be given together")

    manifest = ET.parse(SRC / MANIFEST).getroot()
    version = manifest.findtext("version", "").strip()

    if not version:
        sys.exit(f"error: no <version> in {MANIFEST}")

    if args.version and args.version != version:
        sys.exit(f"error: requested version {args.version} but {MANIFEST} says {version}")

    out_dir = pathlib.Path(args.out_dir).resolve()
    out_dir.mkdir(parents=True, exist_ok=True)
    out = out_dir / f"{ELEMENT}-{version}.zip"

    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as zf:
        for name in INCLUDE:
            path = SRC / name
            files = [path] if path.is_file() else sorted(p for p in path.rglob("*") if p.is_file())
            for f in files:
                zf.write(f, f.relative_to(SRC).as_posix())

        names = zf.namelist()

    missing = [p for p in manifest_paths(manifest) if p not in names and not any(n.startswith(p + "/") for n in names)]

    if missing:
        out.unlink()
        sys.exit("error: manifest references paths missing from the package:\n  " + "\n  ".join(missing))

    print(out)

    if args.repo:
        print(write_update_xml(manifest, version, out, args.repo, args.tag))


if __name__ == "__main__":
    main()
