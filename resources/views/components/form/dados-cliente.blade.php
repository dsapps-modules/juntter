@props(['type' => 'opcional', 'usePhone' => true])

<div class="card bg-light border-0 mb-4">
    <div class="card-body">
        <h6 class="fw-bold text-uppercase small text-muted mb-3">
            DADOS DO CLIENTE <span class="text-muted">({!! $type == 'opcional' ? 'Opcional' : '<span class="text-danger">Obrigatório</span>' !!}
                )</span>
        </h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Nome do
                    cliente {!! $type == 'opcional' ? '' : '<span class="text-danger">*</span>' !!}</label>
                <input type="text" name="client[first_name]" value="{{ old('client.first_name') }}" class="form-control"
                    placeholder="Nome completo">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Sobrenome {!! $type == 'opcional' ? '' : '<span class="text-danger">*</span>' !!}</label>
                <input type="text" name="client[last_name]" value="{{ old('client.last_name') }}"
                    class="form-control" placeholder="Sobrenome">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">CPF/CNPJ {!! $type == 'opcional' ? '' : '<span class="text-danger">*</span>' !!}</label>
                <input type="text" name="client[document]" value="{{ old('client.document') }}" class="form-control"
                    placeholder="000.000.000-00">
            </div>
            @if ($usePhone)
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Telefone {!! $type == 'opcional' ? '' : '<span class="text-danger">*</span>' !!}</label>
                    <input type="text" name="client[phone]" value="{{ old('client.phone') }}" class="form-control"
                        placeholder="(00) 00000-0000">
                </div>
            @endif
            <div class="col-md-{{ $usePhone ? 12 : 6 }} mb-3">
                <label class="form-label fw-bold">Email {!! $type == 'opcional' ? '' : '<span class="text-danger">*</span>' !!}</label>
                <input type="email" name="client[email]" value="{{ old('client.email') }}" class="form-control"
                    placeholder="email@exemplo.com">
            </div>
        </div>
    </div>
</div>
