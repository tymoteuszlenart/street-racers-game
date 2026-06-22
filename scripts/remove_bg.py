#!/usr/bin/env python3
"""
Remove backgrounds from car images using the remove.bg API.
Processed images are saved to public/cars/ (overwriting originals).
Originals are backed up to public/cars/originals/ first.

Usage:
    export REMOVEBG_API_KEY=your_api_key_here
    python scripts/remove_bg.py
    python scripts/remove_bg.py --dry-run          # preview without calling API
    python scripts/remove_bg.py --file cars/foo.png  # single file
"""

import argparse
import os
import shutil
import sys
from pathlib import Path

import requests

API_URL = "https://api.remove.bg/v1.0/removebg"
CARS_DIR = Path(__file__).parent.parent / "public" / "cars"
BACKUP_DIR = CARS_DIR / "originals"


def get_api_key() -> str:
    key = os.environ.get("REMOVEBG_API_KEY", "")
    if not key:
        sys.exit(
            "Error: REMOVEBG_API_KEY environment variable is not set.\n"
            "Get your key at https://www.remove.bg/api and run:\n"
            "  export REMOVEBG_API_KEY=your_key_here"
        )
    return key


def remove_bg(image_path: Path, api_key: str) -> bytes:
    with image_path.open("rb") as f:
        response = requests.post(
            API_URL,
            files={"image_file": f},
            data={"size": "auto"},
            headers={"X-Api-Key": api_key},
            timeout=60,
        )
    if response.status_code == 200:
        return response.content
    error = response.json().get("errors", [{}])[0].get("title", response.text)
    raise RuntimeError(f"remove.bg API error ({response.status_code}): {error}")


def process_images(images: list[Path], api_key: str, dry_run: bool) -> None:
    BACKUP_DIR.mkdir(parents=True, exist_ok=True)

    total = len(images)
    ok = 0
    failed = []

    for i, img in enumerate(images, 1):
        print(f"[{i}/{total}] {img.name}", end="  ")

        if dry_run:
            print("(dry-run, skipped)")
            continue

        backup = BACKUP_DIR / img.name
        if not backup.exists():
            shutil.copy2(img, backup)
            print(f"backed up → originals/{img.name}", end="  ")

        try:
            result = remove_bg(img, api_key)
            img.write_bytes(result)
            print("done")
            ok += 1
        except RuntimeError as e:
            print(f"FAILED — {e}")
            failed.append(img.name)

    print(f"\n{'─' * 40}")
    if dry_run:
        print(f"Dry run complete. Would process {total} image(s).")
    else:
        print(f"Done: {ok}/{total} succeeded.")
        if failed:
            print("Failed:")
            for name in failed:
                print(f"  • {name}")


def main() -> None:
    parser = argparse.ArgumentParser(description="Remove backgrounds from car images.")
    parser.add_argument("--dry-run", action="store_true", help="List files without calling the API")
    parser.add_argument("--file", metavar="PATH", help="Process a single file instead of all cars")
    args = parser.parse_args()

    api_key = get_api_key() if not args.dry_run else "dry-run"

    if args.file:
        path = Path(args.file)
        if not path.is_absolute():
            path = CARS_DIR.parent.parent / args.file
        if not path.exists():
            sys.exit(f"File not found: {path}")
        images = [path]
    else:
        images = sorted(CARS_DIR.glob("*.png")) + sorted(CARS_DIR.glob("*.jpg")) + sorted(CARS_DIR.glob("*.jpeg"))
        if not images:
            sys.exit(f"No images found in {CARS_DIR}")

    print(f"Images to process: {len(images)}")
    for img in images:
        print(f"  {img.relative_to(CARS_DIR.parent.parent)}")
    print()

    process_images(images, api_key, args.dry_run)


if __name__ == "__main__":
    main()
