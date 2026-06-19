"""
config.py  -  Flask application settings
Owner: Person 2 (Backend)

The SECRET_KEY signs the login session cookie. Use any long random
string for development; change it before deploying anywhere real.
"""
import os

SECRET_KEY = os.environ.get("SECRET_KEY", "change-this-to-a-long-random-string")
