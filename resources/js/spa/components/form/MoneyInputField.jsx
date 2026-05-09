import { Input } from 'antd';
import { useRef } from 'react';

export function formatCurrencyInput(value) {
    if (value === null || value === undefined || value === '') {
        return 'R$ 0,00';
    }

    const digits = String(value).replace(/\D/g, '');

    if (digits === '') {
        return 'R$ 0,00';
    }

    const numericValue = Number(digits) / 100;
    const [integerPart, decimalPart] = numericValue.toFixed(2).split('.');
    const formattedInteger = Number(integerPart).toLocaleString('pt-BR');

    return `R$ ${formattedInteger},${decimalPart}`;
}

export function parseCurrencyInput(value) {
    if (typeof value === 'number') {
        return value;
    }

    const digits = String(value).replace(/\D/g, '');

    if (digits === '') {
        return 0;
    }

    return Number(digits) / 100;
}

function extractDigits(value) {
    return String(value ?? '').replace(/\D/g, '');
}

function getNextFormattedValue(currentValue, nextDigits) {
    return formatCurrencyInput(nextDigits || currentValue);
}

export default function MoneyInputField({
    value,
    onChange,
    onKeyDown,
    onPaste,
    onFocus,
    onClick,
    placeholder = 'R$ 0,00',
    ariaLabel = 'Valor monetário',
    className,
    ...props
}) {
    const inputRef = useRef(null);

    function emitValue(nextValue) {
        onChange?.(formatCurrencyInput(nextValue));
    }

    function moveCaretToEnd(target) {
        window.requestAnimationFrame(() => {
            const input = target ?? inputRef.current?.input;

            if (!input || typeof input.setSelectionRange !== 'function') {
                return;
            }

            const end = input.value.length;
            input.setSelectionRange(end, end);
        });
    }

    function handleKeyDown(event) {
        onKeyDown?.(event);

        if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        if (event.key >= '0' && event.key <= '9') {
            event.preventDefault();
            emitValue(getNextFormattedValue(value, extractDigits(value) + event.key));
            return;
        }

        if (event.key === 'Backspace') {
            event.preventDefault();
            emitValue(getNextFormattedValue(value, extractDigits(value).slice(0, -1)));
            return;
        }

        if (event.key === 'Delete') {
            event.preventDefault();
            emitValue('R$ 0,00');
        }
    }

    function handlePaste(event) {
        onPaste?.(event);

        if (event.defaultPrevented) {
            return;
        }

        event.preventDefault();
        const pastedDigits = event.clipboardData?.getData('text')?.replace(/\D/g, '') ?? '';
        emitValue(pastedDigits);
    }

    function handleFocus(event) {
        onFocus?.(event);
        moveCaretToEnd(event.target);
    }

    function handleClick(event) {
        onClick?.(event);
        moveCaretToEnd(event.target);
    }

    function handleChange(event) {
        if (event.defaultPrevented) {
            return;
        }

        const nextValue = event?.target?.value ?? '';
        emitValue(nextValue);
        moveCaretToEnd(event.target);
    }

    return (
        <Input
            {...props}
            ref={inputRef}
            value={formatCurrencyInput(value)}
            onChange={handleChange}
            onKeyDown={handleKeyDown}
            onPaste={handlePaste}
            onFocus={handleFocus}
            onClick={handleClick}
            className={className}
            inputMode="numeric"
            placeholder={placeholder}
            aria-label={ariaLabel}
        />
    );
}
