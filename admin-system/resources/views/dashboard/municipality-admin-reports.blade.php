@extends('layouts.app')

@section('title', 'Reports - ' . $municipality->name)
@section('header', 'Reports')

@section('content')
<style>
    .official-report {
        color: #1f2937;
        background: #fff;
    }
    .official-report-header {
        border-bottom: 3px solid #164e63;
        padding: 1.5rem 1.5rem 1rem;
        text-align: center;
    }
    .report-mark {
        color: #164e63;
        font-size: 2rem;
    }
    .official-report-header h1,
    .official-report-header h2,
    .official-report-header p {
        margin: 0;
    }
    .official-report-header h1 {
        color: #123b4a;
        font-size: 1.35rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .official-report-header h2 {
        font-size: 1.1rem;
        margin-top: 0.45rem;
        text-transform: uppercase;
    }
    .report-period {
        margin-top: 0.8rem !important;
        font-weight: 600;
    }
    .report-section-title {
        border-bottom: 2px solid #164e63;
        color: #164e63;
        font-size: 0.95rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        margin: 1.25rem 1.5rem 0.75rem;
        padding-bottom: 0.35rem;
        text-transform: uppercase;
    }
    .report-summary {
        margin: 0 1.5rem;
    }
    .report-summary td {
        border: 0;
        padding: 0.28rem 0;
    }
    .report-summary td:last-child {
        font-weight: 700;
        text-align: right;
    }
    .report-detail-table {
        margin: 0 1.5rem;
        width: calc(100% - 3rem);
    }
    .report-detail-table th,
    .report-detail-table td {
        border: 1px solid #9ca3af;
        padding: 0.5rem 0.6rem;
        vertical-align: top;
    }
    .report-detail-table th {
        background: #e6f0f2;
        color: #123b4a;
        font-size: 0.78rem;
        text-transform: uppercase;
    }
    .report-signoff {
        margin: 2rem 1.5rem 1.5rem;
    }
    .report-signoff p {
        margin: 0.65rem 0;
    }
    .report-footer {
        border-top: 1px solid #9ca3af;
        color: #4b5563;
        font-size: 0.75rem;
        margin: 1.5rem;
        padding-top: 0.5rem;
        text-align: center;
    }
    @media print {
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        .sidebar,
        .report-controls,
        .report-print-button,
        .main-col > header,
        .main-col > .alert {
            display: none !important;
        }
        .main-col,
        .main-content {
            padding: 0 !important;
        }
        body {
            background: #fff !important;
        }
        .official-report {
            box-shadow: none !important;
            margin: 0 !important;
        }
        .report-footer {
            bottom: 0;
            left: 0;
            margin: 0;
            position: fixed;
            right: 0;
        }
        .report-page-number::after {
            content: 'Page ' counter(page) ' of ' counter(pages);
        }
    }
</style>

<div class="report-controls card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('municipality-admin.reports') }}" class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
                <label for="report-search" class="form-label">Search reports</label>
                <input type="search" name="q" id="report-search" value="{{ $searchTerm }}" class="form-control" placeholder="Search by tourist spot, address, or category">
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label for="report-date" class="form-label">Date added</label>
                <input type="date" name="date" id="report-date" value="{{ $reportDate }}" class="form-control">
            </div>
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                <a href="{{ route('municipality-admin.reports') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
            <div class="col-12 col-md-auto ms-md-auto">
                <button type="button" class="btn btn-dark report-print-button" onclick="window.print()">
                    <i class="fas fa-print"></i> Print All Reports
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card official-report">
    <header class="official-report-header">
        <div class="report-mark"><i class="fas fa-map-location-dot"></i></div>
        <p>REPUBLIC OF THE PHILIPPINES</p>
        <p>PROVINCE OF PANGASINAN</p>
        <p>TOURISM OFFICE / TOURISM SYSTEM</p>
        <h1>Tourist Spot Inventory Report</h1>
        <h2>2nd District of Pangasinan</h2>
        <p>Municipality: {{ $municipality->name }}</p>
        <p class="report-period">Report Period: {{ $reportPeriod }}</p>
        <p>Generated: {{ now()->format('F j, Y') }}</p>
    </header>

    <div class="report-section-title">Summary</div>
    <table class="report-summary">
        <tbody>
            <tr><td>Total Tourist Spots</td><td>{{ $totalSpots }}</td></tr>
            <tr><td>Verified</td><td>{{ $verifiedSpots }}</td></tr>
            <tr><td>Pending Verification</td><td>{{ $pendingSpots }}</td></tr>
        </tbody>
    </table>

    <div class="report-section-title">Tourist Spot Inventory</div>
    <table class="report-detail-table">
            <thead class="bg-light">
                <tr>
                    <th>No.</th>
                    <th>Tourist Spot</th>
                    <th>Category</th>
                    <th>Barangay</th>
                    <th>Status</th>
                    <th>Date Added</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse($spots as $index => $spot)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $spot->name }}</td>
                        <td>{{ ucfirst($spot->category ?? 'nature') }}</td>
                        <td>{{ $spot->barangay ?: 'Not provided' }}</td>
                        <td>
                            @if($hasVerificationStatus && $spot->verification_status === 'approved')
                                Verified
                            @else
                                Pending
                            @endif
                        </td>
                        <td>{{ $spot->created_at?->format('M d, Y') ?? 'Not available' }}</td>
                        <td>{{ $spot->updated_at?->format('M d, Y') ?? 'Not available' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No tourist spots match the selected filters</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    <div class="report-signoff">
        <p>Prepared by: ____________________________________</p>
        <p>Position: Municipal Tourism Officer</p>
        <p>Date Printed: ___________________________________</p>
    </div>
    <footer class="report-footer">Tourist Spot Inventory Report | {{ $municipality->name }} | <span class="report-page-number"></span></footer>
</div>
@endsection