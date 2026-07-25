<?php
// Simple router/proxy for Vercel deployment of Pawganic Supplies
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove leading slash
$request = ltrim($request, '/');

// Default to index.php if empty
if ($request === '') {
    $request = 'index.php';
}

// Security: prevent directory traversal
$realBase = realpath(__DIR__ . '/..');
$targetFile = realpath($realBase . '/' . $request);

// If it's a directory, check for index.php inside it
if ($targetFile && is_dir($targetFile)) {
    $request = rtrim($request, '/') . '/index.php';
    $targetFile = realpath($realBase . '/' . $request);
}

// Add .php extension if not present and the file exists with .php
if (!$targetFile || !file_exists($targetFile)) {
    if (!str_ends_with($request, '.php')) {
        $targetFile = realpath($realBase . '/' . $request . '.php');
        if ($targetFile && file_exists($targetFile)) {
            $request .= '.php';
        }
    }
}

// Verify file exists, is inside the base directory, and is a PHP file
if ($targetFile && file_exists($targetFile) && str_starts_with($targetFile, $realBase) && str_ends_with($targetFile, '.php')) {
    // Override script name and file paths to match the target file
    $_SERVER['SCRIPT_FILENAME'] = $targetFile;
    $_SERVER['SCRIPT_NAME'] = '/' . $request;
    $_SERVER['PHP_SELF'] = '/' . $request;
    
    // Change working directory to the target file's directory so relative includes/requires work
    chdir(dirname($targetFile));
    
    // Execute the file
    require $targetFile;
} else {
    // Fallback to 404
    http_response_code(404);
    $notFoundFile = $realBase . '/404.php';
    if (file_exists($notFoundFile)) {
        require $notFoundFile;
    } else {
        echo "404 Not Found";
    }
}
