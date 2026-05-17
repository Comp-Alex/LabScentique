# LabScentique

A modern PHP-based perfume discovery and inventory management platform with SQLite database.

## Project Structure

```
├── public/                 # Web-accessible files
│   ├── index.php          # Home page, login/register, contact form
│   ├── dashboard.php      # Staff/Owner inventory dashboard
│   ├── accreditation.php  # ISO 17025 accreditation information
│   ├── styles.css         # Complete page styling (responsive design)
│   ├── script.js          # Navigation, modals, user profile management
│   ├── app.js             # Perfume rendering and API interactions
│   ├── validate.js        # Client-side form validation
│   ├── dashboard.js       # Dashboard functionality
│   └── assets/            # Images, logos, and static files
├── api/                   # API endpoints (JSON responses)
│   ├── data.php          # Public perfume data and inventory info
│   ├── contact.php       # Contact form submission handler
│   ├── dashboard.php     # Authenticated staff/owner operations
│   ├── user-profile.php  # User profile management (NEW)
│   └── health.php        # System health check
├── src/                  # Application setup scripts
│   ├── init-db.php       # Database initialization
│   └── test-*.php        # Database testing utilities
├── config/               # Configuration files
│   └── config.php        # PDO SQLite connection
├── database/             # Database schema
│   └── init.sql          # SQLite schema with all tables
├── docs/                 # Documentation
│   ├── README.md         # This file
│   └── TODO.md           # Development tasks
└── assets/               # Static assets (images, fonts, etc.)
```

## Features

### For Users
- **Perfume Discovery** - Browse curated collection of luxury fragrances
- **User Accounts** - Register, login, and manage profile
- **User Profile** - Set full name, bio, and profile picture
- **Purchase System** - Create purchase lists for favorite perfumes
- **Responsive Design** - Works on desktop, tablet, and mobile devices

### For Staff & Owners
- **Dashboard** - Manage perfume inventory and stock levels
- **Purchase Management** - Review and approve purchase requests
- **Inventory Tracking** - Monitor available, damaged, and expired stock
- **Access Control** - Granular permissions for staff members
- **Audit Logging** - Track all inventory changes for compliance

### User Authentication & Security
- **Secure Registration** - Password hashing with bcrypt
- **Session Management** - Server-side session handling
- **Account Security** - Password change functionality
- **Role-Based Access** - Different permissions for registered, staff, and owner roles

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- SQLite3 (usually included with PHP)
- Web browser with JavaScript enabled

### Step 1: Initialize Database
```bash
php src/init-db.php
```
This creates a fresh SQLite database with all required tables and initial data.

### Step 2: Start Development Server
```bash
php -S localhost:8000
```

### Step 3: Access the Application
Open your browser and navigate to: `http://localhost:8000`

## Database Schema

The SQLite database includes the following tables:

- **users** - User accounts with profile information
  - id, username, email, password_hash, full_name, bio, profile_picture_url, role, created_at, updated_at
  
- **perfumes** - Fragrance catalog
  - id, name, description, image_url, top_notes, heart_notes, base_notes, accords, rating, created_at
  
- **inventory** - Stock tracking
  - id, perfume_id, available_quantity, damaged_quantity, expiration_date, last_updated
  
- **purchase_lists** - Purchase requests from staff
  - id, staff_id, status, owner_note, created_at, approved_at
  
- **purchase_list_items** - Items in purchase requests
  - id, purchase_list_id, perfume_id, quantity
  
- **contacts** - Contact form submissions
  - id, name, email, message, created_at
  
- **inventory_access** - Staff permissions for inventory items
  - id, staff_id, inventory_id, access_level, granted_by, granted_at
  
- **inventory_audit** - Audit log of inventory changes
  - id, inventory_id, changed_by, change_type, old_value, new_value, timestamp

## API Endpoints

### Public Endpoints (No Authentication Required)

#### GET /api/data.php?action=perfumes
Retrieve list of all perfumes with optional search.
```
Parameters: search (optional)
Response: { success: true, data: [...] }
```

#### GET /api/data.php?action=perfume_inventory
Get stock level for a specific perfume.
```
Parameters: perfume_id (required)
Response: { success: true, stock: number }
```

#### GET /api/health.php
Check if API is running.
```
Response: { status: 'ok', message: 'PHP backend is running' }
```

#### POST /api/contact.php
Submit contact form message.
```
Body: { name, email, message }
Response: { message: 'Your message was received successfully', data: {...} }
```

### Authenticated Endpoints (Requires Login)

#### GET /api/user-profile.php?action=get
Get current user's profile information.
```
Response: { success: true, data: { id, username, email, full_name, bio, profile_picture_url, role, created_at } }
```

#### POST /api/user-profile.php?action=update
Update user profile information.
```
Body: { full_name, bio, profile_picture_url }
Response: { success: true, message: '...', data: {...} }
```

#### POST /api/user-profile.php?action=update_password
Change user password.
```
Body: { current_password, new_password }
Response: { success: true, message: 'Password updated successfully' }
```

#### GET /api/dashboard.php?action=user_info
Get current user info for dashboard.
```
Response: { user_id, username, role, access_level }
```

#### GET /api/dashboard.php?action=perfumes
Get perfumes list (staff/owner only).

#### GET /api/dashboard.php?action=inventory
Get inventory data (staff/owner only).

#### POST /api/dashboard.php?action=update_inventory
Update inventory quantities (staff/owner only).

## User Roles

- **registered** - Regular user, can create purchases and view perfumes
- **staff** - Employee with inventory management access
- **owner** - Full administrative access

## Configuration

Edit `config/config.php` to modify database settings:
```php
$dsn = 'sqlite:' . __DIR__ . '/../labscentique.db';
```

## Deployment

### Local Deployment (Development)

```bash
# 1. Initialize database
php src/init-db.php

# 2. Start PHP development server
php -S localhost:8000

# 3. Open browser
# http://localhost:8000/public/index.php
```

### Alternative Hosting

For persistent SQLite storage:
- Railway.app
- Render.com
- DigitalOcean App Platform
- Traditional VPS hosting

## Development Notes

- Client-side validation is performed using `validate.js` to reduce server load
- Most data fetching uses the API endpoints for clean separation of concerns
- Profile modal system uses a consistent pattern for all user interactions
- Database can use SQLite (development) or PostgreSQL (production)

## Deployment Checklist

- [ ] Database initialized: `php src/init-db.php`
- [ ] PHP server started: `php -S localhost:8000`
- [ ] Database file created: `labscentique.db`
- [ ] All API endpoints accessible
- [ ] User registration working
- [ ] Profile editing functional
- [ ] Inventory operations verified

## Troubleshooting

### Database Not Found
```
php src/init-db.php
```

### Permission Denied on Database
Check file permissions on `labscentique.db` and ensure PHP process can write to it.

### API Endpoints Return 401
Ensure you're logged in. The dashboard and profile APIs require an active session.

### Styles Not Loading
Verify `public/styles.css` is accessible and check browser console for 404 errors.

## Future Enhancements

- Integration with payment processing
- Advanced search and filtering
- Email notifications
- Two-factor authentication
- Multi-language support
- Performance optimizations for large inventories

## Notes

- User passwords are hashed using PHP's `password_hash()` function with PASSWORD_DEFAULT algorithm
- Sessions are server-side only for improved security
- All user input is validated and sanitized before database operations
- CSRF protection should be implemented for production deployment
- Consider enabling HTTPS in production environments

