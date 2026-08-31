<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>DocTotal | Tu consulta médica, organizada</title>
    <meta name="description" content="Agenda, pacientes, expediente clínico, consultas y recetas en una sola experiencia para tu práctica médica.">
    <meta name="theme-color" content="#07142f">

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/branding/favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --navy: #07142f;
            --navy2: #0c1f48;
            --blue: #2563eb;
            --cyan: #22d3ee;
            --violet: #8b5cf6;
            --green: #10b981;
            --ink: #0f172a;
            --muted: #64748b;
            --soft: #f6f8fc;
            --line: #e6ebf3;
        }

        * {
            box-sizing: border-box
        }

        html {
            scroll-behavior: smooth
        }

        body {
            margin: 0;
            overflow-x: hidden;
            background: #fff;
            color: var(--ink);
            font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif
        }

        a {
            text-decoration: none;
            color: inherit
        }

        .wrap {
            width: min(1180px, calc(100% - 40px));
            margin: auto
        }

        .nav {
            position: fixed;
            inset: 0 0 auto;
            z-index: 50;
            padding: 18px 0;
            transition: .25s
        }

        .nav.scrolled {
            padding: 11px 0;
            background: rgba(5, 13, 31, .88);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            box-shadow: 0 14px 38px rgba(2, 8, 23, .18)
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px
        }

        .brand img {
            height: 42px;
            width: auto;
            display: block
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 26px
        }

        .nav-links a {
            font-size: .88rem;
            font-weight: 650;
            color: rgba(255, 255, 255, .74)
        }

        .nav-links a:hover {
            color: #fff
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 44px;
            padding: 0 19px;
            border-radius: 999px;
            font-weight: 750;
            font-size: .9rem;
            transition: .22s
        }

        .btn:hover {
            transform: translateY(-2px)
        }

        .btn-ghost {
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .14);
            background: rgba(255, 255, 255, .06)
        }

        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--blue), var(--violet));
            box-shadow: 0 14px 35px rgba(37, 99, 235, .3)
        }

        .menu-btn {
            display: none;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, .14);
            background: rgba(255, 255, 255, .06);
            color: #fff;
            align-items: center;
            justify-content: center;
            cursor: pointer
        }

        .mobile-menu {
            display: none;
            position: fixed;
            z-index: 60;
            top: 74px;
            left: 20px;
            right: 20px;
            padding: 13px;
            border-radius: 20px;
            background: rgba(5, 13, 31, .97);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, .1);
            box-shadow: 0 30px 60px rgba(2, 8, 23, .42)
        }

        .mobile-menu.open {
            display: block
        }

        .mobile-menu a {
            display: flex;
            justify-content: space-between;
            padding: 12px 13px;
            border-radius: 12px;
            color: #fff;
            font-weight: 650
        }

        .mobile-menu a:hover {
            background: rgba(255, 255, 255, .07)
        }

        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
            padding: 125px 0 85px;
            background:
                radial-gradient(circle at 80% 18%, rgba(139, 92, 246, .2), transparent 28%),
                radial-gradient(circle at 65% 72%, rgba(34, 211, 238, .12), transparent 28%),
                radial-gradient(circle at 10% 20%, rgba(37, 99, 235, .18), transparent 28%),
                linear-gradient(135deg, #050d1f, var(--navy) 46%, #121a43)
        }

        .hero:before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: .42;
            background-image: linear-gradient(rgba(255, 255, 255, .035) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .035) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, #000, transparent 92%);
            pointer-events: none
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            pointer-events: none
        }

        .orb.one {
            width: 300px;
            height: 300px;
            right: -80px;
            top: 10%;
            background: rgba(139, 92, 246, .32);
            animation: drift 12s ease-in-out infinite
        }

        .orb.two {
            width: 260px;
            height: 260px;
            left: -100px;
            bottom: 5%;
            background: rgba(37, 99, 235, .3);
            animation: drift 15s ease-in-out infinite reverse
        }

        .hero-grid {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: .94fr 1.06fr;
            gap: 60px;
            align-items: center
        }

        .eyebrow,
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em
        }

        .eyebrow {
            margin-bottom: 20px;
            color: #dbeafe;
            background: rgba(37, 99, 235, .12);
            border: 1px solid rgba(96, 165, 250, .28)
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--cyan);
            animation: pulse 2s ease-out infinite
        }

        .hero h1 {
            margin: 0;
            color: #fff;
            font-size: clamp(3rem, 5.2vw, 5rem);
            line-height: .98;
            letter-spacing: -.055em;
            font-weight: 820
        }

        .hero h1 span {
            background: linear-gradient(90deg, #60a5fa, #67e8f9 48%, #a78bfa);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent
        }

        .hero-copy p {
            max-width: 590px;
            margin: 24px 0 0;
            color: rgba(226, 232, 240, .76);
            font-size: 1.08rem;
            line-height: 1.78
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 30px
        }

        .hero-actions .btn {
            min-height: 54px;
            padding: 0 24px
        }

        .checks {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 18px;
            margin-top: 25px;
            color: rgba(226, 232, 240, .68);
            font-size: .82rem;
            font-weight: 600
        }

        .checks span {
            display: inline-flex;
            align-items: center;
            gap: 7px
        }

        .checks svg {
            width: 16px;
            color: #34d399
        }

        .visual {
            position: relative;
            min-height: 600px;
            perspective: 1500px
        }

        .glow {
            position: absolute;
            width: 470px;
            height: 470px;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59, 130, 246, .25), rgba(124, 58, 237, .12) 45%, transparent 70%);
            filter: blur(18px);
            animation: glow 5s ease-in-out infinite
        }

        .window {
            position: absolute;
            z-index: 3;
            left: 50%;
            top: 50%;
            width: min(560px, 100%);
            transform: translate(-48%, -48%) rotateY(-7deg) rotateX(3deg);
            overflow: hidden;
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, .11);
            background: #f8fafc;
            box-shadow: 0 50px 100px rgba(2, 8, 23, .46), 0 0 60px rgba(37, 99, 235, .12);
            animation: floatMain 6s ease-in-out infinite
        }

        .window-top {
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 17px;
            background: #fff;
            border-bottom: 1px solid var(--line)
        }

        .traffic {
            display: flex;
            gap: 6px
        }

        .traffic i {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #cbd5e1
        }

        .window-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .8rem;
            font-weight: 800
        }

        .window-brand img {
            width: 24px;
            height: 24px
        }

        .online {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #166534;
            font-size: .66rem;
            font-weight: 800
        }

        .online:before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #22c55e
        }

        .window-body {
            display: grid;
            grid-template-columns: 145px 1fr;
            min-height: 390px
        }

        .mock-side {
            padding: 18px 12px;
            background: linear-gradient(#08152f, #0c1d42)
        }

        .mock-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 10px;
            margin-bottom: 5px;
            border-radius: 10px;
            color: rgba(226, 232, 240, .57);
            font-size: .67rem;
            font-weight: 650
        }

        .mock-nav:before {
            content: "";
            width: 14px;
            height: 14px;
            border-radius: 5px;
            background: currentColor;
            opacity: .3
        }

        .mock-nav.active {
            color: #fff;
            background: linear-gradient(90deg, rgba(37, 99, 235, .35), rgba(139, 92, 246, .16))
        }

        .mock-main {
            padding: 22px;
            background: #f7f9fd
        }

        .hello strong {
            display: block;
            font-size: .92rem
        }

        .hello span {
            color: #94a3b8;
            font-size: .62rem
        }

        .kpis {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 9px;
            margin-top: 17px
        }

        .kpi {
            padding: 12px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: #fff
        }

        .kpi small {
            display: block;
            color: #94a3b8;
            font-size: .52rem;
            font-weight: 750
        }

        .kpi strong {
            display: block;
            margin-top: 5px;
            font-size: 1.02rem
        }

        .kpi:nth-child(1) strong {
            color: var(--blue)
        }

        .kpi:nth-child(2) strong {
            color: var(--violet)
        }

        .kpi:nth-child(3) strong {
            color: var(--green)
        }

        .agenda-box {
            margin-top: 13px;
            padding: 14px;
            border-radius: 15px;
            border: 1px solid var(--line);
            background: #fff
        }

        .agenda-title {
            display: flex;
            justify-content: space-between;
            font-size: .62rem;
            font-weight: 800;
            color: #334155
        }

        .agenda-title span:last-child {
            color: var(--blue);
            font-size: .54rem
        }

        .appointment {
            display: grid;
            grid-template-columns: 55px 1fr auto;
            gap: 9px;
            align-items: center;
            padding: 10px 0;
            border-top: 1px solid #f1f5f9
        }

        .appointment:first-of-type {
            margin-top: 8px
        }

        .time {
            font-size: .6rem;
            font-weight: 750;
            color: #475569
        }

        .patient strong {
            display: block;
            font-size: .6rem;
            color: #334155
        }

        .patient span {
            font-size: .5rem;
            color: #94a3b8
        }

        .badge {
            padding: 5px 7px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: .46rem;
            font-weight: 800
        }

        .float-card {
            position: absolute;
            z-index: 6;
            padding: 14px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, .15);
            background: rgba(8, 21, 47, .78);
            backdrop-filter: blur(18px);
            box-shadow: 0 22px 50px rgba(2, 8, 23, .34);
            color: #fff
        }

        .float-card .label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(226, 232, 240, .58);
            font-size: .58rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em
        }

        .float-icon {
            display: flex;
            width: 28px;
            height: 28px;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: rgba(37, 99, 235, .18);
            color: #93c5fd
        }

        .float-icon.violet {
            background: rgba(139, 92, 246, .18);
            color: #c4b5fd
        }

        .float-icon.green {
            background: rgba(16, 185, 129, .18);
            color: #6ee7b7
        }

        .float-card strong {
            display: block;
            margin-top: 9px;
            font-size: .86rem
        }

        .float-card p {
            margin: 4px 0 0;
            color: rgba(226, 232, 240, .64);
            font-size: .64rem;
            line-height: 1.45
        }

        .f1 {
            top: 58px;
            right: -22px;
            width: 210px;
            animation: float1 4.6s ease-in-out infinite
        }

        .f2 {
            left: -18px;
            bottom: 100px;
            width: 190px;
            animation: float2 5.4s ease-in-out infinite
        }

        .f3 {
            right: 4px;
            bottom: 34px;
            width: 188px;
            animation: float3 5.8s ease-in-out infinite
        }

        .section {
            padding: 95px 0
        }

        .soft {
            background: var(--soft)
        }

        .dark {
            color: #fff;
            background: radial-gradient(circle at 90% 10%, rgba(139, 92, 246, .15), transparent 28%), linear-gradient(135deg, var(--navy), var(--navy2) 55%, #151a43)
        }

        .section-head {
            max-width: 760px;
            margin: 0 auto 46px;
            text-align: center
        }

        .tag {
            margin-bottom: 12px;
            color: var(--blue);
            background: #eff6ff;
            border: 1px solid #dbeafe
        }

        .dark .tag {
            color: #bfdbfe;
            background: rgba(37, 99, 235, .13);
            border-color: rgba(96, 165, 250, .2)
        }

        .section-head h2,
        .split-copy h2 {
            margin: 0;
            font-size: clamp(2.1rem, 4vw, 3.2rem);
            line-height: 1.06;
            letter-spacing: -.04em;
            font-weight: 820
        }

        .dark h2 {
            color: #fff
        }

        .section-head p,
        .split-copy>p {
            margin: 15px 0 0;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.78
        }

        .dark .section-head p,
        .dark .split-copy>p {
            color: rgba(226, 232, 240, .68)
        }

        .features {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px
        }

        .feature {
            min-height: 205px;
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 12px 32px rgba(15, 23, 42, .05);
            transition: .24s
        }

        .feature:hover {
            transform: translateY(-7px);
            box-shadow: 0 22px 45px rgba(15, 23, 42, .09)
        }

        .icon {
            display: flex;
            width: 46px;
            height: 46px;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #eff6ff;
            color: var(--blue)
        }

        .icon.violet {
            background: #f5f3ff;
            color: #7c3aed
        }

        .icon.cyan {
            background: #ecfeff;
            color: #0891b2
        }

        .icon.green {
            background: #ecfdf5;
            color: #059669
        }

        .icon.rose {
            background: #fff1f2;
            color: #e11d48
        }

        .feature h3 {
            margin: 17px 0 7px;
            font-size: 1rem
        }

        .feature p {
            margin: 0;
            color: var(--muted);
            font-size: .84rem;
            line-height: 1.62
        }

        .split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 74px;
            align-items: center
        }

        .list {
            display: grid;
            gap: 11px;
            margin-top: 27px
        }

        .list-item {
            display: flex;
            gap: 12px;
            padding: 14px 15px;
            border-radius: 16px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, .82)
        }

        .dark .list-item {
            border-color: rgba(255, 255, 255, .1);
            background: rgba(255, 255, 255, .05)
        }

        .list-icon {
            display: flex;
            flex: 0 0 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eff6ff;
            color: var(--blue)
        }

        .dark .list-icon {
            background: rgba(37, 99, 235, .15);
            color: #93c5fd
        }

        .list-item strong {
            display: block;
            font-size: .87rem
        }

        .dark .list-item strong {
            color: #fff
        }

        .list-item span {
            display: block;
            margin-top: 2px;
            color: var(--muted);
            font-size: .76rem;
            line-height: 1.48
        }

        .dark .list-item span {
            color: rgba(226, 232, 240, .6)
        }

        .showcase {
            padding: 26px;
            border-radius: 28px;
            border: 1px solid var(--line);
            background: #fff;
            box-shadow: 0 30px 70px rgba(15, 23, 42, .09)
        }

        .dark .showcase {
            border-color: rgba(255, 255, 255, .09);
            background: rgba(255, 255, 255, .045);
            box-shadow: 0 30px 70px rgba(2, 8, 23, .28)
        }

        .browser {
            overflow: hidden;
            border-radius: 21px;
            border: 1px solid var(--line);
            background: #f8fafc;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
            animation: softFloat 6.5s ease-in-out infinite
        }

        .browser-top {
            height: 43px;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0 14px;
            background: #fff;
            border-bottom: 1px solid var(--line)
        }

        .browser-top i {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #cbd5e1
        }

        .browser-body {
            padding: 17px
        }

        .cal-row {
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 11px;
            align-items: center;
            margin-bottom: 9px
        }

        .cal-time {
            font-size: .63rem;
            color: var(--muted);
            font-weight: 750
        }

        .cal-card {
            padding: 10px 12px;
            border-left: 4px solid var(--blue);
            border-radius: 11px;
            background: #eff6ff
        }

        .cal-card.green {
            border-color: var(--green);
            background: #ecfdf5
        }

        .cal-card.violet {
            border-color: var(--violet);
            background: #f5f3ff
        }

        .cal-card strong {
            display: block;
            font-size: .7rem;
            color: #1e293b
        }

        .cal-card span {
            font-size: .58rem;
            color: var(--muted)
        }

        .record {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px
        }

        .record-card {
            padding: 13px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fff
        }

        .record-card.wide {
            grid-column: 1/-1
        }

        .record-card small {
            display: block;
            color: #94a3b8;
            font-size: .53rem;
            font-weight: 800;
            text-transform: uppercase
        }

        .record-card strong {
            display: block;
            margin-top: 4px;
            font-size: .72rem;
            color: #334155
        }

        .timeline {
            margin-top: 12px;
            padding-left: 18px;
            border-left: 2px solid #dbeafe
        }

        .timeline div {
            position: relative;
            margin-bottom: 11px
        }

        .timeline div:before {
            content: "";
            position: absolute;
            left: -23px;
            top: 4px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--blue);
            border: 3px solid #dbeafe
        }

        .timeline b {
            display: block;
            font-size: .66rem;
            color: #334155
        }

        .timeline span {
            font-size: .56rem;
            color: #94a3b8
        }

        .rx {
            position: relative;
            width: min(380px, 100%);
            margin: auto;
            padding: 28px;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 30px 70px rgba(2, 8, 23, .26);
            transform: rotate(2deg);
            animation: rxFloat 6s ease-in-out infinite
        }

        .rx:before {
            content: "";
            position: absolute;
            z-index: -1;
            inset: 16px -18px -18px 18px;
            border-radius: 18px;
            background: rgba(37, 99, 235, .13);
            transform: rotate(-4deg)
        }

        .rx-head {
            display: flex;
            gap: 10px;
            align-items: center;
            padding-bottom: 13px;
            border-bottom: 1px solid var(--line)
        }

        .rx-head img {
            width: 34px;
            height: 34px
        }

        .rx-head strong {
            display: block;
            font-size: .82rem
        }

        .rx-head span {
            display: block;
            margin-top: 2px;
            color: #94a3b8;
            font-size: .56rem
        }

        .rx-box {
            margin-top: 14px;
            padding: 11px;
            border-radius: 12px;
            background: #f8fafc
        }

        .rx-box small {
            display: block;
            color: #94a3b8;
            font-size: .5rem;
            font-weight: 800;
            text-transform: uppercase
        }

        .rx-box strong {
            display: block;
            margin-top: 3px;
            font-size: .7rem;
            color: #334155
        }

        .rx-line {
            margin-top: 15px
        }

        .rx-line strong {
            font-size: .7rem
        }

        .rx-line p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: .59rem;
            line-height: 1.5
        }

        .signature {
            width: 130px;
            height: 1px;
            margin: 32px auto 7px;
            background: #cbd5e1
        }

        .sig-text {
            text-align: center;
            color: #94a3b8;
            font-size: .53rem
        }

        .benefits {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 17px
        }

        .benefit {
            padding: 25px;
            border: 1px solid var(--line);
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 12px 32px rgba(15, 23, 42, .05)
        }

        .benefit strong {
            display: block;
            margin-top: 14px
        }

        .benefit p {
            margin: 7px 0 0;
            color: var(--muted);
            font-size: .84rem;
            line-height: 1.62
        }

        .cta {
            padding: 92px 0;
            background: radial-gradient(circle at 20% 10%, rgba(34, 211, 238, .13), transparent 24%), radial-gradient(circle at 80% 90%, rgba(139, 92, 246, .17), transparent 26%), linear-gradient(135deg, #07142f, #0c1e46 54%, #151a43)
        }

        .cta-card {
            padding: 54px;
            border-radius: 32px;
            text-align: center;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .1);
            background: rgba(255, 255, 255, .05);
            backdrop-filter: blur(16px);
            box-shadow: 0 40px 90px rgba(2, 8, 23, .3)
        }

        .cta-card h2 {
            margin: 0;
            font-size: clamp(2.2rem, 5vw, 3.6rem);
            line-height: 1.03;
            letter-spacing: -.045em
        }

        .cta-card p {
            max-width: 620px;
            margin: 16px auto 0;
            color: rgba(226, 232, 240, .69);
            line-height: 1.75
        }

        .cta-actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 27px
        }

        .cta-actions .btn {
            min-height: 53px;
            padding: 0 23px
        }

        footer {
            padding: 34px 0;
            background: #050d1f;
            color: rgba(226, 232, 240, .58)
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 11px
        }

        .footer-brand img {
            width: 32px;
            height: 32px
        }

        .footer-brand strong {
            display: block;
            color: #fff;
            font-size: .88rem
        }

        .footer-brand span {
            display: block;
            margin-top: 2px;
            font-size: .7rem
        }

        .footer-links {
            display: flex;
            gap: 17px;
            flex-wrap: wrap
        }

        .footer-links a {
            font-size: .76rem
        }

        .footer-links a:hover {
            color: #fff
        }

        @keyframes floatMain {

            0%,
            100% {
                transform: translate(-48%, -48%) rotateY(-7deg) rotateX(3deg) translateY(0)
            }

            50% {
                transform: translate(-48%, -48%) rotateY(-7deg) rotateX(3deg) translateY(-16px)
            }
        }

        @keyframes float1 {

            0%,
            100% {
                transform: translateY(0) rotate(1.5deg)
            }

            50% {
                transform: translateY(-18px) rotate(-.5deg)
            }
        }

        @keyframes float2 {

            0%,
            100% {
                transform: translateY(0) rotate(-2deg)
            }

            50% {
                transform: translateY(-13px) rotate(.5deg)
            }
        }

        @keyframes float3 {

            0%,
            100% {
                transform: translateY(0) rotate(1deg)
            }

            50% {
                transform: translateY(-16px) rotate(-1deg)
            }
        }

        @keyframes drift {

            0%,
            100% {
                transform: translate3d(0, 0, 0)
            }

            50% {
                transform: translate3d(-28px, 24px, 0) scale(1.08)
            }
        }

        @keyframes glow {

            0%,
            100% {
                opacity: .72;
                transform: translate(-50%, -50%) scale(1)
            }

            50% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1.08)
            }
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(34, 211, 238, .4)
            }

            70% {
                box-shadow: 0 0 0 10px rgba(34, 211, 238, 0)
            }

            100% {
                box-shadow: 0 0 0 0 rgba(34, 211, 238, 0)
            }
        }

        @keyframes softFloat {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(-10px)
            }
        }

        @keyframes rxFloat {

            0%,
            100% {
                transform: rotate(2deg) translateY(0)
            }

            50% {
                transform: rotate(.5deg) translateY(-12px)
            }
        }

        @media(max-width:1080px) {
            .hero-grid {
                grid-template-columns: 1fr
            }

            .hero-copy {
                text-align: center;
                max-width: 760px;
                margin: auto
            }

            .hero-copy p {
                margin-inline: auto
            }

            .hero-actions,
            .checks {
                justify-content: center
            }

            .visual {
                width: min(720px, 100%);
                margin: auto
            }

            .features {
                grid-template-columns: repeat(3, 1fr)
            }
        }

        @media(max-width:900px) {
            .nav-links {
                display: none
            }

            .menu-btn {
                display: flex
            }

            .nav-actions>.btn {
                display: none
            }

            .split {
                grid-template-columns: 1fr;
                gap: 42px
            }

            .benefits {
                grid-template-columns: 1fr 1fr
            }
        }

        @media(max-width:720px) {
            .wrap {
                width: min(100% - 28px, 1180px)
            }

            .hero {
                padding: 105px 0 62px
            }

            .visual {
                min-height: 500px
            }

            .window {
                width: 92%;
                transform: translate(-50%, -50%)
            }

            .window-body {
                grid-template-columns: 1fr
            }

            .mock-side {
                display: none
            }

            .f1 {
                right: 0;
                top: 20px;
                width: 180px
            }

            .f2 {
                left: 0;
                bottom: 70px;
                width: 165px
            }

            .f3 {
                right: 6px;
                bottom: 4px;
                width: 165px
            }

            .features {
                grid-template-columns: 1fr 1fr
            }

            .section {
                padding: 72px 0
            }

            .footer-inner {
                flex-direction: column;
                text-align: center
            }

            .footer-links {
                justify-content: center
            }
        }

        @media(max-width:520px) {
            .brand img {
                height: 36px
            }

            .hero-actions {
                flex-direction: column
            }

            .hero-actions .btn {
                width: 100%
            }

            .checks {
                display: grid;
                grid-template-columns: 1fr 1fr;
                text-align: left
            }

            .visual {
                min-height: 455px
            }

            .window {
                width: 100%
            }

            .mock-main {
                padding: 16px
            }

            .kpis {
                grid-template-columns: 1fr 1fr
            }

            .kpi:last-child {
                grid-column: 1/-1
            }

            .features,
            .benefits {
                grid-template-columns: 1fr
            }

            .showcase {
                padding: 15px
            }

            .record {
                grid-template-columns: 1fr
            }

            .record-card.wide {
                grid-column: auto
            }

            .cta-card {
                padding: 38px 21px
            }
        }

        @media(prefers-reduced-motion:reduce) {

            *,
            *:before,
            *:after {
                animation-duration: .001ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: .001ms !important
            }
        }
    </style>
</head>

<body>

    <nav class="nav" id="navbar">
        <div class="wrap nav-inner">
            <a href="{{ route('home') }}" class="brand" aria-label="DocTotal">
                <img src="{{ asset('images/branding/doctotal-logo-white.png') }}" alt="DocTotal">
            </a>

            <div class="nav-links">
                <a href="#funciones">Funciones</a>
                <a href="#agenda">Agenda</a>
                <a href="#expediente">Expediente</a>
                <a href="#recetas">Recetas</a>
                <a href="#beneficios">Beneficios</a>
            </div>

            <div class="nav-actions">
                @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Ir al dashboard</a>
                @else
                <a href="{{ route('login') }}" class="btn btn-ghost">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Crear cuenta</a>
                @endauth

                <button class="menu-btn" id="menuButton" type="button" aria-label="Abrir menú" aria-expanded="false">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none">
                        <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <div class="mobile-menu" id="mobileMenu">
        <a href="#funciones">Funciones <span>→</span></a>
        <a href="#agenda">Agenda <span>→</span></a>
        <a href="#expediente">Expediente <span>→</span></a>
        <a href="#recetas">Recetas <span>→</span></a>
        <a href="#beneficios">Beneficios <span>→</span></a>
        @auth
        <a href="{{ route('dashboard') }}">Dashboard <span>→</span></a>
        @else
        <a href="{{ route('login') }}">Iniciar sesión <span>→</span></a>
        <a href="{{ route('register') }}">Crear cuenta <span>→</span></a>
        @endauth
    </div>

    <header class="hero">
        <div class="orb one"></div>
        <div class="orb two"></div>

        <div class="wrap hero-grid">
            <div class="hero-copy">
                <div class="eyebrow"><span class="dot"></span>Tecnología para tu práctica médica</div>

                <h1>Tu consulta,<br><span>más clara.</span><br>Tu día, más simple.</h1>

                <p>
                    Agenda, pacientes, expediente clínico, consultas y recetas en una sola experiencia.
                    DocTotal te ayuda a mantener la información organizada para que puedas concentrarte en atender.
                </p>

                <div class="hero-actions">
                    @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">
                        Abrir DocTotal
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none">
                            <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                    @else
                    <a href="{{ route('register') }}" class="btn btn-primary">
                        Comenzar ahora
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none">
                            <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-ghost">Iniciar sesión</a>
                    @endauth
                </div>

                <div class="checks">
                    <span><svg viewBox="0 0 24 24" fill="none">
                            <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>Agenda integrada</span>
                    <span><svg viewBox="0 0 24 24" fill="none">
                            <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>Expediente longitudinal</span>
                    <span><svg viewBox="0 0 24 24" fill="none">
                            <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>Recetas organizadas</span>
                </div>
            </div>

            <div class="visual" aria-hidden="true">
                <div class="glow"></div>

                <div class="window">
                    <div class="window-top">
                        <div class="traffic"><i></i><i></i><i></i></div>
                        <div class="window-brand"><img src="{{ asset('images/branding/doctotal-icon.png') }}" alt="">DocTotal</div>
                        <div class="online">Consulta activa</div>
                    </div>

                    <div class="window-body">
                        <aside class="mock-side">
                            <div class="mock-nav active">Dashboard</div>
                            <div class="mock-nav">Agenda</div>
                            <div class="mock-nav">Pacientes</div>
                            <div class="mock-nav">Consultas</div>
                            <div class="mock-nav">Recetas</div>
                        </aside>

                        <main class="mock-main">
                            <div class="hello"><strong>Buenas tardes, Doctor</strong><span>Tu consulta, organizada de un vistazo.</span></div>

                            <div class="kpis">
                                <div class="kpi"><small>Citas de hoy</small><strong>6</strong></div>
                                <div class="kpi"><small>Pacientes</small><strong>128</strong></div>
                                <div class="kpi"><small>Completadas</small><strong>4</strong></div>
                            </div>

                            <div class="agenda-box">
                                <div class="agenda-title"><span>Agenda de hoy</span><span>Ver agenda</span></div>
                                <div class="appointment">
                                    <div class="time">16:30</div>
                                    <div class="patient"><strong>Paciente programado</strong><span>Consulta general</span></div>
                                    <span class="badge">Confirmada</span>
                                </div>
                                <div class="appointment">
                                    <div class="time">17:30</div>
                                    <div class="patient"><strong>Próxima consulta</strong><span>Seguimiento</span></div>
                                    <span class="badge">Próxima</span>
                                </div>
                            </div>
                        </main>
                    </div>
                </div>

                <div class="float-card f1">
                    <div class="label">
                        <span class="float-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                                <path d="M7 3v3m10-3v3M4 9h16M5 5h14v16H5V5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </span>
                        Próxima cita
                    </div>
                    <strong>17:30 · Seguimiento</strong>
                    <p>Contexto antes de recibir al paciente.</p>
                </div>

                <div class="float-card f2">
                    <div class="label">
                        <span class="float-icon violet">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.8" />
                            </svg>
                        </span>
                        Expediente
                    </div>
                    <strong>Historial clínico</strong>
                    <p>Consultas, diagnósticos y documentos reunidos.</p>
                </div>

                <div class="float-card f3">
                    <div class="label">
                        <span class="float-icon green">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                                <path d="m8 12 3 3 5-6M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </span>
                        Consulta
                    </div>
                    <strong>Registro completado</strong>
                    <p>La evolución queda integrada al expediente.</p>
                </div>
            </div>
        </div>
    </header>

    <section class="section soft" id="funciones">
        <div class="wrap">
            <div class="section-head">
                <div class="tag">Todo conectado</div>
                <h2>Una sola plataforma para el flujo completo de tu consulta.</h2>
                <p>Menos información dispersa. DocTotal reúne lo esencial del trabajo clínico cotidiano en una interfaz coherente.</p>
            </div>

            <div class="features">
                @php
                $features = [
                ['Agenda','Organiza horarios, citas, estados y reprogramaciones.','blue'],
                ['Pacientes','Consulta datos, antecedentes y seguimiento desde un expediente central.','violet'],
                ['Consultas','Registra el encuentro clínico y mantén una evolución ordenada.','cyan'],
                ['Recetas','Genera, consulta, edita e imprime recetas vinculadas a la consulta.','green'],
                ['Documentos','Mantén archivos clínicos importantes asociados al paciente.','rose'],
                ];
                @endphp

                @foreach($features as [$title,$text,$tone])
                <article class="feature">
                    <div class="icon {{ $tone }}">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M5 4h14v16H5V4Zm4 4h6m-6 4h6m-6 4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </div>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section" id="agenda">
        <div class="wrap split">
            <div class="split-copy">
                <div class="tag">Agenda clínica</div>
                <h2>Tu día empieza con claridad.</h2>
                <p>Visualiza las citas programadas, identifica lo que sigue y entra al expediente correcto sin perder tiempo buscando información.</p>

                <div class="list">
                    <div class="list-item">
                        <div class="list-icon">✓</div>
                        <div><strong>Agenda estructurada</strong><span>Horarios, duración y disponibilidad dentro del mismo flujo.</span></div>
                    </div>
                    <div class="list-item">
                        <div class="list-icon">✓</div>
                        <div><strong>Contexto antes de la consulta</strong><span>La próxima cita queda visible para anticipar el siguiente paso.</span></div>
                    </div>
                    <div class="list-item">
                        <div class="list-icon">✓</div>
                        <div><strong>Reprogramación simple</strong><span>Cambia la cita conservando la relación con el paciente.</span></div>
                    </div>
                </div>
            </div>

            <div class="showcase">
                <div class="browser">
                    <div class="browser-top"><i></i><i></i><i></i></div>
                    <div class="browser-body">
                        <div class="cal-row">
                            <div class="cal-time">09:00</div>
                            <div class="cal-card"><strong>Consulta general</strong><span>Paciente programado · 30 min</span></div>
                        </div>
                        <div class="cal-row">
                            <div class="cal-time">10:30</div>
                            <div class="cal-card green"><strong>Seguimiento</strong><span>Consulta confirmada · 30 min</span></div>
                        </div>
                        <div class="cal-row">
                            <div class="cal-time">12:00</div>
                            <div class="cal-card violet"><strong>Primera consulta</strong><span>Nuevo paciente · 45 min</span></div>
                        </div>
                        <div class="cal-row">
                            <div class="cal-time">16:30</div>
                            <div class="cal-card"><strong>Consulta de control</strong><span>Paciente programado · 30 min</span></div>
                        </div>
                        <div class="cal-row">
                            <div class="cal-time">17:30</div>
                            <div class="cal-card green"><strong>Próxima cita</strong><span>Seguimiento · 30 min</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section dark" id="expediente">
        <div class="wrap split">
            <div class="showcase">
                <div class="browser">
                    <div class="browser-top"><i></i><i></i><i></i></div>
                    <div class="browser-body">
                        <div class="record">
                            <div class="record-card"><small>Paciente</small><strong>Expediente clínico</strong></div>
                            <div class="record-card"><small>Tipo sanguíneo</small><strong>Registrado</strong></div>
                            <div class="record-card wide">
                                <small>Historial reciente</small>
                                <div class="timeline">
                                    <div><b>Consulta de seguimiento</b><span>Diagnóstico y evolución registrados</span></div>
                                    <div><b>Documento clínico</b><span>Archivo incorporado al expediente</span></div>
                                    <div><b>Consulta previa</b><span>Tratamiento y receta asociados</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="split-copy">
                <div class="tag">Expediente longitudinal</div>
                <h2>El historial del paciente permanece conectado.</h2>
                <p>Cada consulta se suma a una historia clínica continua para revisar antecedentes, diagnósticos, tratamientos, documentos y recetas sin reconstruir el contexto desde cero.</p>

                <div class="list">
                    <div class="list-item">
                        <div class="list-icon">✓</div>
                        <div><strong>Historia clínica ordenada</strong><span>La evolución queda reunida cronológicamente dentro del expediente.</span></div>
                    </div>
                    <div class="list-item">
                        <div class="list-icon">✓</div>
                        <div><strong>Documentos clínicos</strong><span>Archivos importantes pueden quedar vinculados directamente al paciente.</span></div>
                    </div>
                    <div class="list-item">
                        <div class="list-icon">✓</div>
                        <div><strong>Seguimiento clínico</strong><span>Consulta lo previo antes de registrar nuevas decisiones.</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section soft" id="recetas">
        <div class="wrap split">
            <div class="split-copy">
                <div class="tag">Recetas</div>
                <h2>Del encuentro clínico a la receta, sin romper el flujo.</h2>
                <p>Mantén la prescripción vinculada con la consulta que la originó y genera una salida lista para visualizar, imprimir o conservar dentro del historial.</p>

                <div class="list">
                    <div class="list-item">
                        <div class="list-icon">✓</div>
                        <div><strong>Prescripción vinculada</strong><span>La receta permanece relacionada con la consulta y el paciente.</span></div>
                    </div>
                    <div class="list-item">
                        <div class="list-icon">✓</div>
                        <div><strong>Lista para imprimir</strong><span>Visualiza una presentación limpia para entregar al paciente.</span></div>
                    </div>
                    <div class="list-item">
                        <div class="list-icon">✓</div>
                        <div><strong>Integrada al expediente</strong><span>Consulta posteriormente qué tratamiento fue indicado.</span></div>
                    </div>
                </div>
            </div>

            <div class="rx">
                <div class="rx-head"><img src="{{ asset('images/branding/doctotal-icon.png') }}" alt="">
                    <div><strong>Receta médica</strong><span>Documento generado desde DocTotal</span></div>
                </div>
                <div class="rx-box"><small>Paciente</small><strong>Información vinculada al expediente</strong></div>
                <div class="rx-line"><strong>1. Tratamiento indicado</strong>
                    <p>Dosis, frecuencia, vía y duración registradas durante la consulta.</p>
                </div>
                <div class="rx-line"><strong>2. Indicaciones</strong>
                    <p>Observaciones clínicas y recomendaciones para el seguimiento.</p>
                </div>
                <div class="signature"></div>
                <div class="sig-text">Firma del profesional</div>
            </div>
        </div>
    </section>

    <section class="section" id="beneficios">
        <div class="wrap">
            <div class="section-head">
                <div class="tag">Diseñado para el día a día</div>
                <h2>Menos fricción entre una tarea y la siguiente.</h2>
                <p>DocTotal busca que la tecnología acompañe el trabajo clínico sin convertirse en una distracción.</p>
            </div>

            <div class="benefits">
                <article class="benefit">
                    <div class="icon">⚡</div><strong>Flujo ágil</strong>
                    <p>Accede rápidamente a la agenda, al paciente y a la consulta sin cambiar de herramienta.</p>
                </article>
                <article class="benefit">
                    <div class="icon violet">≡</div><strong>Información clara</strong>
                    <p>La interfaz prioriza jerarquía visual y contexto para reducir la carga de buscar datos.</p>
                </article>
                <article class="benefit">
                    <div class="icon green">✓</div><strong>Continuidad clínica</strong>
                    <p>Las consultas, recetas y documentos permanecen ligados al historial del paciente.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="wrap">
            <div class="cta-card">
                <div class="tag">DocTotal</div>
                <h2>Más claridad para tu consulta.<br>Menos fricción en tu día.</h2>
                <p>Lleva agenda, expediente y atención clínica dentro de una experiencia pensada para mantener el foco donde realmente importa.</p>

                <div class="cta-actions">
                    @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Ir al dashboard →</a>
                    @else
                    <a href="{{ route('register') }}" class="btn btn-primary">Crear mi cuenta →</a>
                    <a href="{{ route('login') }}" class="btn btn-ghost">Ya tengo una cuenta</a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="wrap footer-inner">
            <div class="footer-brand">
                <img src="{{ asset('images/branding/doctotal-icon.png') }}" alt="DocTotal">
                <div><strong>DocTotal</strong><span>Tu consulta médica, organizada.</span></div>
            </div>

            <div class="footer-links">
                <a href="#funciones">Funciones</a>
                <a href="#agenda">Agenda</a>
                <a href="#expediente">Expediente</a>
                <a href="#recetas">Recetas</a>
                @auth
                <a href="{{ route('dashboard') }}">Dashboard</a>
                @else
                <a href="{{ route('login') }}">Iniciar sesión</a>
                @endauth
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.getElementById('navbar');
            const button = document.getElementById('menuButton');
            const menu = document.getElementById('mobileMenu');

            const updateNav = () => navbar.classList.toggle('scrolled', window.scrollY > 24);
            updateNav();
            window.addEventListener('scroll', updateNav, {
                passive: true
            });

            button?.addEventListener('click', function() {
                const open = menu.classList.toggle('open');
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            menu?.querySelectorAll('a').forEach(link => link.addEventListener('click', function() {
                menu.classList.remove('open');
                button?.setAttribute('aria-expanded', 'false');
            }));

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    menu?.classList.remove('open');
                    button?.setAttribute('aria-expanded', 'false');
                }
            });
        });
    </script>

</body>

</html>