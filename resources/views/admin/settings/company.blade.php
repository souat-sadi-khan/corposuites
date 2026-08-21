@extends('admin.layout.app', ['title' => 'Company Profile'])

@section('content')

    <form class="ajax_form settings-page" method="POST" enctype="multipart/form-data" action="{{ route('admin.settings.post') }}">
        @csrf

        {{-- Company Identity --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Company Identity</h5>
                    <p>Legal identity details used across the ERP (payslips, invoices, HR letters).</p>
                </div>

                <i class="ri-building-4-line"></i>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        Legal Company Name
                    </label>

                    <input type="text"
                            name="company_legal_name"
                            class="form-control"
                            value="{{ get_settings('company_legal_name') }}"
                            placeholder="e.g. CorpoSuites Trading LLC">
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Trading / Display Name
                    </label>

                    <input type="text"
                            name="company_trading_name"
                            class="form-control"
                            value="{{ get_settings('company_trading_name') }}"
                            placeholder="Name shown on documents">

                    <small class="text-muted">
                        Falls back to the Brand Name if left blank.
                    </small>
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        Registration Number
                    </label>

                    <input type="text"
                            name="company_registration_number"
                            class="form-control"
                            value="{{ get_settings('company_registration_number') }}"
                            placeholder="Trade license / CR number">
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        Tax / VAT Number
                    </label>

                    <input type="text"
                            name="company_tax_number"
                            class="form-control"
                            value="{{ get_settings('company_tax_number') }}"
                            placeholder="VAT / TRN / EIN">
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        Industry
                    </label>

                    <input type="text"
                            name="company_industry"
                            class="form-control"
                            value="{{ get_settings('company_industry') }}"
                            placeholder="e.g. Wholesale Trading">
                </div>
            </div>
        </div>

        {{-- Contact Information --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Contact Information</h5>
                    <p>Primary contact details for the organization.</p>
                </div>

                <i class="ri-contacts-line"></i>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">
                        Company Email
                    </label>

                    <input type="email"
                            name="company_email"
                            class="form-control"
                            value="{{ get_settings('company_email') }}"
                            placeholder="info@company.com">
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        Phone Number
                    </label>

                    <input type="text"
                            name="company_phone"
                            class="form-control"
                            value="{{ get_settings('company_phone') }}"
                            placeholder="+1 234 567 8900">
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        Alternate / Fax Number
                    </label>

                    <input type="text"
                            name="company_fax"
                            class="form-control"
                            value="{{ get_settings('company_fax') }}"
                            placeholder="Optional">
                </div>

                <div class="col-md-12">
                    <label class="form-label">
                        Website
                    </label>

                    <input type="url"
                            name="company_website"
                            class="form-control"
                            value="{{ get_settings('company_website') }}"
                            placeholder="https://example.com">
                </div>
            </div>
        </div>

        {{-- Registered Address --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Registered Address</h5>
                    <p>Head office / registered address used on official documents.</p>
                </div>

                <i class="ri-map-pin-2-line"></i>
            </div>

            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">
                        Address Line
                    </label>

                    <textarea name="company_address" class="form-control" rows="2" placeholder="Street, building, floor">{{ get_settings('company_address') }}</textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        City
                    </label>

                    <input type="text" name="company_city" class="form-control" value="{{ get_settings('company_city') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        State / Province
                    </label>

                    <input type="text" name="company_state" class="form-control" value="{{ get_settings('company_state') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Postal / ZIP Code
                    </label>

                    <input type="text" name="company_postal_code" class="form-control" value="{{ get_settings('company_postal_code') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Country
                    </label>

                    <input type="text" name="company_country" class="form-control" value="{{ get_settings('company_country') }}">
                </div>
            </div>
        </div>

        {{-- Financial Year --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Financial Year</h5>
                    <p>Used by Accounting and HR reports. HRM's own leave/attendance rules live under Settings → HRM Settings.</p>
                </div>

                <i class="ri-calendar-2-line"></i>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">
                        Fiscal Year Start Month
                    </label>

                    <select name="fiscal_year_start_month" class="form-control select">
                        @php
                            $months = [
                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                            ];
                            $currentFyStart = get_settings('fiscal_year_start_month', 1);
                        @endphp

                        @foreach($months as $num => $label)
                            <option value="{{ $num }}" {{ (int) $currentFyStart === $num ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    <small class="text-muted">
                        E.g. select January for a calendar-year fiscal year, or July for a July-June fiscal year.
                    </small>
                </div>
            </div>
        </div>

        <div class="settings-save-box mt-3">
            <button type="submit" id="submit" class="settings-submit">
                <i class="ri-save-3-line"></i>
                Update Settings
            </button>
            <button type="button" id="submitting" disabled style="display: none;" class="settings-submit">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        $(document).ready(function(){
            _componentSelect();
            _ajaxFormHandler('.ajax_form');
        });
    </script>
@endpush
