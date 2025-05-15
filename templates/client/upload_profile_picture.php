<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please log in to upload a profile picture.";
    header("Location: ../../index.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    $userId = $_SESSION['user_id'];
    
    // Check if file was uploaded
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error'] = "Only JPG, PNG, and GIF images are allowed.";
            header("Location: profile.php");
            exit;
        }
        
        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = "File size must be less than 2MB.";
            header("Location: profile.php");
            exit;
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = "../../assets/images/users/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = 'user_' . $userId . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $newFilename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            try {
                // Check if user already has a profile picture
                $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $oldPicture = $stmt->fetchColumn();
                
                // Delete old picture if it exists
                if ($oldPicture && file_exists($uploadDir . $oldPicture)) {
                    unlink($uploadDir . $oldPicture);
                }
                
                // Update user profile with new picture
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$newFilename, $userId]);
                
                $_SESSION['success'] = "Profile picture updated successfully!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Database error: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Failed to upload file. Please try again.";
        }
    } else {
        // Handle upload errors
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded.",
            UPLOAD_ERR_NO_FILE => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload."
        ];
        
        $errorCode = $_FILES['profile_picture']['error'] ?? UPLOAD_ERR_NO_FILE;
        $errorMessage = $uploadErrors[$errorCode] ?? "Unknown upload error.";
        
        $_SESSION['error'] = "Upload failed: " . $errorMessage;
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

// Redirect back to profile page
header("Location: profile.php");
exit;
?>
