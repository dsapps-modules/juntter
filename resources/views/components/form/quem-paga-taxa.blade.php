<div class="col-md-6 mb-3">
    <label class="form-label fw-bold">
        Quem paga as taxas <span class="text-danger">*</span>
    </label>
    <select name="interest" class="form-select" required>
        <option value="">Selecione...</option>
        <option {{ old('interest') == 'CLIENT' ? 'selected' : '' }} value="CLIENT">Cliente</option>
        <option {{ old('interest') == 'ESTABLISHMENT' ? 'selected' : '' }} value="ESTABLISHMENT">Estabelecimento</option>
    </select>
</div>
