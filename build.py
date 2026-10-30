"""Builds the installable ZIP for com_custompages.

The ZIP is named com_custompages-<version>.zip, with the version taken from the
manifest. Its root holds the manifest plus administrator/, site/, language/ and
media/. Every file and folder the manifest references is checked to be in the ZIP.

Usage:
    python build.py                          # writes ./com_custompages-<version>.zip
    python build.py --version 1.2.0          # fails unless the manifest says 1.2.0
    python build.py --out-dir dist
"""

import argparse
import pathlib
import sys
import xml.etree.ElementTree as ET
import zipfile

ROOT = pathlib.Path(__file__).resolve().parent
SRC = ROOT / "com_custompages"
ELEMENT = "com_custompages"
MANIFEST = "custompages.xml"
INCLUDE = [MANIFEST, "administrator", "site", "language", "media"]


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


def main():
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--version", help="expected version; the build fails if the manifest differs")
    parser.add_argument("--out-dir", default=str(ROOT), help="directory to write the ZIP to")
    args = parser.parse_args()

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


if __name__ == "__main__":
    main()
