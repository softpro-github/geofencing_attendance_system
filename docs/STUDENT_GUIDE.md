# Student User Guide

## Smart Geofencing Attendance System

This guide explains how to register, log in, mark attendance, and view your attendance history as a student.

---

## Table of Contents

- [Creating an Account](#creating-an-account)
- [Logging In](#logging-in)
- [Your Dashboard](#your-dashboard)
- [Marking Attendance](#marking-attendance)
- [Viewing Your History](#viewing-your-history)
- [Exporting and Printing Records](#exporting-and-printing-records)
- [Troubleshooting](#troubleshooting)

---

## Creating an Account

1. Open the system in your browser and click the **Register** tab.
2. Fill in all fields:
   - **Full Name** — your real name as it should appear on records
   - **Matric Number** — e.g. `ICT/225230090`
   - **Department** — select from the list
   - **Academic Level** — 100 Level, 200 Level, 300 Level, or 400 Level
   - **Password** — at least 6 characters
3. Click **Create Account**.
4. You will be returned to the Login tab automatically.

> You only need to register once. Use the same matric number and password for every login.

---

## Logging In

1. On the login page, make sure the **Student** pill is selected (it is by default).
2. Enter your **Matric Number** and **Password**.
3. Click **Log In**.

You will be taken to your Student Dashboard. If you are already logged in and visit the login page, you will be redirected to your dashboard automatically.

---

## Your Dashboard

The dashboard has two summary cards at the top:

| Card | What it shows |
|---|---|
| Classes Attended | Total number of times you have marked attendance. Click it to see all records. |
| Active Courses | How many courses currently have an open attendance session for your department and level. Click it to scroll down to the course list. |

Below the cards you will find the **Available Courses** section showing all courses that are currently accepting attendance from your department and level.

---

## Marking Attendance

> **You must be physically present in or near the classroom.** The system uses your device's GPS to verify your location.

**Step-by-step:**

1. Make sure your browser has **location permission** enabled for this site. When prompted, click **Allow**.
2. Under **Available Courses**, tap or click the course you are marking attendance for. The card will highlight blue.
3. The system fetches the session's allowed location and begins checking your position. You will see one of three status messages:
   - **Yellow** — Getting your location, please wait
   - **Green** — You are within the allowed radius. You may submit.
   - **Red** — You are too far from the classroom. Move closer and try again.
4. If a **countdown timer** is shown (e.g. *Session closes in 14m 32s*), you must submit before it reaches zero.
5. When the status is green, click **Mark Attendance**.

The page will reload with a confirmation message.

### What can prevent attendance from being marked?

| Reason | What to do |
|---|---|
| "Too far from class" | Move physically closer to the classroom and reselect the course |
| "Session has expired" | The session timer ran out — contact your lecturer |
| "No active session" | The lecturer has not activated the session yet |
| "Already marked attendance" | You have already submitted for this session |
| "Marked from this device/IP" | Another student already used this device on the same network |
| Location permission denied | Go to your browser settings and allow location for this site |

---

## Viewing Your History

Click **History** in the top navigation bar, or click the **Classes Attended** stat card.

Your history page shows every class you have attended with:
- Course code
- Date and time
- Distance from the classroom when you marked attendance

### Filtering Records

Use the filter bar at the top of the table:

- **Course dropdown** — show only one course
- **Date dropdown** — show only a specific date (dates shown are only dates that have records)
- The date dropdown updates automatically when you change the course filter
- Click **Clear** to remove all filters

### Stat cards on the history page

Clicking either stat card (**Classes Attended** or **Courses**) clears all active filters and scrolls to the full record list.

---

## Exporting and Printing Records

Both options are in the filter bar on the History page.

### Print / Save as PDF

1. Apply any filters you want (e.g. one course, one date) — or leave them cleared for all records.
2. Click **Print / PDF**.
3. Your browser's print dialog will open. To save as PDF, choose **Save as PDF** as the destination/printer.

The printed version automatically hides the navigation bar, filter controls, and buttons — only the report content is printed.

### Export to CSV

1. Apply filters if needed.
2. Click **Export CSV**.
3. A `.csv` file will download. Open it in Excel, Google Sheets, or any spreadsheet application.

> The CSV always reflects whichever filters are active at the time you click the button.

---

## Troubleshooting

**My location is never captured / stays yellow**
- Make sure you are using a browser that supports Geolocation (Chrome, Firefox, Edge, Safari).
- Check that you allowed location access when the browser asked. If you accidentally denied it, go to your browser's site settings and reset the permission, then reload the page.
- On some devices, you may need to enable GPS / Location Services in your phone settings as well.

**I can see the course but the button stays disabled after the green status shows**
- The button enables as soon as the green status appears. If it does not, reload the page and try again.

**The course I need is not showing**
- Only courses matching your exact department and level are shown. If your registration details are wrong, contact the administrator.
- The lecturer may not have activated the session yet.

**I marked attendance but it does not appear in my history**
- History is loaded when you open the page. Reload the history page to see the latest record.
