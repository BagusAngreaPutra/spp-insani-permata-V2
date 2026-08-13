<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal pembayaran SPP SD IT Permata Insani Islamic School Kota Jambi.">
    <title>Permata Insani &mdash; Sistem Pembayaran SPP</title>
    @include('layouts.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --green-950: #0b2f22;
            --green-900: #103e2d;
            --green-800: #15533b;
            --green-700: #1d6b4c;
            --green-600: #25845d;
            --green-500: #35a975;
            --green-100: #dff3e8;
            --green-50: #f1f9f5;
            --lime: #d9f475;
            --text: #101828;
            --muted: #667085;
            --quiet: #98a2b3;
            --line: #e4e7ec;
            --soft: #f8faf9;
            --white: #fff;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            overflow-x: hidden;
            color: var(--text);
            background: var(--white);
            font-family: "Inter", system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }
        button, a { -webkit-tap-highlight-color: transparent; }
        svg { display: block; }
        .container { width: min(1180px, calc(100% - 48px)); margin-inline: auto; }

        .topbar { color: #d7e7df; background: var(--green-950); font-size: 11px; }
        .topbar-inner { min-height: 34px; display: flex; align-items: center; justify-content: space-between; gap: 18px; }
        .topbar p { margin: 0; }
        .topbar-meta { display: flex; align-items: center; gap: 18px; }
        .topbar-meta span { display: flex; align-items: center; gap: 6px; }
        .topbar svg { width: 13px; height: 13px; color: #75c99d; }

        .header {
            position: sticky;
            z-index: 20;
            top: 0;
            background: rgba(255, 255, 255, .95);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(15px);
        }
        .nav { min-height: 78px; display: flex; align-items: center; justify-content: space-between; gap: 30px; }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .brand-logo { position: relative; }
        .brand-logo::after { content: ""; position: absolute; inset: -4px; z-index: -1; background: var(--green-100); border-radius: 50%; }
        .brand img { width: 43px; height: 43px; display: block; object-fit: cover; border-radius: 50%; }
        .brand-copy strong, .brand-copy span { display: block; }
        .brand-copy strong { color: var(--green-950); font-size: 13px; letter-spacing: -.02em; }
        .brand-copy span { margin-top: 3px; color: var(--muted); font-size: 9px; letter-spacing: .04em; text-transform: uppercase; }
        .nav-menu { display: flex; align-items: center; gap: 30px; margin-left: auto; }
        .nav-menu a { color: #475467; font-size: 12px; font-weight: 600; text-decoration: none; }
        .nav-menu a:hover { color: var(--green-700); }
        .nav-auth { display: flex; align-items: center; gap: 8px; }
        .nav-auth-link {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 14px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 800;
            text-decoration: none;
            transition: transform .18s, background .18s, box-shadow .18s;
        }
        .nav-auth-link:hover { transform: translateY(-1px); }
        .nav-auth-link.student { color: #1d4ed8; background: #eff6ff; border: 1px solid #bfdbfe; }
        .nav-auth-link.student:hover { background: #dbeafe; box-shadow: 0 6px 14px rgba(37, 99, 235, .12); }
        .nav-auth-link.teacher { color: #fff; background: var(--green-700); border: 1px solid var(--green-700); box-shadow: 0 5px 12px rgba(29, 107, 76, .16); }
        .nav-auth-link.teacher:hover { background: var(--green-800); box-shadow: 0 7px 16px rgba(29, 107, 76, .2); }
        .nav-auth-link svg { width: 15px; height: 15px; }

        .hero { position: relative; overflow: hidden; padding: 88px 0 104px; background: #fbfdfc; }
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: .36;
            background-image: radial-gradient(#93b4a5 1px, transparent 1px);
            background-size: 24px 24px;
            mask-image: linear-gradient(to right, #000, transparent 55%);
        }
        .hero::after { content: ""; position: absolute; width: 520px; height: 520px; right: -220px; top: -250px; background: #e3f5eb; border-radius: 50%; filter: blur(2px); }
        .hero-grid { position: relative; z-index: 2; display: grid; grid-template-columns: .92fr 1.08fr; gap: 70px; align-items: center; }
        .hero-grid > *, .preview, .metric, .history-row { min-width: 0; }
        .hero-kicker { display: flex; align-items: center; gap: 9px; margin-bottom: 22px; color: var(--green-700); font-size: 11px; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; }
        .hero-kicker span { width: 27px; height: 1px; background: var(--green-500); }
        h1 { max-width: 620px; margin: 0; color: var(--green-950); font-size: clamp(43px, 5.5vw, 68px); line-height: 1.03; letter-spacing: -.058em; }
        h1 em { position: relative; color: var(--green-600); font-style: normal; white-space: nowrap; }
        h1 em::after { content: ""; position: absolute; height: 8px; right: 0; bottom: 2px; left: 0; z-index: -1; background: var(--lime); border-radius: 99px; transform: rotate(-1.5deg); }
        .hero-copy > p { max-width: 590px; margin: 25px 0 26px; color: var(--muted); font-size: 15px; line-height: 1.8; }
        .hero-access {
            max-width: 620px;
            padding: 17px;
            background: #fff;
            border: 1px solid #dfe7e3;
            border-radius: 14px;
            box-shadow: 0 16px 38px rgba(11, 47, 34, .1);
        }
        .access-heading { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 13px; }
        .access-heading strong { color: var(--green-950); font-size: 12px; }
        .access-heading span { color: var(--muted); font-size: 9px; }
        .access-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .access-button {
            position: relative;
            min-height: 100px;
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) 27px;
            align-items: center;
            gap: 12px;
            overflow: hidden;
            padding: 15px;
            color: #fff;
            border-radius: 10px;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(16, 24, 40, .1);
            transition: transform .2s, box-shadow .2s;
        }
        .access-button::after { content: ""; position: absolute; width: 90px; height: 90px; right: -47px; top: -48px; background: rgba(255,255,255,.11); border-radius: 50%; }
        .access-button:hover { transform: translateY(-3px); box-shadow: 0 14px 25px rgba(16, 24, 40, .16); }
        .access-button.teacher { background: linear-gradient(135deg, #1d6b4c, #25845d); }
        .access-button.student { background: linear-gradient(135deg, #2563eb, #3b82f6); }
        .access-icon { width: 42px; height: 42px; display: grid; place-items: center; color: inherit; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2); border-radius: 9px; }
        .access-icon svg { width: 21px; height: 21px; }
        .access-copy { min-width: 0; }
        .access-copy small, .access-copy strong, .access-copy span { display: block; }
        .access-copy small { margin-bottom: 4px; color: rgba(255,255,255,.75); font-size: 8px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .access-copy strong { font-size: 16px; letter-spacing: -.025em; }
        .access-copy span { margin-top: 5px; overflow: hidden; color: rgba(255,255,255,.82); font-size: 8px; line-height: 1.45; text-overflow: ellipsis; white-space: nowrap; }
        .access-arrow { width: 27px; height: 27px; display: grid; place-items: center; border: 1px solid rgba(255,255,255,.24); border-radius: 50%; }
        .access-arrow svg { width: 13px; height: 13px; }
        .verified { display: flex; flex-wrap: wrap; gap: 18px; margin-top: 20px; }
        .verified span { display: flex; align-items: center; gap: 7px; color: #475467; font-size: 10px; font-weight: 600; }
        .verified svg { width: 15px; height: 15px; color: var(--green-600); }

        .app-stage { position: relative; min-height: 480px; }
        .app-window {
            position: absolute;
            inset: 20px 0 0 20px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #dce4e0;
            border-radius: 13px;
            box-shadow: 0 32px 70px rgba(11, 47, 34, .16);
            transform: rotate(1.2deg);
        }
        .window-bar { height: 48px; display: flex; align-items: center; gap: 6px; padding: 0 17px; background: #fafbfb; border-bottom: 1px solid var(--line); }
        .window-dot { width: 7px; height: 7px; background: #d0d5dd; border-radius: 50%; }
        .window-dot:first-child { background: #71c69a; }
        .window-title { margin-left: 9px; color: #667085; font-size: 9px; font-weight: 600; }
        .app-body { display: grid; grid-template-columns: 116px 1fr; min-height: 410px; }
        .mini-sidebar { padding: 17px 11px; background: #fbfcfc; border-right: 1px solid var(--line); }
        .mini-brand { display: flex; align-items: center; gap: 7px; padding: 0 5px 17px; }
        .mini-brand img { width: 24px; height: 24px; object-fit: cover; border-radius: 50%; }
        .mini-brand strong { color: var(--green-900); font-size: 7px; line-height: 1.3; }
        .mini-label { margin: 7px 6px; color: #b0b8b4; font-size: 6px; text-transform: uppercase; }
        .mini-nav { display: grid; gap: 4px; }
        .mini-nav span { min-height: 29px; display: flex; align-items: center; gap: 7px; padding: 0 8px; color: #667085; border-radius: 5px; font-size: 7px; font-weight: 600; }
        .mini-nav span.active { color: var(--green-700); background: #eaf6f0; }
        .mini-nav svg { width: 11px; height: 11px; }
        .preview { padding: 23px 22px; }
        .preview-head { display: flex; justify-content: space-between; align-items: start; gap: 14px; }
        .preview-head small { display: block; color: var(--green-600); font-size: 7px; font-weight: 700; }
        .preview-head h2 { margin: 4px 0 0; font-size: 20px; letter-spacing: -.04em; }
        .preview-chip { padding: 6px 8px; color: var(--green-700); background: var(--green-50); border: 1px solid #cbe8da; border-radius: 5px; font-size: 6px; font-weight: 700; }
        .metric-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 9px; margin: 22px 0 14px; }
        .metric { padding: 13px; border: 1px solid var(--line); border-radius: 7px; }
        .metric-icon { width: 25px; height: 25px; display: grid; place-items: center; margin-bottom: 13px; color: var(--green-600); background: var(--green-50); border: 1px solid #d9eee3; border-radius: 5px; }
        .metric-icon svg { width: 12px; height: 12px; }
        .metric span, .metric strong { display: block; }
        .metric span { color: var(--muted); font-size: 6px; }
        .metric strong { margin-top: 5px; font-size: 9px; }
        .history-card { border: 1px solid var(--line); border-radius: 7px; }
        .history-head { display: flex; justify-content: space-between; padding: 11px 13px; border-bottom: 1px solid var(--line); font-size: 8px; font-weight: 700; }
        .history-head span:last-child { color: var(--green-600); font-size: 6px; }
        .history-row { display: grid; grid-template-columns: 1.1fr .8fr .7fr; align-items: center; padding: 12px 13px; color: #475467; border-bottom: 1px solid #f0f2f1; font-size: 7px; }
        .history-row > * { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .history-row:last-child { border: 0; }
        .status { width: max-content; padding: 4px 6px; color: #067647; background: #ecfdf3; border: 1px solid #abefc6; border-radius: 4px; font-size: 6px; font-weight: 700; }
        .float-card { position: absolute; z-index: 3; display: flex; align-items: center; gap: 11px; padding: 12px 15px; background: #fff; border: 1px solid var(--line); border-radius: 9px; box-shadow: 0 15px 36px rgba(11, 47, 34, .14); }
        .float-card.one { right: -23px; top: 0; }
        .float-card.two { left: -15px; bottom: -6px; }
        .float-icon { width: 32px; height: 32px; display: grid; place-items: center; color: var(--green-700); background: var(--green-50); border-radius: 7px; }
        .float-icon svg { width: 15px; height: 15px; }
        .float-card span, .float-card strong { display: block; }
        .float-card span { color: var(--muted); font-size: 7px; text-transform: uppercase; letter-spacing: .07em; }
        .float-card strong { margin-top: 3px; color: var(--green-950); font-size: 10px; }

        .identity { position: relative; z-index: 3; margin-top: -25px; }
        .identity-card { display: grid; grid-template-columns: 1.35fr repeat(3, .72fr); color: #fff; background: var(--green-900); border-radius: 10px; box-shadow: 0 18px 35px rgba(11, 47, 34, .14); }
        .identity-intro, .identity-item { min-height: 126px; padding: 25px; }
        .identity-intro { display: flex; gap: 15px; align-items: center; }
        .identity-intro img { width: 56px; height: 56px; object-fit: cover; border: 3px solid rgba(255,255,255,.17); border-radius: 50%; }
        .identity-intro h2 { margin: 0; font-size: 15px; line-height: 1.4; }
        .identity-intro p { margin: 5px 0 0; color: #abc9ba; font-size: 9px; }
        .identity-item { display: flex; flex-direction: column; justify-content: center; border-left: 1px solid rgba(255,255,255,.12); }
        .identity-item span { color: #94b8a7; font-size: 8px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .identity-item strong { margin-top: 8px; color: #fff; font-size: 12px; line-height: 1.5; }

        .features { padding: 100px 0; }
        .section-head { display: flex; align-items: end; justify-content: space-between; gap: 30px; margin-bottom: 37px; }
        .section-tag { color: var(--green-600); font-size: 10px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .section-head h2 { max-width: 610px; margin: 10px 0 0; color: var(--green-950); font-size: clamp(31px, 4vw, 44px); line-height: 1.13; letter-spacing: -.045em; }
        .section-head > p { max-width: 410px; margin: 0; color: var(--muted); font-size: 12px; line-height: 1.75; }
        .bento { display: grid; grid-template-columns: 1.12fr .88fr; grid-template-rows: repeat(2, 225px); gap: 14px; }
        .bento-card { position: relative; overflow: hidden; padding: 27px; background: #fff; border: 1px solid var(--line); border-radius: 10px; }
        .bento-card.large { grid-row: 1 / 3; color: #fff; background: var(--green-950); border-color: var(--green-950); }
        .bento-card h3 { margin: 19px 0 8px; color: var(--green-950); font-size: 18px; letter-spacing: -.025em; }
        .bento-card p { max-width: 390px; margin: 0; color: var(--muted); font-size: 11px; line-height: 1.7; }
        .bento-card.large h3 { max-width: 400px; margin-top: 25px; color: #fff; font-size: 27px; }
        .bento-card.large p { color: #a9c2b6; }
        .bento-icon { width: 38px; height: 38px; display: grid; place-items: center; color: var(--green-700); background: var(--green-50); border: 1px solid #d9ece2; border-radius: 8px; }
        .bento-icon svg { width: 18px; height: 18px; }
        .bento-card.large .bento-icon { color: var(--lime); background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.13); }
        .flow { position: absolute; right: 27px; bottom: 28px; left: 27px; padding: 18px; background: #fff; border-radius: 8px; }
        .flow-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; color: var(--text); font-size: 9px; font-weight: 700; }
        .flow-head span { color: var(--green-600); font-size: 7px; }
        .flow-step { display: grid; grid-template-columns: 27px 1fr auto; gap: 10px; align-items: center; padding: 9px 0; color: #344054; border-top: 1px solid #f0f2f1; font-size: 7px; }
        .flow-step-icon { width: 27px; height: 27px; display: grid; place-items: center; color: var(--green-600); background: var(--green-50); border-radius: 5px; }
        .flow-step-icon svg { width: 12px; height: 12px; }
        .flow-step small { color: var(--quiet); }
        .flow-state { color: #067647; font-size: 6px; font-weight: 700; }
        .mini-bars { position: absolute; right: 27px; bottom: 27px; display: flex; align-items: end; gap: 6px; height: 67px; }
        .mini-bars i { width: 16px; background: var(--green-100); border-radius: 3px 3px 0 0; }
        .mini-bars i:nth-child(1) { height: 29px; }
        .mini-bars i:nth-child(2) { height: 49px; background: #a7dcc1; }
        .mini-bars i:nth-child(3) { height: 38px; }
        .mini-bars i:nth-child(4) { height: 64px; background: var(--green-600); }

        .source { padding: 25px 0; background: #fff; border-top: 1px solid var(--line); }
        .source-inner { display: flex; align-items: center; justify-content: space-between; gap: 20px; color: var(--muted); font-size: 9px; }
        .source a { color: var(--green-700); font-weight: 700; text-underline-offset: 3px; }
        footer { padding: 32px 0; color: #aac2b6; background: var(--green-950); font-size: 10px; }
        .footer-inner { display: flex; justify-content: space-between; gap: 20px; }

        @media (max-width: 980px) {
            .hero-grid { grid-template-columns: 1fr; }
            .hero-copy { max-width: 720px; }
            .app-stage { width: min(100%, 700px); margin: 0 auto; }
            .identity-card { grid-template-columns: 1.4fr repeat(3, 1fr); }
            .identity-intro { grid-column: 1 / -1; min-height: 105px; border-bottom: 1px solid rgba(255,255,255,.12); }
            .identity-item { min-height: 100px; }
            .identity-item:nth-child(2) { border-left: 0; }
            .section-head { align-items: start; flex-direction: column; }
        }
        @media (max-width: 740px) {
            .container { width: min(calc(100% - 30px), 1180px); }
            .topbar-meta span:last-child, .nav-menu { display: none; }
            .nav { min-height: 70px; }
            .hero { padding: 62px 0 85px; }
            .hero-grid { gap: 45px; }
            h1 { font-size: 45px; }
            .access-grid { grid-template-columns: 1fr; }
            .access-button { min-height: 92px; }
            .app-stage { min-height: 400px; }
            .app-window { inset: 12px 0 0; }
            .app-body { grid-template-columns: 1fr; min-height: 345px; }
            .mini-sidebar { display: none; }
            .float-card.one { right: -7px; }
            .float-card.two { left: -7px; }
            .identity-card { grid-template-columns: 1fr; }
            .identity-item { min-height: 90px; border-top: 1px solid rgba(255,255,255,.12); border-left: 0; }
            .features { padding: 75px 0; }
            .bento { grid-template-columns: 1fr; grid-template-rows: 430px repeat(2, 205px); }
            .bento-card.large { grid-row: auto; }
            .nav-menu { display: none; }
        }
        @media (max-width: 480px) {
            .nav { gap: 10px; }
            .brand-copy strong { font-size: 11px; }
            .brand-copy span { font-size: 8px; }
            .nav-auth { gap: 5px; }
            .nav-auth-link { min-height: 38px; gap: 5px; padding: 0 9px; font-size: 9px; }
            .nav-auth-link svg { width: 13px; height: 13px; }
            h1 { font-size: 37px; }
            .hero-copy > p { font-size: 13px; }
            .hero-access { padding: 13px; }
            .access-heading { align-items: flex-start; flex-direction: column; gap: 3px; }
            .access-button { min-height: 88px; }
            .verified { gap: 10px; flex-direction: column; }
            .app-stage { min-height: 375px; }
            .preview { padding: 19px 14px; }
            .metric-grid { gap: 5px; }
            .metric { padding: 9px 7px; }
            .float-card.one { top: -9px; }
            .float-card.two { bottom: -3px; }
            .float-card { padding: 9px 11px; }
            .identity-intro { align-items: flex-start; flex-direction: column; }
            .section-head h2 { font-size: 31px; }
            .source-inner, .footer-inner { align-items: center; flex-direction: column; text-align: center; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="container topbar-inner">
            <p>Portal resmi pembayaran SPP sekolah</p>
            <div class="topbar-meta">
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>Kota Jambi</span>
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>NPSN 70029968</span>
            </div>
        </div>
    </div>

    <header class="header">
        <div class="container nav">
            <a class="brand" href="{{ url('/') }}" aria-label="Beranda Permata Insani">
                <span class="brand-logo"><img src="{{ asset('images/logo.jpg') }}" alt="Logo Permata Insani Islamic School"></span>
                <span class="brand-copy"><strong>Permata Insani</strong><span>Sistem Pembayaran SPP</span></span>
            </a>
            <nav class="nav-menu" aria-label="Navigasi utama">
                <a href="#profil">Profil Sekolah</a>
                <a href="#layanan">Layanan</a>
                <a href="#akses">Akses Masuk</a>
            </nav>
            <div class="nav-auth" aria-label="Akses cepat portal">
                <a class="nav-auth-link student" href="{{ route('siswa.login') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    Siswa
                </a>
                <a class="nav-auth-link teacher" href="{{ route('login') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5V6a2 2 0 0 1 2-2h12v15.5"/><path d="M6 18h13a1 1 0 0 1 1 1v1H6a2 2 0 0 1 0-4h12"/></svg>
                    Guru
                </a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <div class="hero-kicker"><span></span>Administrasi sekolah digital</div>
                    <h1>Pembayaran SPP, kini lebih <em>ringkas.</em></h1>
                    <p>Satu portal untuk membantu siswa mengakses informasi tagihan dan riwayat pembayaran, sekaligus mendukung guru mengelola administrasi SD IT Permata Insani Islamic School.</p>
                    <div class="hero-access" id="akses">
                        <div class="access-heading">
                            <strong>Pilih akses masuk Anda</strong>
                            <span>Gunakan akun yang diberikan sekolah</span>
                        </div>
                        <div class="access-grid">
                            <a class="access-button teacher" href="{{ route('login') }}">
                                <span class="access-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5V6a2 2 0 0 1 2-2h12v15.5"/><path d="M6 18h13a1 1 0 0 1 1 1v1H6a2 2 0 0 1 0-4h12"/></svg></span>
                                <span class="access-copy"><small>Portal pengelola</small><strong>Login Guru</strong><span>Kelola tagihan dan administrasi</span></span>
                                <span class="access-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                            </a>
                            <a class="access-button student" href="{{ route('siswa.login') }}">
                                <span class="access-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
                                <span class="access-copy"><small>Portal pribadi</small><strong>Login Siswa</strong><span>Lihat tagihan dan riwayat bayar</span></span>
                                <span class="access-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                            </a>
                        </div>
                    </div>
                    <div class="verified">
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>Akses sesuai akun</span>
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>Informasi terpusat</span>
                    </div>
                </div>

                <div class="app-stage" aria-label="Pratinjau tampilan portal SPP">
                    <div class="app-window">
                        <div class="window-bar"><i class="window-dot"></i><i class="window-dot"></i><i class="window-dot"></i><span class="window-title">Portal Pembayaran SPP</span></div>
                        <div class="app-body">
                            <aside class="mini-sidebar">
                                <div class="mini-brand"><img src="{{ asset('images/logo.jpg') }}" alt=""><strong>Permata<br>Insani</strong></div>
                                <div class="mini-label">Menu utama</div>
                                <div class="mini-nav">
                                    <span class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11 12 3l9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1Z"/></svg>Ringkasan</span>
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l4 4v16H6z"/><path d="M14 2v5h5M9 13h7M9 17h5"/></svg>Tagihan</span>
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/></svg>Riwayat</span>
                                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>Profil</span>
                                </div>
                            </aside>
                            <div class="preview">
                                <div class="preview-head"><div><small>Portal siswa</small><h2>Ringkasan</h2></div><span class="preview-chip">Akun terlindungi</span></div>
                                <div class="metric-grid">
                                    <div class="metric"><div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l4 4v16H6z"/><path d="M14 2v5h5"/></svg></div><span>Tagihan</span><strong>Informasi SPP</strong></div>
                                    <div class="metric"><div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg></div><span>Status</span><strong>Mudah dipantau</strong></div>
                                    <div class="metric"><div class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/></svg></div><span>Riwayat</span><strong>Tersimpan rapi</strong></div>
                                </div>
                                <div class="history-card">
                                    <div class="history-head">Riwayat pembayaran <span>Lihat semua</span></div>
                                    <div class="history-row"><strong>Administrasi SPP</strong><span>Periode pembayaran</span><i class="status">Tercatat</i></div>
                                    <div class="history-row"><strong>Informasi tagihan</strong><span>Rincian siswa</span><i class="status">Tersedia</i></div>
                                    <div class="history-row"><strong>Data pembayaran</strong><span>Portal siswa</span><i class="status">Terpusat</i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="float-card one"><div class="float-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg></div><div><span>Status sistem</span><strong>Siap digunakan</strong></div></div>
                    <div class="float-card two"><div class="float-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></div><div><span>Administrasi</span><strong>Data lebih terarah</strong></div></div>
                </div>
            </div>
        </section>

        <section class="identity" id="profil">
            <div class="container identity-card">
                <div class="identity-intro"><img src="{{ asset('images/logo.jpg') }}" alt="Logo sekolah"><div><h2>SD IT Permata Insani Islamic School</h2><p>Identitas satuan pendidikan resmi</p></div></div>
                <div class="identity-item"><span>Bentuk pendidikan</span><strong>Sekolah Dasar</strong></div>
                <div class="identity-item"><span>Status</span><strong>Swasta</strong></div>
                <div class="identity-item"><span>NPSN</span><strong>70029968</strong></div>
            </div>
        </section>

        <section class="features" id="layanan">
            <div class="container">
                <div class="section-head">
                    <div><span class="section-tag">Layanan sistem</span><h2>Satu alur administrasi yang lebih tertata.</h2></div>
                    <p>Antarmuka dirancang agar informasi penting mudah ditemukan oleh siswa dan pengelolaan data tetap efisien bagi admin sekolah.</p>
                </div>
                <div class="bento">
                    <article class="bento-card large">
                        <div class="bento-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l4 4v16H6z"/><path d="M14 2v5h5M9 13h7M9 17h5"/></svg></div>
                        <h3>Informasi pembayaran dalam satu tampilan.</h3>
                        <p>Tagihan dan riwayat pembayaran dapat diakses melalui portal yang sesuai.</p>
                        <div class="flow">
                            <div class="flow-head">Alur informasi <span>Portal SPP</span></div>
                            <div class="flow-step"><i class="flow-step-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5h16v14zM8 9h8M8 13h5"/></svg></i><strong>Data tagihan</strong><small class="flow-state">Tersedia</small></div>
                            <div class="flow-step"><i class="flow-step-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg></i><strong>Pembayaran tercatat</strong><small class="flow-state">Terpantau</small></div>
                            <div class="flow-step"><i class="flow-step-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/></svg></i><strong>Riwayat tersimpan</strong><small class="flow-state">Terpusat</small></div>
                        </div>
                    </article>
                    <article class="bento-card">
                        <div class="bento-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg></div>
                        <h3>Akses berbasis peran</h3><p>Portal siswa dan guru dipisahkan agar setiap pengguna mendapat akses yang relevan.</p>
                    </article>
                    <article class="bento-card">
                        <div class="bento-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></div>
                        <h3>Administrasi terarah</h3><p>Data tagihan, transaksi, dan riwayat dikelola melalui satu sistem sekolah.</p>
                        <div class="mini-bars" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                    </article>
                </div>
            </div>
        </section>

        <section class="source">
            <div class="container source-inner">
                <span>Jl. Abdul Muis No. 27, Lingkar Selatan, Kec. Paal Merah, Kota Jambi</span>
                <span>Profil sekolah merujuk pada <a href="https://referensi.data.kemendikdasmen.go.id/residu/satuanpendidikan/detail/70029968" target="_blank" rel="noopener noreferrer">Referensi Data Kemendikdasmen</a>.</span>
            </div>
        </section>
    </main>

    <footer><div class="container footer-inner"><span>&copy; {{ date('Y') }} SD IT Permata Insani Islamic School</span><span>Sistem Pembayaran SPP</span></div></footer>
</body>
</html>
