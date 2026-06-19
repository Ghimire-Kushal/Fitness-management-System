"""
app.py  -  Main Flask application (all routes)
Owner: Person 2 (Backend)

Run it:
    cd backend
    python app.py
Then open http://127.0.0.1:5000

Routes are grouped: public/auth, member, admin.
Each route renders a template from frontend/templates and passes the
named variables listed above it (that list is the contract with the
frontend person).
"""
import os
import sys
from datetime import date, timedelta

from flask import (Flask, render_template, request, redirect,
                   url_for, flash, session)
from werkzeug.security import generate_password_hash, check_password_hash

# --- make sibling folders importable -------------------------------------
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, os.path.join(BASE_DIR, "database"))
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import db                       # database/db.py
import config                   # backend/config.py
from auth import (current_user, login_user, logout_user,
                  login_required, role_required)

# --- tell Flask where the frontend lives ---------------------------------
app = Flask(
    __name__,
    template_folder=os.path.join(BASE_DIR, "frontend", "templates"),
    static_folder=os.path.join(BASE_DIR, "frontend", "static"),
)
app.secret_key = config.SECRET_KEY


# Make the current user available inside every template as `user`.
@app.context_processor
def inject_user():
    return {"user": current_user()}


# =========================================================================
#  PUBLIC / AUTHENTICATION
# =========================================================================

@app.route("/")
def home():
    # template: index.html
    return render_template("index.html")


@app.route("/register", methods=["GET", "POST"])
def register():
    # template: register.html
    if request.method == "POST":
        full_name = request.form.get("full_name", "").strip()
        email = request.form.get("email", "").strip().lower()
        phone = request.form.get("phone", "").strip()
        password = request.form.get("password", "")

        if not full_name or not email or not password:
            flash("Name, email and password are required.", "error")
            return redirect(url_for("register"))

        existing = db.query("SELECT user_id FROM users WHERE email=%s",
                            (email,), fetchone=True)
        if existing:
            flash("That email is already registered. Try logging in.", "error")
            return redirect(url_for("register"))

        db.query(
            """INSERT INTO users (full_name, email, password, phone, role_id)
               VALUES (%s, %s, %s, %s,
                       (SELECT role_id FROM roles WHERE role_name='Member'))""",
            (full_name, email, generate_password_hash(password), phone),
            commit=True,
        )
        flash("Account created. Please log in.", "success")
        return redirect(url_for("login"))

    return render_template("register.html")


@app.route("/login", methods=["GET", "POST"])
def login():
    # template: login.html
    if request.method == "POST":
        email = request.form.get("email", "").strip().lower()
        password = request.form.get("password", "")

        row = db.query(
            """SELECT u.*, r.role_name
               FROM users u JOIN roles r ON u.role_id = r.role_id
               WHERE u.email=%s""",
            (email,), fetchone=True,
        )
        if row and check_password_hash(row["password"], password):
            login_user(row)
            if row["role_name"] == "Admin":
                return redirect(url_for("admin_dashboard"))
            return redirect(url_for("member_dashboard"))

        flash("Email or password is incorrect.", "error")
        return redirect(url_for("login"))

    return render_template("login.html")


@app.route("/logout")
def logout():
    logout_user()
    flash("You have been logged out.", "success")
    return redirect(url_for("login"))


# =========================================================================
#  MEMBER
# =========================================================================

@app.route("/member/dashboard")
@role_required("Member")
def member_dashboard():
    # template: member/dashboard.html
    # vars: membership, bookings, trainers, workouts
    uid = session["user_id"]

    membership = db.query(
        """SELECT m.*, p.plan_name, p.duration_type, p.price
           FROM memberships m JOIN membership_plans p ON m.plan_id = p.plan_id
           WHERE m.user_id=%s AND m.status='active'
           ORDER BY m.start_date DESC LIMIT 1""",
        (uid,), fetchone=True,
    )
    bookings = db.query(
        """SELECT b.*, s.slot_date, s.start_time, s.end_time,
                  tu.full_name AS trainer_name
           FROM bookings b
           JOIN time_slots s ON b.slot_id = s.slot_id
           LEFT JOIN trainer_profiles tp ON b.trainer_id = tp.trainer_id
           LEFT JOIN users tu ON tp.user_id = tu.user_id
           WHERE b.member_id=%s
           ORDER BY s.slot_date, s.start_time""",
        (uid,),
    )
    trainers = db.query(
        """SELECT tp.trainer_id, tu.full_name, tp.specialization
           FROM member_trainers mt
           JOIN trainer_profiles tp ON mt.trainer_id = tp.trainer_id
           JOIN users tu ON tp.user_id = tu.user_id
           WHERE mt.member_id=%s""",
        (uid,),
    )
    workouts = db.query(
        "SELECT plan_id, title FROM workout_plans WHERE member_id=%s",
        (uid,),
    )
    return render_template("member/dashboard.html", membership=membership,
                           bookings=bookings, trainers=trainers,
                           workouts=workouts)


@app.route("/member/membership", methods=["GET", "POST"])
@role_required("Member")
def member_membership():
    # template: member/membership.html
    # vars: plans, current
    uid = session["user_id"]

    if request.method == "POST":
        plan_id = request.form.get("plan_id")
        plan = db.query("SELECT * FROM membership_plans WHERE plan_id=%s",
                        (plan_id,), fetchone=True)
        if not plan:
            flash("Please choose a valid plan.", "error")
            return redirect(url_for("member_membership"))

        days = 365 if plan["duration_type"] == "yearly" else 30
        start = date.today()
        end = start + timedelta(days=days)

        # keep only one active membership at a time
        db.query("UPDATE memberships SET status='expired' "
                 "WHERE user_id=%s AND status='active'", (uid,), commit=True)
        db.query(
            """INSERT INTO memberships (user_id, plan_id, start_date, end_date)
               VALUES (%s, %s, %s, %s)""",
            (uid, plan_id, start, end), commit=True,
        )
        flash(f"You are now on the {plan['plan_name']} plan.", "success")
        return redirect(url_for("member_dashboard"))

    plans = db.query("SELECT * FROM membership_plans ORDER BY price")
    current = db.query(
        """SELECT m.*, p.plan_name FROM memberships m
           JOIN membership_plans p ON m.plan_id = p.plan_id
           WHERE m.user_id=%s AND m.status='active' LIMIT 1""",
        (uid,), fetchone=True,
    )
    return render_template("member/membership.html", plans=plans, current=current)


@app.route("/member/booking", methods=["GET", "POST"])
@role_required("Member")
def member_booking():
    # template: member/booking.html
    # vars: slots, trainers, bookings
    uid = session["user_id"]

    if request.method == "POST":
        slot_id = request.form.get("slot_id")
        booking_type = request.form.get("booking_type")   # gym_session / appointment
        trainer_id = request.form.get("trainer_id") or None

        slot = db.query("SELECT * FROM time_slots WHERE slot_id=%s",
                        (slot_id,), fetchone=True)
        if not slot or booking_type not in ("gym_session", "appointment"):
            flash("Please choose a valid slot and booking type.", "error")
            return redirect(url_for("member_booking"))

        # already booked this slot for this type?
        dup = db.query(
            """SELECT booking_id FROM bookings
               WHERE member_id=%s AND slot_id=%s AND booking_type=%s""",
            (uid, slot_id, booking_type), fetchone=True,
        )
        if dup:
            flash("You have already booked that slot.", "error")
            return redirect(url_for("member_booking"))

        # slot capacity check
        taken = db.query("SELECT COUNT(*) AS c FROM bookings WHERE slot_id=%s",
                         (slot_id,), fetchone=True)["c"]
        if taken >= slot["capacity"]:
            flash("That slot is full. Please pick another time.", "error")
            return redirect(url_for("member_booking"))

        if booking_type == "appointment":
            # trainer must be one assigned to this member
            assigned = db.query(
                "SELECT 1 FROM member_trainers WHERE member_id=%s AND trainer_id=%s",
                (uid, trainer_id), fetchone=True,
            )
            if not assigned:
                flash("You can only book a trainer assigned to you by the admin.",
                      "error")
                return redirect(url_for("member_booking"))
            # trainer not already booked in this slot
            busy = db.query(
                """SELECT COUNT(*) AS c FROM bookings
                   WHERE slot_id=%s AND trainer_id=%s AND booking_type='appointment'""",
                (slot_id, trainer_id), fetchone=True,
            )["c"]
            if busy >= 1:
                flash("That trainer is already booked for this slot.", "error")
                return redirect(url_for("member_booking"))
        else:
            trainer_id = None

        db.query(
            """INSERT INTO bookings (member_id, slot_id, trainer_id, booking_type)
               VALUES (%s, %s, %s, %s)""",
            (uid, slot_id, trainer_id, booking_type), commit=True,
        )
        flash("Booking requested. It is now pending approval.", "success")
        return redirect(url_for("member_booking"))

    slots = db.query(
        """SELECT s.*, (s.capacity - COUNT(b.booking_id)) AS remaining
           FROM time_slots s
           LEFT JOIN bookings b ON b.slot_id = s.slot_id
           WHERE s.slot_date >= CURDATE()
           GROUP BY s.slot_id
           ORDER BY s.slot_date, s.start_time""",
    )
    trainers = db.query(
        """SELECT tp.trainer_id, tu.full_name, tp.specialization
           FROM member_trainers mt
           JOIN trainer_profiles tp ON mt.trainer_id = tp.trainer_id
           JOIN users tu ON tp.user_id = tu.user_id
           WHERE mt.member_id=%s""",
        (uid,),
    )
    bookings = db.query(
        """SELECT b.*, s.slot_date, s.start_time, s.end_time,
                  tu.full_name AS trainer_name
           FROM bookings b
           JOIN time_slots s ON b.slot_id = s.slot_id
           LEFT JOIN trainer_profiles tp ON b.trainer_id = tp.trainer_id
           LEFT JOIN users tu ON tp.user_id = tu.user_id
           WHERE b.member_id=%s
           ORDER BY s.slot_date, s.start_time""",
        (uid,),
    )
    return render_template("member/booking.html", slots=slots,
                           trainers=trainers, bookings=bookings)


@app.route("/member/workout")
@role_required("Member")
def member_workout():
    # template: member/workout.html
    # vars: workouts
    uid = session["user_id"]
    workouts = db.query(
        """SELECT w.*, tu.full_name AS trainer_name
           FROM workout_plans w
           LEFT JOIN trainer_profiles tp ON w.trainer_id = tp.trainer_id
           LEFT JOIN users tu ON tp.user_id = tu.user_id
           WHERE w.member_id=%s
           ORDER BY w.created_at DESC""",
        (uid,),
    )
    return render_template("member/workout.html", workouts=workouts)


@app.route("/member/profile", methods=["GET", "POST"])
@role_required("Member")
def member_profile():
    # template: member/profile.html
    # vars: profile
    uid = session["user_id"]

    if request.method == "POST":
        full_name = request.form.get("full_name", "").strip()
        phone = request.form.get("phone", "").strip()
        password = request.form.get("password", "")

        if not full_name:
            flash("Name cannot be empty.", "error")
            return redirect(url_for("member_profile"))

        db.query("UPDATE users SET full_name=%s, phone=%s WHERE user_id=%s",
                 (full_name, phone, uid), commit=True)
        if password:
            db.query("UPDATE users SET password=%s WHERE user_id=%s",
                     (generate_password_hash(password), uid), commit=True)
        session["full_name"] = full_name
        flash("Profile updated.", "success")
        return redirect(url_for("member_profile"))

    profile = db.query("SELECT * FROM users WHERE user_id=%s", (uid,), fetchone=True)
    return render_template("member/profile.html", profile=profile)


# =========================================================================
#  ADMIN
# =========================================================================

@app.route("/admin/dashboard")
@role_required("Admin")
def admin_dashboard():
    # template: admin/dashboard.html
    # vars: stats, recent
    def count(sql, params=None):
        return db.query(sql, params, fetchone=True)["c"]

    stats = {
        "members": count("SELECT COUNT(*) AS c FROM users u JOIN roles r "
                         "ON u.role_id=r.role_id WHERE r.role_name='Member'"),
        "trainers": count("SELECT COUNT(*) AS c FROM trainer_profiles"),
        "bookings": count("SELECT COUNT(*) AS c FROM bookings"),
        "pending": count("SELECT COUNT(*) AS c FROM bookings WHERE status='pending'"),
        "active_memberships": count("SELECT COUNT(*) AS c FROM memberships "
                                    "WHERE status='active'"),
    }
    recent = db.query(
        """SELECT b.booking_id, b.status, b.booking_type,
                  mu.full_name AS member_name,
                  s.slot_date, s.start_time
           FROM bookings b
           JOIN users mu ON b.member_id = mu.user_id
           JOIN time_slots s ON b.slot_id = s.slot_id
           ORDER BY b.created_at DESC LIMIT 5""",
    )
    return render_template("admin/dashboard.html", stats=stats, recent=recent)


@app.route("/admin/users")
@role_required("Admin")
def admin_users():
    # template: admin/users.html
    # vars: users
    users = db.query(
        """SELECT u.user_id, u.full_name, u.email, u.phone,
                  r.role_name, u.created_at
           FROM users u JOIN roles r ON u.role_id = r.role_id
           ORDER BY r.role_name, u.full_name""",
    )
    return render_template("admin/users.html", users=users)


@app.route("/admin/bookings", methods=["GET", "POST"])
@role_required("Admin")
def admin_bookings():
    # template: admin/bookings.html
    # vars: bookings
    if request.method == "POST":
        booking_id = request.form.get("booking_id")
        new_status = request.form.get("status")
        if new_status in ("pending", "approved", "completed"):
            db.query("UPDATE bookings SET status=%s WHERE booking_id=%s",
                     (new_status, booking_id), commit=True)
            flash("Booking status updated.", "success")
        return redirect(url_for("admin_bookings"))

    bookings = db.query(
        """SELECT b.*, mu.full_name AS member_name,
                  tu.full_name AS trainer_name,
                  s.slot_date, s.start_time, s.end_time
           FROM bookings b
           JOIN users mu ON b.member_id = mu.user_id
           JOIN time_slots s ON b.slot_id = s.slot_id
           LEFT JOIN trainer_profiles tp ON b.trainer_id = tp.trainer_id
           LEFT JOIN users tu ON tp.user_id = tu.user_id
           ORDER BY s.slot_date, s.start_time""",
    )
    return render_template("admin/bookings.html", bookings=bookings)


@app.route("/admin/trainers", methods=["GET", "POST"])
@role_required("Admin")
def admin_trainers():
    # template: admin/trainers.html
    # vars: trainers
    if request.method == "POST":
        full_name = request.form.get("full_name", "").strip()
        email = request.form.get("email", "").strip().lower()
        password = request.form.get("password", "")
        specialization = request.form.get("specialization", "").strip()
        bio = request.form.get("bio", "").strip()

        if not full_name or not email or not password:
            flash("Name, email and password are required.", "error")
            return redirect(url_for("admin_trainers"))
        if db.query("SELECT user_id FROM users WHERE email=%s", (email,),
                    fetchone=True):
            flash("That email is already in use.", "error")
            return redirect(url_for("admin_trainers"))

        new_id = db.query(
            """INSERT INTO users (full_name, email, password, role_id)
               VALUES (%s, %s, %s,
                       (SELECT role_id FROM roles WHERE role_name='Trainer'))""",
            (full_name, email, generate_password_hash(password)), commit=True,
        )
        db.query(
            """INSERT INTO trainer_profiles (user_id, specialization, bio)
               VALUES (%s, %s, %s)""",
            (new_id, specialization, bio), commit=True,
        )
        flash("Trainer added.", "success")
        return redirect(url_for("admin_trainers"))

    trainers = db.query(
        """SELECT tp.trainer_id, tu.full_name, tu.email,
                  tp.specialization, tp.bio
           FROM trainer_profiles tp
           JOIN users tu ON tp.user_id = tu.user_id
           ORDER BY tu.full_name""",
    )
    return render_template("admin/trainers.html", trainers=trainers)


@app.route("/admin/assign", methods=["GET", "POST"])
@role_required("Admin")
def admin_assign():
    # template: admin/assign.html
    # vars: members, trainers, assignments
    if request.method == "POST":
        member_id = request.form.get("member_id")
        trainer_id = request.form.get("trainer_id")
        exists = db.query(
            "SELECT 1 FROM member_trainers WHERE member_id=%s AND trainer_id=%s",
            (member_id, trainer_id), fetchone=True,
        )
        if exists:
            flash("That trainer is already assigned to that member.", "error")
        else:
            db.query(
                "INSERT INTO member_trainers (member_id, trainer_id) VALUES (%s, %s)",
                (member_id, trainer_id), commit=True,
            )
            flash("Trainer assigned to member.", "success")
        return redirect(url_for("admin_assign"))

    members = db.query(
        """SELECT u.user_id, u.full_name FROM users u
           JOIN roles r ON u.role_id = r.role_id
           WHERE r.role_name='Member' ORDER BY u.full_name""",
    )
    trainers = db.query(
        """SELECT tp.trainer_id, tu.full_name, tp.specialization
           FROM trainer_profiles tp JOIN users tu ON tp.user_id = tu.user_id
           ORDER BY tu.full_name""",
    )
    assignments = db.query(
        """SELECT mt.id, mu.full_name AS member_name,
                  tu.full_name AS trainer_name
           FROM member_trainers mt
           JOIN users mu ON mt.member_id = mu.user_id
           JOIN trainer_profiles tp ON mt.trainer_id = tp.trainer_id
           JOIN users tu ON tp.user_id = tu.user_id
           ORDER BY mu.full_name""",
    )
    return render_template("admin/assign.html", members=members,
                           trainers=trainers, assignments=assignments)


@app.route("/admin/workouts", methods=["GET", "POST"])
@role_required("Admin")
def admin_workouts():
    # template: admin/workouts.html
    # vars: members, trainers, plans
    if request.method == "POST":
        member_id = request.form.get("member_id")
        trainer_id = request.form.get("trainer_id") or None
        title = request.form.get("title", "").strip()
        description = request.form.get("description", "").strip()

        if not member_id or not title or not description:
            flash("Member, title and description are required.", "error")
            return redirect(url_for("admin_workouts"))

        db.query(
            """INSERT INTO workout_plans (member_id, trainer_id, title, description)
               VALUES (%s, %s, %s, %s)""",
            (member_id, trainer_id, title, description), commit=True,
        )
        flash("Workout plan assigned to member.", "success")
        return redirect(url_for("admin_workouts"))

    members = db.query(
        """SELECT u.user_id, u.full_name FROM users u
           JOIN roles r ON u.role_id = r.role_id
           WHERE r.role_name='Member' ORDER BY u.full_name""",
    )
    trainers = db.query(
        """SELECT tp.trainer_id, tu.full_name FROM trainer_profiles tp
           JOIN users tu ON tp.user_id = tu.user_id ORDER BY tu.full_name""",
    )
    plans = db.query(
        """SELECT w.*, mu.full_name AS member_name, tu.full_name AS trainer_name
           FROM workout_plans w
           JOIN users mu ON w.member_id = mu.user_id
           LEFT JOIN trainer_profiles tp ON w.trainer_id = tp.trainer_id
           LEFT JOIN users tu ON tp.user_id = tu.user_id
           ORDER BY w.created_at DESC""",
    )
    return render_template("admin/workouts.html", members=members,
                           trainers=trainers, plans=plans)


if __name__ == "__main__":
    app.run(debug=True)
