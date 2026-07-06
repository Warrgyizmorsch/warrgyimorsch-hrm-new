@if(!empty($celebration['showBirthdayCard']))
    @once
        <style>
            .birthday-wish-card {
                position: relative;
            }

            .birthday-wish-card .celebration-icon {
                animation: birthdayIconPulse 2s ease-in-out infinite;
            }

            .birthday-wish-card .birthday-sparkle {
                position: absolute;
                font-size: 16px;
                opacity: 0;
                pointer-events: none;
                animation: birthdaySparkle 3s ease-in-out infinite;
            }

            .birthday-wish-card .birthday-sparkle.s1 { top: 12px; left: 18%; animation-delay: 0s; }
            .birthday-wish-card .birthday-sparkle.s2 { top: 20px; right: 22%; animation-delay: 0.8s; }
            .birthday-wish-card .birthday-sparkle.s3 { bottom: 14px; left: 42%; animation-delay: 1.4s; }
            .birthday-wish-card .birthday-sparkle.s4 { top: 50%; right: 12%; animation-delay: 2s; }

            .birthday-wish-close {
                position: absolute;
                top: 12px;
                right: 12px;
                z-index: 5;
                width: 32px;
                height: 32px;
                border: none;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.92);
                color: #64748b;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
                transition: background 0.15s ease, color 0.15s ease, transform 0.15s ease;
            }

            .birthday-wish-close:hover {
                background: #fff;
                color: #4338ca;
                transform: scale(1.05);
            }

            .birthday-wish-card.is-closing {
                animation: birthdayCardClose 0.45s ease forwards;
            }

            body.birthday-wish-active .birthday-wish-card .balloon,
            body.birthday-wish-active .birthday-wish-card .confetti-piece {
                animation-play-state: running;
            }

            @keyframes birthdayIconPulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.08) rotate(-4deg); }
            }

            @keyframes birthdaySparkle {
                0%, 100% { opacity: 0; transform: translateY(0) scale(0.8); }
                50% { opacity: 1; transform: translateY(-8px) scale(1); }
            }

            @keyframes birthdayCardClose {
                to {
                    opacity: 0;
                    transform: translateY(-12px) scale(0.98);
                }
            }
        </style>
    @endonce

    <div id="birthdayWishCard"
        class="celebration-banner birthday birthday-wish-card"
        data-dismiss-url="{{ route('dashboard.birthday-wish.dismiss') }}">
        <button type="button" class="birthday-wish-close" id="birthdayWishCloseBtn" aria-label="Close birthday wishes">
            <i class="feather-x"></i>
        </button>

        <div class="animation-container">
            <span class="balloon b1">🎈</span>
            <span class="balloon b2">🎈</span>
            <span class="balloon b3">🎈</span>
            <span class="balloon b4">🎈</span>
            <span class="confetti-piece cp1"></span>
            <span class="confetti-piece cp2"></span>
            <span class="confetti-piece cp3"></span>
            <span class="confetti-piece cp4"></span>
            <span class="confetti-piece cp5"></span>
            <span class="birthday-sparkle s1">✨</span>
            <span class="birthday-sparkle s2">🎉</span>
            <span class="birthday-sparkle s3">🎊</span>
            <span class="birthday-sparkle s4">⭐</span>
        </div>

        <div class="celebration-icon">🎂</div>
        <div class="celebration-content pe-5">
            @if($celebration['isBirthdayToday'])
                <h3>Happy Birthday, {{ $employee->name }}! 🎉</h3>
                <p>Wishing you a wonderful day filled with happiness, success, and great health.</p>
            @elseif(($celebration['daysUntilBirthday'] ?? 0) > 0)
                <h3>It&apos;s your birthday month, {{ $employee->name }}! 🎂</h3>
                <p>
                    Your birthday is on {{ $celebration['birthdayFormatted'] }} —
                    {{ (int) $celebration['daysUntilBirthday'] }} day{{ (int) $celebration['daysUntilBirthday'] === 1 ? '' : 's' }} to go.
                    Enjoy the celebrations all month long!
                </p>
            @else
                <h3>Happy birthday month, {{ $employee->name }}! 🎉</h3>
                <p>
                    Your birthday was on {{ $celebration['birthdayFormatted'] }}
                    @if(($celebration['daysSinceBirthday'] ?? 0) > 0)
                        ({{ (int) $celebration['daysSinceBirthday'] }} day{{ (int) $celebration['daysSinceBirthday'] === 1 ? '' : 's' }} ago)
                    @endif
                    — hope it was amazing! We&apos;re still celebrating you this week.
                </p>
            @endif
        </div>
    </div>

    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const card = document.getElementById('birthdayWishCard');
                const closeBtn = document.getElementById('birthdayWishCloseBtn');

                if (!card || !closeBtn) {
                    return;
                }

                document.body.classList.add('birthday-wish-active');

                closeBtn.addEventListener('click', function () {
                    if (card.dataset.closing === '1') {
                        return;
                    }

                    card.dataset.closing = '1';
                    card.classList.add('is-closing');
                    document.body.classList.remove('birthday-wish-active');

                    const token = document.querySelector('meta[name="csrf-token"]')?.content;
                    const dismissUrl = card.dataset.dismissUrl;

                    fetch(dismissUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({}),
                    }).catch(function () {
                        /* Keep card dismissed in UI even if the request fails. */
                    });

                    setTimeout(function () {
                        card.remove();
                    }, 450);
                });
            });
        </script>
    @endonce
@endif
