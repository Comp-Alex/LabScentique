# Vercel Deployment Quick Start

This guide helps you deploy LabScentique to Vercel with full production capability.

## ⚠️ Important: SQLite vs PostgreSQL

**Vercel's ephemeral filesystem deletes SQLite databases between deployments.**

### Recommended Database Options

| Database | Recommendation | Setup Time | Cost |
|----------|---|---|---|
| **PostgreSQL** (Neon) | ⭐ Best | 2 min | Free tier available |
| **PostgreSQL** (Supabase) | ⭐ Best | 2 min | Free tier available |
| **MySQL** (PlanetScale) | ✅ Good | 3 min | Free tier available |
| SQLite (local only) | ⚠️ Dev only | 1 min | N/A |

## 5-Minute Deployment Steps

### Step 1: Prepare Your Repository

```bash
# Initialize Git (if not already done)
git init
git add .
git commit -m "Initial commit: LabScentique with Vercel support"

# Push to GitHub
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/labscentique.git
git push -u origin main
```

### Step 2: Create Your Database

**Option A: PostgreSQL on Neon (Recommended)**
1. Go to https://neon.tech
2. Sign up with GitHub (1 click)
3. Create new project
4. Copy connection string: `postgresql://...`

**Option B: PostgreSQL on Supabase**
1. Go to https://supabase.com
2. Sign up with GitHub
3. Create new project
4. Copy connection string: `postgresql://...`

### Step 3: Deploy to Vercel

1. Go to https://vercel.com
2. Click "Add New" → "Project"
3. Import your GitHub repository
4. Click "Environment Variables" and add:
   - `DB_TYPE` = `postgres`
   - `DATABASE_URL` = (your PostgreSQL connection string)
5. Click "Deploy"

### Step 4: Verify Deployment

After deployment:
1. Visit your Vercel URL: `https://your-project.vercel.app/public/index.php`
2. Test registration/login
3. Test profile management

## Detailed Configuration

### Environment Variables in Vercel

In your Vercel project settings, add these variables:

```
DB_TYPE=postgres
DATABASE_URL=postgresql://user:password@host:port/database
SESSION_TIMEOUT=3600
SESSION_SECURE=true
APP_ENV=production
```

### Database Migration (First Time Only)

If your database doesn't have the schema:

```bash
php src/migrate-database-config.php generate-postgres-schema
```

Then execute the generated PostgreSQL schema in your database console.

## Local Testing Before Deploying

```bash
# 1. Create .env file locally
cat > .env << EOF
DB_TYPE=sqlite
DB_PATH=./labscentique.db
EOF

# 2. Initialize database
php src/init-db.php

# 3. Start server
php -S localhost:8000

# 4. Test at http://localhost:8000/public/index.php
```

## Troubleshooting

### "Failed to connect to database"
- ✅ Verify `DATABASE_URL` is set in Vercel environment variables
- ✅ Test connection string in database client (DBeaver, pgAdmin)
- ✅ Check database username/password is correct

### "User profile not loading"
- ✅ Ensure `users` table has `full_name`, `bio`, `profile_picture_url` columns
- ✅ Run migration script: `php src/migrate-database-config.php generate-postgres-schema`

### "Login page shows, but can't register"
- ✅ Check Vercel function logs: Project → "Deployments" → "Functions"
- ✅ Verify `api/user-profile.php` and `config/config.php` deployed correctly

### "Session not persisting"
- ✅ This is normal on Vercel (stateless serverless)
- ✅ Use database-backed sessions: See [Advanced Session Management](#advanced)

## Next Steps

After successful deployment:

1. **Configure custom domain** (optional)
   - In Vercel: Project → Settings → Domains
   - Add your custom domain

2. **Enable automatic deployments**
   - Already enabled! Push to `main` branch to auto-deploy

3. **Monitor performance**
   - Vercel Analytics: Project → Analytics
   - Check function execution times, cold starts

4. **Set up CI/CD**
   - Tests run automatically on every push
   - Deployments to preview environments for PRs

## Database Considerations

### Persistent Storage on Vercel

All databases listed below persist data between Vercel deployments:
- PostgreSQL (Neon, Supabase, AWS RDS)
- MySQL (PlanetScale)
- MongoDB (Atlas)

### Cost Estimates

- **Neon PostgreSQL**: Free tier includes 3GB storage, 3 projects
- **Supabase PostgreSQL**: Free tier includes 500MB storage
- **PlanetScale MySQL**: Free tier includes unlimited databases (1 read replica)
- **Vercel**: Free tier includes 100 function executions/day

For hobby projects, free tiers are sufficient.

## Support & Documentation

- Vercel Docs: https://vercel.com/docs
- PHP on Vercel: https://vercel.com/docs/functions/serverless-functions/runtimes/php
- Neon Postgres: https://neon.tech/docs
- Supabase: https://supabase.com/docs

---

**Your app is now production-ready! 🎉**
