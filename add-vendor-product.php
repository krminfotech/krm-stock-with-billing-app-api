<?php
// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

// Include database connection
require './db.php';

// Retrieve POST data
$postdata = file_get_contents("php://input");

// Check if POST data is not empty
if (isset($postdata) && !empty($postdata)) {
    // Decode JSON data
    $request = json_decode($postdata);

    // Extract data from JSON
    $productId = $request->productId;
    $quantityChange = $request->quantityChange;

    // Function to update product stock or add new product based on vendor and product ID
    function updateOrAddProductStock($productId, $quantityChange) {
        global $connection;

        // Check if the product exists for the vendor
        $sql = "SELECT COUNT(*) AS count FROM product WHERE product_id = $productId";
        $result = mysqli_query($connection, $sql);
        $row = mysqli_fetch_assoc($result);
        $productExists = ($row['count'] > 0);

        if ($productExists) {
            // Update stock quantity if product exists
            $sql = "UPDATE product SET current_available_qty = current_available_qty + $quantityChange 
                    WHERE product_id = $productId";
        }

        // Execute the query
        if (mysqli_query($connection, $sql)) {
            // If insertion/update is successful
            http_response_code(201); // Set HTTP status code
            $response = [
                'message' => "Product stock updated successfully."
            ];
            echo json_encode($response);
        } else {
            // If insertion/update fails
            http_response_code(422); // Set HTTP status code
            $response = [
                'message' => "Failed to update product stock."
            ];
            echo json_encode($response);
        }
    }

    // Example usage of the function
    updateOrAddProductStock($productId, $quantityChange);
} else {
    // If POST data is empty
    http_response_code(400); // Bad request
    $response = [
        'message' => "Invalid request."
    ];
    echo json_encode($response);
}

// Close database connection
mysqli_close($connection);
?>
