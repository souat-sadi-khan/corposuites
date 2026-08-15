@extends('installer.layout', ['title' => 'Admin Setup'])

@section('content')
    <div class="page-wrapper" id="pageWrapper">
        <div class="reg-card" id="regCard">
            <div class="text-center">
                <div class="logo">
                    <img src="{{ asset('assets/system/images/logo.png') }}" alt="Base Admin Project Logo">
                </div>
                <h1 class="card-title">Super Admin Setup</h1>
                <p class="card-sub">Please enter your details to create your super admin account.</p>
            </div>

            <form id="adminForm">
                @csrf
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" style="display:none;" autocomplete="username"/>
                        <input type="email" name="email" id="email" autocomplete="none" class="form-control">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" style="display:none;" autocomplete="new-password"/>
                            <input type="password" name="password" id="password" class="form-control border-2 py-2" autocomplete="none">
                            <button class="btn btn-outline-secondary border border-2" type="button" id="togglePassword">
                                <i class="ri-eye-line fs-5 lh-0"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control border-2 py-2">
                            <button class="btn btn-outline-secondary border border-2" type="button" id="togglePassword1">
                                <i class="ri-eye-line fs-5 lh-0"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-12 mb-3">
                        <button type="submit" id="adminSaveBtn" class="btn btn-primary w-100">Create Admin</button>

                        <button type="button" id="adminProcessing" class="btn btn-primary w-100" style="display:none;" disabled>
                            Creating Admin <span class="spinner-border spinner-border-sm ms-2"></span>
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const togglePassword = document.querySelector("#togglePassword");
        const togglePassword1 = document.querySelector("#togglePassword1");
        const password = document.querySelector("#password");
        const password_confirmation = document.querySelector("#password_confirmation");
        togglePassword.addEventListener("click", function () {
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            this.querySelector("i").classList.toggle("ri-eye-line");
            this.querySelector("i").classList.toggle("ri-eye-off-line");
        });

        togglePassword1.addEventListener("click", function () {
            const type = password_confirmation.getAttribute("type") === "password" ? "text" : "password";
            password_confirmation.setAttribute("type", type);
            this.querySelector("i").classList.toggle("ri-eye-line");
            this.querySelector("i").classList.toggle("ri-eye-off-line");
        });

        $("#adminForm").submit(function(e){
            e.preventDefault();

            let name = $("#name").val().trim();
            let email = $("#email").val().trim();
            let password = $("#password").val();
            let password_confirmation = $("#password_confirmation").val();

            if(name === "" || email === "" || password === "" || password_confirmation === "")
            {
                Lobibox.notify('warning', {
                    size: 'mini',
                    rounded: true,
                    position: 'bottom right',
                    icon: 'ri-error-warning-line',
                    msg: 'All fields are required.'
                });
                return;
            }

            if(password !== password_confirmation)
            {
                Lobibox.notify('warning', {
                    size: 'mini',
                    rounded: true,
                    position: 'bottom right',
                    icon: 'ri-error-warning-line',
                    msg: 'Passwords do not match.'
                });
                return;
            }

            $("#adminSaveBtn").hide();
            $("#adminProcessing").show();

            $.ajax({
                url:"/install/admin",
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
                            window.location="/install/company";
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
