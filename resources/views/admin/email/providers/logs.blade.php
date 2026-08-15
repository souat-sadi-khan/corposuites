@extends('admin.layout.app', ['title' => 'Test Logs for ' . $provider->name])

@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb small mb-2">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.email.providers.index') }}">
                                Email Provider
                            </a>
                        </li>
                        <li class="breadcrumb-item active">
                            {{ $provider->name }}
                        </li>
                    </ol>
                </nav>

                <h4 class="mb-1 fw-semibold">
                    Logs: {{ $provider->name }}
                </h4>
            </div>
        </div>

        <div class="nx-card">
            <div class="table-responsive">
                <table class="tl-table">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Sender</th>
                            <th>Status</th>
                            <th>Sent At</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->recipient_email }}</td>
                                <td>{{ $log->subject }}</td>
                                <td>{{ $log->senderIdentity->email ?? '-' }}</td>
                                <td>
                                    <span class="badge-s bs-{{ $log->status == 'success' ? 'done' : 'canc' }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td>{{ $log->sent_at->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $log->error_message ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">No logs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
@endsection
