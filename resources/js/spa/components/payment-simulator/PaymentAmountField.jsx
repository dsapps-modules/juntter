import { InputNumber, Typography } from 'antd';

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
            <InputNumber
                value={value}
                onChange={onChange}
                min={0}
                step={0.01}
                precision={2}
                formatter={formatter}
                parser={parser}
                className="spa-sim-input"
                style={{
                    flex: isRightAligned ? '0 0 260px' : '1 1 auto',
                    maxWidth: isRightAligned ? '260px' : '100%',
                    minWidth: '200px',
                }}
                placeholder="0,00"
                aria-label="Informar valor da compra"
            />
        </div>
    );
}
