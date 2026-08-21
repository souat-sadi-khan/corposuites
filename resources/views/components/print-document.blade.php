@props([
    'title',
    'subtitle' => null,
    'meta' => [],
])

{{--
    Reusable standalone print / "Save as PDF" document shell.

    Renders a full, self-contained HTML page (company header, an
    optional meta info box, whatever is passed into the default
    slot, and a footer) — meant to be opened in a new tab and
    printed to PDF via the browser's own print dialog, the same
    convention every other print view in this project uses
    (Delivery Notes, Barcode Generator, POS receipts), rather than
    adding a server-side PDF library dependency.

    Company details come from Settings -> Company / Branding.
    Money values passed through $meta or the slot should already
    be formatted with format_currency() by the caller.
--}}

@php
    $companyName = get_settings('company_trading_name')
        ?: get_settings('company_legal_name')
        ?: get_settings('brand_name')
        ?: config('app.name');

    $addressParts = array_filter([
        get_settings('company_address'),
        get_settings('company_city'),
        get_settings('company_state'),
        get_settings('company_postal_code'),
        get_settings('company_country'),
    ]);

    $companyAddress = implode(', ', $addressParts);
    $companyPhone = get_settings('company_phone');
    $companyEmail = get_settings('company_email');
    $companyWebsite = get_settings('company_website');
    $companyTax = get_settings('company_tax_number');
    $companyLogo = get_settings('system_logo');

    $generatedBy = auth()->guard('admin')->check()
        ? auth()->guard('admin')->user()->name
        : null;
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2430;
            margin: 32px 40px;
            font-size: 13px;
        }

        .print-actions {
            margin-bottom: 20px;
        }

        .print-actions button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid #4f52e8;
            background: #4f52e8;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #1f2430;
            margin-bottom: 18px;
        }

        .doc-header-company {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .doc-logo {
            max-width: 64px;
            max-height: 64px;
            object-fit: contain;
        }

        .doc-company-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .doc-company-line {
            font-size: 11.5px;
            color: #555;
            line-height: 1.5;
            max-width: 340px;
        }

        .doc-header-title {
            text-align: right;
        }

        .doc-header-title h1 {
            font-size: 19px;
            margin: 0 0 4px;
        }

        .doc-subtitle {
            font-size: 12.5px;
            color: #667085;
        }

        .doc-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            background: #f7f8fa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 16px;
            margin-bottom: 20px;
        }

        .doc-meta-item {
            font-size: 12px;
        }

        .doc-meta-item span {
            display: block;
            color: #8a9199;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 2px;
        }

        .doc-meta-item strong {
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        th, td {
            border: 1px solid #e2e5ea;
            padding: 8px 10px;
            text-align: left;
            font-size: 12.5px;
        }

        th {
            background: #f5f6f8;
            font-weight: 700;
        }

        .doc-footer {
            margin-top: 40px;
            padding-top: 12px;
            border-top: 1px solid #e2e5ea;
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            color: #8a9199;
        }

        @media print {
            .print-actions { display: none; }
            body { margin: 8mm 12mm; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button onclick="window.print()"><i class="ri-printer-line"></i> Print / Save as PDF</button>
    </div>

    <div class="doc-header">
        <div class="doc-header-company">
            @if($companyLogo)
                <img class="doc-logo" src="{{ asset($companyLogo) }}" alt="Logo">
            @endif
            <div>
                <div class="doc-company-name">{{ $companyName }}</div>

                @if($companyAddress)
                    <div class="doc-company-line">{{ $companyAddress }}</div>
                @endif

                <div class="doc-company-line">
                    {{ implode(' · ', array_filter([$companyPhone, $companyEmail, $companyWebsite])) }}
                </div>

                @if($companyTax)
                    <div class="doc-company-line">Tax / VAT No: {{ $companyTax }}</div>
                @endif
            </div>
        </div>

        <div class="doc-header-title">
            <h1>{{ $title }}</h1>
            @if($subtitle)
                <div class="doc-subtitle">{{ $subtitle }}</div>
            @endif
        </div>
    </div>

    @if(count($meta))
        <div class="doc-meta">
            @foreach($meta as $label => $value)
                <div class="doc-meta-item">
                    <span>{{ $label }}</span>
                    <strong>{{ $value }}</strong>
                </div>
            @endforeach
        </div>
    @endif

    <div class="doc-content">
        {{ $slot }}
    </div>

    <div class="doc-footer">
        <div>Generated on {{ now()->format('d M Y, h:i A') }}{{ $generatedBy ? ' by ' . $generatedBy : '' }}</div>
        <div>{{ $companyName }}{{ $companyWebsite ? ' — ' . $companyWebsite : '' }}</div>
    </div>
</body>
</html>
