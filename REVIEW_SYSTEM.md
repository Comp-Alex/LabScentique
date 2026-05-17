# LabScentique Review System Documentation

## Overview
A complete review and rating system has been integrated into LabScentique with the following features:

### ✨ Key Features

#### 1. **Guest & Registered User Reviews**
   - Guest users can leave reviews by providing their name and email
   - Registered users can leave reviews while logged in
   - Star rating system (1-5 stars) with interactive feedback
   - Comment field with validation (5-1000 characters)

#### 2. **Role-Based Visibility**
   - **Guests & Registered Users**: Can only see reviews marked as "visible to guests"
   - **Staff & Owner**: Can see all reviews (both public and private)
   - Guest reviews are marked as private by default (only visible to staff/owner)
   - Users can opt-in to make their reviews visible to other guests

#### 3. **Staff/Owner Reply System**
   - Staff and owner accounts can reply to any review
   - One reply per staff/owner per review
   - Replies are always visible to everyone (when the parent review is visible)
   - Reply button appears in the review modal for authorized users

#### 4. **Floating Modal Interface**
   - Non-intrusive overlay design that doesn't navigate away from the page
   - Shows all reviews for a product on the left side
   - Review form on the right side for submitting new reviews
   - Auto-updating average rating for perfumes

---

## Technical Architecture

### Database Tables

#### `product_reviews` Table
```sql
- id: INTEGER PRIMARY KEY
- perfume_id: INTEGER (FK to perfumes)
- user_id: INTEGER (FK to users, NULL for guests)
- guest_name: TEXT (for guest reviews)
- guest_email: TEXT (for guest reviews)
- rating: INTEGER (1-5)
- comment: TEXT (5-1000 characters)
- is_visible_to_guests: BOOLEAN (0/1)
- created_at: DATETIME
- updated_at: DATETIME
```

#### `review_replies` Table
```sql
- id: INTEGER PRIMARY KEY
- review_id: INTEGER (FK to product_reviews)
- user_id: INTEGER (FK to users, staff/owner only)
- reply_text: TEXT (5-500 characters)
- created_at: DATETIME
- updated_at: DATETIME
- UNIQUE(review_id, user_id) - Only one reply per staff per review
```

---

## API Endpoints

### 1. Get Reviews
**Endpoint**: `/api/reviews.php?action=get_reviews&perfume_id={id}`  
**Method**: GET  
**Returns**: 
- Reviews visible based on user role
- Includes reply information if available
- Respects privacy settings

### 2. Submit Review
**Endpoint**: `/api/reviews.php`  
**Method**: POST  
**Payload**:
```json
{
  "action": "submit_review",
  "perfume_id": 1,
  "rating": 5,
  "comment": "Amazing fragrance!",
  "guest_name": "John Doe",
  "guest_email": "john@example.com",
  "is_visible_to_guests": true
}
```
**Returns**: Review ID and updated average rating

### 3. Submit Reply (Staff/Owner Only)
**Endpoint**: `/api/reviews.php`  
**Method**: POST  
**Payload**:
```json
{
  "action": "submit_reply",
  "review_id": 1,
  "reply_text": "Thank you for your review!"
}
```
**Returns**: Reply ID and responder name

---

## Frontend Components

### Files Added

1. **`review-modal.html`**
   - Contains the HTML structure for both review and reply modals
   - Includes form fields for star rating, comments, and guest info
   - Displays existing reviews with replies

2. **`review-styles.css`**
   - Complete styling for review modals and components
   - Responsive design for mobile/tablet/desktop
   - Dark theme matching LabScentique branding
   - Animations and transitions

3. **`review-system.js`**
   - `ReviewSystem` object with all functionality
   - Methods:
     - `init()` - Initialize on page load
     - `openModal(perfumeId, perfumeName)` - Open review modal
     - `closeModal()` - Close review modal
     - `setRating(rating)` - Handle star rating
     - `submitReview()` - Submit new review
     - `submitReply()` - Submit staff reply
     - `loadReviews()` - Fetch and display reviews
     - Various utility methods

### Modified Files

1. **`index_view.php`**
   - Added `review-styles.css` link in head
   - Added data attributes to body for user role and ID
   - Included review modal HTML
   - Added `review-system.js` script before closing body

2. **`app.js`**
   - Added review button to product cards ("💬 Leave a Review")
   - Added event listeners for review button clicks
   - Integrated with `ReviewSystem.openModal()`

3. **`styles.css`**
   - Added `.button-outline` class for review buttons
   - Added review section styling

---

## Usage Guide

### For Guest Users
1. Click "💬 Leave a Review" button on any product card
2. Select a star rating (1-5)
3. Write your comment (min 5 characters)
4. Enter your name and email
5. (Optional) Check "Make this review visible to other guests"
6. Submit the review
7. Staff will review and it will appear when approved

### For Registered Users
1. Login to your account
2. Click "💬 Leave a Review" button on any product card
3. Select star rating and write comment
4. Name and email are auto-populated (optional: check visibility box)
5. Submit review
6. Your review appears immediately

### For Staff/Owner
1. Login as staff or owner
2. Click "💬 Leave a Review" to see all reviews (including private ones)
3. To reply to a review:
   - Click "💬 Reply to Review" button on any review
   - Write your response (5-500 characters)
   - Click "Send Reply"
   - Reply appears immediately under the review

---

## Rating System Details

### Star Feedback Messages
- ⭐ 1 star: "Poor"
- ⭐⭐ 2 stars: "Fair"
- ⭐⭐⭐ 3 stars: "Good"
- ⭐⭐⭐⭐ 4 stars: "Very Good"
- ⭐⭐⭐⭐⭐ 5 stars: "Excellent"

### Average Rating Calculation
- Automatically calculated from all guest and registered user reviews
- Updates the `perfumes.rating` field after each new review
- Formula: `AVG(rating)` rounded to 1 decimal place

---

## Visibility Rules

| User Type | Can See | Can Review | Can Reply |
|-----------|---------|-----------|-----------|
| Guest | Public reviews only | Yes | No |
| Registered | Public reviews only | Yes | No |
| Staff | All reviews | Yes | Yes |
| Owner | All reviews | Yes | Yes |

---

## Security Features

1. **Input Validation**
   - Email format validation for guests
   - Rating range validation (1-5)
   - Comment length validation (5-1000 chars)
   - Reply length validation (5-500 chars)

2. **XSS Prevention**
   - HTML escaping for all user inputs
   - Using `escapeHtml()` function in JavaScript
   - Using `htmlspecialchars()` in PHP

3. **Authorization**
   - Only authenticated staff/owner can submit replies
   - Role-based visibility enforcement at API level
   - Session validation for all requests

4. **Database**
   - Foreign key constraints
   - Unique constraint on reply (one per staff per review)
   - Proper data type usage

---

## Testing the System

### Test Scenarios

1. **Guest Review Flow**
   - Open any product
   - Click "Leave a Review"
   - Submit as guest
   - Review should show after staff approval

2. **Registered User Review**
   - Login as "guest_user" (password: guest123)
   - Click "Leave a Review"
   - Submit
   - Should appear immediately

3. **Staff Reply**
   - Login as "staff" (password: staff123)
   - Click "Leave a Review" on product
   - Click "Reply to Review" on a guest review
   - Reply should appear immediately

4. **Privacy Settings**
   - Login as guest user
   - Submit review with "Make visible" unchecked
   - Logout
   - Browse as guest - review should NOT appear
   - Login as staff - review SHOULD appear

---

## Troubleshooting

### Review Modal Not Opening
- Ensure `review-system.js` is loaded
- Check browser console for errors
- Verify body has `data-user-role` and `data-user-id` attributes

### Reviews Not Showing
- Check database for `product_reviews` table
- Verify reviews are marked as `is_visible_to_guests = 1` for guests
- Check user role in browser console: `window.userData.role`

### Can't Submit Reviews
- Ensure database is initialized with `init.sql`
- Check API endpoint: `/api/reviews.php`
- Verify form validation (rating 1-5, comment 5+ chars)
- Check console for API errors

### Replies Not Appearing
- Only staff/owner can submit replies
- Verify user role is 'staff' or 'owner'
- Check that parent review is visible
- Ensure one reply per staff per review (can edit existing)

---

## Future Enhancements

Potential features to add:
- Photo/image uploads with reviews
- Review editing for users (within time limit)
- Review helpful/unhelpful voting
- Review filtering by rating
- Verified purchase badge
- Review moderation dashboard
- Email notifications for reviews

---

## Files Summary

### New Files Created:
- `/api/reviews.php` - Backend API for review operations
- `/public/review-modal.html` - Modal HTML structure
- `/public/review-styles.css` - Complete styling
- `/public/review-system.js` - Frontend JavaScript logic

### Files Modified:
- `/database/init.sql` - Added review tables
- `/public/index_view.php` - Integrated review system
- `/public/app.js` - Added review buttons and listeners
- `/public/styles.css` - Added button styles

---

## Contact & Support

For issues or questions about the review system, contact the development team.
