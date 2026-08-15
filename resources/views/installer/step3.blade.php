@extends('installer.layout', ['title' => 'Database Configuration'])

@section('content')
    <div class="page-wrapper" id="pageWrapper">
        <div class="reg-card" id="regCard">
            <div class="text-center">
                <div class="logo">
                    <img src="{{ asset('assets/system/images/logo.png') }}" alt="Base Admin Project Logo">
                </div>
                <h1 class="card-title">Database Configuration</h1>
                <p class="card-sub">Please enter your database details to proceed.</p>
            </div>

            <form id="dbForm">
                @csrf
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Database Name</label>
                        <input name="db_name" id="db_name" class="form-control" placeholder="">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Database Username</label>
                        <input type="text" style="display:none;" autocomplete="username"/>
                        <input name="db_user" id="db_user" class="form-control" autocomplete="none" placeholder="">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Database Password</label>
                        <input type="password" style="display:none;" autocomplete="new-password"/>
                        <input type="password" name="db_pass" id="db_pass" class="form-control" autocomplete="none" placeholder="">
                    </div>

                    <div class="col-md-12 mb-3">
                        <button type="submit" id="dbSaveBtn" class="btn btn-primary w-100">
                            Save & Continue
                        </button>

                        <button type="button" id="dbProcessing"
                            class="btn btn-primary w-100"
                            style="display:none;" disabled>
                            Processing
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
        $("#dbForm").submit(function(e){
            e.preventDefault();

            let dbName = $("#db_name").val().trim();
            let dbUser = $("#db_user").val().trim();

            if(dbName === "" || dbUser === "")
            {
                Lobibox.notify('warning', {
                    size: 'mini',
                    rounded: true,
                    position: 'bottom right',
                    icon: 'ri-error-warning-line',
                    msg: 'Database name and username are required.'
                });
                return;
            }

            $("#dbSaveBtn").hide();
            $("#dbProcessing").show();

            $.ajax({
                url: "/install/database",
                type: "POST",
                data: $(this).serialize(),
                success: function(res){
                    $("#dbSaveBtn").show();
                    $("#dbProcessing").hide();

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
                            window.location="/install/migration";
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
                    $("#dbSaveBtn").show();
                    $("#dbProcessing").hide();

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

        @if(session('error'))
            Lobibox.notify('error', {
                size: 'mini',
                rounded: true,
                icon: 'ri-close-circle-line',
                position: 'bottom right',
                msg: "{{ session('error') }}"
            });
        @endif

        @if(session('success'))
            Lobibox.notify('success', {
                size: 'mini',
                rounded: true,
                icon: 'ri-checkbox-circle-line',
                position: 'bottom right',
                msg: "{{ session('success') }}"
            });
        @endif

    </script>

@endpush
