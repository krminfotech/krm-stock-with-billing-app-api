<?php
// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

// Include database connection
require '../db.php';

// Retrieve POST data
$postdata = file_get_contents("php://input");

// Check if POST data is not empty
if (isset($postdata) && !empty($postdata)) {
    // Decode JSON data
    $request = json_decode($postdata, true);

    // Extract data from JSON
    $customerId = $request['customerId'];
    $paymentModeId = $request['paymentModeId'];
    $paymentTypeId = $request['paymentTypeId'];
    $productsToSell = $request['products']; // Array of products to sell

    // Function to sell products to a customer, update stock, and create invoice and customer invoiced product records
    function sellProductsToCustomer($customerId, $paymentModeId, $paymentTypeId, $productsToSell) {
        global $connection;

        // Begin transaction
        mysqli_begin_transaction($connection);

        try {
            // Create invoice record
            $invoiceSql = "INSERT INTO invoice (customer_id, payment_mode_id, payment_type_id) 
                           VALUES ($customerId, $paymentModeId, $paymentTypeId)";
            mysqli_query($connection, $invoiceSql);
            $invoiceId = mysqli_insert_id($connection);

            // Iterate over each product to sell
            foreach ($productsToSell as $product) {
                $productId = $product['productId'];
                $quantitySold = $product['quantity'];

                // Check if the product is available in stock
                $sql = "SELECT current_available_qty FROM product WHERE product_id = $productId";
                $result = mysqli_query($connection, $sql);
                $row = mysqli_fetch_assoc($result);

                if ($row && $row['current_available_qty'] >= $quantitySold) {
                    // Sell the product and update stock quantity
                    $newQty = $row['current_available_qty'] - $quantitySold;
                    $updateSql = "UPDATE product SET current_available_qty = $newQty WHERE product_id = $productId";
                    mysqli_query($connection, $updateSql);

                    // Create customer invoiced product record
                    $customerInvoicedProductSql = "INSERT INTO customer_invoiced_product (invoice_id, product_id, quantity) 
                                                   VALUES ($invoiceId, $productId, $quantitySold)";
                    mysqli_query($connection, $customerInvoicedProductSql);
                } else {
                    // Rollback transaction and return out of stock message
                    mysqli_rollback($connection);
                    http_response_code(422);
                    $response = [
                        'error' => "Product with ID $productId is out of stock."
                    ];
                    echo json_encode($response);
                    return; // Stop further processing
                }
            }

            // Commit transaction
            mysqli_commit($connection);

            // Return success message
            http_response_code(200);
            $response = [
                'message' => "Products sold successfully."
            ];
            echo json_encode($response);
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($connection);

            // Return error message
            http_response_code(500);
            $response = [
                'error' => "An error occurred while processing the transaction: " . $e->getMessage()
            ];
            echo json_encode($response);
        }
    }

    // Example usage of the function
    sellProductsToCustomer($customerId, $paymentModeId, $paymentTypeId, $productsToSell);
} else {
    // If POST data is empty
    http_response_code(400); // Bad request
    $response = [
        'error' => "Invalid request."
    ];
    echo json_encode($response);
}

// Close database connection
mysqli_close($connection);
?>
