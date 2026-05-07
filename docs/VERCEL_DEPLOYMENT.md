# Vercel Deployment Guide for LabScentique

## Overview

LabScentique can be deployed to Vercel with some important considerations about its PHP + SQLite stack.

## ⚠️ Important Limitations

### SQLite Database Issue
Vercel's serverless functions have **ephemeral storage** - each invocation gets a fresh filesystem. This means:
- **SQLite files will be lost** after each deployment or function invocation
- Database changes won't persist
- **SQLite is NOT recommended** for Vercel

### Recommended Solutions

#### Option 1: PostgreSQL (Recommended)
Use a managed PostgreSQL database with Vercel. This provides:
- ✅ Persistent data storage
- ✅ Best performance with Vercel
- ✅ Easy scalability
- ✅ Built-in backups

#### Option 2: MySQL/MariaDB
Compatible databases like:
- PlanetScale (MySQL)
- AWS RDS
- DigitalOcean Managed Database

#### Option 3: Alternative Hosting
If you prefer to keep SQLite:
- Railway.app
- Render.com
- Heroku alternatives
- Traditional VPS hosting

## Prerequisites

- Vercel account ([vercel.com](https://vercel.com))
- GitHub repository with LabScentique code
- For PostgreSQL option: Database service account

## Deployment Steps

### Step 1: Prepare Repository

```bash
# 1. Initialize git if not already done
git init
git add .
git commit -m "Initial commit: LabScentique for Vercel"

# 2. Push to GitHub
git remote add origin https://github.com/YOUR_USERNAME/labscentique.git
git branch -M main
git push -u origin main
```

### Step 2: Set Up Database (Choose One)

#### Option A: PostgreSQL Setup

1. **Create PostgreSQL Database**
   - Use PlanetScale, Supabase, Neon, or AWS RDS
   - Get connection string: `postgresql://user:pass@host:port/database`

2. **Create Database Tables**
   - Convert `database/init.sql` from SQLite to PostgreSQL syntax
   - Or use the migration script below

#### Option B: Keep SQLite (Development Only)

For local development or non-production:
- SQLite will work on first deployment
- Data will be reset on redeployments
- Not suitable for production

### Step 3: Configure Vercel Project

1. **Go to Vercel Dashboard**
   - Click "Add New..." → "Project"
   - Import your GitHub repository

2. **Set Environment Variables**
   - Go to Project Settings → Environment Variables
   - Add the following variables:

   ```
   DB_TYPE = postgres
   DATABASE_URL = postgresql://user:pass@host:port/database
   ```

   Or for SQLite (development only):
   ```
   DB_TYPE = sqlite
   DB_PATH = /tmp/labscentique.db
   ```

3. **Configure Framework Preset**
   - Select "Other" (not automatically detected)

4. **Build Command**: Leave empty or use `npm install`
5. **Output Directory**: Leave empty
6. **Install Command**: Leave empty

### Step 4: Deploy

```bash
# Option A: Deploy from Vercel Dashboard
# Click "Deploy" button

# Option B: Deploy from CLI
npm i -g vercel
vercel --prod
```

## Database Migration from SQLite to PostgreSQL

If migrating from SQLite to PostgreSQL, use this script:

```bash
# 1. Export data from SQLite
sqlite3 labscentique.db .dump > backup.sql

# 2. Convert SQLite SQL to PostgreSQL syntax
# Key changes needed:
# - AUTOINCREMENT → SERIAL
# - datetime('now') → NOW()
# - TEXT → VARCHAR (optional)

# 3. Import to PostgreSQL
psql -U user -d database -f converted.sql
```

Or use our migration script:
```bash
php src/migrate-to-postgres.php
```

## Update Configuration for Vercel

### config/config.php

The config file should be updated to support both database types:

```php
<?php
$dbType = $_ENV['DB_TYPE'] ?? 'sqlite';

if ($dbType === 'postgres') {
    $dsn = $_ENV['DATABASE_URL'];
} else {
    $dbPath = $_ENV['DB_PATH'] ?? __DIR__ . '/../labscentique.db';
    $dsn = 'sqlite:' . $dbPath;
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, '', '', $options);
?>
```

## URL Structure on Vercel

After deployment, your app will be accessible at:
- Main URL: `https://your-project.vercel.app`
- Public folder: `https://your-project.vercel.app/public`
- Home page: `https://your-project.vercel.app/public/index.php`

### Custom Domain

1. Go to Project Settings → Domains
2. Add your custom domain
3. Update DNS records as shown in Vercel

## Troubleshooting

### Error: Database Not Found

**Cause**: Environment variables not set
**Solution**: 
- Check Vercel Project Settings → Environment Variables
- Redeploy after adding variables: `vercel --prod`

### Error: PHP Files Not Executing

**Cause**: Routes not properly configured
**Solution**:
- Verify `vercel.json` is in root directory
- Check that `functions` section includes all PHP files

### Error: Connection Refused (PostgreSQL)

**Cause**: Database credentials incorrect
**Solution**:
- Verify `DATABASE_URL` format
- Test connection locally first
- Check database firewall rules allow Vercel IPs

### 502 Bad Gateway

**Cause**: PHP error or timeout
**Solution**:
- Check Vercel logs: `vercel logs`
- Verify all required files are deployed
- Check API response times

## Performance Optimization

### Cold Start Times
- PHP serverless functions have ~1-3 second cold start
- Optimize database queries
- Use connection pooling for PostgreSQL

### Caching
- Configure Redis if available through Vercel integrations
- Use HTTP caching headers

### Static Assets
- Serve images from CDN or Vercel's asset optimization
- Update image URLs in database

## Security Considerations

### Environment Variables
- Never commit `.env` files
- Use Vercel's environment variables for secrets
- Use different environments: Preview, Production

### Database Access
- Use strong passwords
- Restrict database access by IP (if possible)
- Use encrypted connections (SSL/TLS)

### PHP Configuration
- Disable PHP functions that might be restricted:
  ```php
  // Check: ini_get('disable_functions')
  ```

## Monitoring & Logs

### View Deployment Logs
```bash
vercel logs --follow
```

### Monitor Performance
- Check Vercel Analytics
- Monitor error rates
- Track API response times

## Scheduled Tasks

For tasks like database cleanup, use:
- Vercel Cron Jobs (premium)
- External cron services (EasyCron, cron-job.org)
- GitHub Actions

Example in `vercel.json`:
```json
"crons": [
  {
    "path": "/api/cron/cleanup.php",
    "schedule": "0 2 * * *"
  }
]
```

## Rollback & Versions

- All deployments are automatically saved
- Go to Vercel Dashboard → Deployments
- Click "Promote to Production" to rollback

## Costs

Vercel Pricing:
- **Hobby (Free)**: 100GB bandwidth/month, limited functions
- **Pro ($20/month)**: 1TB bandwidth/month
- **Enterprise**: Custom pricing

Database costs vary by provider (typically $20-100+/month for managed databases).

## Next Steps

1. ✅ Configure database (PostgreSQL recommended)
2. ✅ Set environment variables in Vercel
3. ✅ Push code to GitHub
4. ✅ Connect GitHub to Vercel
5. ✅ Deploy and test
6. ✅ Set custom domain (optional)
7. ✅ Monitor logs and performance

## Alternative: Docker Container

For more control, deploy as Docker container:
- Use services like Railway, Render, or Heroku
- Allows persistent filesystem for SQLite
- Better PHP customization

## Support

- Vercel Documentation: https://vercel.com/docs
- PHP Runtime: https://vercel.com/docs/serverless-functions/runtimes/php
- PostgreSQL Hosting: https://vercel.com/integrations/postgres

---

**Last Updated**: May 2026
