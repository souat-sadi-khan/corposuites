@if($row->id != 1)
    <div class="tl-actions">

        {{-- @if(auth()->user()->canImpersonate()) --}}
            <a href="{{ route('admin.users.impersonate', $row->id)}}" class="tl-icon-btn" title="Login As {{ $row->name }}">
                <i class="ri-login-circle-line"></i>
            </a>
        {{-- @endif --}}

        <!-- Edit -->
        <button
            class="tl-icon-btn"
            id="openModal"
            data-url="{{ route('admin.stuff.edit', $row->id) }}"
            title="Edit">

            <i class="ri-pencil-line"></i>

        </button>

        <!-- Password -->
        <button
            class="tl-icon-btn"
            id="openModal"
            data-width="50%"
            data-url="{{ route('admin.stuff.edit.password', $row->id) }}"
            title="Reset Password">

            <i class="ri-fingerprint-line"></i>

        </button>

        <!-- Delete -->
        <button
            class="tl-icon-btn danger"
            id="delete_item"
            data-id="{{ $row->id }}"
            data-url="{{ route('admin.stuff.destroy', $row->id) }}"
            data-del="1"
            title="Delete">

            <i class="ri-delete-bin-line"></i>

        </button>
    </div>
@endif
