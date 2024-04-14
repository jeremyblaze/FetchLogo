# Website Logo Fetcher API

A PHP-based API that fetches apple touch icons / favicons for you.

Try it at [neverbeforeseen.co/logos](https://neverbeforeseen.co/logos?ref=github)

## Overview
This API fetches and caches website icons, prioritizing the Apple touch icon. It is designed to retrieve icons from specified hostnames and serve them directly from the cache for subsequent requests. The API focuses on delivering high-resolution icons when available and supports efficient handling of web resources.

## Getting Started

### Base URL
```url
http://yourserver.com/index.php
```
Replace `http://yourserver.com/index.php` with the actual URL where your PHP script is hosted.

### HTTP Method
**GET** - Retrieves and serves the icon associated with the specified hostname.

## API Reference

### Request Parameters

| Parameter   | Required | Description                                                       |
|-------------|----------|-------------------------------------------------------------------|
| `hostname`  | Yes      | The fully-qualified domain name from which to fetch the icon.     |

### Responses

#### Success Response
- **Content-Type:** `image/png`, `image/jpeg`, etc., depending on the icon's format.
- **Content:** The binary data of the icon image.

#### Error Responses
- **400 Bad Request:** The `hostname` parameter was not provided.
- **404 Not Found:** No icon could be retrieved or failed to download.

## Examples

### Example Request
To fetch and cache the icon from Apple's website:
```url
http://yourserver.com/index.php?hostname=apple.com
```

This request will fetch the icon for apple.com and, if not already cached, will cache it for subsequent requests.

## Notes

Ensure the server is configured to allow PHP to make external HTTP requests and write files (check the allow_url_fopen and appropriate directory permissions in your PHP configuration).
This API is designed to be simple and straightforward, aiming for high performance with minimal overhead.
Contributing
Contributions are welcome! Please feel free to submit pull requests or open issues to suggest improvements or report bugs.

## License

This project is open source and available under the MIT License.