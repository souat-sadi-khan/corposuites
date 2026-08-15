@extends('installer.layout', ['title' => 'System Setup'])

@section('content')
    <div class="page-wrapper" id="pageWrapper">
        <div class="reg-card" id="regCard">
            <div class="text-center">
                <div class="logo">
                    <img src="{{ asset('assets/system/images/logo.png') }}" alt="Base Admin Project Logo">
                </div>
                <h1 class="card-title">System Setup</h1>
                <p class="card-sub">Set up your system account to get started with the platform.</p>
            </div>

            <form id="companyForm">
                @csrf

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">System Name</label>
                        <input type="text" name="system_name" id="system_name" class="form-control" required>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">System Email</label>
                        <input type="email" name="system_email" id="system_email" class="form-control" required>
                    </div>

                    <!-- currency -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency" id="currency" class="form-control" value="BDT">
                    </div>

                    <!-- currency_symbol -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Currency Symbol</label>
                        <input type="text" name="currency_symbol" id="currency_symbol" class="form-control" value="$" required>
                    </div>

                    <div class="col-md-12 mb-3">
                        <button type="submit" id="adminSaveBtn" class="btn btn-primary w-100">
                            Update
                        </button>

                        <button type="button" id="adminProcessing" class="btn btn-primary w-100" style="display:none;" disabled>
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
        $("#companyForm").submit(function(e){
            e.preventDefault();

            $("#adminSaveBtn").hide();
            $("#adminProcessing").show();

            $.ajax({
                url:"/install/company",
                type:"POST",
                data: $(this).serialize(),
                success:function(res){
                    $("#adminSaveBtn").show();
                    $("#adminProcessing").hide();

                    if(res.status)
                    {
                        Lobibox.notify('success', {
                            size:'mini',
                            rounded:true,
                            position:'bottom right',
                            icon:'ri-checkbox-circle-line',
                            msg: res.message
                        });

                        setTimeout(function(){
                            window.location="/install/complete";
                        },1200);
                    }
                    else
                    {
                        Lobibox.notify('error', {
                            size:'mini',
                            rounded:true,
                            position:'bottom right',
                            icon:'ri-close-circle-line',
                            msg: res.message
                        });
                    }
                },
                error:function(xhr){
                    $("#adminSaveBtn").show();
                    $("#adminProcessing").hide();

                    if(xhr.status === 422)
                    {
                        let errors = xhr.responseJSON.errors;
                        let message = '';

                        $.each(errors, function(key, value){
                            message += value + '<br>';
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
                        size:'mini',
                        rounded:true,
                        position:'bottom right',
                        icon:'ri-close-circle-line',
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
