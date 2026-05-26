# Lecturer User Guide

## Smart Geofencing Attendance System

This guide covers everything a lecturer needs to manage courses, run attendance sessions, view history, and export records.

---

## Table of Contents

- [Getting an Account](#getting-an-account)
- [Logging In](#logging-in)
- [Your Dashboard](#your-dashboard)
- [Managing Courses](#managing-courses)
- [Running an Attendance Session](#running-an-attendance-session)
- [Session Timers](#session-timers)
- [Viewing Session History](#viewing-session-history)
- [Exporting and Printing](#exporting-and-printing)
- [End of Semester — Deleting Courses](#end-of-semester--deleting-courses)

---

## Getting an Account

Lecturer accounts are created via the **Lecturer Register** page. Ask your system administrator for the registration link, or navigate directly to:

```
/lecturer_register
```

Fill in your desired username and password and submit. You can then log in immediately.

---

## Logging In

1. On the main login page, click the **Lecturer** pill.
2. Enter your **Username** and **Password**.
3. Click **Log In**.

If you are already logged in, visiting the login page will redirect you straight to the dashboard.

---

## Your Dashboard

The top of the dashboard shows three summary cards:

| Card | What it shows |
|---|---|
| Active Sessions | Number of courses you currently have open. Click to go to History. |
| Total Courses | How many courses you have registered. Click to jump to the Courses tab. |
| Attendance Records | Lifetime total attendance marks across all your courses. Click to go to History. |

Below the cards are three tabs:

| Tab | Purpose |
|---|---|
| Manage Courses | View all your courses, see session status, activate/deactivate, delete |
| Session Control | Alternative form-based interface for activating and deactivating sessions |
| Export Data | Export raw attendance records course by course |

---

## Managing Courses

### Adding a course

Click **Add Course** in the top navigation bar. Fill in the course code, department, and academic level, then submit.

> The department and level you set on a course determine which students will see it as an available course on their dashboards.

### Viewing your courses

The **Manage Courses** tab lists all your courses with:
- Course code
- Department and level
- Current session status (Active / No Active Session)
- A live countdown if a timed session is running

### Activating a session from a course card

1. Click anywhere on a course card. A modal dialog will open.
2. The modal shows the current status and captures your location automatically.
3. Set the **Allowed Radius** (metres) — students must be within this distance to mark attendance.
4. Set the **Session Duration** — choose a fixed time limit or "No limit" to close manually.
5. Click **Activate Session**.

The modal will close and the course badge will change to **Session Active**.

> If your location has not been captured yet (shown in yellow), wait a few seconds and try again. You must have location permission enabled in your browser.

### Deactivating a session

1. Click the course card that has an active session.
2. Click **Deactivate Session** in the modal.

Students will immediately lose access to mark attendance for that course.

### Restarting a session with new settings

If a session is already active and you want to change the radius or duration:

1. Click the course card.
2. Adjust the radius and duration fields.
3. Click **Restart Session**.

The old session is closed and a new one starts immediately.

---

## Running an Attendance Session

**Before activating:**
- Be physically in the classroom or at the exact location where students should be present.
- Ensure your browser has location permission enabled.
- Your device's GPS must be on.

**Typical workflow:**

1. Arrive at the classroom.
2. Open the lecturer dashboard on your phone or laptop.
3. Click the course card for the current class.
4. Set radius (50–100m is typical for a classroom) and duration if desired.
5. Click **Activate Session** — students can now mark attendance.
6. At the end of class, click the card again and click **Deactivate Session**, or let the timer run out.

**What students see:**
- The course appears in their Available Courses list.
- A countdown timer shows if you set a duration.
- They are shown whether they are within range before they can submit.

---

## Session Timers

When activating a session you can choose a duration:

| Option | Behaviour |
|---|---|
| No limit | Session stays open until you manually deactivate it |
| 15 / 30 / 45 min | Auto-closes after that time |
| 1 hour / 1.5 hours / 2 hours | Auto-closes after that time |

**Live countdown** is shown:
- On the course card in the Manage Courses tab (e.g. *28m 14s left*)
- In the session modal when you open a card
- On each student's dashboard after they select the course

When the timer reaches zero, the session is automatically marked inactive. Any student who has not yet submitted will see "Session has expired" and the Mark Attendance button will be disabled.

---

## Viewing Session History

Click **History** in the navigation bar, or click any stat card on the dashboard.

The history page shows every attendance session you have ever run, with a list of students who attended each one.

### Filtering

Use the filter bar at the top:
- **Course** — filter by a specific course
- **Date** — filter by date (only dates with sessions are shown; list updates when you change course)
- Click **Apply** to load filtered results
- Click **Clear** to remove filters

### Expanding a session

Click any session row to expand it and see the list of students who marked attendance, including:
- Matric number and name
- Time they marked attendance
- Distance from the classroom when they submitted

### Stat cards on the history page

All three stat cards (**Total Sessions**, **Total Attendance Records**, **Courses**) act as a "show all" button — clicking any of them clears the current filters.

---

## Exporting and Printing

### Print all sessions (current filter)

Click **Print / PDF** in the filter bar. Your browser's print dialog opens. Select **Save as PDF** to save a document.

When printing, all session panels are automatically expanded so every attendee is included in the output.

### Export all sessions to CSV

Click **Export CSV** in the filter bar. A `.csv` file downloads with the following columns:

| Column | Description |
|---|---|
| Course Code | The course identifier |
| Department | Target department |
| Level | Target academic level |
| Session Date | Date the session was started |
| Session Time | Time the session was started |
| Session Status | active or inactive |
| Student Matric | Student's matric number |
| Student Name | Student's full name |
| Time Marked | When they submitted attendance |
| Distance (m) | Distance from classroom at time of submission |

The export always reflects whatever course and date filters are active.

### Print a single session

On any session row, click the **Print** button (visible on the right side of the row, next to the expand arrow). This opens a dedicated printable report for that one session in a new tab. Click **Print / Save PDF** on that page.

---

## End of Semester — Deleting Courses

At the end of a semester you can delete courses you no longer need. This removes the course and all associated attendance sessions and records permanently.

**To delete a course:**

1. Go to the **Manage Courses** tab.
2. Find the course you want to remove.
3. Click the red **trash icon** button on the right side of the course card.
4. A confirmation dialog will appear warning you that all data will be deleted.
5. Click **Yes, delete it** to confirm.

> This action cannot be undone. Export any records you need to keep before deleting a course.

**Recommended end-of-semester process:**
1. Export the full course history to CSV (History page → Export CSV with that course selected).
2. Save the exported file somewhere safe.
3. Return to the dashboard and delete the course.
