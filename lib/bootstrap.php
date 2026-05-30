<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

const VIETTEL_SINVOICE_MODULE = 'viettel_sinvoice';
const VIETTEL_SINVOICE_VERSION = '1.0.0';
const VIETTEL_SINVOICE_TABLE = 'mod_viettel_sinvoice_invoices';
const VIETTEL_SINVOICE_DEFAULT_BASE_URL = 'https://api-vinvoice.viettel.vn/services/einvoiceapplication/api';

function viettel_sinvoice_setting(string $key, $default = ''): string
{
    try {
        $value = Capsule::table('tbladdonmodules')
            ->where('module', VIETTEL_SINVOICE_MODULE)
            ->where('setting', $key)
            ->value('value');

        if ($value === null || $value === '') {
            return (string) $default;
        }

        return trim((string) $value);
    } catch (Exception $e) {
        return (string) $default;
    }
}

function viettel_sinvoice_bool($value, bool $default = false): bool
{
    if ($value === null || $value === '') {
        return $default;
    }

    if (is_bool($value)) {
        return $value;
    }

    return in_array(strtolower(trim((string) $value)), ['1', 'on', 'yes', 'true', 'enabled'], true);
}

function viettel_sinvoice_configured(): bool
{
    $mode = strtolower(viettel_sinvoice_setting('authMode', 'api_key'));
    $required = ['supplierTaxCode', 'templateCode', 'invoiceSeries', 'sellerLegalName', 'sellerAddress'];

    foreach ($required as $key) {
        if (viettel_sinvoice_setting($key) === '') {
            return false;
        }
    }

    if ($mode === 'api_key') {
        foreach (['appKid', 'xKid', 'xApiKey'] as $key) {
            if (viettel_sinvoice_setting($key) === '') {
                return false;
            }
        }
        return true;
    }

    if (in_array($mode, ['basic', 'token'], true)) {
        return viettel_sinvoice_setting('username') !== '' && viettel_sinvoice_setting('password') !== '';
    }

    return false;
}

function viettel_sinvoice_install_schema(): void
{
    if (Capsule::schema()->hasTable(VIETTEL_SINVOICE_TABLE)) {
        viettel_sinvoice_upgrade_schema();
        return;
    }

    Capsule::schema()->create(VIETTEL_SINVOICE_TABLE, function ($table) {
        /** @var \Illuminate\Database\Schema\Blueprint $table */
        $table->increments('id');
        $table->unsignedInteger('invoice_id');
        $table->unsignedInteger('client_id')->default(0);
        $table->string('transaction_uuid', 36);
        $table->string('supplier_tax_code', 32)->default('');
        $table->string('template_code', 64)->default('');
        $table->string('invoice_series', 64)->default('');
        $table->string('invoice_no', 64)->nullable();
        $table->string('transaction_id', 128)->nullable();
        $table->string('reservation_code', 128)->nullable();
        $table->string('code_of_tax', 128)->nullable();
        $table->string('status', 32)->default('pending');
        $table->unsignedInteger('poll_count')->default(0);
        $table->text('last_request')->nullable();
        $table->mediumText('last_response')->nullable();
        $table->text('last_error')->nullable();
        $table->text('metadata')->nullable();
        $table->timestamp('issued_at')->nullable();
        $table->timestamp('last_poll_at')->nullable();
        $table->timestamps();

        $table->unique('invoice_id');
        $table->unique('transaction_uuid');
        $table->index('client_id');
        $table->index('status');
    });
}

function viettel_sinvoice_upgrade_schema(): void
{
    if (!Capsule::schema()->hasTable(VIETTEL_SINVOICE_TABLE)) {
        viettel_sinvoice_install_schema();
        return;
    }

    $schema = Capsule::schema();
    $schema->table(VIETTEL_SINVOICE_TABLE, function ($table) use ($schema) {
        /** @var \Illuminate\Database\Schema\Blueprint $table */
        if (!$schema->hasColumn(VIETTEL_SINVOICE_TABLE, 'metadata')) {
            $table->text('metadata')->nullable()->after('last_error');
        }
        if (!$schema->hasColumn(VIETTEL_SINVOICE_TABLE, 'last_poll_at')) {
            $table->timestamp('last_poll_at')->nullable()->after('issued_at');
        }
    });
}

function viettel_sinvoice_now(): string
{
    return date('Y-m-d H:i:s');
}

function viettel_sinvoice_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function viettel_sinvoice_json($value): string
{
    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $encoded === false ? '{}' : $encoded;
}

function viettel_sinvoice_decode_json($value): array
{
    $decoded = is_string($value) && trim($value) !== '' ? json_decode($value, true) : [];
    return is_array($decoded) ? $decoded : [];
}

function viettel_sinvoice_clean_text($value, int $maxLength = 255): string
{
    $value = trim(preg_replace('/\s+/u', ' ', (string) $value));
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }

    return substr($value, 0, $maxLength);
}

function viettel_sinvoice_money($amount): int
{
    return (int) round((float) $amount, 0);
}

function viettel_sinvoice_normalize_number($value)
{
    $number = (float) $value;
    if (abs($number - round($number)) < 0.000001) {
        return (int) round($number);
    }

    return $number;
}

function viettel_sinvoice_uuid_v4(): string
{
    if (function_exists('random_bytes')) {
        $data = random_bytes(16);
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $data = openssl_random_pseudo_bytes(16);
    } else {
        $data = md5(uniqid('', true), true);
    }

    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function viettel_sinvoice_vietnam_timestamp_ms(): int
{
    $date = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    return ((int) $date->format('U')) * 1000;
}

function viettel_sinvoice_array_value(array $data, string $key): string
{
    if (isset($data[$key]) && is_scalar($data[$key])) {
        return trim((string) $data[$key]);
    }

    return '';
}

function viettel_sinvoice_first_non_empty(array $values): string
{
    foreach ($values as $value) {
        if (is_scalar($value)) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }
    }

    return '';
}

function viettel_sinvoice_mask_value($value): string
{
    $value = (string) $value;
    if (strlen($value) <= 8) {
        return '***';
    }

    return substr($value, 0, 4) . '...' . substr($value, -4);
}

function viettel_sinvoice_redact($data)
{
    if (!is_array($data)) {
        return $data;
    }

    foreach ($data as $key => $value) {
        $lower = strtolower((string) $key);
        $secretKey = in_array($lower, ['authorization', 'cookie', 'app-kid', 'x-kid', 'x-api-key', 'password', 'access_token', 'accesstoken', 'refresh_token', 'refreshtoken', 'id_token', 'idtoken', 'token', 'secret', 'api_key', 'apikey'], true)
            || strpos($lower, 'password') !== false
            || strpos($lower, 'token') !== false
            || strpos($lower, 'secret') !== false
            || strpos($lower, 'api_key') !== false
            || strpos($lower, 'apikey') !== false;
        $piiKey = in_array($lower, ['buyername', 'buyerlegalname', 'buyertaxcode', 'buyeremail', 'buyerphonenumber', 'buyeraddressline', 'email', 'phonenumber', 'phone', 'address', 'address1', 'address2', 'tax_id', 'taxid'], true);

        if ($secretKey) {
            $data[$key] = viettel_sinvoice_mask_value($value);
        } elseif ($piiKey) {
            $data[$key] = '***';
        } elseif (is_array($value)) {
            $data[$key] = viettel_sinvoice_redact($value);
        }
    }

    return $data;
}

function viettel_sinvoice_headers_for_log(array $headers): array
{
    $out = [];
    foreach ($headers as $header) {
        $parts = explode(':', $header, 2);
        if (count($parts) === 2) {
            $out[trim($parts[0])] = trim($parts[1]);
        } else {
            $out[] = $header;
        }
    }

    return viettel_sinvoice_redact($out);
}

function viettel_sinvoice_response_for_log($raw, int $http, int $errno = 0): array
{
    $rawForLog = is_string($raw) ? trim($raw) : '';
    $responseForLog = [];

    if ($rawForLog !== '') {
        $responseForLog = json_decode($rawForLog, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $responseForLog = [
                '_invalid_json' => true,
                '_body_length' => strlen($rawForLog),
            ];
        } elseif (!is_array($responseForLog)) {
            $responseForLog = [
                '_non_array_json' => true,
                '_body_length' => strlen($rawForLog),
            ];
        }
    }

    $responseForLog['_http_code'] = $http;
    if ($errno) {
        $responseForLog['_transport_error'] = true;
        $responseForLog['curl_errno'] = $errno;
    }

    return viettel_sinvoice_redact($responseForLog);
}

function viettel_sinvoice_positive_int($value, int $default): int
{
    $value = (int) $value;
    return $value > 0 ? $value : $default;
}

function viettel_sinvoice_limited_int($value, int $default, int $min, int $max): int
{
    $value = viettel_sinvoice_positive_int($value, $default);
    return max($min, min($max, $value));
}

function viettel_sinvoice_base_url(): string
{
    $url = rtrim(viettel_sinvoice_setting('apiBaseUrl', VIETTEL_SINVOICE_DEFAULT_BASE_URL), '/');
    if ($url === '') {
        $url = VIETTEL_SINVOICE_DEFAULT_BASE_URL;
    }
    if (stripos($url, 'http://') === 0) {
        $url = 'https://' . substr($url, 7);
    } elseif (stripos($url, 'https://') !== 0) {
        $url = 'https://' . $url;
    }

    return $url;
}

function viettel_sinvoice_access_token(): string
{
    static $accessToken = null;
    static $expiresAt = 0;

    if ($accessToken !== null && $expiresAt > time()) {
        return $accessToken;
    }

    $payload = [
        'username' => viettel_sinvoice_setting('username'),
        'password' => viettel_sinvoice_setting('password'),
    ];

    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    $body = viettel_sinvoice_json($payload);
    $curl = curl_init(viettel_sinvoice_base_url() . '/auth/login');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => viettel_sinvoice_limited_int(viettel_sinvoice_setting('connectTimeout', '10'), 10, 1, 10),
        CURLOPT_TIMEOUT => viettel_sinvoice_limited_int(viettel_sinvoice_setting('timeout', '90'), 90, 10, 90),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $raw = curl_exec($curl);
    $errno = curl_errno($curl);
    $error = curl_error($curl);
    $http = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $responseForLog = viettel_sinvoice_response_for_log($raw, $http, $errno);
    logModuleCall(VIETTEL_SINVOICE_MODULE, 'auth/login', viettel_sinvoice_redact($payload), $responseForLog, [], [$payload['password']]);

    if ($errno) {
        throw new RuntimeException('Viettel SInvoice login transport error.');
    }

    $decoded = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : [];
    if (!is_array($decoded)) {
        throw new RuntimeException('Viettel SInvoice login returned invalid JSON.');
    }

    $token = viettel_sinvoice_first_non_empty([
        viettel_sinvoice_array_value($decoded, 'access_token'),
        viettel_sinvoice_array_value($decoded, 'accessToken'),
        viettel_sinvoice_array_value($decoded, 'token'),
        isset($decoded['result']) && is_array($decoded['result']) ? viettel_sinvoice_array_value($decoded['result'], 'access_token') : '',
        isset($decoded['result']) && is_array($decoded['result']) ? viettel_sinvoice_array_value($decoded['result'], 'accessToken') : '',
    ]);

    if ($token === '') {
        throw new RuntimeException('Viettel SInvoice login response did not include access_token.');
    }

    $accessToken = $token;
    $expiresAt = time() + 82800;

    return $accessToken;
}

function viettel_sinvoice_auth_headers(): array
{
    $mode = strtolower(viettel_sinvoice_setting('authMode', 'api_key'));

    if ($mode === 'api_key') {
        return [
            'APP-KID: ' . viettel_sinvoice_setting('appKid'),
            'X-KID: ' . viettel_sinvoice_setting('xKid'),
            'X-API-KEY: ' . viettel_sinvoice_setting('xApiKey'),
        ];
    }

    if ($mode === 'basic') {
        return ['Authorization: Basic ' . base64_encode(viettel_sinvoice_setting('username') . ':' . viettel_sinvoice_setting('password'))];
    }

    if ($mode === 'token') {
        return ['Cookie: access_token=' . viettel_sinvoice_access_token()];
    }

    throw new InvalidArgumentException('Unsupported Viettel SInvoice auth mode.');
}

function viettel_sinvoice_api_request(string $method, string $endpoint, array $payload = null): array
{
    $url = viettel_sinvoice_base_url() . $endpoint;
    $headers = viettel_sinvoice_auth_headers();
    $headers[] = 'Accept: application/json';

    $body = null;
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $body = viettel_sinvoice_json($payload);
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => viettel_sinvoice_limited_int(viettel_sinvoice_setting('connectTimeout', '10'), 10, 1, 10),
        CURLOPT_TIMEOUT => viettel_sinvoice_limited_int(viettel_sinvoice_setting('timeout', '90'), 90, 10, 90),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    if ($body !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    }

    $raw = curl_exec($curl);
    $errno = curl_errno($curl);
    $error = curl_error($curl);
    $http = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $responseForLog = viettel_sinvoice_response_for_log($raw, $http, $errno);

    logModuleCall(
        VIETTEL_SINVOICE_MODULE,
        $method . ' ' . $endpoint,
        ['headers' => viettel_sinvoice_headers_for_log($headers), 'body' => viettel_sinvoice_redact($payload ?: [])],
        $responseForLog,
        [],
        [
            viettel_sinvoice_setting('password'),
            viettel_sinvoice_setting('xApiKey'),
            viettel_sinvoice_setting('appKid'),
            viettel_sinvoice_setting('xKid'),
        ]
    );

    if ($errno) {
        return [
            '_transport_error' => true,
            '_async_candidate' => true,
            '_http_code' => $http,
            'error' => 'Viettel SInvoice transport error.',
            'curl_errno' => $errno,
        ];
    }

    $raw = is_string($raw) ? trim($raw) : '';
    $decoded = $raw !== '' ? json_decode($raw, true) : [];
    if ($raw !== '' && json_last_error() !== JSON_ERROR_NONE) {
        $decoded = ['_invalid_json' => true, '_body_length' => strlen($raw)];
    }
    if (!is_array($decoded)) {
        $decoded = ['_non_array_json' => true, '_body_length' => strlen($raw)];
    }

    $decoded['_http_code'] = $http;

    return $decoded;
}

function viettel_sinvoice_invoice_record(int $invoiceId)
{
    return Capsule::table(VIETTEL_SINVOICE_TABLE)->where('invoice_id', $invoiceId)->first();
}

function viettel_sinvoice_create_invoice_record(int $invoiceId, int $clientId): object
{
    $existing = viettel_sinvoice_invoice_record($invoiceId);
    if ($existing) {
        return $existing;
    }

    $now = viettel_sinvoice_now();
    $uuid = viettel_sinvoice_uuid_v4();

    Capsule::table(VIETTEL_SINVOICE_TABLE)->insert([
        'invoice_id' => $invoiceId,
        'client_id' => $clientId,
        'transaction_uuid' => $uuid,
        'supplier_tax_code' => viettel_sinvoice_setting('supplierTaxCode'),
        'template_code' => viettel_sinvoice_setting('templateCode'),
        'invoice_series' => viettel_sinvoice_setting('invoiceSeries'),
        'status' => 'pending',
        'poll_count' => 0,
        'metadata' => viettel_sinvoice_json([]),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return viettel_sinvoice_invoice_record($invoiceId);
}

function viettel_sinvoice_update_record(int $recordId, array $values): void
{
    $values['updated_at'] = viettel_sinvoice_now();
    Capsule::table(VIETTEL_SINVOICE_TABLE)->where('id', $recordId)->update($values);
}

function viettel_sinvoice_fetch_invoice(int $invoiceId): array
{
    $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
    if (!$invoice) {
        throw new InvalidArgumentException('Invoice not found.');
    }

    $items = Capsule::table('tblinvoiceitems')->where('invoiceid', $invoiceId)->orderBy('id')->get();
    $client = Capsule::table('tblclients')->where('id', (int) $invoice->userid)->first();
    if (!$client) {
        throw new InvalidArgumentException('Invoice client not found.');
    }

    $currency = Capsule::table('tblcurrencies')->where('id', (int) $client->currency)->first();

    return [
        'invoice' => $invoice,
        'items' => $items,
        'client' => $client,
        'currency' => $currency,
    ];
}

function viettel_sinvoice_qualify_invoice(int $invoiceId, string &$message): bool
{
    $message = '';

    if (!viettel_sinvoice_configured()) {
        $message = 'Viettel SInvoice is not fully configured.';
        return false;
    }

    $data = viettel_sinvoice_fetch_invoice($invoiceId);
    $invoice = $data['invoice'];
    $items = $data['items'];
    $client = $data['client'];
    $currencyCode = isset($data['currency']->code) ? strtoupper((string) $data['currency']->code) : 'VND';

    if ($currencyCode !== 'VND') {
        $message = 'Viettel SInvoice currently supports VND invoices only.';
        return false;
    }

    if (!in_array((string) $invoice->status, ['Paid', 'Unpaid'], true)) {
        $message = 'Invoice status is not eligible for HĐĐT issuance.';
        return false;
    }

    if (count($items) === 0) {
        $message = 'Invoice has no line items.';
        return false;
    }

    $buyerName = viettel_sinvoice_first_non_empty([
        isset($client->companyname) ? $client->companyname : '',
        trim((isset($client->firstname) ? $client->firstname : '') . ' ' . (isset($client->lastname) ? $client->lastname : '')),
    ]);
    if ($buyerName === '') {
        $message = 'Client name is required.';
        return false;
    }

    $address = viettel_sinvoice_client_address($client);
    if ($address === '') {
        $message = 'Client address is required.';
        return false;
    }

    if (viettel_sinvoice_bool(viettel_sinvoice_setting('requireBuyerTaxCode', ''), false) && viettel_sinvoice_buyer_tax_code($client) === '') {
        $message = 'Buyer tax code is required.';
        return false;
    }

    return true;
}

function viettel_sinvoice_client_address($client): string
{
    $parts = [
        isset($client->address1) ? $client->address1 : '',
        isset($client->address2) ? $client->address2 : '',
        isset($client->city) ? $client->city : '',
        isset($client->state) ? $client->state : '',
        isset($client->postcode) ? $client->postcode : '',
        isset($client->country) ? $client->country : '',
    ];

    return viettel_sinvoice_clean_text(implode(', ', array_filter($parts)), 500);
}

function viettel_sinvoice_buyer_tax_code($client): string
{
    $field = viettel_sinvoice_setting('buyerTaxCodeField', 'tax_id');
    $candidates = [];

    foreach ([$field, 'tax_id', 'taxid', 'vat', 'taxId'] as $property) {
        if (isset($client->{$property}) && is_scalar($client->{$property})) {
            $candidates[] = $client->{$property};
        }
    }

    if ($field !== '') {
        try {
            $customField = Capsule::table('tblcustomfields')
                ->join('tblcustomfieldsvalues', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')
                ->where('tblcustomfields.type', 'client')
                ->where('tblcustomfields.fieldname', 'like', '%' . $field . '%')
                ->where('tblcustomfieldsvalues.relid', (int) $client->id)
                ->value('tblcustomfieldsvalues.value');

            if (is_scalar($customField)) {
                $candidates[] = $customField;
            }
        } catch (Exception $e) {
            // Ignore custom field lookup failures.
        }
    }

    return preg_replace('/\s+/', '', viettel_sinvoice_first_non_empty($candidates));
}

function viettel_sinvoice_build_payload(array $data, object $record): array
{
    $invoice = $data['invoice'];
    $items = $data['items'];
    $client = $data['client'];
    $currency = $data['currency'];
    $currencyCode = isset($currency->code) ? strtoupper((string) $currency->code) : 'VND';
    $lines = viettel_sinvoice_build_lines($items);
    $taxBreakdowns = viettel_sinvoice_build_tax_breakdowns($lines);

    return [
        'generalInvoiceInfo' => [
            'invoiceType' => viettel_sinvoice_setting('invoiceType', '1'),
            'templateCode' => viettel_sinvoice_setting('templateCode'),
            'invoiceSeries' => viettel_sinvoice_setting('invoiceSeries'),
            'invoiceIssuedDate' => viettel_sinvoice_vietnam_timestamp_ms(),
            'currencyCode' => $currencyCode,
            'exchangeRate' => 1,
            'adjustmentType' => '1',
            'paymentStatus' => (string) $invoice->status === 'Paid',
            'cusGetInvoiceRight' => true,
            'transactionUuid' => $record->transaction_uuid,
        ],
        'sellerInfo' => viettel_sinvoice_seller_info(),
        'buyerInfo' => viettel_sinvoice_buyer_info($client),
        'payments' => viettel_sinvoice_payments(),
        'itemInfo' => $lines,
        'taxBreakdowns' => $taxBreakdowns,
        'summarizeInfo' => viettel_sinvoice_summarize($lines, $taxBreakdowns),
    ];
}

function viettel_sinvoice_seller_info(): array
{
    $seller = [
        'sellerLegalName' => viettel_sinvoice_setting('sellerLegalName'),
        'sellerTaxCode' => viettel_sinvoice_setting('supplierTaxCode'),
        'sellerAddressLine' => viettel_sinvoice_setting('sellerAddress'),
    ];

    $optional = [
        'sellerPhoneNumber' => 'sellerPhone',
        'sellerEmail' => 'sellerEmail',
        'sellerBankName' => 'sellerBankName',
        'sellerBankAccount' => 'sellerBankAccount',
        'sellerCityName' => 'sellerCityName',
    ];

    foreach ($optional as $apiKey => $configKey) {
        $value = viettel_sinvoice_setting($configKey);
        if ($value !== '') {
            $seller[$apiKey] = $value;
        }
    }

    return $seller;
}

function viettel_sinvoice_buyer_info($client): array
{
    $company = isset($client->companyname) ? trim((string) $client->companyname) : '';
    $personalName = viettel_sinvoice_first_non_empty([
        trim((isset($client->firstname) ? $client->firstname : '') . ' ' . (isset($client->lastname) ? $client->lastname : '')),
        isset($client->fullname) ? $client->fullname : '',
    ]);
    $taxCode = viettel_sinvoice_buyer_tax_code($client);
    $buyer = [
        'buyerName' => $personalName !== '' ? $personalName : ($company !== '' ? $company : 'Khách lẻ'),
        'buyerAddressLine' => viettel_sinvoice_client_address($client),
    ];

    if ($company !== '') {
        $buyer['buyerLegalName'] = $company;
    }
    if ($taxCode !== '') {
        $buyer['buyerTaxCode'] = $taxCode;
    }
    if (isset($client->email) && trim((string) $client->email) !== '') {
        $buyer['buyerEmail'] = trim((string) $client->email);
    }
    if (isset($client->phonenumber) && trim((string) $client->phonenumber) !== '') {
        $buyer['buyerPhoneNumber'] = trim((string) $client->phonenumber);
    }
    if ($taxCode === '' && viettel_sinvoice_bool(viettel_sinvoice_setting('buyerNotGetInvoiceForB2c', ''), false)) {
        $buyer['buyerNotGetInvoice'] = 1;
    }

    return $buyer;
}

function viettel_sinvoice_payments(): array
{
    $payment = ['paymentMethodName' => viettel_sinvoice_setting('paymentMethod', 'CK')];
    $code = viettel_sinvoice_setting('paymentMethodCode', '2');
    if ($code !== '') {
        $payment['paymentMethod'] = $code;
    }

    return [$payment];
}

function viettel_sinvoice_build_lines($items): array
{
    $lines = [];
    $lineNumber = 1;

    foreach ($items as $item) {
        $type = isset($item->type) ? (string) $item->type : '';
        $amount = isset($item->amount) ? (float) $item->amount : 0.0;
        if (strcasecmp($type, 'Discount') === 0 || $amount < 0) {
            continue;
        }

        $qty = 1;
        $lineNet = viettel_sinvoice_money($amount);
        $taxRate = viettel_sinvoice_item_tax_rate($item);
        $taxAmount = viettel_sinvoice_money($lineNet * $taxRate / 100);

        $lines[] = [
            'lineNumber' => $lineNumber,
            'itemCode' => 'WHMCS-' . (isset($item->id) ? (int) $item->id : $lineNumber),
            'itemName' => viettel_sinvoice_clean_text(isset($item->description) ? $item->description : ('WHMCS invoice item #' . $lineNumber), 500),
            'unitName' => viettel_sinvoice_setting('defaultUnit', 'Lần'),
            'unitPrice' => $lineNet,
            'quantity' => $qty,
            'itemTotalAmountWithoutTax' => $lineNet,
            'taxPercentage' => viettel_sinvoice_normalize_number($taxRate),
            'taxAmount' => $taxAmount,
            'discount' => 0,
            'itemDiscount' => 0,
            'itemTotalAmountWithTax' => viettel_sinvoice_money($lineNet + $taxAmount),
            'itemTotalAmountAfterDiscount' => $lineNet,
        ];
        $lineNumber++;
    }

    if (empty($lines)) {
        throw new RuntimeException('Invoice has no billable non-discount line items.');
    }

    return $lines;
}

function viettel_sinvoice_item_tax_rate($item)
{
    if (isset($item->taxed) && (int) $item->taxed === 1) {
        return viettel_sinvoice_normalize_number(viettel_sinvoice_setting('defaultVatRate', '10'));
    }

    return 0;
}

function viettel_sinvoice_build_tax_breakdowns(array $lines): array
{
    $breakdowns = [];
    foreach ($lines as $line) {
        $rate = (string) viettel_sinvoice_normalize_number($line['taxPercentage']);
        if (!isset($breakdowns[$rate])) {
            $breakdowns[$rate] = [
                'taxPercentage' => viettel_sinvoice_normalize_number($line['taxPercentage']),
                'taxableAmount' => 0,
                'taxAmount' => 0,
            ];
        }

        $breakdowns[$rate]['taxableAmount'] = viettel_sinvoice_money($breakdowns[$rate]['taxableAmount'] + $line['itemTotalAmountWithoutTax']);
        $breakdowns[$rate]['taxAmount'] = viettel_sinvoice_money($breakdowns[$rate]['taxAmount'] + $line['taxAmount']);
    }

    return array_values($breakdowns);
}

function viettel_sinvoice_summarize(array $lines, array $taxBreakdowns): array
{
    $totalWithoutTax = 0;
    $totalTax = 0;

    foreach ($lines as $line) {
        $totalWithoutTax += $line['itemTotalAmountWithoutTax'];
    }
    foreach ($taxBreakdowns as $breakdown) {
        $totalTax += $breakdown['taxAmount'];
    }

    $totalWithoutTax = viettel_sinvoice_money($totalWithoutTax);
    $totalTax = viettel_sinvoice_money($totalTax);

    return [
        'totalAmountWithoutTax' => $totalWithoutTax,
        'totalTaxAmount' => $totalTax,
        'totalAmountWithTax' => viettel_sinvoice_money($totalWithoutTax + $totalTax),
        'totalAmountAfterDiscount' => $totalWithoutTax,
        'discountAmount' => 0,
    ];
}

function viettel_sinvoice_response_result(array $response): array
{
    if (isset($response['result']) && is_array($response['result'])) {
        return $response['result'];
    }
    if (isset($response['data']) && is_array($response['data'])) {
        return $response['data'];
    }

    return $response;
}

function viettel_sinvoice_extract_invoice_no(array $response): string
{
    $result = viettel_sinvoice_response_result($response);

    return viettel_sinvoice_first_non_empty([
        viettel_sinvoice_array_value($result, 'invoiceNo'),
        viettel_sinvoice_array_value($result, 'invoiceNoWithPattern'),
        viettel_sinvoice_array_value($response, 'invoiceNo'),
        viettel_sinvoice_array_value($response, 'invoiceNumber'),
    ]);
}

function viettel_sinvoice_successful_response(array $response): bool
{
    return viettel_sinvoice_extract_invoice_no($response) !== '';
}

function viettel_sinvoice_terminal_error(array $response): bool
{
    $errorCode = strtoupper(viettel_sinvoice_first_non_empty([
        viettel_sinvoice_array_value($response, 'errorCode'),
        viettel_sinvoice_array_value($response, 'code'),
    ]));

    if ($errorCode === '' || $errorCode === 'NULL') {
        return false;
    }

    return !in_array($errorCode, ['GENERAL', 'PROCESSING', 'PENDING', 'INVOICE_DUPLICATE'], true);
}

function viettel_sinvoice_should_async(array $response): bool
{
    if (!empty($response['_async_candidate'])) {
        return true;
    }

    $http = isset($response['_http_code']) ? (int) $response['_http_code'] : 0;
    if ($http >= 500 || $http === 408 || $http === 429 || $http === 0) {
        return true;
    }

    $errorCode = strtoupper(viettel_sinvoice_first_non_empty([
        viettel_sinvoice_array_value($response, 'errorCode'),
        viettel_sinvoice_array_value($response, 'code'),
    ]));
    if (in_array($errorCode, ['INVOICE_DUPLICATE', 'GENERAL', 'PROCESSING', 'PENDING'], true)) {
        return true;
    }

    return viettel_sinvoice_extract_invoice_no($response) === '' && $http >= 200 && $http < 300 && !viettel_sinvoice_terminal_error($response);
}

function viettel_sinvoice_provider_message(array $response, string $default): string
{
    $message = viettel_sinvoice_first_non_empty([
        viettel_sinvoice_array_value($response, 'description'),
        viettel_sinvoice_array_value($response, 'message'),
        viettel_sinvoice_array_value($response, 'error'),
        isset($response['result']) && is_array($response['result']) ? viettel_sinvoice_array_value($response['result'], 'description') : '',
    ]);
    $code = viettel_sinvoice_first_non_empty([
        viettel_sinvoice_array_value($response, 'errorCode'),
        viettel_sinvoice_array_value($response, 'code'),
    ]);

    if ($code !== '') {
        $message = '[' . $code . '] ' . ($message !== '' ? $message : $default);
    }

    return $message !== '' ? $message : $default;
}

function viettel_sinvoice_mark_sent(object $record, array $response, string $source): void
{
    $result = viettel_sinvoice_response_result($response);
    $invoiceNo = viettel_sinvoice_extract_invoice_no($response);
    $metadata = viettel_sinvoice_decode_json(isset($record->metadata) ? $record->metadata : '');
    $metadata['last_sync_source'] = $source;
    $metadata['last_sync_at'] = date('c');

    viettel_sinvoice_update_record((int) $record->id, [
        'invoice_no' => $invoiceNo,
        'transaction_id' => viettel_sinvoice_array_value($result, 'transactionID'),
        'reservation_code' => viettel_sinvoice_array_value($result, 'reservationCode'),
        'code_of_tax' => viettel_sinvoice_array_value($result, 'codeOfTax'),
        'status' => 'sent',
        'last_response' => viettel_sinvoice_json(viettel_sinvoice_redact($response)),
        'metadata' => viettel_sinvoice_json($metadata),
        'issued_at' => viettel_sinvoice_now(),
    ]);

    logActivity('Viettel SInvoice issued invoice ' . $invoiceNo . ' for WHMCS invoice #' . (int) $record->invoice_id);
}

function viettel_sinvoice_mark_async(object $record, string $message, array $context = []): void
{
    $metadata = viettel_sinvoice_decode_json(isset($record->metadata) ? $record->metadata : '');
    $metadata['last_async_at'] = date('c');
    $metadata['last_async_message'] = $message;

    if (!empty($context)) {
        $metadata['last_async_context'] = viettel_sinvoice_redact($context);
    }

    viettel_sinvoice_update_record((int) $record->id, [
        'status' => 'async',
        'last_response' => !empty($context) ? viettel_sinvoice_json(viettel_sinvoice_redact($context)) : (isset($record->last_response) ? $record->last_response : null),
        'metadata' => viettel_sinvoice_json($metadata),
    ]);
}

function viettel_sinvoice_mark_error(object $record, string $message, array $context = []): void
{
    $metadata = viettel_sinvoice_decode_json(isset($record->metadata) ? $record->metadata : '');
    $metadata['last_error_at'] = date('c');
    $metadata['last_error'] = $message;

    if (!empty($context)) {
        $metadata['last_error_context'] = viettel_sinvoice_redact($context);
    }

    viettel_sinvoice_update_record((int) $record->id, [
        'status' => 'error',
        'last_error' => $message,
        'last_response' => !empty($context) ? viettel_sinvoice_json(viettel_sinvoice_redact($context)) : (isset($record->last_response) ? $record->last_response : null),
        'metadata' => viettel_sinvoice_json($metadata),
    ]);
}

function viettel_sinvoice_issue_invoice(int $invoiceId, bool $force = false): array
{
    $message = '';
    if (!viettel_sinvoice_qualify_invoice($invoiceId, $message)) {
        $data = viettel_sinvoice_fetch_invoice($invoiceId);
        $record = viettel_sinvoice_create_invoice_record($invoiceId, (int) $data['invoice']->userid);
        viettel_sinvoice_mark_error($record, $message, ['stage' => 'qualification']);
        return ['success' => false, 'status' => 'error', 'message' => $message];
    }

    $data = viettel_sinvoice_fetch_invoice($invoiceId);
    $record = viettel_sinvoice_create_invoice_record($invoiceId, (int) $data['invoice']->userid);
    if (!$force && in_array((string) $record->status, ['sent', 'async'], true)) {
        return ['success' => true, 'status' => (string) $record->status, 'message' => 'Invoice already has a Viettel SInvoice record.'];
    }

    try {
        $payload = viettel_sinvoice_build_payload($data, $record);
        viettel_sinvoice_update_record((int) $record->id, [
            'status' => 'pending',
            'last_request' => viettel_sinvoice_json(viettel_sinvoice_redact($payload)),
            'last_error' => null,
        ]);

        $response = viettel_sinvoice_api_request(
            'POST',
            '/InvoiceAPI/InvoiceWS/createInvoice/' . rawurlencode(viettel_sinvoice_setting('supplierTaxCode')),
            $payload
        );

        $record = viettel_sinvoice_invoice_record($invoiceId);
        if (viettel_sinvoice_successful_response($response)) {
            viettel_sinvoice_mark_sent($record, $response, 'createInvoice');
            return ['success' => true, 'status' => 'sent', 'message' => 'Invoice issued successfully.'];
        }

        if (viettel_sinvoice_should_async($response)) {
            viettel_sinvoice_mark_async($record, 'Viettel SInvoice response is pending or ambiguous; queued for UUID reconciliation.', $response);
            return ['success' => true, 'status' => 'async', 'message' => 'Invoice queued for reconciliation.'];
        }

        $message = viettel_sinvoice_provider_message($response, 'Viettel SInvoice issue request failed.');
        viettel_sinvoice_mark_error($record, $message, $response);
        return ['success' => false, 'status' => 'error', 'message' => $message];
    } catch (Exception $e) {
        $record = viettel_sinvoice_invoice_record($invoiceId);
        if ($record) {
            viettel_sinvoice_mark_async($record, 'Viettel SInvoice request exception; queued for UUID reconciliation.', ['exception' => get_class($e), 'code' => $e->getCode()]);
        }

        return ['success' => true, 'status' => 'async', 'message' => 'Invoice queued for reconciliation after transport exception.'];
    }
}

function viettel_sinvoice_poll_invoice(int $invoiceId): array
{
    $record = viettel_sinvoice_invoice_record($invoiceId);
    if (!$record) {
        return ['success' => false, 'status' => 'missing', 'message' => 'No Viettel SInvoice record found.'];
    }

    if ((string) $record->status === 'sent') {
        return ['success' => true, 'status' => 'sent', 'message' => 'Invoice is already issued.'];
    }

    $query = http_build_query([
        'supplierTaxCode' => $record->supplier_tax_code ?: viettel_sinvoice_setting('supplierTaxCode'),
        'transactionUuid' => $record->transaction_uuid,
    ], '', '&');

    try {
        $response = viettel_sinvoice_api_request('GET', '/InvoiceAPI/InvoiceWS/searchInvoiceByTransactionUuid?' . $query);
        $record = viettel_sinvoice_invoice_record($invoiceId);

        if (viettel_sinvoice_successful_response($response)) {
            viettel_sinvoice_mark_sent($record, $response, 'searchInvoiceByTransactionUuid');
            return ['success' => true, 'status' => 'sent', 'message' => 'Invoice issued successfully.'];
        }

        $http = isset($response['_http_code']) ? (int) $response['_http_code'] : 0;
        $errorCode = strtoupper(viettel_sinvoice_first_non_empty([
            viettel_sinvoice_array_value($response, 'errorCode'),
            viettel_sinvoice_array_value($response, 'code'),
        ]));
        if ($http === 404 || in_array($errorCode, ['NOT_FOUND', 'INVOICE_NOT_FOUND', 'TRANSACTION_NOT_FOUND'], true)) {
            $message = viettel_sinvoice_provider_message($response, 'Viettel SInvoice could not find the transaction UUID.');
            viettel_sinvoice_mark_error($record, $message, $response);
            return ['success' => false, 'status' => 'error', 'message' => $message];
        }

        $metadata = viettel_sinvoice_decode_json(isset($record->metadata) ? $record->metadata : '');
        $metadata['last_poll_at'] = date('c');
        $pollCount = isset($record->poll_count) ? (int) $record->poll_count + 1 : 1;
        viettel_sinvoice_update_record((int) $record->id, [
            'status' => 'async',
            'poll_count' => $pollCount,
            'last_poll_at' => viettel_sinvoice_now(),
            'last_response' => viettel_sinvoice_json(viettel_sinvoice_redact($response)),
            'metadata' => viettel_sinvoice_json($metadata),
        ]);

        return ['success' => true, 'status' => 'async', 'message' => 'Invoice is still pending.'];
    } catch (Exception $e) {
        $record = viettel_sinvoice_invoice_record($invoiceId);
        if ($record) {
            viettel_sinvoice_mark_async($record, 'Viettel SInvoice poll exception; will retry.', ['exception' => get_class($e), 'code' => $e->getCode()]);
        }

        return ['success' => true, 'status' => 'async', 'message' => 'Invoice poll will retry.'];
    }
}

function viettel_sinvoice_poll_pending(int $limit = 10): int
{
    $records = Capsule::table(VIETTEL_SINVOICE_TABLE)
        ->where('status', 'async')
        ->orderBy('updated_at')
        ->limit(max(1, min(50, $limit)))
        ->get();

    $count = 0;
    foreach ($records as $record) {
        viettel_sinvoice_poll_invoice((int) $record->invoice_id);
        $count++;
    }

    return $count;
}
