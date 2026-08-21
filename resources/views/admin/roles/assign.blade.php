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
            | Group permissions: Module -> Menu -> Actions
            |--------------------------------------------------------------------------
            |
            | e.g. "employee.view" / "employee.create" / ... become:
            |
            |   HRM (module)
            |     Employee (menu)
            |       - View
            |       - Create
            |       - Edit
            |       - Delete
            |
            | See permission_module_map() / group_permissions_by_module() in
            | app/Helpers/Helper.php for how a permission's group prefix
            | (the part before the first dot) is mapped to an ERP module.
            */

            $tree = group_permissions_by_module($permissions);

            $totalPermissions = collect($permissions)->count();
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
                           placeholder="Search module, menu or permission..."
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

                @foreach($tree as $moduleLabel => $menus)

                    @php
                        $moduleSlug = \Illuminate\Support\Str::slug($moduleLabel, '-');
                        $moduleTotal = collect($menus)->flatten()->count();
                    @endphp


                    <div class="perm-module mb-3"
                         data-module="{{ $moduleSlug }}"
                         data-name="{{ strtolower($moduleLabel) }}">


                        {{-- =================================================
                            MODULE HEADER
                        ================================================== --}}

                        <div class="perm-module-head"
                             data-role="perm-toggle">


                            {{-- Module All Switch --}}
                            <label class="perm-switch module-switch"
                                   onclick="event.stopPropagation();"
                                   title="Enable / disable every {{ $moduleLabel }} permission">

                                <input type="checkbox"
                                       class="module-select-all-chk"
                                       data-module="{{ $moduleSlug }}">

                                <span class="track"></span>

                            </label>


                            {{-- Module Information --}}
                            <div class="perm-module-title">

                                <span class="perm-module-name">
                                    {{ $moduleLabel }}
                                </span>

                                <span class="perm-module-meta"
                                      data-role="module-meta"
                                      data-module="{{ $moduleSlug }}">
                                    0 of {{ $moduleTotal }} selected &middot; {{ count($menus) }} {{ Str::plural('menu', count($menus)) }}
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
                            MODULE PERMISSIONS (grouped by menu)
                        ================================================== --}}

                        <div class="perm-module-body perm-module-body--menus">

                            @foreach($menus as $menuPrefix => $menuPermissions)

                                @php
                                    $menuUid = $moduleSlug . '__' . \Illuminate\Support\Str::slug($menuPrefix, '-');
                                    $menuLabel = toWord($menuPrefix);
                                    $menuTotal = count($menuPermissions);
                                @endphp

                                <div class="perm-menu-block"
                                     data-menu="{{ $menuUid }}"
                                     data-name="{{ strtolower($menuLabel) }}">

                                    <div class="perm-menu-head">

                                        <label class="perm-switch perm-menu-switch"
                                               title="Enable / disable every {{ $menuLabel }} permission">

                                            <input type="checkbox"
                                                   class="menu-select-all-chk"
                                                   data-menu="{{ $menuUid }}"
                                                   data-module="{{ $moduleSlug }}">

                                            <span class="track"></span>

                                        </label>

                                        <span class="perm-menu-name">
                                            {{ $menuLabel }}
                                        </span>

                                        <span class="perm-menu-badge"
                                              data-role="menu-badge"
                                              data-menu="{{ $menuUid }}">
                                            0/{{ $menuTotal }}
                                        </span>

                                    </div>

                                    <div class="perm-menu-grid">

                                        @foreach($menuPermissions as $permission)

                                            @php
                                                $action = \Illuminate\Support\Str::afterLast($permission->name, '.');
                                            @endphp


                                            <label class="perm-chip"
                                                   data-perm-label="{{ strtolower($action) }}">

                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permission->name }}"
                                                    class="permission-chk"
                                                    data-module="{{ $moduleSlug }}"
                                                    data-menu="{{ $menuUid }}"
                                                    {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}
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

                            @endforeach

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

    const menuBlocks =
        root.querySelectorAll('.perm-menu-block');

    const totalPermissions =
        permissionCheckboxes.length;



    /*
    |--------------------------------------------------------------------------
    | Query helpers
    |--------------------------------------------------------------------------
    */

    function getModulePermissions(moduleSlug) {

        return root.querySelectorAll(
            '.permission-chk[data-module="' + moduleSlug + '"]'
        );

    }


    function getMenuPermissions(menuUid) {

        return root.querySelectorAll(
            '.permission-chk[data-menu="' + menuUid + '"]'
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
    | Refresh a single menu's state (toggle + badge)
    |--------------------------------------------------------------------------
    */

    function refreshMenu(menuUid) {

        const permissions =
            getMenuPermissions(menuUid);

        const menu =
            root.querySelector(
                '.perm-menu-block[data-menu="' + menuUid + '"]'
            );

        if (!menu) {
            return;
        }

        const menuSwitch =
            menu.querySelector('.menu-select-all-chk');

        const menuBadge =
            menu.querySelector('[data-role="menu-badge"]');

        let selected = 0;

        permissions.forEach(function (checkbox) {

            if (checkbox.checked) {
                selected++;
            }

        });

        const total = permissions.length;

        if (menuSwitch) {

            menuSwitch.checked =
                total > 0 && selected === total;

            menuSwitch.indeterminate =
                selected > 0 && selected < total;

        }

        if (menuBadge) {

            menuBadge.textContent =
                selected + '/' + total;

            menuBadge.classList.toggle(
                'is-active',
                selected > 0
            );

        }

        menu.classList.toggle(
            'has-selection',
            selected > 0
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

        const menuCount =
            module.querySelectorAll('.perm-menu-block').length;


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
                ' selected · ' +
                menuCount +
                (menuCount === 1 ? ' menu' : ' menus');

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

        menuBlocks.forEach(function (menu) {

            refreshMenu(
                menu.getAttribute('data-menu')
            );

        });

        modules.forEach(function (module) {

            refreshModule(
                module.getAttribute('data-module')
            );

        });

        permissionCheckboxes.forEach(paintPermission);

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

                const menuUid =
                    checkbox.getAttribute('data-menu');


                refreshMenu(menuUid);

                refreshModule(moduleSlug);

                refreshGlobal();

            }
        );

    });



    /*
    |--------------------------------------------------------------------------
    | MENU: Select All
    |--------------------------------------------------------------------------
    */

    root
        .querySelectorAll('.menu-select-all-chk')
        .forEach(function (menuSwitch) {

            menuSwitch.addEventListener(
                'change',
                function () {

                    const menuUid =
                        menuSwitch.getAttribute('data-menu');

                    const moduleSlug =
                        menuSwitch.getAttribute('data-module');

                    const permissions =
                        getMenuPermissions(menuUid);

                    permissions.forEach(function (checkbox) {

                        checkbox.checked =
                            menuSwitch.checked;

                        paintPermission(checkbox);

                    });

                    refreshMenu(menuUid);

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


                    /*
                    | Refresh every menu inside this module too,
                    | so each menu's own toggle stays in sync.
                    */

                    const module =
                        root.querySelector(
                            '.perm-module[data-module="' + moduleSlug + '"]'
                        );

                    if (module) {

                        module
                            .querySelectorAll('.perm-menu-block')
                            .forEach(function (menu) {

                                refreshMenu(
                                    menu.getAttribute('data-menu')
                                );

                            });

                    }


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
                | Refresh every menu + module
                |--------------------------------------------------------------------------
                */

                menuBlocks.forEach(function (menu) {

                    refreshMenu(
                        menu.getAttribute('data-menu')
                    );

                });

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
                        !!query && moduleName.indexOf(query) !== -1;


                    let visiblePermissionsInModule = 0;


                    /*
                    |--------------------------------------------------------------------------
                    | Filter menu blocks, then chips within each menu
                    |--------------------------------------------------------------------------
                    */

                    module
                        .querySelectorAll('.perm-menu-block')
                        .forEach(function (menuBlock) {

                            const menuName =
                                menuBlock.getAttribute('data-name') || '';

                            const menuMatches =
                                !!query && menuName.indexOf(query) !== -1;

                            let visiblePermissions = 0;

                            menuBlock
                                .querySelectorAll('.perm-chip')
                                .forEach(function (chip) {

                                    const permissionName =
                                        chip.getAttribute(
                                            'data-perm-label'
                                        ) || '';

                                    const visible =
                                        !query ||
                                        moduleMatches ||
                                        menuMatches ||
                                        permissionName.indexOf(query) !== -1;

                                    chip.classList.toggle(
                                        'perm-hidden-row',
                                        !visible
                                    );

                                    if (visible) {
                                        visiblePermissions++;
                                    }

                                });


                            const showMenu =
                                !query ||
                                moduleMatches ||
                                menuMatches ||
                                visiblePermissions > 0;


                            menuBlock.classList.toggle(
                                'perm-hidden-row',
                                !showMenu
                            );


                            if (showMenu) {
                                visiblePermissionsInModule++;
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
                        visiblePermissionsInModule > 0;


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
