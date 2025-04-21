<?php
declare(strict_types=1);

$maxPrice = filter_input(INPUT_POST, 'max_price', FILTER_VALIDATE_INT);
$city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);

$validCities = ['aizkraukle', 'aluksne', 'balvi', 'bauska', 'cesis', 'daugavpils', 'dobele', 'gulbene', 'jekabpils', 'jelgava', 'kraslava', 'kuldiga', 'liepaja', 'limbazi', 'ludza', 'madona', 'ogre', 'preili', 'rezekne', 'saldus', 'talsi', 'tukums', 'valka', 'valmiera', 'ventspils']; // Add all valid cities
if (!in_array($city, $validCities)) {
    $city = 'preili'; 
}

$preferences = [
    'max_price' => $maxPrice ?: 50000, 
    'city' => $city
];

file_put_contents('user_preferences.json', json_encode($preferences));

if (file_exists('listings_data.json')) {
    unlink('listings_data.json');
}

header('Location: index.php?settings=updated');
exit;