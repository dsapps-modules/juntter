<?php

namespace App\View\Components\Form;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PaymentSelector extends Component
{
    public function __construct(
        public string $plan,
        public array $rates,
        public string $selected = '6x',
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.form.payment-selector');
    }
}
