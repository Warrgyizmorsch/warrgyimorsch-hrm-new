@extends('layouts.app')

@section('content')

<div class="zoho-page-shell">
<div class="main-content zoho-module-content">
<div class="celebrations-container">
    <!-- Hero Header -->
    <div class="celebrations-hero">
        <div class="hero-content">
            <h1 class="hero-title">Team <span class="gradient-text">Milestones</span></h1>
            <p class="hero-subtitle">Celebrating the people who make our company great.</p>
        </div>

        <div class="celebration-switcher">
            <button class="switcher-btn active" onclick="switchCelebrationTab('birthday', this)">
                <span class="icon">🎂</span> Birthdays
            </button>
            <button class="switcher-btn" onclick="switchCelebrationTab('anniversary', this)">
                <span class="icon">🎖️</span> Anniversaries
            </button>
        </div>
    </div>

    <!-- Birthday Tab Content -->
    <div id="tab-birthday" class="celebration-tab-content">
        <div class="section-title">
            <h2>🎂 This Month Birthdays</h2>
        </div>
        <!-- Birthday Section -->
        <div class="celebration-section">
            <div class="premium-grid">
                @php $birthdayCount = 0; @endphp
                @foreach ($employees as $employee)
                    @php
                        $birthday = \Carbon\Carbon::parse($employee->date_of_birth)->year(now()->year);
                        $today = now()->startOfDay();
                        $birthdayThisYear = $birthday->copy()->startOfDay();

                        if ($birthday->isPast() && !$birthday->isToday()) {
                            $birthday->addYear();
                        }
                        $daysRemaining = $today->diffInDays($birthday->startOfDay(), false);
                    @endphp

                    @if (\Carbon\Carbon::parse($employee->date_of_birth)->month == now()->month && $birthdayThisYear->gte($today))
                        @php $birthdayCount++; @endphp
                        <div class="premium-card-wrapper animate-card">
                            <div class="premium-card birthday-theme">
                                <div class="card-glow"></div>
                                <div class="premium-card-body">
                                    <div class="premium-profile-section">
                                        <div class="premium-avatar-container">
                                            @if($employee->photo)
                                                <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->name }}" class="avatar-img">
                                            @else
                                                <div class="avatar-initials">
                                                    {{ substr($employee->name, 0, 1) }}
                                                </div>
                                            @endif
                                            <div class="floating-icon birthday-icon">🎂</div>
                                        </div>
                                    </div>
                                    
                                    <div class="premium-info-section">
                                        <h3 class="emp-name">{{ $employee->name }}</h3>
                                        <p class="emp-label">Birthday Celebration</p>
                                        
                                        <div class="premium-card-footer">
                                            <div class="date-info">
                                                <span class="date-text">{{ $birthday->format('d M, Y') }}</span>
                                            </div>
                                            <div class="status-indicator {{ $daysRemaining == 0 ? 'is-today' : 'is-upcoming' }}">
                                                @if($daysRemaining == 0)
                                                    Today! 🎉
                                                @else
                                                    {{ $daysRemaining }} Days Left
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            @if($birthdayCount == 0)
                <div class="empty-state-premium">
                    <div class="empty-icon">🎂</div>
                    <h3>No Birthdays Soon</h3>
                    <p>No team members have birthdays in this month.</p>
                </div>
            @endif
        </div>

        <div class="section-title upcoming-title">
            <h2>📅 Upcoming Month Birthdays</h2>
        </div>
        <div class="celebration-section">
            <div class="premium-grid">
                @php $birthdayCount = 0; @endphp
                @foreach ($employees as $employee)
                    @php
                        $birthday = \Carbon\Carbon::parse($employee->date_of_birth)->year(now()->year);
                        if ($birthday->isPast() && !$birthday->isToday()) {
                            $birthday->addYear();
                        }
                        $today = now()->startOfDay();
                        $daysRemaining = $today->diffInDays($birthday->startOfDay(), false);
                    @endphp

                    @if (\Carbon\Carbon::parse($employee->date_of_birth)->month == now()->copy()->addMonth()->month)
                        @php $birthdayCount++; @endphp
                        <div class="premium-card-wrapper animate-card">
                            <div class="premium-card birthday-theme">
                                <div class="card-glow"></div>
                                <div class="premium-card-body">
                                    <div class="premium-profile-section">
                                        <div class="premium-avatar-container">
                                            @if($employee->photo)
                                                <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->name }}" class="avatar-img">
                                            @else
                                                <div class="avatar-initials">
                                                    {{ substr($employee->name, 0, 1) }}
                                                </div>
                                            @endif
                                            <div class="floating-icon birthday-icon">🎂</div>
                                        </div>
                                    </div>
                                    
                                    <div class="premium-info-section">
                                        <h3 class="emp-name">{{ $employee->name }}</h3>
                                        <p class="emp-label">Birthday Celebration</p>
                                        
                                        <div class="premium-card-footer">
                                            <div class="date-info">
                                                <span class="date-text">{{ $birthday->format('d M, Y') }}</span>
                                            </div>
                                            <div class="status-indicator {{ $daysRemaining == 0 ? 'is-today' : 'is-upcoming' }}">
                                                @if($daysRemaining == 0)
                                                    Today! 🎉
                                                @else
                                                    {{ $daysRemaining }} Days Left
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            @if($birthdayCount == 0)
                <div class="empty-state-premium">
                    <div class="empty-icon">🎂</div>
                    <h3>No Birthdays Soon</h3>
                    <p>No team members have birthdays in upcoming month.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Anniversary Tab Content -->
    <div id="tab-anniversary" class="celebration-tab-content d-none">
        <div class="section-title">
            <h2>🎖️ This Month Anniversaries</h2>
        </div>
        <div class="celebration-section">
            <div class="premium-grid">
                @php $anniversaryCount = 0; @endphp
                @foreach ($employees as $employee)
                    @php
                        $joiningDate = \Carbon\Carbon::parse($employee->date_of_joining);
                        $anniversary = $joiningDate->copy()->year(now()->year);
                        $today = now()->startOfDay();
                        $anniversaryThisYear = $anniversary->copy()->startOfDay();

                        if ($anniversary->isPast() && !$anniversary->isToday()) {
                            $anniversary->addYear();
                        }
                        $daysRemaining = $today->diffInDays($anniversary->startOfDay(), false);
                        $years = $joiningDate->diffInYears($anniversary);
                    @endphp

                    @if($years > 0 && \Carbon\Carbon::parse($employee->date_of_joining)->month == now()->month && $anniversaryThisYear->gte($today))
                        @php $anniversaryCount++; @endphp
                        <div class="premium-card-wrapper animate-card">
                            <div class="premium-card anniversary-theme">
                                <div class="card-glow"></div>
                                <div class="premium-card-body">
                                    <div class="premium-profile-section">
                                        <div class="premium-avatar-container">
                                            @if($employee->photo)
                                                <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->name }}" class="avatar-img">
                                            @else
                                                <div class="avatar-initials">
                                                    {{ substr($employee->name, 0, 1) }}
                                                </div>
                                            @endif
                                            <div class="floating-icon anniversary-icon">🏆</div>
                                        </div>
                                    </div>
                                    
                                    <div class="premium-info-section">
                                        <h3 class="emp-name">{{ $employee->name }}</h3>
                                        <p class="emp-label">{{ $years }}{{ $years == 1 ? 'st' : ($years == 2 ? 'nd' : ($years == 3 ? 'rd' : 'th')) }} Work Anniversary</p>
                                        
                                        <div class="premium-card-footer">
                                            <div class="date-info">
                                                <span class="date-text">{{ $anniversary->format('d M, Y') }}</span>
                                            </div>
                                            <div class="status-indicator {{ $daysRemaining == 0 ? 'is-today' : 'is-upcoming' }}">
                                                @if($daysRemaining == 0)
                                                    Today! 🎊
                                                @else
                                                    {{ $daysRemaining }} Days Left
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            @if($anniversaryCount == 0)
                <div class="empty-state-premium">
                    <div class="empty-icon">🏆</div>
                    <h3>No Anniversaries Soon</h3>
                    <p>No work anniversaries in this month.</p>
                </div>
            @endif
        </div>

        <div class="section-title upcoming-title">
            <h2>📅 Upcoming Month Anniversaries</h2>
        </div>
        <div class="celebration-section">
            <div class="premium-grid">
                @php $anniversaryCount = 0; @endphp
                @foreach ($employees as $employee)
                    @php
                        $joiningDate = \Carbon\Carbon::parse($employee->date_of_joining);
                        $anniversary = $joiningDate->copy()->year(now()->year);
                        if ($anniversary->isPast() && !$anniversary->isToday()) {
                            $anniversary->addYear();
                        }
                        $today = now()->startOfDay();
                        $daysRemaining = $today->diffInDays($anniversary->startOfDay(), false);
                        $years = $joiningDate->diffInYears($anniversary);
                    @endphp

                    @if($years > 0 && \Carbon\Carbon::parse($employee->date_of_joining)->month == now()->copy()->addMonth()->month)
                        @php $anniversaryCount++; @endphp
                        <div class="premium-card-wrapper animate-card">
                            <div class="premium-card anniversary-theme">
                                <div class="card-glow"></div>
                                <div class="premium-card-body">
                                    <div class="premium-profile-section">
                                        <div class="premium-avatar-container">
                                            @if($employee->photo)
                                                <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->name }}" class="avatar-img">
                                            @else
                                                <div class="avatar-initials">
                                                    {{ substr($employee->name, 0, 1) }}
                                                </div>
                                            @endif
                                            <div class="floating-icon anniversary-icon">🏆</div>
                                        </div>
                                    </div>
                                    
                                    <div class="premium-info-section">
                                        <h3 class="emp-name">{{ $employee->name }}</h3>
                                        <p class="emp-label">{{ $years }}{{ $years == 1 ? 'st' : ($years == 2 ? 'nd' : ($years == 3 ? 'rd' : 'th')) }} Work Anniversary</p>
                                        
                                        <div class="premium-card-footer">
                                            <div class="date-info">
                                                <span class="date-text">{{ $anniversary->format('d M, Y') }}</span>
                                            </div>
                                            <div class="status-indicator {{ $daysRemaining == 0 ? 'is-today' : 'is-upcoming' }}">
                                                @if($daysRemaining == 0)
                                                    Today! 🎊
                                                @else
                                                    {{ $daysRemaining }} Days Left
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            @if($anniversaryCount == 0)
                <div class="empty-state-premium">
                    <div class="empty-icon">🏆</div>
                    <h3>No Anniversaries Soon</h3>
                    <p>No work anniversaries in upcoming month.</p>
                </div>
            @endif
        </div>
    </div>

</div>
</div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

    .section-title {
        margin: 40px auto 25px;
        max-width: 1400px;
    }

    .section-title h2 {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 12px;
        letter-spacing: -0.01em;
    }

    .upcoming-title {
        margin-top: 60px;
        padding-top: 40px;
        border-top: 2px dashed #e2e8f0;
    }

    .celebrations-container {
        --bg-main: #f4f7f9;
        --card-bg: #ffffff;
        --primary: #1070e0;
        --secondary: #1a2138;
        --text-dark: #1a2138;
        --text-light: #616e88;
        --birthday-gradient: linear-gradient(135deg, #1070e0 0%, #1a2138 100%);
        --anniversary-gradient: linear-gradient(135deg, #1070e0 0%, #0d5bb8 100%);
        --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.04);
        --shadow-strong: 0 20px 40px -12px rgba(16, 112, 224, 0.08);
        
        padding: 30px;
        font-family: 'Outfit', sans-serif;
        box-sizing: border-box;
    }

    /* Fix for squashed layout on mobile */
    @media (max-width: 1024px) {
        .nxl-container {
            margin-left: 0 !important;
            padding-left: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        
        .nxl-content {
            padding: 0 !important;
            margin: 0 !important;
        }
    }

    .celebrations-container * {
        box-sizing: border-box;
    }

    /* Hero Header */
    .celebrations-hero {
        text-align: center;
        margin-bottom: 50px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .hero-title {
        font-size: 42px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 12px;
        letter-spacing: -0.02em;
    }

    .gradient-text {
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero-subtitle {
        font-size: 16px;
        color: var(--text-light);
        margin-bottom: 35px;
    }

    /* Switcher Pill */
    .celebration-switcher {
        display: inline-flex;
        background: #f1f5f9;
        padding: 5px;
        border-radius: 30px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.03);
        gap: 4px;
        border: 1px solid #e2e8f0;
    }

    .switcher-btn {
        border: none;
        background: transparent;
        padding: 10px 28px;
        border-radius: 25px;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-light);
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .switcher-btn:hover {
        color: var(--text-dark);
    }

    .switcher-btn .icon {
        font-size: 16px;
        transition: transform 0.25s ease;
    }

    .switcher-btn:hover .icon {
        transform: scale(1.15) rotate(-10deg);
    }

    .switcher-btn.active {
        background: #ffffff;
        color: var(--primary);
        box-shadow: 0 4px 10px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.02);
    }

    /* Grid - 3 per row */
    .premium-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 32px;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Premium Card */
    .premium-card-wrapper {
        perspective: 2000px;
    }

    .premium-card {
        background: var(--card-bg);
        border-radius: 24px;
        padding: 28px;
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: var(--shadow-soft);
        border: 1px solid #f1f5f9;
        min-height: 180px;
    }

    .premium-card.birthday-theme {
        --theme-color: #1070e0;
        --theme-bg-light: #eef4fc;
        --theme-border-light: #c5daf5;
        --theme-glow: radial-gradient(circle, rgba(16, 112, 224, 0.04) 0%, transparent 70%);
        --theme-glow-hover: radial-gradient(circle, rgba(16, 112, 224, 0.08) 0%, transparent 70%);
    }

    .premium-card.anniversary-theme {
        --theme-color: #0d5bb8;
        --theme-bg-light: #eef4fc;
        --theme-border-light: #c5daf5;
        --theme-glow: radial-gradient(circle, rgba(13, 91, 184, 0.04) 0%, transparent 70%);
        --theme-glow-hover: radial-gradient(circle, rgba(13, 91, 184, 0.08) 0%, transparent 70%);
    }

    .premium-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-strong);
        border-color: var(--theme-border-light);
    }

    .card-glow {
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: var(--theme-glow);
        pointer-events: none;
        transition: background 0.4s ease;
    }

    .premium-card:hover .card-glow {
        background: var(--theme-glow-hover);
    }

    .premium-card-body {
        display: flex;
        align-items: center;
        gap: 24px;
        position: relative;
        z-index: 1;
    }

    /* Profile Section */
    .premium-profile-section {
        flex-shrink: 0;
    }

    .premium-avatar-container {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        padding: 4px;
        background: #fff;
        box-shadow: 0 8px 16px rgba(0,0,0,0.03);
        position: relative;
        border: 2px solid var(--theme-border-light);
        transition: border-color 0.4s ease;
    }

    .premium-card:hover .premium-avatar-container {
        border-color: var(--theme-color);
    }

    .avatar-img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .avatar-initials {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--theme-color) 0%, var(--secondary) 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: 700;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
    }

    .premium-card.anniversary-theme .avatar-initials {
        background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
    }

    .floating-icon {
        position: absolute;
        bottom: -2px;
        right: -2px;
        background: #fff;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        border: 2px solid #fff;
        z-index: 10;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .premium-card:hover .floating-icon {
        transform: scale(1.15) rotate(15deg);
        box-shadow: 0 6px 14px rgba(0,0,0,0.15);
    }

    /* Info Section */
    .premium-info-section {
        flex: 1;
        min-width: 0;
    }

    .emp-name {
        font-size: 19px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 4px;
        letter-spacing: -0.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .emp-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-light);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 16px;
    }

    .premium-card-footer {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .date-info {
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--text-light);
        font-weight: 500;
    }

    .date-text {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
    }

    .status-indicator {
        display: inline-flex;
        padding: 5px 12px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        width: fit-content;
    }

    .is-today {
        background: #fff1f2;
        color: #e11d48;
        border: 1px solid #fecdd3;
        animation: pulse-border 2s infinite;
    }

    .is-upcoming {
        background: var(--theme-bg-light);
        color: var(--theme-color);
        border: 1px solid var(--theme-border-light);
    }

    @keyframes pulse-border {
        0% { box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.3); }
        70% { box-shadow: 0 0 0 8px rgba(225, 29, 72, 0); }
        100% { box-shadow: 0 0 0 0 rgba(225, 29, 72, 0); }
    }

    /* Animations */
    .animate-card {
        animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive */
    @media (max-width: 1400px) {
        .premium-grid {
            max-width: 100%;
            gap: 24px;
        }
    }

    @media (max-width: 1200px) {
        .premium-grid { grid-template-columns: repeat(2, 1fr); }
        .hero-title { font-size: 36px; }
    }

    @media (max-width: 992px) {
        .celebrations-container { padding: 25px; }
        .hero-title { font-size: 32px; }
    }

    @media (max-width: 768px) {
        .premium-grid { 
            grid-template-columns: 1fr; 
            gap: 20px; 
        }
        .celebrations-container { padding: 20px 15px; }
        .celebrations-hero { margin-bottom: 35px; }
        .hero-title { font-size: 28px; }
        .hero-subtitle { font-size: 14px; margin-bottom: 25px; }
        
        .celebration-switcher {
            width: 100%;
            display: flex;
        }
        
        .switcher-btn {
            padding: 10px 16px;
            font-size: 13px;
            flex: 1;
            justify-content: center;
        }

        .premium-card {
            padding: 24px;
            border-radius: 20px;
        }

        .premium-card-body {
            gap: 20px;
        }

        .premium-avatar-container {
            width: 80px;
            height: 80px;
        }

        .avatar-initials {
            font-size: 28px;
        }

        .floating-icon {
            width: 30px;
            height: 30px;
            font-size: 16px;
        }

        .emp-name {
            font-size: 18px;
        }
    }

    @media (max-width: 600px) {
        .celebration-switcher {
            flex-direction: column;
            width: 100%;
            max-width: 280px;
            border-radius: 20px;
            padding: 4px;
        }
        
        .switcher-btn {
            width: 100%;
            border-radius: 16px;
            padding: 10px;
        }

        .premium-card-body { 
            flex-direction: column; 
            text-align: center; 
            gap: 16px;
        }

        .premium-card-footer {
            align-items: center;
        }

        .status-indicator {
            margin: 0 auto;
        }
        
        .empty-state-premium {
            padding: 40px 20px;
            border-radius: 20px;
        }

        .empty-icon {
            font-size: 48px;
        }

        .empty-state-premium h3 {
            font-size: 18px;
        }
    }

    .d-none { display: none !important; }

    /* Empty State */
    .empty-state-premium {
        background: #ffffff;
        border-radius: 28px;
        padding: 60px 40px;
        text-align: center;
        border: 2px dashed #e2e8f0;
        margin-top: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.01);
    }

    .empty-icon {
        font-size: 64px;
        margin-bottom: 16px;
        animation: float-emoji 3s ease-in-out infinite;
    }

    @keyframes float-emoji {
        0%, 100% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-8px) scale(1.08); }
    }

    .empty-state-premium h3 {
        font-size: 22px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 8px;
    }

    .empty-state-premium p {
        font-size: 15px;
        color: var(--text-light);
        max-width: 360px;
        margin: 0;
    }
</style>

<script>
    function switchCelebrationTab(tabId, el) {
        document.querySelectorAll('.switcher-btn').forEach(btn => btn.classList.remove('active'));
        el.classList.add('active');

        document.querySelectorAll('.celebration-tab-content').forEach(sec => sec.classList.add('d-none'));
        const target = document.getElementById('tab-' + tabId);
        target.classList.remove('d-none');

        // Re-trigger animations
        const cards = target.querySelectorAll('.animate-card');
        cards.forEach((card, index) => {
            card.style.animation = 'none';
            card.offsetHeight; // reflow
            card.style.animation = null;
            card.style.animationDelay = (index * 0.08) + 's';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const initialCards = document.querySelectorAll('#tab-birthday .animate-card');
        initialCards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.08) + 's';
        });
    });
</script>

@endsection