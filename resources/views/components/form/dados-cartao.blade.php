<div class="card bg-light border-0 mb-4">
    <div class="card-body">
        <h6 class="fw-bold text-uppercase small text-muted mb-3">
            DADOS DO CARTÃO <span class="text-danger">(OBRIGATÓRIO)</span>
        </h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">
                    Nome do titular <span class="text-danger">*</span>
                </label>
                <input type="text" name="card[holder_name]" value="{{ old('card.holder_name') }}" class="form-control"
                    placeholder="Nome completo" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">CPF/CNPJ do titular</label>
                <input type="text" name="card[holder_document]" value="{{ old('card.holder_document') }}"
                    class="form-control" placeholder="000.000.000-00">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">
                    Número do cartão <span class="text-danger">*</span>
                </label>
                <input type="text" name="card[card_number]" value="{{ old('card.card_number') }}" minlength="13"
                    maxlength="16" class="form-control" placeholder="Apenas números, sem espaços" required>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-bold">
                    Mês <span class="text-danger">*</span>
                </label>
                <select name="card[expiration_month]" class="form-select" required>
                    <option value="">MM</option>
                    <option value="1" {{ old('card.expiration_month') == 1 ? 'selected' : '' }}>01</option>
                    <option value="2" {{ old('card.expiration_month') == 2 ? 'selected' : '' }}>02</option>
                    <option value="3" {{ old('card.expiration_month') == 3 ? 'selected' : '' }}>03</option>
                    <option value="4" {{ old('card.expiration_month') == 4 ? 'selected' : '' }}>04</option>
                    <option value="5" {{ old('card.expiration_month') == 5 ? 'selected' : '' }}>05</option>
                    <option value="6" {{ old('card.expiration_month') == 6 ? 'selected' : '' }}>06</option>
                    <option value="7" {{ old('card.expiration_month') == 7 ? 'selected' : '' }}>07</option>
                    <option value="8" {{ old('card.expiration_month') == 8 ? 'selected' : '' }}>08</option>
                    <option value="9" {{ old('card.expiration_month') == 9 ? 'selected' : '' }}>09</option>
                    <option value="10" {{ old('card.expiration_month') == 10 ? 'selected' : '' }}>10</option>
                    <option value="11" {{ old('card.expiration_month') == 11 ? 'selected' : '' }}>11</option>
                    <option value="12" {{ old('card.expiration_month') == 12 ? 'selected' : '' }}>12</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-bold">
                    Ano <span class="text-danger">*</span>
                </label>
                <select name="card[expiration_year]" class="form-select" required>
                    <option value="">AAAA</option>
                    <option value="2025" {{ old('card.expiration_year') == 2025 ? 'selected' : '' }}>2025</option>
                    <option value="2026" {{ old('card.expiration_year') == 2026 ? 'selected' : '' }}>2026</option>
                    <option value="2027" {{ old('card.expiration_year') == 2027 ? 'selected' : '' }}>2027</option>
                    <option value="2028" {{ old('card.expiration_year') == 2028 ? 'selected' : '' }}>2028</option>
                    <option value="2029" {{ old('card.expiration_year') == 2029 ? 'selected' : '' }}>2029</option>
                    <option value="2030" {{ old('card.expiration_year') == 2030 ? 'selected' : '' }}>2030</option>
                    <option value="2031" {{ old('card.expiration_year') == 2031 ? 'selected' : '' }}>2031</option>
                    <option value="2032" {{ old('card.expiration_year') == 2032 ? 'selected' : '' }}>2032</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-bold">
                    CVV <span class="text-danger">*</span>
                </label>
                <input type="text" name="card[security_code]" value="{{ old('card.security_code') }}"
                    class="form-control" placeholder="000" required>
            </div>
        </div>
    </div>
</div>
