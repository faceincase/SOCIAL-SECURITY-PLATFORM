Report App — A simple PHP web application for submitting and viewing incident reports with image uploads and a database. Built as a group project.

PHP files:
- index.php — Landing page; introduces the system and links to submit a report and view existing reports.
- report.php — Report submission form; validates input, handles image uploads, and saves reports to the SQLite database (`DATABASE/reports.sqlite`) and `REPORT_IMAGES/`.
- issues.php — Lists all submitted reports; displays a table, statistics and an interactive map (uses postcodes to geocode locations).


LOGIN HAS TWO TYPES:
- ADMIN
- USER


NO LOGIN REQUIRED:
- index.php
- report.php

LOGIN REQUIRED:
- issues.php - ADMIN ACCESS
- blog.php - USER ACCESS



----- Normal accounts:

- USERNAME: adam
- PASSWORD: adam

- USERNAME: test
- PASSWORD: test

- USERNAME: isla
- PASSWORD: 12345


----- Admin accounts:

- USERNAME: MrAdmin
- PASSWORD: 321

- USERNAME: bob
- PASSWORD: bob

- USERNAME: alice
- PASSWORD: alice

