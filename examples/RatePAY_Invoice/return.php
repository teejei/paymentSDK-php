<?php
// # RatePay invoice return after transaction
// The consumer gets redirected to this page after a RatePay invoice transaction.

// ## Required objects
// To include the necessary files, we use the composer for PSR-4 autoloading.
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../inc/common.php';

use Wirecard\PaymentSdk\Config;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\TransactionService;

// ### Config
// #### Basic configuration
// The basic configuration requires the base URL for Wirecard and the username and password for the HTTP requests.
$baseUrl = 'https://api-test.wirecard.com';
$httpUser = '70000-APITEST-AP';
$httpPass = 'qD2wzQ_hrc!8';

// The configuration is stored in an object containing the connection settings set above.
// A default currency can also be provided.
$config = new Config\Config($baseUrl, $httpUser, $httpPass, 'EUR');

// #### RatePay invoice
// Create and add a configuration object with the RatePay invoice settings
$ratepayInvoiceMAID = '73ce088c-b195-4977-8ea8-0be32cca9c2e';
$ratepayInvoiceKey = 'd92724cf-5508-44fd-ad67-695e149212d5';

$ratepayInvoiceConfig = new Config\PaymentMethodConfig(
    RatepayInvoiceTransaction::NAME,
    $ratepayInvoiceMAID,
    $ratepayInvoiceKey
);
$config->add($ratepayInvoiceConfig);


// ## Transaction

// ### Transaction Service
// The `TransactionService` is used to determine the response from the service provider.
$service = new TransactionService($config);
$response = $service->handleResponse($_POST);


// ## Payment results

// The response from the service can be used for disambiguation.
// In case of a successful transaction, a `SuccessResponse` object is returned.
if ($response instanceof SuccessResponse) {
    $xmlResponse = new SimpleXMLElement($response->getRawData());
    $transactionType = $response->getTransactionType();
    echo 'Reservation successfully completed.<br>';
    echo getTransactionLink($baseUrl, $ratepayInvoiceMAID, $response->getTransactionId());
    ?>
    <form action="pay-based-on-reserve.php" method="post">
        <input type="hidden" name="parentTransactionId" value="<?= $response->getTransactionId() ?>"/>
        <input type="submit" value="Capture the reservation">
    </form>
    <br>
    <form action="cancel.php" method="post">
        <input type="hidden" name="parentTransactionId" value="<?= $response->getTransactionId() ?>"/>
        <label for="amount">Amount to cancel:</label>
        <input type="text" name="amount" id="amount" value="2400"/>
        <input type="submit" value="Cancel">
    </form>
    <form action="credit.php" method="post">
        <input type="hidden" name="parentTransactionId" value="<?= $response->getTransactionId() ?>"/>
        <label for="amount">Amount to credit:</label>
        <input type="text" name="amount" id="amount" value="100"/>
        <input type="submit" value="Credit">
    </form>
    <?php
// In case of a failed transaction, a `FailureResponse` object is returned.
} elseif ($response instanceof FailureResponse) {
// In our example we iterate over all errors and echo them out.
// You should display them as error, warning or information based on the given severity.
    foreach ($response->getStatusCollection() as $status) {
        /**
         * @var $status \Wirecard\PaymentSdk\Entity\Status
         */
        $severity = ucfirst($status->getSeverity());
        $code = $status->getCode();
        $description = $status->getDescription();
        echo sprintf('%s with code %s and message "%s" occurred.<br>', $severity, $code, $description);
    }
}
