<?php
session_start();
include('assets/condb/condb.php');

if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0 && isset($_POST['totalPrice'])) {
    $totalPrice = $_POST['totalPrice']; // Total price including shipping

    if (!isset($_SESSION['customer_id'])) {
        echo "<script>
                alert('กรุณาเข้าสู่ระบบก่อนทำการสั่งซื้อ');
                window.location.href = 'login.php';
              </script>";
        exit();
    }

    try {
        $conn->beginTransaction();

        // Calculate total product price from the cart
        $totalProductPrice = 0;
        foreach ($_SESSION['cart'] as $item) {
            $totalProductPrice += $item['price'] * $item['quantity'];
        }

        // Insert order with initial payment status set to 0 (pending)
        $sqlOrder = "INSERT INTO tb_orders (customer_id, total_price, total_product_price, order_date, order_status) 
                     VALUES (:customerId, :totalPrice, :totalProductPrice, NOW(), 0)";
        $stmtOrder = $conn->prepare($sqlOrder);
        $customerId = $_SESSION['customer_id']; // Use customer_id from session
        $stmtOrder->bindParam(':customerId', $customerId);
        $stmtOrder->bindParam(':totalPrice', $totalPrice); // Total price including shipping
        $stmtOrder->bindParam(':totalProductPrice', $totalProductPrice); // Total product price
        $stmtOrder->execute();

        $orderId = $conn->lastInsertId();

        // Prepare insert for order details
        $sqlOrderDetails = "INSERT INTO tb_order_details (order_id, product_id, quantity, price) 
                            VALUES (:orderId, :productId, :quantity, :price)";
        $stmtOrderDetails = $conn->prepare($sqlOrderDetails);

        foreach ($_SESSION['cart'] as $item) {
            // Insert order details
            $stmtOrderDetails->bindParam(':orderId', $orderId);
            $stmtOrderDetails->bindParam(':productId', $item['product_id']);
            $stmtOrderDetails->bindParam(':quantity', $item['quantity']);
            $stmtOrderDetails->bindParam(':price', $item['price']);
            $stmtOrderDetails->execute();

            // Reduce stock quantity in products table
            $sqlUpdateStock = "UPDATE products SET stockQty = stockQty - :quantity WHERE product_id = :productId";
            $stmtUpdateStock = $conn->prepare($sqlUpdateStock);
            $stmtUpdateStock->bindParam(':quantity', $item['quantity']);
            $stmtUpdateStock->bindParam(':productId', $item['product_id']);
            $stmtUpdateStock->execute();
        }

        $conn->commit();

        // Set session variable to show success message
        $_SESSION['order_success'] = true;

        unset($_SESSION['cart']);

        header("Location: cart_history.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        echo "การสั่งซื้อไม่สำเร็จ: " . $e->getMessage();
    }
} else {
    echo "ตะกร้าสินค้าว่างหรือไม่มีข้อมูลราคา";
}
?>
