# LabScentique

This workspace contains a PHP-rendered website with SQL database for LabScentique.

## Project Structure

```
├── public/                 # Web-accessible files
│   ├── index.php          # Home page and contact form handler
│   ├── dashboard.php      # User dashboard
│   ├── accreditation.php  # ISO 17025 accreditation information
│   ├── styles.css         # Page styling
│   └── script.js          # Navigation toggle script
├── src/                   # Application scripts
│   ├── init-db.php        # Database initialization script
│   └── test-db.php        # Database testing script
├── config/                # Configuration files
│   └── config.php         # PDO database connection configuration
├── database/              # Database schema
│   └── init.sql           # SQLite schema and seed script
├── api/                   # API endpoints
│   └── contact.php        # Contact form API handler
├── docs/                  # Documentation
│   ├── README.md          # This file
│   └── TODO.md            # Development tasks
└── assets/                # Static assets (images, etc.)
```

## Included files

- `public/index.php` - PHP-rendered home page and contact form handler
- `public/dashboard.php` - User dashboard for managing purchases
- `public/accreditation.php` - Accreditation page with ISO 21500 project management information
- `public/styles.css` - Page styling
- `public/script.js` - Navigation toggle script
- `config/config.php` - PDO database connection configuration
- `database/init.sql` - SQLite schema and seed script for `labscentique.db`
- `api/contact.php` - Contact form API endpoint

## Run locally

1. Start the PHP-rendered frontend from the project root:
   ```bash
   php -S localhost:8000
   ```
2. Open the app in your browser at `http://localhost:8000`.

## Database setup

1. Run the SQLite initialization script:
   ```bash
   php src/init-db.php
   ```
2. The command creates or recreates `labscentique.db` in the project root.
3. If you need to change the database file path or connection settings, update `config/config.php`.

## Notes

- The contact form submits directly to `index.php` and stores messages in the SQL database.
- You can still use `backend/api/health.php` and `backend/api/contact.php` as optional API endpoints.
