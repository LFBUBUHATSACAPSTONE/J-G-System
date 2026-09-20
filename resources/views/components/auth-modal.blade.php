<div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true" data-current-view="login">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                {{-- Left panel: carousel (shared across all views) --}}
                <div class="auth-modal__panel-left">
                    <div id="authCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="{{ asset('images/carousel/3.webp') }}" alt="">
                                <p class="auth-modal__tagline">Plan it. Light it. Sound it.</p>
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carousel/5.webp') }}" alt="">
                                <p class="auth-modal__tagline">Plan loud. Shine bright.</p>
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carousel/7.webp') }}" alt="">
                                <p class="auth-modal__tagline">Bright beats. Smooth events.</p>
                            </div>
                        </div>
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#authCarousel" data-bs-slide-to="0" class="active"></button>
                            <button type="button" data-bs-target="#authCarousel" data-bs-slide-to="1"></button>
                            <button type="button" data-bs-target="#authCarousel" data-bs-slide-to="2"></button>
                        </div>
                    </div>
                    <a href="{{ url('/user/landing') }}" class="auth-modal__back-link">Back to Website</a>
                </div>

                {{-- Right panel: swappable form views --}}
                <div class="auth-modal__panel-right">

                    {{-- View: Login --}}
                    <div class="auth-modal__view" data-view="login">
                        @include('components.auth.login-form')
                    </div>

                    {{-- View: Sign Up --}}
                    <div class="auth-modal__view d-none" data-view="signup">
                        @include('components.auth.signup-form')
                    </div>

                    {{-- View: Forgot Password --}}
                    <div class="auth-modal__view d-none" data-view="forgot-password">
                        @include('components.auth.forgot-password-form')
                    </div>

                    {{-- View: Verification --}}
                    <div class="auth-modal__view d-none" data-view="verification">
                        @include('components.auth.verification-form')
                    </div>

                    {{-- View: New Password --}}
                    <div class="auth-modal__view d-none" data-view="new-password">
                        @include('components.auth.new-password-form')
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>