@extends('installer.layout', ['title' => 'License Verification'])

@section('content')
    <div class="page-wrapper" id="pageWrapper">
        <div class="reg-card" id="regCard">
            <div class="text-center">
                <div class="logo">
                    <img src="{{ asset('assets/system/images/logo.png') }}" alt="Base Admin Project Logo">
                </div>
                <h1 class="card-title">Access Key? 🔑</h1>
                <p class="card-sub">Please enter your access key to verify your license.</p>
            </div>

            <form id="licenseForm">
                @csrf

                <div class="row">
                    <div class="col-md-12 mb-3 form-group">
                        <label for="access_key" class="form-label">Access Key</label>
                        <input type="text" id="access_key" name="access_key" class="form-control border-2 py-2" placeholder="Enter Access Key">
                    </div>

                    <div class="col-md-12">
                        <button type="submit" id="verifyLicenseBtn" class="btn btn-primary w-100 py-2">
                            Verify License <i class="ri-shield-check-line ms-2"></i>
                        </button>

                        <button type="button" disabled id="processing"
                            class="btn btn-primary w-100 py-2"
                            style="display:none;">
                            Verifying License
                            <span class="spinner-border spinner-border-sm ms-2"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $("#licenseForm").submit(function(e){
            e.preventDefault();

            let accessKey = $("#access_key").val().trim();

            if(accessKey === "")
            {
                Lobibox.notify('warning', {
                    size: 'mini',
                    rounded: true,
                    position: 'bottom right',
                    icon: 'ri-error-warning-line',
                    msg: 'Access Key is required.'
                });
                return;
            }

            $("#verifyLicenseBtn").hide();
            $("#processing").show();

            $.ajax({
                url: "/install/license",
                type: "POST",
                data: {
                    access_key: accessKey,
                    _token: $('input[name="_token"]').val()
                },
                success: function(res){
                    $("#verifyLicenseBtn").show();
                    $("#processing").hide();

                    if(res.status)
                    {
                        Lobibox.notify('success', {
                            size: 'mini',
                            rounded: true,
                            icon: 'ri-checkbox-circle-line',
                            position: 'bottom right',
                            msg: res.message
                        });

                        setTimeout(function(){
                            window.location = "/install/database";
                        },1200);
                    }
                    else
                    {
                        Lobibox.notify('error', {
                            size: 'mini',
                            rounded: true,
                            icon: 'ri-close-circle-line',
                            position: 'bottom right',
                            msg: res.message
                        });
                    }
                },
                error: function(xhr){
                    $("#verifyLicenseBtn").show();
                    $("#processing").hide();

                    if(xhr.status === 422)
                    {
                        let errors = xhr.responseJSON.errors;
                        let message = '';

                        $.each(errors, function(key, value){
                            message += value[0] + '<br>';
                        });

                        Lobibox.notify('warning', {
                            size: 'mini',
                            rounded: true,
                            icon: 'ri-error-warning-line',
                            position: 'bottom right',
                            msg: message
                        });

                        return;
                    }

                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        icon: 'ri-close-circle-line',
                        position: 'bottom right',
                        msg: 'Server error. Please try again.'
                    });
                }
            });
        });

    </script>
@endpush
