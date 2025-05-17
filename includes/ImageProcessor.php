<?php
/**
 * ImageProcessor Class
 * 
 * Handles image processing for product images in the Brew & Bake website.
 * Features:
 * - Convert images to PNG format with transparency support
 * - Resize images to 800x800 pixels (1:1 aspect ratio)
 * - Optimize file size to be under 200KB
 * - Maintain proper naming conventions
 */
class ImageProcessor {
    /**
     * @var string The directory where product images are stored
     */
    private $imageDir;
    
    /**
     * @var int The target width for product images
     */
    private $targetWidth = 800;
    
    /**
     * @var int The target height for product images
     */
    private $targetHeight = 800;
    
    /**
     * @var int The maximum file size in bytes (200KB)
     */
    private $maxFileSize = 204800;
    
    /**
     * @var array Allowed image mime types
     */
    private $allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    /**
     * Constructor
     * 
     * @param string $imageDir The directory where product images are stored
     */
    public function __construct($imageDir = 'assets/images/products/') {
        $this->imageDir = $imageDir;
        
        // Create the directory if it doesn't exist
        if (!is_dir($this->imageDir)) {
            mkdir($this->imageDir, 0755, true);
        }
    }
    
    /**
     * Process an uploaded image
     * 
     * @param array $file The uploaded file ($_FILES array element)
     * @param string $productName The name of the product
     * @param string $categoryName The category of the product
     * @return array Result with status and message
     */
    public function processImage($file, $productName, $categoryName) {
        // Check if file was uploaded properly
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return [
                'success' => false,
                'message' => 'No file was uploaded.'
            ];
        }
        
        // Check file type
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.'
            ];
        }
        
        // Generate filename based on product name
        $filename = $this->generateFilename($productName);
        $outputPath = $this->imageDir . $filename;
        
        // Process the image
        try {
            // Create image resource based on file type
            $sourceImage = $this->createImageFromFile($file['tmp_name'], $fileType);
            if (!$sourceImage) {
                return [
                    'success' => false,
                    'message' => 'Failed to create image resource.'
                ];
            }
            
            // Get original dimensions
            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);
            
            // Create a new image with the target dimensions
            $targetImage = imagecreatetruecolor($this->targetWidth, $this->targetHeight);
            
            // Enable alpha channel for transparency
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
            imagefill($targetImage, 0, 0, $transparent);
            
            // Resize the image while maintaining aspect ratio
            $this->resizeAndCenterImage($sourceImage, $targetImage, $sourceWidth, $sourceHeight);
            
            // Save the image as PNG
            imagepng($targetImage, $outputPath, 9); // Maximum compression
            
            // Free up memory
            imagedestroy($sourceImage);
            imagedestroy($targetImage);
            
            // Check if the file size is within limits
            if (filesize($outputPath) > $this->maxFileSize) {
                // If file is too large, try to optimize it
                $this->optimizeImage($outputPath);
                
                // If still too large after optimization, return a warning
                if (filesize($outputPath) > $this->maxFileSize) {
                    return [
                        'success' => true,
                        'message' => 'Image processed successfully but could not be optimized to under 200KB.',
                        'filename' => $filename,
                        'path' => $outputPath,
                        'warning' => true
                    ];
                }
            }
            
            return [
                'success' => true,
                'message' => 'Image processed successfully.',
                'filename' => $filename,
                'path' => $outputPath
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error processing image: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate a filename based on product name
     * 
     * @param string $productName The name of the product
     * @return string The generated filename
     */
    private function generateFilename($productName) {
        // Convert to lowercase
        $filename = strtolower($productName);
        
        // Replace spaces with hyphens
        $filename = str_replace(' ', '-', $filename);
        
        // Remove any special characters
        $filename = preg_replace('/[^a-z0-9\-]/', '', $filename);
        
        // Add .png extension
        $filename .= '.png';
        
        return $filename;
    }
    
    /**
     * Create an image resource from a file
     * 
     * @param string $filePath The path to the file
     * @param string $fileType The mime type of the file
     * @return resource The image resource
     */
    private function createImageFromFile($filePath, $fileType) {
        switch ($fileType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/gif':
                return imagecreatefromgif($filePath);
            case 'image/webp':
                return imagecreatefromwebp($filePath);
            default:
                return false;
        }
    }
    
    /**
     * Resize and center an image
     * 
     * @param resource $sourceImage The source image resource
     * @param resource $targetImage The target image resource
     * @param int $sourceWidth The width of the source image
     * @param int $sourceHeight The height of the source image
     */
    private function resizeAndCenterImage($sourceImage, $targetImage, $sourceWidth, $sourceHeight) {
        // Calculate aspect ratios
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $this->targetWidth / $this->targetHeight;
        
        // Calculate dimensions for resizing
        if ($sourceRatio > $targetRatio) {
            // Source image is wider
            $newWidth = $sourceHeight * $targetRatio;
            $newHeight = $sourceHeight;
            $sourceX = ($sourceWidth - $newWidth) / 2;
            $sourceY = 0;
        } else {
            // Source image is taller
            $newWidth = $sourceWidth;
            $newHeight = $sourceWidth / $targetRatio;
            $sourceX = 0;
            $sourceY = ($sourceHeight - $newHeight) / 2;
        }
        
        // Resize and center the image
        imagecopyresampled(
            $targetImage,
            $sourceImage,
            0, 0, $sourceX, $sourceY,
            $this->targetWidth, $this->targetHeight,
            $newWidth, $newHeight
        );
    }
    
    /**
     * Optimize an image to reduce file size
     * 
     * @param string $filePath The path to the image file
     */
    private function optimizeImage($filePath) {
        // Load the image
        $image = imagecreatefrompng($filePath);
        
        // Create a new image with reduced quality
        $optimizedImage = imagecreatetruecolor($this->targetWidth, $this->targetHeight);
        
        // Enable alpha channel for transparency
        imagesavealpha($optimizedImage, true);
        $transparent = imagecolorallocatealpha($optimizedImage, 0, 0, 0, 127);
        imagefill($optimizedImage, 0, 0, $transparent);
        
        // Copy and resize the image
        imagecopyresampled(
            $optimizedImage,
            $image,
            0, 0, 0, 0,
            $this->targetWidth, $this->targetHeight,
            $this->targetWidth, $this->targetHeight
        );
        
        // Save with higher compression
        imagepng($optimizedImage, $filePath, 9);
        
        // Free up memory
        imagedestroy($image);
        imagedestroy($optimizedImage);
    }
}
?>
