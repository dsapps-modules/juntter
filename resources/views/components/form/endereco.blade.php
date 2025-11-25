<div class="card bg-light border-0 mb-4">
    <div class="card-body">
        <h6 class="fw-bold text-uppercase small text-muted mb-3">
            ENDEREÇO DO CLIENTE <span class="text-danger">(OBRIGATÓRIO)</span>
        </h6>
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label fw-bold">
                    Rua <span class="text-danger">*</span>
                </label>
                <input type="text" name="client[address][street]" value="{{ old('client.address.street') }}"
                    class="form-control" placeholder="Nome da rua" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">
                    Número <span class="text-danger">*</span>
                </label>
                <input type="text" name="client[address][number]" value="{{ old('client.address.number') }}"
                    class="form-control" placeholder="123" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Complemento</label>
                <input type="text" name="client[address][complement]" value="{{ old('client.address.complement') }}"
                    class="form-control" placeholder="Apto 101">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">
                    Bairro <span class="text-danger">*</span>
                </label>
                <input type="text" name="client[address][neighborhood]"
                    value="{{ old('client.address.neighborhood') }}" class="form-control" placeholder="Centro" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">
                    CEP <span class="text-danger">*</span>
                </label>
                <input type="text" name="client[address][zip_code]" value="{{ old('client.address.zip_code') }}"
                    class="form-control" placeholder="00000-000" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label fw-bold">
                    Cidade <span class="text-danger">*</span>
                </label>
                <input type="text" name="client[address][city]" value="{{ old('client.address.city') }}"
                    class="form-control" placeholder="Nome da cidade" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">
                    Estado <span class="text-danger">*</span>
                </label>
                <select name="client[address][state]" class="form-select" required>
                    <option value="">Selecione...</option>
                    <option value="AC" {{ old('client.address.state') == 'AC' ? 'selected' : '' }}>Acre</option>
                    <option value="AL" {{ old('client.address.state') == 'AL' ? 'selected' : '' }}>Alagoas</option>
                    <option value="AP" {{ old('client.address.state') == 'AP' ? 'selected' : '' }}>Amapá</option>
                    <option value="AM" {{ old('client.address.state') == 'AM' ? 'selected' : '' }}>Amazonas</option>
                    <option value="BA" {{ old('client.address.state') == 'BA' ? 'selected' : '' }}>Bahia</option>
                    <option value="CE" {{ old('client.address.state') == 'CE' ? 'selected' : '' }}>Ceará</option>
                    <option value="DF" {{ old('client.address.state') == 'DF' ? 'selected' : '' }}>Distrito Federal
                    </option>
                    <option value="ES" {{ old('client.address.state') == 'ES' ? 'selected' : '' }}>Espírito Santo
                    </option>
                    <option value="GO" {{ old('client.address.state') == 'GO' ? 'selected' : '' }}>Goiás</option>
                    <option value="MA" {{ old('client.address.state') == 'MA' ? 'selected' : '' }}>Maranhão
                    </option>
                    <option value="MT" {{ old('client.address.state') == 'MT' ? 'selected' : '' }}>Mato Grosso
                    </option>
                    <option value="MS" {{ old('client.address.state') == 'MS' ? 'selected' : '' }}>Mato Grosso do
                        Sul</option>
                    <option value="MG" {{ old('client.address.state') == 'MG' ? 'selected' : '' }}>Minas Gerais
                    </option>
                    <option value="PA" {{ old('client.address.state') == 'PA' ? 'selected' : '' }}>Pará</option>
                    <option value="PB" {{ old('client.address.state') == 'PB' ? 'selected' : '' }}>Paraíba</option>
                    <option value="PR" {{ old('client.address.state') == 'PR' ? 'selected' : '' }}>Paraná</option>
                    <option value="PE" {{ old('client.address.state') == 'PB' ? 'selected' : '' }}>Pernambuco
                    </option>
                    <option value="PI" {{ old('client.address.state') == 'PI' ? 'selected' : '' }}>Piauí</option>
                    <option value="RJ" {{ old('client.address.state') == 'RJ' ? 'selected' : '' }}>Rio de Janeiro
                    </option>
                    <option value="RN" {{ old('client.address.state') == 'RN' ? 'selected' : '' }}>Rio Grande do
                        Norte</option>
                    <option value="RS" {{ old('client.address.state') == 'RS' ? 'selected' : '' }}>Rio Grande do
                        Sul
                    </option>
                    <option value="RO" {{ old('client.address.state') == 'RO' ? 'selected' : '' }}>Rondônia
                    </option>
                    <option value="RR" {{ old('client.address.state') == 'RR' ? 'selected' : '' }}>Roraima</option>
                    <option value="SC" {{ old('client.address.state') == 'SC' ? 'selected' : '' }}>Santa Catarina
                    </option>
                    <option value="SP" {{ old('client.address.state') == 'SP' ? 'selected' : '' }}>São Paulo
                    </option>
                    <option value="SE" {{ old('client.address.state') == 'SE' ? 'selected' : '' }}>Sergipe</option>
                    <option value="TO" {{ old('client.address.state') == 'TO' ? 'selected' : '' }}>Tocantins
                    </option>
                </select>
            </div>
        </div>
    </div>
</div>
