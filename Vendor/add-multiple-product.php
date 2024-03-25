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
    $productsToUpdate = $request['products']; // Array of products to update

    // Function to update product quantities
    function updateProductQuantities($productsToUpdate) {
        global $connection;

        // Begin transaction
        mysqli_begin_transaction($connection);

        try {
            // Iterate over each product to update
            foreach ($productsToUpdate as $product) {
                $productId = $product['productId'];
                $quantityToAdd = $product['quantity'];

                // Retrieve current quantity of the product
                $getCurrentQuantitySql = "SELECT current_available_qty FROM product WHERE product_id = $productId";
                $result = mysqli_query($connection, $getCurrentQuantitySql);
                $row = mysqli_fetch_assoc($result);

                if ($row) {
                    // Calculate new quantity
                    $currentQuantity = $row['current_available_qty'];
                    $newQuantity = $currentQuantity + $quantityToAdd;

                    // Update product quantity
                    $updateQuantitySql = "UPDATE product SET current_available_qty = $newQuantity WHERE product_id = $productId";
                    mysqli_query($connection, $updateQuantitySql);
                } else {
                    // Product not found
                    http_response_code(404);
                    $response = [
                        'error' => "Product with ID $productId not found."
                    ];
                    echo json_encode($response);
                    return;
                }
            }

            // Commit transaction
            mysqli_commit($connection);

            // Return success message
            http_response_code(200);
            $response = [
                'message' => "Product quantities updated successfully."
            ];
            echo json_encode($response);
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($connection);

            // Return error message
            http_response_code(500);
            $response = [
                'error' => "An error occurred while updating product quantities: " . $e->getMessage()
            ];
            echo json_encode($response);
        }
    }

    // Example usage of the function
    updateProductQuantities($productsToUpdate);
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
