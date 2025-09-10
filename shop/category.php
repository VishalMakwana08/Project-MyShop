<?php
// include 'check_internet.php';
require_once '../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

$shop_id = intval($_SESSION['shop_id']);
$success = $error = "";

// Show and clear previous session messages
if (isset($_SESSION['category_success'])) {
    $success = $_SESSION['category_success'];
    unset($_SESSION['category_success']);
}
if (isset($_SESSION['category_error'])) {
    $error = $_SESSION['category_error'];
    unset($_SESSION['category_error']);
}

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);

    if ($name === "") {
        $_SESSION['category_error'] = "❌ Category name is required.";
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM categories WHERE shop_id = ? AND name = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("is", $shop_id, $name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                $_SESSION['category_error'] = "⚠️ Category already exists.";
            } else {
                $insert_stmt = $conn->prepare("INSERT INTO categories (shop_id, name) VALUES (?, ?)");
                if ($insert_stmt) {
                    $insert_stmt->bind_param("is", $shop_id, $name);
                    if ($insert_stmt->execute()) {
                        $_SESSION['category_success'] = "✅ Category added successfully.";
                    } else {
                        $_SESSION['category_error'] = "❌ Failed to add category: " . $insert_stmt->error;
                    }
                    $insert_stmt->close();
                } else {
                    $_SESSION['category_error'] = "❌ Database error preparing insert statement: " . $conn->error;
                }
            }
            $check_stmt->close();
        } else {
            $_SESSION['category_error'] = "❌ Database error preparing check statement: " . $conn->error;
        }
    }
    header("Location: category.php");
    exit;
}

// Delete category
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND shop_id = ?");
    if ($delete_stmt) {
        $delete_stmt->bind_param("ii", $id, $shop_id);
        if ($delete_stmt->execute()) {
            $_SESSION['category_success'] = "🗑️ Category deleted successfully.";
        } else {
            $_SESSION['category_error'] = "❌ Failed to delete category: " . $delete_stmt->error;
        }
        $delete_stmt->close();
    } else {
        $_SESSION['category_error'] = "❌ Database error preparing delete statement: " . $conn->error;
    }
    header("Location: category.php");
    exit;
}

// Fetch categories with optional search
$cats = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

if ($search !== "") {
    $query_stmt = $conn->prepare("SELECT id, name FROM categories WHERE shop_id = ? AND name LIKE ? ORDER BY name");
    $like = "%" . $search . "%";
    $query_stmt->bind_param("is", $shop_id, $like);
} else {
    $query_stmt = $conn->prepare("SELECT id, name FROM categories WHERE shop_id = ? ORDER BY name");
    $query_stmt->bind_param("i", $shop_id);
}

if ($query_stmt) {
    $query_stmt->execute();
    $result = $query_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cats[] = $row;
    }
    $query_stmt->close();
} else {
    $error = "❌ Database error fetching categories: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories - RetailFlow POS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
       <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fade-in 0.5s ease-out forwards; }
        .fade-out { opacity: 0; transition: opacity 0.5s ease-out; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased text-gray-800 p-6 flex items-center justify-center">
    <div class="max-w-5xl w-full mx-auto bg-white p-6 sm:p-8 shadow-xl rounded-xl border border-gray-100 animate-fade-in">
        
        <!-- Header with buttons -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6">
            <h1 class="text-3xl font-extrabold text-indigo-700 mb-4 sm:mb-0">🏷️ Manage Categories</h1>
            <div class="flex gap-3">
 <a href="dashboard.php" class="inline-flex items-center justify-center bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg shadow-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition duration-200 ease-in-out">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                    Dashboard
                </a>
                <a href="logout.php" class="inline-flex items-center bg-red-500 text-white px-4 py-2 rounded-lg shadow hover:bg-red-600 transition">
                    Logout
                </a>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="bg-green-100 text-green-800 px-5 py-3 rounded-lg mb-5 flex items-center space-x-3 border border-green-200 shadow-sm" id="successMessage">
                <p class="font-medium"><?= $success ?></p>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-800 px-5 py-3 rounded-lg mb-5 flex items-center space-x-3 border border-red-200 shadow-sm" id="errorMessage">
                <p class="font-medium"><?= $error ?></p>
            </div>
        <?php endif; ?>

      
        <!-- Add category -->
<div class="mb-8 p-6 bg-gray-50 rounded-lg border border-gray-200 shadow-inner">
    <h2 class="text-xl font-semibold text-gray-700 mb-4">Add New Category</h2>
    <div class="flex flex-col sm:flex-row gap-3">
        <input id="newCategoryInput" placeholder="Enter new category name" class="flex-1 border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:border-blue-500 focus:ring-blue-500" required>
        <button id="addCategoryBtn" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:bg-blue-700">➕ Add</button>
    </div>
</div>


       <!-- Search bar -->
<!-- <div class="mb-8 flex items-center gap-3">
    <input type="text" id="searchInput" placeholder="Search categories..."
        class="flex-1 border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:border-indigo-500 focus:ring-indigo-500">
</div> -->
<div class="mb-8 flex items-center gap-3 relative">
    <input 
        type="text" 
        id="searchInput" 
        placeholder="Search categories..." 
        class="flex-1 border border-gray-300 rounded-md shadow-sm px-4 py-2 pr-10 focus:border-indigo-500 focus:ring-indigo-500"
    >
    <!-- Clear Button -->
    <button 
        type="button" 
        id="clearBtn" 
        class="absolute right-3 top-1/2 -translate-y-1/2 text-red-400 hover:text-red-600 hidden"
    >
        ✕
    </button>
</div>


        <!-- Category list -->
        <?php if (empty($cats)): ?>
            <div class="bg-gray-50 p-6 rounded-lg text-center text-gray-500 border border-gray-200 shadow-inner">
                <p>No categories found<?= $search ? " for search '$search'" : "" ?>. Try adding one.</p>
            </div>
        <?php else: ?>
            <div>
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Existing Categories</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($cats as $c): ?>
                        <div class="bg-white p-4 rounded-lg shadow border hover:shadow-lg transition">
                            <span class="block text-lg font-semibold text-gray-800 mb-3 truncate"><?= htmlspecialchars($c['name']) ?></span>
                            <a href="?delete=<?= intval($c['id']) ?>" onclick="return confirm('Delete category <?= htmlspecialchars($c['name']) ?>?')" 
                               class="inline-flex items-center text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-md text-sm">
                                🗑️ Delete
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <!-- <script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const categoriesContainer = document.querySelector('.grid');
    const newCategoryInput = document.getElementById('newCategoryInput');
    const addCategoryBtn = document.getElementById('addCategoryBtn');

    // Render categories in the grid
    function renderCategories(data) {
        categoriesContainer.innerHTML = "";
        if (data.length === 0) {
            categoriesContainer.innerHTML = `
                <div class="col-span-full bg-gray-50 p-6 rounded-lg text-center text-gray-500 border border-gray-200 shadow-inner">
                    <p>No categories found.</p>
                </div>`;
            return;
        }
        data.forEach(cat => {
            const div = document.createElement("div");
            div.className = "bg-white p-4 rounded-lg shadow border hover:shadow-lg transition";
            div.innerHTML = `
                <span class="block text-lg font-semibold text-gray-800 mb-3 truncate">${cat.name}</span>
                <a href="?delete=${cat.id}" 
                   onclick="return confirm('Delete category ${cat.name}?')" 
                   class="inline-flex items-center text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-md text-sm">
                   🗑️ Delete
                </a>`;
            categoriesContainer.appendChild(div);
        });
    }

    // Fetch categories
    async function fetchCategories(query = "") {
        const res = await fetch("search_category.php?q=" + encodeURIComponent(query));
        const data = await res.json();
        renderCategories(data);
    }

    // Initial load
    fetchCategories();

    // Real-time search
    searchInput.addEventListener("input", function () {
        fetchCategories(this.value);
    });

    // Add category via AJAX
    addCategoryBtn.addEventListener("click", async function () {
        const name = newCategoryInput.value.trim();
        if (!name) return;

        const formData = new FormData();
        formData.append("name", name);

        const res = await fetch("add_category.php", {
            method: "POST",
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            newCategoryInput.value = "";
            fetchCategories(searchInput.value); // refresh list
            alert("✅ Category added: " + data.name);
        } else {
            alert("❌ " + data.message);
        }
    });
});
</script> -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const clearBtn = document.getElementById('clearBtn');
    const categoriesContainer = document.querySelector('.grid');
    const newCategoryInput = document.getElementById('newCategoryInput');
    const addCategoryBtn = document.getElementById('addCategoryBtn');

    // Render categories
    function renderCategories(data) {
        categoriesContainer.innerHTML = "";
        if (data.length === 0) {
            categoriesContainer.innerHTML = `
                <div class="col-span-full bg-gray-50 p-6 rounded-lg text-center text-gray-500 border border-gray-200 shadow-inner">
                    <p>No categories found.</p>
                </div>`;
            return;
        }
        data.forEach(cat => {
            const div = document.createElement("div");
            div.className = "bg-white p-4 rounded-lg shadow border hover:shadow-lg transition";
            div.innerHTML = `
                <span class="block text-lg font-semibold text-gray-800 mb-3 truncate">${cat.name}</span>
                <a href="?delete=${cat.id}" 
                   onclick="return confirm('Delete category ${cat.name}?')" 
                   class="inline-flex items-center text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-md text-sm">
                   🗑️ Delete
                </a>`;
            categoriesContainer.appendChild(div);
        });
    }

    // Fetch categories
    async function fetchCategories(query = "") {
        const res = await fetch("search_category.php?q=" + encodeURIComponent(query));
        const data = await res.json();
        renderCategories(data);
    }

    // Initial load
    fetchCategories();

    // Real-time search
    searchInput.addEventListener("input", function () {
        fetchCategories(this.value);
        toggleClearButton();
    });

    // Clear button logic
    clearBtn.addEventListener("click", function () {
        searchInput.value = "";
        fetchCategories(); // reset list
        toggleClearButton();
        searchInput.focus();
    });

    function toggleClearButton() {
        clearBtn.classList.toggle("hidden", searchInput.value.trim() === "");
    }

    // Add category via AJAX
    addCategoryBtn.addEventListener("click", async function () {
        const name = newCategoryInput.value.trim();
        if (!name) return;

        const formData = new FormData();
        formData.append("name", name);

        const res = await fetch("add_category.php", {
            method: "POST",
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            newCategoryInput.value = "";
            fetchCategories(searchInput.value); // refresh list
            alert("✅ Category added: " + data.name);
        } else {
            alert("❌ " + data.message);
        }
    });
});
</script>


</body>
</html>
