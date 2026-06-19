"""
db.py  -  Database connection layer
Owner: Person 1 (Database)

This file is the ONLY place the rest of the app talks to MySQL.
The backend imports `query()` from here and never opens its own
connection. If you change the database name, user, or password,
change it here once.

Install the driver:  pip install mysql-connector-python
"""
import os
import mysql.connector

# ---- Connection settings -------------------------------------------------
# Override any of these with environment variables if you prefer,
# otherwise edit the defaults to match your local MySQL setup.
DB_CONFIG = {
    "host":     os.environ.get("DB_HOST", "localhost"),
    "user":     os.environ.get("DB_USER", "root"),
    "password": os.environ.get("DB_PASSWORD", ""),   # <-- set your MySQL password
    "database": os.environ.get("DB_NAME", "gym_db"),
}


def get_connection():
    """Open a new MySQL connection."""
    return mysql.connector.connect(**DB_CONFIG)


def query(sql, params=None, fetchone=False, commit=False):
    """
    Run one SQL statement.

      query("SELECT * FROM users")                 -> list of rows (dicts)
      query("SELECT ... WHERE id=%s", (5,), fetchone=True) -> single row or None
      query("INSERT ...", (...), commit=True)      -> new row id (lastrowid)

    Rows come back as dictionaries so templates can use row['name'].
    """
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    try:
        cursor.execute(sql, params or ())
        if commit:
            conn.commit()
            return cursor.lastrowid
        return cursor.fetchone() if fetchone else cursor.fetchall()
    finally:
        cursor.close()
        conn.close()
