<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mājas zem €<?= number_format(MAX_PRICE, 0, '.', ' ') ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
<h1>Mājas zem €<?= number_format(MAX_PRICE, 0, '.', ' ') ?></h1>
        
        <?php if ($hasNewListings): ?>
            <div class="notification-banner">
                <p>New properties have been found and notification email has been sent!</p>
            </div>
        <?php endif; ?>
        
        <div class="sort-controls">
            <form action="" method="get">
                <label for="sort">Sort by:</label>
                <select id="sort" name="sort">
                    <option value="price" <?= $sortBy === 'price' ? 'selected' : '' ?>>Price</option>
                    <option value="square_footage" <?= $sortBy === 'square_footage' ? 'selected' : '' ?>>Square Footage</option>
                    <option value="land_area" <?= $sortBy === 'land_area' ? 'selected' : '' ?>>Land Area</option>
                    <option value="date_added" <?= $sortBy === 'date_added' ? 'selected' : '' ?>>Date Added</option>
                </select>
                
                <label for="order">Order:</label>
                <select id="order" name="order">
                    <option value="asc" <?= $sortOrder === 'asc' ? 'selected' : '' ?>>Ascending</option>
                    <option value="desc" <?= $sortOrder === 'desc' ? 'selected' : '' ?>>Descending</option>
                </select>
                
                <button type="submit">Sort</button>
            </form>
        </div>
        
        <?php if (empty($listings)): ?>
            <div class="no-listings">No properties found under €50,000</div>
        <?php else: ?>
            <?php foreach ($listings as $listing): ?>
                <?php 
                    $isNew = false;
                    if (isset($newListings) && !empty($newListings)) {
                        foreach ($newListings as $newListing) {
                            if ($newListing['id'] === $listing['id']) {
                                $isNew = true;
                                break;
                            }
                        }
                    }
                ?>
                <div class="listing <?= $isNew ? 'new-listing' : '' ?>">
                    <div class="listing-img">
                        <img src="<?= htmlspecialchars($listing['image']) ?>" alt="Property Image" class="listing-img-thumbnail">
                    </div>
                    <div class="listing-details">
                        <h3><a href="<?= htmlspecialchars($listing['link']) ?>" target="_blank"><?= htmlspecialchars($listing['description']) ?></a></h3>
                        <p><strong>Region:</strong> <?= htmlspecialchars($listing['region']) ?></p>
                        <p><strong>Square Footage:</strong> <?= htmlspecialchars($listing['square_footage']) ?></p>
                        <p><strong>Floors:</strong> <?= htmlspecialchars($listing['floors']) ?></p>
                        <p><strong>Land Area:</strong> <?= htmlspecialchars($listing['land_area']) ?></p>
                        <p><strong>Price:</strong> <?= htmlspecialchars($listing['price']) ?></p>
                        <?php if (isset($listing['date_added'])): ?>
                            <p><strong>Added:</strong> <?= date('Y-m-d', $listing['date_added']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (isset($debugInfo) && $debugInfo): ?>
            <?= $debugInfo ?>
        <?php endif; ?>
    </div>
</body>
</html>