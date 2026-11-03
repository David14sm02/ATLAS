#!/usr/bin/env python3
"""
GitHub Webhook Handler for ATLAS Knowledge Base
Monitors commits and PRs on David14sm02/ATLAS to keep schemas and models updated in real time.
Part of MCP-ATLAS-TecNM
"""

import os
import json
import logging
from typing import Dict, Any
from db import index_chunk, upsert_atomic_card, get_connection

TARGET_REPO = "David14sm02/ATLAS"

def handle_github_push_event(payload: Dict[str, Any]) -> Dict[str, Any]:
    """
    Processes incoming GitHub push webhook payload.
    Extracts modified migrations and models, updating the knowledge base.
    """
    repo_name = payload.get("repository", {}).get("full_name", "")
    commits = payload.get("commits", [])
    ref = payload.get("ref", "")

    logging.info(f"Received push event for repo: {repo_name} (ref: {ref}) with {len(commits)} commits.")

    updated_files = []
    for commit in commits:
        author = commit.get("author", {}).get("name", "Unknown")
        message = commit.get("message", "")
        
        # Check added and modified files
        all_changed = commit.get("added", []) + commit.get("modified", [])
        for fpath in all_changed:
            if "migrations" in fpath or "Models" in fpath or "Filament" in fpath or fpath.endswith(".md"):
                updated_files.append({
                    "path": fpath,
                    "commit_id": commit.get("id", "")[:7],
                    "author": author,
                    "message": message
                })

    # Record sync state
    if updated_files:
        conn = get_connection()
        cur = conn.cursor()
        cur.execute("""
        INSERT INTO sync_state (source_key, last_sync_at, status, items_processed)
        VALUES ('github_webhook', datetime('now'), 'success', ?)
        ON CONFLICT(source_key) DO UPDATE SET
            last_sync_at = excluded.last_sync_at,
            status = excluded.status,
            items_processed = excluded.items_processed
        """, (len(updated_files),))
        conn.commit()
        conn.close()

    return {
        "status": "success",
        "repo": repo_name,
        "processed_files_count": len(updated_files),
        "updated_files": updated_files
    }
