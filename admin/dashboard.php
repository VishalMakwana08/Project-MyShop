<?php
// include 'check_internet.php';
// dashboard.php
// This file serves as the main admin dashboard.
// It includes an authentication check, and then the HTML, CSS, and JavaScript
// for displaying shops, contact requests, and unit management.

session_start(); // Start the session to manage login state

// Database connection details (!!! IMPORTANT: REPLACE WITH YOUR ACTUAL CREDENTIALS !!!)
// NOTE: This is your DATABASE NAME, which remains 'shop_management_db'
$servername = "localhost";
$username = "root"; // Example: Your MySQL username (e.g., 'root' for XAMPP default)
$password = "";     // Example: Your MySQL password (e.g., '' for XAMPP default)
$dbname = "shop_management_db"; // This refers to your actual MySQL database name

// --- Authentication Check (PHP) ---
// This runs every time dashboard.php is accessed.
// If the admin is not logged in, it redirects them to the login page.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to the login page. Adjust path if login.php is not in the same directory.
    // This path is relative to the current file (dashboard.php)
    header('Location: login.php');
    exit(); // Stop script execution after redirection
}

// Optionally, you can fetch admin user details here if needed for display
// For example: $loggedInAdminUsername = $_SESSION['admin_username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <!-- <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"> -->
      <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
    <style>
        /* Custom styles for responsiveness and layout */
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            height: 100vh; /* Set body height to full viewport height */
            overflow: hidden; /* Prevent body from scrolling, only content area will scroll */
            background-color: #f9fafb; /* Very light gray background */
        }
        #sidebar {
            width: 280px; /* Slightly wider sidebar */
            background-color: #2C3E50; /* Darker blue-gray for a professional look */
            color: #ecf0f1; /* Light text for contrast */
            padding-top: 2.5rem; /* More padding at top */
            flex-shrink: 0;
            box-shadow: 4px 0 10px rgba(0,0,0,0.2); /* More prominent shadow */
            transition: width 0.3s ease-in-out;
            display: flex; /* Use flex for internal layout */
            flex-direction: column;
            overflow-y: auto; /* Allow sidebar to scroll internally if its content overflows */
        }
        #sidebar.collapsed {
            width: 70px; /* Example for a collapsed state */
        }
        #content {
            flex-grow: 1;
            /* Removed direct padding to allow sticky header to manage top space */
            /* Instead, content will use flex column and its children will have padding */
            background-color: #ffffff; /* Clean white background for content */
            overflow-y: auto; /* THIS MAKES THE MAIN CONTENT SCROLLABLE */
            border-top-left-radius: 1rem; /* Rounded top-left corner for content */
            box-shadow: -4px 0 10px rgba(0,0,0,0.05); /* Subtle shadow on content side */
            display: flex; /* Use flex for internal layout of content area */
            flex-direction: column; /* Arrange content sections vertically */
        }
        .nav-link {
            color: #bdc3c7; /* Lighter gray text */
            padding: 1rem 1.75rem; /* More padding */
            display: flex;
            align-items: center;
            gap: 1rem; /* Increased gap for icons and text */
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
            border-radius: 0.5rem; /* More rounded corners */
            margin: 0 1.25rem 0.75rem 1.25rem; /* Adjusted margins */
            font-weight: 500; /* Medium font weight */
        }
        .nav-link:hover {
            background-color: #34495e; /* Darker blue-gray on hover */
            color: white;
        }
        .nav-link.active {
            background-color: #2ecc71; /* Emerald green for active link */
            color: white;
            font-weight: 600;
            box-shadow: inset 3px 0 0 #27ae60; /* Highlight border on active */
        }
        .shop-card, .contact-card { /* Removed .unit-card as it's no longer used */
            cursor: pointer;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border-radius: 0.75rem; /* More rounded corners */
            background-color: #ffffff;
            border: 1px solid #e5e7eb; /* Subtle border */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); /* Lighter default shadow */
            position: relative; /* For new badge positioning */
        }
        .shop-card:hover, .contact-card:hover { /* Removed .unit-card */
            transform: translateY(-5px); /* More pronounced lift */
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1); /* Stronger shadow on hover */
        }
        .card-header {
            background-color: #f9fafb; /* Very light gray header for cards */
            padding: 1.25rem 1.75rem; /* More padding */
            border-bottom: 1px solid #e2e8f0; /* Lighter border */
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
        }
        .card-body {
            padding: 1.75rem; /* More padding */
        }
        .table {
            width: 100%;
            border-collapse: collapse; /* Remove space between cells */
        }
        .table th, .table td {
            padding: 1rem 1.5rem; /* More padding in table cells */
            vertical-align: middle; /* Vertically align content */
            border-top: 1px solid #edf2f7; /* Lighter border */
        }
        .table thead th {
            border-bottom: 2px solid #cbd5e1; /* Lighter bottom border for header */
            background-color: #f3f4f6; /* Light header background */
            color: #374151; /* Darker text for headers */
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem; /* Slightly smaller font for headers */
            letter-spacing: 0.05em;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #ffffff; /* White stripe for tables */
        }
        .table-striped tbody tr:hover {
            background-color: #f0fdfa; /* Light teal on hover for table rows */
        }
        .btn-logout {
            background-color: #e74c3c; /* Red for logout */
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem; /* More rounded */
            font-weight: 600;
            transition: background-color 0.2s ease-in-out, transform 0.1s ease-in-out;
            border: none;
            width: calc(100% - 2.5rem); /* Adjust width to match nav-link padding */
            margin: 1.25rem;
            text-align: center;
            display: flex; /* For icon alignment */
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-logout:hover {
            background-color: #c0392b; /* Darker red on hover */
            transform: translateY(-2px);
        }
        .form-label {
            display: block;
            font-weight: 600; /* Bolder label */
            margin-bottom: 0.5rem;
            color: #1f2937; /* Darker label color */
        }
        .form-input, .form-textarea { /* Added form-textarea */
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db; /* Lighter border */
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); /* Subtle inner shadow */
        }
        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #2ecc71; /* Emerald green border on focus */
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.25); /* Emerald green shadow on focus */
        }
        .form-button {
            background-color: #2ecc71; /* Emerald green for add button */
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem; /* More rounded */
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out, transform 0.1s ease-in-out;
            border: none;
        }
        .form-button:hover {
            background-color: #27ae60; /* Darker emerald green on hover */
            transform: translateY(-2px);
        }
        .message-box {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem; /* More space below messages */
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .message-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* New sticky header for content area */
        .content-header {
            position: sticky;
            top: 0;
            background-color: #ffffff; /* White background to match content */
            padding: 2.5rem 2.5rem 1.5rem 2.5rem; /* Top/bottom padding, matches content side padding */
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); /* Subtle shadow */
            border-top-left-radius: 1rem; /* Match content area radius */
            border-top-right-radius: 1rem; /* Match content area radius */
            display: flex;
            flex-direction: column;
            gap: 1rem; /* Space between title and search bar */
            flex-shrink: 0; /* Prevent header from shrinking */
        }

        /* Adjust content below sticky header */
        .view-content-area {
            padding: 0 2.5rem 2.5rem 2.5rem; /* Apply side and bottom padding here */
            flex-grow: 1; /* Allow this area to take remaining space and scroll */
            overflow-y: auto; /* Ensure this area itself scrolls if content overflows */
        }

        /* New item highlight for cards */
        .new-item {
            border: 2px solid #3498db; /* Blue border for new items */
            box-shadow: 0 0 15px rgba(52, 152, 219, 0.3); /* Soft blue glow */
        }
        .new-item::before {
            content: 'NEW';
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background-color: #e74c3c; /* Red badge */
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            z-index: 1;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
                height: auto; /* Allow body to grow on small screens */
                overflow: visible; /* Allow body to scroll on small screens */
            }
            #sidebar {
                width: 100%;
                height: auto;
                padding-bottom: 10px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                position: static; /* Remove fixed position on small screens */
            }
            #sidebar .nav-column {
                flex-direction: row;
                justify-content: space-around;
                flex-wrap: wrap;
            }
            #sidebar .nav-item {
                flex: 1 1 auto; /* Allow items to grow and shrink */
                text-align: center;
            }
            #sidebar .nav-link {
                margin: 0.5rem auto; /* Center items */
                justify-content: center; /* Center content within link */
                padding: 0.75rem 1rem; /* Adjust padding for smaller screens */
                gap: 0.5rem;
            }
            .btn-logout {
                width: auto;
                margin: 0.5rem auto;
                padding: 0.75rem 1.25rem;
            }
            .content-header {
                padding: 1rem 1rem 0.75rem 1rem; /* Adjusted padding for small screens */
                border-top-left-radius: 0;
                border-top-right-radius: 0;
            }
            .view-content-area {
                padding: 0 1rem 1rem 1rem; /* Adjusted padding for small screens */
            }
        }
    </style>
</head>
<body>
    <div id="sidebar">
        <h4 class="text-center text-white text-xl font-semibold mb-6">Admin Panel</h4>
        <ul class="nav flex-column nav-column">
            <li class="nav-item">
                <a class="nav-link active" href="#" data-view="shops">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2H7a2 2 0 00-2 2v2m7-7h.01M7 11h.01M17 11h.01"></path></svg>
                    <span>Shops</span>
                    <span id="newShopsBadge" class="ml-auto px-2 py-0.5 text-xs font-semibold rounded-full bg-red-500 text-white hidden">0</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-view="contact-requests">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <span>Contact Requests</span>
                    <span id="newRequestsBadge" class="ml-auto px-2 py-0.5 text-xs font-semibold rounded-full bg-red-500 text-white hidden">0</span>
                </a>
            </li>
            <!-- Removed Unit Management nav item as requested -->
        </ul>
        <div class="mt-auto p-4"> <!-- Push logout to bottom -->
             <button id="logoutButton" class="btn-logout">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Logout
            </button>
        </div>
    </div>

    <div id="content">
        <!-- Main Content Header - Fixed and contains title, counts, and search -->
        <div class="content-header">
            <div class="flex justify-between items-center mb-4">
                <h2 id="contentTitle" class="text-3xl font-bold text-gray-800"></h2>
                <div class="flex space-x-4 text-gray-600 font-medium">
                    <span id="totalShopsCount" class="hidden">Total Shops: 0</span>
                    <span id="totalRequestsCount" class="hidden">Total Requests: 0</span>
                </div>
            </div>
            <!-- Search inputs will be conditionally displayed by JS -->
            <input type="text" id="shopSearchInput" placeholder="Search shops by name, email, or GST number..."
                   class="form-input w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500 hidden">
            <input type="text" id="contactSearchInput" placeholder="Search contact requests by sender, email, or subject..."
                   class="form-input w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500 hidden">
        </div>

        <!-- Main Content Area - Scrolls independently -->
        <div class="view-content-area">
            <!-- All Shops View -->
            <div id="shopsListView" class="view-section">
                <div id="shopsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Shop cards will be loaded here by JavaScript -->
                </div>
                <div id="noShopsMessage" class="text-center text-gray-500 mt-8 hidden">No shops registered yet.</div>
                <div id="noSearchResultsMessage" class="text-center text-gray-500 mt-8 hidden">No shops found matching your search.</div>
            </div>

            <!-- Shop Details View (hidden by default) -->
            <div id="shopDetailsView" class="view-section hidden">
                <button class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md back-button mb-6" onclick="showShopsList()">← Back to All Shops</button>
                <div class="bg-white rounded-lg shadow-md mb-8">
                    <div class="card-header">
                        <h3 id="detailShopName" class="text-xl font-semibold text-gray-800"></h3>
                    </div>
                    <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                        <p><strong>Owner:</strong> <span id="detailOwnerName"></span></p>
                        <p><strong>Email:</strong> <span id="detailOwnerEmail"></span></p>
                        <p><strong>Mobile:</strong> <span id="detailOwnerMobile"></span></p>
                        <p><strong>Address:</strong> <span id="detailAddress"></span></p>
                        <p><strong>Registration Date:</strong> <span id="detailRegistrationDate"></span></p>
                        <p><strong>GST Number:</strong> <span id="detailGstNumber"></span></p>
                        <p><strong>Shop License:</strong> <span id="detailShopLicense"></span></p>
                        <p><strong>Auto Discount:</strong> <span id="detailAutoDiscount"></span></p>
                        <p><strong>Show GST License:</strong> <span id="detailShowGstLicense"></span></p>
                        <!-- Add more shop details here -->
                    </div>
                </div>

                <h4 class="text-2xl font-semibold text-gray-800 mb-4">Contact Requests for this Shop</h4>
                <div id="shopContactRequestsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Shop-specific contact request cards will be loaded here by JavaScript -->
                </div>
                <div id="noShopContactRequests" class="text-center text-gray-500 py-4 hidden">No contact requests for this shop yet.</div>
            </div>

            <!-- All Contact Requests View -->
            <div id="allContactRequestsView" class="view-section hidden">
                <div id="allContactRequestsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- All contact request cards will be loaded here by JavaScript -->
                </div>
                <div id="noAllContactRequests" class="text-center text-gray-500 py-4 hidden">No contact requests found.</div>
                <div id="noAllContactSearchResults" class="text-center text-gray-500 mt-8 hidden">No contact requests found matching your search.</div>
            </div>

            <!-- Contact Request Details View (hidden by default) -->
            <div id="contactDetailsView" class="view-section hidden">
                <button class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md back-button mb-6" onclick="showAllContactRequests()">← Back to All Contact Requests</button>
                <div class="bg-white rounded-lg shadow-md mb-8">
                    <div class="card-header">
                        <h3 id="detailContactSubject" class="text-xl font-semibold text-gray-800"></h3>
                    </div>
                    <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                        <p><strong>Shop:</strong> <span id="detailContactShopName"></span></p>
                        <p><strong>Sender Name:</strong> <span id="detailContactOwnerName"></span></p>
                        <p><strong>Sender Email:</strong> <span id="detailContactOwnerEmail"></span></p>
                        <div class="md:col-span-2">
                            <p><strong>Message:</strong></p>
                            <p id="detailContactMessage" class="bg-gray-50 p-4 rounded-md mt-2 whitespace-pre-wrap"></p>
                        </div>
                        <p><strong>Date:</strong> <span id="detailContactDate"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
    // --- API Endpoints ---
    const API_BASE_URL = 'http://localhost:81/shop-management-system/admin/';
    const GET_ALL_SHOPS_API = API_BASE_URL + 'get_all_shops.php';
    const GET_SHOP_CONTACT_REQUESTS_API = API_BASE_URL + 'get_shop_contact_requests.php';
    const GET_ALL_CONTACT_REQUESTS_API = API_BASE_URL + 'get_all_contact_requests.php';
    const LOGOUT_API = API_BASE_URL + 'logout.php';
    const LOGIN_PAGE_URL = API_BASE_URL + 'login.php';

    // --- Global Data Stores ---
    let allShopsData = [];
    let allContactRequestsData = [];

    // --- Local Storage for "New" Tracking ---
    let viewedShopIds = new Set(JSON.parse(localStorage.getItem('viewedShopIds') || '[]'));
    let viewedRequestIds = new Set(JSON.parse(localStorage.getItem('viewedRequestIds') || '[]'));

    // --- DOM Elements ---
    const contentTitle = document.getElementById('contentTitle');
    const shopsListView = document.getElementById('shopsListView');
    const shopDetailsView = document.getElementById('shopDetailsView');
    const allContactRequestsView = document.getElementById('allContactRequestsView');
    const contactDetailsView = document.getElementById('contactDetailsView');

    const shopsContainer = document.getElementById('shopsContainer');
    const noShopsMessage = document.getElementById('noShopsMessage');
    const noSearchResultsMessage = document.getElementById('noSearchResultsMessage');

    const shopSearchInput = document.getElementById('shopSearchInput');
    const contactSearchInput = document.getElementById('contactSearchInput');

    const shopContactRequestsContainer = document.getElementById('shopContactRequestsContainer');
    const noShopContactRequestsMessage = document.getElementById('noShopContactRequests');

    const allContactRequestsContainer = document.getElementById('allContactRequestsContainer');
    const noAllContactRequestsMessage = document.getElementById('noAllContactRequests');
    const noAllContactSearchResults = document.getElementById('noAllContactSearchResults');

    const totalShopsCountSpan = document.getElementById('totalShopsCount');
    const totalRequestsCountSpan = document.getElementById('totalRequestsCount');

    const newShopsBadge = document.getElementById('newShopsBadge');
    const newRequestsBadge = document.getElementById('newRequestsBadge');

    const logoutButton = document.getElementById('logoutButton');

    // --- Utility ---
    function hideAllViews() {
        document.querySelectorAll('.view-section').forEach(v => v.classList.add('hidden'));
        shopSearchInput.classList.add('hidden');
        contactSearchInput.classList.add('hidden');
        totalShopsCountSpan.classList.add('hidden');
        totalRequestsCountSpan.classList.add('hidden');
    }

    function setActiveNavLink(dataView) {
        document.querySelectorAll('#sidebar .nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.dataset.view === dataView) link.classList.add('active');
        });
    }

    function updateNewCounts() {
        const newShopsCount = allShopsData.filter(shop => !viewedShopIds.has(String(shop.id))).length;
        newShopsBadge.textContent = newShopsCount;
        newShopsBadge.classList.toggle('hidden', newShopsCount === 0);

        const newRequestsCount = allContactRequestsData.filter(req => !viewedRequestIds.has(String(req.id))).length;
        newRequestsBadge.textContent = newRequestsCount;
        newRequestsBadge.classList.toggle('hidden', newRequestsCount === 0);
    }

    // --- Views ---
    function showShopsList() {
        hideAllViews();
        shopsListView.classList.remove('hidden');
        contentTitle.textContent = 'All Registered Shops';
        shopSearchInput.classList.remove('hidden');
        totalShopsCountSpan.classList.remove('hidden');
        setActiveNavLink('shops');
        loadShops(shopSearchInput.value);
    }

    function showShopDetails(shopId) {
        hideAllViews();
        shopDetailsView.classList.remove('hidden');
        contentTitle.textContent = 'Shop Details';
        loadShopDetails(shopId);
        loadContactRequestsForShop(shopId);

        viewedShopIds.add(String(shopId));
        localStorage.setItem('viewedShopIds', JSON.stringify([...viewedShopIds]));
        updateNewCounts();
    }

    function showAllContactRequests() {
        hideAllViews();
        allContactRequestsView.classList.remove('hidden');
        contentTitle.textContent = 'All Contact Requests';
        contactSearchInput.classList.remove('hidden');
        totalRequestsCountSpan.classList.remove('hidden');
        setActiveNavLink('contact-requests');
        loadAllContactRequests(contactSearchInput.value);
    }

    function showContactRequestDetails(requestId) {
        hideAllViews();
        contactDetailsView.classList.remove('hidden');
        contentTitle.textContent = 'Contact Request Details';
        loadContactDetails(requestId);

        viewedRequestIds.add(String(requestId));
        localStorage.setItem('viewedRequestIds', JSON.stringify([...viewedRequestIds]));
        updateNewCounts();
    }

    // --- Data Loaders ---
    async function loadShops(searchTerm = '') {
        try {
            const response = await fetch(GET_ALL_SHOPS_API);
            const data = await response.json();
            allShopsData = data.success ? data.shops : [];
        } catch (err) {
            console.error("Error fetching shops:", err);
            allShopsData = [];
        }

        // Filter
        let displayShops = allShopsData;
        if (searchTerm.trim()) {
            const term = searchTerm.toLowerCase();
            displayShops = displayShops.filter(shop =>
                shop.shop_name.toLowerCase().includes(term) ||
                shop.owner_email.toLowerCase().includes(term) ||
                (shop.gst_number && shop.gst_number.toLowerCase().includes(term))
            );
        }

        shopsContainer.innerHTML = '';
        if (displayShops.length) {
            displayShops.forEach(shop => {
                const isNew = !viewedShopIds.has(String(shop.id));
                shopsContainer.innerHTML += `
                    <div class="bg-white rounded-lg shadow-md p-6 shop-card ${isNew ? 'new-item':''}" onclick="showShopDetails(${shop.id})">
                        <h5 class="text-lg font-semibold">${shop.shop_name}</h5>
                        <p><strong>Owner:</strong> ${shop.owner_name}</p>
                        <p><strong>Email:</strong> ${shop.owner_email}</p>
                        <p class="text-sm">${shop.address.substring(0,70)}${shop.address.length>70?'...':''}</p>
                        <small>Registered: ${new Date(shop.registration_date).toLocaleDateString()}</small>
                    </div>
                `;
            });
        } else {
            (searchTerm ? noSearchResultsMessage : noShopsMessage).classList.remove('hidden');
        }
        totalShopsCountSpan.textContent = `Total Shops: ${allShopsData.length}`;
        updateNewCounts();
    }

    async function loadShopDetails(shopId) {
        const shop = allShopsData.find(s => Number(s.id) === Number(shopId));
        if (!shop) return;

        document.getElementById('detailShopName').textContent = shop.shop_name;
        document.getElementById('detailOwnerName').textContent = shop.owner_name;
        document.getElementById('detailOwnerEmail').textContent = shop.owner_email;
        document.getElementById('detailOwnerMobile').textContent = shop.owner_mobile;
        document.getElementById('detailAddress').textContent = shop.address;
        document.getElementById('detailRegistrationDate').textContent = new Date(shop.registration_date).toLocaleDateString();
        document.getElementById('detailGstNumber').textContent = shop.gst_number || 'N/A';
        document.getElementById('detailShopLicense').textContent = shop.shop_license || 'N/A';
        document.getElementById('detailAutoDiscount').textContent = shop.auto_discount ? 'Yes' : 'No';
        document.getElementById('detailShowGstLicense').textContent = shop.show_gst_license ? 'Yes' : 'No';
    }

    async function loadContactRequestsForShop(shopId) {
        try {
            const response = await fetch(`${GET_SHOP_CONTACT_REQUESTS_API}?shop_id=${shopId}`);
            const data = await response.json();
            shopContactRequestsContainer.innerHTML = '';
            if (data.success && data.contact_requests.length) {
                data.contact_requests.forEach(req => {
                    const isNew = !viewedRequestIds.has(String(req.id));
                    shopContactRequestsContainer.innerHTML += `
                        <div class="bg-white rounded-lg shadow-md p-6 contact-card ${isNew?'new-item':''}" onclick="showContactRequestDetails(${req.id})">
                            <h5 class="text-lg font-semibold">${req.subject}</h5>
                            <p><strong>Sender:</strong> ${req.owner_name}</p>
                            <p><strong>Email:</strong> ${req.owner_email}</p>
                            <p class="text-sm">${req.message.substring(0,70)}${req.message.length>70?'...':''}</p>
                            <small>Date: ${new Date(req.created_at).toLocaleDateString()}</small>
                        </div>
                    `;
                });
            } else {
                noShopContactRequestsMessage.classList.remove('hidden');
            }
        } catch (err) {
            console.error("Error loading shop requests:", err);
        }
    }

    async function loadAllContactRequests(searchTerm = '') {
        try {
            const response = await fetch(GET_ALL_CONTACT_REQUESTS_API);
            const data = await response.json();
            allContactRequestsData = data.success ? data.contact_requests : [];
        } catch (err) {
            console.error("Error fetching contact requests:", err);
            allContactRequestsData = [];
        }

        let display = allContactRequestsData;
        if (searchTerm.trim()) {
            const term = searchTerm.toLowerCase();
            display = display.filter(req =>
                req.owner_name.toLowerCase().includes(term) ||
                req.owner_email.toLowerCase().includes(term) ||
                req.subject.toLowerCase().includes(term) ||
                req.message.toLowerCase().includes(term)
            );
        }

        allContactRequestsContainer.innerHTML = '';
        if (display.length) {
            display.forEach(req => {
                const isNew = !viewedRequestIds.has(String(req.id));
                allContactRequestsContainer.innerHTML += `
                    <div class="bg-white rounded-lg shadow-md p-6 contact-card ${isNew?'new-item':''}" onclick="showContactRequestDetails(${req.id})">
                        <h5 class="text-lg font-semibold">${req.subject}</h5>
                        <p><strong>Sender:</strong> ${req.owner_name}</p>
                        <p><strong>Email:</strong> ${req.owner_email}</p>
                        <p class="text-sm">${req.message.substring(0,70)}${req.message.length>70?'...':''}</p>
                        <small>Date: ${new Date(req.created_at).toLocaleDateString()}</small>
                    </div>
                `;
            });
        } else {
            (searchTerm ? noAllContactSearchResults : noAllContactRequestsMessage).classList.remove('hidden');
        }
        totalRequestsCountSpan.textContent = `Total Requests: ${allContactRequestsData.length}`;
        updateNewCounts();
    }

    async function loadContactDetails(requestId) {
        const req = allContactRequestsData.find(r => Number(r.id) === Number(requestId));
        if (!req) return;
        document.getElementById('detailContactSubject').textContent = req.subject;
        document.getElementById('detailContactOwnerName').textContent = req.owner_name;
        document.getElementById('detailContactOwnerEmail').textContent = req.owner_email;
        document.getElementById('detailContactMessage').textContent = req.message;
        document.getElementById('detailContactDate').textContent = new Date(req.created_at).toLocaleDateString();
    }

    // --- Event Listeners ---
    document.querySelectorAll('#sidebar .nav-link').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const view = link.dataset.view;
            if (view === 'shops') showShopsList();
            if (view === 'contact-requests') showAllContactRequests();
        });
    });

    shopSearchInput.addEventListener('input', () => loadShops(shopSearchInput.value));
    contactSearchInput.addEventListener('input', () => loadAllContactRequests(contactSearchInput.value));

    logoutButton.addEventListener('click', async () => {
        if (!confirm("Are you sure you want to log out?")) return;
        await fetch(LOGOUT_API);
        window.location.href = LOGIN_PAGE_URL;
    });

    // --- Initial Load + Live Refresh ---
    document.addEventListener('DOMContentLoaded', async () => {
        await loadShops();
        await loadAllContactRequests();
        showShopsList();

        // 🔄 Live update every 15 seconds (adjustable)
        setInterval(() => {
            if (!shopsListView.classList.contains('hidden')) loadShops(shopSearchInput.value);
            if (!allContactRequestsView.classList.contains('hidden')) loadAllContactRequests(contactSearchInput.value);
        }, 15000);
    });
</script>

</body>
</html>
