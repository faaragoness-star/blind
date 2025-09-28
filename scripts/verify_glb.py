#!/usr/bin/env python3
import os, sys, struct, json, argparse

def check_glb(path, max_bytes):
    size = os.path.getsize(path)
    if size > max_bytes:
        return (False, f"ERROR: {os.path.basename(path)} supera {max_bytes} bytes ({size})")
    with open(path, "rb") as f:
        head = f.read(12)
        if len(head) < 12 or head[:4] != b'glTF':
            return (True, f"WARN: {os.path.basename(path)} no parece GLB v2 (cabecera)")
        version, length = struct.unpack("<II", head[4:12])
        if version != 2:
            return (True, f"WARN: {os.path.basename(path)} no es glTF v2 (v{version})")
        # Lee primer chunk (JSON)
        chunk_header = f.read(8)
        if len(chunk_header) < 8:
            return (True, f"WARN: {os.path.basename(path)} sin chunk JSON")
        chunk_len, chunk_type = struct.unpack("<II", chunk_header)
        if chunk_type != 0x4E4F534A:  # 'JSON'
            return (True, f"WARN: {os.path.basename(path)} primer chunk no es JSON")
        json_str = f.read(chunk_len).decode("utf-8", errors="ignore")
        try:
            doc = json.loads(json_str)
        except Exception:
            return (True, f"WARN: {os.path.basename(path)} JSON ilegible")
        used = set(doc.get("extensionsUsed", []))
        has_draco = "KHR_draco_mesh_compression" in used
        msg = f"OK: {os.path.basename(path)} ({size} B)"
        if not has_draco:
            msg += " — WARN: sin KHR_draco_mesh_compression"
        return (True, msg)

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--dir", required=True)
    ap.add_argument("--max-bytes", type=int, default=12*1024*1024)
    args = ap.parse_args()

    ok = True
    if not os.path.isdir(args.dir):
        print(f"OK assets: {args.dir} no existe (saltado)")
        return 0

    for root, _, files in os.walk(args.dir):
        for fn in files:
            if fn.lower().endswith(".glb"):
                good, msg = check_glb(os.path.join(root, fn), args.max_bytes)
                print(msg)
                ok = ok and good
    return 0 if ok else 1

if __name__ == "__main__":
    sys.exit(main())
