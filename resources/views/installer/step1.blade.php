@extends('installer.layout', ['title' => 'System Requirements'])

@section('content')

    <div class="page-wrapper v-100" id="pageWrapper">

        <div class="step-one-card reg-card" id="regCard">

            <!-- Logo + heading -->
            <div class="text-center">
                <div class="logo">
                    <img src="{{ asset('assets/system/images/logo.png') }}" alt="Base Admin Project Logo">
                </div>
                <h1 class="card-title">System Requirements</h1>
                <p class="card-sub">Please ensure that your server meets the following requirements before proceeding with the installation.</p>
            </div>

            <div class="row">
                <div class="col-md-12 d-flex flex-column justify-content-center">
                    <div class="card p-3 mb-4" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light top-0">
                                <tr>
                                    <th>Requirement</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- PHP Version --}}
                                <tr>
                                    <td>
                                        <strong>{{ $requirements['php']['name'] }}</strong><br>
                                        <small>
                                            Current Version: {{ $requirements['php']['current'] }}<br>
                                            @if(!$requirements['php']['status'])
                                                <span class="text-warning">Upgrade PHP to >= 8.1</span>
                                            @else
                                                <span class="text-success">PHP version is OK</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        @if($requirements['php']['status'])
                                            <i class="ri-checkbox-circle-line text-success fs-5"></i>
                                        @else
                                            <i class="ri-close-circle-line text-danger fs-5"></i>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Extensions --}}
                                @foreach($requirements['extensions'] as $ext => $status)
                                <tr>
                                    <td>
                                        Extension: {{ $ext }}<br>
                                        <small>
                                            @if(!$status)
                                                <span class="text-warning">Install {{ $ext }} PHP extension</span>
                                            @else
                                                <span class="text-success">{{ $ext }} installed</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        @if($status)
                                            <i class="ri-checkbox-circle-line text-success fs-5"></i>
                                        @else
                                            <i class="ri-close-circle-line text-danger fs-5"></i>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach

                                {{-- Writable Folders --}}
                                @foreach($requirements['permissions'] as $folder => $status)
                                <tr>
                                    <td>
                                        Writable: {{ $folder }}<br>
                                        <small>
                                            @if(!$status)
                                                <span class="text-warning">Make folder writable (chmod 775 or 777)</span>
                                            @else
                                                <span class="text-success">Folder is writable</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        @if($status)
                                            <i class="ri-checkbox-circle-line text-success fs-5"></i>
                                        @else
                                            <i class="ri-close-circle-line text-danger fs-5"></i>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach

                                {{-- PHP Recommended Settings --}}
                                @foreach($requirements['settings'] as $setting => $ok)
                                    <tr>
                                        <td>
                                            PHP Setting: {{ $setting }}<br>
                                            <small>
                                                @if(!$ok)
                                                    <span class="text-warning">Increase {{ $setting }} in php.ini</span>
                                                @else
                                                    <span class="text-success">{{ $setting }} is OK</span>
                                                @endif
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @if($ok)
                                                <i class="ri-checkbox-circle-line text-success fs-5"></i>
                                            @else
                                                <i class="ri-close-circle-line text-danger fs-5"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach

                                {{-- Disabled Functions --}}
                                @if(!empty($requirements['disabled_functions']))
                                    <tr>
                                        <td>
                                            Disabled Functions<br>
                                            <small>{{ implode(', ', $requirements['disabled_functions']) }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning">Check</span>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    {{-- Buttons --}}
                    <div id="installer-buttons">
                        <button id="next" class="btn btn-primary w-100 py-2 mb-3" style="display:none;">
                            Next <i class="ri-arrow-right-long-line ms-2"></i>
                        </button>
                        <button id="refresh" class="btn btn-warning w-100 py-2 mb-3" style="display:none;">
                            Refresh <i class="ri-restart-line ms-2"></i>
                        </button>
                        <button id="processing" disabled style="display: none;" class="btn btn-primary w-100 py-2 mb-3">
                            Processing
                            <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            // Check if all required pass
            let allPassed = true;

            // PHP version
            if (!{{ $requirements['php']['status'] ? 'true' : 'false' }}) allPassed = false;

            // Extensions
            @foreach($requirements['extensions'] as $ext => $status)
                if (!{{ $status ? 'true' : 'false' }}) allPassed = false;
            @endforeach

            // Permissions
            @foreach($requirements['permissions'] as $folder => $status)
                if (!{{ $status ? 'true' : 'false' }}) allPassed = false;
            @endforeach

            // Recommended settings
            @foreach($requirements['settings'] as $setting => $ok)
                if (!{{ $ok ? 'true' : 'false' }}) allPassed = false;
            @endforeach

            if(allPassed) {
                $('#next').show();
            } else {
                $('#refresh').show();
            }

            // Next button click -> go to next step
            $('#next').click(function(){
                $(this).hide();
                $('#processing').show();
                window.location.href = '/install/license';
            });

            // Refresh button click
            $('#refresh').click(function(){
                location.reload();
            });

        });
    </script>
@endpush
