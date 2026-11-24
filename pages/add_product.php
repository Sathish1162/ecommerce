
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../includes/db.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $imagePath = null;

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message = "Invalid request. Please try again.";
        $messageType = "error";
    } elseif ($name === "" || !is_numeric($price) || (float)$price <= 0) {
        $message = "Enter a product name and a valid price.";
        $messageType = "error";
    } else {
        try {
            // Process optional product image
            if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException("Image upload failed.");
                }

                if ($_FILES["image"]["size"] > 2 * 1024 * 1024) {
                    throw new RuntimeException("Image must be smaller than 2 MB.");
                }

                $allowedTypes = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $fileInfo->file($_FILES["image"]["tmp_name"]);

                if (!isset($allowedTypes[$mimeType])) {
                    throw new RuntimeException("Only JPG, PNG and WEBP images are allowed.");
                }

                $uploadDirectory = __DIR__ . "/../images/";

                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0755, true);
                }

                $fileName = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mimeType];
                $destination = $uploadDirectory . $fileName;

                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $destination)) {
                    throw new RuntimeException("Could not save the uploaded image.");
                }

                $imagePath = "images/" . $fileName;
            }

            $stmt = $conn->prepare(
                "INSERT INTO products (name, price, description, image)
                 VALUES (:name, :price, :description, :image)"
            );

            $stmt->execute([
                ":name" => $name,
                ":price" => $price,
                ":description" => $description,
                ":image" => $imagePath
            ]);

            $message = "Product added successfully!";
            $messageType = "success";

        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            $messageType = "error";
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = "Something went wrong while saving the product.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | My Store</title>
    <link rel="stylesheet" href="../css/add_product.css">
</head>
<body>

    <div class="product-form-container">
        <a href="../index.php" class="back-link">← Back to Home</a>

        <h1>Add a Product</h1>
        <p class="subtitle">Add a new item to your store.</p>

        <?php if ($message !== ""): ?>
            <div class="message <?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
            >

            <label for="name">Product Name</label>
            <input
                type="text"
                id="name"
                name="name"
                maxlength="255"
                placeholder="Enter product name"
                required
                value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
            >

            <label for="price">Price (₹)</label>
            <input
                type="number"
                id="price"
                name="price"
                min="0.01"
                step="0.01"
                placeholder="Enter product price"
                required
                value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
            >

            <label for="description">Description</label>
            <textarea
                id="description"
                name="description"
                rows="4"
                placeholder="Describe your product"
            ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>

            <label for="image">Product Image (optional)</label>
            <input
                type="file"
                id="image"
                name="image"
                accept=".jpg,.jpeg,.png,.webp"
            >
            <small>JPG, PNG or WEBP. Maximum size: 2 MB.</small>

            <button type="submit">Add Product</button>
        </form>
    </div>

</body>
</html>
