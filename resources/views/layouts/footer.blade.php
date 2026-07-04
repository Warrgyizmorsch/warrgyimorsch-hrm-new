<footer class="footer">
    <p class="fs-11 text-muted fw-medium mb-0 copyright">
        <span>Copyright © {{ date('Y') }} Warrgyizmorsch Pvt Ltd</span>
    </p>
    <div class="d-flex align-items-center gap-4">
        <a href="{{ route('tickets.index') }}" class="fs-11 fw-semibold text-uppercase">Need Support?</a>
        <a href="{{ url('/help') }}" class="fs-11 fw-semibold text-uppercase">Help</a>
        <a href="{{ url('/terms') }}" class="fs-11 fw-semibold text-uppercase">Terms</a>
        <a href="{{ url('/privacy') }}" class="fs-11 fw-semibold text-uppercase">Privacy</a>
    </div>
</footer>