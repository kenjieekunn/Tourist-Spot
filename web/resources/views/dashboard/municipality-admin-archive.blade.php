@extends('layouts.app')

@section('title', 'Archive - ' . $municipality->name . ' Admin')
@section('header', 'Archive')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">{{ $municipality->name }} archive</h2>
        <div class="text-muted small">{{ $archives->total() }} archived records</div>
    </div>
</div>

<nav class="nav nav-tabs mb-4" aria-label="Municipality archive type">
    @foreach($archiveTypes as $type => $label)
        <a class="nav-link {{ $selectedType === $type ? 'active' : '' }}"
            href="{{ route('municipality-admin.archive', ['type' => $type]) }}"
            @if($selectedType === $type) aria-current="page" @endif>
            {{ $label }}
        </a>
    @endforeach
</nav>

@if($archiveUnavailable)
    <div class="alert alert-warning" role="alert">
        Archive storage is not available yet. Run <code>php artisan migrate --force</code> from the Laravel app directory, then reload this page.
    </div>
@else
    @forelse($archives as $archive)
        @php
            $spotRecord = $archive->record_type === 'tourist_spot';
            $details = $spotRecord
                ? trim(implode(' | ', array_filter([
                    data_get($archive->data, 'category') ? ucfirst(data_get($archive->data, 'category')) : null,
                    data_get($archive->data, 'barangay'),
                    data_get($archive->data, 'address'),
                ])))
                : data_get($archive->data, 'email');
        @endphp
        <article class="border-bottom py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h3 class="h6 mb-0">{{ $archive->record_name }}</h3>
                        <span class="badge text-bg-secondary">{{ $spotRecord ? 'Deleted tourist spot' : 'Deleted staff account' }}</span>
                    </div>
                    @if($details)
                        <div class="small text-muted">{{ $details }}</div>
                    @endif
                </div>
                <div class="small text-muted text-md-end">
                    <div>Deleted {{ optional($archive->archived_at)->format('M d, Y h:i A') }}</div>
                    @if($archive->archived_by_name)
                        <div>By {{ $archive->archived_by_name }}</div>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="text-center text-muted py-5 border rounded">
            <i class="fas fa-box-open fa-2x mb-3" aria-hidden="true"></i>
            <div>No archived records in this category.</div>
        </div>
    @endforelse

    <div class="mt-3">{{ $archives->links() }}</div>
@endif
@endsection