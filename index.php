<?php
declare(strict_types=1);

const MAX_PRICE = 50000;
const SOURCE_URL = "https://www.ss.lv/lv/real-estate/homes-summer-residences/preili-and-reg/";
const BASE_URL = "https://www.ss.lv";
const DATA_FILE = "listings_data.json";

function fetchUrl(string $url): string {
    $ch = curl_init($url);
    
    if (!$ch) {
        throw new Exception("Failed to initialize cURL");
    }
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
        CURLOPT_HTTPHEADER => ["Accept-Language: lv,en;q=0.9"],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new Exception("Failed to fetch page. cURL error: $error");
    }
    
    if ($httpCode >= 400) {
        throw new Exception("HTTP error: $httpCode");
    }

    return $response;
}

function parseListings(string $html): array {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $rows = $xpath->query("//tr[@id]");
    $listings = [];

    if (!$rows) {
        return $listings;
    }

    foreach ($rows as $row) {
        $priceNode = $xpath->query(".//td[last()]", $row)->item(0);
        $descNode = $xpath->query(".//a[@class='am']", $row)->item(0);
        
        if (!$priceNode || !$descNode) {
            continue;
        }

        $priceText = trim($priceNode->nodeValue);
        $priceNumber = (int)preg_replace('/[^\d]/', '', $priceText);

        if ($priceNumber > 0 && $priceNumber <= MAX_PRICE) {
            $detailCells = $xpath->query(".//td[contains(@class, 'msga2-o')]", $row);
            
            $details = [];
            $detailTypes = ['region', 'square_footage', 'floors', 'land_area'];
            
            if ($detailCells) {
                foreach ($detailCells as $index => $cell) {
                    if ($index < count($detailTypes)) {
                        $details[$detailTypes[$index]] = trim($cell->nodeValue);
                    }
                }
            }
            
            foreach ($detailTypes as $type) {
                if (!isset($details[$type])) {
                    $details[$type] = 'N/A';
                }
            }
            
            $link = "";
            if ($descNode instanceof DOMElement) {
                $link = BASE_URL . $descNode->getAttribute("href");
            }
            
            $imgSrc = 'https://via.placeholder.com/150';
            $imgNode = $xpath->query(".//img[contains(@class, 'isfoto')]", $row)->item(0);
            if ($imgNode instanceof DOMElement) {
                $imgSrc = $imgNode->getAttribute("src");

                if (strpos($imgSrc, 'http') !== 0) {
                    $imgSrc = "https:" . $imgSrc;
                }
            }
            
            if (preg_match('/\/msg\/([0-9]+)\.html/', $link, $matches)) {
                $id = $matches[1];
            } else {
                $id = md5($link . $priceText . $details['region'] . $details['square_footage']);
            }
            
            $timestamp = time();
            
            $squareFootageNumeric = (int)preg_replace('/[^\d]/', '', $details['square_footage']);
            $landAreaNumeric = (int)preg_replace('/[^\d]/', '', $details['land_area']);
            
            $listings[] = [
                'id' => $id,
                'link' => $link,
                'description' => trim($descNode->nodeValue),
                'region' => $details['region'],
                'square_footage' => $details['square_footage'],
                'square_footage_numeric' => $squareFootageNumeric,
                'floors' => $details['floors'],
                'land_area' => $details['land_area'],
                'land_area_numeric' => $landAreaNumeric,
                'image' => $imgSrc,
                'price' => $priceText,
                'price_numeric' => $priceNumber,
                'date_added' => time()
            ];
        }
    }

    return $listings;
}

function sortListings(array $listings, string $sortBy = 'price', string $sortOrder = 'asc'): array {
    $sortableFields = [
        'price' => 'price_numeric',
        'square_footage' => 'square_footage_numeric',
        'land_area' => 'land_area_numeric', 
        'date_added' => 'date_added'
    ];
    
    $field = $sortableFields[$sortBy] ?? 'price_numeric';
    
    usort($listings, function($a, $b) use ($field, $sortOrder) {
        if ($a[$field] == $b[$field]) {
            return 0;
        }
        
        if ($sortOrder === 'asc') {
            return $a[$field] <=> $b[$field];
        } else {
            return $b[$field] <=> $a[$field];
        }
    });
    
    return $listings;
}

function saveListingsData(array $listings): void {
    file_put_contents(DATA_FILE, json_encode($listings, JSON_PRETTY_PRINT));
}

function loadPreviousListings(): array {
    if (file_exists(DATA_FILE)) {
        $data = file_get_contents(DATA_FILE);
        return json_decode($data, true) ?: [];
    }
    return [];
}

function findNewListings(array $currentListings, array $previousListings): array {
    $previousIds = array_column($previousListings, 'id');
    
    $newListings = [];
    foreach ($currentListings as $listing) {
        if (!empty($listing['id']) && !in_array($listing['id'], $previousIds)) {
            $newListings[] = $listing;
        }
    }
    
    return $newListings;
}

function sendNotificationEmail(array $newListings): void {
    if (empty($newListings)) {
        return;
    }
    
    $to = 'youremail@example.com';
    $subject = 'New Property Listings Under €50,000';
    
    $message = '<html><body>';
    $message .= '<h2>New Properties Found Today</h2>';
    
    foreach ($newListings as $listing) {
        $message .= '<div style="margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">';
        $message .= '<h3><a href="' . $listing['link'] . '">' . htmlspecialchars($listing['description']) . '</a></h3>';
        $message .= '<p><strong>Price:</strong> ' . htmlspecialchars($listing['price']) . '</p>';
        $message .= '<p><strong>Region:</strong> ' . htmlspecialchars($listing['region']) . '</p>';
        $message .= '<p><strong>Square Footage:</strong> ' . htmlspecialchars($listing['square_footage']) . '</p>';
        $message .= '<p><strong>Land Area:</strong> ' . htmlspecialchars($listing['land_area']) . '</p>';
        $message .= '</div>';
    }
    
    $message .= '</body></html>';
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: Property Notifier <noreply@yourdomain.com>'
    ];
    
    mail($to, $subject, $message, implode("\r\n", $headers));
}

function debugRow(DOMNode $row, DOMXPath $xpath): string {
    $output = "<div class='debug-info'><h3>Row Debug Information</h3>";
    
    $cells = $xpath->query(".//td", $row);
    if ($cells) {
        $output .= "<h4>Cells in Row:</h4><ol>";
        foreach ($cells as $index => $cell) {
            $output .= "<li>Cell " . ($index + 1) . ": " . htmlspecialchars($cell->nodeValue) . "</li>";
        }
        $output .= "</ol>";
    }
    
    $output .= "<h4>Specific Elements:</h4><ul>";
    $classes = ['msga2-o', 'am', 'a18', 'isfoto'];
    foreach ($classes as $class) {
        $elements = $xpath->query(".//*[contains(@class, '$class')]", $row);
        if ($elements && $elements->length > 0) {
            $output .= "<li>Found " . $elements->length . " elements with class '$class'</li>";
            $output .= "<ul>";
            foreach ($elements as $element) {
                $output .= "<li>" . htmlspecialchars($element->nodeValue) . "</li>";
            }
            $output .= "</ul>";
        } else {
            $output .= "<li>No elements found with class '$class'</li>";
        }
    }
    $output .= "</ul></div>";
    
    return $output;
}

try {
    $sortBy = $_GET['sort'] ?? 'price';
    $sortOrder = $_GET['order'] ?? 'asc';
    
    if (!in_array($sortBy, ['price', 'square_footage', 'land_area', 'date_added'])) {
        $sortBy = 'price';
    }
    
    if (!in_array($sortOrder, ['asc', 'desc'])) {
        $sortOrder = 'asc';
    }
    
    $html = fetchUrl(SOURCE_URL);
    $currentListings = parseListings($html);
    
    $previousListings = loadPreviousListings();
    $newListings = findNewListings($currentListings, $previousListings);
    
    if (!empty($newListings)) {
        sendNotificationEmail($newListings);
    }
    
    saveListingsData($currentListings);
    
    $sortedListings = sortListings($currentListings, $sortBy, $sortOrder);
    
    $debug = false;
    $debugInfo = '';
    
    if ($debug) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query("//tr[@id]");
        
        if ($rows && $rows->length > 0) {
            $debugInfo = debugRow($rows->item(0), $xpath);
        }
    }
    
    $listings = $sortedListings;
    $hasNewListings = !empty($newListings);
    
    require 'template.php';
    
} catch (Exception $e) {
    http_response_code(500);
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Error</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 30px; text-align: center; }
            .error { color: #e74c3c; border: 1px solid #e74c3c; border-radius: 5px; padding: 20px; max-width: 600px; margin: 0 auto; }
        </style>
    </head>
    <body>
        <div class='error'>
            <h2>Error</h2>
            <p>{$e->getMessage()}</p>
        </div>
    </body>
    </html>";
    
    error_log("Property Scraper Error: " . $e->getMessage());
}