<style>
    .login-left {
        position: relative;
        overflow: hidden;
        color: #fff;
        padding: 3rem;
        background: linear-gradient(
            160deg,
            #0093E5 0%,
            #1c6fa8 60%,
            #045199 100%
        );
    }

    .login-left-content {
        position: relative;
        z-index: 1;
        max-width: 420px;
        transform: translateX(-3rem);
    }

    #business_icon {
        position: absolute;
        width: 20rem;

        right: -5rem;
        bottom: 2rem;

        opacity: 0.5;

        z-index: -1;
    }

    .swimming-title {
        padding-left: 5rem;
        margin-top: -1.5rem;
    }

</style>

<div class="login-left col-xl-6 d-none d-xl-flex align-items-center justify-content-center">
    <div class="login-left-content">
        <img 
            id="business_icon"
            src="{{ asset('assets/images/icon_fbss.png') }}" 
            alt="">
    
        <h1 class="display-2 fw-bold mb-3">Learn</h1>
        <h1 class="display-2 fw-bold mb-3 swimming-title">Swimming</h1>

        <p class="fs-5 mb-0">
            Build skills. Build confidence. For life.
        </p>
    </div>
</div>
