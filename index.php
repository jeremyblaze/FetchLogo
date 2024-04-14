<?php
function extractHostname($url) {
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }
    $parsedUrl = parse_url($url);
    return $parsedUrl['host'] ?? null;
}

$inputUrl = $_GET['url'] ?? null;
if (!$inputUrl) {
    header('HTTP/1.1 400 Bad Request');
    echo 'URL is required';
    exit;
}

$hostname = extractHostname($inputUrl);
if (!$hostname) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid URL provided';
    exit;
}

$cacheDir = 'cache/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$url = "https://$hostname";

function getIcons($url) {
    $options = [
        'http' => [
            'method' => "GET",
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $content = @file_get_contents($url, false, $context);

    if (!$content) {
        return false;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($content);
    $links = $dom->getElementsByTagName('link');
    $icons = [];
    $manifestUrl = null;

    foreach ($links as $link) {
        $rel = strtolower($link->getAttribute('rel'));
        $href = $link->getAttribute('href');
        if (substr($href, 0, 4) !== 'http') {
            $href = rtrim($url, '/') . '/' . ltrim($href, '/');
        }

        if (in_array($rel, ['apple-touch-icon', 'apple-touch-icon-precomposed', 'icon', 'shortcut icon'])) {
            $sizes = $link->getAttribute('sizes');
            if ($sizes) {
                $size = explode('x', $sizes)[0] * explode('x', $sizes)[1];
            } else {
                $size = 0;  // Default size if not specified, likely to be small
            }
            $icons[] = ['url' => $href, 'size' => $size, 'type' => $rel];
        } elseif ($rel === 'manifest' && !$manifestUrl) {
            $manifestUrl = $href;
        }
    }

    // Check if a manifest file is linked and parse it
    if ($manifestUrl) {
        $manifestContent = @file_get_contents($manifestUrl);
        if ($manifestContent) {
            $manifest = json_decode($manifestContent, true);
            if (isset($manifest['icons'])) {
                foreach ($manifest['icons'] as $icon) {
                    $iconUrl = $icon['src'];
                    if (substr($iconUrl, 0, 4) !== 'http') {
                        $iconUrl = rtrim($url, '/') . '/' . ltrim($iconUrl, '/');
                    }
                    $size = explode('x', $icon['sizes'])[0] * explode('x', $icon['sizes'])[1];
                    $icons[] = ['url' => $iconUrl, 'size' => $size, 'type' => 'manifest-icon'];
                }
            }
        }
    }

    return $icons;
}

function downloadIcon($icons, $cacheDir, $hostname) {
    // Sort icons by size in descending order
    usort($icons, function ($a, $b) {
        return $b['size'] - $a['size'];
    });

    foreach ($icons as $icon) {
        $url = $icon['url'];
        $fileName = parse_url($url, PHP_URL_PATH);
        $fileName = basename($fileName);
        $cachedFilePath = $cacheDir . $hostname . '_' . $fileName;

        // Check if the icon is already cached
        if (file_exists($cachedFilePath)) {
            return $cachedFilePath;
        }

        // Download the icon
        $iconData = @file_get_contents($url);
        if ($iconData) {
            file_put_contents($cachedFilePath, $iconData);
            return $cachedFilePath;
        }
    }

    return false;
}

$icons = getIcons($url);

if ($icons) {
    $cachedIcon = downloadIcon($icons, $cacheDir, $hostname);
    if ($cachedIcon) {
        // Serve the cached icon
        header('Content-Type: image/' . pathinfo($cachedIcon, PATHINFO_EXTENSION));
        readfile($cachedIcon);
        exit;
    }
}

header('HTTP/1.1 404 Not Found');
echo 'Failed to retrieve or download any icons';
?>
