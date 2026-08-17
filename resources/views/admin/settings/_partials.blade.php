@if(Auth::guard('admin')->user()->employee_id)
    <ul class="nav custom-tabs" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ Request::is('admin/profile') ? 'active' : '' }}" href="{{ route('admin.profile') }}">
                <i class="ri-user-line"></i>
                Personal Information
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a class="nav-link {{ Request::is('admin/profile/documents') ? 'active' : '' }}" href="{{ route('admin.profile.documents') }}">
                <i class="ri-file-user-line"></i>
                My Documents
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a class="nav-link {{ Request::is('admin/profile/emergency-contacts') ? 'active' : '' }}" href="{{ route('admin.profile.emergency-contacts') }}">
                <i class="ri-contacts-line"></i>
                Emergency Contacts
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a class="nav-link {{ Request::is('admin/profile/bank-accounts') ? 'active' : '' }}" href="{{ route('admin.profile.bank-accounts') }}">
                <i class="ri-bank-line"></i>
                My Bank Accounts
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a class="nav-link {{ Request::is('admin/profile/educations') ? 'active' : '' }}" href="{{ route('admin.profile.educations') }}">
                <i class="ri-graduation-cap-line"></i>
                My Educations
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a class="nav-link {{ Request::is('admin/profile/experiences') ? 'active' : '' }}" href="{{ route('admin.profile.experiences') }}">
                <i class="ri-briefcase-line"></i>
                My Experiences
            </a>
        </li>
    </ul>
@endif