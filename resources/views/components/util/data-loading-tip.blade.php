@props(['text' => 'Processando pagamento...'])

<div id="loading" class="loading-overlay" style="display: none;">
    <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
        <div class="text-center text-white">
            <div class="spinner-border spinner-custom mb-3" style="width: 3rem; height: 3rem;"></div>
            <h5>{{ $text }}</h5>
            <p class="mb-0">Aguarde um momento</p>
        </div>
    </div>
</div>
