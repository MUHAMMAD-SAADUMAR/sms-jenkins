# Student Management System

A PHP + MySQL web application built for CSC418 (DevOps for Cloud Computing) Assignment 1.
It is deployed twice: on EC2 (IaaS) and on Elastic Beanstalk + RDS (PaaS).

## Modules

| Module | Features |
|---|---|
| 1. Users & authentication | Student registration (with validation), login/logout, roles (admin/student), profile, change password, admin activates/deactivates students, student search |
| 2. Course management | List and search courses; admin adds, edits and deletes courses (code format, credit hours, capacity rules) |
| 3. Enrollments & grades | Students enroll in or drop courses (capacity limit, 18 credit-hour limit, no dropping after grading); admin views students per course, assigns grades, removes students; student transcript with CGPA |

Also: a dashboard per role, and `health.php`, which reports database connectivity as JSON.

## Default login

| Role | Email | Password |
|---|---|---|
| Admin | `admin@sms.local` | `Admin@123` |

Students create their own accounts on the Register page.

## Configuration

Database settings are read from environment variables, so the same code runs everywhere:

| Variable | Example |
|---|---|
| `DB_HOST` | `127.0.0.1` or the RDS endpoint |
| `DB_PORT` | `3306` |
| `DB_NAME` | `sms` |
| `DB_USER` | `sms_app` |
| `DB_PASS` | the app user's password |
| `APP_ENV` | `ec2` or `beanstalk` (shown in the footer) |

- EC2: set with `SetEnv` in an Apache config file.
- Elastic Beanstalk: set as environment properties.

## Files

```
config.php            settings, DB connection, session, helpers
index.php             redirects to dashboard or login
login.php register.php logout.php profile.php users.php      Module 1
courses.php course_form.php course_delete.php                Module 2
enroll.php my_courses.php enrollments.php                    Module 3
dashboard.php health.php
includes/             page header and footer
assets/style.css
database/schema.sql   tables + starting data (not deployed to the web root)
```
Webhook test: Thu Oct 29 12:16:08 UTC 2026
Webhook test: Thu Oct 29 12:16:13 UTC 2026
Webhook test: Thu Oct 29 12:16:28 UTC 2026
