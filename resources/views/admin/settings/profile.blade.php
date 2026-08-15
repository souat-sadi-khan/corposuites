@extends('admin.layout.app', ['title' => 'My Profile', 'offcanvas' => '50%'])

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
                    <a href="{{ route('admin.edit.profile') }}" class="pm-btn pm-btn-primary">
                        <i class="ri-edit-box-line"></i>
                        Edit Profile
                    </a>

                    <a href="{{ route('admin.edit.password') }}" class="pm-btn">
                        <i class="ri-lock-password-line"></i>
                        Password
                    </a>
                </div>
            </div>
        </div>

        <div class="pm-layout">
            <div class="pm-sidebar">
                <div class="pm-card">
                    <h5>About</h5>

                    <div class="pm-list">
                        <div><i class="ri-user-3-line"></i><span>Full Name: {{ Auth::guard('admin')->user()->name }}</span></div>
                        <div><i class="ri-shield-check-line"></i><span>Status: <strong class="text-success">Active</strong></span></div>
                        <div><i class="ri-code-s-slash-line"></i><span>Role: {{ Auth::guard('admin')->user()->getRoleNames()->first() }}</span></div>
                        <div><i class="ri-map-pin-line"></i><span>Location: {{ $profile->address ?? 'Location Not Set' }}</span></div>
                        <div><i class="ri-earth-line"></i><span>Country: {{ $profile->country ?? 'Country Not Set' }}</span></div>
                    </div>
                </div>

                <div class="pm-card">
                    <h5>Contacts</h5>

                    <div class="pm-list">
                        <div><i class="ri-phone-line"></i><span>Phone: {{ Auth::guard('admin')->user()->phone ?? 'Phone Not Set' }}</span></div>
                        <div><i class="ri-whatsapp-line"></i><span>WhatsApp: {{ $profile->whatsapp ?? 'WhatsApp Not Set' }}</span></div>
                        <div><i class="ri-mail-line"></i><span>Email: {{ Auth::guard('admin')->user()->email }}</span></div>
                    </div>
                </div>

                <div class="pm-card">
                    <h5>Education</h5>

                    <div class="pm-list">
                        <div><i class="ri-graduation-cap-line"></i><span>Education: {{ $profile->highest_education ?? 'Education Not Set' }}</span></div>
                        <div><i class="ri-school-line"></i><span>University: {{ $profile->university ?? 'University Not Set' }}</span></div>
                        <div><i class="ri-book-open-line"></i><span>Subject: {{ $profile->major ?? 'Subject Not Set' }}</span></div>
                    </div>
                </div>

                <div class="pm-card">
                    <h5>Overview</h5>

                    <div class="pm-list">
                        <div><i class="ri-checkbox-circle-line"></i><span>Tasks Completed: 18,240</span></div>
                        <div><i class="ri-briefcase-line"></i><span>Active Projects: 12</span></div>
                        <div><i class="ri-line-chart-line"></i><span>Performance Score: 92%</span></div>
                    </div>
                </div>

                <div class="pm-card">
                    <h5>Skills</h5>

                    <div class="pm-skills">
                        @if ($skills)
                            @foreach ($skills as $skill)
                                <span>{{ $skill }}</span>
                            @endforeach
                        @else
                            <span>No Skills Added</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="pm-main">
                <div class="pm-card pm-timeline-card">
                    <h5>Activity Timeline</h5>

                    <div class="pm-timeline">
                        @forelse($activities as $activity)
                            <div class="pm-event">
                                <span class="pm-event-dot
                                    @if($activity->action == 'create') dot-success
                                    @elseif($activity->action == 'update') dot-info
                                    @elseif($activity->action == 'delete') dot-danger
                                    @else dot-primary
                                    @endif
                                "></span>

                                <div class="pm-event-content">
                                    <strong class="text-capitalize">{{ $activity->action }} {{ $activity->module }}</strong>
                                    <p>{{ $activity->description }}</p>
                                    <small>{{ $activity->created_at->diffForHumans() }}</small>
                                </div>

                                <button
                                    data-url="{{ route('admin.activity.show', $activity->id) }}"
                                    class="pm-icon-btn side-offcanvas"
                                    type="button"
                                >
                                    <i class="ri-eye-line"></i>
                                </button>
                            </div>
                        @empty
                            <div class="pm-empty">No activity found.</div>
                        @endforelse
                    </div>
                </div>

                <div class="pm-bottom-grid">
                    <div class="pm-card">
                        <h5>Connections</h5>

                        <div class="pm-social-list">
                            <div>
                                <i class="ri-facebook-circle-fill text-primary"></i>
                                <span>Facebook</span>
                                @if ($profile->facebook_url)
                                    <a href="{{ $profile->facebook_url }}" target="_blank"><i class="ri-external-link-line"></i></a>
                                @endif
                            </div>

                            <div>
                                <i class="ri-twitter-x-line"></i>
                                <span>Twitter</span>
                                @if ($profile->twitter_url)
                                    <a href="{{ $profile->twitter_url }}" target="_blank"><i class="ri-external-link-line"></i></a>
                                @endif
                            </div>

                            <div>
                                <i class="ri-instagram-line text-danger"></i>
                                <span>Instagram</span>
                                @if ($profile->instagram_url)
                                    <a href="{{ $profile->instagram_url }}" target="_blank"><i class="ri-external-link-line"></i></a>
                                @endif
                            </div>

                            <div>
                                <i class="ri-linkedin-box-fill text-primary"></i>
                                <span>LinkedIn</span>
                                @if ($profile->linkedin_url)
                                    <a href="{{ $profile->linkedin_url }}" target="_blank"><i class="ri-external-link-line"></i></a>
                                @endif
                            </div>

                            <div>
                                <i class="ri-youtube-fill text-danger"></i>
                                <span>YouTube</span>
                                @if ($profile->youtube_url)
                                    <a href="{{ $profile->youtube_url }}" target="_blank"><i class="ri-external-link-line"></i></a>
                                @endif
                            </div>

                            <div>
                                <i class="ri-tiktok-line"></i>
                                <span>TikTok</span>
                                @if ($profile->tiktok_url)
                                    <a href="{{ $profile->tiktok_url }}" target="_blank"><i class="ri-external-link-line"></i></a>
                                @endif
                            </div>

                            <div>
                                <i class="ri-pinterest-fill text-danger"></i>
                                <span>Pinterest</span>
                                @if ($profile->pinterest_url)
                                    <a href="{{ $profile->pinterest_url }}" target="_blank"><i class="ri-external-link-line"></i></a>
                                @endif
                            </div>

                            <div>
                                <i class="ri-github-fill"></i>
                                <span>Github</span>
                                @if ($profile->github_url)
                                    <a href="{{ $profile->github_url }}" target="_blank"><i class="ri-external-link-line"></i></a>
                                @endif
                            </div>

                            <div>
                                <i class="ri-global-line text-success"></i>
                                <span>Website</span>
                                @if ($profile->website_url)
                                    <a href="{{ $profile->website_url }}" target="_blank"><i class="ri-external-link-line"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="pm-card">
                        <h5>Address</h5>

                        <div class="pm-list">
                            <div><i class="ri-map-pin-2-line"></i><span>Address: {{ $profile->address ?? 'Address Not Set' }}</span></div>
                            <div><i class="ri-building-2-line"></i><span>City: {{ $profile->city ?? 'City Not Set' }}</span></div>
                            <div><i class="ri-map-pin-range-line"></i><span>Zip: {{ $profile->postal_code ?? 'Zip Not Set' }}</span></div>
                            <div><i class="ri-community-line"></i><span>State: {{ $profile->state ?? 'State Not Set' }}</span></div>
                            <div><i class="ri-earth-line"></i><span>Country: {{ $profile->country ?? 'Country Not Set' }}</span></div>
                        </div>
                    </div>

                    <div class="pm-card">
                        <h5>Professional</h5>

                        <div class="pm-list">
                            <div><i class="ri-id-card-line"></i><span>Job Title: {{ $profile->current_job_title ?? 'Job Not Set' }}</span></div>
                            <div><i class="ri-building-4-line"></i><span>Company: {{ $profile->current_company ?? 'Company Not Set' }}</span></div>
                            <div>
                                <i class="ri-time-line"></i>
                                <span>
                                    Experience:
                                    {{ $profile->years_of_experience ? $profile->years_of_experience . ' Years' : 'Experience Not Set' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pm-card pm-summary-card">
                        <h5>Quick Summary</h5>

                        <div class="pm-summary">
                            <div>
                                <strong>18,240</strong>
                                <span>Tasks</span>
                            </div>

                            <div>
                                <strong>12</strong>
                                <span>Projects</span>
                            </div>

                            <div>
                                <strong>92%</strong>
                                <span>Score</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            _componentSelect();
            _componentDropify();
            _ajaxFormHandler('.ajax_form');
            _componentRemoteOffcanvasLoadAfterAjax();
        });
    </script>
@endpush
