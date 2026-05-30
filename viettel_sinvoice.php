<?php

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/bootstrap.php';

function viettel_sinvoice_config(): array
{
    return [
        'name' => 'Viettel SInvoice',
        'description' => 'Viettel SInvoice HĐĐT addon for WHMCS. Issues invoices via vinvoice.viettel.vn after InvoicePaid or admin action.',
        'author' => 'HiTechCloud',
        'language' => 'english',
        'version' => VIETTEL_SINVOICE_VERSION,
        'fields' => [
            'autoIssueOnPaid' => [
                'FriendlyName' => 'Auto Issue On Paid',
                'Type' => 'yesno',
                'Default' => 'on',
                'Description' => 'Automatically issue Viettel SInvoice when WHMCS InvoicePaid fires.',
            ],
            'authMode' => [
                'FriendlyName' => 'Auth Mode',
                'Type' => 'dropdown',
                'Options' => ['api_key' => 'API Key headers', 'basic' => 'Basic Auth', 'token' => 'Token Cookie'],
                'Default' => 'api_key',
            ],
            'apiBaseUrl' => [
                'FriendlyName' => 'API Base URL',
                'Type' => 'text',
                'Size' => '80',
                'Default' => VIETTEL_SINVOICE_DEFAULT_BASE_URL,
            ],
            'username' => [
                'FriendlyName' => 'Username',
                'Type' => 'text',
                'Size' => '40',
                'Default' => '',
                'Description' => 'Required for Basic/Token authentication.',
            ],
            'password' => [
                'FriendlyName' => 'Password',
                'Type' => 'password',
                'Size' => '40',
                'Default' => '',
                'Description' => 'Required for Basic/Token authentication.',
            ],
            'appKid' => [
                'FriendlyName' => 'APP-KID',
                'Type' => 'text',
                'Size' => '40',
                'Default' => '',
            ],
            'xKid' => [
                'FriendlyName' => 'X-KID',
                'Type' => 'text',
                'Size' => '40',
                'Default' => '',
            ],
            'xApiKey' => [
                'FriendlyName' => 'X-API-KEY',
                'Type' => 'password',
                'Size' => '60',
                'Default' => '',
            ],
            'supplierTaxCode' => [
                'FriendlyName' => 'Supplier Tax Code',
                'Type' => 'text',
                'Size' => '30',
                'Default' => '',
                'Description' => 'Seller MST used in Viettel endpoints.',
            ],
            'templateCode' => [
                'FriendlyName' => 'Template Code',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '1/001',
            ],
            'invoiceSeries' => [
                'FriendlyName' => 'Invoice Series',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
            ],
            'invoiceType' => [
                'FriendlyName' => 'Invoice Type',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '1',
            ],
            'sellerLegalName' => [
                'FriendlyName' => 'Seller Legal Name',
                'Type' => 'text',
                'Size' => '80',
                'Default' => '',
            ],
            'sellerAddress' => [
                'FriendlyName' => 'Seller Address',
                'Type' => 'textarea',
                'Rows' => '3',
                'Cols' => '80',
                'Default' => '',
            ],
            'sellerPhone' => [
                'FriendlyName' => 'Seller Phone',
                'Type' => 'text',
                'Size' => '30',
                'Default' => '',
            ],
            'sellerEmail' => [
                'FriendlyName' => 'Seller Email',
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
            ],
            'sellerBankName' => [
                'FriendlyName' => 'Seller Bank Name',
                'Type' => 'text',
                'Size' => '60',
                'Default' => '',
            ],
            'sellerBankAccount' => [
                'FriendlyName' => 'Seller Bank Account',
                'Type' => 'text',
                'Size' => '40',
                'Default' => '',
            ],
            'sellerCityName' => [
                'FriendlyName' => 'Seller City Name',
                'Type' => 'text',
                'Size' => '40',
                'Default' => '',
            ],
            'paymentMethod' => [
                'FriendlyName' => 'Payment Method Name',
                'Type' => 'text',
                'Size' => '20',
                'Default' => 'CK',
            ],
            'paymentMethodCode' => [
                'FriendlyName' => 'Payment Method Code',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '2',
            ],
            'defaultUnit' => [
                'FriendlyName' => 'Default Unit',
                'Type' => 'text',
                'Size' => '20',
                'Default' => 'Lần',
            ],
            'defaultVatRate' => [
                'FriendlyName' => 'Default VAT Rate',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '10',
                'Description' => 'Used for WHMCS taxed line items because WHMCS stores only a taxed flag per item.',
            ],
            'requireBuyerTaxCode' => [
                'FriendlyName' => 'Require Buyer Tax Code',
                'Type' => 'yesno',
                'Default' => '',
            ],
            'buyerTaxCodeField' => [
                'FriendlyName' => 'Buyer Tax Code Field',
                'Type' => 'text',
                'Size' => '40',
                'Default' => 'tax_id',
                'Description' => 'Client property/custom field name containing buyer MST.',
            ],
            'buyerNotGetInvoiceForB2c' => [
                'FriendlyName' => 'B2C Buyer Not Get Invoice',
                'Type' => 'yesno',
                'Default' => '',
            ],
            'timeout' => [
                'FriendlyName' => 'API Timeout',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '90',
                'Description' => 'Seconds. Viettel recommends 60-90 seconds.',
            ],
            'connectTimeout' => [
                'FriendlyName' => 'Connect Timeout',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '10',
                'Description' => 'Seconds. Values above 10 are capped for security hardening.',
            ],
        ],
    ];
}

function viettel_sinvoice_activate(): array
{
    try {
        viettel_sinvoice_install_schema();

        return [
            'status' => 'success',
            'description' => 'Viettel SInvoice table created. Configure credentials before enabling auto issue.',
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Unable to activate Viettel SInvoice module.',
        ];
    }
}

function viettel_sinvoice_deactivate(): array
{
    return [
        'status' => 'success',
        'description' => 'Viettel SInvoice deactivated. Invoice records were kept for audit compliance.',
    ];
}

function viettel_sinvoice_upgrade($vars): void
{
    viettel_sinvoice_upgrade_schema();
}

function viettel_sinvoice_output($vars): void
{
    $moduleLink = isset($vars['modulelink']) ? (string) $vars['modulelink'] : 'addonmodules.php?module=viettel_sinvoice';
    $message = '';
    $messageClass = 'info';

    try {
        $action = isset($_POST['vsi_action']) ? trim((string) $_POST['vsi_action']) : '';
        if ($action !== '') {
            $token = isset($_POST['token']) ? (string) $_POST['token'] : '';
            if (!function_exists('check_token') || !check_token('WHMCS.admin.default', $token)) {
                throw new RuntimeException('Invalid admin security token.');
            }

            $invoiceId = isset($_POST['invoice_id']) ? (int) $_POST['invoice_id'] : 0;
            if ($invoiceId <= 0 && $action !== 'poll_pending') {
                throw new InvalidArgumentException('Invalid invoice ID.');
            }

            if ($action === 'issue') {
                $result = viettel_sinvoice_issue_invoice($invoiceId, false);
                $message = $result['message'];
                $messageClass = $result['success'] ? 'success' : 'danger';
            } elseif ($action === 'retry') {
                $result = viettel_sinvoice_issue_invoice($invoiceId, true);
                $message = $result['message'];
                $messageClass = $result['success'] ? 'success' : 'danger';
            } elseif ($action === 'poll') {
                $result = viettel_sinvoice_poll_invoice($invoiceId);
                $message = $result['message'];
                $messageClass = $result['success'] ? 'success' : 'danger';
            } elseif ($action === 'poll_pending') {
                $count = viettel_sinvoice_poll_pending(20);
                $message = 'Polled ' . $count . ' pending Viettel SInvoice records.';
                $messageClass = 'success';
            } else {
                throw new InvalidArgumentException('Unsupported action.');
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageClass = 'danger';
    }

    echo '<h2>Viettel SInvoice</h2>';
    echo '<p>Issue Vietnamese HĐĐT through Viettel SInvoice for WHMCS invoices.</p>';

    if ($message !== '') {
        echo '<div class="alert alert-' . viettel_sinvoice_e($messageClass) . '">' . viettel_sinvoice_e($message) . '</div>';
    }

    if (!viettel_sinvoice_configured()) {
        echo '<div class="alert alert-warning">Module is not fully configured. Fill API credentials, seller info, template code, and invoice series in addon settings.</div>';
    }

    echo '<form method="post" action="' . viettel_sinvoice_e($moduleLink) . '" class="form-inline" style="margin-bottom:15px">';
    echo '<input type="hidden" name="token" value="' . viettel_sinvoice_e(function_exists('generate_token') ? generate_token('plain') : '') . '">';
    echo '<input type="hidden" name="vsi_action" value="poll_pending">';
    echo '<button type="submit" class="btn btn-default">Poll Pending Records</button>';
    echo '</form>';

    echo '<form method="post" action="' . viettel_sinvoice_e($moduleLink) . '" class="form-inline" style="margin-bottom:20px">';
    echo '<input type="hidden" name="token" value="' . viettel_sinvoice_e(function_exists('generate_token') ? generate_token('plain') : '') . '">';
    echo '<input type="number" min="1" name="invoice_id" class="form-control" placeholder="Invoice ID" required> ';
    echo '<button type="submit" name="vsi_action" value="issue" class="btn btn-primary">Issue</button> ';
    echo '<button type="submit" name="vsi_action" value="poll" class="btn btn-info">Poll</button> ';
    echo '<button type="submit" name="vsi_action" value="retry" class="btn btn-warning">Force Retry</button>';
    echo '</form>';

    $records = Capsule::table(VIETTEL_SINVOICE_TABLE)
        ->orderBy('id', 'desc')
        ->limit(50)
        ->get();

    echo '<table class="table table-striped table-condensed">';
    echo '<thead><tr><th>ID</th><th>WHMCS Invoice</th><th>Client</th><th>Status</th><th>Transaction UUID</th><th>Invoice No</th><th>Reservation</th><th>Updated</th><th>Last Error</th></tr></thead><tbody>';
    foreach ($records as $record) {
        echo '<tr>';
        echo '<td>' . (int) $record->id . '</td>';
        echo '<td><a href="invoices.php?action=edit&id=' . (int) $record->invoice_id . '">#' . (int) $record->invoice_id . '</a></td>';
        echo '<td><a href="clientssummary.php?userid=' . (int) $record->client_id . '">#' . (int) $record->client_id . '</a></td>';
        echo '<td>' . viettel_sinvoice_e($record->status) . '</td>';
        echo '<td><code>' . viettel_sinvoice_e($record->transaction_uuid) . '</code></td>';
        echo '<td>' . viettel_sinvoice_e($record->invoice_no) . '</td>';
        echo '<td>' . viettel_sinvoice_e($record->reservation_code) . '</td>';
        echo '<td>' . viettel_sinvoice_e($record->updated_at) . '</td>';
        echo '<td>' . viettel_sinvoice_e($record->last_error) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
