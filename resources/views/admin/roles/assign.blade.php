<form class="ajax-form perm-assign-form"
      method="POST"
      action="{{ route('admin.roles.assign.update', $role->id) }}">

    @csrf

    <div class="offcanvas-header">
        <div>
            <h5 class="offcanvas-title mb-0">
                Assign Permissions
            </h5>

            <small class="text-muted">
                {{ $role->name }}
            </small>
        </div>

        <button type="button"
                class="btn-close"
                data-bs-dismiss="offcanvas"
                aria-label="Close">
        </button>
    </div>


    <div class="offcanvas-body">

        @php
            /*
            |--------------------------------------------------------------------------
            | Group permissions by module
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | employee.view
            | employee.create
            | employee.edit
            | employee.delete
            |
            | becomes:
            |
            | Employee
            |   - View
            |   - Create
            |   - Edit
            |   - Delete
            |
            */

            $grouped = split_name($permissions);

            $totalPermissions = collect($grouped)
                ->flatten()
                ->count();
        @endphp


        <div class="perm-assign" id="permAssign">


            {{-- ============================================================
                TOP TOOLBAR
            ============================================================= --}}

            <div class="perm-toolbar">

                {{-- Search --}}
                <div class="perm-search">
                    <i class="ri-search-line"></i>

                    <input type="text"
                           id="permSearch"
                           class="form-control"
                           placeholder="Search module or permission..."
                           autocomplete="off">
                </div>


                {{-- Global Select All --}}
                <label class="perm-switch"
                       title="Enable or disable all permissions">

                    <input type="checkbox"
                           id="globalSelectAll">

                    <span class="track"></span>

                    <span class="switch-label">
                        Select All
                    </span>

                </label>


                {{-- Global Count --}}
                <span class="perm-count"
                      id="permCount">
                    0 / {{ $totalPermissions }}
                </span>

            </div>



            {{-- ============================================================
                MODULE LIST
            ============================================================= --}}

            <div class="perm-module-list">

                @foreach($grouped as $module => $perms)

                    @php
                        $moduleSlug = \Illuminate\Support\Str::slug($module, '-');
                        $moduleName = \Illuminate\Support\Str::headline($module);
                        $moduleTotal = count($perms);
                    @endphp


                    <div class="perm-module"
                         data-module="{{ $moduleSlug }}"
                         data-name="{{ strtolower($moduleName) }}">


                        {{-- =================================================
                            MODULE HEADER
                        ================================================== --}}

                        <div class="perm-module-head"
                             data-role="perm-toggle">


                            {{-- Module All Switch --}}
                            <label class="perm-switch module-switch"
                                   onclick="event.stopPropagation();"
                                   title="Enable / disable all {{ $moduleName }} permissions">

                                <input type="checkbox"
                                       class="module-select-all-chk"
                                       data-module="{{ $moduleSlug }}">

                                <span class="track"></span>

                            </label>


                            {{-- Module Information --}}
                            <div class="perm-module-title">

                                <span class="perm-module-name">
                                    {{ $moduleName }}
                                </span>

                                <span class="perm-module-meta"
                                      data-role="module-meta"
                                      data-module="{{ $moduleSlug }}">
                                    0 of {{ $moduleTotal }} selected
                                </span>

                            </div>


                            {{-- Permission Total --}}
                            <span class="perm-badge"
                                  data-role="module-badge"
                                  data-module="{{ $moduleSlug }}">
                                {{ $moduleTotal }}
                            </span>


                            {{-- Collapse Icon --}}
                            <i class="ri-arrow-down-s-line perm-chevron"></i>

                        </div>



                        {{-- =================================================
                            MODULE PERMISSIONS
                        ================================================== --}}

                        <div class="perm-module-body">

                            <div class="perm-grid">

                                @foreach($perms as $permission)

                                    @php
                                        $action = \Illuminate\Support\Str::afterLast(
                                            $permission,
                                            '.'
                                        );
                                    @endphp


                                    <label class="perm-chip"
                                           data-perm-label="{{ strtolower($action) }}">

                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission }}"
                                            class="permission-chk"
                                            data-module="{{ $moduleSlug }}"
                                            {{ in_array($permission, $rolePermissions) ? 'checked' : '' }}
                                        >


                                        <span class="tick">
                                            <i class="ri-check-line"></i>
                                        </span>


                                        <span class="perm-chip-label">
                                            {{ \Illuminate\Support\Str::headline($action) }}
                                        </span>

                                    </label>

                                @endforeach

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>



            {{-- ============================================================
                EMPTY SEARCH RESULT
            ============================================================= --}}

            <div class="perm-empty"
                 id="permEmpty">

                <i class="ri-search-eye-line"></i>

                <span>
                    No permissions match your search.
                </span>

            </div>

        </div>

    </div>



    {{-- ================================================================
        FOOTER
    ================================================================= --}}

    <div class="offcanvas-footer d-flex justify-content-between align-items-center p-3 border-top">

        <button type="button"
                class="btn-nx-outline"
                data-bs-dismiss="offcanvas">

            <i class="ri-close-large-line me-1"></i>

            Close

        </button>


        <div class="d-flex align-items-center gap-2">

            <button id="submit"
                    type="submit"
                    class="btn-nx-primary">

                <i class="ri-check-line me-1"></i>

                Save Permission

            </button>


            <button type="button"
                    class="btn-nx-primary"
                    id="submitting"
                    disabled
                    style="display:none;">

                <span class="spinner-border spinner-border-sm me-1"
                      role="status"
                      aria-hidden="true">
                </span>

            </button>

        </div>

    </div>

</form>



<script>
(function () {

    const root = document.getElementById('permAssign');

    if (!root) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Elements
    |--------------------------------------------------------------------------
    */

    const permissionCheckboxes =
        root.querySelectorAll('.permission-chk');

    const globalCheckbox =
        root.querySelector('#globalSelectAll');

    const countElement =
        root.querySelector('#permCount');

    const searchElement =
        root.querySelector('#permSearch');

    const emptyElement =
        root.querySelector('#permEmpty');

    const modules =
        root.querySelectorAll('.perm-module');

    const totalPermissions =
        permissionCheckboxes.length;



    /*
    |--------------------------------------------------------------------------
    | Get module permissions
    |--------------------------------------------------------------------------
    */

    function getModulePermissions(moduleSlug) {

        return root.querySelectorAll(
            '.permission-chk[data-module="' + moduleSlug + '"]'
        );

    }



    /*
    |--------------------------------------------------------------------------
    | Paint individual permission
    |--------------------------------------------------------------------------
    */

    function paintPermission(checkbox) {

        const chip =
            checkbox.closest('.perm-chip');

        if (!chip) {
            return;
        }

        chip.classList.toggle(
            'is-checked',
            checkbox.checked
        );

    }



    /*
    |--------------------------------------------------------------------------
    | Refresh module state
    |--------------------------------------------------------------------------
    */

    function refreshModule(moduleSlug) {

        const permissions =
            getModulePermissions(moduleSlug);

        const module =
            root.querySelector(
                '.perm-module[data-module="' + moduleSlug + '"]'
            );

        if (!module) {
            return;
        }


        const moduleSwitch =
            module.querySelector(
                '.module-select-all-chk'
            );

        const moduleMeta =
            module.querySelector(
                '[data-role="module-meta"]'
            );

        const moduleBadge =
            module.querySelector(
                '[data-role="module-badge"]'
            );


        let selected = 0;


        permissions.forEach(function (checkbox) {

            if (checkbox.checked) {
                selected++;
            }

        });


        const total =
            permissions.length;


        /*
        |--------------------------------------------------------------------------
        | Module All Switch
        |--------------------------------------------------------------------------
        */

        if (moduleSwitch) {

            moduleSwitch.checked =
                total > 0 &&
                selected === total;

            moduleSwitch.indeterminate =
                selected > 0 &&
                selected < total;

        }


        /*
        |--------------------------------------------------------------------------
        | Module Counter
        |--------------------------------------------------------------------------
        */

        if (moduleMeta) {

            moduleMeta.textContent =
                selected +
                ' of ' +
                total +
                ' selected';

        }


        /*
        |--------------------------------------------------------------------------
        | Module Badge
        |--------------------------------------------------------------------------
        */

        if (moduleBadge) {

            moduleBadge.textContent =
                total;

            moduleBadge.classList.toggle(
                'is-active',
                selected > 0
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Module Active State
        |--------------------------------------------------------------------------
        */

        module.classList.toggle(
            'has-selection',
            selected > 0
        );

        module.classList.toggle(
            'all-selected',
            total > 0 &&
            selected === total
        );

    }



    /*
    |--------------------------------------------------------------------------
    | Refresh global Select All
    |--------------------------------------------------------------------------
    */

    function refreshGlobal() {

        let selected = 0;


        permissionCheckboxes.forEach(function (checkbox) {

            if (checkbox.checked) {
                selected++;
            }

        });


        /*
        |--------------------------------------------------------------------------
        | Counter
        |--------------------------------------------------------------------------
        */

        if (countElement) {

            countElement.textContent =
                selected +
                ' / ' +
                totalPermissions;

        }


        /*
        |--------------------------------------------------------------------------
        | Global Switch
        |--------------------------------------------------------------------------
        */

        if (globalCheckbox) {

            globalCheckbox.checked =
                totalPermissions > 0 &&
                selected === totalPermissions;

            globalCheckbox.indeterminate =
                selected > 0 &&
                selected < totalPermissions;

        }

    }



    /*
    |--------------------------------------------------------------------------
    | Refresh everything
    |--------------------------------------------------------------------------
    */

    function refreshAll() {

        const processedModules = {};

        permissionCheckboxes.forEach(function (checkbox) {

            paintPermission(checkbox);


            const moduleSlug =
                checkbox.getAttribute('data-module');


            if (!processedModules[moduleSlug]) {

                refreshModule(moduleSlug);

                processedModules[moduleSlug] = true;

            }

        });


        refreshGlobal();

    }



    /*
    |--------------------------------------------------------------------------
    | Individual permission toggle
    |--------------------------------------------------------------------------
    */

    permissionCheckboxes.forEach(function (checkbox) {

        checkbox.addEventListener(
            'change',
            function () {

                paintPermission(checkbox);


                const moduleSlug =
                    checkbox.getAttribute('data-module');


                refreshModule(moduleSlug);

                refreshGlobal();

            }
        );

    });



    /*
    |--------------------------------------------------------------------------
    | MODULE: Select All
    |--------------------------------------------------------------------------
    */

    root
        .querySelectorAll('.module-select-all-chk')
        .forEach(function (moduleSwitch) {


            moduleSwitch.addEventListener(
                'change',
                function () {

                    const moduleSlug =
                        moduleSwitch.getAttribute(
                            'data-module'
                        );


                    const permissions =
                        getModulePermissions(moduleSlug);


                    /*
                    | Enable / disable every permission
                    | inside this module
                    */

                    permissions.forEach(
                        function (checkbox) {

                            checkbox.checked =
                                moduleSwitch.checked;

                            paintPermission(checkbox);

                        }
                    );


                    refreshModule(moduleSlug);

                    refreshGlobal();

                }
            );

        });



    /*
    |--------------------------------------------------------------------------
    | GLOBAL: Select All
    |--------------------------------------------------------------------------
    */

    if (globalCheckbox) {

        globalCheckbox.addEventListener(
            'change',
            function () {


                permissionCheckboxes.forEach(
                    function (checkbox) {

                        checkbox.checked =
                            globalCheckbox.checked;

                        paintPermission(checkbox);

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Refresh every module
                |--------------------------------------------------------------------------
                */

                modules.forEach(function (module) {

                    refreshModule(
                        module.getAttribute('data-module')
                    );

                });


                refreshGlobal();

            }
        );

    }



    /*
    |--------------------------------------------------------------------------
    | Module Collapse / Expand
    |--------------------------------------------------------------------------
    */

    root
        .querySelectorAll('[data-role="perm-toggle"]')
        .forEach(function (header) {


            header.addEventListener(
                'click',
                function (event) {


                    /*
                    |--------------------------------------------------------------------------
                    | Don't collapse when clicking module switch
                    |--------------------------------------------------------------------------
                    */

                    if (
                        event.target.closest('.perm-switch')
                    ) {
                        return;
                    }


                    const module =
                        header.closest('.perm-module');


                    if (!module) {
                        return;
                    }


                    module.classList.toggle(
                        'collapsed'
                    );

                }
            );

        });



    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    if (searchElement) {

        searchElement.addEventListener(
            'input',
            function () {

                const query =
                    this.value
                        .trim()
                        .toLowerCase();


                let anyVisible = false;


                modules.forEach(function (module) {


                    const moduleName =
                        module.getAttribute(
                            'data-name'
                        ) || '';


                    const moduleMatches =
                        moduleName.indexOf(query) !== -1;


                    let visiblePermissions = 0;


                    /*
                    |--------------------------------------------------------------------------
                    | Filter permissions
                    |--------------------------------------------------------------------------
                    */

                    module
                        .querySelectorAll('.perm-chip')
                        .forEach(function (chip) {


                            const permissionName =
                                chip.getAttribute(
                                    'data-perm-label'
                                ) || '';


                            const visible =
                                !query ||
                                moduleMatches ||
                                permissionName.indexOf(query) !== -1;


                            chip.classList.toggle(
                                'perm-hidden-row',
                                !visible
                            );


                            if (visible) {
                                visiblePermissions++;
                            }

                        });


                    /*
                    |--------------------------------------------------------------------------
                    | Filter module
                    |--------------------------------------------------------------------------
                    */

                    const showModule =
                        !query ||
                        moduleMatches ||
                        visiblePermissions > 0;


                    module.classList.toggle(
                        'perm-hidden-row',
                        !showModule
                    );


                    if (showModule) {

                        anyVisible = true;


                        /*
                        |--------------------------------------------------------------------------
                        | Automatically expand searched module
                        |--------------------------------------------------------------------------
                        */

                        if (query) {

                            module.classList.remove(
                                'collapsed'
                            );

                        }

                    }

                });


                /*
                |--------------------------------------------------------------------------
                | Empty State
                |--------------------------------------------------------------------------
                */

                if (emptyElement) {

                    emptyElement.classList.toggle(
                        'show',
                        !anyVisible
                    );

                }

            }
        );

    }



    /*
    |--------------------------------------------------------------------------
    | Initial State
    |--------------------------------------------------------------------------
    */

    refreshAll();

})();
</script>