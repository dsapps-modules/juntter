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
                <select name="card[expiration_month]" id="expiration_month" class="form-select" required>
                    <option value="">MM</option>
                    @php
                        $currentMonth = (int) date('m');
                        $selectedYear = old('card.expiration_year', date('Y'));
                        $isCurrentYear = $selectedYear == date('Y');
                    @endphp
                    @for ($i = 1; $i <= 12; $i++)
                        @php
                            $monthValue = str_pad($i, 2, '0', STR_PAD_LEFT);
                            $isHidden = $isCurrentYear && $i < $currentMonth;
                        @endphp
                        <option value="{{ $i }}" 
                            {{ old('card.expiration_month') == $i ? 'selected' : '' }}
                            {{ $isHidden ? 'style=display:none disabled' : '' }}>
                            {{ $monthValue }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label fw-bold">
                    Ano <span class="text-danger">*</span>
                </label>
                <select name="card[expiration_year]" id="expiration_year" class="form-select" required>
                    <option value="">AAAA</option>
                    @php
                        $currentYear = (int) date('Y');
                    @endphp
                    @foreach (range($currentYear, $currentYear + 9) as $year)
                        <option value="{{ $year }}" {{ old('card.expiration_year', $currentYear) == $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const monthSelect = document.getElementById('expiration_month');
        const yearSelect = document.getElementById('expiration_year');
        const currentYear = new Date().getFullYear();
        const currentMonth = new Date().getMonth() + 1;

        function updateMonths() {
            const selectedYear = parseInt(yearSelect.value);
            const isCurrentYear = selectedYear === currentYear;

            Array.from(monthSelect.options).forEach(option => {
                if (option.value === "") return;

                const monthValue = parseInt(option.value);
                if (isCurrentYear && monthValue < currentMonth) {
                    option.style.display = 'none';
                    option.disabled = true;
                    if (monthSelect.value == option.value) {
                        monthSelect.value = "";
                    }
                } else {
                    option.style.display = 'block';
                    option.disabled = false;
                }
            });
        }

        yearSelect.addEventListener('change', updateMonths);
        
        // Initial call to set state correctly on load
        if (yearSelect.value) {
            updateMonths();
        } else {
            // Default to current year if requested by user
            yearSelect.value = currentYear;
            updateMonths();
        }
    });
</script>