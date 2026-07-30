import { Input } from 'antd';
import { useEffect, useRef, useState } from 'react';

function formatNumericInput(value, prefix) {
    if (value === null || value === undefined || value === '') {
        return `${prefix}0,00`;
    }

    const digits = String(value).replace(/\D/g, '');

    if (digits === '') {
        return `${prefix}0,00`;
    }

    const numericValue = Number(digits) / 100;
    const [integerPart, decimalPart] = numericValue.toFixed(2).split('.');
    const formattedInteger = Number(integerPart).toLocaleString('pt-BR');

    return `${prefix}${formattedInteger},${decimalPart}`;
}

export function formatCurrencyInput(value) {
    return formatNumericInput(value, 'R$ ');
}

export function formatPercentageInput(value) {
    return formatNumericInput(value, '');
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

function getNextFormattedValue(currentValue, nextDigits, formatter) {
    return formatter(nextDigits === '' ? '' : nextDigits || currentValue);
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
    showCurrencySymbol = true,
    ...props
}) {
    const inputRef = useRef(null);
    const formatter = showCurrencySymbol ? formatCurrencyInput : formatPercentageInput;
    const [digitsValue, setDigitsValue] = useState(() => extractDigits(value));
    const displayValue = formatter(digitsValue);

    useEffect(() => {
        setDigitsValue(extractDigits(value));
    }, [value]);

    function emitValue(nextValue) {
        const nextDigitsValue = extractDigits(nextValue);
        const nextFormattedValue = formatter(nextDigitsValue);

        setDigitsValue(nextDigitsValue);
        onChange?.(nextFormattedValue);
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
            emitValue(getNextFormattedValue(digitsValue, digitsValue + event.key, formatter));
            return;
        }

        if (event.key === 'Backspace') {
            event.preventDefault();
            emitValue(getNextFormattedValue(digitsValue, digitsValue.slice(0, -1), formatter));
            return;
        }

        if (event.key === 'Delete') {
            event.preventDefault();
            emitValue('');
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
        const nextDigitsValue = extractDigits(nextValue);

        if (nextDigitsValue === digitsValue) {
            moveCaretToEnd(event.target);
            return;
        }

        emitValue(nextValue);
        moveCaretToEnd(event.target);
    }

    return (
        <Input
            {...props}
            ref={inputRef}
            value={displayValue}
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
