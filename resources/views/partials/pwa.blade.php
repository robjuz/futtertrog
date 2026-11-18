<link rel="manifest" href="{{ route('laravelpwa.manifest') }}">
<meta name="theme-color" content="#115e59">
<link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/serviceworker.js', { scope: '/', updateViaCache: 'none' })
                .then(function (registration) {
                    registration.addEventListener('updatefound', function () {
                        const worker = registration.installing;
                        const hadController = !!navigator.serviceWorker.controller;
                        if (worker) worker.addEventListener('statechange', function () {
                            if (worker.state === 'activated' && hadController) {
                                window.dispatchEvent(new Event('futtertrog:update'));
                            }
                        });
                    });
                    registration.update().catch(function () {});
                }).catch(function () {});
        });
    }
</script>
