<select class="form-control payment-selector" data-plan="{{ $plan }}">
    @foreach ($rates as $installments => $rate)
        <option value="{{ $installments }}" data-rate="{{ number_format((float) $rate, 2, '.', '') }}"
            @selected((string) $installments === (string) $selected)>
            Parcelado {{ $installments }}
        </option>
    @endforeach
</select>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const paymentSelectors = document.querySelectorAll('.payment-selector');

                paymentSelectors.forEach(selector => {
                    selector.addEventListener('change', function() {
                        const selectedOption = this.options[this.selectedIndex];
                        const rate = selectedOption.getAttribute('data-rate');
                        const planName = this.getAttribute('data-plan');
                        const parcelas = selectedOption.value;

                        const priceElement = document.querySelector(
                            `.parcelado-rate[data-plan="${planName}"]`);
                        const labelElement = document.querySelector(
                            `.parcelado-label[data-plan="${planName}"]`);

                        if (priceElement && rate) {
                            priceElement.textContent = rate + '%';
                        }

                        if (labelElement && parcelas) {
                            labelElement.textContent = `Parcelado ${parcelas}`;
                        }
                    });
                });
            });
        </script>
    @endpush
@endonce
