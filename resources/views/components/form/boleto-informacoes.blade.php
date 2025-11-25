<div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">
                Valor do boleto <span class="text-danger">*</span>
            </label>
            <input type="text" name="amount" value="{{ old('amount') }}" class="form-control" placeholder="0,00"
                required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">
                Data de vencimento <span class="text-danger">*</span>
            </label>
            <input type="date" name="expiration" value="{{ old('expiration') }}" class="form-control" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Data limite para pagamento</label>
            <input type="date" name="payment_limit_date" value="{{ old('payment_limit_date') }}"
                class="form-control">
            <small class="text-muted">Opcional - Data limite após o vencimento</small>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">É para recarga?</label>
            <select name="recharge" class="form-select">
                <option value="0" {{ old('recharge') == '0' ? 'selected' : '' }}>Não</option>
                <option value="1" {{ old('recharge') == '1' ? 'selected' : '' }}>Sim</option>
            </select>
            <small class="text-muted">Opcional - Para carteiras digitais</small>
        </div>
    </div>
</div>
