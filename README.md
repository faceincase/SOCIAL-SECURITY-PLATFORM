# Social Input Platform — Report Management App

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb3?logo=php)](https://www.php.net/)
[![SQLite](https://img.shields.io/badge/DB-SQLite3-003b57?logo=sqlite)](https://sqlite.org/index.html)
[![Tailwind CSS](https://img.shields.io/badge/UI-TailwindCSS-38bdf8?logo=tailwindcss)](https://tailwindcss.com/)
[![Status](https://img.shields.io/badge/Status-Demo-green)](#-security--disclaimer)

Concise, student-led web app to report community issues (potholes, lighting, waste, etc.), track them in an admin dashboard, and share updates via a lightweight blog. Built with plain PHP + SQLite and Tailwind CSS.

## Highlights

- Secure-ish flows: registration/login with hashed passwords and role flags
- Issue reporting with categories/subcategories, optional image, UK postcode check
- Admin dashboard: list, moderate, export JSON, map + network visualisations
- Community blog: create posts (logged-in users), admin delete
- Lightweight includes, no framework, quick to deploy (XAMPP/WAMP or PHP CLI)

## Tech Stack 

- Backend: PHP 8.x (PDO, sessions)
- Database: SQLite (single file under DATABASE/)
- Frontend: Tailwind CSS, Lucide icons
- Maps/Charts: Leaflet, D3, postcodes.io lookup

## Project Structure

```
.
├── index.php            # Landing page, overview, disclaimer modal
├── report.php           # Submit report (image upload, postcode validation)
├── issues.php           # Admin dashboard (visualisations, exports, moderation)
├── blog.php             # Community feed (DB + demo posts)
├── new_post.php         # Create blog post (requires login)
├── login.php            # Register/Login (user + admin flag)
├── login_admin.php      # 2-step admin verification (demo)
├── verify.php           # JS verification helper
├── debug.php            # Admin-only debug/metrics
├── documentation.php    # In-app docs
├── ASSETS/
│   ├── header.php
│   └── footer.php
├── DATABASE/
│   └── reports.sqlite   # Created/updated at runtime
├── IMAGES/
└── REPORT_IMAGES/       # Uploaded report images
```

## Quick Start

1) Requirements
- PHP 8.0+ with SQLite extension enabled
- Write permissions on `DATABASE/` and `REPORT_IMAGES/`

2) Run locally (quickest)

```bash
# From the project folder
php -S localhost:8000
```

Open http://localhost:8000

Alternative: drop the folder into XAMPP/WAMP `htdocs` and visit via http://localhost/…

## Key Pages

| Page | Purpose |
|------|---------|
| index.php | Landing, navigation, project overview |
| report.php | Submit issue (postcode, category/subcategory, optional image) |
| issues.php | Admin dashboard: list, moderation, map, stats, JSON export |
| blog.php | Community feed (DB-backed + demo content) |
| new_post.php | Create post (requires login) |
| login.php | Register/login (password hashing) |
| login_admin.php | Demo 2-step verification for admins |

## Notes for Admin/Testing

- Register via `login.php`. To grant admin, set `users.type = 'admin'` in the SQLite DB (demo).
- Access the dashboard at `issues.php` (admin only). Exports are JSON downloads.

## Security & Disclaimer

This is a coursework/demo system. Do not use real personal data. Hardening (CSRF, stricter authz, secrets management, content security) should precede any production use.

---

Made with care for a university group project. 🚀
