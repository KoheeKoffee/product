<?php
session_start();
$host = "localhost";
$username = "root";
$password = "";
$dbname = "product_db";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$logged_in_user = $_SESSION['first_name'] . " " . $_SESSION['last_name'];

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$message = $_SESSION['message'] ?? "";
$popupType = $_SESSION['popupType'] ?? "";
$showPopup = !empty($message);
unset($_SESSION['message'], $_SESSION['popupType']);

if (isset($_POST["submit"])) {
    $product_name = $_POST["product_name"];
    $category = $_POST["category"];
    $price = $_POST["price"];
    $stock = $_POST["stock"];
    $supplier = $_POST["supplier"];
    $details = $_POST["details"];

    if (empty($product_name) || empty($category) || empty($price) || empty($stock) || empty($supplier) || empty($details)) {
        $_SESSION['message'] = "Please fill in all fields.";
        $_SESSION['popupType'] = "error";
        $showPopup = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (!is_numeric($price) || !is_numeric($stock)) {
        $_SESSION['message'] = "
        <p>I know you put the name of the number but I can't just prove it.</p>
        <img src='https://media1.tenor.com/m/x-YvNUa0UPQAAAAd/james-doakes-james-doakes-sus-face.gif'alt='GIF' width='220'>";
        $_SESSION['popupType'] = "error";
        $showPopup = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        try {
            $sql = "INSERT INTO products (product_name, category, price, stock, supplier, details)
                    VALUES (:product_name, :category, :price, :stock, :supplier, :details)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':supplier', $supplier);
            $stmt->bindParam(':details', $details);
            $stmt->execute();

            $_SESSION['message'] = "<h3>Product added successfully!</h3>
            <img src='https://tiermaker.com/images/template_images/2022/782255/all-genshin-impact-emotes-stickers-40-782255/110praise.png'
                alt='IMG' width='250'>
";
            
            $_SESSION['popupType'] = "success";
            $showPopup = true;
            header("Location: " . $_SERVER['PHP_SELF']);
        exit();
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['popupType'] = "error";
            $showPopup = true;
            header("Location: " . $_SERVER['PHP_SELF']);
        exit();
        }
    }
}
if (isset($_POST['update'])) {
    $id = $_POST['update_id'];
    $name = $_POST['product_name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $supplier = $_POST['supplier'];
    $details = $_POST['details'];
     $columnCheck = $conn->prepare("SHOW COLUMNS FROM products LIKE 'updated_at'");
    $columnCheck->execute();
    $columnExists = $columnCheck->fetch();
    if (!$columnExists) {
        $conn->exec("ALTER TABLE products ADD COLUMN updated_at DATETIME NULL");
    }
    $stmt = $conn->prepare("UPDATE products SET 
        product_name = :name,
        category = :category,
        price = :price,
        stock = :stock,
        supplier = :supplier,
        details = :details,
        updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':name' => $name,
        ':category' => $category,
        ':price' => $price,
        ':stock' => $stock,
        ':supplier' => $supplier,
        ':details' => $details,
        ':id' => $id
    ]);

    $_SESSION['message'] = "Product updated successfully!";
    $_SESSION['popupType'] = "success";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['delete'])) {
    $conn->prepare("DELETE FROM products WHERE id=?")->execute([$_POST['id']]);
    $_SESSION['message'] = "Product deleted!";
    $_SESSION['popupType'] = "deleted";
    $showPopup = true;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$products = [];
try {
    $result = $conn->query("SELECT * FROM products");
    $products = $result->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error loading records: " . $e->getMessage();
    $popupType = "error";
}

?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Product Page</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="background-overlay"></div>
<h3 style="color:white; text-align:left; margin-right:20px;">
  Welcome, <?php echo htmlspecialchars($logged_in_user); ?> |
  <a href="logout.php" style="color:red;">Logout</a>
</h3>
<h2>Product List</h2>
<div class="form-container">
    <form method="POST">
        <label>Product Name:</label>
        <input type="text" name="product_name">

        <label>Category:</label>
        <select name="category" class="drop-down">
            <option value="">Select a Category</option>
            <option value="Electronic">Electronic</option>
            <option value="Clothing">Clothing</option>
            <option value="Home & Kitchen">Home & Kitchen</option>
            <option value="Sports">Sports</option>
            <option value="Books">Books</option>
            <option value="Essential">Essential</option>
        </select>

        <label>Price:</label>
        <input type="text" name="price">

        <label>Stock:</label>
        <input type="text" name="stock">

        <label>Supplier:</label>
        <input type="text" name="supplier">

        <label>Description:</label>
        <input type="text" name="details">

        <button type="submit" name="submit">Add Product</button>
    </form>
</div>

<h2>List of Products</h2>
<?php $edit_id = $_POST['edit'] ?? null; ?>
<?php if (count($products) > 0): ?>
<div class="table-container">
<table>
    <tr>
        <th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Supplier</th><th>Description</th><th>Created At</th><th>Updated At</th><th>UPDATE</th>
    </tr>
    <?php foreach ($products as $p): ?>
    <tr>
        <form method="POST">
        <td><?php echo $p['id']; ?></td>

        <?php if ($edit_id == $p['id']): ?>
            <td><input type="text" name="product_name" value="<?php echo htmlspecialchars($p['product_name']); ?>"></td>
            <td>
                <select name="category">
                    <option value="Electronic" <?php if($p['category'] == 'Electronic') echo 'selected'; ?>>Electronic</option>
                    <option value="Clothing" <?php if($p['category'] == 'Clothing') echo 'selected'; ?>>Clothing</option>
                    <option value="Home & Kitchen" <?php if($p['category'] == 'Home & Kitchen') echo 'selected'; ?>>Home & Kitchen</option>
                    <option value="Sports" <?php if($p['category'] == 'Sports') echo 'selected'; ?>>Sports</option>
                    <option value="Books" <?php if($p['category'] == 'Books') echo 'selected'; ?>>Books</option>
                    <option value="Essential" <?php if($p['category'] == 'Essential') echo 'selected'; ?>>Essential</option>
                </select>
            </td>
            <td><input type="text" name="price" value="<?php echo $p['price']; ?>"></td>
            <td><input type="text" name="stock" value="<?php echo $p['stock']; ?>"></td>
            <td><input type="text" name="supplier" value="<?php echo htmlspecialchars($p['supplier']); ?>"></td>
            <td><input type="text" name="details" value="<?php echo htmlspecialchars($p['details']); ?>"></td>
            <td><?php echo htmlspecialchars($p['created_at']); ?></td>
            <td><?php echo htmlspecialchars($p['updated_at']); ?></td>
            <td>
                <input type="hidden" name="update_id" value="<?php echo $p['id']; ?>">
                <button type="submit" name="update">Save</button>
                <button type="submit">Cancel</button>
            </td>
        </form>
        <?php else: ?>
            <td><?php echo htmlspecialchars($p['product_name']); ?></td>
            <td><?php echo htmlspecialchars($p['category']); ?></td>
            <td><?php echo $p['price']; ?></td>
            <td><?php echo $p['stock']; ?></td>
            <td><?php echo htmlspecialchars($p['supplier']); ?></td>
            <td><?php echo htmlspecialchars($p['details']); ?></td>
            <td><?php echo htmlspecialchars($p['created_at']); ?></td>
            <td><?php echo htmlspecialchars($p['updated_at']); ?></td>
            <td>
                <form method="POST" style="display:inline;">
            <input type="hidden" name="edit" value="<?= $p['id'] ?>">
            <button type="submit">Edit</button>
        </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Do you wish to delete this product?');">
                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                    <button type="submit" name="delete">Delete</button>
                </form>
            </td>
        <?php endif; ?>
        </form>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php else: ?>
<p style="text-align:center; color: white;">No products found.</p>
<?php endif; ?>

<div class="overlay" id="popupOverlay">
    <div class="popup <?php echo $popupType; ?>">
        <?php echo $message; ?>
        <button class="close-btn" onclick="closePopup()">Close</button>
    </div>
</div>
<script>
function closePopup() {
    document.getElementById("popupOverlay").style.display = "none";
}
<?php if ($showPopup): ?>
document.getElementById("popupOverlay").style.display = "flex";
<?php endif; ?>
</script>
</body>
</html>
