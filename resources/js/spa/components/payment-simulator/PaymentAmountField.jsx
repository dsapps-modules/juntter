import { useEffect, useState } from 'react';
import MoneyInputField, { formatCurrencyInput, parseCurrencyInput } from '../form/MoneyInputField';

export default function PaymentAmountField({
    value,
    onChange,
    formatter,
    parser,
    align = 'left',
}) {
    const isRightAligned = align === 'right';
    const [displayValue, setDisplayValue] = useState(() => formatter ? formatter(value) : formatCurrencyInput(value));

    useEffect(() => {
        setDisplayValue(formatter ? formatter(value) : formatCurrencyInput(value));
    }, [formatter, value]);

    return (
        <MoneyInputField
            value={displayValue}
            onChange={(nextValue) => {
                setDisplayValue(nextValue);
                const parsedValue = parser ? parser(nextValue) : parseCurrencyInput(nextValue);
                onChange?.(typeof parsedValue === 'number' ? parsedValue : Number(parsedValue));
            }}
            className="spa-sim-input"
            style={{
                width: '100%',
                maxWidth: isRightAligned ? '260px' : '100%',
                minWidth: '200px',
            }}
            placeholder="0,00"
            ariaLabel="Informar valor da compra"
        />
    );
}
