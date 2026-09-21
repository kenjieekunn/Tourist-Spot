@extends('layouts.app')

@section('title', 'Reports - Super Admin')
@section('header', 'Reports')

@section('content')
<style>
    .official-report { color: #1f2937; background: #fff; }
    .official-report-header { border-bottom: 3px solid #164e63; padding: 1.5rem; text-align: center; }
    .report-mark { color: #164e63; font-size: 2rem; }
    .official-report-header h1, .official-report-header h2, .official-report-header p { margin: 0; }
    .official-report-header h1 { color: #123b4a; font-size: 1.35rem; letter-spacing: 0.04em; text-transform: uppercase; }
    .official-report-header h2 { font-size: 1.1rem; margin-top: 0.45rem; text-transform: uppercase; }
    .report-period { margin-top: 0.8rem !important; font-weight: 600; }
    .report-section-title { border-bottom: 2px solid #164e63; color: #164e63; font-size: 0.95rem; font-weight: 800; letter-spacing: 0.08em; margin: 1.25rem 1.5rem 0.75rem; padding-bottom: 0.35rem; text-transform: uppercase; }
    .report-summary { margin: 0 1.5rem; }
    .report-summary td { border: 0; padding: 0.28rem 0; }
    .report-summary td:last-child { font-weight: 700; text-align: right; }
    .report-detail-table { margin: 0 1.5rem; width: calc(100% - 3rem); }
    .report-detail-table th, .report-detail-table td { border: 1px solid #9ca3af; padding: 0.5rem 0.6rem; vertical-align: top; }
    .report-detail-table th { background: #e6f0f2; color: #123b4a; font-size: 0.78rem; text-transform: uppercase; }
    .report-signoff { margin: 2rem 1.5rem 1.5rem; }
    .report-signoff p { margin: 0.65rem 0; }
    .report-footer { border-top: 1px solid #9ca3af; color: #4b5563; font-size: 0.75rem; margin: 1.5rem; padding-top: 0.5rem; text-align: center; }
    .report-print-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: flex-end; margin-bottom: 1rem; }
    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        .sidebar, .report-controls, .main-col > header, .main-col > .alert { display: none !important; }
        .main-col, .main-content { padding: 0 !important; }
        body { background: #fff !important; }
        .official-report { box-shadow: none !important; margin: 0 !important; }
        .official-report.print-report-hidden { display: none !important; }
        .report-footer { bottom: 0; left: 0; margin: 0; position: fixed; right: 0; }
        .report-page-number::after { content: 'Page ' counter(page) ' of ' counter(pages); }
    }
</style>

<div class="report-controls card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('super-admin.reports') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label for="reportType" class="form-label">Report Type</label>
                <select name="report_type" id="reportType" class="form-select">
                    <option value="all" @selected($reportType === 'all')>All Reports</option>
                    <option value="overall" @selected($reportType === 'overall')>Overall Report</option>
                    <option value="verification" @selected($reportType === 'verification')>Verification Report</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label for="municipalityId" class="form-label">Municipality</label>
                <select name="municipality_id" id="municipalityId" class="form-select">
                    <option value="0" @selected($municipalityId === 0)>All Municipalities</option>
                    @foreach($municipalities as $municipality)
                        <option value="{{ $municipality->id }}" @selected($municipalityId === $municipality->id)>{{ $municipality->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label for="reportStatus" class="form-label">Status</label>
                <select name="status" id="reportStatus" class="form-select">
                    <option value="all" @selected($status === 'all')>All Statuses</option>
                    <option value="pending" @selected($status === 'pending')>Pending</option>
                    <option value="approved" @selected($status === 'approved')>Verified</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label for="dateFrom" class="form-label">Date From</label>
                <input type="date" name="date_from" id="dateFrom" value="{{ $dateFrom }}" class="form-control">
            </div>
            <div class="col-12 col-md-2">
                <label for="dateTo" class="form-label">Date To</label>
                <input type="date" name="date_to" id="dateTo" value="{{ $dateTo }}" class="form-control">
            </div>
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary"><i class="fas fa-file-lines"></i> Generate Report</button>
                <a href="{{ route('super-admin.reports') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4 report-controls">
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase">Tourist Spots</div><div class="fs-2 fw-bold">{{ $totalSpots }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase">Municipalities</div><div class="fs-2 fw-bold">{{ $totalMunicipalities }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase">Municipal Admins</div><div class="fs-2 fw-bold">{{ $totalAdmins }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase">Reviews</div><div class="fs-2 fw-bold">{{ $totalReviews }}</div></div></div></div>
</div>

<div class="report-print-actions report-controls">
    @if($reportType !== 'verification')
        <button type="button" class="btn btn-dark" onclick="printReport('overall-report')"><i class="fas fa-print"></i> Print</button>
    @endif
    @if($reportType !== 'overall')
        <button type="button" class="btn btn-outline-dark" onclick="printReport('verification-report')"><i class="fas fa-clipboard-check"></i> Print Verification Report</button>
    @endif
    <button type="button" class="btn btn-outline-success" onclick="exportPdf()"><i class="fas fa-file-pdf"></i> Export PDF</button>
</div>

@if($reportType !== 'verification')
<div id="overall-report" class="card official-report">
    <header class="official-report-header">
        <div class="report-mark"><i class="fas fa-map-location-dot"></i></div>
        <p>REPUBLIC OF THE PHILIPPINES</p><p>PROVINCE OF PANGASINAN</p><p>TOURISM OFFICE / TOURISM SYSTEM</p>
        <h1>Tourist Spot Management Report</h1><h2>2nd District of Pangasinan</h2>
        <p class="report-period">Report Period: {{ $reportPeriod }}</p><p>Generated: {{ now()->format('F j, Y') }}</p>
    </header>
    <div class="report-section-title">Summary</div>
    <table class="report-summary"><tbody>
        <tr><td>Total Tourist Spots</td><td>{{ $totalSpots }}</td></tr>
        <tr><td>Verified</td><td>{{ $verifiedSpots }}</td></tr>
        <tr><td>Pending Verification</td><td>{{ $pendingSpots }}</td></tr>
        <tr><td>Active</td><td>{{ $activeSpots }}</td></tr>
    </tbody></table>
    <div class="report-section-title">Detailed Report</div>
    <table class="report-detail-table"><thead><tr><th>No.</th><th>Tourist Spot</th><th>Municipality</th><th>Status</th></tr></thead><tbody>
        @php $reportNumber = 0; @endphp
        @forelse($touristSpots as $municipalityName => $spots)
            @foreach($spots as $spot)
                @php $reportNumber++; @endphp
                <tr><td>{{ $reportNumber }}</td><td><strong>{{ $spot->name }}</strong><br><small>{{ $spot->address ?: 'No address provided' }}</small></td><td>{{ $municipalityName }}</td><td>@if($hasVerificationStatus){{ ucfirst($spot->verification_status ?? 'pending') }}@else{{ in_array($spot->status, ['open', 'active']) ? 'Active' : ucfirst($spot->status ?? 'Unknown') }}@endif</td></tr>
            @endforeach
        @empty
            <tr><td colspan="4" class="text-center py-4">No tourist spots have been added</td></tr>
        @endforelse
    </tbody></table>
    <div class="report-signoff"><p>Prepared by: ____________________________________</p><p>Position: Provincial Tourism Officer</p><p>Date Printed: ___________________________________</p></div>
    <footer class="report-footer">Tourist Spot Management Report | 2nd District of Pangasinan | <span class="report-page-number"></span></footer>
</div>
@endif

@if($reportType !== 'overall')
<div id="verification-report" class="card official-report">
    <header class="official-report-header">
        <div class="report-mark"><i class="fas fa-clipboard-check"></i></div>
        <p>REPUBLIC OF THE PHILIPPINES</p><p>PROVINCE OF PANGASINAN</p><p>TOURISM OFFICE / TOURISM SYSTEM</p>
        <h1>Tourist Spot Verification Report</h1><h2>2nd District of Pangasinan</h2>
        <p class="report-period">Report Period: {{ $reportPeriod }}</p><p>Generated: {{ now()->format('F j, Y') }}</p>
    </header>
    <div class="report-section-title">Verification Summary</div>
    <table class="report-summary"><tbody>
        <tr><td>Pending Verification</td><td>{{ $pendingSpots }}</td></tr>
        <tr><td>Verified</td><td>{{ $verifiedSpots }}</td></tr>
    </tbody></table>
    <div class="report-section-title">Verification Details</div>
    <table class="report-detail-table"><thead><tr><th>No.</th><th>Tourist Spot</th><th>Submitted By</th><th>Date</th><th>Status</th></tr></thead><tbody>
        @php $verificationNumber = 0; @endphp
        @forelse($verificationSpots as $spot)
            @php $verificationNumber++; @endphp
            <tr><td>{{ $verificationNumber }}</td><td><strong>{{ $spot->name }}</strong></td><td>{{ $spot->creator?->name ?? 'Admin' }}</td><td>{{ $spot->created_at?->format('M d, Y') ?? 'Not available' }}</td><td>{{ $hasVerificationStatus && $spot->verification_status === 'approved' ? 'Verified' : 'Pending' }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-center py-4">No verification records found</td></tr>
        @endforelse
    </tbody></table>
    <div class="report-signoff"><p>Prepared by: ____________________________________</p><p>Position: Provincial Tourism Officer</p><p>Date Printed: ___________________________________</p></div>
    <footer class="report-footer">Tourist Spot Verification Report | 2nd District of Pangasinan | <span class="report-page-number"></span></footer>
</div>
@endif

<script>
    function printReport(reportId) {
        document.querySelectorAll('.official-report').forEach(report => {
            report.classList.toggle('print-report-hidden', report.id !== reportId);
        });
        window.print();
        window.setTimeout(() => document.querySelectorAll('.official-report').forEach(report => report.classList.remove('print-report-hidden')), 500);
    }

    function exportPdf() {
        const reportId = '{{ $reportType === 'verification' ? 'verification-report' : 'overall-report' }}';
        printReport(reportId);
    }

</script>
@endsection
