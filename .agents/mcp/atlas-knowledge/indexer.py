#!/usr/bin/env python3
"""
Full-Text Indexer for ATLAS Knowledge Base
Indexes local project documentation, regulatory manuals and GitHub repository code into SQLite FTS5.
Part of atlas-knowledge-mcp
"""

import os
import re
import hashlib
from datetime import datetime
from typing import Dict, Any
from db import (
    init_db,
    index_chunk,
    get_connection,
    get_indexed_document,
    record_indexed_document,
    delete_chunks_by_reference
)

DOCS_DIR = os.environ.get("ATLAS_DOCS_DIR", os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", "..", "docs")))
REPO_DIR = os.environ.get("ATLAS_REPO_DIR", os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", "..")))

def calculate_sha256(filepath: str) -> str:
    sha = hashlib.sha256()
    with open(filepath, 'rb') as f:
        while chunk := f.read(65536):
            sha.update(chunk)
    return sha.hexdigest()

def chunk_markdown(content: str, max_chars: int = 1500):
    """Splits markdown into logical sections based on headers or paragraphs."""
    sections = re.split(r'\n(?=#{1,4}\s)', content)
    chunks = []
    for sec in sections:
        sec = sec.strip()
        if not sec:
            continue
        lines = sec.split('\n')
        header = lines[0] if lines[0].startswith('#') else "General"
        if len(sec) <= max_chars:
            chunks.append((header, sec))
        else:
            # Sub-split long sections
            paragraphs = sec.split('\n\n')
            current = []
            cur_len = 0
            for p in paragraphs:
                if cur_len + len(p) > max_chars and current:
                    chunks.append((header, '\n\n'.join(current)))
                    current = [p]
                    cur_len = len(p)
                else:
                    current.append(p)
                    cur_len += len(p)
            if current:
                chunks.append((header, '\n\n'.join(current)))
    return chunks

def extract_text_from_file(fpath: str) -> str:
    """Extracts text content from various file formats (.md, .txt, .docx, .pdf, .xlsx, .xlsm)."""
    ext = os.path.splitext(fpath)[1].lower()
    
    if ext in ('.md', '.txt'):
        with open(fpath, 'r', encoding='utf-8', errors='ignore') as fp:
            return fp.read()
            
    elif ext == '.docx':
        try:
            import docx
            doc = docx.Document(fpath)
            parts = []
            for p in doc.paragraphs:
                if p.text.strip():
                    parts.append(p.text.strip())
            for t in doc.tables:
                for row in t.rows:
                    row_txt = " | ".join([c.text.strip().replace('\n', ' ') for c in row.cells if c.text.strip()])
                    if row_txt:
                        parts.append(f"| {row_txt} |")
            return "\n\n".join(parts)
        except Exception as e:
            print(f"[-] Error reading docx {fpath}: {e}")
            return ""

    elif ext == '.pdf':
        try:
            import pypdf
            reader = pypdf.PdfReader(fpath)
            pages_txt = []
            for i, p in enumerate(reader.pages):
                txt = p.extract_text() or ""
                if txt.strip():
                    pages_txt.append(f"## Página {i+1}\n{txt.strip()}")
            return "\n\n".join(pages_txt)
        except Exception as e:
            print(f"[-] Error reading pdf {fpath}: {e}")
            return ""

    elif ext in ('.xlsx', '.xlsm'):
        try:
            import openpyxl
            wb = openpyxl.load_workbook(fpath, read_only=True, data_only=True)
            summary = [f"# Resumen de Libro Excel: {os.path.basename(fpath)}", f"Hojas: {', '.join(wb.sheetnames)}"]
            for sname in wb.sheetnames[:5]:  # Summarize first 5 sheets
                sheet = wb[sname]
                summary.append(f"## Hoja: {sname}")
                headers = []
                for i, row in enumerate(sheet.iter_rows(max_row=3, values_only=True)):
                    if i == 0:
                        headers = [str(c).strip().replace('\n', ' ') for c in row if c is not None]
                        summary.append(f"Columnas ({len(headers)}): {', '.join(headers[:30])}")
                    elif i == 1:
                        sample_vals = [str(c).strip()[:40] for c in row if c is not None]
                        summary.append(f"Muestra fila 1: {', '.join(sample_vals[:15])}")
            wb.close()
            return "\n\n".join(summary)
        except Exception as e:
            print(f"[-] Error reading excel summary {fpath}: {e}")
            return ""

    return ""

def sync_local_docs_incremental(docs_dir: str = DOCS_DIR) -> Dict[str, int]:
    """Scans local documents and indexes markdown, docx, pdf, and excel files."""
    if not os.path.exists(docs_dir):
        return {"updated": 0, "skipped": 0, "chunks": 0}

    updated_files = 0
    skipped_files = 0
    new_chunks = 0
    supported_extensions = ('.md', '.txt', '.docx', '.pdf', '.xlsx', '.xlsm')

    for root, _, files in os.walk(docs_dir):
        for f in files:
            if f.startswith('~$'):
                continue
            ext = os.path.splitext(f)[1].lower()
            if ext in supported_extensions:
                fpath = os.path.join(root, f)
                try:
                    mtime = str(os.path.getmtime(fpath))
                    doc_meta = get_indexed_document(fpath)
                    if doc_meta and doc_meta.get("modified_at") == mtime:
                        skipped_files += 1
                        continue

                    sha = calculate_sha256(fpath)
                    if doc_meta and doc_meta.get("sha256_hash") == sha:
                        record_indexed_document(
                            source_type="LOCAL_DOC",
                            file_path_or_id=fpath,
                            file_name=f,
                            sha256_hash=sha,
                            modified_at=mtime
                        )
                        skipped_files += 1
                        continue

                    # File changed or new: delete old chunks and insert new
                    ref_link = f"file://{fpath}"
                    delete_chunks_by_reference(ref_link)

                    text = extract_text_from_file(fpath)
                    if not text.strip():
                        # Still record it so we don't re-read empty files every time
                        record_indexed_document(
                            source_type="LOCAL_DOC",
                            file_path_or_id=fpath,
                            file_name=f,
                            sha256_hash=sha,
                            modified_at=mtime
                        )
                        skipped_files += 1
                        continue

                    chunks = chunk_markdown(text)
                    for header, chunk_text in chunks:
                        index_chunk(
                            doc_title=f,
                            section=header,
                            content=chunk_text,
                            source_type="LOCAL_DOC",
                            reference_link=ref_link
                        )
                        new_chunks += 1

                    record_indexed_document(
                        source_type="LOCAL_DOC",
                        file_path_or_id=fpath,
                        file_name=f,
                        sha256_hash=sha,
                        modified_at=mtime
                    )
                    updated_files += 1
                except Exception as e:
                    print(f"[-] Error indexing {f}: {e}")

    return {"updated": updated_files, "skipped": skipped_files, "chunks": new_chunks}

def sync_github_repo_incremental(repo_dir: str = REPO_DIR) -> Dict[str, int]:
    """Scans code repository and incrementally indexes migrations, models, and filament resources."""
    if not os.path.exists(repo_dir):
        return {"updated": 0, "skipped": 0, "chunks": 0}

    updated_files = 0
    skipped_files = 0
    new_chunks = 0

    targets = [
        ("MIGRATION", os.path.join(repo_dir, "database", "migrations")),
        ("MODEL", os.path.join(repo_dir, "app", "Models")),
        ("FILAMENT", os.path.join(repo_dir, "app", "Filament"))
    ]

    for label, folder in targets:
        if not os.path.exists(folder):
            continue
        for root, _, files in os.walk(folder):
            for f in files:
                if f.endswith('.php'):
                    fpath = os.path.join(root, f)
                    try:
                        mtime = str(os.path.getmtime(fpath))
                        doc_meta = get_indexed_document(fpath)
                        if doc_meta and doc_meta.get("modified_at") == mtime:
                            skipped_files += 1
                            continue

                        sha = calculate_sha256(fpath)
                        if doc_meta and doc_meta.get("sha256_hash") == sha:
                            record_indexed_document(
                                source_type="GITHUB",
                                file_path_or_id=fpath,
                                file_name=f,
                                sha256_hash=sha,
                                modified_at=mtime
                            )
                            skipped_files += 1
                            continue

                        rel_path = os.path.relpath(fpath, repo_dir)
                        ref_link = f"https://github.com/Richpol99/ATLAS/blob/main/{rel_path}"
                        delete_chunks_by_reference(ref_link)

                        with open(fpath, 'r', encoding='utf-8', errors='ignore') as fp:
                            code = fp.read()

                        index_chunk(
                            doc_title=f"GitHub: {f}",
                            section=f"{label} - {rel_path}",
                            content=code[:2500],
                            source_type="GITHUB",
                            reference_link=ref_link
                        )
                        record_indexed_document(
                            source_type="GITHUB",
                            file_path_or_id=fpath,
                            file_name=f,
                            sha256_hash=sha,
                            modified_at=mtime
                        )
                        updated_files += 1
                        new_chunks += 1
                    except Exception as e:
                        print(f"[-] Error indexing code {f}: {e}")

    return {"updated": updated_files, "skipped": skipped_files, "chunks": new_chunks}

def sync_all_sources_incremental(docs_dir: str = DOCS_DIR, repo_dir: str = REPO_DIR) -> Dict[str, Any]:
    """Unified entry point for background auto-sync without blocking."""
    init_db()
    docs_res = sync_local_docs_incremental(docs_dir)
    repo_res = sync_github_repo_incremental(repo_dir)
    return {
        "docs": docs_res,
        "repo": repo_res,
        "total_updated": docs_res["updated"] + repo_res["updated"],
        "total_new_chunks": docs_res["chunks"] + repo_res["chunks"]
    }

def run_full_indexer():
    init_db()
    res = sync_all_sources_incremental()
    print(f"[+] Indexing completed: {res['total_updated']} files updated ({res['total_new_chunks']} chunks).")

if __name__ == "__main__":
    run_full_indexer()

