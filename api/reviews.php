<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../config/config.php';
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}
$action = $_GET['action'] ?? $_POST['action'] ?? $input['action'] ?? '';

// Get all reviews for a perfume with role-based visibility
if ($action === 'get_reviews' && $method === 'GET') {
    try {
        $perfumeId = (int)($_GET['perfume_id'] ?? 0);
        if (!$perfumeId) {
            http_response_code(400);
            echo json_encode(['error' => 'Perfume ID required']);
            exit;
        }

        $userRole = $_SESSION['role'] ?? 'guest';
        $userId = $_SESSION['user_id'] ?? null;

        // Build visibility condition based on user role
        $visibilityCondition = '';
        if ($userRole === 'staff' || $userRole === 'owner' || $userRole === 'admin') {
            // Staff/owner/admin can see all reviews
            $visibilityCondition = '';
        } else {
            // Guests and registered users can only see reviews marked as visible
            $visibilityCondition = 'AND is_visible_to_guests = 1';
        }

        $query = "
            SELECT 
                pr.id,
                pr.perfume_id,
                pr.user_id,
                pr.guest_name,
                pr.guest_email,
                pr.rating,
                pr.comment,
                pr.is_visible_to_guests,
                pr.created_at,
                pr.updated_at,
                COALESCE(u.username, pr.guest_name) as reviewer_name,
                u.role as reviewer_role,
                COALESCE(
                    (SELECT json_object('id', rr.id, 'user_id', rr.user_id, 'reply_text', rr.reply_text, 'created_at', rr.created_at, 'responder_name', u2.username, 'responder_role', u2.role)
                     FROM review_replies rr
                     LEFT JOIN users u2 ON rr.user_id = u2.id
                     WHERE rr.review_id = pr.id
                     LIMIT 1),
                    NULL
                ) as reply
            FROM product_reviews pr
            LEFT JOIN users u ON pr.user_id = u.id
            WHERE pr.perfume_id = :perfume_id
            $visibilityCondition
            ORDER BY pr.created_at DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([':perfume_id' => $perfumeId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse JSON reply field for each review
        foreach ($reviews as &$review) {
            $review['rating'] = (int)$review['rating'];
            $review['is_visible_to_guests'] = (bool)$review['is_visible_to_guests'];
            if ($review['reply']) {
                $review['reply'] = json_decode($review['reply'], true);
            }
        }

        echo json_encode(['success' => true, 'data' => $reviews]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch reviews']);
    }
    exit;
}

// Submit a new review
if ($action === 'submit_review' && $method === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $perfumeId = (int)($input['perfume_id'] ?? 0);
        $rating = (int)($input['rating'] ?? 0);
        $comment = trim($input['comment'] ?? '');
        $guestName = trim($input['guest_name'] ?? '');
        $guestEmail = trim($input['guest_email'] ?? '');
        $isVisibleToGuests = (bool)($input['is_visible_to_guests'] ?? false);

        // Validation
        if (!$perfumeId || $rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid perfume ID or rating (1-5)']);
            exit;
        }

        if (!$comment || strlen($comment) < 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Comment must be at least 5 characters']);
            exit;
        }

        // Determine if it's a guest or registered user review
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            // Guest review - must have guest name and email
            if (!$guestName || !$guestEmail) {
                http_response_code(400);
                echo json_encode(['error' => 'Guest name and email required']);
                exit;
            }
            
            if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email format']);
                exit;
            }
        }

        // Verify perfume exists
        $stmt = $pdo->prepare('SELECT id FROM perfumes WHERE id = :id');
        $stmt->execute([':id' => $perfumeId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Perfume not found']);
            exit;
        }

        // Insert review
        $stmt = $pdo->prepare('
            INSERT INTO product_reviews (perfume_id, user_id, guest_name, guest_email, rating, comment, is_visible_to_guests)
            VALUES (:perfume_id, :user_id, :guest_name, :guest_email, :rating, :comment, :is_visible_to_guests)
        ');
        
        $stmt->execute([
            ':perfume_id' => $perfumeId,
            ':user_id' => $userId,
            ':guest_name' => $guestName ?: null,
            ':guest_email' => $guestEmail ?: null,
            ':rating' => $rating,
            ':comment' => $comment,
            ':is_visible_to_guests' => $isVisibleToGuests ? 1 : 0
        ]);

        $reviewId = (int)$pdo->lastInsertId();

        // Calculate new average rating for perfume
        $stmt = $pdo->query("
            SELECT AVG(rating) as avg_rating FROM product_reviews WHERE perfume_id = $perfumeId
        ");
        $result = $stmt->fetch();
        $avgRating = round($result['avg_rating'] ?? 0, 1);

        // Update perfume rating
        $stmt = $pdo->prepare('UPDATE perfumes SET rating = :rating WHERE id = :id');
        $stmt->execute([':rating' => $avgRating, ':id' => $perfumeId]);

        echo json_encode([
            'success' => true,
            'message' => 'Review submitted successfully',
            'review_id' => $reviewId,
            'average_rating' => $avgRating
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit review: ' . $e->getMessage()]);
    }
    exit;
}

// Submit a reply to a review (staff/owner only)
if ($action === 'submit_reply' && $method === 'POST') {
    try {
        $userRole = $_SESSION['role'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;

        // Check authorization
        if (!$userId || ($userRole !== 'staff' && $userRole !== 'owner' && $userRole !== 'admin')) {
            http_response_code(403);
            echo json_encode(['error' => 'Only staff/owner can reply to reviews']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $reviewId = (int)($input['review_id'] ?? 0);
        $replyText = trim($input['reply_text'] ?? '');

        // Validation
        if (!$reviewId || !$replyText || strlen($replyText) < 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Review ID required and reply must be at least 5 characters']);
            exit;
        }

        // Verify review exists
        $stmt = $pdo->prepare('SELECT id FROM product_reviews WHERE id = :id');
        $stmt->execute([':id' => $reviewId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Review not found']);
            exit;
        }

        // Check if staff/owner already replied to this review
        $stmt = $pdo->prepare('SELECT id FROM review_replies WHERE review_id = :review_id AND user_id = :user_id');
        $stmt->execute([':review_id' => $reviewId, ':user_id' => $userId]);
        $existingReply = $stmt->fetch();

        if ($existingReply) {
            // Update existing reply
            $stmt = $pdo->prepare('
                UPDATE review_replies 
                SET reply_text = :reply_text, updated_at = datetime("now")
                WHERE review_id = :review_id AND user_id = :user_id
            ');
            $stmt->execute([
                ':reply_text' => $replyText,
                ':review_id' => $reviewId,
                ':user_id' => $userId
            ]);
            $replyId = $existingReply['id'];
        } else {
            // Insert new reply
            $stmt = $pdo->prepare('
                INSERT INTO review_replies (review_id, user_id, reply_text)
                VALUES (:review_id, :user_id, :reply_text)
            ');
            $stmt->execute([
                ':review_id' => $reviewId,
                ':user_id' => $userId,
                ':reply_text' => $replyText
            ]);
            $replyId = (int)$pdo->lastInsertId();
        }

        // Get staff/owner name
        $stmt = $pdo->prepare('SELECT username FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        echo json_encode([
            'success' => true,
            'message' => 'Reply submitted successfully',
            'reply_id' => $replyId,
            'responder_name' => $user['username']
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit reply: ' . $e->getMessage()]);
    }
    exit;
}

// Default response
http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
exit;
