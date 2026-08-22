@extends('admin.layout.app', ['title' => 'Localization Settings'])

@section('content')
    <form class="ajax_form settings-page fm-body" method="POST" enctype="multipart/form-data" action="{{ route('admin.settings.post') }}">
        @csrf

        {{-- ========================================= --}}
        {{-- Date & Time Format --}}
        {{-- ========================================= --}}

        <section class="settings-card" id="datetime">
            <div class="settings-card-head d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5>Date & Time Format</h5>
                    <p class="text-muted small mb-0">
                        Configure global date and time display format.
                    </p>
                </div>

                <i class="ri-calendar-line fs-4"></i>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        Date Format
                    </label>

                    <select name="date_format"
                            class="form-control select">


                        @foreach([
                            'Y-m-d',
                            'd-m-Y',
                            'm/d/Y',
                            'd M Y',
                            'M d, Y'
                        ] as $format)


                        <option value="{{ $format }}"
                        {{ get_settings('date_format') == $format ? 'selected' : '' }}>

                            {{ $format }}

                        </option>


                        @endforeach


                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Time Format
                    </label>
                    <select name="time_format"
                            class="form-control select">


                        <option value="12"
                        {{ get_settings('time_format') == '12' ? 'selected' : '' }}>
                            12 Hour
                        </option>


                        <option value="24"
                        {{ get_settings('time_format') == '24' ? 'selected' : '' }}>
                            24 Hour
                        </option>


                    </select>


                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Week Start Day
                    </label>

                    <select name="week_start"
                            class="form-control select">


                        @foreach([
                            'Sunday',
                            'Monday',
                            'Saturday'
                        ] as $day)


                        <option value="{{ $day }}"
                        {{ get_settings('week_start') == $day ? 'selected' : '' }}>

                            {{ $day }}

                        </option>


                        @endforeach


                    </select>


                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        First Day Of Month
                    </label>

                    <select name="month_start"
                            class="form-control select">


                        <option value="1">
                            1st Day
                        </option>


                        <option value="15">
                            Mid Month
                        </option>


                    </select>


                </div>
            </div>
        </section>

        {{-- ========================================= --}}
        {{-- Number Format --}}
        {{-- ========================================= --}}

        <section class="settings-card" id="number-format">

            <div class="settings-card-head d-flex justify-content-between align-items-center mb-3">

                <div>
                    <h5>Number Format</h5>
                    <p class="text-muted small mb-0">
                        Configure decimal and thousand separator format.
                    </p>
                </div>

                <i class="ri-hashtag fs-4"></i>

            </div>


            <div class="row g-3">


                <div class="col-md-6">

                    <label class="form-label">
                        Decimal Separator
                    </label>

                    <select name="decimal_separator"
                            class="form-control select">

                        <option value="."
                        {{ get_settings('decimal_separator') == '.' ? 'selected' : '' }}>
                            Dot (.)
                        </option>

                        <option value=","
                        {{ get_settings('decimal_separator') == ',' ? 'selected' : '' }}>
                            Comma (,)
                        </option>

                    </select>

                </div>



                <div class="col-md-6">

                    <label class="form-label">
                        Thousand Separator
                    </label>

                    <select name="thousand_separator"
                            class="form-control select">

                        <option value=","
                        {{ get_settings('thousand_separator') == ',' ? 'selected' : '' }}>
                            Comma (,)
                        </option>

                        <option value="."
                        {{ get_settings('thousand_separator') == '.' ? 'selected' : '' }}>
                            Dot (.)
                        </option>

                        <option value=" "
                        {{ get_settings('thousand_separator') == ' ' ? 'selected' : '' }}>
                            Space
                        </option>

                    </select>

                </div>



                <div class="col-md-6">

                    <label class="form-label">
                        Decimal Places
                    </label>


                    <input type="number"
                            class="form-control"
                            name="decimal_places"
                            value="{{ old('decimal_places', get_settings('decimal_places',2)) }}"
                            min="0"
                            max="6">

                </div>



                <div class="col-md-6">

                    <label class="form-label">
                        Number Display Example
                    </label>

                    <input type="text"
                            class="form-control"
                            name="number_example"
                            value="{{ get_settings('number_example','1,234.56') }}"
                            readonly>

                </div>


            </div>


        </section>

        {{-- ========================================= --}}
        {{-- Currency Settings --}}
        {{-- ========================================= --}}

        <section class="settings-card" id="currency">


            <div class="settings-card-head d-flex justify-content-between align-items-center mb-3">

                <div>

                    <h5>Currency Settings</h5>

                    <p class="text-muted small mb-0">
                        Configure default currency and money display.
                    </p>

                </div>

                <i class="ri-money-dollar-circle-line fs-4"></i>

            </div>




            <div class="row g-3">



                <div class="col-md-6">


                    <label class="form-label">
                        Default Currency
                    </label>


                    <select name="currency"
                            class="form-control select">


                        @foreach([
                            'USD'=>'US Dollar ($)',
                            'EUR'=>'Euro (€)',
                            'GBP'=>'British Pound (£)',
                            'BDT'=>'Bangladeshi Taka (৳)',
                            'INR'=>'Indian Rupee (₹)'
                        ] as $code=>$currency)


                            <option value="{{ $code }}"
                            {{ get_settings('currency') == $code ? 'selected' : '' }}>

                                {{ $currency }}

                            </option>


                        @endforeach


                    </select>


                </div>



                <div class="col-md-6">

                    <label class="form-label">
                        Currency Position
                    </label>


                    <select name="currency_position"
                            class="form-control select2-infinity">


                        <option value="before"
                        {{ get_settings('currency_position') == 'before' ? 'selected' : '' }}>
                            Before Amount ($100)
                        </option>


                        <option value="after"
                        {{ get_settings('currency_position') == 'after' ? 'selected' : '' }}>
                            After Amount (100$)
                        </option>


                    </select>


                </div>




                <div class="col-md-6">


                    <label class="form-label">
                        Currency Symbol
                    </label>


                    <input type="text"
                            name="currency_symbol"
                            class="form-control"
                            value="{{ old('currency_symbol', get_settings('currency_symbol','$')) }}">


                </div>




                <div class="col-md-6">


                    <label class="form-label">
                        Currency Code Display
                    </label>


                    <select name="show_currency_code"
                            class="form-control select2-infinity">


                        <option value="1"
                        {{ get_settings('show_currency_code') == 1 ? 'selected' : '' }}>
                            Show (USD 100)
                        </option>


                        <option value="0"
                        {{ get_settings('show_currency_code') == 0 ? 'selected' : '' }}>
                            Hide
                        </option>


                    </select>


                </div>



            </div>


        </section>

        {{-- ========================================= --}}
        {{-- Timezone Settings --}}
        {{-- ========================================= --}}

        <section class="settings-card" id="timezone">


            <div class="settings-card-head d-flex justify-content-between align-items-center mb-3">


                <div>

                    <h5>Timezone Settings</h5>

                    <p class="text-muted small mb-0">
                        Configure system timezone and server time.
                    </p>

                </div>


                <i class="ri-time-line fs-4"></i>


            </div>




            <div class="row g-3">


                <div class="col-md-6">


                    <label class="form-label">
                        Default Timezone
                    </label>


                    <select name="timezone"
                            class="form-control select">


                        @foreach(tz_list() as $timezone)


                            <option value="{{ $timezone['zone'] }}"
                            {{ get_settings('timezone') == $timezone['zone'] ? 'selected' : '' }}>

                                {{ $timezone['diff_from_GMT'] }}
                                -
                                {{ $timezone['zone'] }}

                            </option>


                        @endforeach


                    </select>


                </div>



                <div class="col-md-6">


                    <label class="form-label">
                        Server Time Sync
                    </label>


                    <select name="server_time_sync"
                            class="form-control select2-infinity">


                        <option value="auto">
                            Automatic
                        </option>


                        <option value="manual">
                            Manual
                        </option>


                    </select>


                </div>



            </div>


        </section>

        {{-- ========================================= --}}
        {{-- Regional Settings --}}
        {{-- ========================================= --}}

        <section class="settings-card" id="regional">


            <div class="settings-card-head d-flex justify-content-between align-items-center mb-3">


                <div>

                    <h5>Regional Settings</h5>

                    <p class="text-muted small mb-0">
                        Configure default country and region preferences.
                    </p>

                </div>


                <i class="ri-map-pin-line fs-4"></i>


            </div>




            <div class="row g-3">



                <div class="col-md-6">

                    <label class="form-label">
                        Default Country
                    </label>


                    <select name="default_country"
                            class="form-control select2-infinity">


                        <option value="BD">
                            Bangladesh
                        </option>

                        <option value="US">
                            United States
                        </option>

                        <option value="GB">
                            United Kingdom
                        </option>


                    </select>

                </div>




                <div class="col-md-6">

                    <label class="form-label">
                        State Required
                    </label>


                    <select name="state_required"
                            class="form-control select2-infinity">


                        <option value="1">
                            Enabled
                        </option>


                        <option value="0">
                            Disabled
                        </option>


                    </select>


                </div>



            </div>


        </section>

        {{-- ========================================= --}}
        {{-- Direction Settings --}}
        {{-- ========================================= --}}

        <section class="settings-card" id="direction">

            <div class="settings-card-head d-flex justify-content-between align-items-center mb-3">

                <div>
                    <h5>Direction Settings</h5>
                    <p class="text-muted small mb-0">
                        Configure interface text direction for supported languages.
                    </p>
                </div>

                <i class="ri-align-right fs-4"></i>

            </div>


            <div class="row g-3">


                <div class="col-md-6">

                    <label class="form-label">
                        Default Direction
                    </label>


                    <select name="direction"
                            class="form-control select2-infinity">


                        <option value="ltr"
                        {{ get_settings('direction') == 'ltr' ? 'selected' : '' }}>
                            Left To Right (LTR)
                        </option>


                        <option value="rtl"
                        {{ get_settings('direction') == 'rtl' ? 'selected' : '' }}>
                            Right To Left (RTL)
                        </option>


                    </select>

                </div>



                <div class="col-md-6">

                    <label class="form-label">
                        RTL Support
                    </label>


                    <select name="rtl_support"
                            class="form-control select2-infinity">


                        <option value="1"
                        {{ get_settings('rtl_support') == 1 ? 'selected' : '' }}>
                            Enabled
                        </option>


                        <option value="0"
                        {{ get_settings('rtl_support') == 0 ? 'selected' : '' }}>
                            Disabled
                        </option>


                    </select>


                </div>


            </div>

        </section>


        {{-- ========================================= --}}
        {{-- Translation Settings --}}
        {{-- ========================================= --}}

        <section class="settings-card" id="translation">


            <div class="settings-card-head d-flex justify-content-between align-items-center mb-3">


                <div>

                    <h5>Translation Settings</h5>

                    <p class="text-muted small mb-0">
                        Manage translation behavior and language fallback.
                    </p>

                </div>


                <i class="ri-file-translate-line fs-4"></i>


            </div>



            <div class="row g-3">


                <div class="col-md-6">

                    <label class="form-label">
                        Translation Mode
                    </label>


                    <select name="translation_mode"
                            class="form-control select2-infinity">


                        <option value="database"
                        {{ get_settings('translation_mode') == 'database' ? 'selected' : '' }}>
                            Database
                        </option>


                        <option value="file"
                        {{ get_settings('translation_mode') == 'file' ? 'selected' : '' }}>
                            Language File
                        </option>


                        <option value="both"
                        {{ get_settings('translation_mode') == 'both' ? 'selected' : '' }}>
                            Both
                        </option>


                    </select>


                </div>




                <div class="col-md-6">

                    <label class="form-label">
                        Missing Translation Handling
                    </label>


                    <select name="missing_translation"
                            class="form-control select2-infinity">


                        <option value="key">
                            Show Translation Key
                        </option>


                        <option value="empty">
                            Show Empty
                        </option>


                        <option value="fallback">
                            Use Fallback Language
                        </option>


                    </select>


                </div>



                <div class="col-md-6">

                    <label class="form-label">
                        Translation Cache
                    </label>


                    <select name="translation_cache"
                            class="form-control select2-infinity">


                        <option value="1">
                            Enabled
                        </option>


                        <option value="0">
                            Disabled
                        </option>


                    </select>


                </div>



            </div>


        </section>

        {{-- ========================================= --}}
        {{-- Address Format --}}
        {{-- ========================================= --}}

        <section class="settings-card" id="address-format">


            <div class="settings-card-head d-flex justify-content-between align-items-center mb-3">


                <div>

                    <h5>Address Format</h5>

                    <p class="text-muted small mb-0">
                        Configure customer and company address display format.
                    </p>

                </div>


                <i class="ri-map-pin-user-line fs-4"></i>


            </div>



            <div class="row g-3">


                <div class="col-md-6">


                    <label class="form-label">
                        Address Display Order
                    </label>


                    <select name="address_format"
                            class="form-control select2-infinity">


                        <option value="country_first">
                            Country First
                        </option>


                        <option value="city_first">
                            City First
                        </option>


                        <option value="street_first">
                            Street First
                        </option>


                    </select>


                </div>



                <div class="col-md-6">


                    <label class="form-label">
                        Postal Code Position
                    </label>


                    <select name="postal_position"
                            class="form-control select2-infinity">


                        <option value="before">
                            Before City
                        </option>


                        <option value="after">
                            After City
                        </option>


                    </select>


                </div>



            </div>


        </section>

        {{-- ========================================= --}}
        {{-- Measurement Unit --}}
        {{-- ========================================= --}}

        <section class="settings-card" id="measurement">


            <div class="settings-card-head d-flex justify-content-between align-items-center mb-3">


                <div>

                    <h5>Measurement Unit</h5>

                    <p class="text-muted small mb-0">
                        Configure weight, length and distance units.
                    </p>

                </div>


                <i class="ri-ruler-line fs-4"></i>


            </div>



            <div class="row g-3">


                <div class="col-md-6">


                    <label class="form-label">
                        Weight Unit
                    </label>


                    <select name="weight_unit"
                            class="form-control select2-infinity">


                        <option value="kg">
                            Kilogram (KG)
                        </option>


                        <option value="lb">
                            Pound (LB)
                        </option>


                        <option value="g">
                            Gram (G)
                        </option>


                    </select>


                </div>



                <div class="col-md-6">


                    <label class="form-label">
                        Distance Unit
                    </label>


                    <select name="distance_unit"
                            class="form-control select2-infinity">


                        <option value="km">
                            Kilometer (KM)
                        </option>


                        <option value="mile">
                            Mile
                        </option>


                    </select>


                </div>



            </div>


        </section>


        {{-- ========================================= --}}
        {{-- Save Button --}}
        {{-- ========================================= --}}


        <div class="d-flex justify-content-end mt-4">


            <button type="submit"
                    id="submit"
                    class="btn btn-primary">


                <i class="ri-save-3-line me-1"></i>

                Save Localization


            </button>



            <button type="button"
                    id="submitting"
                    class="btn btn-primary"
                    style="display:none"
                    disabled>


                <span class="spinner-border spinner-border-sm me-2"></span>

                Saving...


            </button>


        </div>
    </form>

@endsection



@push('scripts')

<script>

$(document).ready(function(){

    _componentSelect();

    _componentDropify();

    _ajaxFormHandler('.ajax_form');


});


</script>

@endpush
