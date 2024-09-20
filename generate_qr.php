<?php
require 'vendor/autoload.php'; //autoloader is to include library downloaded using Composer

use Dompdf\Dompdf;
use Dompdf\Options; //to allow external contents to be loaded (i.e QRcode)

$options = new Options();
$options -> set('isRemoteEnabled', TRUE);
$dompdf = new Dompdf($options);
$html = file_get_contents('sample-bp.html');

// Initialize the variable
$responseXML = ''; 
$combinedHtml = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

        //Start handling SOAP
        $id = $_POST['id'];
        $transaction_no = $_POST['transaction_no'];

    if (!empty($id) && !empty($transaction_no)) {
        //$soapUrl = "http://cts.tbsbts.com.my/ws_online/service.asmx";
        $soapUrl = "http://10.20.40.111/ws_online/service.asmx";
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

            $responseXML = simplexml_load_string($response);

		if ($responseXML === false) {
			echo "Failed to parse XML response.<br>";
			echo "Raw response:<br>";
			echo "<pre>" . htmlspecialchars($response) . "</pre>";
			exit;
		}

		// Register the XML namespace for XPath
		$responseXML->registerXPathNamespace('ns', 'https://eTicketing_Online.tbsbts.com.my/');

		//Extract the needed details
        $details = $responseXML->xpath("//ns:detail");
        }
    }
    //Start construct output
    if (!empty($details)) {

        foreach ($details as $detail) {
            
            //Gather single used variables
            //$qrLink = $responseXML->xpath("//ns:QRLink")[0] ?? null;
            $qrLink = "http://10.20.40.22//QRCodeGenerator/QRCodeHandler.ashx";
            $ticketNo = $detail['barcodevalue'] ?? null;
            $transactionId = $responseXML->xpath("//ns:transactionid")[0] ?? null;
            $companyName = $responseXML->xpath("//ns:companyname")[0] ?? null;	
            $deptDate = $responseXML->xpath("//ns:departuredate")[0];
            $deptTime = $responseXML->xpath("//ns:departuretime")[0];
            $gateNo = $responseXML->xpath("//ns:gateno")[0];

                //Gather other variables
                $barcode = urlencode($ticketNo);
                $barcode = str_replace('%2C', ',', $barcode);
                $qrImageURL = $qrLink . "?t=" . $barcode . "&e=M&q=Two&s=4";
                $from = $responseXML->xpath("//ns:from")[0];
                $to = $responseXML->xpath("//ns:to")[0];
                $tripno = $responseXML->xpath("//ns:tripno")[0];
                $paxName = $detail['name'];
                $paxIC = $detail['ic'];
                $paxPhone = $detail['contact'];
                $seatNo = $detail['seatdesn'];
                $price = $detail['price'];
                $authCode = $detail['ticketno'];
                
                // Dynamic filename generation
                $orderID = $responseXML->xpath("//ns:orderid")[0] ?? 'Unknown_Order';
                $paxName = $detail['name'] ?? 'Unknown_Name';
                $deptDate2 = $responseXML->xpath("//ns:departuredate")[0] ?? 'Unknown_Date';
                $deptDate2 = str_replace('/', '', $deptDate2);
                $filename = str_replace(' ', '_', $id) . "_" . $orderID . "_" . $deptDate2 . "_". $paxName. ".pdf";

                //Passing param to HTML
                $html = str_replace('{{qrImageURL}}', $qrImageURL, $html);
                $html = str_replace('{{from}}', $from, $html);
                $html = str_replace('{{to}}', $to, $html);
                $html = str_replace('{{companyName}}', $companyName, $html);
                $html = str_replace('{{deptDate}}', $deptDate, $html);
                $html = str_replace('{{deptTime}}', $deptTime, $html);
                $html = str_replace('{{paxName}}', $paxName, $html);
                $html = str_replace('{{paxIC}}', $paxIC, $html);
                $html = str_replace('{{gateNo}}', $gateNo, $html);
                $html = str_replace('{{seatNo}}', $seatNo, $html);
                $html = str_replace('{{transactionId}}', $transactionId, $html);
                $html = str_replace('{{orderID}}', $orderID, $html);
                $html = str_replace('{{authCode}}', $authCode, $html);

                echo ($html);        

            }
            
            // Prepare hidden form fields for PDF generation
            echo '<form action="generate_pdf.php" method="post">';
            echo '<input type="hidden" name="id" value="' . htmlspecialchars($id) . '">';
            echo '<input type="hidden" name="transaction_no" value="' . htmlspecialchars($transaction_no) . '">';
            echo '<input type="submit" value="Download PDF">';
            echo '</form>';
                
                
        }else{
            echo "Your details are empty!";
        }
    }

//End generate QR code

/*if ($generatePDF){
    //Load the htmlContent into DOMPDF
    $dompdf->loadHtml($htmlContent);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();

    //Header for Download
    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo $pdfOutput;
    exit;
}*/

?>