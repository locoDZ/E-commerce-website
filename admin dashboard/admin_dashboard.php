<?php
session_start();

// Redirect to login page if user is not privileged
if (!isset($_SESSION['is_privileged']) || $_SESSION['is_privileged'] != 1) {
    header("Location: login.php");
    exit();
}

// Handle logout request
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../login and regestration/login.php");
    exit();
}

require_once '../user_db_config.php';

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Add new item
                $name = $_POST['name'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $quantity = $_POST['quantity'];
                $discount = $_POST['discount'] ?: null;

                $target_dir = "uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
                $unique_filename = uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $unique_filename;

                $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
                if (in_array($file_extension, $allowed_types)) {
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $stmt = $conn->prepare("INSERT INTO items (name, description, price, discount, quantity, image_path) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$name, $description, $price, $discount, $quantity, $target_file]);
                        $message = "Item added successfully";
                    } else {
                        $message = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                }
                break;

            case 'update':
                // Update existing item
                $id = $_POST['id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $quantity = $_POST['quantity'];
                $discount = $_POST['discount'] ?: null;

                if (!empty($_FILES['image']['name'])) {
                    $target_dir = "uploads/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $target_file = $target_dir . basename($_FILES["image"]["name"]);

                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $stmt = $conn->prepare("UPDATE items SET name=?, description=?, price=?, quantity=?, discount=?, image_path=? WHERE id=?");
                        $stmt->execute([$name, $description, $price, $quantity, $discount, $target_file, $id]);
                    }
                } else {
                    $stmt = $conn->prepare("UPDATE items SET name=?, description=?, price=?, quantity=?, discount=? WHERE id=?");
                    $stmt->execute([$name, $description, $price, $quantity, $discount, $id]);
                }
                $message = "Item updated successfully";
                break;

            case 'delete':
                // Delete item
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Item deleted successfully";
                break;

            case 'update_privilege':
                // Update user privilege
                $user_id = $_POST['user_id'];
                $is_privileged = $_POST['is_privileged'];
                $stmt = $conn->prepare("UPDATE users SET is_privileged = ? WHERE id = ?");
                $stmt->execute([$is_privileged, $user_id]);
                $message = "User privilege updated successfully";
                break;
        }
    }
}

// Fetch items and users from the database
$stmt = $conn->query("SELECT * FROM items");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $conn->query("SELECT id, username, email, is_privileged FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .header-container {
            background-color: #4CAF50;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
        }

        .logout-btn {
            background-color: #ff4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .logout-btn:hover {
            background-color: #cc0000;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <h1>Admin Dashboard</h1>
        <div class="logout-container">
            <form method="POST" style="margin: 0;">
                <button type="submit" name="logout" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>
    <div class="container">
        <div class="forum-container">
            <h2>Add New Item</h2>
            <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Price:</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
                <div class="form-group">
                    <label>Discount:</label>
                    <input type="number" step="0.01" name="discount" min="0" max="100">
                </div>
                <div class="form-group">
                    <label>Quantity:</label>
                    <input type="number" name="quantity" required>
                </div>
                <div class="form-group">
                    <label>Image:</label>
                    <input type="file" name="image" required>
                </div>
                <button type="submit">Add Item</button>
            </form>
        </div>
        <div class="sid-container">
            <div class="item-container">
                <h2>Item List</h2>
                <table>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Discount</th>
                        <th>Quantity</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($item['image_path']); ?>" height="50"></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                        <td>$<?php echo htmlspecialchars($item['price']); ?></td>
                        <td><?php echo $item['discount'] ? htmlspecialchars('$'.$item['discount'])  : '-'; ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td>
                            <button onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <div id="editModal" class="modal" style="display:none;">
                <div class="modal-content">
                    <h2>Edit Item</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="form-group">
                            <label>Name:</label>
                            <input type="text" name="name" id="edit-name" required>
                        </div>
                        <div class="form-group">
                            <label>Description:</label>
                            <textarea name="description" id="edit-description" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Price:</label>
                            <input type="number" step="0.01" name="price" id="edit-price" required>
                        </div>
                        <div class="form-group">
                            <label>Discount:</label>
                            <input type="number" step="0.01" name="discount" id="edit-discount" min="0" max="100">
                        </div>
                        <div class="form-group">
                            <label>Quantity:</label>
                            <input type="number" name="quantity" id="edit-quantity" required>
                        </div>
                        <div class="form-group">
                            <label>Image:</label>
                            <input type="file" name="image">
                            <p>Leave empty to keep current image</p>
                        </div>
                        <button type="submit">Update Item</button>
                        <button type="button" onclick="closeModal()">Cancel</button>
                    </form>
                </div>
            </div>
            <div class="user-container">
                <h2>User Management</h2>
                <table>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Privilege Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['is_privileged'] ? 'Admin' : 'User'; ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="update_privilege">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="is_privileged" value="<?php echo $user['is_privileged'] ? '0' : '1'; ?>">
                                <button type="submit" onclick="return confirm('Are you sure you want to change this user\'s privileges?')">
                                    <?php echo $user['is_privileged'] ? 'Remove Admin' : 'Make Admin'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <script>
            function editItem(item) {
                document.getElementById('editModal').style.display = 'block';
                document.getElementById('edit-id').value = item.id;
                document.getElementById('edit-name').value = item.name;
                document.getElementById('edit-description').value = item.description;
                document.getElementById('edit-price').value = item.price;
                document.getElementById('edit-quantity').value = item.quantity;
            }

            function closeModal() {
                document.getElementById('editModal').style.display = 'none';
            }
        </script>
    </div>
</body>
</html>
