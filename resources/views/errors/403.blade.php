@extends('installer.layout', ['title' => 'Access Forbidden'])

@section('content')
    <style>
        .error-page-500 {
        height: 100vh;
        background: linear-gradient(135deg, #2af598, #009efd);
        display: flex;
        justify-content: center;
        align-items: center;
        color: #fff;
        text-align: center;
        position: relative;
        overflow: hidden;
        }

        .error-page-500::before {
        content: "";
        position: absolute;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.6);
        }

        .error-page-500 .content {
        position: relative;
        z-index: 2;
        animation: fadeIn 1s ease;
        }

        .error-page-500 h1 {
        font-size: 8rem;
        font-weight: bold;
        text-shadow: 0 4px 25px rgba(0, 0, 0, 0.6);
        color: #fff;
        animation: popShake 1s ease-in-out;
        }

        .error-page-500 h2 {
        font-size: 2rem;
        font-weight: 600;
        animation: fadeDown 0.6s ease 0.4s both;
        }

        .error-page-500 p {
        max-width: 500px;
        margin: 20px auto;
        opacity: 0.9;
        animation: fadeUp 0.6s ease 0.7s both;
        }

        .error-page-500 .btn-home {
        border-radius: 30px;
        padding: 12px 30px;
        font-weight: bold;
        animation: fadeIn 0.6s ease 1s both;
        }

        /* Animations */
        @keyframes popShake {
        0% { transform: scale(0); opacity: 0; }
        40% { transform: scale(1.1) rotate(5deg); }
        60% { transform: rotate(-5deg); }
        80% { transform: rotate(3deg); }
        100% { transform: scale(1) rotate(0); opacity: 1; }
        }
        @keyframes fadeDown {
        0% { transform: translateY(-40px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
        }
        @keyframes fadeUp {
        0% { transform: translateY(40px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
        }
        @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
        }
    </style>
    <div class="error-page-500">
        <div class="container content">
            <h1 class="">403</h1>
            <h2 class="mb-3">Access Forbidden</h2>
            <p class="mb-4">
                {{ $exception->getMessage() ?? "You don’t have permission to access this page." }}
            </p>
            <a href="{{ url('/') }}" class="btn btn-light btn-home">
                <i class="bi bi-house-door-fill me-2"></i> Go Back Home
            </a>
        </div>
    </div>
@endsection
