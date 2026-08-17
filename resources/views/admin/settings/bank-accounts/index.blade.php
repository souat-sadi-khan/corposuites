@extends('admin.layout.app', ['title' => 'My Bank Accounts', 'offcanvas' => '50%', 'modal' => 'lg'])

@section('content')
    <div class="container-fluid p-0 profile-page">
        <div class="pm-hero card">
            <div class="pme-cover pme-cover{{ $profile->cover_photo ? '-'. $profile->cover_photo : '' }}" ></div>

            <div class="pm-header">
                <div class="pm-user">
                    <img
                        class="pm-avatar"
                        src="{{ Auth::guard('admin')->user()->avatar ? asset(Auth::guard('admin')->user()->avatar) : asset('assets/system/images/avatar.png') }}"
                        alt="{{ Auth::guard('admin')->user()->name }}"
                    >

                    <div class="pm-user-info">
                        <h4>{{ Auth::guard('admin')->user()->name }}</h4>
                        <p>
                            {{ $profile->designation ?? 'Designation Not Set' }}
                            &bull;
                            {{ $profile->city ?? 'City Not Set' }}
                            &bull;
                            Joined {{ date('F Y', strtotime(Auth::guard('admin')->user()->created_at)) }}
                        </p>

                        <div class="pm-badges">
                            <span>13.5k Tasks</span>
                            <span>146 Projects</span>
                            <span>897 Connections</span>
                        </div>
                    </div>
                </div>

                <div class="pm-actions">
                    <a href="{{ route('admin.profile') }}" class="pm-btn pm-btn-outline">
                        <i class="ri-edit-box-line"></i>
                        Back to Profile
                    </a>

                    <!-- Add Button -->
                    <button id="openModal" data-url="{{ route('admin.bank-accounts.create', ['employee_id' => Auth::guard('admin')->user()->employee_id ]) }}" class="btn-nx-primary">
                        <i class="ri-add-line"></i>
                        Add Bank Account
                    </button>
                </div>
            </div>
        </div>

        @include('admin.settings._partials')

        <div class="nx-card tl-card">
            <div class="table-responsive">
                <table id="bankAccountTable" data-url="{{ route('admin.profile.bank-accounts') }}" data-employee-id="{{ request('employee_id') }}" class="tl-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Bank</th>
                            <th>Branch / IFSC</th>
                            <th>Status</th>
                            <th class="no-sort text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="tl-footer">
                <div class="tl-info" id="tlInfo"></div>
                <div class="tl-pagination">
                    <button class="tl-page-btn" id="tlPrev" title="Previous page">
                        <i class="ri-arrow-left-s-line"></i>
                    </button>
                    <button class="tl-page-btn" id="tlNext" title="Next page">
                        <i class="ri-arrow-right-s-line"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
    <script src="{{ asset('assets/system/js/pages/my-bank-accounts.js') }}"></script>
@endpush
