"""
auth.py  -  Login state + role-based access
Owner: Person 2 (Backend)

Small helpers so routes can require a login or a specific role.
Usage in app.py:

    @app.route("/admin/dashboard")
    @role_required("Admin")
    def admin_dashboard():
        ...
"""
from functools import wraps
from flask import session, redirect, url_for, flash


def current_user():
    """Return the logged-in user as a dict, or None."""
    if "user_id" in session:
        return {
            "user_id": session["user_id"],
            "full_name": session["full_name"],
            "role": session["role"],
        }
    return None


def login_user(user):
    """Save the essentials of a user row into the session."""
    session["user_id"] = user["user_id"]
    session["full_name"] = user["full_name"]
    session["role"] = user["role_name"]


def logout_user():
    session.clear()


def login_required(view):
    @wraps(view)
    def wrapped(*args, **kwargs):
        if current_user() is None:
            flash("Please log in to continue.", "error")
            return redirect(url_for("login"))
        return view(*args, **kwargs)
    return wrapped


def role_required(role_name):
    """Allow only users whose role matches (also requires login)."""
    def decorator(view):
        @wraps(view)
        def wrapped(*args, **kwargs):
            user = current_user()
            if user is None:
                flash("Please log in to continue.", "error")
                return redirect(url_for("login"))
            if user["role"] != role_name:
                flash("You do not have access to that page.", "error")
                return redirect(url_for("home"))
            return view(*args, **kwargs)
        return wrapped
    return decorator
