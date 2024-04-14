<?php
// Function to extract the hostname from a given URL or a simple hostname input
function getHostnameFromUrl($input) {
    if (filter_var($input, FILTER_VALIDATE_URL)) {
        $parsedUrl = parse_url($input);
        return $parsedUrl['host'] ?? null; // Return the hostname part of the URL
    } else {
        return $input; // Assume the input is already a hostname
    }
}

// Get the URL or hostname from the query string
$inputUrl = $_GET['url'] ?? null;

if (!$inputUrl) {
    header('HTTP/1.1 400 Bad Request');
    echo 'A URL or hostname is required';
    exit;
}

// Extract and clean the hostname
$hostname = getHostnameFromUrl($inputUrl);

if (!$hostname) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid URL or hostname provided';
    exit;
}

// Cache directory
$cacheDir = 'cache/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Prepare the URL for fetching
$url = "https://$hostname";

function getIcons($url) {
    // User agent and other headers to mimic a browser request
    $options = [
        'http' => [
            'method' => "GET",
            'follow_location' => 1, // Follow redirects
            'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]
    ];
    $context = stream_context_create($options);

    // Fetch webpage content
    $content = @file_get_contents($url, false, $context);
    if (!$content) {
        return false;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($content);
    $links = $dom->getElementsByTagName('link');
    $icons = [];

    foreach ($links as $link) {
        $rel = $link->getAttribute('rel');
        if (in_array($rel, ['apple-touch-icon', 'apple-touch-icon-precomposed', 'icon', 'shortcut icon'])) {
            $href = $link->getAttribute('href');
            if (substr($href, 0, 4) !== 'http') {
                $href = rtrim($url, '/') . '/' . ltrim($href, '/');
            }
            $size = $link->getAttribute('sizes');
            $icons[] = ['url' => $href, 'size' => $size, 'rel' => $rel];
        }
    }

    // Sort icons by size (assuming sizes are in the format "widthxheight")
    usort($icons, function($a, $b) {
        $aSize = explode('x', strtolower($a['size']));
        $bSize = explode('x', strtolower($b['size']));
        $aArea = (int)$aSize[0] * (int)$aSize[1];
        $bArea = (int)$bSize[0] * (int)$bSize[1];
        return $bArea <=> $aArea;
    });

    return $icons;
}

function downloadIcon($url, $cacheDir, $hostname) {
    $fileName = parse_url($url, PHP_URL_PATH);
    $fileName = basename($fileName);
    $cachedFilePath = $cacheDir . $hostname . '_' . $fileName;

    // Check if the icon is already cached
    if (file_exists($cachedFilePath)) {
        return $cachedFilePath;
    }

    // Download the icon
    $iconData = file_get_contents($url);
    if ($iconData) {
        file_put_contents($cachedFilePath, $iconData);
        return $cachedFilePath;
    }

    return false;
}

$icons = getIcons($url);

if ($icons) {
    $iconUrl = $icons['apple-touch-icon'] ?? $icons['apple-touch-icon-precomposed'] ?? $icons['icon'] ?? $icons['shortcut icon'] ?? null;
    if ($iconUrl) {
        // Attempt to download and cache the icon
        $cachedIcon = downloadIcon($iconUrl, $cacheDir, $hostname);
        if ($cachedIcon) {
            // Serve the cached icon
            header('Content-Type: image/' . pathinfo($cachedIcon, PATHINFO_EXTENSION));
            readfile($cachedIcon);
        } else {
            header('HTTP/1.1 404 Not Found');
            echo 'Failed to download the icon';
        }
    } else {
        header('HTTP/1.1 404 Not Found');
        echo 'No icon found';
    }
} else {
    header('HTTP/1.1 404 Not Found');
    echo 'Failed to retrieve icons';
}
?>
