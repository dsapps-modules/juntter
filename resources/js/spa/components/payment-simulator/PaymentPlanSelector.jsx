import { Select, Typography } from 'antd';

export default function PaymentPlanSelector({
    value,
    onChange,
    align = 'left',
    label = 'Plano considerado',
    options = [],
    ariaLabel = 'Selecionar opção da simulação',
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
                {label}
            </Typography.Text>
            <Select
                value={value}
                options={options}
                onChange={onChange}
                className="spa-sim-select"
                style={{
                    flex: isRightAligned ? '0 0 260px' : '1 1 auto',
                    maxWidth: isRightAligned ? '260px' : '100%',
                    minWidth: '200px',
                }}
                aria-label={ariaLabel}
            />
        </div>
    );
}
