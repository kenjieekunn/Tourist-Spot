@extends('layouts.app')

@section('title', 'Reports - Super Admin')
@section('header', '')

@section('content')
@php
    $reportTitles = ['management' => 'Tourist Spot Management Report', 'verification' => 'Tourist Spot Verification Report', 'reviews' => 'Tourist Reviews Report'];
    $reportTitle = $reportTitles[$reportType];
    $reportIcon = ['management' => 'fa-chart-column', 'verification' => 'fa-clipboard-check', 'reviews' => 'fa-star'][$reportType];
    $chartMax = max(1, $municipalities->max('tourist_spots_count'));
@endphp
<style>
    .reports-page { --tourism-teal: #0f766e; --tourism-green: #3f7d42; --tourism-ink: #173f43; }
    .reports-page .card { border: 1px solid #dce9e5; border-radius: 10px; box-shadow: 0 8px 24px rgba(23, 63, 67, .06); }
    .reports-page .btn-tourism { background: var(--tourism-teal); border-color: var(--tourism-teal); color: #fff; }
    .reports-page .btn-tourism:hover { background: #115e59; border-color: #115e59; color: #fff; }
    .report-stat { border-top: 3px solid var(--tourism-teal); }
    .report-stat .stat-icon { color: var(--tourism-teal); font-size: 1.15rem; }
    .official-report { color: #1f2937; background: #fff; }
    .official-report-header { border-bottom: 3px solid #0f766e; padding: 1.5rem; text-align: center; }
    .report-mark { color: #0f766e; font-size: 2rem; }
    .official-report-header h1, .official-report-header h2, .official-report-header p { margin: 0; }
    .official-report-header h1 { color: #173f43; font-size: 1.35rem; letter-spacing: .04em; text-transform: uppercase; }
    .official-report-header h2 { font-size: 1.1rem; margin-top: .45rem; text-transform: uppercase; }
    .report-period { margin-top: .8rem !important; font-weight: 600; }
    .report-section-title { border-bottom: 2px solid #0f766e; color: #0f766e; font-size: .95rem; font-weight: 800; letter-spacing: .08em; margin: 1.25rem 1.5rem .75rem; padding-bottom: .35rem; text-transform: uppercase; }
    .report-summary { margin: 0 1.5rem; width: calc(100% - 3rem); }
    .report-summary td { border: 0; padding: .28rem 0; }
    .report-summary td:last-child { font-weight: 700; text-align: right; }
    .report-detail-table { margin: 0 1.5rem; width: calc(100% - 3rem); }
    .report-detail-table th, .report-detail-table td { border: 1px solid #9ca3af; padding: .5rem .6rem; vertical-align: top; }
    .report-detail-table th { background: #e6f5f1; color: #173f43; font-size: .78rem; text-transform: uppercase; }
    .municipality-divider td { background: #eef8f5; color: #0f766e; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
    .report-chart { margin: 0 1.5rem 1rem; }
    .chart-row { align-items: center; display: flex; gap: .6rem; margin: .35rem 0; }
    .chart-label { flex: 0 0 8rem; font-size: .78rem; }
    .chart-track { background: #e5efec; flex: 1; height: .55rem; }
    .chart-fill { background: linear-gradient(90deg, #0f766e, #3f7d42); height: 100%; }
    .report-signoff { display: flex; gap: 3rem; margin: 2rem 1.5rem 1.5rem; page-break-inside: avoid; }
    .report-signoff div { flex: 1; }
    .report-signoff p { margin: .55rem 0; }
    .report-footer { border-top: 1px solid #9ca3af; color: #4b5563; font-size: .75rem; margin: 1.5rem; padding-top: .5rem; text-align: center; }
    .report-preview-empty { border: 1px dashed #9bd2c7; border-radius: 10px; color: #55736e; padding: 4rem 1rem; text-align: center; }
    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        .sidebar, .report-controls, .main-col > header, .main-col > .alert { display: none !important; }
        .main-col, .main-content { margin-left: 0 !important; padding: 0 !important; }
        body { background: #fff !important; }
        .official-report { box-shadow: none !important; margin: 0 !important; }
        .report-footer { bottom: 0; left: 0; margin: 0; position: fixed; right: 0; }
        .report-page-number::after { content: 'Page ' counter(page) ' of ' counter(pages); }
        .municipality-divider { page-break-after: avoid; }
        tr { page-break-inside: avoid; }
    }
</style>

<div class="reports-page">
    <div class="report-controls card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('super-admin.reports') }}" class="row g-3 align-items-end" id="reportForm">
                <input type="hidden" name="generated" value="1">
                <div class="col-12 col-md-3"><label for="reportType" class="form-label">Report Type</label><select name="report_type" id="reportType" class="form-select"><option value="management" @selected($reportType === 'management')>Management Report</option><option value="verification" @selected($reportType === 'verification')>Verification Report</option><option value="reviews" @selected($reportType === 'reviews')>Reviews Report</option></select></div>
                <div class="col-12 col-md-3"><label for="municipalityId" class="form-label">Municipality</label><select name="municipality_id" id="municipalityId" class="form-select"><option value="0" @selected($municipalityId === 0)>All Municipalities</option>@foreach($municipalities as $municipality)<option value="{{ $municipality->id }}" @selected($municipalityId === $municipality->id)>{{ $municipality->name }}</option>@endforeach</select></div>
                <div class="col-12 col-md-2"><label for="reportStatus" class="form-label">Status</label><select name="status" id="reportStatus" class="form-select"><option value="all" @selected($status === 'all')>All Statuses</option><option value="pending" @selected($status === 'pending')>Pending</option><option value="approved" @selected($status === 'approved')>Verified/Approved</option></select></div>
                <div class="col-6 col-md-1"><label for="dateFrom" class="form-label">From</label><input type="date" name="date_from" id="dateFrom" value="{{ $dateFrom }}" class="form-control"></div>
                <div class="col-6 col-md-1"><label for="dateTo" class="form-label">To</label><input type="date" name="date_to" id="dateTo" value="{{ $dateTo }}" class="form-control"></div>
                <div class="col-12 col-md-2"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="include_chart" value="1" id="includeChart" @checked(request()->boolean('include_chart'))><label class="form-check-label" for="includeChart">Include summary chart</label></div><button type="submit" class="btn btn-tourism w-100" id="generateReport"><i class="fas fa-file-lines me-1"></i> Generate Report</button></div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4 report-controls">
        <div class="col-6 col-lg-3"><div class="card report-stat h-100"><div class="card-body"><div class="stat-icon"><i class="fas fa-location-dot"></i></div><div class="text-muted small text-uppercase">Tourist Spots</div><div class="fs-2 fw-bold text-teal">{{ $totalSpots }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card report-stat h-100"><div class="card-body"><div class="stat-icon"><i class="fas fa-map"></i></div><div class="text-muted small text-uppercase">Municipalities</div><div class="fs-2 fw-bold">{{ $totalMunicipalities }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card report-stat h-100"><div class="card-body"><div class="stat-icon"><i class="fas fa-user-shield"></i></div><div class="text-muted small text-uppercase">Municipal Admins</div><div class="fs-2 fw-bold">{{ $totalAdmins }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card report-stat h-100"><div class="card-body"><div class="stat-icon"><i class="fas fa-star"></i></div><div class="text-muted small text-uppercase">Reviews</div>@if($totalReviews > 0)<div class="fs-2 fw-bold">{{ $totalReviews }}</div>@else<span class="badge text-bg-light border mt-2">Coming Soon</span>@endif</div></div></div>
    </div>

    @if(!$hasGeneratedReport)
        <div class="report-preview-empty report-controls"><i class="fas fa-file-circle-plus fa-2x mb-3" style="color: var(--tourism-teal);"></i><h5>Choose filters, then generate a report preview</h5><p class="mb-0">The official report will appear here after generation.</p></div>
    @else
        <div class="report-controls d-flex justify-content-end mb-3"><button type="button" class="btn btn-tourism" onclick="printReport()"><i class="fas fa-print me-1"></i> Print / Export PDF</button></div>
        <div id="official-report" class="card official-report">
            <header class="official-report-header"><div class="report-mark"><i class="fas {{ $reportIcon }}"></i></div><p>REPUBLIC OF THE PHILIPPINES</p><p>PROVINCE OF PANGASINAN</p><p>TOURISM OFFICE / TOURISM SYSTEM</p><h1>{{ $reportTitle }}</h1><h2>2nd District of Pangasinan</h2><p class="report-period">Report Period: {{ $reportPeriod }}</p><p>Generated: {{ now()->format('F j, Y') }}</p></header>

            @if($reportType === 'management')
                <div class="report-section-title">Summary</div><table class="report-summary"><tbody><tr><td>Total Tourist Spots</td><td>{{ $totalSpots }}</td></tr><tr><td>Verified</td><td>{{ $verifiedSpots }}</td></tr><tr><td>Pending Verification</td><td>{{ $pendingSpots }}</td></tr><tr><td>Active</td><td>{{ $activeSpots }}</td></tr><tr><td>Municipalities with 0 spots</td><td>{{ $emptyMunicipalities }}</td></tr></tbody></table>
                @if(request()->boolean('include_chart'))<div class="report-section-title">Spots by Municipality</div><div class="report-chart">@foreach($municipalities as $municipality)<div class="chart-row"><span class="chart-label">{{ $municipality->name }}</span><span class="chart-track"><span class="chart-fill d-block" style="width: {{ ($municipality->tourist_spots_count / $chartMax) * 100 }}%"></span></span><strong>{{ $municipality->tourist_spots_count }}</strong></div>@endforeach</div>@endif
                <div class="report-section-title">Detailed Report</div><table class="report-detail-table"><thead><tr><th>No.</th><th>Municipality / Tourist Spot</th><th>Status</th><th>Date Added</th><th>Date Verified</th></tr></thead><tbody>@php $reportNumber = 0; @endphp @forelse($touristSpots as $municipalityName => $spots)<tr class="municipality-divider"><td colspan="5">{{ $municipalityName }} ({{ $spots->count() }} spots)</td></tr>@foreach($spots as $spot)@php $reportNumber++; @endphp<tr><td>{{ $reportNumber }}</td><td><strong>{{ $spot->name }}</strong><br><small>{{ $spot->address ?: 'No address provided' }}</small></td><td>{{ $hasVerificationStatus ? ucfirst($spot->verification_status ?? 'pending') : (in_array($spot->status, ['open', 'active']) ? 'Active' : ucfirst($spot->status ?? 'Unknown')) }}</td><td>{{ optional($spot->created_at)->format('M d, Y') }}</td><td>{{ $spot->verification_status === 'approved' ? optional($spot->updated_at)->format('M d, Y') : 'Pending' }}</td></tr>@endforeach @empty<tr><td colspan="5" class="text-center py-4">No tourist spots have been added</td></tr>@endforelse</tbody></table>
            @elseif($reportType === 'verification')
                <div class="report-section-title">Verification Summary</div><table class="report-summary"><tbody><tr><td>Pending Verification</td><td>{{ $pendingSpots }}</td></tr><tr><td>Verified</td><td>{{ $verifiedSpots }}</td></tr><tr><td>Municipalities Covered</td><td>{{ $totalMunicipalities }}</td></tr></tbody></table>
                <div class="report-section-title">Verification Details</div><table class="report-detail-table"><thead><tr><th>No.</th><th>Municipality / Tourist Spot</th><th>Submitted By</th><th>Date Added</th><th>Status</th></tr></thead><tbody>@php $verificationNumber = 0; @endphp @forelse($verificationSpots as $spot)@php $verificationNumber++; $submitter = $spot->creator?->name ?? $spot->municipality?->admins?->first()?->name ?? 'Unassigned municipality admin'; @endphp<tr><td>{{ $verificationNumber }}</td><td><strong>{{ $spot->municipality?->name ?? 'Unknown Municipality' }}</strong><br>{{ $spot->name }}</td><td>{{ $submitter }}</td><td>{{ optional($spot->created_at)->format('M d, Y') }}</td><td>{{ $hasVerificationStatus && $spot->verification_status === 'approved' ? 'Verified' : 'Pending' }}</td></tr>@empty<tr><td colspan="5" class="text-center py-4">No verification records found</td></tr>@endforelse</tbody></table>
            @else
                <div class="report-section-title">Review Summary</div><table class="report-summary"><tbody><tr><td>Total Reviews</td><td>{{ $reportReviews->count() }}</td></tr><tr><td>Approved Reviews</td><td>{{ $reportReviews->where('status', 'approved')->count() }}</td></tr><tr><td>Pending Reviews</td><td>{{ $reportReviews->where('status', 'pending')->count() }}</td></tr></tbody></table><div class="report-section-title">Review Details</div><table class="report-detail-table"><thead><tr><th>No.</th><th>Tourist Spot</th><th>Municipality</th><th>Reviewer</th><th>Date</th><th>Status</th></tr></thead><tbody>@forelse($reportReviews as $review)<tr><td>{{ $loop->iteration }}</td><td>{{ $review->touristSpot?->name ?? 'Unknown Spot' }}</td><td>{{ $review->touristSpot?->municipality?->name ?? 'Unknown Municipality' }}</td><td>{{ $review->user_name ?: 'Visitor' }}</td><td>{{ optional($review->created_at)->format('M d, Y') }}</td><td>{{ ucfirst($review->status ?? 'pending') }}</td></tr>@empty<tr><td colspan="6" class="text-center py-4">No reviews found</td></tr>@endforelse</tbody></table>
            @endif

            <div class="report-signoff"><div><p>Prepared by: ____________________________________</p><p>Provincial Tourism Officer</p><p>Date: __________________________________________</p></div><div><p>Approved by: ____________________________________</p><p>Provincial Tourism Officer / Approving Authority</p><p>Date: __________________________________________</p></div></div>
            <footer class="report-footer">{{ $reportTitle }} | 2nd District of Pangasinan | <span class="report-page-number"></span></footer>
        </div>
    @endif
</div>

<script>
    document.getElementById('reportForm').addEventListener('submit', function () {
        const button = document.getElementById('generateReport');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Generating...';
    });
    function printReport() { window.print(); }
</script>
@endsection
