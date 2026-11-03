#!/usr/bin/env python3
"""
ATLAS Knowledge Base - SQLite FTS5 Storage Engine
Part of atlas-knowledge-mcp
"""

import sqlite3
import os
import json
from datetime import datetime
from typing import List, Dict, Any, Optional

DEFAULT_DB_PATH = os.environ.get(
    "ATLAS_DB_PATH",
    os.path.join(os.path.dirname(os.path.abspath(__file__)), "atlas_knowledge.db")
)

def get_connection(db_path: str = DEFAULT_DB_PATH) -> sqlite3.Connection:
    os.makedirs(os.path.dirname(db_path), exist_ok=True)
    conn = sqlite3.connect(db_path)
    conn.row_factory = sqlite3.Row
    return conn

def init_db(db_path: str = DEFAULT_DB_PATH):
    conn = get_connection(db_path)
    cur = conn.cursor()

    # Document registry
    cur.execute("""
    CREATE TABLE IF NOT EXISTS indexed_documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        source_type TEXT NOT NULL,
        file_path_or_id TEXT UNIQUE NOT NULL,
        file_name TEXT NOT NULL,
        mime_type TEXT,
        sha256_hash TEXT,
        modified_at TEXT,
        indexed_at TEXT NOT NULL
    );
    """)

    # High-density Atomic Cards (< 800 tokens each)
    cur.execute("""
    CREATE TABLE IF NOT EXISTS atomic_cards (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        topic_slug TEXT UNIQUE NOT NULL,
        title TEXT NOT NULL,
        high_density_content TEXT NOT NULL,
        token_count_estimate INTEGER NOT NULL,
        updated_at TEXT NOT NULL
    );
    """)

    # Full-Text Search Table (FTS5) with unicode61 tokenizer
    cur.execute("""
    CREATE VIRTUAL TABLE IF NOT EXISTS search_index_fts USING fts5(
        doc_title,
        section,
        content,
        source_type,
        reference_link,
        tokenize = 'unicode61 remove_diacritics 2'
    );
    """)

    # Sync state tracking
    cur.execute("""
    CREATE TABLE IF NOT EXISTS sync_state (
        source_key TEXT PRIMARY KEY,
        last_sync_at TEXT NOT NULL,
        status TEXT NOT NULL,
        items_processed INTEGER NOT NULL
    );
    """)

    conn.commit()
    conn.close()

def upsert_atomic_card(topic_slug: str, title: str, content: str, db_path: str = DEFAULT_DB_PATH):
    conn = get_connection(db_path)
    cur = conn.cursor()
    # Estimate tokens: ~4 chars per token in Spanish/English technical text
    est_tokens = len(content) // 4
    now = datetime.utcnow().isoformat()

    cur.execute("""
    INSERT INTO atomic_cards (topic_slug, title, high_density_content, token_count_estimate, updated_at)
    VALUES (?, ?, ?, ?, ?)
    ON CONFLICT(topic_slug) DO UPDATE SET
        title = excluded.title,
        high_density_content = excluded.high_density_content,
        token_count_estimate = excluded.token_count_estimate,
        updated_at = excluded.updated_at
    """, (topic_slug, title, content, est_tokens, now))

    conn.commit()
    conn.close()

def get_atomic_card(topic_slug: str, db_path: str = DEFAULT_DB_PATH) -> Optional[Dict[str, Any]]:
    conn = get_connection(db_path)
    cur = conn.cursor()
    cur.execute("SELECT * FROM atomic_cards WHERE topic_slug = ?", (topic_slug,))
    row = cur.fetchone()
    conn.close()
    if row:
        return dict(row)
    return None

def list_atomic_cards(db_path: str = DEFAULT_DB_PATH) -> List[Dict[str, Any]]:
    conn = get_connection(db_path)
    cur = conn.cursor()
    cur.execute("SELECT topic_slug, title, token_count_estimate, updated_at FROM atomic_cards ORDER BY topic_slug ASC")
    rows = cur.fetchall()
    conn.close()
    return [dict(r) for r in rows]

def index_chunk(doc_title: str, section: str, content: str, source_type: str, reference_link: str, db_path: str = DEFAULT_DB_PATH):
    conn = get_connection(db_path)
    cur = conn.cursor()
    cur.execute("""
    INSERT INTO search_index_fts (doc_title, section, content, source_type, reference_link)
    VALUES (?, ?, ?, ?, ?)
    """, (doc_title, section, content, source_type, reference_link))
    conn.commit()
    conn.close()

def search_knowledge(query: str, source_filter: Optional[str] = None, limit: int = 3, db_path: str = DEFAULT_DB_PATH) -> List[Dict[str, Any]]:
    conn = get_connection(db_path)
    cur = conn.cursor()
    
    # Sanitize FTS5 query
    clean_query = query.replace('"', '').replace("'", "").strip()
    if not clean_query:
        conn.close()
        return []

    # Format words for FTS5 prefix match
    words = [w for w in clean_query.split() if len(w) > 2]
    if not words:
        words = clean_query.split()
    
    fts_expression = " OR ".join(f'"{w}"*' for w in words)

    if source_filter and source_filter.lower() != "todos":
        cur.execute("""
        SELECT doc_title, section, content, source_type, reference_link, rank
        FROM search_index_fts
        WHERE search_index_fts MATCH ? AND source_type = ?
        ORDER BY rank
        LIMIT ?
        """, (fts_expression, source_filter.upper(), limit))
    else:
        cur.execute("""
        SELECT doc_title, section, content, source_type, reference_link, rank
        FROM search_index_fts
        WHERE search_index_fts MATCH ?
        ORDER BY rank
        LIMIT ?
        """, (fts_expression, limit))
        
    rows = cur.fetchall()
    conn.close()
    return [dict(r) for r in rows]

def get_indexed_document(file_path_or_id: str, db_path: str = DEFAULT_DB_PATH) -> Optional[Dict[str, Any]]:
    conn = get_connection(db_path)
    cur = conn.cursor()
    cur.execute("SELECT * FROM indexed_documents WHERE file_path_or_id = ?", (file_path_or_id,))
    row = cur.fetchone()
    conn.close()
    return dict(row) if row else None

def record_indexed_document(
    source_type: str,
    file_path_or_id: str,
    file_name: str,
    mime_type: str = "text/plain",
    sha256_hash: str = "",
    modified_at: str = "",
    db_path: str = DEFAULT_DB_PATH
):
    conn = get_connection(db_path)
    cur = conn.cursor()
    now = datetime.utcnow().isoformat()
    cur.execute("""
    INSERT INTO indexed_documents (source_type, file_path_or_id, file_name, mime_type, sha256_hash, modified_at, indexed_at)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON CONFLICT(file_path_or_id) DO UPDATE SET
        file_name = excluded.file_name,
        mime_type = excluded.mime_type,
        sha256_hash = excluded.sha256_hash,
        modified_at = excluded.modified_at,
        indexed_at = excluded.indexed_at
    """, (source_type, file_path_or_id, file_name, mime_type, sha256_hash, modified_at, now))
    conn.commit()
    conn.close()

def delete_chunks_by_reference(reference_link: str, db_path: str = DEFAULT_DB_PATH):
    conn = get_connection(db_path)
    cur = conn.cursor()
    cur.execute("DELETE FROM search_index_fts WHERE reference_link = ?", (reference_link,))
    conn.commit()
    conn.close()

if __name__ == "__main__":
    init_db()
    print("Database initialized successfully at:", DEFAULT_DB_PATH)

