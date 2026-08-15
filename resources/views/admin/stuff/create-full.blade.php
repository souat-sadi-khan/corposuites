@extends('admin.layout.app', ['title' => t('stuff.create_new_stuff')])

@section('content')
    <div class="form-steper-wizard">
        <div id="stepper2" class="bs-stepper">

            <div class="card">

                <!-- Card Header – Steps -->
                <div class="card-header bg-transparent">
                    <div class="d-lg-flex flex-lg-row align-items-lg-center justify-content-lg-between" role="tablist">

                        <!-- Step 1 -->
                        <div class="step active" data-target="#test-nl-1">
                            <div class="step-trigger" role="tab" id="stepper2trigger1" aria-controls="test-nl-1" aria-selected="true">
                                <div class="bs-stepper-circle">
                                    <i class="ri-user-line fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 steper-title">
                                        {{ t('stuff.personal_info') }}
                                    </h5>
                                    <p class="mb-0 steper-sub-title">
                                        {{ t('stuff.personal_info_details') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bs-stepper-line"></div>

                        <!-- Step 2 -->
                        <div class="step" data-target="#test-nl-2">
                            <div class="step-trigger" role="tab" id="stepper2trigger2" aria-controls="test-nl-2" aria-selected="false">
                                <div class="bs-stepper-circle">
                                    <i class="ri-map-pin-line fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 steper-title">
                                        {{ t('stuff.address_info') }}
                                    </h5>
                                    <p class="mb-0 steper-sub-title">
                                        {{ t('stuff.address_info_details') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bs-stepper-line"></div>

                        <!-- Step 3 -->
                        <div class="step" data-target="#test-nl-3">
                            <div class="step-trigger" role="tab" id="stepper2trigger3" aria-controls="test-nl-3" aria-selected="false">
                                <div class="bs-stepper-circle">
                                    <i class="ri-school-line fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 steper-title">
                                        {{ t('stuff.eduction_info') }}
                                    </h5>
                                    <p class="mb-0 steper-sub-title">
                                        {{ t('stuff.eduction_info_details') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bs-stepper-line"></div>

                        <!-- Step 4 -->
                        <div class="step" data-target="#test-nl-4">
                            <div class="step-trigger" role="tab" id="stepper2trigger4" aria-controls="test-nl-4" aria-selected="false">
                                <div class="bs-stepper-circle">
                                    <i class="ri-user-community-line fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 steper-title">
                                        {{ t('stuff.social_info') }}
                                    </h5>
                                    <p class="mb-0 steper-sub-title">
                                        {{ t('stuff.social_info_details') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Card Body – Form Panes -->
                <div class="card-body">
                    <div class="bs-stepper-content">

                        <form class="ajax_form" action="{{ route('admin.stuff.store') }}" method="POST" enctype="multipart/form-data">

                            <!-- ========== STEP 1 ========== -->
                            <div id="test-nl-1" role="tabpanel" class="bs-stepper-pane fm-body active" aria-labelledby="stepper2trigger1">

                                <div class="row g-3">

                                    <div class="col-md-6 form-group">
                                        <label for="role_id" class="form-label">
                                            Role
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select name="role_id" id="role_id" class="form-select select" data-placeholder="Select Role" data-parsley-errors-container="#role_id_error">
                                            <option value="">Select Role</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                        <span id="role_id_error"></span>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label for="status" class="form-label">
                                            Status
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select name="status" id="status" class="form-select select" data-minimum-results-for-search="Infinity">
                                            <option value="1" selected>Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">
                                            Name
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="name" class="form-control" placeholder="Full Name" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" placeholder="Phone" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">
                                            Email
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" name="email" class="form-control" placeholder="Email" />
                                        <small class="text-muted">This email address is used for login to the system.</small>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">
                                            Password
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="password" id="password" class="form-control" placeholder="********" />
                                        <small class="text-muted">This password is used for login to the system.</small>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Designation</label>
                                        <input type="text" name="designation" class="form-control" placeholder="Designation" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">WhatsApp</label>
                                        <input type="text" name="whatsapp" class="form-control" placeholder="WhatsApp Number" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Avatar</label>
                                        <input type="file" name="avatar" class="form-control dropify"/>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Cover Photo</label>
                                        <input type="file" name="cover_photo" class="form-control dropify" />
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Skills</label>
                                        <input type="text" name="skills" class="form-control tag" placeholder="Laravel, Vue, React etc" value="Laravel, Vue.js, Tailwind" />
                                    </div>

                                    <div class="col-12">
                                        <button type="button" class="btn-nx-primary px-4" onclick="stepper2.next()">
                                            Next <i class="ri-arrow-right-line ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ========== STEP 2 ========== -->
                            <div id="test-nl-2" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper2trigger2">
                                <div class="row fm-body g-3">

                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" class="form-control" rows="3"></textarea>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Postal Code</label>
                                        <input type="text" name="postal_code" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">State</label>
                                        <input type="text" name="state" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Country</label>
                                        <input type="text" name="country" class="form-control" />
                                    </div>

                                    <div class="col-12">
                                        <div class="d-flex align-items-center gap-3">
                                            <button type="button" class="btn-nx-outline px-4" onclick="stepper2.previous()">
                                                <i class="ri-arrow-left-line me-2"></i> Previous
                                            </button>
                                            <button type="button" class="btn-nx-primary px-4" onclick="stepper2.next()">
                                                Next <i class="ri-arrow-right-line ms-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ========== STEP 3 ========== -->
                            <div id="test-nl-3" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper2trigger3">
                                <div class="row fm-body g-3">

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Highest Education</label>
                                        <input type="text" name="highest_education" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">University</label>
                                        <input type="text" name="university" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Major</label>
                                        <input type="text" name="major" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Current Job Title</label>
                                        <input type="text" name="current_job_title" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Current Company</label>
                                        <input type="text" name="current_company" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Years of Experience</label>
                                        <input type="number" name="years_of_experience" class="form-control" />
                                    </div>

                                    <div class="col-12">
                                        <div class="d-flex align-items-center gap-3">
                                            <button type="button" class="btn-nx-outline px-4" onclick="stepper2.previous()">
                                                <i class="ri-arrow-left-line me-2"></i> Previous
                                            </button>
                                            <button type="button" class="btn-nx-primary px-4" onclick="stepper2.next()">
                                                Next <i class="ri-arrow-right-line ms-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ========== STEP 4 ========== -->
                            <div id="test-nl-4" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper2trigger4">
                                <div class="row fm-body g-3">

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Facebook</label>
                                        <input type="url" name="facebook_url" placeholder="https://facebook.com/johndoe" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Twitter</label>
                                        <input type="url" name="twitter_url" placeholder="https://twitter.com/johndoe" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Instagram</label>
                                        <input type="url" name="instagram_url" placeholder="https://instagram.com/johndoe" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">LinkedIn</label>
                                        <input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/johndoe" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Pinterest</label>
                                        <input type="url" name="pinterest_url"  class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">TikTok</label>
                                        <input type="url" name="tiktok_url" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">GitHub</label>
                                        <input type="url" name="github_url" placeholder="https://github.com/johndoe" class="form-control" />
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Website</label>
                                        <input type="url" name="website_url" placeholder="https://johndoe.dev" class="form-control" />
                                    </div>

                                    <div class="col-12">
                                        <div class="d-flex align-items-center gap-3">
                                            <button type="button" class="btn-nx-outline px-4" onclick="stepper2.previous()">
                                                <i class="ri-arrow-left-line me-2"></i> Previous
                                            </button>
                                            <button class="btn-nx-primary px-4" type="submit" id="submit">
                                                Submit
                                            </button>
                                            <button class="btn-nx-primary px-4" type="button" id="submitting" disabled style="display:none;">
                                                <span class="spinner-border spinner-border-sm"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        _componentSelect();
        _componentDropify();

        const stepper2 = (function() {

            const container = document.getElementById('stepper2');
            const steps = container.querySelectorAll('.step');
            const panes = container.querySelectorAll('.bs-stepper-pane');
            const lines = container.querySelectorAll('.bs-stepper-line');
            const form = document.querySelector('.ajax_form');

            let currentIndex = 0;

            // find active step index
            steps.forEach((el, i) => {
                if (el.classList.contains('active')) currentIndex = i;
            });

            // Validate the currently visible pane using its group
            function validateCurrentStep() {
                const activePane = document.querySelector('.bs-stepper-pane.active');
                if (!activePane) return true;
                const group = activePane.dataset.group || 'step1';

                if (typeof window.Parsley !== 'undefined' && form) {
                    const parsleyForm = $(form).parsley();
                    // Validate only the fields belonging to this group
                    const isValid = parsleyForm.validate({ group: group });
                    // If invalid, trigger error display (already done by validate)
                    return isValid;
                } else {
                    // Fallback to native HTML5 validation
                    const inputs = activePane.querySelectorAll('input, select, textarea');
                    let valid = true;
                    inputs.forEach(el => {
                        if (el.hasAttribute('required') && !el.value.trim()) {
                            el.reportValidity();
                            valid = false;
                        } else if (el.type === 'email' && el.value && !el.validity.valid) {
                            el.reportValidity();
                            valid = false;
                        }
                        // add more as needed
                    });
                    return valid;
                }
            }

            function updateUI(index) {
                steps.forEach((step, i) => {
                    step.classList.remove('active', 'done');
                    if (i < index) step.classList.add('done');
                    if (i === index) step.classList.add('active');
                });

                panes.forEach((pane, i) => {
                    pane.classList.toggle('active', i === index);
                });

                lines.forEach((line, i) => {
                    line.classList.toggle('done', i < index);
                });

                steps.forEach((step, i) => {
                    const trigger = step.querySelector('.step-trigger');
                    if (trigger) {
                        trigger.setAttribute('aria-selected', i === index ? 'true' : 'false');
                    }
                });

                const cardBody = container.querySelector('.card-body');
                if (cardBody) cardBody.scrollTop = 0;
            }

            function goTo(index) {
                if (index < 0) index = 0;
                if (index >= steps.length) index = steps.length - 1;
                currentIndex = index;
                updateUI(currentIndex);
            }

            // Next: validate current step before moving forward
            function next() {
                if (validateCurrentStep()) {
                    goTo(currentIndex + 1);
                }
            }

            // Previous: no validation required
            function previous() {
                goTo(currentIndex - 1);
            }

            // Click handlers for step triggers
            steps.forEach((step, i) => {
                const trigger = step.querySelector('.step-trigger');
                if (trigger) {
                    trigger.addEventListener('click', function(e) {
                        e.preventDefault();
                        // Only allow going to current, previous, or next (one step ahead)
                        if (i <= currentIndex + 1) {
                            if (i > currentIndex) { // moving forward -> validate
                                if (validateCurrentStep()) {
                                    goTo(i);
                                }
                            } else { // moving backward or same
                                goTo(i);
                            }
                        }
                    });
                }
            });

            // expose public methods
            return { next, previous, goTo, getCurrentIndex: () => currentIndex };
        })();

        _ajaxFormHandler('.ajax_form');
    </script>
@endpush
