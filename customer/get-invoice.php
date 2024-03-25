<?php
// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

// Include database connection
require '../db.php';

// Check if invoice ID is provided in the URL parameters
if (isset($_GET['invoiceId']) && !empty($_GET['invoiceId'])) {
    $invoiceId = $_GET['invoiceId'];

    // Query to retrieve invoice details
    $invoiceQuery = "SELECT * FROM invoice WHERE invoice_id = $invoiceId";
    $invoiceResult = mysqli_query($connection, $invoiceQuery);

    // Check if invoice exists
    if (mysqli_num_rows($invoiceResult) > 0) {
        $invoiceData = mysqli_fetch_assoc($invoiceResult);

        // Query to retrieve products associated with the invoice
        $productsQuery = "SELECT p.*, c.quantity
                          FROM product p
                          INNER JOIN customer_invoiced_product c ON p.product_id = c.product_id
                          WHERE c.invoice_id = $invoiceId";
        $productsResult = mysqli_query($connection, $productsQuery);

        // Prepare invoice data
        $invoice = [
            'invoice_id' => $invoiceData['invoice_id'],
            'customer_id' => $invoiceData['customer_id'],
            'payment_mode_id' => $invoiceData['payment_mode_id'],
            'payment_type_id' => $invoiceData['payment_type_id'],
            'created_at' => $invoiceData['created_at'],
            'products' => []
        ];

        // Prepare products data
        while ($product = mysqli_fetch_assoc($productsResult)) {
            $invoice['products'][] = [
                'product_id' => $product['product_id'],
                'product_name' => $product['product_name'],
                'quantity' => $product['quantity'],
                'current_sales_amount' => $product['current_sales_amount']
                // Add more product details as needed
            ];
        }

        // Return invoice data as JSON
        echo json_encode($invoice);
    } else {
        // Invoice not found
        http_response_code(404);
        $response = [
            'error' => 'Invoice not found.'
        ];
        echo json_encode($response);
    }
} else {
    // Invoice ID not provided
    http_response_code(400);
    $response = [
        'error' => 'Invoice ID is required.'
    ];
    echo json_encode($response);
}

// Close database connection
mysqli_close($connection);
?>
