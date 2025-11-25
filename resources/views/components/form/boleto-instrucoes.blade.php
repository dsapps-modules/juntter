<div class="card bg-light border-0 mb-4">
    <div class="card-body">
        <h6 class="fw-bold text-uppercase small text-muted mb-3">
            INSTRUÇÕES DO BOLETO <span class="text-danger">(OBRIGATÓRIO)</span>
        </h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">
                    É carnê? <span class="text-danger">*</span>
                </label>
                <select name="instruction[booklet]" class="form-select" required>
                    <option value="0" {{ old('instruction.booklet') == '0' ? 'selected' : '' }}>Não
                    </option>
                    <option value="1" {{ old('instruction.booklet') == '0' ? 'selected' : '' }}>Sim
                    </option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Descrição</label>
                <input type="text" name="instruction[description]" value="{{ old('instruction.description') }}"
                    class="form-control" placeholder="Descrição do boleto">
                <small class="text-muted">Opcional - Descrição exibida no
                    boleto</small>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">
                    Multa por atraso <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <input type="text" name="instruction[late_fee][amount]"
                        value="{{ old('instruction.late_fee.amount') }}" class="form-control" placeholder="2,00"
                        required style="width: 80%;">
                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                </div>
                <small class="text-muted">Ex: 2,00 para 2%</small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">
                    Juros ao mês <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <input type="text" name="instruction[interest][amount]"
                        value="{{ old('instruction.interest.amount') }}" class="form-control" placeholder="1,00"
                        required style="width: 80%;">
                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                </div>
                <small class="text-muted">Ex: 1,00 para 1%</small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">
                    Desconto <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <input type="text" name="instruction[discount][amount]"
                        value="{{ old('instruction.discount.amount') }}" class="form-control" placeholder="5,00"
                        required style="width: 80%;">
                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                </div>
                <small class="text-muted">Ex: 5,00 para 5%</small>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">
                Data limite para desconto <span class="text-danger">*</span>
            </label>
            <input type="date" name="instruction[discount][limit_date]"
                value="{{ old('instruction.discount.limit_date') }}" id="discount_limit_date" class="form-control"
                required>
        </div>
    </div>
</div>
