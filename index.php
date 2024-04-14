<?php
// Get the hostname from the query string
$hostname = $_GET['hostname'] ?? null;

if (!$hostname) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Hostname is required';
    exit;
}

// Cache directory
$cacheDir = 'cache/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Prepare the URL
$url = "https://$hostname";

function getIcons($url) {
    // User agent and other headers to mimic a browser request
    $options = [
        'http' => [
            'method' => "GET",
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36\r\n"
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
        if (in_array($link->getAttribute('rel'), ['apple-touch-icon', 'apple-touch-icon-precomposed', 'icon', 'shortcut icon'])) {
            $href = $link->getAttribute('href');
            if (substr($href, 0, 4) !== 'http') {
                $href = rtrim($url, '/') . '/' . ltrim($href, '/');
            }
            $icons[$link->getAttribute('rel')] = $href;
        }
    }

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
    if (isset($icons['apple-touch-icon'])) {
        $iconUrl = $icons['apple-touch-icon'];
    } elseif (isset($icons['apple-touch-icon-precomposed'])) {
        $iconUrl = $icons['apple-touch-icon-precomposed'];
    } elseif (isset($icons['icon'])) {
        $iconUrl = $icons['icon'];
    } elseif (isset($icons['shortcut icon'])) {
        $iconUrl = $icons['shortcut icon'];
    } else {
        header('HTTP/1.1 404 Not Found');
        echo 'No icon found';
        exit;
    }

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
    echo 'Failed to retrieve icons';
}
?>
