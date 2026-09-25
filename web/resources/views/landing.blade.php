<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Discover verified tourist spots, municipalities, and directions across Pangasinan's 2nd District.">
    <title>Pangasinan 2nd District | Discover More</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --teal: #0d3b3e; --teal-mid: #1a5c52; --orange: #f2622e; --ink: #153b3b; --muted: #667b78; --cream: #f7f8f3; --line: #e1ebe6; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; color: var(--ink); background: var(--cream); font-family: 'DM Sans', sans-serif; }
        a { color: inherit; text-decoration: none; }
        .wrap { width: min(1160px, calc(100% - 40px)); margin: 0 auto; }
        .hero { min-height: 720px; color: white; position: relative; isolation: isolate; background: linear-gradient(115deg, rgba(7, 38, 42, .95) 2%, rgba(13, 59, 62, .74) 57%, rgba(20, 85, 71, .56)), url('https://www.discoverthephilippines.com/wp-content/uploads/2021/08/article-cover-photo-pangasinan-guide-810x421.jpg') center/cover; }
        .hero::after { content: ''; position: absolute; inset: auto 0 0; height: 150px; background: linear-gradient(transparent, var(--cream)); z-index: -1; }
        .nav { height: 86px; display: flex; justify-content: space-between; align-items: center; gap: 24px; }
        .brand { display: inline-flex; align-items: center; gap: 12px; font-family: Outfit, sans-serif; font-weight: 700; font-size: 1.02rem; letter-spacing: .2px; }
        .brand i { color: #69d3b6; font-size: 1.5rem; }
        .nav-links { display: flex; gap: 28px; color: rgba(255,255,255,.82); font-size: .9rem; }
        .nav-links a:hover { color: white; }
        .login-btn, .hero-primary, .download-btn { background: var(--orange); color: white; border: 0; font-weight: 700; border-radius: 7px; padding: 13px 23px; box-shadow: 0 10px 25px rgba(242,98,46,.2); transition: transform .2s, box-shadow .2s, background .2s; }
        .login-btn:hover, .hero-primary:hover, .download-btn:hover { background: #dc5122; color: white; transform: translateY(-2px); box-shadow: 0 13px 28px rgba(242,98,46,.34); }
        .hero-content { padding: 116px 0 180px; max-width: 720px; }
        .eyebrow { color: #8de0c8; font-weight: 700; font-size: .76rem; letter-spacing: 2px; text-transform: uppercase; }
        h1, h2, h3 { font-family: Outfit, sans-serif; }
        h1 { font-size: clamp(3rem, 6vw, 5.3rem); line-height: .99; letter-spacing: -.05em; margin: 18px 0 24px; max-width: 700px; }
        .hero-copy { max-width: 590px; color: rgba(255,255,255,.8); line-height: 1.7; font-size: 1.05rem; }
        .hero-actions { display: flex; align-items: center; gap: 13px; margin-top: 35px; }
        .hero-secondary { border: 1px solid rgba(255,255,255,.55); color: white; border-radius: 7px; padding: 12px 22px; font-weight: 700; background: rgba(255,255,255,.06); }
        .hero-secondary:hover { background: rgba(255,255,255,.16); }
        .section { padding: 94px 0; }
        .section-heading { display: flex; justify-content: space-between; gap: 25px; align-items: end; margin-bottom: 38px; }
        .section-heading h2 { font-size: clamp(2rem, 4vw, 3.2rem); line-height: 1; margin: 8px 0 0; letter-spacing: -.04em; }
        .section-heading p { color: var(--muted); max-width: 400px; line-height: 1.6; margin: 0; }
        .overview { background: var(--cream); }
        .feature-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .feature { background: white; border: 1px solid var(--line); padding: 27px 23px; min-height: 190px; border-radius: 8px; }
        .feature-icon { width: 43px; height: 43px; display: grid; place-items: center; border-radius: 50%; background: #e1f3eb; color: var(--teal-mid); font-size: 1.1rem; margin-bottom: 21px; }
        .feature h3 { font-size: 1.08rem; margin: 0 0 8px; }
        .feature p { color: var(--muted); font-size: .88rem; line-height: 1.55; margin: 0; }
        .stats { display: flex; gap: 42px; margin-top: 43px; padding-top: 25px; border-top: 1px solid var(--line); }
        .stat strong { color: var(--orange); font: 800 2rem Outfit, sans-serif; display: block; }
        .stat span { color: var(--muted); font-size: .8rem; }
        .municipalities { background: white; }
        .municipality-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
        .municipality-card { min-height: 320px; position: relative; overflow: hidden; border-radius: 8px; color: white; background: linear-gradient(135deg, var(--teal), var(--teal-mid)); }
        .municipality-card img { width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0; transition: transform .5s; }
        .municipality-card:hover img { transform: scale(1.06); }
        .municipality-card::after { content: ''; position: absolute; inset: 25% 0 0; background: linear-gradient(transparent, rgba(4, 28, 31, .95)); }
        .municipality-info { position: absolute; z-index: 1; bottom: 22px; left: 21px; right: 18px; }
        .municipality-info small { color: #a9d9c6; font-size: .75rem; }
        .municipality-info h3 { margin: 6px 0 0; font-size: 1.45rem; }
        .app-section { background: #edf3ee; overflow: hidden; }
        .app-layout { display: grid; grid-template-columns: .85fr 1.15fr; align-items: center; gap: 100px; }
        .phone-wrap { display: flex; justify-content: center; }
        .phone { width: 240px; height: 470px; padding: 11px; border-radius: 32px; background: #142d2c; box-shadow: 20px 22px 0 rgba(13,59,62,.11), 0 25px 45px rgba(20,58,54,.2); transform: rotate(-5deg); }
        .phone-screen { height: 100%; border-radius: 23px; overflow: hidden; background: #f4f8f3; position: relative; }
        .phone-map { height: 62%; background: linear-gradient(145deg, rgba(9,72,71,.65), rgba(65,147,109,.35)), url('https://www.discoverthephilippines.com/wp-content/uploads/2021/08/article-cover-photo-pangasinan-guide-810x421.jpg') center/cover; position: relative; }
        .phone-map::before { content: 'Pangasinan'; position: absolute; top: 16px; left: 17px; color: white; font: 700 1rem Outfit, sans-serif; }
        .map-pin { position: absolute; color: var(--orange); font-size: 1.55rem; filter: drop-shadow(0 2px 2px rgba(0,0,0,.2)); }
        .pin-one { top: 42%; left: 28%; }.pin-two { top: 62%; right: 24%; }.pin-three { top: 26%; right: 31%; }
        .phone-list { background: white; border-radius: 18px 18px 0 0; margin-top: -16px; position: relative; padding: 18px 14px; }
        .phone-list strong { display: block; font: 700 .95rem Outfit, sans-serif; margin-bottom: 13px; }.spot-line { height: 38px; display: flex; gap: 8px; align-items: center; color: var(--muted); font-size: .72rem; border-top: 1px solid #eef1ed; }.spot-dot { width: 27px; height: 27px; border-radius: 7px; background: #d5e9dc; }
        .app-copy h2 { font-size: clamp(2.2rem, 4vw, 3.6rem); line-height: 1; letter-spacing: -.04em; margin: 12px 0 19px; }
        .app-copy p { color: var(--muted); line-height: 1.7; max-width: 500px; }
        .download-btn { display: inline-flex; align-items: center; gap: 10px; margin-top: 17px; font-size: 1rem; padding: 16px 24px; }
        .download-meta, .download-note { color: #6c817b; font-size: .76rem; margin-top: 12px; }.download-note { max-width: 450px; line-height: 1.5; }
        .steps { background: white; }.step-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 42px; }.step { display: flex; gap: 16px; }.step-number { color: var(--orange); font: 800 1.7rem Outfit, sans-serif; }.step h3 { margin: 0 0 6px; font-size: 1.05rem; }.step p { color: var(--muted); margin: 0; font-size: .88rem; line-height: 1.5; }
        footer { color: rgba(255,255,255,.73); background: linear-gradient(120deg, var(--teal), #1a6a55); padding: 55px 0 28px; }.footer-main { display: flex; justify-content: space-between; gap: 30px; }.footer-brand { color: white; font: 700 1.05rem Outfit, sans-serif; }.footer-main p { font-size: .85rem; max-width: 315px; line-height: 1.6; }.footer-links { display: flex; gap: 16px; font-size: 1.05rem; }.footer-links a:hover { color: white; }.copyright { border-top: 1px solid rgba(255,255,255,.17); margin-top: 35px; padding-top: 20px; font-size: .77rem; }
        @media (max-width: 800px) { .nav-links { display: none; }.hero { min-height: 660px; }.hero-content { padding-top: 80px; }.feature-grid, .municipality-grid { grid-template-columns: repeat(2, 1fr); }.app-layout { grid-template-columns: 1fr; gap: 55px; }.phone { transform: rotate(-3deg); }.section { padding: 70px 0; } }
        @media (max-width: 520px) { .wrap { width: min(100% - 28px, 1160px); }.nav { height: 72px; }.brand { font-size: .9rem; }.login-btn { padding: 10px 15px; font-size: .8rem; }.hero-content { padding: 70px 0 145px; }.hero-actions { flex-wrap: wrap; }.section-heading { display: block; }.section-heading p { margin-top: 17px; }.feature-grid, .municipality-grid, .step-grid { grid-template-columns: 1fr; }.municipality-card { min-height: 270px; }.stats { gap: 20px; justify-content: space-between; }.stat strong { font-size: 1.55rem; }.footer-main { display: block; }.footer-links { margin-top: 24px; } }
    </style>
</head>
<body>
    <header class="hero" id="home">
        <nav class="nav wrap" aria-label="Main navigation">
            <a class="brand" href="#home"><i class="fas fa-location-dot"></i><span>Pangasinan 2nd District</span></a>
            <div class="nav-links"><a href="#overview">About</a><a href="#municipalities">Municipalities</a><a href="#app">Download App</a></div>
            <a class="login-btn" href="{{ route('login.form') }}">LOGIN</a>
        </nav>
        <div class="hero-content wrap">
            <div class="eyebrow">Your local guide to meaningful journeys</div>
            <h1>Discover the hidden gems of Pangasinan's 2nd District.</h1>
            <p class="hero-copy">A navigation and information platform for the places, stories, and people that make our eight municipalities worth discovering.</p>
            <div class="hero-actions"><a class="hero-primary" href="#municipalities">Explore Spots <i class="fas fa-arrow-right ms-1"></i></a><a class="hero-secondary" href="#app">Download App</a></div>
        </div>
    </header>

    <main>
        <section class="section overview" id="overview"><div class="wrap">
            <div class="section-heading"><div><div class="eyebrow" style="color:var(--teal-mid)">Built for better days out</div><h2>Everything you need<br>to go further.</h2></div><p>Plan with confidence, find places worth your time, and see the district through the eyes of the people who know it best.</p></div>
            <div class="feature-grid">
                <article class="feature"><div class="feature-icon"><i class="fas fa-location-arrow"></i></div><h3>Interactive navigation</h3><p>Find spots, plan routes, and get directions without the guesswork.</p></article>
                <article class="feature"><div class="feature-icon"><i class="fas fa-building-columns"></i></div><h3>Municipality directory</h3><p>Explore the character and attractions of all eight municipalities.</p></article>
                <article class="feature"><div class="feature-icon"><i class="fas fa-circle-check"></i></div><h3>Verified places</h3><p>Discover tourist spots reviewed and verified by local administrators.</p></article>
                <article class="feature"><div class="feature-icon"><i class="fas fa-star"></i></div><h3>Real reviews</h3><p>Make better choices with ratings and stories from fellow travelers.</p></article>
            </div>
            <div class="stats"><div class="stat"><strong>{{ $totalSpots }}</strong><span>Verified tourist spots</span></div><div class="stat"><strong>{{ $totalMunicipalities }}</strong><span>Municipalities</span></div><div class="stat"><strong>{{ $totalReviews }}</strong><span>Tourist reviews</span></div></div>
        </div></section>

        <section class="section municipalities" id="municipalities"><div class="wrap">
            <div class="section-heading"><div><div class="eyebrow" style="color:var(--teal-mid)">Start somewhere beautiful</div><h2>Featured municipalities</h2></div><p>Four ways into the district, each with a different rhythm, view, and story.</p></div>
            <div class="municipality-grid">
                @forelse($featuredMunicipalities as $municipality)
                    <a class="municipality-card" href="{{ route('login.form') }}" aria-label="Explore {{ $municipality->name }}">
                        @if($municipality->image_url)<img src="{{ preg_match('#^https?://#i', $municipality->image_url) ? $municipality->image_url : url($municipality->image_url) }}" alt="{{ $municipality->name }}" loading="lazy">@endif
                        <div class="municipality-info"><small>Municipality in Pangasinan</small><h3>{{ $municipality->name }}</h3></div>
                    </a>
                @empty
                    <div class="feature" style="grid-column:1/-1">Municipality highlights are being prepared.</div>
                @endforelse
            </div>
        </div></section>

        <section class="section app-section" id="app"><div class="wrap app-layout">
            <div class="phone-wrap"><div class="phone"><div class="phone-screen"><div class="phone-map"><i class="fas fa-location-dot map-pin pin-one"></i><i class="fas fa-location-dot map-pin pin-two"></i><i class="fas fa-location-dot map-pin pin-three"></i></div><div class="phone-list"><strong>Explore nearby</strong><div class="spot-line"><span class="spot-dot"></span> Lingayen Beach</div><div class="spot-line"><span class="spot-dot"></span> Local favorites</div><div class="spot-line"><span class="spot-dot"></span> Plan your route</div></div></div></div></div>
            <div class="app-copy"><div class="eyebrow" style="color:var(--teal-mid)">Your next stop, offline</div><h2>Take Pangasinan with you.</h2><p>Keep the district close wherever the road takes you. Browse spot details, save directions, and make plans even when the signal fades.</p><a class="download-btn" href="/downloads/app.apk" download><i class="fab fa-android"></i> Download for Android</a><div class="download-meta">v1.0.0 · 25 MB APK</div><div class="download-note">For Android devices only. You may need to enable “Install from unknown sources” in your phone settings.</div></div>
        </div></section>

        <section class="section steps"><div class="wrap"><div class="section-heading"><div><div class="eyebrow" style="color:var(--teal-mid)">A simpler way to wander</div><h2>From curious to there.</h2></div></div><div class="step-grid"><div class="step"><div class="step-number">01</div><div><h3>Search and browse</h3><p>Find a spot or municipality that fits the day you want.</p></div></div><div class="step"><div class="step-number">02</div><div><h3>See the details</h3><p>Check photos, descriptions, ratings, and visitor reviews.</p></div></div><div class="step"><div class="step-number">03</div><div><h3>Get directions</h3><p>Choose your route and let the adventure begin.</p></div></div></div></div></section>
    </main>

    <footer><div class="wrap"><div class="footer-main"><div><div class="footer-brand"><i class="fas fa-location-dot me-2"></i>Pangasinan 2nd District</div><p>Helping visitors and locals discover the places that make our home special.</p></div><div><div class="footer-links"><a href="#home" aria-label="Home"><i class="fas fa-house"></i></a><a href="#overview" aria-label="About"><i class="fas fa-circle-info"></i></a><a href="mailto:tourism@pangasinan2nddistrict.gov.ph" aria-label="Email"><i class="fas fa-envelope"></i></a></div></div></div><div class="copyright">© {{ date('Y') }} 2nd District of Pangasinan Tourism Office · <a href="{{ route('login.form') }}">Admin portal</a></div></div></footer>
</body>
</html>