@extends('admin.layout.app', ['title' => 'Update Profile'])

@push('styles')

@endpush

@section('content')
    <div class="profile-wizard">
        <div class="pw-card">
            <div class="pw-steps">
                <button type="button" class="pw-step active" data-step="1">
                    <span><i class="ri-user-3-line"></i></span>
                    <strong>Personal</strong>
                    <small>Your basic details</small>
                </button>

                <div class="pw-line"></div>

                <button type="button" class="pw-step" data-step="2">
                    <span><i class="ri-map-pin-line"></i></span>
                    <strong>Address</strong>
                    <small>Location details</small>
                </button>

                <div class="pw-line"></div>

                <button type="button" class="pw-step" data-step="3">
                    <span><i class="ri-briefcase-4-line"></i></span>
                    <strong>Career</strong>
                    <small>Education and work</small>
                </button>

                <div class="pw-line"></div>

                <button type="button" class="pw-step" data-step="4">
                    <span><i class="ri-share-line"></i></span>
                    <strong>Social</strong>
                    <small>Profile links</small>
                </button>
            </div>

            <div class="pw-progress">
                <span></span>
            </div>

            <form class="ajax_form pw-form" action="{{ route('admin.update.profile') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="pw-pane active" data-pane="1">
                    <div class="pw-pane-head">
                        <h5>Personal Information</h5>
                        <p>Keep your public profile identity updated.</p>
                    </div>

                    <div class="profile-media-editor">
                        <input
                            type="hidden"
                            name="cover_theme"
                            id="coverTheme"
                            value="{{ $profile->cover_theme ?? 'aurora' }}"
                        >

                        <div class="pme-cover pme-cover-{{ $profile->cover_theme ?? 'aurora' }}" id="profileCoverPreview">
                            <div class="pme-avatar-wrap">
                                <button type="button" class="pme-avatar-btn" id="avatarPickerBtn">
                                    <img
                                        id="avatarPreview"
                                        src="{{ Auth::guard('admin')->user()->avatar ? asset(Auth::guard('admin')->user()->avatar) : asset('assets/system/images/avatar.png') }}"
                                        alt="Avatar"
                                    >
                                    <span><i class="ri-camera-line"></i></span>
                                </button>

                                <div class="pme-avatar-menu" id="avatarMenu">
                                    <button type="button" data-avatar-action="upload">
                                        <i class="ri-upload-cloud-2-line"></i>
                                        Upload Photo
                                    </button>
                                    <button type="button" data-avatar-action="camera">
                                        <i class="ri-camera-line"></i>
                                        Take Photo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <input type="file" name="avatar" id="avatarUploadInput" class="d-none" accept="image/*">

                        <div class="pme-cover-tools">
                            <div>
                                <h6>Cover Style</h6>
                                <p>Select a professional gradient for your profile cover.</p>
                            </div>

                            <div class="pme-cover-swatches">
                                <button type="button" class="pme-swatch pme-cover-aurora active" data-cover="aurora"></button>
                                <button type="button" class="pme-swatch pme-cover-ocean" data-cover="ocean"></button>
                                <button type="button" class="pme-swatch pme-cover-emerald" data-cover="emerald"></button>
                                <button type="button" class="pme-swatch pme-cover-sunset" data-cover="sunset"></button>
                                <button type="button" class="pme-swatch pme-cover-royal" data-cover="royal"></button>
                                <button type="button" class="pme-swatch pme-cover-graphite" data-cover="graphite"></button>
                                <button type="button" class="pme-swatch pme-cover-coral" data-cover="coral"></button>
                                <button type="button" class="pme-swatch pme-cover-skyline" data-cover="skyline"></button>
                                <button type="button" class="pme-swatch pme-cover-mint" data-cover="mint"></button>
                                <button type="button" class="pme-swatch pme-cover-plum" data-cover="plum"></button>
                                <button type="button" class="pme-swatch pme-cover-gold" data-cover="gold"></button>
                                <button type="button" class="pme-swatch pme-cover-crimson" data-cover="crimson"></button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Name</label>
                            <div class="pw-field">
                                <i class="ri-user-line"></i>
                                <input type="text" required value="{{ Auth::guard('admin')->user()->name }}" name="name" class="form-control" placeholder="Full name">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Username</label>
                            <div class="pw-field">
                                <i class="ri-at-line"></i>
                                <input type="text" name="username" value="{{ Auth::guard('admin')->user()->username }}" class="form-control" placeholder="Username">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Email</label>
                            <div class="pw-field">
                                <i class="ri-mail-line"></i>
                                <input type="email" name="email" value="{{ Auth::guard('admin')->user()->email }}" class="form-control" placeholder="Email address">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Phone</label>
                            <div class="pw-field">
                                <i class="ri-phone-line"></i>
                                <input type="text" name="phone" value="{{ Auth::guard('admin')->user()->phone }}" class="form-control" placeholder="Phone number">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Designation</label>
                            <div class="pw-field">
                                <i class="ri-medal-line"></i>
                                <input type="text" value="{{ $profile->designation }}" name="designation" class="form-control" placeholder="Designation">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">WhatsApp</label>
                            <div class="pw-field">
                                <i class="ri-whatsapp-line"></i>
                                <input type="text" value="{{ $profile->whatsapp }}" name="whatsapp" class="form-control" placeholder="WhatsApp number">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Skills</label>
                            <div class="pw-field">
                                <i class="ri-price-tag-3-line"></i>
                                <input type="text" name="skills" class="form-control tag" placeholder="Laravel, Vue, React" value="{{ $skills }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pw-pane" data-pane="2">
                    <div class="pw-pane-head">
                        <h5>Address Information</h5>
                        <p>Add the location details connected to your profile.</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="4" placeholder="Street address">{{ $profile->address }}</textarea>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">City</label>
                            <div class="pw-field">
                                <i class="ri-building-line"></i>
                                <input type="text" name="city" value="{{ $profile->city }}" class="form-control" placeholder="City">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Postal Code</label>
                            <div class="pw-field">
                                <i class="ri-map-pin-range-line"></i>
                                <input type="text" name="postal_code" value="{{ $profile->postal_code }}" class="form-control" placeholder="Postal code">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">State</label>
                            <div class="pw-field">
                                <i class="ri-community-line"></i>
                                <input type="text" name="state" value="{{ $profile->state }}" class="form-control" placeholder="State">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Country</label>
                            <div class="pw-field">
                                <i class="ri-earth-line"></i>
                                <input type="text" name="country" value="{{ $profile->country }}" class="form-control" placeholder="Country">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pw-pane" data-pane="3">
                    <div class="pw-pane-head">
                        <h5>Education & Experience</h5>
                        <p>Show your academic and professional background.</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Highest Education</label>
                            <div class="pw-field">
                                <i class="ri-graduation-cap-line"></i>
                                <input type="text" name="highest_education" value="{{ $profile->highest_education }}" class="form-control" placeholder="Highest education">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">University</label>
                            <div class="pw-field">
                                <i class="ri-school-line"></i>
                                <input type="text" name="university" value="{{ $profile->university }}" class="form-control" placeholder="University">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Major</label>
                            <div class="pw-field">
                                <i class="ri-book-open-line"></i>
                                <input type="text" name="major" value="{{ $profile->major }}" class="form-control" placeholder="Major">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Current Job Title</label>
                            <div class="pw-field">
                                <i class="ri-id-card-line"></i>
                                <input type="text" name="current_job_title" value="{{ $profile->current_job_title }}" class="form-control" placeholder="Current job title">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Current Company</label>
                            <div class="pw-field">
                                <i class="ri-building-4-line"></i>
                                <input type="text" name="current_company" value="{{ $profile->current_company }}" class="form-control" placeholder="Current company">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Years of Experience</label>
                            <div class="pw-field">
                                <i class="ri-time-line"></i>
                                <input type="number" name="years_of_experience" value="{{ $profile->years_of_experience }}" class="form-control" placeholder="Years of experience">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pw-pane" data-pane="4">
                    <div class="pw-pane-head">
                        <h5>Social Links</h5>
                        <p>Connect your social and professional profiles.</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Facebook</label>
                            <div class="pw-field">
                                <i class="ri-facebook-circle-fill"></i>
                                <input type="url" name="facebook_url" value="{{ $profile->facebook_url }}" class="form-control" placeholder="Facebook URL">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Twitter / X</label>
                            <div class="pw-field">
                                <i class="ri-twitter-x-line"></i>
                                <input type="url" name="twitter_url" value="{{ $profile->twitter_url }}" class="form-control" placeholder="Twitter URL">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Instagram</label>
                            <div class="pw-field">
                                <i class="ri-instagram-line"></i>
                                <input type="url" name="instagram_url" value="{{ $profile->instagram_url }}" class="form-control" placeholder="Instagram URL">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">LinkedIn</label>
                            <div class="pw-field">
                                <i class="ri-linkedin-box-fill"></i>
                                <input type="url" name="linkedin_url" value="{{ $profile->linkedin_url }}" class="form-control" placeholder="LinkedIn URL">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Pinterest</label>
                            <div class="pw-field">
                                <i class="ri-pinterest-fill"></i>
                                <input type="url" name="pinterest_url" value="{{ $profile->pinterest_url }}" class="form-control" placeholder="Pinterest URL">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">TikTok</label>
                            <div class="pw-field">
                                <i class="ri-tiktok-line"></i>
                                <input type="url" name="tiktok_url" value="{{ $profile->tiktok_url }}" class="form-control" placeholder="TikTok URL">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">GitHub</label>
                            <div class="pw-field">
                                <i class="ri-github-fill"></i>
                                <input type="url" name="github_url" value="{{ $profile->github_url }}" class="form-control" placeholder="GitHub URL">
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Website</label>
                            <div class="pw-field">
                                <i class="ri-global-line"></i>
                                <input type="url" name="website_url" value="{{ $profile->website_url }}" class="form-control" placeholder="Website URL">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pw-footer">
                    <button type="button" class="pw-btn pw-btn-light" id="pwPrev">
                        <i class="ri-arrow-left-line"></i>
                        Previous
                    </button>

                    <button type="button" class="pw-btn pw-btn-primary" id="pwNext">
                        Next
                        <i class="ri-arrow-right-line"></i>
                    </button>

                    <button class="pw-btn pw-btn-success" type="submit" id="submit">
                        <i class="ri-save-3-line"></i>
                        Save Profile
                    </button>

                    <button class="pw-btn pw-btn-success" type="submit" id="submitting" disabled style="display:none;">
                        <span class="spinner-border spinner-border-sm"></span>
                        Saving
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>

        $(document).ready(function() {
            _ajaxFormHandler('.ajax_form');

            let currentStep = 1;
            const totalSteps = 4;

            function showStep(step) {
                currentStep = step;

                $('.pw-pane').removeClass('active');
                $('.pw-pane[data-pane="' + step + '"]').addClass('active');

                $('.pw-step').removeClass('active done');

                $('.pw-step').each(function () {
                    const itemStep = Number($(this).data('step'));

                    if (itemStep < step) {
                        $(this).addClass('done');
                    }

                    if (itemStep === step) {
                        $(this).addClass('active');
                    }
                });

                $('.pw-progress span').css('width', (step / totalSteps * 100) + '%');

                $('#pwPrev').toggle(step > 1);
                $('#pwNext').toggle(step < totalSteps);
                $('#submit').toggle(step === totalSteps);
            }

            function validateCurrentStep() {
                let isValid = true;
                const pane = $('.pw-pane[data-pane="' + currentStep + '"]');

                pane.find('[required]').each(function () {
                    if (!this.checkValidity()) {
                        this.reportValidity();
                        isValid = false;
                        return false;
                    }
                });

                return isValid;
            }

            $('#pwNext').on('click', function () {
                if (validateCurrentStep() && currentStep < totalSteps) {
                    showStep(currentStep + 1);
                }
            });

            $('#pwPrev').on('click', function () {
                if (currentStep > 1) {
                    showStep(currentStep - 1);
                }
            });

            $('.pw-step').on('click', function () {
                const targetStep = Number($(this).data('step'));

                if (targetStep <= currentStep || validateCurrentStep()) {
                    showStep(targetStep);
                }
            });

            showStep(1);
        });

        $(function () {
            const savedCover = $('#coverTheme').val() || 'aurora';

            $('.pme-swatch').removeClass('active');
            $('.pme-swatch[data-cover="' + savedCover + '"]').addClass('active');

            $('.pme-swatch').on('click', function () {
                const cover = $(this).data('cover');
                $('.pme-swatch').removeClass('active');
                $(this).addClass('active');
                $('#coverTheme').val(cover);
                $('#profileCoverPreview')
                    .removeClass(function (index, className) {
                        return (className.match(/(^|\s)pme-cover-\S+/g) || []).join(' ');
                    })
                    .addClass('pme-cover pme-cover-' + cover);
            });

            $('#avatarPickerBtn').on('click', function (e) {
                e.stopPropagation();
                $('#avatarMenu').toggleClass('is-open');
            });

            $('[data-avatar-action="upload"]').on('click', function () {
                $('#avatarMenu').removeClass('is-open');
                $('#avatarUploadInput').trigger('click');
            });

            $('[data-avatar-action="camera"]').on('click', function () {
                $('#avatarMenu').removeClass('is-open');
                $('#avatarCameraInput').trigger('click');
            });

            // FIXED: Use DataTransfer to copy the file from camera input to avatar input
            $('#avatarUploadInput, #avatarCameraInput').on('change', function () {
                const file = this.files && this.files[0];
                if (!file) {
                    return;
                }

                const previewUrl = URL.createObjectURL(file);
                $('#avatarPreview').attr('src', previewUrl);

                if (this.id === 'avatarCameraInput') {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    $('#avatarUploadInput')[0].files = dataTransfer.files;
                }
            });

            $(document).on('click', function () {
                $('#avatarMenu').removeClass('is-open');
            });
        });

        // Camera action using getUserMedia
        $('[data-avatar-action="camera"]').on('click', function () {
            $('#avatarMenu').removeClass('is-open');

            // Check if browser supports getUserMedia
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Your browser does not support camera access. Please use the upload option.');
                return;
            }

            // Request camera (facing user for selfie)
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                .then(function (stream) {
                    // Create a hidden video element to show the camera feed (optional)
                    const video = document.createElement('video');
                    video.srcObject = stream;
                    video.play();

                    // After a short delay, capture a frame
                    setTimeout(function () {
                        const canvas = document.createElement('canvas');
                        canvas.width = video.videoWidth || 640;
                        canvas.height = video.videoHeight || 480;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                        // Stop all tracks to release camera
                        stream.getTracks().forEach(track => track.stop());

                        // Convert canvas to Blob
                        canvas.toBlob(function (blob) {
                            if (!blob) {
                                alert('Could not capture image. Please try again.');
                                return;
                            }

                            // Create a File from the Blob
                            const file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });

                            // Update the avatar preview
                            const previewUrl = URL.createObjectURL(file);
                            $('#avatarPreview').attr('src', previewUrl);

                            // Assign the file to the hidden input using DataTransfer
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            $('#avatarUploadInput')[0].files = dataTransfer.files;

                            // Optional: close any menu or modal
                        }, 'image/jpeg', 0.9); // 0.9 quality
                    }, 500); // wait for video to start
                })
                .catch(function (err) {
                    console.error('Camera error:', err);
                    alert('Unable to access camera. Please check permissions or use the upload option.');
                });
        });
    </script>
@endpush
