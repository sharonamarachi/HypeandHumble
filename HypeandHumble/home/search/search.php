<?php
session_start();

$searchQuery = isset($_GET['q']) ? htmlspecialchars(trim($_GET['q'])) : '';

define('DB_SERVER', 'sql106.infinityfree.com');
define('DB_USERNAME', 'if0_38503886');
define('DB_PASSWORD', 'StlFnsLkFkx');
define('DB_NAME', 'if0_38503886_hypehumbledb');
define('DB_PORT', 3306);

try {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT 
                s.service_id,
                s.name AS service_name,
                s.description,
                s.price,
                s.service_type,
                p.rating,
                u.name AS company_name
            FROM services s
            JOIN providers p ON s.provider_id = p.provider_id
            JOIN users u ON p.user_id = u.user_id
            WHERE s.status = 'active'";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("SQL prepare error: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $cardsData = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cardsData[] = [
                'service_id' => $row['service_id'],
                'service_name' => htmlspecialchars($row['service_name']),
                'description' => htmlspecialchars($row['description']),
                'price' => number_format($row['price'], 2),
                'service_type' => htmlspecialchars($row['service_type']),
                'rating' => $row['rating'],
                'company_name' => htmlspecialchars($row['company_name']),
                'link' => '../card.php?id=' . $row['service_id']
            ];
        }
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log($e->getMessage());
    $cardsData = array();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hype & Humble - Sport Services</title>
    <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
    <meta name="description" content="Find and book sport services with Hype & Humble">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* Base Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            background-color: #f8fafc;
            color: #2d3748;
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 70px;
        }

        .search-header {
            background: #ffffff;
            margin: 2rem auto;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            max-width: 800px;
        }

        .search-header:focus-within {
            box-shadow: 0 0 0 3px rgba(90, 62, 161, 0.2);
            border-color: #5a3ea1;
        }

        .search-input {
            border: none;
            background: transparent;
            font-size: 1.1rem;
            font-weight: 500;
            flex: 1;
            outline: none;
            padding: 0.5rem;
            color: #2d3748;
        }

        .search-icon {
            color: #5a3ea1;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .search-icon:hover {
            color: #3a2a6e;
            transform: scale(1.1);
        }

        .content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        @media (min-width: 992px) {
            .content {
                flex-direction: row;
            }
        }

        .filters {
            width: 100%;
            background: #ffffff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        @media (min-width: 992px) {
            .filters {
                width: 280px;
                position: sticky;
                top: 1rem;
                height: fit-content;
            }
        }

        .filter-section {
            margin-bottom: 2rem;
        }

        .filter-section h3 {
            margin-bottom: 1rem;
            font-size: 1.1rem;
            color: #3a2a6e;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-section h3 i {
            font-size: 0.9rem;
            color: #718096;
        }

        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-option {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            cursor: pointer;
        }

        .filter-option input[type="radio"] {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid #e0d6ff;
            border-radius: 50%;
            margin-right: 0.75rem;
            cursor: pointer;
            position: relative;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .filter-option input[type="radio"]:checked {
            border-color: #5a3ea1;
        }

        .filter-option input[type="radio"]:checked::after {
            content: '';
            position: absolute;
            width: 10px;
            height: 10px;
            background: #5a3ea1;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .filter-option label {
            cursor: pointer;
            font-size: 0.95rem;
            color: #2d3748;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .filter-option:hover label {
            color: #3a2a6e;
        }

        .results {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            height: auto;
            align-content: flex-start;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            animation: fadeIn 0.3s ease-out forwards;
            opacity: 0;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            border-color: #e0d6ff;
        }

        .card-content {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            flex-grow: 1;
        }

        .service-type-tag {
            background: #e0d6ff;
            color: #3a2a6e;
            padding: 0.35rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            width: fit-content;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .service-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            line-height: 1.4;
        }

        .service-description {
            font-size: 0.9rem;
            color: #718096;
            line-height: 1.5;
            margin: 0.5rem 0;
            flex-grow: 1;
        }

        .card-footer {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding-top: 0.5rem;
            border-top: 1px solid #e2e8f0;
            margin-top: auto;
        }

        .service-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #3a2a6e;
        }

        .service-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-name {
            font-size: 0.9rem;
            color: #2d3748;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 60%;
        }

        .rating-badge {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            background: #e0d6ff;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
        }

        .rating-stars {
            display: flex;
            align-items: center;
        }

        .rating-stars i {
            color: #3a2a6e;
            font-size: 0.8rem;
        }

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .no-results h3 {
            font-size: 1.25rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .no-results p {
            color: #718096;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }

            .results {
                grid-template-columns: 1fr;
            }

            .search-header {
                margin: 1.5rem 0;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .card:nth-child(4) {
            animation-delay: 0.4s;
        }

        .card:nth-child(5) {
            animation-delay: 0.5s;
        }

        .card:nth-child(6) {
            animation-delay: 0.6s;
        }
    </style>

</head>

<body>
    <!-- Navbar -->
    <?php include __DIR__ . '/../../navbar.php'; ?>

    <!-- Main Content -->
    <main>
        <div class="container">
            <div class="search-header">
                <input type="text"
                    placeholder="Search for services..."
                    class="search-input"
                    value="<?php echo $searchQuery; ?>"
                    aria-label="Search services" />
                <i class="fas fa-search search-icon" role="button" aria-label="Search"></i>
            </div>

            <div class="content">
                <!-- Filters Sidebar -->
                <aside class="filters">
                    <div class="filter-section">
                        <h3><i class="fas fa-sort"></i> Sort By</h3>
                        <div class="filter-options">
                            <div class="filter-option">
                                <input type="radio" id="sort_rating" name="sort" value="rating" checked />
                                <label for="sort_rating">Top Rated</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" id="sort_name_asc" name="sort" value="name_asc" />
                                <label for="sort_name_asc">Alphabetical (A-Z)</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" id="sort_name_desc" name="sort" value="name_desc" />
                                <label for="sort_name_desc">Alphabetical (Z-A)</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" id="sort_price_asc" name="sort" value="price_asc" />
                                <label for="sort_price_asc">Price (Low to High)</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" id="sort_price_desc" name="sort" value="price_desc" />
                                <label for="sort_price_desc">Price (High to Low)</label>
                            </div>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3><i class="fas fa-tag"></i> Service Type</h3>
                        <div class="filter-options">
                            <div class="filter-option">
                                <input type="radio" id="service_all" name="service" value="all" checked />
                                <label for="service_all">All Services</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" id="service_humble" name="service" value="humble" />
                                <label for="service_humble">Humble</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" id="service_hype" name="service" value="hype" />
                                <label for="service_hype">Hype</label>
                            </div>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3><i class="fas fa-euro-sign"></i> Price Range</h3>
                        <div class="filter-options">
                            <div class="filter-option">
                                <input type="radio" id="price_all" name="price" value="all" checked />
                                <label for="price_all">All Prices</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" id="price_0-5" name="price" value="0-5" />
                                <label for="price_0-5">€0 - €5</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" id="price_5-10" name="price" value="5-10" />
                                <label for="price_5-10">€5 - €10</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" id="price_10-15" name="price" value="10-15" />
                                <label for="price_10-15">€10 - €15</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" id="price_15-20" name="price" value="15-20" />
                                <label for="price_15-20">€15 - €20</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" id="price_20-25" name="price" value="20-25" />
                                <label for="price_20-25">€20 - €25</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" id="price_25+" name="price" value="25+" />
                                <label for="price_25+">€25+</label>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Results Section -->
                <section class="results" id="resultsContainer">
                    <?php if (empty($cardsData)): ?>
                        <div class="no-results">
                            <h3>No services available at the moment</h3>
                            <p>Please check back later</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cardsData as $card): ?>
                            <a href="<?php echo $card['link']; ?>" class="card" data-service-type="<?php echo strtolower($card['service_type']); ?>">
                                <div class="card-content">
                                    <span class="service-type-tag"><?php echo $card['service_type']; ?></span>
                                    <h3 class="service-title"><?php echo $card['service_name']; ?></h3>
                                    <p class="service-description"><?php echo $card['description'] ?: 'No description available'; ?></p>
                                    <div class="card-footer">
                                        <div class="service-price">€<?php echo $card['price']; ?></div>
                                        <div class="service-meta">
                                            <span class="company-name" title="<?php echo $card['company_name']; ?>">
                                                <?php echo $card['company_name']; ?>
                                            </span>
                                            <div class="rating-badge">
                                                <div class="rating-stars" aria-label="Rating: <?php echo $card['rating']; ?> out of 5">
                                                    <?php
                                                    $rating = min(5, max(0, intval($card['rating'])));
                                                    for ($i = 1; $i <= 5; $i++):
                                                    ?>
                                                        <i class="fas fa-star<?php echo $i > $rating ? ' far' : ''; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/../../footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            const searchIcon = document.querySelector('.search-icon');
            const cards = document.querySelectorAll('.card');

            searchInput.addEventListener('input', filterCards);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') filterCards();
            });
            searchIcon.addEventListener('click', filterCards);

            document.querySelectorAll('input[name="sort"]').forEach(radio => {
                radio.addEventListener('change', filterCards);
            });

            document.querySelectorAll('input[name="service"]').forEach(radio => {
                radio.addEventListener('change', filterCards);
            });

            document.querySelectorAll('input[name="price"]').forEach(radio => {
                radio.addEventListener('change', filterCards);
            });

            function filterCards() {
                const searchQuery = searchInput.value.trim().toLowerCase();
                const serviceValue = document.querySelector('input[name="service"]:checked').value;
                const priceValue = document.querySelector('input[name="price"]:checked').value;

                cards.forEach(card => {
                    const cardName = card.querySelector('.service-title').textContent.toLowerCase();
                    const cardDescription = card.querySelector('.service-description').textContent.toLowerCase();
                    const cardCompany = card.querySelector('.company-name').textContent.toLowerCase();
                    const cardType = card.dataset.serviceType;
                    const cardPrice = parseFloat(card.querySelector('.service-price').textContent.replace('€', ''));

                    // Search filter
                    const matchesSearch = !searchQuery ||
                        cardName.includes(searchQuery) ||
                        cardDescription.includes(searchQuery) ||
                        cardCompany.includes(searchQuery);

                    // Service type filter
                    const matchesService = serviceValue === 'all' || cardType === serviceValue;

                    // Price filter
                    let matchesPrice = true;
                    if (priceValue !== 'all') {
                        switch (priceValue) {
                            case '0-5':
                                matchesPrice = cardPrice >= 0 && cardPrice <= 5;
                                break;
                            case '5-10':
                                matchesPrice = cardPrice > 5 && cardPrice <= 10;
                                break;
                            case '10-15':
                                matchesPrice = cardPrice > 10 && cardPrice <= 15;
                                break;
                            case '15-20':
                                matchesPrice = cardPrice > 15 && cardPrice <= 20;
                                break;
                            case '20-25':
                                matchesPrice = cardPrice > 20 && cardPrice <= 25;
                                break;
                            case '25+':
                                matchesPrice = cardPrice > 25;
                                break;
                        }
                    }

                    // Show/hide card based on filters
                    if (matchesSearch && matchesService && matchesPrice) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });

                // sorting
                const sortValue = document.querySelector('input[name="sort"]:checked').value;
                const resultsContainer = document.getElementById('resultsContainer');
                const cardsArray = Array.from(cards).filter(card => card.style.display !== 'none');

                cardsArray.sort((a, b) => {
                    const aName = a.querySelector('.service-title').textContent;
                    const bName = b.querySelector('.service-title').textContent;
                    const aPrice = parseFloat(a.querySelector('.service-price').textContent.replace('€', ''));
                    const bPrice = parseFloat(b.querySelector('.service-price').textContent.replace('€', ''));
                    const aRating = parseInt(a.querySelector('.rating-stars').getAttribute('aria-label').match(/\d+/)[0]);
                    const bRating = parseInt(b.querySelector('.rating-stars').getAttribute('aria-label').match(/\d+/)[0]);

                    switch (sortValue) {
                        case 'rating':
                            return bRating - aRating;
                        case 'name_asc':
                            return aName.localeCompare(bName);
                        case 'name_desc':
                            return bName.localeCompare(aName);
                        case 'price_asc':
                            return aPrice - bPrice;
                        case 'price_desc':
                            return bPrice - aPrice;
                        default:
                            return 0;
                    }
                });

                // Re-append sorted cards
                cardsArray.forEach(card => {
                    resultsContainer.appendChild(card);
                });

                // Show no results message if all cards are hidden
                const visibleCards = Array.from(cards).filter(card => card.style.display !== 'none');
                const noResults = document.querySelector('.no-results');

                if (visibleCards.length === 0) {
                    if (!noResults) {
                        const noResultsDiv = document.createElement('div');
                        noResultsDiv.className = 'no-results';
                        noResultsDiv.innerHTML = `
                            <h3>No services found matching your criteria</h3>
                            <p>Try adjusting your search or filters</p>
                        `;
                        resultsContainer.innerHTML = '';
                        resultsContainer.appendChild(noResultsDiv);
                    }
                } else if (noResults) {
                    noResults.remove();
                }
            }
        });
    </script>
</body>

</html>