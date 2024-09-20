<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $transaction_no = $_POST['transaction_no'];

    if (!empty($id) && !empty($transaction_no)) {
        $soapUrl = "http://cts.tbsbts.com.my/ws_online/service.asmx";
        //$soapUrl = "http://10.20.40.111/ws_online/service.asmx";
        $soapAction = "https://eTicketing_Online.tbsbts.com.my/BoardingPass";
        //$soapAction = "http://10.20.40.116/BoardingPass";

        // XML SOAP request
        $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
        <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <BoardingPass xmlns="https://eTicketing_Online.tbsbts.com.my/">
              <id>' . $id . '</id>
              <transaction_no>' . $transaction_no . '</transaction_no>
            </BoardingPass>
          </soap:Body>
        </soap:Envelope>';

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $soapUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: text/xml; charset=utf-8",
            "SOAPAction: " . $soapAction
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);

        // Execute request and get response
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            // If there's an error, show the cURL error
            echo 'Curl error: ' . curl_error($ch);
        } else {
            /*// Display the raw XML response
            echo "<h3>Raw Response:</h3>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";*/

            // Load the response into a SimpleXML object for parsing
            $responseXML = simplexml_load_string($response);

            // Namespace needs to be manually handled
            $responseXML->registerXPathNamespace('ns', 'https://eTicketing_Online.tbsbts.com.my/');

            // Extract specific data (for example, ticket number, etc.)
            $details = $responseXML->xpath("//ns:detail");

            if (!empty($details)) {
                echo "<h3>Boarding Pass Details</h3>";
                foreach ($details as $detail) {


                    // Get the QRLink and display it as an image with the proper parameters
                    $qrLink = $responseXML->xpath("//ns:QRLink")[0];
                    $ticketNo = $detail['barcodevalue'];
                    $transactionId = $responseXML->xpath("//ns:transactionid")[0];
                    $companyName = $responseXML->xpath("//ns:companyname")[0];

                    if ($qrLink) {

                        $barcode = urlencode($ticketNo);
                        $barcode = str_replace('%2C', ',', $barcode);

                        // Forming the QR link with parameters
                        $qrImageURL = $qrLink . "?t=" . $barcode . "&e=M&q=Two&s=4";
                        echo "<img class='QRImage' src='" . $qrImageURL . "' alt='QR Code' align='middle'><br><hr>";


                    echo "<p>" . $responseXML->xpath("//ns:from")[0] . " to " . $responseXML->xpath("//ns:to")[0] . " (Trip ID: " . $responseXML->xpath("//ns:tripno")[0] . ")</p><hr>";
                    echo "<p>" . $responseXML->xpath("//ns:companyname")[0] . " | " . $responseXML->xpath("//ns:departuredate")[0] . " | " . $responseXML->xpath("//ns:departuretime")[0] . "</p><hr>";
                    echo "Name<br>" . $detail['name'] . "<br><hr>";
                    echo "IC/Passport<br>" . $detail['ic'] . "<br><hr>";
                    echo "Contact<br>" . $detail['contact'] . "<br><hr>";
                    echo "Gate | Seat No. <br>" .$responseXML->xpath("//ns:gateno")[0] . " | " . $detail['seatdesn'] . "<br><hr>";

                    echo "Ticket No: " . $detail['ticketno'] . "<br><hr>";
                    //echo "Barcode Value: " . $detail['barcodevalue'] . "<br>";
                    //echo "Seat: " . $detail['seatdesn'] . "<br>";
                    //echo "Name: " . $detail['name'] . "<br>";
                    //echo "Contact: " . $detail['contact'] . "<br>";
                    echo "Price: RM" . $detail['price'] . "<br><hr>";

                    

                    echo "Booking Code: " . $responseXML->xpath("//ns:bookingcode")[0] . "<br>";
                    echo "Order ID: " . $responseXML->xpath("//ns:orderid")[0] . "<br>";
                    //echo "Auth Code: " . $responseXML->xpath("//ns:authcode")[0] . "<br>";
                    echo "Transaction ID: " . $responseXML->xpath("//ns:transactionid")[0] . "<br>";
                    //echo "Company Name: " . $responseXML->xpath("//ns:companyname")[0] . "<br>";
                    //echo "Trip No: " . $responseXML->xpath("//ns:tripno")[0] . "<br>";
                    //echo "From: " . $responseXML->xpath("//ns:from")[0] . "<br>";
                    //echo "To: " . $responseXML->xpath("//ns:to")[0] . "<br>";
                    //echo "Departure Date: " . $responseXML->xpath("//ns:departuredate")[0] . "<br>";
                    //echo "Departure Time: " . $responseXML->xpath("//ns:departuretime")[0] . "<br>";
                    //echo "Gate No: " . $responseXML->xpath("//ns:gateno")[0] . "<br>";
                    //echo "Card Type: " . $responseXML->xpath("//ns:cardtype")[0] . "<br>";
                    


                    
                    }
                    echo "<hr>";
                }
            } else {
                echo "No details found or an error occurred.";
            }
        }

        curl_close($ch);
    } else {
        echo "Please provide both ID and Transaction Number.";
    }
}
?>

<!-- HTML form for input -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boarding Pass SOAP Request</title>
</head>
<body>
    <h2>Enter ID and Transaction Number</h2>
    <form method="post" action="">
        <label for="id">ID:</label>
        <input type="text" name="id" id="id"><br><br>

        <label for="transaction_no">Transaction Number:</label>
        <input type="text" name="transaction_no" id="transaction_no"><br><br>

        <input type="submit" value="Send">
    </form>
</body>
</html>
