<form class="ajax-form" method="POST" action="{{ route('admin.roles.assign.update',$role->id) }}">
    @csrf

    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            Assign Permission for {{ $role->name }}
        </h5>

        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">

        <div class="global-select-row mb-3">
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       id="globalSelectAll">

                <label class="form-check-label" for="globalSelectAll">
                    &nbsp; &nbsp;Select All Modules
                </label>
            </div>
        </div>

        @foreach(split_name($permissions) as $module => $perms)

            @php
                $moduleSlug = tounderscore($module);
            @endphp

            <div class="system-card mb-3">

                <div class="system-header"
                     data-bs-toggle="collapse"
                     data-bs-target="#{{ $moduleSlug }}">

                    <span class="system-name">
                        {{ $module }}
                    </span>

                    <div>
                        <span class="badge-modules">
                            {{ count($perms) }} Permissions
                        </span>
                    </div>

                </div>

                <div class="system-body collapse show"
                     id="{{ $moduleSlug }}">

                    <div class="module-row">

                        <div class="module-select-all">
                            <input
                                type="checkbox"
                                class="form-check-input module-select-all-chk"
                                data-module="{{ $moduleSlug }}">
                        </div>

                        <div class="module-info">
                            <div class="module-name">
                                Select All
                            </div>
                        </div>

                        <div class="permission-checkboxes">

                            @foreach($perms as $permission)

                                <div class="form-check">

                                    <input
                                        class="form-check-input permission-chk {{ $moduleSlug }}"
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission }}"
                                        data-module="{{ $moduleSlug }}"
                                        id="{{ Str::slug($permission) }}"

                                        {{ in_array($permission,$rolePermissions) ? 'checked' : '' }}
                                    >

                                    <label
                                        class="form-check-label"
                                        for="{{ Str::slug($permission) }}">

                                        {{ toSpan($permission) }}

                                    </label>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            </div>

        @endforeach

    </div>

    <div class="offcanvas-footer d-flex justify-content-between align-items-center p-3 border-top">
        <button type="button" class="btn-nx-outline" data-bs-dismiss="offcanvas">
            <i class="ri-close-large-line me-1"></i> Close
        </button>

        <button id="submit" type="submit" class="btn-nx-primary">
            <i class="ri-check-line me-1"></i>
            Save Permission
        </button>
        <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        </button>
    </div>
</form>

{{-- <div class="offcanvas-header">
    <h5 class="offcanvas-title">Assign Permission for {{ $role->name }} </h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
</div>

<div class="offcanvas-body">
    <div class="systems-grid" id="systemsGrid">

        <!-- Global Select All -->

        <div class="global-select-row">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="globalSelectAll" />
                <label class="form-check-label" for="globalSelectAll">
                    &nbsp; Select All Modules
                </label>
            </div>
        </div>

        <div class="system-card">
            <div class="system-header" data-bs-toggle="collapse" data-bs-target="#collapseSys0" aria-expanded="true">
                <span class="system-name"><i class="bi bi-people"></i> CRM</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-modules">6 Modules</span>
                    <i class="ri-arrow-down-s-line toggle-icon"></i>
                </div>
            </div>
            <div class="system-body collapse show" id="collapseSys0">
                <!-- Modules -->
                <div class="module-row">
                    <div class="module-select-all"><input type="checkbox" class="form-check-input module-select-all-chk" data-system="crm" data-module="Dashboard"></div>
                    <div class="module-info"><div class="module-name">Dashboard</div><div class="module-desc" title="Overview metrics">Overview metrics</div></div>
                    <div class="permission-checkboxes">
                        <!-- Permissions with hidden inputs -->
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Dashboard][View]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Dashboard" data-perm="View" id="p-crm-Dashboard-View"><label class="form-check-label" for="p-crm-Dashboard-View">View</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Dashboard][Create]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Dashboard" data-perm="Create" id="p-crm-Dashboard-Create"><label class="form-check-label" for="p-crm-Dashboard-Create">Create</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Dashboard][Edit]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Dashboard" data-perm="Edit" id="p-crm-Dashboard-Edit"><label class="form-check-label" for="p-crm-Dashboard-Edit">Edit</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Dashboard][Delete]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Dashboard" data-perm="Delete" id="p-crm-Dashboard-Delete"><label class="form-check-label" for="p-crm-Dashboard-Delete">Delete</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Dashboard][Export]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Dashboard" data-perm="Export" id="p-crm-Dashboard-Export"><label class="form-check-label" for="p-crm-Dashboard-Export">Export</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Dashboard][Import]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Dashboard" data-perm="Import" id="p-crm-Dashboard-Import"><label class="form-check-label" for="p-crm-Dashboard-Import">Import</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Dashboard][Manage]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Dashboard" data-perm="Manage" id="p-crm-Dashboard-Manage"><label class="form-check-label" for="p-crm-Dashboard-Manage">Manage</label></div>
                    </div>
                </div>

                <div class="module-row">
                    <div class="module-select-all"><input type="checkbox" class="form-check-input module-select-all-chk" data-system="crm" data-module="Leads"></div>
                    <div class="module-info"><div class="module-name">Leads</div><div class="module-desc" title="CRUD, status, assign, import">CRUD, status, assign, import</div></div>
                    <div class="permission-checkboxes">
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Leads][View]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Leads" data-perm="View" id="p-crm-Leads-View"><label class="form-check-label" for="p-crm-Leads-View">View</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Leads][Create]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Leads" data-perm="Create" id="p-crm-Leads-Create"><label class="form-check-label" for="p-crm-Leads-Create">Create</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Leads][Edit]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Leads" data-perm="Edit" id="p-crm-Leads-Edit"><label class="form-check-label" for="p-crm-Leads-Edit">Edit</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Leads][Delete]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Leads" data-perm="Delete" id="p-crm-Leads-Delete"><label class="form-check-label" for="p-crm-Leads-Delete">Delete</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Leads][Export]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Leads" data-perm="Export" id="p-crm-Leads-Export"><label class="form-check-label" for="p-crm-Leads-Export">Export</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Leads][Import]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Leads" data-perm="Import" id="p-crm-Leads-Import"><label class="form-check-label" for="p-crm-Leads-Import">Import</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Leads][Manage]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Leads" data-perm="Manage" id="p-crm-Leads-Manage"><label class="form-check-label" for="p-crm-Leads-Manage">Manage</label></div>
                    </div>
                </div>

                <div class="module-row">
                    <div class="module-select-all"><input type="checkbox" class="form-check-input module-select-all-chk" data-system="crm" data-module="Customers"></div>
                    <div class="module-info"><div class="module-name">Customers</div><div class="module-desc" title="CRUD, history, block">CRUD, history, block</div></div>
                    <div class="permission-checkboxes">
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Customers][View]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Customers" data-perm="View" id="p-crm-Customers-View"><label class="form-check-label" for="p-crm-Customers-View">View</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Customers][Create]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Customers" data-perm="Create" id="p-crm-Customers-Create"><label class="form-check-label" for="p-crm-Customers-Create">Create</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Customers][Edit]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Customers" data-perm="Edit" id="p-crm-Customers-Edit"><label class="form-check-label" for="p-crm-Customers-Edit">Edit</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Customers][Delete]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Customers" data-perm="Delete" id="p-crm-Customers-Delete"><label class="form-check-label" for="p-crm-Customers-Delete">Delete</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Customers][Export]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Customers" data-perm="Export" id="p-crm-Customers-Export"><label class="form-check-label" for="p-crm-Customers-Export">Export</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Customers][Import]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Customers" data-perm="Import" id="p-crm-Customers-Import"><label class="form-check-label" for="p-crm-Customers-Import">Import</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Customers][Manage]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Customers" data-perm="Manage" id="p-crm-Customers-Manage"><label class="form-check-label" for="p-crm-Customers-Manage">Manage</label></div>
                    </div>
                </div>

                <div class="module-row">
                    <div class="module-select-all"><input type="checkbox" class="form-check-input module-select-all-chk" data-system="crm" data-module="Deals"></div>
                    <div class="module-info"><div class="module-name">Deals</div><div class="module-desc" title="CRUD, move stage, win/loss">CRUD, move stage, win/loss</div></div>
                    <div class="permission-checkboxes">
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Deals][View]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Deals" data-perm="View" id="p-crm-Deals-View"><label class="form-check-label" for="p-crm-Deals-View">View</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Deals][Create]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Deals" data-perm="Create" id="p-crm-Deals-Create"><label class="form-check-label" for="p-crm-Deals-Create">Create</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Deals][Edit]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Deals" data-perm="Edit" id="p-crm-Deals-Edit"><label class="form-check-label" for="p-crm-Deals-Edit">Edit</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Deals][Delete]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Deals" data-perm="Delete" id="p-crm-Deals-Delete"><label class="form-check-label" for="p-crm-Deals-Delete">Delete</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Deals][Export]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Deals" data-perm="Export" id="p-crm-Deals-Export"><label class="form-check-label" for="p-crm-Deals-Export">Export</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Deals][Import]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Deals" data-perm="Import" id="p-crm-Deals-Import"><label class="form-check-label" for="p-crm-Deals-Import">Import</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Deals][Manage]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Deals" data-perm="Manage" id="p-crm-Deals-Manage"><label class="form-check-label" for="p-crm-Deals-Manage">Manage</label></div>
                    </div>
                </div>

                <div class="module-row">
                    <div class="module-select-all"><input type="checkbox" class="form-check-input module-select-all-chk" data-system="crm" data-module="Quotations"></div>
                    <div class="module-info"><div class="module-name">Quotations</div><div class="module-desc" title="CRUD, email, convert">CRUD, email, convert</div></div>
                    <div class="permission-checkboxes">
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Quotations][View]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Quotations" data-perm="View" id="p-crm-Quotations-View"><label class="form-check-label" for="p-crm-Quotations-View">View</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Quotations][Create]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Quotations" data-perm="Create" id="p-crm-Quotations-Create"><label class="form-check-label" for="p-crm-Quotations-Create">Create</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Quotations][Edit]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Quotations" data-perm="Edit" id="p-crm-Quotations-Edit"><label class="form-check-label" for="p-crm-Quotations-Edit">Edit</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Quotations][Delete]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Quotations" data-perm="Delete" id="p-crm-Quotations-Delete"><label class="form-check-label" for="p-crm-Quotations-Delete">Delete</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Quotations][Export]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Quotations" data-perm="Export" id="p-crm-Quotations-Export"><label class="form-check-label" for="p-crm-Quotations-Export">Export</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Quotations][Import]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Quotations" data-perm="Import" id="p-crm-Quotations-Import"><label class="form-check-label" for="p-crm-Quotations-Import">Import</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Quotations][Manage]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Quotations" data-perm="Manage" id="p-crm-Quotations-Manage"><label class="form-check-label" for="p-crm-Quotations-Manage">Manage</label></div>
                    </div>
                </div>

                <div class="module-row">
                    <div class="module-select-all"><input type="checkbox" class="form-check-input module-select-all-chk" data-system="crm" data-module="Support Tickets"></div>
                    <div class="module-info"><div class="module-name">Support Tickets</div><div class="module-desc" title="CRUD, assign, resolve">CRUD, assign, resolve</div></div>
                    <div class="permission-checkboxes">
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Support Tickets][View]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Support Tickets" data-perm="View" id="p-crm-SupportTickets-View"><label class="form-check-label" for="p-crm-SupportTickets-View">View</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Support Tickets][Create]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Support Tickets" data-perm="Create" id="p-crm-SupportTickets-Create"><label class="form-check-label" for="p-crm-SupportTickets-Create">Create</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Support Tickets][Edit]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Support Tickets" data-perm="Edit" id="p-crm-SupportTickets-Edit"><label class="form-check-label" for="p-crm-SupportTickets-Edit">Edit</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Support Tickets][Delete]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Support Tickets" data-perm="Delete" id="p-crm-SupportTickets-Delete"><label class="form-check-label" for="p-crm-SupportTickets-Delete">Delete</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Support Tickets][Export]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Support Tickets" data-perm="Export" id="p-crm-SupportTickets-Export"><label class="form-check-label" for="p-crm-SupportTickets-Export">Export</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Support Tickets][Import]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Support Tickets" data-perm="Import" id="p-crm-SupportTickets-Import"><label class="form-check-label" for="p-crm-SupportTickets-Import">Import</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[crm][Support Tickets][Manage]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="crm" data-module="Support Tickets" data-perm="Manage" id="p-crm-SupportTickets-Manage"><label class="form-check-label" for="p-crm-SupportTickets-Manage">Manage</label></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="system-card">
            <div class="system-header" data-bs-toggle="collapse" data-bs-target="#collapseSys4" aria-expanded="true">
                <span class="system-name"><i class="bi bi-gear-wide-connected"></i> Settings</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-modules">5 Modules</span>
                    <i class="ri-arrow-down-s-line toggle-icon"></i>
                </div>
            </div>
            <div class="system-body collapse show" id="collapseSys4">
                <div class="module-row">
                    <div class="module-select-all"><input type="checkbox" class="form-check-input module-select-all-chk" data-system="settings" data-module="Users"></div>
                    <div class="module-info"><div class="module-name">Users</div><div class="module-desc" title="CRUD, status, reset pwd">CRUD, status, reset pwd</div></div>
                    <div class="permission-checkboxes">
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Users][View]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Users" data-perm="View" id="p-settings-Users-View"><label class="form-check-label" for="p-settings-Users-View">View</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Users][Create]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Users" data-perm="Create" id="p-settings-Users-Create"><label class="form-check-label" for="p-settings-Users-Create">Create</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Users][Edit]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Users" data-perm="Edit" id="p-settings-Users-Edit"><label class="form-check-label" for="p-settings-Users-Edit">Edit</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Users][Delete]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Users" data-perm="Delete" id="p-settings-Users-Delete"><label class="form-check-label" for="p-settings-Users-Delete">Delete</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Users][Export]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Users" data-perm="Export" id="p-settings-Users-Export"><label class="form-check-label" for="p-settings-Users-Export">Export</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Users][Import]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Users" data-perm="Import" id="p-settings-Users-Import"><label class="form-check-label" for="p-settings-Users-Import">Import</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Users][Manage]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Users" data-perm="Manage" id="p-settings-Users-Manage"><label class="form-check-label" for="p-settings-Users-Manage">Manage</label></div>
                    </div>
                </div>
                <div class="module-row">
                    <div class="module-select-all"><input type="checkbox" class="form-check-input module-select-all-chk" data-system="settings" data-module="Roles &amp; Permissions"></div>
                    <div class="module-info"><div class="module-name">Roles &amp; Permissions</div><div class="module-desc" title="CRUD, assign">CRUD, assign</div></div>
                    <div class="permission-checkboxes">
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Roles &amp; Permissions][View]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Roles &amp; Permissions" data-perm="View" id="p-settings-RolesPermissions-View"><label class="form-check-label" for="p-settings-RolesPermissions-View">View</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Roles &amp; Permissions][Create]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Roles &amp; Permissions" data-perm="Create" id="p-settings-RolesPermissions-Create"><label class="form-check-label" for="p-settings-RolesPermissions-Create">Create</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Roles &amp; Permissions][Edit]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Roles &amp; Permissions" data-perm="Edit" id="p-settings-RolesPermissions-Edit"><label class="form-check-label" for="p-settings-RolesPermissions-Edit">Edit</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Roles &amp; Permissions][Delete]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Roles &amp; Permissions" data-perm="Delete" id="p-settings-RolesPermissions-Delete"><label class="form-check-label" for="p-settings-RolesPermissions-Delete">Delete</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Roles &amp; Permissions][Export]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Roles &amp; Permissions" data-perm="Export" id="p-settings-RolesPermissions-Export"><label class="form-check-label" for="p-settings-RolesPermissions-Export">Export</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Roles &amp; Permissions][Import]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Roles &amp; Permissions" data-perm="Import" id="p-settings-RolesPermissions-Import"><label class="form-check-label" for="p-settings-RolesPermissions-Import">Import</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Roles &amp; Permissions][Manage]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Roles &amp; Permissions" data-perm="Manage" id="p-settings-RolesPermissions-Manage"><label class="form-check-label" for="p-settings-RolesPermissions-Manage">Manage</label></div>
                    </div>
                </div>
                <div class="module-row">
                    <div class="module-select-all"><input type="checkbox" class="form-check-input module-select-all-chk" data-system="settings" data-module="Activity Logs"></div>
                    <div class="module-info"><div class="module-name">Activity Logs</div><div class="module-desc" title="View, clear, search">View, clear, search</div></div>
                    <div class="permission-checkboxes">
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Activity Logs][View]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Activity Logs" data-perm="View" id="p-settings-ActivityLogs-View"><label class="form-check-label" for="p-settings-ActivityLogs-View">View</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Activity Logs][Create]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Activity Logs" data-perm="Create" id="p-settings-ActivityLogs-Create"><label class="form-check-label" for="p-settings-ActivityLogs-Create">Create</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Activity Logs][Edit]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Activity Logs" data-perm="Edit" id="p-settings-ActivityLogs-Edit"><label class="form-check-label" for="p-settings-ActivityLogs-Edit">Edit</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Activity Logs][Delete]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Activity Logs" data-perm="Delete" id="p-settings-ActivityLogs-Delete"><label class="form-check-label" for="p-settings-ActivityLogs-Delete">Delete</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Activity Logs][Export]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Activity Logs" data-perm="Export" id="p-settings-ActivityLogs-Export"><label class="form-check-label" for="p-settings-ActivityLogs-Export">Export</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Activity Logs][Import]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Activity Logs" data-perm="Import" id="p-settings-ActivityLogs-Import"><label class="form-check-label" for="p-settings-ActivityLogs-Import">Import</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Activity Logs][Manage]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Activity Logs" data-perm="Manage" id="p-settings-ActivityLogs-Manage"><label class="form-check-label" for="p-settings-ActivityLogs-Manage">Manage</label></div>
                    </div>
                </div>
                <div class="module-row">
                    <div class="module-select-all"><input type="checkbox" class="form-check-input module-select-all-chk" data-system="settings" data-module="System Settings"></div>
                    <div class="module-info"><div class="module-name">System Settings</div><div class="module-desc" title="Profile, email, gateway">Profile, email, gateway</div></div>
                    <div class="permission-checkboxes">
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][System Settings][View]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="System Settings" data-perm="View" id="p-settings-SystemSettings-View"><label class="form-check-label" for="p-settings-SystemSettings-View">View</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][System Settings][Create]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="System Settings" data-perm="Create" id="p-settings-SystemSettings-Create"><label class="form-check-label" for="p-settings-SystemSettings-Create">Create</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][System Settings][Edit]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="System Settings" data-perm="Edit" id="p-settings-SystemSettings-Edit"><label class="form-check-label" for="p-settings-SystemSettings-Edit">Edit</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][System Settings][Delete]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="System Settings" data-perm="Delete" id="p-settings-SystemSettings-Delete"><label class="form-check-label" for="p-settings-SystemSettings-Delete">Delete</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][System Settings][Export]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="System Settings" data-perm="Export" id="p-settings-SystemSettings-Export"><label class="form-check-label" for="p-settings-SystemSettings-Export">Export</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][System Settings][Import]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="System Settings" data-perm="Import" id="p-settings-SystemSettings-Import"><label class="form-check-label" for="p-settings-SystemSettings-Import">Import</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][System Settings][Manage]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="System Settings" data-perm="Manage" id="p-settings-SystemSettings-Manage"><label class="form-check-label" for="p-settings-SystemSettings-Manage">Manage</label></div>
                    </div>
                </div>
                <div class="module-row">
                    <div class="module-select-all"><input type="checkbox" class="form-check-input module-select-all-chk" data-system="settings" data-module="Backup"></div>
                    <div class="module-info"><div class="module-name">Backup</div><div class="module-desc" title="Create, download, restore">Create, download, restore</div></div>
                    <div class="permission-checkboxes">
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Backup][View]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Backup" data-perm="View" id="p-settings-Backup-View"><label class="form-check-label" for="p-settings-Backup-View">View</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Backup][Create]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Backup" data-perm="Create" id="p-settings-Backup-Create"><label class="form-check-label" for="p-settings-Backup-Create">Create</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Backup][Edit]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Backup" data-perm="Edit" id="p-settings-Backup-Edit"><label class="form-check-label" for="p-settings-Backup-Edit">Edit</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Backup][Delete]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Backup" data-perm="Delete" id="p-settings-Backup-Delete"><label class="form-check-label" for="p-settings-Backup-Delete">Delete</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Backup][Export]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Backup" data-perm="Export" id="p-settings-Backup-Export"><label class="form-check-label" for="p-settings-Backup-Export">Export</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Backup][Import]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Backup" data-perm="Import" id="p-settings-Backup-Import"><label class="form-check-label" for="p-settings-Backup-Import">Import</label></div>
                        <div class="form-check"><input type="hidden" class="perm-hidden" name="permissions[settings][Backup][Manage]" value="0"><input type="checkbox" class="form-check-input permission-chk" data-system="settings" data-module="Backup" data-perm="Manage" id="p-settings-Backup-Manage"><label class="form-check-label" for="p-settings-Backup-Manage">Manage</label></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="offcanvas-footer d-flex justify-content-between align-items-center p-3 border-top">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">
        <i class="bx fs-5 lh-0 bx-x me-2"></i>
        Close
    </button>
    <button type="submit" id="submit" class="btn btn-primary">
        Update <i class="bx fs-5 lh-0 ms-2 bx-send-alt"></i>
    </button>
    <button id="submitting" type="button" style="display: none;" disabled class="btn btn-primary">
        <span class="spinner-border spinner-border-sm"></span>
    </button>
</div> --}}
