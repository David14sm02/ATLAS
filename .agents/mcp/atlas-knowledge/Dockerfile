# ATLAS Knowledge Hub MCP Server - Cloud Container (Render / Railway / VPS TecNM)
FROM python:3.11-slim

# Set working directory
WORKDIR /app

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/*

# Install python dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt flask

# Copy project files
COPY . .

# Environment configuration
ENV PYTHONUNBUFFERED=1
ENV ATLAS_DB_PATH=/app/atlas_knowledge.db
ENV ATLAS_AUTO_SYNC=true
ENV ATLAS_AUTO_SYNC_INTERVAL=300
ENV PORT=8000

# Expose SSE / HTTP port
EXPOSE 8000

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8000/ || exit 1

# Default command: run in SSE / HTTP mode for cloud clients (Cursor, VS Code, Claude Code)
CMD ["python3", "server.py", "--transport", "sse", "--port", "8000"]
