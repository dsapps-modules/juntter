import { Typography } from 'antd';
import MoneyInputField, { formatCurrencyInput, parseCurrencyInput } from '../form/MoneyInputField';

export default function PaymentAmountField({
    value,
    onChange,
    formatter,
    parser,
    align = 'left',
}) {
    const isRightAligned = align === 'right';

    return (
        <div
            className="spa-sim-field"
            style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                gap: '16px',
                width: '100%',
            }}
        >
            <Typography.Text
                strong
                className="spa-sim-label"
                style={{
                    flex: '0 0 auto',
                    textAlign: 'left',
                    whiteSpace: 'nowrap',
                }}
            >
                Valor
            </Typography.Text>
            <MoneyInputField
                value={formatter ? formatter(value) : formatCurrencyInput(value)}
                onChange={(nextValue) => {
                    const parsedValue = parser ? parser(nextValue) : parseCurrencyInput(nextValue);
                    onChange?.(typeof parsedValue === 'number' ? parsedValue : Number(parsedValue));
                }}
                className="spa-sim-input"
                style={{
                    flex: isRightAligned ? '0 0 260px' : '1 1 auto',
                    maxWidth: isRightAligned ? '260px' : '100%',
                    minWidth: '200px',
                }}
                placeholder="0,00"
                ariaLabel="Informar valor da compra"
            />
        </div>
    );
}
