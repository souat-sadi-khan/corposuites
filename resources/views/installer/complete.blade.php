@extends('installer.layout', ['title' => 'License Verification'])

@section('content')
    <div class="page-wrapper" id="pageWrapper">
        <div class="reg-card" id="regCard">
            <div class="text-center">
                <div class="logo">
                    <img src="{{ asset('assets/system/images/logo.png') }}" alt="Base Admin Project Logo">
                </div>
                @if($status)
                    <div class="text-center">
                        <i class="ri-checkbox-circle-line text-success fs-1"></i>
                        <h2 class="mt-3">Installation Completed!</h2>
                        <p class="text-muted">Your system is ready to use.</p>
                        <button id="next" class="btn btn-primary w-100 py-2 mb-3">
                            Go to Admin Login <i class="ri-arrow-right-line ms-2"></i>
                        </button>
                        <button id="processing" disabled style="display: none;" class="btn btn-primary w-100 py-2 mb-3">
                            Processing
                            <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                @else
                    <div class="text-center">
                        <i class="ri-close-circle-line text-danger fs-1"></i>
                        <h2 class="mt-3">Installation Error</h2>
                        <ul class="list-group mt-3">
                            @foreach($errors as $err)
                                <li class="list-group-item list-group-item-danger">{{ $err }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ url('/install') }}" class="btn btn-warning mt-3">Go Back to Installer</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $("#next").click(function(){
            $("#next").hide();
            $("#processing").show();
            setTimeout(() => {
                window.location="/"
            }, 500);
        })

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
