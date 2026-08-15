@extends('admin.layout.app', ['title' => 'General Settings'])

@section('content')
<div class="settings-page">
    <form class="ajax_form" method="POST" action="{{ route('admin.settings.post') }}">
        @csrf

        <section class="settings-card" id="general">

            <div class="settings-card-head d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5>General Settings</h5>
                    <p class="text-muted small mb-0">
                        Manage core system identity and default application configuration.
                    </p>
                </div>

                <i class="ri-settings-3-line fs-4"></i>
            </div>


            <ul class="nav nav-tabs mb-3" id="generalTabs">

                <li class="nav-item">
                    <button class="nav-link active"
                            data-bs-toggle="tab"
                            data-bs-target="#organization"
                            type="button">
                        Organization
                    </button>
                </li>

                <li class="nav-item">
                    <button class="nav-link"
                            data-bs-toggle="tab"
                            data-bs-target="#defaults"
                            type="button">
                        Defaults
                    </button>
                </li>

                <li class="nav-item">
                    <button class="nav-link"
                            data-bs-toggle="tab"
                            data-bs-target="#user-defaults"
                            type="button">
                        User Defaults
                    </button>
                </li>

                <li class="nav-item">
                    <button class="nav-link"
                            data-bs-toggle="tab"
                            data-bs-target="#application"
                            type="button">
                        Application
                    </button>
                </li>

            </ul>


            <div class="tab-content">


                {{-- Organization --}}
                <div class="tab-pane fade show active"
                     id="organization">

                    <div class="border rounded p-3">

                        <h6 class="mb-1">
                            Organization Information
                        </h6>

                        <p class="text-muted small mb-3">
                            Basic system and company identity information.
                        </p>


                        <div class="row g-3">


                            <div class="col-md-6">

                                <label class="form-label">
                                    System Name
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text"
                                       name="system_name"
                                       class="form-control"
                                       value="{{ old('system_name', get_settings('system_name')) }}"
                                       placeholder="Enter system name"
                                       required>

                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    System Short Name
                                </label>

                                <input type="text"
                                       name="system_short_name"
                                       class="form-control"
                                       value="{{ old('system_short_name', get_settings('system_short_name')) }}"
                                       placeholder="Example: CRM">

                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Company Name
                                </label>

                                <input type="text"
                                       name="company_name"
                                       class="form-control"
                                       value="{{ old('company_name', get_settings('company_name')) }}"
                                       placeholder="Company name">

                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Legal Business Name
                                </label>

                                <input type="text"
                                       name="legal_business_name"
                                       class="form-control"
                                       value="{{ old('legal_business_name', get_settings('legal_business_name')) }}"
                                       placeholder="Registered business name">

                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Organization Type
                                </label>

                                <select name="organization_type"
                                        class="form-control select2-infinity">

                                    @foreach([
                                        'LLC',
                                        'Corporation',
                                        'Partnership',
                                        'Sole Proprietorship',
                                        'Non Profit'
                                    ] as $type)

                                    <option value="{{ $type }}"
                                        {{ get_settings('organization_type') == $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>

                                    @endforeach

                                </select>

                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Registration Number
                                </label>

                                <input type="text"
                                       name="registration_number"
                                       class="form-control"
                                       value="{{ old('registration_number', get_settings('registration_number')) }}"
                                       placeholder="Registration number">

                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Tax / VAT Number
                                </label>

                                <input type="text"
                                       name="tax_vat_number"
                                       class="form-control"
                                       value="{{ old('tax_vat_number', get_settings('tax_vat_number')) }}"
                                       placeholder="Tax identification number">

                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Website URL
                                </label>

                                <input type="url"
                                       name="website_url"
                                       class="form-control"
                                       value="{{ old('website_url', get_settings('website_url')) }}"
                                       placeholder="https://example.com">

                            </div>


                        </div>

                    </div>

                </div>



                {{-- Defaults --}}
                <div class="tab-pane fade"
                     id="defaults">


                    <div class="border rounded p-3">


                        <h6 class="mb-1">
                            System Defaults
                        </h6>


                        <p class="text-muted small mb-3">
                            Default values used across the application.
                        </p>


                        <div class="row g-3">


                            <div class="col-md-6">

                                <label class="form-label">
                                    Default Pagination
                                </label>

                                <input type="number"
                                       name="default_pagination"
                                       class="form-control"
                                       value="{{ old('default_pagination', get_settings('default_pagination')) }}"
                                       placeholder="15">

                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Default Dashboard
                                </label>


                                <select name="default_dashboard"
                                        class="form-control select2-infinity">


                                    @foreach([
                                        'main'=>'Main Dashboard',
                                        'analytics'=>'Analytics',
                                        'reports'=>'Reports'
                                    ] as $key=>$label)


                                    <option value="{{ $key }}"
                                    {{ get_settings('default_dashboard') == $key ? 'selected':'' }}>

                                        {{ $label }}

                                    </option>


                                    @endforeach


                                </select>

                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Default Currency
                                </label>

                                <input type="text"
                                       name="default_currency"
                                       class="form-control"
                                       value="{{ old('default_currency', get_settings('default_currency')) }}"
                                       placeholder="USD">

                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Currency Position
                                </label>


                                <select name="currency_position"
                                        class="form-control select2-infinity">

                                    <option value="before"
                                    {{ get_settings('currency_position')=='before'?'selected':'' }}>
                                        Before Amount
                                    </option>


                                    <option value="after"
                                    {{ get_settings('currency_position')=='after'?'selected':'' }}>
                                        After Amount
                                    </option>


                                </select>

                            </div>



                        </div>

                    </div>


                </div>




                {{-- User Defaults --}}
                <div class="tab-pane fade"
                     id="user-defaults">


                    <div class="border rounded p-3">


                        <h6 class="mb-1">
                            User Default Settings
                        </h6>


                        <p class="text-muted small mb-3">
                            Default values for newly created users.
                        </p>


                        <div class="row g-3">


                            <div class="col-md-6">

                                <label class="form-label">
                                    Default User Role
                                </label>


                                <select name="default_user_role"
                                        class="form-control select2-infinity">


                                    @foreach([
                                        'user',
                                        'editor',
                                        'admin',
                                        'guest'
                                    ] as $role)


                                    <option value="{{ $role }}"
                                    {{ get_settings('default_user_role')==$role?'selected':'' }}>

                                        {{ ucfirst($role) }}

                                    </option>


                                    @endforeach


                                </select>

                            </div>




                            <div class="col-md-6">

                                <label class="form-label">
                                    Default User Status
                                </label>


                                <select name="default_user_status"
                                        class="form-control select2-infinity">


                                    @foreach([
                                        'active',
                                        'inactive',
                                        'pending'
                                    ] as $status)


                                    <option value="{{ $status }}"
                                    {{ get_settings('default_user_status')==$status?'selected':'' }}>

                                        {{ ucfirst($status) }}

                                    </option>


                                    @endforeach


                                </select>


                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Default Avatar URL
                                </label>


                                <input type="url"
                                       name="default_avatar_url"
                                       class="form-control"
                                       value="{{ old('default_avatar_url', get_settings('default_avatar_url')) }}"
                                       placeholder="https://example.com/avatar.png">


                            </div>


                        </div>


                    </div>


                </div>




                {{-- Application --}}
                <div class="tab-pane fade"
                     id="application">


                    <div class="border rounded p-3">


                        <h6 class="mb-1">
                            Application Information
                        </h6>


                        <p class="text-muted small mb-3">
                            Software version and installation details.
                        </p>



                        <div class="row g-3">


                            <div class="col-md-6">

                                <label class="form-label">
                                    Application Version
                                </label>

                                <input type="text"
                                       name="application_version"
                                       class="form-control"
                                       value="{{ old('application_version', get_settings('application_version')) }}"
                                       placeholder="1.0.0">

                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Build Number
                                </label>

                                <input type="text"
                                       name="build_number"
                                       class="form-control"
                                       value="{{ old('build_number', get_settings('build_number')) }}"
                                       placeholder="2026.01">

                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Environment
                                </label>


                                <select name="environment"
                                        class="form-control select2-infinity">


                                    @foreach([
                                        'local',
                                        'development',
                                        'staging',
                                        'production'
                                    ] as $env)


                                    <option value="{{ $env }}"
                                    {{ get_settings('environment')==$env?'selected':'' }}>

                                        {{ ucfirst($env) }}

                                    </option>


                                    @endforeach


                                </select>


                            </div>



                            <div class="col-md-6">

                                <label class="form-label">
                                    Installation Date
                                </label>

                                <input type="date"
                                       name="installation_date"
                                       class="form-control"
                                       value="{{ old('installation_date', get_settings('installation_date')) }}">

                            </div>



                            <div class="col-md-12">

                                <label class="form-label">
                                    Instance ID
                                </label>


                                <input type="text"
                                       name="instance_id"
                                       class="form-control"
                                       value="{{ get_settings('instance_id') }}"
                                       readonly>


                            </div>


                        </div>


                    </div>


                </div>


            </div>


        </section>


        <div class="settings-save-box mt-3">

            <button type="submit"
                    class="settings-submit">

                <i class="ri-save-3-line"></i>
                Update Settings

            </button>

        </div>


    </form>
</div>
@endsection


@push('scripts')
<script>
$(document).ready(function(){

    _componentSelect();
    _ajaxFormHandler('.ajax_form');

});
</script>
@endpush
